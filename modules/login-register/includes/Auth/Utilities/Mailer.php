<?php

namespace AlmadenBookster\Auth\Utilities;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Minimal HTML mailer for auth flows.
 */
final class Mailer
{
    public static function send_auth_confirmation(string $email, string $display_name, string $verification_url, string $token): bool
    {
        $subject = sprintf(
            /* translators: %s site name */
            __('Confirm your email for %s', 'almaden-bookster'),
            wp_specialchars_decode((string) get_bloginfo('name'), ENT_QUOTES)
        );

        $html = self::render('auth-confirmation', [
            'display_name' => $display_name,
            'verification_url' => $verification_url,
            'token' => $token,
        ]);

        return (bool) wp_mail(
            $email,
            $subject,
            $html,
            ['Content-Type: text/html; charset=UTF-8']
        );
    }

    public static function render(string $template, array $context = []): string
    {
        $template = sanitize_key($template);

        if ($template === 'password-reset') {
            return self::render_password_reset($context);
        }

        if ($template === 'auth-confirmation') {
            return self::render_auth_confirmation($context);
        }

        return '';
    }

    private static function render_auth_confirmation(array $context): string
    {
        $display_name = trim((string) ($context['display_name'] ?? ''));
        $verification_url = esc_url((string) ($context['verification_url'] ?? ''));
        $token = sanitize_text_field((string) ($context['token'] ?? ''));

        ob_start();
        ?>
        <div style="background:#f8fafc;padding:32px 20px;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
            <div style="max-width:600px;margin:0 auto;background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:32px;">
                <p style="margin:0 0 12px;font-size:12px;letter-spacing:.18em;text-transform:uppercase;color:#64748b;">Almaden Bookster</p>
                <h1 style="margin:0 0 16px;font-size:28px;line-height:1.1;"><?php echo esc_html__('Confirm your email', 'almaden-bookster'); ?></h1>
                <p style="margin:0 0 18px;font-size:15px;line-height:1.7;">
                    <?php echo esc_html($display_name !== '' ? sprintf(__('Hi %s, thanks for joining.', 'almaden-bookster'), $display_name) : __('Thanks for joining.', 'almaden-bookster')); ?>
                </p>
                <p style="margin:0 0 24px;font-size:15px;line-height:1.7;"><?php echo esc_html__('Use the button below to confirm your account.', 'almaden-bookster'); ?></p>
                <p style="margin:0 0 24px;">
                    <a href="<?php echo $verification_url; ?>" style="display:inline-block;background:#111827;color:#fff;text-decoration:none;padding:14px 22px;border-radius:999px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;"><?php echo esc_html__('Confirm email', 'almaden-bookster'); ?></a>
                </p>
                <p style="margin:0 0 8px;font-size:13px;line-height:1.6;color:#475569;"><?php echo esc_html__('If the button does not work, paste this token on the confirmation screen:', 'almaden-bookster'); ?></p>
                <p style="margin:0;padding:12px 14px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;font-family:monospace;font-size:13px;word-break:break-all;"><?php echo esc_html($token); ?></p>
            </div>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    private static function render_password_reset(array $context): string
    {
        $user_login = trim((string) ($context['user_login'] ?? ''));
        $reset_url = esc_url((string) ($context['reset_url'] ?? ''));

        ob_start();
        ?>
        <div style="background:#f8fafc;padding:32px 20px;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
            <div style="max-width:600px;margin:0 auto;background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:32px;">
                <p style="margin:0 0 12px;font-size:12px;letter-spacing:.18em;text-transform:uppercase;color:#64748b;">Almaden Bookster</p>
                <h1 style="margin:0 0 16px;font-size:28px;line-height:1.1;"><?php echo esc_html__('Reset your password', 'almaden-bookster'); ?></h1>
                <p style="margin:0 0 18px;font-size:15px;line-height:1.7;">
                    <?php echo esc_html($user_login !== '' ? sprintf(__('We received a password reset request for %s.', 'almaden-bookster'), $user_login) : __('We received a password reset request.', 'almaden-bookster')); ?>
                </p>
                <p style="margin:0 0 24px;">
                    <a href="<?php echo $reset_url; ?>" style="display:inline-block;background:#111827;color:#fff;text-decoration:none;padding:14px 22px;border-radius:999px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;"><?php echo esc_html__('Create new password', 'almaden-bookster'); ?></a>
                </p>
                <p style="margin:0;font-size:13px;line-height:1.6;color:#475569;"><?php echo esc_html__('If the button does not work, open this link in your browser:', 'almaden-bookster'); ?></p>
                <p style="margin:8px 0 0;word-break:break-all;"><a href="<?php echo $reset_url; ?>"><?php echo esc_html($reset_url); ?></a></p>
            </div>
        </div>
        <?php
        return (string) ob_get_clean();
    }
}
