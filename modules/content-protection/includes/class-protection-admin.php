<?php
/**
 * Operational controls for safe rollout and rollback.
 *
 * @package AlmadenBookster
 */

namespace AlmadenBookster\ContentProtection;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Protection_Admin {
	const PAGE = 'almaden-content-protection';

	/** Register admin controls. */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_page' ) );
		add_action( 'admin_post_almaden_save_content_protection', array( __CLASS__, 'save_settings' ) );
		add_action( 'add_meta_boxes_almaden-books', array( __CLASS__, 'register_meta_box' ) );
		add_action( 'save_post_almaden-books', array( __CLASS__, 'save_book_override' ) );
	}

	/** Add the page below the Almaden Books post type. */
	public static function register_page() {
		add_submenu_page(
			'edit.php?post_type=almaden-books',
			__( 'Protección de contenido', 'almaden-bookster' ),
			__( 'Protección', 'almaden-bookster' ),
			'manage_options',
			self::PAGE,
			array( __CLASS__, 'render_page' )
		);
	}

	/** Render global switch and deterministic rollout control. */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$enabled = Protection_Policy::global_enabled();
		$rollout = Protection_Policy::rollout_percentage();
		?>
		<div id="almaden-content-protection-admin" class="wrap">
			<h1><?php esc_html_e( 'Protección de contenido', 'almaden-bookster' ); ?></h1>
			<p><?php esc_html_e( 'Controla el rollout del Reader. La bandera global funciona como apagado de emergencia; con ella activa, los overrides por libro prevalecen sobre el porcentaje.', 'almaden-bookster' ); ?></p>
			<?php if ( isset( $_GET['updated'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Configuración guardada.', 'almaden-bookster' ); ?></p></div>
			<?php endif; ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="almaden_save_content_protection">
				<?php wp_nonce_field( 'almaden_save_content_protection' ); ?>
				<table class="form-table" role="presentation"><tbody>
					<tr><th scope="row"><?php esc_html_e( 'Bandera global', 'almaden-bookster' ); ?></th><td>
						<label><input type="checkbox" name="enabled" value="1" <?php checked( $enabled ); ?>> <?php esc_html_e( 'Activar protección', 'almaden-bookster' ); ?></label>
					</td></tr>
					<tr><th scope="row"><label for="almaden-protection-rollout"><?php esc_html_e( 'Rollout', 'almaden-bookster' ); ?></label></th><td>
						<input id="almaden-protection-rollout" type="number" min="0" max="100" name="rollout" value="<?php echo esc_attr( $rollout ); ?>"> %
						<p class="description"><?php esc_html_e( 'La cohorte es estable por libro. Usa 0 para pausar o 100 para activar en todos.', 'almaden-bookster' ); ?></p>
					</td></tr>
				</tbody></table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/** Persist global controls. */
	public static function save_settings() {
		check_admin_referer( 'almaden_save_content_protection' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No tienes permiso para cambiar esta configuración.', 'almaden-bookster' ), '', array( 'response' => 403 ) );
		}
		update_option( Protection_Policy::ENABLED_OPTION, isset( $_POST['enabled'] ) ? '1' : '0', false );
		$rollout = isset( $_POST['rollout'] ) ? max( 0, min( 100, absint( $_POST['rollout'] ) ) ) : 0;
		update_option( Protection_Policy::ROLLOUT_OPTION, $rollout, false );
		wp_safe_redirect( add_query_arg( array( 'post_type' => 'almaden-books', 'page' => self::PAGE, 'updated' => '1' ), admin_url( 'edit.php' ) ) );
		exit;
	}

	/** Add per-book rollback control. */
	public static function register_meta_box() {
		add_meta_box( 'almaden-content-protection-book', __( 'Protección del Reader', 'almaden-bookster' ), array( __CLASS__, 'render_meta_box' ), 'almaden-books', 'side' );
	}

	/** Render per-book policy override. */
	public static function render_meta_box( $post ) {
		$value = sanitize_key( (string) get_post_meta( $post->ID, Protection_Policy::BOOK_META, true ) );
		$value = in_array( $value, array( 'enabled', 'disabled' ), true ) ? $value : 'inherit';
		wp_nonce_field( 'almaden_content_protection_book_' . $post->ID, 'almaden_content_protection_nonce' );
		?>
		<label for="almaden-content-protection-override" class="screen-reader-text"><?php esc_html_e( 'Política de protección', 'almaden-bookster' ); ?></label>
		<select id="almaden-content-protection-override" name="almaden_content_protection_override" class="widefat">
			<option value="inherit" <?php selected( $value, 'inherit' ); ?>><?php esc_html_e( 'Heredar configuración global', 'almaden-bookster' ); ?></option>
			<option value="enabled" <?php selected( $value, 'enabled' ); ?>><?php esc_html_e( 'Forzar activada', 'almaden-bookster' ); ?></option>
			<option value="disabled" <?php selected( $value, 'disabled' ); ?>><?php esc_html_e( 'Forzar desactivada', 'almaden-bookster' ); ?></option>
		</select>
		<?php
	}

	/** Save the per-book override safely. */
	public static function save_book_override( $post_id ) {
		$nonce = isset( $_POST['almaden_content_protection_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['almaden_content_protection_nonce'] ) ) : '';
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'almaden_content_protection_book_' . $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$value = isset( $_POST['almaden_content_protection_override'] ) ? sanitize_key( wp_unslash( $_POST['almaden_content_protection_override'] ) ) : 'inherit';
		if ( in_array( $value, array( 'enabled', 'disabled' ), true ) ) {
			update_post_meta( $post_id, Protection_Policy::BOOK_META, $value );
		} else {
			delete_post_meta( $post_id, Protection_Policy::BOOK_META );
		}
	}
}
