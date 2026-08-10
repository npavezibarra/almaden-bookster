<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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

if ( ! function_exists( 'almaden_bookster_inject_bookshelf_into_navigation_block_html' ) ) {
	function almaden_bookster_inject_bookshelf_into_navigation_block_html( $block_content, $block = array() ) {
		if ( is_admin() || '' === trim( (string) $block_content ) || ! almaden_bookster_should_show_bookshelf_in_regular_menu() ) {
			return $block_content;
		}

		if ( ! isset( $block['blockName'] ) || 0 !== strpos( (string) $block['blockName'], 'core/navigation' ) ) {
			return $block_content;
		}

		$bookshelf_url = function_exists( 'almaden_bookster_get_store_page_url' ) ? esc_url( almaden_bookster_get_store_page_url() ) : '';
		$bookshelf_label = function_exists( 'almaden_bookster_get_store_menu_label' ) ? almaden_bookster_get_store_menu_label() : ( function_exists( 'almaden_bookster_get_store_title' ) ? almaden_bookster_get_store_title() : 'Ebook Store' );

		if ( '' !== $bookshelf_url && false !== stripos( $block_content, $bookshelf_url ) ) {
			return $block_content;
		}

		if ( '' !== $bookshelf_label && false !== stripos( wp_strip_all_tags( $block_content ), $bookshelf_label ) ) {
			return $block_content;
		}

		$bookshelf_item = almaden_bookster_build_bookshelf_menu_item_html();
		if ( '' === $bookshelf_item ) {
			return $block_content;
		}

		$updated_block_content = preg_replace(
			'/<ul([^>]*)>/i',
			'<ul$1>' . $bookshelf_item,
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
			$bookshelf_item . '</ul>',
			$block_content,
			1
		);

		return null === $updated_block_content ? $block_content : $updated_block_content;
	}
}

if ( ! function_exists( 'almaden_bookster_strip_shell_pages_from_menu_items_html' ) ) {
	function almaden_bookster_strip_shell_pages_from_menu_items_html( $items ) {
		if ( is_admin() || '' === trim( (string) $items ) ) {
			return $items;
		}

		foreach ( almaden_bookster_get_shell_navigation_exclusion_rules() as $rule ) {
			$rule_url   = isset( $rule['url'] ) ? trim( (string) $rule['url'] ) : '';

			if ( '' !== $rule_url ) {
				$items = preg_replace(
					"#<li[^>]*>\\s*<a[^>]*href=(\"|')" . preg_quote( esc_url( $rule_url ), '#' ) . "(\"|')[^>]*>.*?</a>\\s*</li>#is",
					'',
					$items
				);
			}
		}

		return $items;
	}
}

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

			if ( '' !== $rule_url ) {
				$block_content = preg_replace(
					"#<li[^>]*>\\s*<a[^>]*href=(\"|')" . preg_quote( esc_url( $rule_url ), '#' ) . "(\"|')[^>]*>.*?</a>\\s*</li>#is",
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
