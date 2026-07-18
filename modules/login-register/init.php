<?php
/**
 * Module: Login Register
 * Description: Front-end login, registration, and email confirmation flow for Almaden Bookster.
 */

if (!defined('ABSPATH')) {
    exit;
}

use AlmadenBookster\Auth\AuthOrchestrator;
use AlmadenBookster\Auth\PasswordPage;

define('ALMADEN_BOOKSTER_AUTH_PATH', plugin_dir_path(__FILE__));
define('ALMADEN_BOOKSTER_AUTH_URL', plugin_dir_url(__FILE__));

spl_autoload_register(function ($class) {
    if (strpos($class, 'AlmadenBookster\\Auth\\') === 0) {
        $relative_class = substr($class, 21);
        $file = ALMADEN_BOOKSTER_AUTH_PATH . 'includes/Auth/' . str_replace('\\', '/', $relative_class) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
        return;
    }

    if (strpos($class, 'PL_Auth_') !== 0) {
        return;
    }

    $file = ALMADEN_BOOKSTER_AUTH_PATH . 'includes/class-' . strtolower(str_replace('_', '-', $class)) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

if (!class_exists(__NAMESPACE__ . '\\Module', false)) {
    final class Module {
        public static function activate(): void
        {
            if (class_exists(PasswordPage::class)) {
                PasswordPage::activate();
            }
        }

        public static function init(): void
        {
            add_action('plugins_loaded', [__CLASS__, 'register'], 20);

            if (did_action('plugins_loaded')) {
                self::register();
            }
        }

        public static function register(): void
        {
            if (class_exists(AuthOrchestrator::class)) {
                AuthOrchestrator::get_instance();
            }
        }
    }
}

Module::init();
