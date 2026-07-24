<?php

namespace AlmadenBookster\BlogPost;

use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Editor {
	private const PAGE_KEY = 'blog_creator';
	private const NONCE_ACTION = 'almaden_blog_post_nonce';
	private const AJAX_LIST = 'almaden_blog_post_list';
	private const AJAX_GET = 'almaden_blog_post_get';
	private const AJAX_SAVE = 'almaden_blog_post_save';
	private const AJAX_DELETE = 'almaden_blog_post_delete';

	public static function init(): void {
		add_action( 'template_redirect', array( __CLASS__, 'maybe_render_editor' ), 5 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'wp_ajax_' . self::AJAX_LIST, array( __CLASS__, 'ajax_list_posts' ) );
		add_action( 'wp_ajax_' . self::AJAX_GET, array( __CLASS__, 'ajax_get_post' ) );
		add_action( 'wp_ajax_' . self::AJAX_SAVE, array( __CLASS__, 'ajax_save_post' ) );
		add_action( 'wp_ajax_' . self::AJAX_DELETE, array( __CLASS__, 'ajax_delete_post' ) );
	}

	public static function can_access_editor( ?int $user_id = null ): bool {
		$user_id = null === $user_id ? get_current_user_id() : absint( $user_id );
		if ( $user_id <= 0 ) {
			return false;
		}

		return current_user_can( 'edit_posts' ) || current_user_can( 'edit_others_posts' ) || current_user_can( 'manage_options' );
	}

	public static function can_edit_post( int $post_id, ?int $user_id = null ): bool {
		$user_id = null === $user_id ? get_current_user_id() : absint( $user_id );
		if ( $user_id <= 0 || $post_id <= 0 ) {
			return false;
		}

		if ( current_user_can( 'manage_options' ) || current_user_can( 'edit_others_posts' ) ) {
			return true;
		}

		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			return false;
		}

		return (int) $post->post_author === $user_id && current_user_can( 'edit_post', $post_id );
	}

	public static function maybe_render_editor(): void {
		$slug = function_exists( 'almaden_bookster_get_blog_creator_slug' ) ? almaden_bookster_get_blog_creator_slug() : 'blog-editor';
		if ( ! is_page( $slug ) || ! is_main_query() ) {
			return;
		}

		$is_auth_modal_view = isset( $_GET['pl_auth_view'] ) && in_array( sanitize_key( (string) wp_unslash( $_GET['pl_auth_view'] ) ), array( 'login', 'register' ), true );
		if ( ! is_user_logged_in() && ! $is_auth_modal_view ) {
			auth_redirect();
		}

		if ( is_user_logged_in() && ! self::can_access_editor() ) {
			wp_die( esc_html__( 'No tienes permisos para acceder al editor de blog.', 'almaden-bookster' ) );
		}

		show_admin_bar( false );
		wp_enqueue_media();

		$template_path = ALMADEN_BOOKSTER_BLOG_POST_PLUGIN_DIR . 'templates/blog-post/blog-post-app.php';
		if ( file_exists( $template_path ) ) {
			require_once $template_path;
			exit;
		}

		wp_die( esc_html__( 'Plantilla del editor de blog no encontrada.', 'almaden-bookster' ) );
	}

	public static function enqueue_assets(): void {
		$slug = function_exists( 'almaden_bookster_get_blog_creator_slug' ) ? almaden_bookster_get_blog_creator_slug() : 'blog-editor';
		if ( ! is_page( $slug ) || ! is_main_query() ) {
			return;
		}

		$css_path = ALMADEN_BOOKSTER_BLOG_POST_PLUGIN_DIR . 'assets/css/blog-post-editor.css';
		$js_path = ALMADEN_BOOKSTER_BLOG_POST_PLUGIN_DIR . 'assets/js/blog-post-editor.js';

		$css_ver = file_exists( $css_path ) ? (string) filemtime( $css_path ) : '1.0.0';
		$js_ver  = file_exists( $js_path ) ? (string) filemtime( $js_path ) : '1.0.0';

		wp_enqueue_style(
			'almaden-blog-post-editor',
			ALMADEN_BOOKSTER_BLOG_POST_PLUGIN_URL . 'assets/css/blog-post-editor.css',
			array(),
			$css_ver
		);

		wp_enqueue_style(
			'almaden-blog-post-editor-fonts',
			'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Newsreader:opsz,wght@6..72,200;6..72,300;6..72,400;6..72,500&display=swap',
			array(),
			null
		);

		wp_enqueue_script(
			'almaden-blog-post-editor',
			ALMADEN_BOOKSTER_BLOG_POST_PLUGIN_URL . 'assets/js/blog-post-editor.js',
			array( 'jquery' ),
			$js_ver,
			true
		);

		$current_user = wp_get_current_user();
		wp_localize_script(
			'almaden-blog-post-editor',
			'almadenBlogPostData',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( self::NONCE_ACTION ),
				'currentUserId' => (int) $current_user->ID,
				'pageUrl' => function_exists( 'almaden_bookster_get_blog_creator_page_url' ) ? almaden_bookster_get_blog_creator_page_url() : home_url( '/' ),
				'i18n'    => array(
					'loading' => __( 'Cargando...', 'almaden-bookster' ),
					'noPostsYet' => __( 'Aún no has creado posts.', 'almaden-bookster' ),
					'newPost' => __( 'Nuevo post', 'almaden-bookster' ),
					'save' => __( 'Guardar', 'almaden-bookster' ),
					'publish' => __( 'Publicar', 'almaden-bookster' ),
					'published' => __( 'Publicado', 'almaden-bookster' ),
					'draft' => __( 'Borrador', 'almaden-bookster' ),
					'edit' => __( 'Editar', 'almaden-bookster' ),
					'deleteConfirm' => __( '¿Eliminar este post?', 'almaden-bookster' ),
					'errorTitleRequired' => __( 'Debes agregar un título.', 'almaden-bookster' ),
					'errorGeneric' => __( 'No se pudo guardar el post.', 'almaden-bookster' ),
					'coverImage' => __( 'Imagen de portada', 'almaden-bookster' ),
					'uploadImage' => __( 'Subir imagen', 'almaden-bookster' ),
					'insertImage' => __( 'Insertar imagen', 'almaden-bookster' ),
					'selectImage' => __( 'Selecciona una imagen', 'almaden-bookster' ),
				),
			)
		);
	}

	public static function ajax_list_posts(): void {
		self::assert_ajax_access();

		$args = array(
			'post_type'      => 'post',
			'post_status'    => array( 'draft', 'publish' ),
			'posts_per_page' => 24,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'no_found_rows'   => true,
		);

		if ( ! current_user_can( 'edit_others_posts' ) && ! current_user_can( 'manage_options' ) ) {
			$args['author'] = get_current_user_id();
		}

		$posts = get_posts( $args );
		$items = array();

		foreach ( $posts as $post ) {
			if ( ! $post instanceof WP_Post ) {
				continue;
			}

			$items[] = self::normalize_post_card( $post );
		}

		wp_send_json_success(
			array(
				'posts' => $items,
			)
		);
	}

	public static function ajax_get_post(): void {
		self::assert_ajax_access();

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$post = $post_id > 0 ? get_post( $post_id ) : null;
		if ( ! $post instanceof WP_Post || 'post' !== $post->post_type || ! self::can_edit_post( $post_id ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Post no encontrado.', 'almaden-bookster' ),
				),
				404
			);
		}

		wp_send_json_success(
			array(
				'post' => self::normalize_post_detail( $post ),
			)
		);
	}

	public static function ajax_save_post(): void {
		self::assert_ajax_access();

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		if ( $post_id > 0 && ! self::can_edit_post( $post_id ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'No tienes permiso para editar este post.', 'almaden-bookster' ),
				),
				403
			);
		}

		$title = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$content = isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( $_POST['content'] ) ) : '';
		$excerpt = isset( $_POST['excerpt'] ) ? wp_kses_post( wp_unslash( $_POST['excerpt'] ) ) : '';
		$status = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'draft';
		$thumbnail_id = isset( $_POST['thumbnail_id'] ) ? absint( $_POST['thumbnail_id'] ) : 0;

		if ( '' === $title ) {
			wp_send_json_error(
				array(
					'message' => __( 'Debes agregar un título.', 'almaden-bookster' ),
				),
				400
			);
		}

		$status = in_array( $status, array( 'draft', 'publish' ), true ) ? $status : 'draft';
		$postarr = array(
			'post_title'   => $title,
			'post_content' => $content,
			'post_excerpt' => $excerpt,
			'post_status'  => $status,
			'post_type'    => 'post',
		);

		if ( $post_id > 0 ) {
			$postarr['ID'] = $post_id;
			$result = wp_update_post( $postarr, true );
		} else {
			$postarr['post_author'] = get_current_user_id();
			$result = wp_insert_post( $postarr, true );
		}

		if ( is_wp_error( $result ) ) {
			wp_send_json_error(
				array(
					'message' => $result->get_error_message(),
				),
				500
			);
		}

		$saved_post_id = (int) $result;
		if ( $thumbnail_id > 0 ) {
			set_post_thumbnail( $saved_post_id, $thumbnail_id );
		} else {
			delete_post_thumbnail( $saved_post_id );
		}

		$saved_post = get_post( $saved_post_id );
		wp_send_json_success(
			array(
				'post' => $saved_post instanceof WP_Post ? self::normalize_post_detail( $saved_post ) : array(
					'id' => $saved_post_id,
					'title' => $title,
				),
			)
		);
	}

	public static function ajax_delete_post(): void {
		self::assert_ajax_access();

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		if ( $post_id <= 0 || ! self::can_edit_post( $post_id ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'No tienes permiso para eliminar este post.', 'almaden-bookster' ),
				),
				403
			);
		}

		wp_trash_post( $post_id );
		wp_send_json_success( array( 'deleted' => true ) );
	}

	private static function assert_ajax_access(): void {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		if ( ! is_user_logged_in() || ! self::can_access_editor() ) {
			wp_send_json_error(
				array(
					'message' => __( 'No autorizado.', 'almaden-bookster' ),
				),
				403
			);
		}
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function normalize_post_card( WP_Post $post ): array {
		$thumbnail_url = get_the_post_thumbnail_url( $post, 'medium_large' );
		if ( ! is_string( $thumbnail_url ) ) {
			$thumbnail_url = '';
		}

		return array(
			'id' => (int) $post->ID,
			'title' => get_the_title( $post ),
			'status' => (string) $post->post_status,
			'status_label' => 'publish' === $post->post_status ? __( 'Publicado', 'almaden-bookster' ) : __( 'Borrador', 'almaden-bookster' ),
			'date' => get_the_date( get_option( 'date_format' ), $post ),
			'permalink' => get_permalink( $post ),
			'thumbnail_url' => $thumbnail_url,
			'excerpt' => trim( wp_strip_all_tags( (string) $post->post_excerpt ) ),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function normalize_post_detail( WP_Post $post ): array {
		$thumbnail_id = get_post_thumbnail_id( $post );
		$thumbnail_url = $thumbnail_id > 0 ? wp_get_attachment_image_url( $thumbnail_id, 'large' ) : get_the_post_thumbnail_url( $post, 'large' );
		if ( ! is_string( $thumbnail_url ) ) {
			$thumbnail_url = '';
		}

		return array(
			'id' => (int) $post->ID,
			'title' => (string) $post->post_title,
			'content' => (string) $post->post_content,
			'excerpt' => (string) $post->post_excerpt,
			'status' => (string) $post->post_status,
			'thumbnail_id' => (int) $thumbnail_id,
			'thumbnail_url' => $thumbnail_url,
			'permalink' => get_permalink( $post ),
		);
	}
}
