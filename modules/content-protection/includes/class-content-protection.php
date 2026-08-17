<?php
/**
 * Content protection policy and Reader asset renderer.
 *
 * @package AlmadenBookster
 */

namespace AlmadenBookster\ContentProtection;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Content_Protection {
	/**
	 * Return the effective content-protection policy for a book.
	 *
	 * @param int $book_id Book post ID.
	 * @return array<string, mixed>
	 */
	public static function get_policy( $book_id ) {
		return Protection_Policy::resolve( $book_id );
	}

	/**
	 * Remove chapter bodies from the initial Reader payload when delivery is on demand.
	 *
	 * @param array $chapters Chapter metadata and content.
	 * @param int   $book_id  Book post ID.
	 * @return array
	 */
	public static function prepare_chapters( $chapters, $book_id ) {
		$policy = self::get_policy( $book_id );
		if ( empty( $policy['enabled'] ) || ! is_user_logged_in() || 'on_demand' !== ( $policy['chapter_delivery'] ?? '' ) || ! is_array( $chapters ) ) {
			return $chapters;
		}

		return array_map(
			static function( $chapter ) {
				if ( is_array( $chapter ) ) {
					unset( $chapter['content'] );
				}
				return $chapter;
			},
			$chapters
		);
	}

	/**
	 * Print the module stylesheet in the standalone Reader head.
	 *
	 * @param int $book_id Book post ID.
	 * @return void
	 */
	public static function render_head( $book_id ) {
		$policy = self::get_policy( $book_id );
		if ( empty( $policy['enabled'] ) ) {
			return;
		}

		$relative_path = 'assets/css/content-protection.css';
		$path          = ALMADEN_BOOKSTER_CONTENT_PROTECTION_DIR . $relative_path;
		$url           = ALMADEN_BOOKSTER_CONTENT_PROTECTION_URL . $relative_path;
		$version       = file_exists( $path ) ? (string) filemtime( $path ) : '1.0.0';
		?>
		<link rel="stylesheet" id="almaden-content-protection-css" href="<?php echo esc_url( add_query_arg( 'ver', $version, $url ) ); ?>">
		<?php
		if ( ! empty( $policy['block_print'] ) ) {
			self::render_stylesheet( 'assets/css/content-protection-print.css', 'almaden-content-protection-print-css', 'print' );
		}
	}

	/**
	 * Print the accessible notice, runtime configuration, and guard script.
	 *
	 * @param int $book_id Book post ID.
	 * @return void
	 */
	public static function render_footer( $book_id ) {
		$policy = self::get_policy( $book_id );
		if ( empty( $policy['enabled'] ) ) {
			return;
		}

		$runtime_delivery = is_user_logged_in() ? sanitize_key( (string) ( $policy['chapter_delivery'] ?? 'inline' ) ) : 'inline';
		$config = array(
			'enabled'           => true,
			'blockClipboard'    => ! empty( $policy['block_clipboard'] ),
			'blockDrag'         => ! empty( $policy['block_drag'] ),
			'blockPrint'        => ! empty( $policy['block_print'] ),
			'chapterDelivery'   => $runtime_delivery,
			'bookId'            => absint( $book_id ),
			'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
			'chapterNonce'      => wp_create_nonce( 'almaden_reader_content_' . absint( $book_id ) ),
			'telemetryEnabled'  => ! empty( $policy['telemetry'] ) && is_user_logged_in(),
			'telemetryNonce'    => wp_create_nonce( 'almaden_reader_telemetry_' . absint( $book_id ) ),
			'protectedSelector' => '[data-almaden-protected-content], [data-almaden-protected-excerpt]',
			'allowedSelector'   => '[data-almaden-copy-allowed], input, textarea, select, [contenteditable="true"]',
			'notice'            => __( 'La copia de texto está desactivada en este ebook. Puedes guardarlo como highlight.', 'almaden-bookster' ),
			'loadingNotice'     => __( 'Cargando capítulo…', 'almaden-bookster' ),
			'chapterError'      => __( 'No fue posible cargar este capítulo. Intenta nuevamente.', 'almaden-bookster' ),
		);
		$relative_path = 'assets/js/clipboard-guard.js';
		$path          = ALMADEN_BOOKSTER_CONTENT_PROTECTION_DIR . $relative_path;
		$url           = ALMADEN_BOOKSTER_CONTENT_PROTECTION_URL . $relative_path;
		$version       = file_exists( $path ) ? (string) filemtime( $path ) : '1.0.0';
		?>
		<div id="almaden-content-protection-notice" class="almaden-content-protection-notice" role="status" aria-live="polite" aria-atomic="true" hidden></div>
		<div id="almaden-content-protection-print-notice" aria-hidden="true">
			<strong><?php esc_html_e( 'Impresión no disponible', 'almaden-bookster' ); ?></strong>
			<span><?php echo esc_html( get_the_title( $book_id ) ); ?></span>
			<small><?php esc_html_e( 'Este ebook está protegido para lectura dentro de Almaden.', 'almaden-bookster' ); ?></small>
		</div>
		<script>window.almadenContentProtectionConfig = <?php echo wp_json_encode( $config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); ?>;</script>
		<?php if ( 'on_demand' === $config['chapterDelivery'] ) : ?>
		<script id="almaden-chapter-loader-js" src="<?php echo esc_url( self::asset_url( 'assets/js/chapter-loader.js' ) ); ?>"></script>
		<?php endif; ?>
		<?php if ( $config['telemetryEnabled'] ) : ?>
		<script id="almaden-protection-telemetry-js" src="<?php echo esc_url( self::asset_url( 'assets/js/telemetry.js' ) ); ?>"></script>
		<?php endif; ?>
		<?php if ( $config['blockPrint'] ) : ?>
		<script id="almaden-print-guard-js" src="<?php echo esc_url( self::asset_url( 'assets/js/print-guard.js' ) ); ?>"></script>
		<?php endif; ?>
		<script id="almaden-content-protection-js" src="<?php echo esc_url( add_query_arg( 'ver', $version, $url ) ); ?>"></script>
		<?php
	}

	/**
	 * Build a cache-busted module asset URL.
	 *
	 * @param string $relative_path Relative module path.
	 * @return string
	 */
	private static function asset_url( $relative_path ) {
		$path    = ALMADEN_BOOKSTER_CONTENT_PROTECTION_DIR . $relative_path;
		$version = file_exists( $path ) ? (string) filemtime( $path ) : '1.0.0';
		return add_query_arg( 'ver', $version, ALMADEN_BOOKSTER_CONTENT_PROTECTION_URL . $relative_path );
	}

	/**
	 * Render a stylesheet owned by the module.
	 *
	 * @param string $relative_path Relative module path.
	 * @param string $id            DOM id.
	 * @param string $media         Media query target.
	 * @return void
	 */
	private static function render_stylesheet( $relative_path, $id, $media = 'all' ) {
		printf(
			'<link rel="stylesheet" id="%1$s" href="%2$s" media="%3$s">',
			esc_attr( $id ),
			esc_url( self::asset_url( $relative_path ) ),
			esc_attr( $media )
		);
	}
}
