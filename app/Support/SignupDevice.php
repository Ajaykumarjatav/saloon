<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\Request;

/** Classify the device used at tenant registration from the User-Agent. */
final class SignupDevice
{
    public static function attributesFromRequest(Request $request): array
    {
        $ua = substr((string) $request->userAgent(), 0, 500);

        return [
            'signup_device' => self::labelFromUserAgent($ua),
            'signup_user_agent' => $ua !== '' ? $ua : null,
        ];
    }

    public static function labelFromUserAgent(?string $ua): string
    {
        $ua = strtolower(trim((string) $ua));
        if ($ua === '') {
            return 'Unknown';
        }

        if (
            str_contains($ua, 'okhttp')
            || str_contains($ua, 'dart/')
            || str_contains($ua, 'expo')
            || str_contains($ua, 'reactnative')
            || str_contains($ua, 'cfnetwork')
        ) {
            return 'App';
        }

        if (
            str_contains($ua, 'ipad')
            || str_contains($ua, 'tablet')
            || (str_contains($ua, 'android') && ! str_contains($ua, 'mobile'))
        ) {
            return 'Tablet';
        }

        if (
            str_contains($ua, 'mobi')
            || str_contains($ua, 'iphone')
            || str_contains($ua, 'ipod')
            || str_contains($ua, 'android')
            || str_contains($ua, 'windows phone')
        ) {
            return 'Mobile';
        }

        return 'Desktop';
    }
}
