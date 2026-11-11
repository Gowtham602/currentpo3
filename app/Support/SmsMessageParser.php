<?php

namespace App\Support;

class SmsMessageParser
{
    public static function parse(?string $message): array
    {
        // Handle Excel non-breaking spaces and extra whitespace
        $message = (string) $message;

        $message = preg_replace(
            '/[\p{Z}\s]+/u',
            ' ',
            trim($message)
        );

        // 1. Points Added
        if (preg_match('/POINTS\s+ADDED\s*:/iu', $message)) {
            preg_match(
                '/BRANCH\s*:\s*([A-Z]+)/iu',
                $message,
                $branchMatch
            );

            return [
                'message_type' => 'points_added',
                'branch' => strtoupper($branchMatch[1] ?? ''),
            ];
        }

        // 2. Points Redeemed
        if (preg_match('/POINTS\s+REDEEM\s*:/iu', $message)) {
            preg_match(
                '/BRANCH\s*:\s*([A-Z]+)/iu',
                $message,
                $branchMatch
            );

            return [
                'message_type' => 'points_redeemed',
                'branch' => strtoupper($branchMatch[1] ?? ''),
            ];
        }

        // 3. Points Returned
        if (preg_match('/POINTS\s+RETURNED\s*:/iu', $message)) {
            preg_match(
                '/BRANCH\s*:\s*([A-Z]+)/iu',
                $message,
                $branchMatch
            );

            return [
                'message_type' => 'points_returned',
                'branch' => strtoupper($branchMatch[1] ?? ''),
            ];
        }

        // 4. Redeem OTP
        if (
            preg_match(
                '/GREENCARD\s+([A-Z]+)\s+REDEEM/iu',
                $message,
                $matches
            )
        ) {
            return [
                'message_type' => 'redeem_otp',
                'branch' => strtoupper($matches[1]),
            ];
        }

        // 5. Profile Update OTP
        if (
            preg_match(
                '/GREENCARD\s+([A-Z]+)\s+PROFILE\s+UPDATE/iu',
                $message,
                $matches
            )
        ) {
            return [
                'message_type' => 'otp_profile_updated',
                'branch' => strtoupper($matches[1]),
            ];
        }

        return [
            'message_type' => null,
            'branch' => null,
        ];
    }
}
