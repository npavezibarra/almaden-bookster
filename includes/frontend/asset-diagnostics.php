<?php
/**
 * Asset helpers and diagnostics for isolated Almaden app shells.
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'almaden_bookster_asset_path' ) ) {
	function almaden_bookster_asset_path( $relative_path ) {
		return dirname( __DIR__, 2 ) . '/' . ltrim( (string) $relative_path, '/' );
	}
}

if ( ! function_exists( 'almaden_bookster_asset_url' ) ) {
	function almaden_bookster_asset_url( $relative_path ) {
		$relative_path = ltrim( (string) $relative_path, '/' );
		$path = almaden_bookster_asset_path( $relative_path );
		$url = plugins_url( $relative_path, dirname( __DIR__, 2 ) . '/almaden-bookster.php' );
		$version = file_exists( $path ) ? (string) filemtime( $path ) : (string) time();

		return add_query_arg( 'ver', $version, $url );
	}
}

if ( ! function_exists( 'almaden_bookster_editor_css_url' ) ) {
	function almaden_bookster_editor_css_url() {
		return almaden_bookster_asset_url( 'assets/css/editor-style.css' );
	}
}

if ( ! function_exists( 'almaden_bookster_fontawesome_css_url' ) ) {
	function almaden_bookster_fontawesome_css_url() {
		return almaden_bookster_asset_url( 'assets/vendor/fontawesome/css/all.min.css' );
	}
}

if ( ! function_exists( 'almaden_bookster_debug_assets_enabled' ) ) {
	function almaden_bookster_debug_assets_enabled() {
		return current_user_can( 'manage_options' )
			&& isset( $_GET['almaden_debug_assets'] )
			&& '1' === (string) wp_unslash( $_GET['almaden_debug_assets'] );
	}
}

if ( ! function_exists( 'almaden_bookster_activate_shell_asset_quarantine' ) ) {
	function almaden_bookster_activate_shell_asset_quarantine( $surface = 'shell' ) {
		$GLOBALS['almaden_bookster_shell_asset_quarantine'] = sanitize_key( (string) $surface );
	}
}

if ( ! function_exists( 'almaden_bookster_shell_asset_quarantine_allowed_style' ) ) {
	function almaden_bookster_shell_asset_quarantine_allowed_style( $handle, $src ) {
		$handle = (string) $handle;
		$src = (string) $src;
		$allowed_handles = array(
			'admin-bar',
			'buttons',
			'common',
			'dashicons',
			'imgareaselect',
			'media-views',
			'mediaelement',
			'wp-mediaelement',
		);
		if ( in_array( $handle, $allowed_handles, true ) ) {
			return true;
		}
		if ( 0 === strpos( $handle, 'almaden' ) || 0 === strpos( $handle, 'almaden-bookster' ) ) {
			return true;
		}
		if ( false !== strpos( $src, '/wp-content/plugins/almaden-bookster/' ) ) {
			return true;
		}

		return false;
	}
}

if ( ! function_exists( 'almaden_bookster_apply_shell_asset_quarantine' ) ) {
	function almaden_bookster_apply_shell_asset_quarantine() {
		if ( empty( $GLOBALS['almaden_bookster_shell_asset_quarantine'] ) || ! isset( $GLOBALS['wp_styles'] ) ) {
			return;
		}
		$wp_styles = $GLOBALS['wp_styles'];
		if ( ! is_object( $wp_styles ) || empty( $wp_styles->queue ) ) {
			return;
		}
		foreach ( (array) $wp_styles->queue as $handle ) {
			$registered = isset( $wp_styles->registered[ $handle ] ) ? $wp_styles->registered[ $handle ] : null;
			$src = is_object( $registered ) && isset( $registered->src ) ? (string) $registered->src : '';
			if ( ! almaden_bookster_shell_asset_quarantine_allowed_style( $handle, $src ) ) {
				wp_dequeue_style( $handle );
				wp_deregister_style( $handle );
			}
		}
	}
}
add_action( 'wp_enqueue_scripts', 'almaden_bookster_apply_shell_asset_quarantine', PHP_INT_MAX );
add_action( 'admin_enqueue_scripts', 'almaden_bookster_apply_shell_asset_quarantine', PHP_INT_MAX );

if ( ! function_exists( 'almaden_bookster_asset_diagnostics_payload' ) ) {
	function almaden_bookster_asset_diagnostics_payload( $surface = 'shell' ) {
		$assets = array(
			'editorCss' => 'assets/css/editor-style.css',
			'bundledFontsCss' => 'assets/fonts/bundled/bundled-fonts.css',
			'fontAwesomeCss' => 'assets/vendor/fontawesome/css/all.min.css',
			'fontAwesomeSolid' => 'assets/vendor/fontawesome/webfonts/fa-solid-900.woff2',
			'fontAwesomeRegular' => 'assets/vendor/fontawesome/webfonts/fa-regular-400.woff2',
			'fontAwesomeBrands' => 'assets/vendor/fontawesome/webfonts/fa-brands-400.woff2',
			'typstBinary' => 'runtime/typst/typst',
		);
		$checks = array();
		foreach ( $assets as $key => $relative_path ) {
			$path = almaden_bookster_asset_path( $relative_path );
			$checks[ $key ] = array(
				'relativePath' => $relative_path,
				'path' => $path,
				'url' => 'typstBinary' === $key ? '' : almaden_bookster_asset_url( $relative_path ),
				'exists' => file_exists( $path ),
				'readable' => is_readable( $path ),
				'executable' => is_executable( $path ),
				'size' => file_exists( $path ) ? filesize( $path ) : 0,
				'mtime' => file_exists( $path ) ? filemtime( $path ) : 0,
			);
		}

		return array(
			'surface' => $surface,
			'pluginVersion' => defined( 'ALMADEN_BOOKSTER_VERSION' ) ? ALMADEN_BOOKSTER_VERSION : '1.0.0',
			'pluginDir' => dirname( __DIR__, 2 ) . '/',
			'pluginUrl' => plugins_url( '', dirname( __DIR__, 2 ) . '/almaden-bookster.php' ),
			'phpVersion' => PHP_VERSION,
			'serverSoftware' => isset( $_SERVER['SERVER_SOFTWARE'] ) ? (string) wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) : '',
			'assets' => $checks,
		);
	}
}

if ( ! function_exists( 'almaden_bookster_render_asset_diagnostics' ) ) {
	function almaden_bookster_render_asset_diagnostics( $surface = 'shell' ) {
		if ( ! almaden_bookster_debug_assets_enabled() ) {
			return;
		}

		$payload = almaden_bookster_asset_diagnostics_payload( $surface );
		?>
		<script>
			window.almadenAssetDiagnostics = <?php echo wp_json_encode( $payload ); ?>;
			(function () {
				function styleOf(selector) {
					var node = document.querySelector(selector);
					if (!node) return null;
					var computed = window.getComputedStyle(node);
					return {
						selector: selector,
						fontFamily: computed.fontFamily,
						fontSize: computed.fontSize,
						fontWeight: computed.fontWeight,
						lineHeight: computed.lineHeight,
						height: computed.height,
						padding: computed.padding,
						display: computed.display
					};
				}
				function assetRows() {
					var assets = window.almadenAssetDiagnostics.assets || {};
					return Object.keys(assets).map(function (key) {
						var asset = assets[key];
						return '<tr><td>' + key + '</td><td>' + (asset.exists ? 'yes' : 'no') + '</td><td>' + asset.size + '</td><td><code>' + (asset.url || asset.relativePath) + '</code></td></tr>';
					}).join('');
				}
				function collect() {
					var sheets = Array.prototype.slice.call(document.styleSheets).map(function (sheet) {
						return sheet.href || 'inline';
					});
					var almadenSheets = sheets.filter(function (href) {
						return href.indexOf('/wp-content/plugins/almaden-bookster/') !== -1;
					});
					var foreignSheets = sheets.filter(function (href) {
						return href !== 'inline' && href.indexOf('/wp-content/plugins/almaden-bookster/') === -1 && href.indexOf('/wp-includes/') === -1;
					});
					return {
						fonts: {
							urbanist: document.fonts && document.fonts.check ? document.fonts.check('16px Urbanist') : null,
							fontAwesome: document.fonts && document.fonts.check ? document.fonts.check('900 16px "Font Awesome 6 Free"') : null
						},
						styles: [
							styleOf('body'),
							styleOf('#almaden-app-nav'),
							styleOf('#almaden-workshop'),
							styleOf('.book-card'),
							styleOf('.fa-solid'),
							styleOf('button')
						],
						almadenStyleSheets: almadenSheets,
						foreignStyleSheets: foreignSheets,
						styleSheets: sheets
					};
				}
				function render() {
					var runtime = collect();
					window.almadenAssetDiagnostics.runtime = runtime;
					var panel = document.createElement('div');
					panel.id = 'almaden-asset-debug-panel';
					panel.innerHTML = '<strong>Almaden Assets Debug</strong>'
						+ '<button type="button" aria-label="Cerrar" onclick="this.parentNode.remove()">x</button>'
						+ '<pre>' + JSON.stringify(runtime, null, 2).replace(/[<>&]/g, function (c) { return ({'<':'&lt;','>':'&gt;','&':'&amp;'}[c]); }) + '</pre>'
						+ '<table><thead><tr><th>Asset</th><th>Exists</th><th>Size</th><th>URL</th></tr></thead><tbody>' + assetRows() + '</tbody></table>';
					document.body.appendChild(panel);
				}
				if (document.readyState === 'loading') {
					document.addEventListener('DOMContentLoaded', render);
				} else {
					render();
				}
			}());
		</script>
		<style>
			#almaden-asset-debug-panel {
				position: fixed;
				right: 16px;
				bottom: 16px;
				z-index: 999999;
				width: min(720px, calc(100vw - 32px));
				max-height: 70vh;
				overflow: auto;
				padding: 14px;
				border: 1px solid #111827;
				border-radius: 8px;
				background: #ffffff;
				color: #111827;
				font: 12px/1.4 system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
				box-shadow: 0 18px 50px rgba(15, 23, 42, 0.24);
			}
			#almaden-asset-debug-panel button {
				float: right;
				border: 0;
				background: #111827;
				color: #ffffff;
				border-radius: 4px;
				cursor: pointer;
			}
			#almaden-asset-debug-panel pre {
				white-space: pre-wrap;
			}
			#almaden-asset-debug-panel table {
				width: 100%;
				border-collapse: collapse;
			}
			#almaden-asset-debug-panel th,
			#almaden-asset-debug-panel td {
				padding: 4px;
				border-top: 1px solid #e5e7eb;
				text-align: left;
				vertical-align: top;
			}
		</style>
		<?php
	}
}

if ( ! function_exists( 'almaden_bookster_maybe_render_asset_report' ) ) {
	function almaden_bookster_maybe_render_asset_report() {
		if ( ! almaden_bookster_debug_assets_enabled() && ! ( current_user_can( 'manage_options' ) && isset( $_GET['almaden_asset_report'] ) && '1' === (string) wp_unslash( $_GET['almaden_asset_report'] ) ) ) {
			return;
		}

		if ( ! isset( $_GET['almaden_asset_report'] ) || '1' !== (string) wp_unslash( $_GET['almaden_asset_report'] ) ) {
			return;
		}

		$payload = almaden_bookster_asset_diagnostics_payload( 'asset-report' );
		$typst = $payload['assets']['typstBinary']['path'] ?? '';
		if ( '' !== $typst && is_executable( $typst ) && function_exists( 'shell_exec' ) ) {
			$payload['typstVersion'] = trim( (string) shell_exec( escapeshellarg( $typst ) . ' --version 2>&1' ) );
		}
		$payload['phpSapi'] = PHP_SAPI;
		$payload['requestUri'] = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';

		wp_send_json( $payload );
	}
}
add_action( 'template_redirect', 'almaden_bookster_maybe_render_asset_report', 0 );
