<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'almaden_bookster_user_can_access_internal_shell_page' ) ) {
	function almaden_bookster_user_can_access_internal_shell_page( $user_id = null ) {
		$user = null;

		if ( $user_id ) {
			$user = get_user_by( 'id', absint( $user_id ) );
		} elseif ( function_exists( 'wp_get_current_user' ) ) {
			$user = wp_get_current_user();
		}

		if ( ! $user || empty( $user->ID ) ) {
			return false;
		}

		if ( function_exists( 'user_can' ) && user_can( $user, 'manage_options' ) ) {
			return true;
		}

		$allowed_roles = array( 'administrator', 'editor', 'author' );
		$user_roles    = is_array( $user->roles ?? null ) ? $user->roles : array();

		return ! empty( array_intersect( $allowed_roles, $user_roles ) );
	}
}

if ( ! function_exists( 'almaden_bookster_get_login_register_button_html' ) ) {
	function almaden_bookster_get_login_register_button_html( $label = 'Login / Register', $extra_classes = '' ) {
		$classes = array(
			'inline-flex',
			'items-center',
			'rounded-full',
			'border',
			'border-black',
			'bg-black',
			'px-4',
			'py-2',
			'text-sm',
			'font-semibold',
			'text-white',
			'transition',
			'hover:border-neutral-800',
			'hover:bg-neutral-800',
		);

		if ( is_string( $extra_classes ) && '' !== trim( $extra_classes ) ) {
			$classes = array_merge( $classes, preg_split( '/\s+/', trim( $extra_classes ) ) );
		}

		return sprintf(
			'<button type="button" onclick="if (window.PLAuthOpenModal) { window.PLAuthOpenModal(\'login\'); } return false;" data-pl-auth-open data-pl-auth-view="login" class="%1$s">%2$s</button>',
			esc_attr( implode( ' ', array_filter( array_map( 'sanitize_html_class', $classes ) ) ) ),
			esc_html( $label )
		);
	}
}

if ( ! function_exists( 'almaden_bookster_render_login_register_button' ) ) {
	function almaden_bookster_render_login_register_button( $label = 'Login / Register', $extra_classes = '' ) {
		echo almaden_bookster_get_login_register_button_html( $label, $extra_classes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

if ( ! function_exists( 'almaden_bookster_render_shell_access_denied_page' ) ) {
	function almaden_bookster_render_shell_access_denied_page( $args = array() ) {
		$args = wp_parse_args(
			is_array( $args ) ? $args : array(),
			array(
				'title'       => 'No tienes acceso a esta página - Almaden',
				'body_id'     => 'almaden-shell-access-denied-body',
				'page_key'    => '',
				'message'     => 'Esta página está restringida.',
				'submessage'  => 'Si necesitas acceso, entra con una cuenta autorizada.',
			)
		);

		$template_path = dirname( __FILE__ ) . '/../../templates/shell/access-denied-app.php';
		if ( file_exists( $template_path ) ) {
			$almaden_bookster_shell_access_denied_args = $args;
			require_once $template_path;
			exit;
		}

		wp_die( 'Plantilla de acceso denegado no encontrada.' );
	}
}

if ( ! function_exists( 'almaden_bookster_get_shell_access_denied_copy' ) ) {
	function almaden_bookster_get_shell_access_denied_copy( $page_key ) {
		$page_key = sanitize_key( (string) $page_key );

	if ( function_exists( 'almaden_bookster_is_page_admin_only' ) && almaden_bookster_is_page_admin_only( $page_key ) ) {
		if ( 'authors' === $page_key ) {
			return array(
				'message'    => 'Esta página no está habilitada para ti.',
				'submessage' => 'Solo el admin desarrollador puede verla mientras este switch esté activo.',
			);
		}

		return array(
			'message'    => 'Esta página está reservada al admin desarrollador.',
			'submessage' => 'No aparecerá en el shell ni en el navbar para usuarios que no sean administradores.',
		);
		}

		return array(
			'message'    => 'No tienes permisos para acceder a esta página.',
			'submessage' => 'Esta sección pertenece al shell interno del plugin.',
		);
	}
}

if ( ! function_exists( 'almaden_bookster_maybe_render_shell_page_access' ) ) {
	function almaden_bookster_maybe_render_shell_page_access( $page_key ) {
		$page_key = sanitize_key( (string) $page_key );

		if ( '' === $page_key ) {
			return true;
		}

		if ( function_exists( 'almaden_bookster_user_can_access_frontend_page' ) && almaden_bookster_user_can_access_frontend_page( $page_key ) ) {
			return true;
		}

		if ( function_exists( 'almaden_bookster_render_shell_access_denied_page' ) ) {
			$copy = function_exists( 'almaden_bookster_get_shell_access_denied_copy' ) ? almaden_bookster_get_shell_access_denied_copy( $page_key ) : array();
			almaden_bookster_render_shell_access_denied_page(
				array_merge(
					array(
						'page_key' => $page_key,
					),
					is_array( $copy ) ? $copy : array()
				)
			);
		}

		return false;
	}
}
