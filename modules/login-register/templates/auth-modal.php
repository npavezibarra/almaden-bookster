<?php
/**
 * Login/register modal template.
 */

if (!defined('ABSPATH')) {
    exit;
}

$auto_open = !empty($auto_open);
$is_spanish = strpos(get_locale(), 'es') === 0;
$contractor_company_name = function_exists( 'almaden_bookster_get_contractor_company_name' ) ? trim( (string) almaden_bookster_get_contractor_company_name() ) : '';
$contractor_logo_url = function_exists( 'almaden_bookster_get_contractor_logo_url' ) ? trim( (string) almaden_bookster_get_contractor_logo_url() ) : '';
$contractor_logo_width = function_exists( 'almaden_bookster_get_contractor_logo_width' ) ? absint( almaden_bookster_get_contractor_logo_width() ) : 160;
if ( $contractor_logo_width < 40 ) {
    $contractor_logo_width = 40;
}
if ( $contractor_logo_width > 300 ) {
    $contractor_logo_width = 300;
}
$brand_label = '' !== $contractor_company_name ? $contractor_company_name : 'almaden';

$labels = [
    'welcome' => $is_spanish ? 'Bienvenido de nuevo' : 'Welcome back',
    'register_title' => $is_spanish ? 'Crea tu cuenta' : 'Create your account',
    'forgot_title' => $is_spanish ? 'Olvidé contraseña' : 'Forgot password',
    'login_copy' => $is_spanish ? 'Inicia sesión para continuar o crea una nueva cuenta.' : 'Log in to continue or create a new account.',
    'register_copy' => $is_spanish ? 'Crea una cuenta para recibir tu email de confirmación.' : 'Create an account and we will send you a confirmation email.',
    'forgot_copy' => $is_spanish ? 'Ingresa tu correo electrónico para restablecer tu contraseña.' : 'Enter your email to reset your password.',
    'login' => $is_spanish ? 'Ingresar' : 'Login',
    'register' => $is_spanish ? 'Registrarse' : 'Register',
    'first_name' => $is_spanish ? 'Nombre' : 'First name',
    'last_name' => $is_spanish ? 'Apellido' : 'Last name',
    'email' => $is_spanish ? 'Correo electrónico' : 'Email',
    'login_identifier' => $is_spanish ? 'Correo o usuario' : 'Email or username',
    'confirm_email' => $is_spanish ? 'Confirmar correo' : 'Confirm email',
    'password' => $is_spanish ? 'Contraseña' : 'Password',
    'confirm_password' => $is_spanish ? 'Confirmar contraseña' : 'Confirm password',
    'remember_me' => $is_spanish ? 'Recuérdame' : 'Remember me',
    'create_account' => $is_spanish ? 'Crear cuenta' : 'Create account',
    'new_here' => $is_spanish ? '¿No tienes cuenta?' : 'New here?',
    'create_account_link' => $is_spanish ? 'Crea una cuenta' : 'Create an account',
    'already_account' => $is_spanish ? '¿Ya tienes cuenta?' : 'Already have an account?',
    'back_to_login' => $is_spanish ? 'Inicia sesión' : 'Back to login',
    'forgot_link' => $is_spanish ? 'Olvidé mi contraseña' : 'Forgot password',
];

$login_url = \AlmadenBookster\Auth\Utilities\AuthUtils::build_modal_url('login', $redirect_to);
$register_url = \AlmadenBookster\Auth\Utilities\AuthUtils::build_modal_url('register', $redirect_to);
?>
<style id="pl-auth-critical-css">
    #pl-auth-overlay {
        position: fixed;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 24px;
        background: rgb(0 0 0 / 68%);
        backdrop-filter: blur(10px);
        z-index: 9999;
        opacity: 0;
        visibility: hidden;
        transition: opacity 180ms ease, visibility 180ms ease;
        font-family: Poppins, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        box-sizing: border-box;
    }
    #pl-auth-overlay * {
        box-sizing: border-box;
    }
    #pl-auth-overlay.is-open {
        opacity: 1;
        visibility: visible;
    }
    #pl-auth-card {
        position: relative;
        width: min(100%, 400px);
        max-height: min(90vh, 760px);
        overflow: auto;
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 18px 70px rgb(0 0 0 / 24%);
        transform: translateY(12px) scale(0.98);
        transition: transform 180ms ease;
    }
    #pl-auth-overlay.is-open #pl-auth-card {
        transform: translateY(0) scale(1);
    }
    .pl-auth-shell {
        padding: 40px;
    }
    .pl-auth-close {
        position: absolute;
        top: 14px;
        right: 14px;
        width: 40px;
        height: 40px;
        border: 0;
        border-radius: 999px;
        background: transparent;
        color: #000;
        cursor: pointer;
        font-size: 24px;
        line-height: 1;
        z-index: 3;
    }
    .pl-auth-loading {
        position: absolute;
        inset: 0;
        display: none;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        gap: 14px;
        padding: 26px;
        background: rgb(248 250 252 / 92%);
        backdrop-filter: blur(6px);
        z-index: 2;
        text-align: center;
    }
    #pl-auth-overlay.is-loading .pl-auth-loading {
        display: flex;
    }
    .pl-auth-loading__spinner {
        width: 34px;
        height: 34px;
        border-radius: 999px;
        border: 3px solid rgb(15 23 42 / 14%);
        border-top-color: rgb(15 23 42 / 78%);
        animation: plAuthSpin 0.8s linear infinite;
    }
    @keyframes plAuthSpin {
        to { transform: rotate(360deg); }
    }
    .pl-auth-eyebrow,
    .pl-auth-logo,
    .pl-auth-tabs {
        display: none;
    }
    .pl-auth-title {
        margin: 0 0 8px;
        color: #000;
        font-size: 1.75rem;
        font-weight: 600;
        line-height: 1.05;
        letter-spacing: -0.02em;
        text-transform: uppercase;
    }
    .pl-auth-copy {
        margin: 0 0 40px;
        color: #666;
        font-size: 0.85rem;
        line-height: 1.45;
    }
    .pl-auth-field {
        position: relative;
        margin-bottom: 30px;
    }
    .pl-auth-field label {
        display: block;
        margin-bottom: 2px;
        color: #000;
        font-size: 0.7rem;
        font-weight: 600;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }
    .pl-auth-field input {
        width: 100%;
        border: 0;
        border-bottom: 2px solid #e0e0e0;
        border-radius: 0;
        padding: 8px 0;
        background: transparent;
        color: #000;
        font-size: 0.95rem;
        outline: none;
    }
    .pl-auth-submit {
        width: 100%;
        margin-top: 10px;
        border: 1px solid #000;
        border-radius: 6px;
        padding: 8px;
        background: #000;
        color: #fff;
        cursor: pointer;
        font-size: 13px;
        font-weight: 500;
        letter-spacing: 2px;
        text-transform: uppercase;
    }
    .pl-auth-footer {
        margin-top: 30px;
        text-align: center;
        color: #666;
        font-size: 0.75rem;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }
    .pl-auth-hidden {
        display: none !important;
    }
</style>
<div id="pl-auth-overlay" data-initial-view="<?php echo esc_attr($view); ?>" data-notice="<?php echo esc_attr($notice); ?>" data-error="<?php echo esc_attr($error); ?>" data-auto-open="<?php echo esc_attr($auto_open ? '1' : '0'); ?>">
    <div id="pl-auth-card" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr__('Authentication', 'almaden-bookster'); ?>">
        <div class="pl-auth-loading" data-pl-auth-loading aria-hidden="true">
            <div class="pl-auth-loading__spinner" aria-hidden="true"></div>
            <div class="pl-auth-loading__text"><?php echo esc_html($is_spanish ? 'Creando cuenta…' : 'Creating account…'); ?></div>
        </div>
        <button class="pl-auth-close" type="button" data-pl-auth-close aria-label="<?php echo esc_attr__('Close', 'almaden-bookster'); ?>">×</button>
        <div class="pl-auth-shell">
            <p class="pl-auth-eyebrow"><?php echo esc_html__('Almaden Bookster', 'almaden-bookster'); ?></p>
            <div class="pl-auth-logo" data-pl-auth-logo>
                <?php if ( '' !== $contractor_logo_url ) : ?>
                    <img
                        src="<?php echo esc_url( $contractor_logo_url ); ?>"
                        alt="<?php echo esc_attr( $brand_label ); ?>"
                        class="pl-auth-logo__image"
                        style="width: <?php echo esc_attr( (string) $contractor_logo_width ); ?>px; max-width: <?php echo esc_attr( (string) $contractor_logo_width ); ?>px;"
                    />
                <?php else : ?>
                    <span class="urbanist-almaden-logo pl-auth-logo__wordmark"><?php echo esc_html( $brand_label ); ?></span>
                <?php endif; ?>
            </div>
            <h2 class="pl-auth-title" data-pl-auth-title><?php echo esc_html($view === 'register' ? $labels['register_title'] : $labels['welcome']); ?></h2>
            <p class="pl-auth-copy" data-pl-auth-copy><?php echo esc_html($view === 'register' ? $labels['register_copy'] : $labels['login_copy']); ?></p>

            <div class="pl-auth-message" data-pl-auth-message>
                <span class="material-symbols-outlined pl-auth-message__icon" aria-hidden="true">warning</span>
                <span class="pl-auth-message__text" data-pl-auth-message-text></span>
            </div>

            <div class="pl-auth-tabs">
                <button class="pl-auth-tab is-active" type="button" data-pl-auth-view="login"><?php echo esc_html($labels['login']); ?></button>
                <button class="pl-auth-tab" type="button" data-pl-auth-view="register"><?php echo esc_html($labels['register']); ?></button>
            </div>

            <form class="pl-auth-form" method="post" action="<?php echo esc_url($action_url); ?>" data-pl-auth-form>
                <input type="hidden" name="action" value="pl_auth_submit">
                <input type="hidden" name="pl_auth_nonce" value="<?php echo esc_attr($nonce); ?>">
                <input type="hidden" name="mode" value="<?php echo esc_attr($view); ?>" data-pl-auth-mode>
                <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>" data-pl-auth-redirect>

                <?php include ALMADEN_BOOKSTER_AUTH_PATH . 'templates/auth/parts/register-fields.php'; ?>

                <div class="pl-auth-field">
                    <label for="pl-auth-email" data-pl-auth-email-label><?php echo esc_html($view === 'register' ? $labels['email'] : $labels['login_identifier']); ?></label>
                    <input
                        id="pl-auth-email"
                        name="user_login"
                        type="<?php echo esc_attr($view === 'register' ? 'email' : 'text'); ?>"
                        autocomplete="<?php echo esc_attr($view === 'register' ? 'email' : 'username'); ?>"
                        inputmode="<?php echo esc_attr($view === 'register' ? 'email' : 'text'); ?>"
                        placeholder="<?php echo esc_attr($is_spanish ? ($view === 'register' ? 'correo@ejemplo.com' : 'correo@ejemplo.com o usuario') : ($view === 'register' ? 'email@domain.com' : 'email@domain.com or username')); ?>"
                    >
                    <div class="pl-auth-inline-message" data-pl-auth-inline-message style="display:none; margin-top:10px; font-size:12px; font-weight:600; color:#000000;"></div>
                </div>

                <div class="pl-auth-field pl-auth-register-only pl-auth-hidden" data-pl-auth-email-confirm>
                    <label for="pl-auth-email-confirm"><?php echo esc_html($labels['confirm_email']); ?></label>
                    <input id="pl-auth-email-confirm" name="email_confirm" type="email" autocomplete="email" placeholder="correo@ejemplo.com">
                </div>

                <div class="pl-auth-field" data-pl-auth-password-field>
                    <label for="pl-auth-password"><?php echo esc_html($labels['password']); ?></label>
                    <input id="pl-auth-password" name="password" type="password" autocomplete="<?php echo esc_attr($view === 'register' ? 'new-password' : 'current-password'); ?>" placeholder="********">
                </div>

                <div class="pl-auth-field pl-auth-register-only pl-auth-hidden" data-pl-auth-password-confirm>
                    <label for="pl-auth-password-confirm"><?php echo esc_html($labels['confirm_password']); ?></label>
                    <input id="pl-auth-password-confirm" name="password_confirm" type="password" autocomplete="new-password" placeholder="********">
                </div>

                <?php include ALMADEN_BOOKSTER_AUTH_PATH . 'templates/auth/parts/login-form.php'; ?>

                <button class="pl-auth-submit" type="submit" data-pl-auth-submit><?php echo esc_html($view === 'register' ? $labels['create_account'] : $labels['login']); ?></button>
            </form>

            <div class="pl-auth-footer">
                <span data-pl-auth-footer-copy><?php echo esc_html($view === 'register' ? $labels['already_account'] : $labels['new_here']); ?></span>
                <a href="<?php echo esc_url($view === 'register' ? $login_url : $register_url); ?>" data-pl-auth-toggle-link><?php echo esc_html($view === 'register' ? $labels['back_to_login'] : $labels['create_account_link']); ?></a>
            </div>
        </div>
    </div>
</div>
<script>
    (function () {
        if (window.PLAuthOpenModal && window.PLAuthCloseModal) {
            return;
        }

        function showView(view) {
            var overlay = document.getElementById('pl-auth-overlay');
            if (!overlay) return;
            var isRegister = view === 'register';
            var mode = overlay.querySelector('[data-pl-auth-mode]');
            var registerFields = overlay.querySelectorAll('.pl-auth-register-only');
            var loginOnly = overlay.querySelector('[data-pl-auth-login-row]');
            var email = document.getElementById('pl-auth-email');
            var emailLabel = overlay.querySelector('[data-pl-auth-email-label]');
            var submit = overlay.querySelector('[data-pl-auth-submit]');
            if (mode) mode.value = isRegister ? 'register' : 'login';
            registerFields.forEach(function (field) {
                field.classList.toggle('pl-auth-hidden', !isRegister);
            });
            if (loginOnly) loginOnly.classList.toggle('pl-auth-hidden', isRegister);
            if (email) {
                email.type = isRegister ? 'email' : 'text';
                email.setAttribute('autocomplete', isRegister ? 'email' : 'username');
                email.setAttribute('inputmode', isRegister ? 'email' : 'text');
            }
            if (emailLabel) {
                emailLabel.textContent = isRegister
                    ? <?php echo wp_json_encode($labels['email']); ?>
                    : <?php echo wp_json_encode($labels['login_identifier']); ?>;
            }
            if (submit) {
                submit.textContent = isRegister
                    ? <?php echo wp_json_encode($labels['create_account']); ?>
                    : <?php echo wp_json_encode($labels['login']); ?>;
            }
        }

        window.PLAuthOpenModal = function (view) {
            var overlay = document.getElementById('pl-auth-overlay');
            if (!overlay) return;
            showView(view === 'register' ? 'register' : 'login');
            overlay.classList.add('is-open');
        };

        window.PLAuthCloseModal = function () {
            var overlay = document.getElementById('pl-auth-overlay');
            if (overlay) overlay.classList.remove('is-open', 'is-loading');
        };

        document.addEventListener('click', function (event) {
            var close = event.target && event.target.closest ? event.target.closest('[data-pl-auth-close]') : null;
            if (close) {
                event.preventDefault();
                window.PLAuthCloseModal();
                return;
            }

            var trigger = event.target && event.target.closest ? event.target.closest('[data-pl-auth-open], [data-rcp-auth-open]') : null;
            if (!trigger) return;
            event.preventDefault();
            window.PLAuthOpenModal(trigger.getAttribute('data-pl-auth-view') === 'register' ? 'register' : 'login');
        });

        document.addEventListener('DOMContentLoaded', function () {
            var overlay = document.getElementById('pl-auth-overlay');
            if (!overlay) return;
            var initialView = overlay.getAttribute('data-initial-view') || 'login';
            showView(initialView === 'register' ? 'register' : 'login');
            if (overlay.getAttribute('data-auto-open') === '1') {
                overlay.classList.add('is-open');
            }
        });
    })();
</script>
