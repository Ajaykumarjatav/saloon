<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\SendWhatsAppNotification;
use App\Models\Salon;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TenantWelcomeWhatsAppService
{
    public const FROM_E164 = '+919950105679';

    public function __construct(private readonly AuditLogService $audit) {}

    public function isSent(User $account): bool
    {
        return $account->welcome_whatsapp_sent_at !== null;
    }

    public function recipientPhone(User $account): ?string
    {
        $account->loadMissing(['salons' => fn ($q) => $q->withoutGlobalScopes()->select('id', 'owner_id', 'phone', 'whatsapp_number', 'whatsapp_same_as_phone')]);

        $candidates = [
            $account->phone,
            $account->salons->first()?->whatsappNumberForSite(),
            $account->salons->first()?->phone,
        ];

        foreach ($candidates as $raw) {
            $e164 = self::toE164((string) $raw);
            if ($e164) {
                return $e164;
            }
        }

        return null;
    }

    /**
     * @return array{status: string, message: string, phone?: string}
     */
    public function sendOrConfirm(User $account, bool $markOnly = false): array
    {
        return DB::transaction(function () use ($account, $markOnly) {
            /** @var User $locked */
            $locked = User::query()->where('id', $account->id)->lockForUpdate()->firstOrFail();

            if ($locked->welcome_whatsapp_sent_at !== null) {
                return [
                    'status' => 'already_sent',
                    'message' => 'Welcome WhatsApp was already sent for this account. The button has been hidden.',
                ];
            }

            $phone = $this->recipientPhone($locked);

            if ($markOnly) {
                $this->markSent($locked);
                $this->audit->admin(
                    'tenant.welcome_whatsapp.marked',
                    'Welcome WhatsApp marked as already sent for '.$locked->email,
                    $locked,
                    ['user_id' => $locked->id]
                );

                return [
                    'status' => 'already_sent',
                    'message' => 'Marked as already sent. The welcome WhatsApp button is now hidden.',
                    'phone' => $phone,
                ];
            }

            if ($phone === null) {
                throw new RuntimeException('No mobile number on this account or store. Add a phone in Settings, then try again.');
            }

            $from = (string) config('services.twilio.whatsapp_from', 'whatsapp:'.self::FROM_E164);
            if (! config('services.twilio.sid') || ! config('services.twilio.token') || $from === '') {
                throw new RuntimeException('WhatsApp sending is not configured. Set Twilio SID, token, and TWILIO_WHATSAPP_FROM.');
            }

            (new SendWhatsAppNotification(
                $phone,
                $this->messageBody($locked),
                null,
                $from
            ))->handle();

            $this->markSent($locked);
            $this->audit->admin(
                'tenant.welcome_whatsapp.sent',
                'Welcome WhatsApp sent to '.$phone.' for '.$locked->email,
                $locked,
                ['user_id' => $locked->id, 'phone' => $phone]
            );

            return [
                'status' => 'sent',
                'message' => 'Welcome WhatsApp sent from +91 99501 05679.',
                'phone' => $phone,
            ];
        });
    }

    public static function toE164(string $raw): ?string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';
        if ($digits === '') {
            return null;
        }
        if (strlen($digits) === 10) {
            $digits = '91'.$digits;
        }
        if (strlen($digits) < 10) {
            return null;
        }

        return '+'.$digits;
    }

    private function markSent(User $account): void
    {
        $account->forceFill([
            'welcome_whatsapp_sent_at' => now(),
            'welcome_whatsapp_sent_by' => Auth::id(),
        ])->save();
    }

    private function messageBody(User $account): string
    {
        $salon = Salon::withoutGlobalScopes()->where('owner_id', $account->id)->orderBy('id')->first();
        $salonName = $salon?->name ?: 'your business';
        $loginUrl = rtrim((string) config('app.url'), '/').'/login';

        return "Hi {$account->name}, welcome to EasyGrox!\n\n"
            ."Your account for {$salonName} is ready. Log in here:\n{$loginUrl}\n\n"
            .'If you need help getting started, reply to this chat.';
    }
}
