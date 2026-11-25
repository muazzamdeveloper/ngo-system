<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends plain-text mail. When SMTP is configured in Email & Notifications, it is used for
 * official mail; otherwise the application's default mailer is used.
 */
class MailSender
{
    public static function smtpConfigured(): bool
    {
        return filled(SiteSetting::get('smtp.host')) && filled(SiteSetting::get('smtp.from_address'));
    }

    /** Builds a mailer from the saved SMTP settings (or returns the default mailer). */
    public static function mailer(bool $viaSmtp)
    {
        if (! $viaSmtp || ! self::smtpConfigured()) {
            return Mail::mailer();
        }

        return Mail::build([
            'transport' => 'smtp',
            'scheme' => SiteSetting::get('smtp.encryption') === 'ssl' ? 'smtps' : null,
            'url' => null,
            'host' => SiteSetting::get('smtp.host'),
            'port' => (int) (SiteSetting::get('smtp.port') ?: 587),
            'username' => SiteSetting::get('smtp.username'),
            'password' => SiteSetting::get('smtp.password'),
            'timeout' => 15,
            'local_domain' => parse_url((string) config('app.url'), PHP_URL_HOST) ?: null,
            'stream' => ['ssl' => ['verify_peer' => true, 'verify_peer_name' => true]],
        ]);
    }

    /**
     * Sends one text message to each address. Returns [sent, failed]. A single failure never
     * stops the batch; failures are logged without the recipient addresses.
     */
    public static function send(array $addresses, string $subject, string $body, bool $viaSmtp, ?string $from = null): array
    {
        $mailer = self::mailer($viaSmtp);
        $sent = 0;
        $failed = 0;

        $fromAddress = SiteSetting::get('smtp.from_address') ?: config('mail.from.address');
        $fromName = SiteSetting::get('smtp.from_name') ?: config('mail.from.name');

        foreach ($addresses as $address) {
            try {
                $mailer->raw($body, function ($message) use ($address, $subject, $fromAddress, $fromName) {
                    $message->to($address)->subject($subject)->from($fromAddress, $fromName);
                });
                $sent++;
            } catch (Throwable $e) {
                $failed++;
                Log::warning('Mail send failed', ['error' => class_basename($e)]);
            }
        }

        return [$sent, $failed];
    }
}
