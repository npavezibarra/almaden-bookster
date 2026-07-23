<?php

namespace AlmadenBookster\Auth\Utilities;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Utility functions for Authentication.
 */
class AuthUtils
{
    /**
     * Builds the URL for the auth modal.
     */
    public static function build_modal_url(string $view, string $redirect_to = '', array $args = []): string
    {
        $view = self::sanitize_view($view);
        $redirect_to = self::resolve_redirect_to($redirect_to);
        $redirect_to_clean = remove_query_arg([
            'pl_auth_view',
            'pl_auth_notice',
            'pl_auth_error',
            'pl_auth_unverified',
            'pl_auth_unverified_after_quiz',
            'redirect_to',
        ], $redirect_to);

        $modal_base_url = self::is_admin_like_url($redirect_to_clean)
            ? home_url('/')
            : $redirect_to_clean;
        $modal_base_url = remove_query_arg([
            'pl_auth_view',
            'pl_auth_notice',
            'pl_auth_error',
            'pl_auth_unverified',
            'pl_auth_unverified_after_quiz',
            'redirect_to',
        ], $modal_base_url);
        if ($modal_base_url === '') {
            $modal_base_url = home_url('/');
        }

        $query_args = array_merge([
            'pl_auth_view' => $view,
            'redirect_to' => $redirect_to_clean,
        ], $args);

        // Keep users on the page they were on; the modal is global (footer/body injection).
        return add_query_arg($query_args, $modal_base_url);
    }

    /**
     * Resolves and validates the redirect_to URL.
     */
    public static function resolve_redirect_to(string $redirect_to): string
    {
        $redirect_to = trim($redirect_to);
        if ($redirect_to === '' || self::is_admin_post_url($redirect_to)) {
            $referer = '';
            if (function_exists('wp_get_raw_referer')) {
                $referer = (string) wp_get_raw_referer();
            }

            if ($referer === '' && isset($_SERVER['HTTP_REFERER'])) {
                $referer = (string) wp_unslash($_SERVER['HTTP_REFERER']);
            }

            if ($referer !== '' && !self::is_admin_post_url($referer)) {
                $redirect_to = $referer;
            } else {
                $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '/';
                $request_url = home_url($request_uri ?: '/');
                if (self::is_admin_post_url($request_url)) {
                    $request_url = home_url('/');
                }
                $redirect_to = $request_url;
            }
        }

        $redirect_to = remove_query_arg(['pl_auth_view', 'pl_auth_notice', 'pl_auth_error', 'pl_auth_unverified', 'pl_auth_unverified_after_quiz'], $redirect_to);
        $redirect_to = wp_validate_redirect($redirect_to, home_url('/'));

        if (self::is_admin_post_url($redirect_to)) {
            return home_url('/');
        }

        $redirect_to = remove_query_arg(['redirect_to'], $redirect_to);

        return $redirect_to;
    }

    /**
     * Returns the current public URL where the auth form should post back.
     */
    public static function current_page_url(): string
    {
        $referer = '';
        if (function_exists('wp_get_raw_referer')) {
            $referer = (string) wp_get_raw_referer();
        }

        if ($referer === '' && isset($_SERVER['HTTP_REFERER'])) {
            $referer = (string) wp_unslash($_SERVER['HTTP_REFERER']);
        }

        if ($referer !== '' && !self::is_admin_post_url($referer)) {
            return self::normalize_public_url($referer);
        }

        $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '/';
        $current_url = home_url($request_uri ?: '/');

        return self::normalize_public_url($current_url);
    }

    /**
     * Detects admin-post URLs so auth redirects never land there.
     */
    public static function is_admin_post_request(): bool
    {
        $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
        return self::is_admin_post_url($request_uri);
    }

    private static function is_admin_post_url(string $url): bool
    {
        if ($url === '') {
            return false;
        }

        $parsed_path = (string) wp_parse_url($url, PHP_URL_PATH);
        if ($parsed_path === '') {
            return false;
        }

        return strpos($parsed_path, '/wp-admin/admin-post.php') !== false;
    }

    /**
     * Detects URLs that should not act as the visible shell for the auth modal.
     */
    private static function is_admin_like_url(string $url): bool
    {
        if ($url === '') {
            return false;
        }

        $parsed_path = (string) wp_parse_url($url, PHP_URL_PATH);
        if ($parsed_path === '') {
            return false;
        }

        return strpos($parsed_path, '/wp-admin/') !== false || strpos($parsed_path, '/wp-login.php') !== false;
    }

    /**
     * Normalizes a public URL and strips auth-only query args.
     */
    private static function normalize_public_url(string $url): string
    {
        $url = remove_query_arg(['pl_auth_view', 'pl_auth_notice', 'pl_auth_error', 'pl_auth_unverified', 'pl_auth_unverified_after_quiz'], $url);
        $url = wp_validate_redirect($url, home_url('/'));

        if (self::is_admin_post_url($url)) {
            return home_url('/');
        }

        return $url;
    }

    /**
     * Sanitizes the auth view name.
     */
    public static function sanitize_view(string $view): string
    {
        $view = sanitize_key($view);
        if (!in_array($view, ['login', 'register', 'forgot'], true)) {
            return 'login';
        }

        return $view;
    }

    /**
     * Generates a unique username from an email address.
     */
    public static function generate_username_from_email(string $email): string
    {
        $base = sanitize_user(strstr($email, '@', true) ?: $email, true);
        if ($base === '') {
            $base = 'user';
        }

        $username = $base;
        $i = 1;
        while (username_exists($username)) {
            $username = $base . $i;
            $i++;
        }

        return $username;
    }
}
