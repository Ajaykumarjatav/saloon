<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Notifications\Messages\MailMessage;

/** Platform support contact shown on tenant-facing emails. */
final class SupportContact
{
    public static function phoneDisplay(): string
    {
        return (string) config('mail.support_phone', '+91 99501 05679');
    }

    public static function phoneTelHref(): string
    {
        $digits = preg_replace('/\D+/', '', self::phoneDisplay()) ?: '919950105679';

        return 'tel:+'.$digits;
    }

    public static function phoneWhatsAppHref(): string
    {
        $digits = preg_replace('/\D+/', '', self::phoneDisplay()) ?: '919950105679';

        return 'https://wa.me/'.$digits;
    }

    public static function helpLineHtml(): string
    {
        $phone = e(self::phoneDisplay());
        $tel = e(self::phoneTelHref());
        $wa = e(self::phoneWhatsAppHref());

        return 'Need help? Call or WhatsApp <a href="'.$tel.'" style="color:inherit;text-decoration:underline;">'.$phone.'</a>'
            .' · <a href="'.$wa.'" style="color:inherit;text-decoration:underline;">Chat on WhatsApp</a>';
    }

    public static function appendToMailMessage(MailMessage $mail): MailMessage
    {
        return $mail
            ->line('Need help? Call or WhatsApp '.self::phoneDisplay().'.')
            ->salutation("Regards,\nEasyGrox");
    }
}
