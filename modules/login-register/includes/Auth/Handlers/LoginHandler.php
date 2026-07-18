<?php

namespace AlmadenBookster\Auth\Handlers;

use AlmadenBookster\Auth\Utilities\AuthUtils;
use WP_User;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles login requests.
 */
class LoginHandler
{
    /**
     * Sends the browser to a URL, even if headers were already flushed.
     */
    private static function redirect_to(string $url): void
    {
        $url = wp_validate_redirect($url, home_url('/'));

        if (headers_sent()) {
            nocache_headers();
            echo '<!doctype html><html><head><meta charset="utf-8"><meta http-equiv="refresh" content="0;url=' . esc_url($url) . '"><script>window.location.replace(' . wp_json_encode($url) . ');</script></head><body></body></html>';
            exit;
        }

        wp_safe_redirect($url);
        exit;
    }

    /**
     * Maps WP login error codes to UI-safe codes.
     */
    private static function normalize_error_code(string $code): string
    {
        // Never reveal whether the username/email or password was wrong.
        $generic_credential_codes = [
            'invalid_username',
            'incorrect_password',
            'invalid_email',
            'invalidcombo',
            'authentication_failed',
        ];

        if (in_array($code, $generic_credential_codes, true)) {
            return 'invalid_credentials';
        }

        if ($code === 'empty_username' || $code === 'empty_password') {
            return 'missing_login';
        }

        return $code !== '' ? $code : 'invalid_login';
    }

    /**
     * Processes a login request.
     */
    public static function handle(string $redirect_to): void
    {
        $login_or_email = '';
        if (isset($_POST['user_login'])) {
            $login_or_email = sanitize_text_field((string) wp_unslash($_POST['user_login']));
        } elseif (isset($_POST['email'])) {
            $login_or_email = sanitize_text_field((string) wp_unslash($_POST['email']));
        }
        
        $password = isset($_POST['password']) ? (string) wp_unslash($_POST['password']) : '';
        $remember = !empty($_POST['remember']);

        if ($login_or_email === '' || $password === '') {
            self::redirect_to(AuthUtils::build_modal_url('login', $redirect_to, ['pl_auth_error' => 'missing_login']));
        }

        if (is_email($login_or_email)) {
            $user = get_user_by('email', $login_or_email);
            if ($user instanceof WP_User && isset($user->user_login)) {
                $login_or_email = (string) $user->user_login;
            }
        }

        $user = wp_signon([
            'user_login' => $login_or_email,
            'user_password' => $password,
            'remember' => $remember,
        ], is_ssl());

        if (is_wp_error($user)) {
            $code = self::normalize_error_code((string) $user->get_error_code());
            self::redirect_to(AuthUtils::build_modal_url('login', $redirect_to, ['pl_auth_error' => $code]));
        }

        $redirect_to = remove_query_arg(['pl_auth_notice', 'pl_auth_error', 'pl_auth_view'], $redirect_to);
        $redirect_to = add_query_arg(['pl_auth_notice' => 'login_success'], $redirect_to);
        self::redirect_to($redirect_to);
    }
}
