<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'almaden_bookster_get_shared_nav_items' ) ) {
	function almaden_bookster_get_shared_nav_items() {
		$nav_items = array(
			array(
				'key'   => 'authors',
				'label' => function_exists( 'almaden_bookster_get_authors_title' ) ? almaden_bookster_get_authors_title() : 'Autores',
				'url'   => function_exists( 'almaden_bookster_get_authors_page_url' ) ? almaden_bookster_get_authors_page_url() : home_url( '/' ),
			),
			array(
				'key'   => 'publisher',
				'label' => 'Editoriales',
				'url'   => function_exists( 'almaden_bookster_get_publisher_page_url' ) ? almaden_bookster_get_publisher_page_url() : home_url( '/' ),
			),
			array(
				'key'   => 'store',
				'label' => function_exists( 'almaden_bookster_get_store_title' ) ? almaden_bookster_get_store_title() : 'Ebook Store',
				'url'   => function_exists( 'almaden_bookster_get_store_page_url' ) ? almaden_bookster_get_store_page_url() : home_url( '/' ),
			),
		);

		if ( ! function_exists( 'almaden_bookster_user_can_access_frontend_page' ) ) {
			return $nav_items;
		}

		return array_values(
			array_filter(
				$nav_items,
				static function( $nav_item ) {
					$item_key = isset( $nav_item['key'] ) ? sanitize_key( (string) $nav_item['key'] ) : '';
					if ( '' === $item_key ) {
						return true;
					}

					return almaden_bookster_user_can_access_frontend_page( $item_key );
				}
			)
		);
	}
}

if ( ! function_exists( 'almaden_bookster_render_shared_nav' ) ) {
	function almaden_bookster_render_shared_nav( $active_nav_key = '' ) {
		$active_nav_key = sanitize_key( (string) $active_nav_key );
		$nav_items = function_exists( 'almaden_bookster_get_shared_nav_items' ) ? almaden_bookster_get_shared_nav_items() : array();
		$contractor_company_name = function_exists( 'almaden_bookster_get_contractor_company_name' ) ? trim( (string) almaden_bookster_get_contractor_company_name() ) : '';
		$contractor_logo_url = function_exists( 'almaden_bookster_get_contractor_logo_url' ) ? trim( (string) almaden_bookster_get_contractor_logo_url() ) : '';
		$brand_label = '' !== $contractor_company_name ? $contractor_company_name : 'almaden';
		$current_user = function_exists( 'wp_get_current_user' ) ? wp_get_current_user() : null;
		$is_logged_in = function_exists( 'is_user_logged_in' ) ? is_user_logged_in() : false;
		$current_user_name = '';
		$current_user_avatar = '';
		$logout_url = '';
		$contractor_logo_width = function_exists( 'almaden_bookster_get_contractor_logo_width' ) ? absint( almaden_bookster_get_contractor_logo_width() ) : 160;
		if ( $contractor_logo_width < 40 ) {
			$contractor_logo_width = 40;
		}
		if ( $contractor_logo_width > 300 ) {
			$contractor_logo_width = 300;
		}
		$user_menu_items = array();

		if ( function_exists( 'almaden_bookster_get_dashboard_page_url' ) ) {
			$user_menu_items[] = array(
				'key'   => 'dashboard',
				'label' => function_exists( 'almaden_bookster_get_dashboard_title' ) ? almaden_bookster_get_dashboard_title() : 'Dashboard',
				'url'   => almaden_bookster_get_dashboard_page_url(),
			);
		}
		if ( function_exists( 'almaden_bookster_get_reading_stats_page_url' ) ) {
			$user_menu_items[] = array(
				'key'   => 'reading_stats',
				'label' => function_exists( 'almaden_bookster_get_reading_stats_title' ) ? almaden_bookster_get_reading_stats_title() : 'My Reading Stats',
				'url'   => almaden_bookster_get_reading_stats_page_url(),
			);
		}
		if ( function_exists( 'almaden_bookster_get_creator_page_url' ) ) {
			$user_menu_items[] = array(
				'key'   => 'creator',
				'label' => 'Taller',
				'url'   => almaden_bookster_get_creator_page_url(),
			);
		}
		if ( function_exists( 'almaden_bookster_get_course_archive_page_url' ) ) {
			$user_menu_items[] = array(
				'key'   => 'course_archive',
				'label' => function_exists( 'almaden_bookster_get_course_archive_title' ) ? almaden_bookster_get_course_archive_title() : 'Cursos',
				'url'   => almaden_bookster_get_course_archive_page_url(),
			);
		}
		if ( function_exists( 'almaden_bookster_get_blog_creator_page_url' ) ) {
			$user_menu_items[] = array(
				'key'   => 'blog_creator',
				'label' => function_exists( 'almaden_bookster_get_blog_creator_title' ) ? almaden_bookster_get_blog_creator_title() : 'Blog',
				'url'   => almaden_bookster_get_blog_creator_page_url(),
			);
		}

		if ( function_exists( 'almaden_bookster_user_can_access_frontend_page' ) ) {
			$user_menu_items = array_values(
				array_filter(
					$user_menu_items,
					static function( $nav_item ) {
						$item_key = isset( $nav_item['key'] ) ? sanitize_key( (string) $nav_item['key'] ) : '';
						if ( '' === $item_key ) {
							return true;
						}

						return almaden_bookster_user_can_access_frontend_page( $item_key );
					}
				)
			);
		}

		if ( $is_logged_in && $current_user ) {
			$current_user_name = trim( (string) ( $current_user->display_name ?? '' ) );
			if ( '' === $current_user_name ) {
				$current_user_name = trim( (string) ( $current_user->user_login ?? '' ) );
			}

			if ( function_exists( 'get_avatar' ) ) {
				$current_user_avatar = get_avatar( (int) $current_user->ID, 32, '', $current_user_name, array( 'class' => 'h-8 w-8 rounded-full object-cover' ) );
			}

			$current_request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
			$logout_redirect = home_url( $current_request_uri );
			$logout_url = function_exists( 'wp_logout_url' ) ? wp_logout_url( $logout_redirect ) : '';
		}

		ob_start();
		?>
		<nav id="almaden-app-nav" class="border-b border-gray-200 bg-white">
			<div id="almaden-app-nav-inner" class="mx-auto max-w-7xl px-8">
				<div class="flex h-16 justify-between">
					<div class="flex items-center">
						<div class="flex-shrink-0 flex items-center text-black">
							<?php $shell_home_url = function_exists( 'almaden_bookster_get_shell_home_page_url' ) ? almaden_bookster_get_shell_home_page_url() : home_url( '/' ); ?>
							<a href="<?php echo esc_url( $shell_home_url ); ?>" class="inline-flex items-center gap-3 transition hover:opacity-80">
								<?php if ( '' !== $contractor_logo_url ) : ?>
									<img src="<?php echo esc_url( $contractor_logo_url ); ?>" alt="<?php echo esc_attr( $brand_label ); ?>" class="h-9 w-auto object-contain object-left" style="width: <?php echo esc_attr( (string) $contractor_logo_width ); ?>px; max-width: <?php echo esc_attr( (string) $contractor_logo_width ); ?>px;" />
									<?php if ( '' !== trim( (string) $contractor_company_name ) ) : ?>
										<span class="hidden text-xl font-semibold tracking-tight text-black md:inline-flex"><?php echo esc_html( $brand_label ); ?></span>
									<?php endif; ?>
								<?php else : ?>
									<span class="text-2xl tracking-tight urbanist-almaden-logo"><?php echo esc_html( $brand_label ); ?></span>
								<?php endif; ?>
							</a>
						</div>
						<div class="hidden items-center sm:ml-8 sm:flex sm:space-x-6">
							<?php foreach ( $nav_items as $nav_item ) : ?>
								<?php
								$item_key = isset( $nav_item['key'] ) ? sanitize_key( (string) $nav_item['key'] ) : '';
								$is_active = $item_key && $active_nav_key === $item_key;
								$item_url  = isset( $nav_item['url'] ) ? esc_url( $nav_item['url'] ) : '#';
								$item_label = isset( $nav_item['label'] ) ? $nav_item['label'] : '';
								$base_classes = array(
									'border-b-2',
									'px-1',
									'pt-1',
									'text-sm',
									'font-medium',
									'h-full',
									'flex',
									'items-center',
									'transition-colors',
								);
								if ( $is_active ) {
									$base_classes[] = 'border-black';
									$base_classes[] = 'text-black';
								} else {
									$base_classes[] = 'border-transparent';
									$base_classes[] = 'text-gray-500';
									$base_classes[] = 'hover:text-black';
									$base_classes[] = 'hover:border-gray-300';
								}
								?>
								<a href="<?php echo esc_url( $item_url ); ?>" class="<?php echo esc_attr( implode( ' ', $base_classes ) ); ?>">
									<?php echo esc_html( $item_label ); ?>
								</a>
							<?php endforeach; ?>
						</div>
					</div>
					<div class="flex items-center space-x-3" data-almaden-auth-nav-actions>
						<?php if ( $is_logged_in ) : ?>
							<div class="relative" data-almaden-user-menu-root>
								<button
									type="button"
									data-almaden-user-menu-button
									aria-haspopup="true"
									aria-expanded="false"
									class="inline-flex items-center gap-2 rounded-full border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm transition hover:border-gray-300 hover:bg-gray-50"
								>
									<?php if ( ! empty( $current_user_avatar ) ) : ?>
										<?php echo $current_user_avatar; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									<?php endif; ?>
									<span class="max-w-[12rem] truncate"><?php echo esc_html( $current_user_name ); ?></span>
									<svg class="h-4 w-4 text-gray-500 transition-transform duration-200" data-almaden-user-menu-caret viewBox="0 0 20 20" fill="none" aria-hidden="true">
										<path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" />
									</svg>
								</button>
								<div
									id="almaden-app-user-menu"
									data-almaden-user-menu
									class="invisible absolute right-0 top-full z-50 mt-3 w-64 translate-y-1 rounded-[1.5rem] border border-gray-200 bg-white p-2 opacity-0 transition-all duration-200"
								>
									<?php foreach ( $user_menu_items as $nav_item ) : ?>
										<?php
										$item_key = isset( $nav_item['key'] ) ? sanitize_key( (string) $nav_item['key'] ) : '';
										$is_active = $item_key && $active_nav_key === $item_key;
										$item_url  = isset( $nav_item['url'] ) ? esc_url( $nav_item['url'] ) : '#';
										$item_label = isset( $nav_item['label'] ) ? $nav_item['label'] : '';
										$link_classes = array(
											'flex',
											'items-center',
											'rounded-[1.1rem]',
											'px-4',
											'py-3',
											'text-base',
											'font-semibold',
											'transition',
										);
										if ( $is_active ) {
											$link_classes[] = 'bg-gray-100';
											$link_classes[] = 'text-black';
										} else {
											$link_classes[] = 'text-gray-900';
											$link_classes[] = 'hover:bg-gray-50';
											$link_classes[] = 'hover:text-black';
										}
										?>
										<a href="<?php echo esc_url( $item_url ); ?>" class="<?php echo esc_attr( implode( ' ', $link_classes ) ); ?>">
											<?php echo esc_html( $item_label ); ?>
										</a>
									<?php endforeach; ?>
									<div class="my-1 h-px bg-gray-200"></div>
									<a
										href="<?php echo esc_url( $logout_url ); ?>"
										class="flex items-center rounded-[1.1rem] px-4 py-3 text-base font-semibold text-gray-900 transition hover:bg-gray-50 hover:text-black"
										>
											Cerrar sesión
										</a>
									</div>
							</div>
						<?php else : ?>
							<?php
							if ( function_exists( 'almaden_bookster_render_login_register_button' ) ) {
								almaden_bookster_render_login_register_button();
							}
							?>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</nav>
		<?php
		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'almaden_bookster_render_user_menu_script' ) ) {
	function almaden_bookster_render_user_menu_script() {
		?>
		<script>
			(function () {
				const root = document.querySelector('[data-almaden-user-menu-root]');
				if (!root) return;

				const button = root.querySelector('[data-almaden-user-menu-button]');
				const menu = root.querySelector('[data-almaden-user-menu]');
				const caret = root.querySelector('[data-almaden-user-menu-caret]');

				function closeMenu() {
					menu.classList.add('invisible', 'opacity-0', 'translate-y-1');
					menu.classList.remove('visible', 'opacity-100', 'translate-y-0');
					button.setAttribute('aria-expanded', 'false');
					if (caret) caret.style.transform = 'rotate(0deg)';
				}

				function openMenu() {
					menu.classList.remove('invisible', 'opacity-0', 'translate-y-1');
					menu.classList.add('visible', 'opacity-100', 'translate-y-0');
					button.setAttribute('aria-expanded', 'true');
					if (caret) caret.style.transform = 'rotate(180deg)';
				}

				button.addEventListener('click', function (event) {
					event.stopPropagation();
					const isOpen = button.getAttribute('aria-expanded') === 'true';
					if (isOpen) {
						closeMenu();
					} else {
						openMenu();
					}
				});

				menu.addEventListener('click', function (event) {
					event.stopPropagation();
				});

				document.addEventListener('click', closeMenu);
				document.addEventListener('keydown', function (event) {
					if (event.key === 'Escape') closeMenu();
				});
			})();
		</script>
		<?php
	}
}

if ( ! function_exists( 'almaden_bookster_render_access_preview_switcher' ) ) {
	function almaden_bookster_render_access_preview_switcher() {
		if ( ! function_exists( 'almaden_bookster_user_can_use_access_preview' ) || ! almaden_bookster_user_can_use_access_preview() ) {
			return '';
		}

		$roles = function_exists( 'almaden_bookster_get_access_preview_roles' ) ? almaden_bookster_get_access_preview_roles() : array(
			'administrator' => 'Administrador',
			'editor'        => 'Editor',
			'author'        => 'Autor',
			'customer'      => 'Cliente',
			'subscriber'    => 'Suscriptor',
		);
		$current_role = function_exists( 'almaden_bookster_get_access_preview_role' ) ? almaden_bookster_get_access_preview_role() : 'administrator';
		if ( '' === $current_role || ! isset( $roles[ $current_role ] ) ) {
			$current_role = 'administrator';
		}
		$current_label = isset( $roles[ $current_role ] ) ? $roles[ $current_role ] : 'Administrador';

		ob_start();
		?>
		<div class="fixed bottom-4 left-1/2 z-[80] -translate-x-1/2">
			<div class="relative" data-almaden-access-preview-root>
				<button
					type="button"
					data-almaden-access-preview-button
					aria-haspopup="true"
					aria-expanded="false"
					class="inline-flex items-center gap-3 rounded-full border border-slate-200 bg-white px-4 py-3 text-left text-sm font-semibold text-slate-900 shadow-[0_12px_40px_rgba(15,23,42,0.12)] transition hover:border-slate-300 hover:bg-slate-50"
				>
					<span class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400">Vista</span>
					<span data-almaden-access-preview-label class="text-sm font-semibold text-slate-900"><?php echo esc_html( $current_label ); ?></span>
					<svg class="h-4 w-4 text-slate-500 transition-transform duration-200" data-almaden-access-preview-caret viewBox="0 0 20 20" fill="none" aria-hidden="true">
						<path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" />
					</svg>
				</button>
				<div
					data-almaden-access-preview-menu
					class="invisible absolute left-1/2 bottom-full z-50 mb-3 w-72 -translate-x-1/2 translate-y-2 rounded-[1.5rem] border border-slate-200 bg-white p-2 opacity-0 shadow-[0_18px_50px_rgba(15,23,42,0.18)] transition-all duration-200"
				>
					<p class="px-3 py-2 text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Simular rol</p>
					<?php foreach ( $roles as $role_key => $role_label ) : ?>
						<?php $is_selected = $current_role === $role_key; ?>
						<button
							type="button"
							data-almaden-access-preview-role="<?php echo esc_attr( $role_key ); ?>"
							class="flex w-full items-center justify-between rounded-[1.1rem] px-4 py-3 text-left text-sm font-semibold transition <?php echo $is_selected ? 'bg-slate-900 text-white' : 'text-slate-900 hover:bg-slate-50'; ?>"
						>
							<span><?php echo esc_html( $role_label ); ?></span>
							<?php if ( $is_selected ) : ?>
								<span class="text-[11px] font-semibold uppercase tracking-[0.22em]">Activo</span>
							<?php endif; ?>
						</button>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'almaden_bookster_render_access_preview_script' ) ) {
	function almaden_bookster_render_access_preview_script() {
		if ( ! function_exists( 'almaden_bookster_user_can_use_access_preview' ) || ! almaden_bookster_user_can_use_access_preview() ) {
			return;
		}
		?>
		<script>
			(function () {
				const root = document.querySelector('[data-almaden-access-preview-root]');
				if (!root) return;

				const button = root.querySelector('[data-almaden-access-preview-button]');
				const menu = root.querySelector('[data-almaden-access-preview-menu]');
				const label = root.querySelector('[data-almaden-access-preview-label]');
				const caret = root.querySelector('[data-almaden-access-preview-caret]');
				const options = root.querySelectorAll('[data-almaden-access-preview-role]');
				const cookieName = 'almaden_bookster_preview_role';

				function setCookie(value) {
					document.cookie = cookieName + '=' + encodeURIComponent(value) + '; path=/; max-age=' + (60 * 60 * 24 * 30) + '; samesite=lax';
				}

				function closeMenu() {
					menu.classList.add('invisible', 'opacity-0', 'translate-y-2');
					menu.classList.remove('visible', 'opacity-100', 'translate-y-0');
					button.setAttribute('aria-expanded', 'false');
					if (caret) caret.style.transform = 'rotate(0deg)';
				}

				function openMenu() {
					menu.classList.remove('invisible', 'opacity-0', 'translate-y-2');
					menu.classList.add('visible', 'opacity-100', 'translate-y-0');
					button.setAttribute('aria-expanded', 'true');
					if (caret) caret.style.transform = 'rotate(180deg)';
				}

				button.addEventListener('click', function (event) {
					event.stopPropagation();
					const isOpen = button.getAttribute('aria-expanded') === 'true';
					if (isOpen) {
						closeMenu();
					} else {
						openMenu();
					}
				});

				options.forEach(function (option) {
					option.addEventListener('click', function (event) {
						event.stopPropagation();
						const role = option.getAttribute('data-almaden-access-preview-role') || 'administrator';
						if (label) {
							const labelNode = option.querySelector('span');
							label.textContent = labelNode ? labelNode.textContent : role;
						}
						setCookie(role);
						window.location.reload();
					});
				});

				document.addEventListener('click', closeMenu);
				document.addEventListener('keydown', function (event) {
					if (event.key === 'Escape') closeMenu();
				});
			})();
		</script>
		<?php
	}
}

if ( ! function_exists( 'almaden_bookster_render_access_preview_footer' ) ) {
	function almaden_bookster_render_access_preview_footer() {
		echo almaden_bookster_render_access_preview_switcher(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		almaden_bookster_render_access_preview_script();
	}
}

add_action( 'wp_footer', 'almaden_bookster_render_access_preview_footer', 999 );

if ( ! function_exists( 'almaden_bookster_render_app_shell_start' ) ) {
	function almaden_bookster_render_app_shell_start( $args = array() ) {
		$args = is_array( $args ) ? $args : array();
		if ( function_exists( 'show_admin_bar' ) ) {
			show_admin_bar( false );
		}
		if ( class_exists( '\AlmadenBookster\Auth\AuthOrchestrator' ) ) {
			\AlmadenBookster\Auth\AuthOrchestrator::get_instance()->enqueue_assets();
		}
		$defaults = array(
			'title'            => 'almaden',
			'body_class'       => array( 'min-h-screen', 'flex', 'flex-col', 'theme-light' ),
			'body_id'          => 'almaden-app-body',
			'extra_head_html'  => '',
			'nav_items'        => array(),
			'actions_html'     => '',
			'active_nav_key'   => '',
			'logo_text'        => 'almaden',
		);
		$args = wp_parse_args( $args, $defaults );

		$body_class = $args['body_class'];
		if ( ! is_array( $body_class ) ) {
			$body_class = preg_split( '/\s+/', trim( (string) $body_class ) );
		}
		$body_class = array_filter( array_map( 'sanitize_html_class', (array) $body_class ) );
		$body_class[] = 'almaden-app-body';
		$body_class = array_values( array_unique( $body_class ) );

		$active_nav_key = sanitize_key( (string) $args['active_nav_key'] );

		$current_user = function_exists( 'wp_get_current_user' ) ? wp_get_current_user() : null;
		$is_logged_in = function_exists( 'is_user_logged_in' ) ? is_user_logged_in() : false;
		$current_user_name = '';
		$current_user_avatar = '';
		$logout_url = '';

		if ( $is_logged_in && $current_user ) {
			$current_user_name = trim( (string) ( $current_user->display_name ?? '' ) );
			if ( '' === $current_user_name ) {
				$current_user_name = trim( (string) ( $current_user->user_login ?? '' ) );
			}

			if ( function_exists( 'get_avatar' ) ) {
				$current_user_avatar = get_avatar( (int) $current_user->ID, 32, '', $current_user_name, array( 'class' => 'h-8 w-8 rounded-full object-cover' ) );
			}

			$current_request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
			$logout_redirect = home_url( $current_request_uri );
			$logout_url = function_exists( 'wp_logout_url' ) ? wp_logout_url( $logout_redirect ) : '';
		}

			?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo esc_html( $args['title'] ); ?></title>
	<script>
		window.almadenAuthDebug = window.almadenAuthDebug || {
			page: <?php echo wp_json_encode( array(
				'bodyId' => $args['body_id'],
				'isLoggedIn' => $is_logged_in,
				'userName' => $current_user_name,
			) ); ?>,
		};
		console.error('[almaden] app shell boot', window.almadenAuthDebug.page);
		window.tailwind = window.tailwind || {};
		window.tailwind.config = {
			theme: {
				extend: {
					fontFamily: {
						sans: ['"Urbanist"', 'sans-serif']
					}
				}
			}
		};
	</script>
	<script src="https://cdn.tailwindcss.com"></script>
	<?php if ( function_exists( 'almaden_bookster_get_bundled_fonts_stylesheet_url' ) ) : ?>
		<link rel="stylesheet" href="<?php echo esc_url( almaden_bookster_get_bundled_fonts_stylesheet_url() ); ?>">
	<?php endif; ?>
	<?php if ( ! empty( $args['extra_head_html'] ) ) : ?>
		<?php echo $args['extra_head_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php endif; ?>
	<style>
		html {
			margin-top: 0 !important;
		}
		.urbanist-almaden-logo {
			font-family: "Urbanist", sans-serif;
			font-optical-sizing: auto;
			font-weight: 700;
			font-size: 34px !important;
			font-style: normal;
		}
	</style>
	<?php wp_head(); ?>
	<style id="almaden-app-shell-overrides">
		html {
			margin-top: 0 !important;
		}
		main {
			padding-top: 20px !important;
			background-color: #f5f5f5;
		}
		html,
		body,
		#page,
		#content,
		#wpwrap {
			background-color: #f5f5f5 !important;
			background-image: none !important;
		}
		#wpadminbar {
			display: none !important;
		}
		#almaden-app-user-menu {
			box-shadow: 0 18px 40px rgba(15, 23, 42, 0.12);
		}
		#almaden-app-nav {
			position: sticky;
			top: 0;
			z-index: 60;
			background-color: rgba(255, 255, 255, 0.96);
			backdrop-filter: saturate(180%) blur(14px);
			-webkit-backdrop-filter: saturate(180%) blur(14px);
		}
		#almaden-app-nav-inner {
			padding-left: 2rem;
			padding-right: 2rem;
		}
		/* Keep the application UI independent from the active WordPress theme. */
		body.almaden-app-body,
		body.almaden-app-body #almaden-app-nav,
		body.almaden-app-body #almaden-app-nav *,
		body.almaden-app-body .almaden-app-content-shell,
		body.almaden-app-body .almaden-app-content-shell :where(*):not(.cover-thumbnail-wrapper):not(.cover-thumbnail-wrapper *):not(.cover-spread-container):not(.cover-spread-container *):not(.ebook-page-content):not(.ebook-page-content *):not(#ebook-page):not(#ebook-page *):not(#reader-content):not(#reader-content *):not([data-almaden-book-font]):not([data-almaden-book-font] *),
		body.almaden-app-body button,
		body.almaden-app-body input,
		body.almaden-app-body select,
		body.almaden-app-body textarea {
			font-family: "Urbanist", sans-serif !important;
		}
		:root {
			--almaden-app-max-width: 80rem;
		}
		.almaden-app-content-shell {
			box-sizing: border-box;
			margin-left: auto;
			margin-right: auto;
			max-width: var(--almaden-app-max-width, 80rem);
			padding-left: 2rem;
			padding-right: 2rem;
			width: 100%;
			background-color: #f5f5f5;
		}
	</style>
</head>
<body<?php echo ! empty( $body_class ) ? ' class="' . esc_attr( implode( ' ', $body_class ) ) . '"' : ''; ?><?php echo '' !== trim( (string) $args['body_id'] ) ? ' id="' . esc_attr( $args['body_id'] ) . '"' : ''; ?>>
	<?php echo almaden_bookster_render_shared_nav( $active_nav_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<?php
	}
}

if ( ! function_exists( 'almaden_bookster_render_app_shell_end' ) ) {
	function almaden_bookster_render_app_shell_end() {
		if ( class_exists( '\AlmadenBookster\Auth\UI\Renderer' ) ) {
			echo \AlmadenBookster\Auth\UI\Renderer::get_auth_modal_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		?>
		<div class="mx-auto w-full max-w-7xl px-4 pb-4 text-right">
			<a href="<?php echo esc_url( admin_url() ); ?>" class="inline-flex items-center text-xs font-medium text-gray-400 transition hover:text-gray-600">
				Volver a WP
			</a>
		</div>
		<?php almaden_bookster_render_user_menu_script(); ?>
		<?php
		wp_footer();
		?>
</body>
</html>
		<?php
	}
}
