<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'almaden_bookster_get_shell_navigation_exclusion_rules' ) ) {
	function almaden_bookster_get_shell_navigation_exclusion_rules() {
		$rules = array();

		$add_rule = static function( $key, $slug, $label, $page_id = 0 ) use ( &$rules ) {
			$slug    = trim( (string) $slug, '/' );
			$label   = trim( (string) $label );
			$page_id = absint( $page_id );

			if ( '' === $slug && '' === $label && $page_id <= 0 ) {
				return;
			}

			$rules[] = array(
				'key'     => sanitize_key( (string) $key ),
				'slug'    => $slug,
				'label'   => $label,
				'page_id' => $page_id,
				'url'     => '' !== $slug ? home_url( '/' . $slug . '/' ) : '',
			);
		};

		$add_rule(
			'creator',
			function_exists( 'almaden_bookster_get_creator_slug' ) ? almaden_bookster_get_creator_slug() : 'almaden-booklist',
			function_exists( 'almaden_bookster_get_creator_title' ) ? almaden_bookster_get_creator_title() : 'Taller',
			function_exists( 'almaden_bookster_get_creator_page_id' ) ? almaden_bookster_get_creator_page_id() : 0
		);
		if ( ! function_exists( 'almaden_bookster_is_shell_home_menu_enabled' ) || ! almaden_bookster_is_shell_home_menu_enabled() ) {
			$add_rule(
				'shell_home',
				function_exists( 'almaden_bookster_get_shell_home_slug' ) ? almaden_bookster_get_shell_home_slug() : 'almaden-home',
				function_exists( 'almaden_bookster_get_shell_home_title' ) ? almaden_bookster_get_shell_home_title() : 'Almaden App',
				function_exists( 'almaden_bookster_get_shell_home_page_id' ) ? almaden_bookster_get_shell_home_page_id() : 0
			);
		}
		$add_rule(
			'dashboard',
			function_exists( 'almaden_bookster_get_dashboard_slug' ) ? almaden_bookster_get_dashboard_slug() : 'dashboard',
			function_exists( 'almaden_bookster_get_dashboard_title' ) ? almaden_bookster_get_dashboard_title() : 'Dashboard',
			function_exists( 'almaden_bookster_get_dashboard_page_id' ) ? almaden_bookster_get_dashboard_page_id() : 0
		);
		$add_rule(
			'course_creator',
			function_exists( 'almaden_bookster_get_course_creator_slug' ) ? almaden_bookster_get_course_creator_slug() : 'sala-de-clases',
			function_exists( 'almaden_bookster_get_course_creator_title' ) ? almaden_bookster_get_course_creator_title() : 'Sala de clases',
			function_exists( 'almaden_bookster_get_course_creator_page_id' ) ? almaden_bookster_get_course_creator_page_id() : 0
		);
		$add_rule(
			'course_archive',
			function_exists( 'almaden_bookster_get_course_archive_slug' ) ? almaden_bookster_get_course_archive_slug() : 'almaden-cursos',
			function_exists( 'almaden_bookster_get_course_archive_title' ) ? almaden_bookster_get_course_archive_title() : 'Cursos',
			function_exists( 'almaden_bookster_get_course_archive_page_id' ) ? almaden_bookster_get_course_archive_page_id() : 0
		);
		$add_rule(
			'blog_creator',
			function_exists( 'almaden_bookster_get_blog_creator_slug' ) ? almaden_bookster_get_blog_creator_slug() : 'blog-editor',
			function_exists( 'almaden_bookster_get_blog_creator_title' ) ? almaden_bookster_get_blog_creator_title() : 'Blog',
			function_exists( 'almaden_bookster_get_blog_creator_page_id' ) ? almaden_bookster_get_blog_creator_page_id() : 0
		);
		$add_rule(
			'authors',
			function_exists( 'almaden_bookster_get_authors_slug' ) ? almaden_bookster_get_authors_slug() : 'autores',
			function_exists( 'almaden_bookster_get_authors_title' ) ? almaden_bookster_get_authors_title() : 'Autores',
			function_exists( 'almaden_bookster_get_authors_page_id' ) ? almaden_bookster_get_authors_page_id() : 0
		);
		$add_rule(
			'author',
			function_exists( 'almaden_bookster_get_author_page_slug' ) ? almaden_bookster_get_author_page_slug() : 'autor',
			function_exists( 'almaden_bookster_get_author_page_title' ) ? almaden_bookster_get_author_page_title() : 'Autor',
			function_exists( 'almaden_bookster_get_author_page_id' ) ? almaden_bookster_get_author_page_id() : 0
		);
		$add_rule(
			'publisher',
			function_exists( 'almaden_bookster_get_publisher_page_slug' ) ? almaden_bookster_get_publisher_page_slug() : 'editorial',
			function_exists( 'almaden_bookster_get_publisher_page_title' ) ? almaden_bookster_get_publisher_page_title() : 'Editorial',
			function_exists( 'almaden_bookster_get_publisher_page_id' ) ? almaden_bookster_get_publisher_page_id() : 0
		);
		$add_rule(
			'publisher_onboarding',
			function_exists( 'almaden_bookster_get_publisher_onboarding_page_slug' ) ? almaden_bookster_get_publisher_onboarding_page_slug() : ( function_exists( 'almaden_bookster_get_publisher_onboarding_slug' ) ? almaden_bookster_get_publisher_onboarding_slug() : 'crear-editorial' ),
			function_exists( 'almaden_bookster_get_publisher_onboarding_page_title' ) ? almaden_bookster_get_publisher_onboarding_page_title() : ( function_exists( 'almaden_bookster_get_publisher_onboarding_title' ) ? almaden_bookster_get_publisher_onboarding_title() : 'Crear editorial' ),
			function_exists( 'almaden_bookster_get_publisher_onboarding_page_id' ) ? almaden_bookster_get_publisher_onboarding_page_id() : 0
		);
		$add_rule(
			'quiz_builder',
			function_exists( 'almaden_bookster_get_quiz_page_slug' ) ? almaden_bookster_get_quiz_page_slug() : 'almaden-book-quiz',
			function_exists( 'almaden_bookster_get_quiz_page_title' ) ? almaden_bookster_get_quiz_page_title() : 'Book Quiz',
			function_exists( 'almaden_bookster_get_quiz_page_id' ) ? almaden_bookster_get_quiz_page_id() : 0
		);
		$add_rule(
			'store',
			function_exists( 'almaden_bookster_get_store_slug' ) ? almaden_bookster_get_store_slug() : 'bookshelf',
			function_exists( 'almaden_bookster_get_store_title' ) ? almaden_bookster_get_store_title() : 'Ebook Store',
			function_exists( 'almaden_bookster_get_store_page_id' ) ? almaden_bookster_get_store_page_id() : 0
		);

		return $rules;
	}
}

if ( ! function_exists( 'almaden_bookster_should_show_shell_home_in_regular_menu' ) ) {
	function almaden_bookster_should_show_shell_home_in_regular_menu() {
		return function_exists( 'almaden_bookster_is_shell_home_menu_enabled' ) && almaden_bookster_is_shell_home_menu_enabled() && function_exists( 'almaden_bookster_get_shell_home_page_id' ) && almaden_bookster_get_shell_home_page_id() > 0;
	}
}

if ( ! function_exists( 'almaden_bookster_build_shell_home_menu_item_html' ) ) {
	function almaden_bookster_build_shell_home_menu_item_html() {
		if ( ! almaden_bookster_should_show_shell_home_in_regular_menu() ) {
			return '';
		}

		$url   = function_exists( 'almaden_bookster_get_shell_home_page_url' ) ? almaden_bookster_get_shell_home_page_url() : home_url( '/' );
		$label = function_exists( 'almaden_bookster_get_shell_home_title' ) ? almaden_bookster_get_shell_home_title() : 'Almaden App';
		$page_id = function_exists( 'almaden_bookster_get_shell_home_page_id' ) ? almaden_bookster_get_shell_home_page_id() : 0;
		$classes = array( 'menu-item', 'menu-item-type-post_type', 'menu-item-object-page', 'menu-item-shell-home' );

		if ( $page_id > 0 && is_page( $page_id ) ) {
			$classes[] = 'current-menu-item';
			$classes[] = 'current_page_item';
		}

		return sprintf(
			'<li class="%1$s"><a href="%2$s">%3$s</a></li>',
			esc_attr( implode( ' ', $classes ) ),
			esc_url( $url ),
			esc_html( $label )
		);
	}
}

if ( ! function_exists( 'almaden_bookster_should_strip_shell_navigation_item' ) ) {
	function almaden_bookster_should_strip_shell_navigation_item( $item_url = '', $item_title = '', $item_id = 0 ) {
		$item_url   = trim( (string) $item_url );
		$item_title = trim( wp_strip_all_tags( (string) $item_title ) );
		$item_id    = absint( $item_id );

		foreach ( almaden_bookster_get_shell_navigation_exclusion_rules() as $rule ) {
			$rule_url   = isset( $rule['url'] ) ? trim( (string) $rule['url'] ) : '';
			$rule_title = isset( $rule['label'] ) ? trim( (string) $rule['label'] ) : '';
			$rule_id    = isset( $rule['page_id'] ) ? absint( $rule['page_id'] ) : 0;

			if ( $rule_id > 0 && $item_id > 0 && $rule_id === $item_id ) {
				return true;
			}

			if ( '' !== $rule_url && '' !== $item_url && untrailingslashit( $rule_url ) === untrailingslashit( $item_url ) ) {
				return true;
			}

			if ( '' !== $rule_title && '' !== $item_title && 0 === strcasecmp( $rule_title, $item_title ) ) {
				return true;
			}
		}

		return false;
	}
}

if ( ! function_exists( 'almaden_bookster_remove_shell_pages_from_nav_menu_items' ) ) {
	function almaden_bookster_remove_shell_pages_from_nav_menu_items( $items ) {
		if ( is_admin() || empty( $items ) || ! is_array( $items ) ) {
			return $items;
		}

		return array_values(
			array_filter(
				$items,
				static function( $item ) {
					$item_url   = isset( $item->url ) ? (string) $item->url : '';
					$item_title = isset( $item->title ) ? (string) $item->title : '';
					$item_id    = isset( $item->object_id ) ? absint( $item->object_id ) : 0;

					return ! almaden_bookster_should_strip_shell_navigation_item( $item_url, $item_title, $item_id );
				}
			)
		);
	}
}
add_filter( 'wp_nav_menu_objects', 'almaden_bookster_remove_shell_pages_from_nav_menu_items', 20 );

if ( ! function_exists( 'almaden_bookster_prepend_shell_home_to_nav_menu_items_html' ) ) {
function almaden_bookster_prepend_shell_home_to_nav_menu_items_html( $items ) {
		if ( is_admin() || '' === trim( (string) $items ) || ! almaden_bookster_should_show_shell_home_in_regular_menu() ) {
			return $items;
		}

		$shell_home_url = function_exists( 'almaden_bookster_get_shell_home_page_url' ) ? esc_url( almaden_bookster_get_shell_home_page_url() ) : '';
		if ( '' !== $shell_home_url && false !== stripos( $items, $shell_home_url ) ) {
			return $items;
		}

		$shell_home_item = almaden_bookster_build_shell_home_menu_item_html();
		if ( '' === $shell_home_item ) {
			return $items;
		}

		return $shell_home_item . $items;
	}
}
add_filter( 'wp_nav_menu_items', 'almaden_bookster_prepend_shell_home_to_nav_menu_items_html', 21 );

if ( ! function_exists( 'almaden_bookster_inject_shell_home_into_navigation_block_html' ) ) {
	function almaden_bookster_inject_shell_home_into_navigation_block_html( $block_content, $block = array() ) {
		if ( is_admin() || '' === trim( (string) $block_content ) || ! almaden_bookster_should_show_shell_home_in_regular_menu() ) {
			return $block_content;
		}

		if ( ! isset( $block['blockName'] ) || 0 !== strpos( (string) $block['blockName'], 'core/navigation' ) ) {
			return $block_content;
		}

		$shell_home_url = function_exists( 'almaden_bookster_get_shell_home_page_url' ) ? esc_url( almaden_bookster_get_shell_home_page_url() ) : '';
		$shell_home_label = function_exists( 'almaden_bookster_get_shell_home_title' ) ? almaden_bookster_get_shell_home_title() : 'Almaden App';

		if ( '' !== $shell_home_url && false !== stripos( $block_content, $shell_home_url ) ) {
			return $block_content;
		}

		if ( '' !== $shell_home_label && false !== stripos( wp_strip_all_tags( $block_content ), $shell_home_label ) ) {
			return $block_content;
		}

		$shell_home_item = almaden_bookster_build_shell_home_menu_item_html();
		if ( '' === $shell_home_item ) {
			return $block_content;
		}

		$updated_block_content = preg_replace(
			'/<ul([^>]*)>/i',
			'<ul$1>' . $shell_home_item,
			$block_content,
			1
		);

		if ( null === $updated_block_content ) {
			return $block_content;
		}

		if ( $updated_block_content !== $block_content ) {
			return $updated_block_content;
		}

		$updated_block_content = preg_replace(
			'/<\/ul>/i',
			$shell_home_item . '</ul>',
			$block_content,
			1
		);

		return null === $updated_block_content ? $block_content : $updated_block_content;
	}
}
add_filter( 'render_block_core/navigation', 'almaden_bookster_inject_shell_home_into_navigation_block_html', 20, 2 );

if ( ! function_exists( 'almaden_bookster_strip_shell_pages_from_menu_items_html' ) ) {
	function almaden_bookster_strip_shell_pages_from_menu_items_html( $items ) {
		if ( is_admin() || '' === trim( (string) $items ) ) {
			return $items;
		}

		foreach ( almaden_bookster_get_shell_navigation_exclusion_rules() as $rule ) {
			$rule_url   = isset( $rule['url'] ) ? trim( (string) $rule['url'] ) : '';
			$rule_title = isset( $rule['label'] ) ? trim( (string) $rule['label'] ) : '';

			if ( '' !== $rule_url ) {
				$items = preg_replace(
					"#<li[^>]*>\\s*<a[^>]*href=(\"|')" . preg_quote( esc_url( $rule_url ), '#' ) . "(\"|')[^>]*>.*?</a>\\s*</li>#is",
					'',
					$items
				);
			}

			if ( '' !== $rule_title ) {
				$items = preg_replace(
					"#<li[^>]*>\\s*<a[^>]*>\\s*" . preg_quote( $rule_title, '#' ) . "\\s*</a>\\s*</li>#is",
					'',
					$items
				);
			}
		}

		return $items;
	}
}
add_filter( 'wp_nav_menu_items', 'almaden_bookster_strip_shell_pages_from_menu_items_html', 20, 2 );

if ( ! function_exists( 'almaden_bookster_strip_shell_pages_from_navigation_block' ) ) {
	function almaden_bookster_strip_shell_pages_from_navigation_block( $block_content, $block = array() ) {
		if ( is_admin() || '' === trim( (string) $block_content ) ) {
			return $block_content;
		}

		if ( ! isset( $block['blockName'] ) || 0 !== strpos( (string) $block['blockName'], 'core/navigation' ) ) {
			return $block_content;
		}

		foreach ( almaden_bookster_get_shell_navigation_exclusion_rules() as $rule ) {
			$rule_url   = isset( $rule['url'] ) ? trim( (string) $rule['url'] ) : '';
			$rule_title = isset( $rule['label'] ) ? trim( (string) $rule['label'] ) : '';

			if ( '' !== $rule_url ) {
				$block_content = preg_replace(
					"#<li[^>]*>\\s*<a[^>]*href=(\"|')" . preg_quote( esc_url( $rule_url ), '#' ) . "(\"|')[^>]*>.*?</a>\\s*</li>#is",
					'',
					$block_content
				);
			}

			if ( '' !== $rule_title ) {
				$block_content = preg_replace(
					"#<li[^>]*>\\s*<a[^>]*class=(\"|')[^\"']*wp-block-navigation-item__content[^\"']*(\"|')[^>]*>\\s*" . preg_quote( $rule_title, '#' ) . "\\s*</a>\\s*</li>#is",
					'',
					$block_content
				);
			}
		}

		return $block_content;
	}
}

if ( ! function_exists( 'almaden_bookster_navigation_block_contains_page_list' ) ) {
	function almaden_bookster_navigation_block_contains_page_list( $blocks ) {
		if ( ! is_array( $blocks ) ) {
			return false;
		}

		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}

			$block_name = isset( $block['blockName'] ) ? (string) $block['blockName'] : '';
			if ( 'core/page-list' === $block_name ) {
				return true;
			}

			if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) && almaden_bookster_navigation_block_contains_page_list( $block['innerBlocks'] ) ) {
				return true;
			}
		}

		return false;
	}
}

if ( ! function_exists( 'almaden_bookster_build_navigation_link_block' ) ) {
	function almaden_bookster_build_navigation_link_block( $label, $url ) {
		$label = trim( wp_strip_all_tags( (string) $label ) );
		$url   = esc_url_raw( (string) $url );

		if ( '' === $label || '' === $url ) {
			return null;
		}

		$markup = sprintf(
			'<!-- wp:navigation-link %s /-->',
			wp_json_encode(
				array(
					'label' => $label,
					'url'   => $url,
				),
				JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
			)
		);

		$parsed = function_exists( 'parse_blocks' ) ? parse_blocks( $markup ) : array();
		return ! empty( $parsed[0] ) ? $parsed[0] : null;
	}
}

if ( ! function_exists( 'almaden_bookster_get_filtered_navigation_fallback_blocks' ) ) {
	function almaden_bookster_get_filtered_navigation_fallback_blocks( $fallback_blocks ) {
		$fallback_blocks = is_array( $fallback_blocks ) ? $fallback_blocks : array();
		$shell_home_block = null;
		$shell_home_page_id = function_exists( 'almaden_bookster_get_shell_home_page_id' ) ? almaden_bookster_get_shell_home_page_id() : 0;

		if ( almaden_bookster_should_show_shell_home_in_regular_menu() ) {
			$shell_home_block = almaden_bookster_build_navigation_link_block(
				function_exists( 'almaden_bookster_get_shell_home_title' ) ? almaden_bookster_get_shell_home_title() : 'Almaden App',
				function_exists( 'almaden_bookster_get_shell_home_page_url' ) ? almaden_bookster_get_shell_home_page_url() : home_url( '/' )
			);
		}

		if ( almaden_bookster_navigation_block_contains_page_list( $fallback_blocks ) ) {
			$pages = get_pages(
				array(
					'sort_column' => 'menu_order,post_title',
					'order'       => 'asc',
				)
			);

			$blocks = array();

			foreach ( $pages as $page ) {
				if ( ! is_object( $page ) || empty( $page->ID ) ) {
					continue;
				}

				if ( ! empty( $page->post_parent ) ) {
					continue;
				}

				$page_id = (int) $page->ID;
				$page_url = get_permalink( $page_id );
				$page_title = isset( $page->post_title ) ? (string) $page->post_title : '';

				if ( $shell_home_page_id > 0 && $shell_home_page_id === $page_id ) {
					continue;
				}

				if ( almaden_bookster_should_strip_shell_navigation_item( $page_url, $page_title, $page_id ) ) {
					continue;
				}

				$navigation_block = almaden_bookster_build_navigation_link_block( $page_title, $page_url );
				if ( $navigation_block ) {
					$blocks[] = $navigation_block;
				}
			}

			if ( $shell_home_block ) {
				array_unshift( $blocks, $shell_home_block );
			}

			return $blocks;
		}

		$filtered_blocks = almaden_bookster_prune_shell_pages_from_block_children( $fallback_blocks );

		if ( $shell_home_block ) {
			array_unshift( $filtered_blocks, $shell_home_block );
		}

		return $filtered_blocks;
	}
}

if ( ! function_exists( 'almaden_bookster_prune_shell_pages_from_block_children' ) ) {
	function almaden_bookster_prune_shell_pages_from_block_children( $blocks ) {
		if ( ! is_array( $blocks ) ) {
			return $blocks;
		}

		$filtered_blocks = array();

		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				$filtered_blocks[] = $block;
				continue;
			}

			$block_name  = isset( $block['blockName'] ) ? (string) $block['blockName'] : '';
			$block_attrs = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();
			$block_url   = isset( $block_attrs['url'] ) ? (string) $block_attrs['url'] : '';
			$block_label  = '';

			if ( isset( $block_attrs['label'] ) ) {
				$block_label = (string) $block_attrs['label'];
			} elseif ( isset( $block_attrs['title'] ) ) {
				$block_label = (string) $block_attrs['title'];
			}

			$block_id = isset( $block_attrs['id'] ) ? absint( $block_attrs['id'] ) : 0;

			if ( in_array( $block_name, array( 'core/navigation-link', 'core/page-list-item' ), true ) && almaden_bookster_should_strip_shell_navigation_item( $block_url, $block_label, $block_id ) ) {
				continue;
			}

			if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				$block['innerBlocks'] = almaden_bookster_prune_shell_pages_from_block_children( $block['innerBlocks'] );
			}

			$filtered_blocks[] = $block;
		}

		return $filtered_blocks;
	}
}

if ( ! function_exists( 'almaden_bookster_prune_shell_pages_from_block_tree' ) ) {
	function almaden_bookster_prune_shell_pages_from_block_tree( $parsed_block ) {
		if ( ! is_array( $parsed_block ) || empty( $parsed_block['blockName'] ) ) {
			return $parsed_block;
		}

		if ( ! empty( $parsed_block['innerBlocks'] ) && is_array( $parsed_block['innerBlocks'] ) ) {
			$parsed_block['innerBlocks'] = almaden_bookster_prune_shell_pages_from_block_children( $parsed_block['innerBlocks'] );
		}

		return $parsed_block;
	}
}

add_filter( 'render_block_data', 'almaden_bookster_prune_shell_pages_from_block_tree', 20 );
add_filter( 'block_core_navigation_render_fallback', 'almaden_bookster_get_filtered_navigation_fallback_blocks', 20 );
add_filter( 'render_block_core/navigation-link', 'almaden_bookster_strip_shell_pages_from_navigation_block', 15, 2 );
add_filter( 'render_block_core/page-list-item', 'almaden_bookster_strip_shell_pages_from_navigation_block', 15, 2 );

if ( ! function_exists( 'almaden_bookster_cleanup_navigation_posts' ) ) {
	function almaden_bookster_cleanup_navigation_posts() {
		$navigation_posts = get_posts(
			array(
				'post_type'              => 'wp_navigation',
				'post_status'            => 'any',
				'posts_per_page'         => -1,
				'no_found_rows'          => true,
				'orderby'                => 'ID',
				'order'                  => 'ASC',
				'suppress_filters'       => true,
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false,
			)
		);

		foreach ( $navigation_posts as $navigation_post ) {
			if ( ! $navigation_post instanceof WP_Post ) {
				continue;
			}

			$blocks = function_exists( 'parse_blocks' ) ? parse_blocks( (string) $navigation_post->post_content ) : array();
			$clean_blocks = almaden_bookster_prune_shell_pages_from_block_children( $blocks );
			$clean_markup = function_exists( 'serialize_blocks' ) ? serialize_blocks( $clean_blocks ) : '';

			if ( $clean_markup !== (string) $navigation_post->post_content ) {
				wp_update_post(
					array(
						'ID'           => (int) $navigation_post->ID,
						'post_content' => $clean_markup,
					)
				);
			}
		}
	}
}

if ( ! function_exists( 'almaden_bookster_cleanup_classic_nav_menus' ) ) {
	function almaden_bookster_cleanup_classic_nav_menus() {
		$menus = wp_get_nav_menus();

		foreach ( $menus as $menu ) {
			$items = wp_get_nav_menu_items( $menu->term_id, array( 'update_post_term_cache' => false ) );
			if ( empty( $items ) || ! is_array( $items ) ) {
				continue;
			}

			foreach ( $items as $item ) {
				if ( ! $item instanceof WP_Post ) {
					continue;
				}

				$item_url   = isset( $item->url ) ? (string) $item->url : '';
				$item_title = isset( $item->title ) ? (string) $item->title : '';
				$item_id    = isset( $item->object_id ) ? absint( $item->object_id ) : 0;

				if ( almaden_bookster_should_strip_shell_navigation_item( $item_url, $item_title, $item_id ) ) {
					wp_delete_post( (int) $item->ID, true );
				}
			}
		}
	}
}

if ( ! function_exists( 'almaden_bookster_cleanup_navigation_entries_on_activation' ) ) {
	function almaden_bookster_cleanup_navigation_entries_on_activation() {
		almaden_bookster_cleanup_classic_nav_menus();
		almaden_bookster_cleanup_navigation_posts();
	}
}
