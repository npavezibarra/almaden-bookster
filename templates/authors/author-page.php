<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$author = isset( $author ) && is_object( $author ) ? $author : null;

$is_single_author     = is_object( $author ) && ! empty( $author );
$author_name          = $is_single_author ? $author->display_name : __( 'Autor', 'almaden-bookster' );
$author_photo_id      = $is_single_author ? almaden_bookster_get_author_profile_photo_id( $author->ID ) : 0;
$author_backcover_id  = $is_single_author ? almaden_bookster_get_author_backcover_id( $author->ID ) : 0;
$author_hero_bg       = $is_single_author && function_exists( 'almaden_bookster_get_author_hero_background_settings' ) ? almaden_bookster_get_author_hero_background_settings( $author->ID ) : ( function_exists( 'almaden_bookster_get_author_hero_background_defaults' ) ? almaden_bookster_get_author_hero_background_defaults() : array() );
$author_bio           = $is_single_author ? trim( (string) get_user_meta( $author->ID, 'description', true ) ) : '';
$author_socials       = $is_single_author ? almaden_bookster_get_author_social_links( $author->ID ) : almaden_bookster_get_author_social_link_defaults();
$author_books         = array();
$author_photo_url     = $author_photo_id > 0 ? wp_get_attachment_image_url( $author_photo_id, 'large' ) : '';
$author_backcover_url = $author_backcover_id > 0 ? wp_get_attachment_image_url( $author_backcover_id, 'full' ) : '';
$author_hero_style    = $is_single_author && function_exists( 'almaden_bookster_get_author_hero_background_style' ) ? almaden_bookster_get_author_hero_background_style( $author->ID ) : '';
$can_edit_author      = $is_single_author && function_exists( 'almaden_bookster_can_edit_author_profile' ) ? almaden_bookster_can_edit_author_profile( $author->ID ) : false;

$author_posts_dummy = array(
	array(
		'title'   => 'Cómo escribir una apertura memorable',
		'excerpt' => 'Una nota breve sobre los primeros párrafos que atrapan y sostienen al lector.',
		'meta'    => 'Blog post · 4 min',
		'url'     => '#',
	),
	array(
		'title'   => 'Lo que hace que un autor funcione en una editorial',
		'excerpt' => 'Dummy data para simular una pieza editorial vinculada al perfil del autor.',
		'meta'    => 'Blog post · 7 min',
		'url'     => '#',
	),
);

$author_courses_dummy = array(
	array(
		'title'   => 'Curso: Escritura de no ficción',
		'excerpt' => 'Contenido de ejemplo para el módulo Learni.',
		'meta'    => 'Curso · Learni',
		'url'     => '#',
	),
	array(
		'title'   => 'Curso: Narrativa y estructura',
		'excerpt' => 'Una segunda tarjeta dummy para cursos publicados.',
		'meta'    => 'Curso · Learni',
		'url'     => '#',
	),
);

if ( $is_single_author ) {
	$all_books = get_posts(
		array(
			'post_type'      => 'almaden-books',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);

	foreach ( $all_books as $book ) {
		$author_ids = function_exists( 'almaden_bookster_get_book_author_ids' ) ? almaden_bookster_get_book_author_ids( $book->ID ) : array();
		if ( in_array( $author->ID, $author_ids, true ) ) {
			$author_books[] = $book;
		}
	}
}
?>

<main id="almaden-author-page" class="almaden-app-content-shell">
	<?php if ( $is_single_author ) : ?>
	<section
		class="almaden-author-hero"
		id="almaden-author-hero"
		data-author-id="<?php echo esc_attr( absint( $author->ID ) ); ?>"
		data-hero-background-type="<?php echo esc_attr( isset( $author_hero_bg['type'] ) ? $author_hero_bg['type'] : 'color' ); ?>"
			data-hero-background-image-id="<?php echo esc_attr( isset( $author_hero_bg['image_id'] ) ? absint( $author_hero_bg['image_id'] ) : 0 ); ?>"
			data-hero-background-image-url="<?php echo esc_attr( isset( $author_hero_bg['image_id'] ) && absint( $author_hero_bg['image_id'] ) > 0 ? ( wp_get_attachment_image_url( absint( $author_hero_bg['image_id'] ), 'full' ) ?: '' ) : '' ); ?>"
			data-hero-background-color="<?php echo esc_attr( isset( $author_hero_bg['color'] ) ? $author_hero_bg['color'] : '#ebff43' ); ?>"
			data-hero-gradient-from="<?php echo esc_attr( isset( $author_hero_bg['gradient_from'] ) ? $author_hero_bg['gradient_from'] : '#ebff43' ); ?>"
			data-hero-gradient-to="<?php echo esc_attr( isset( $author_hero_bg['gradient_to'] ) ? $author_hero_bg['gradient_to'] : '#f5f5ef' ); ?>"
			data-hero-gradient-angle="<?php echo esc_attr( isset( $author_hero_bg['gradient_angle'] ) ? absint( $author_hero_bg['gradient_angle'] ) : 90 ); ?>"
			data-hero-overlay-color="<?php echo esc_attr( isset( $author_hero_bg['overlay_color'] ) ? $author_hero_bg['overlay_color'] : '#000000' ); ?>"
			data-hero-overlay-opacity="<?php echo esc_attr( isset( $author_hero_bg['overlay_opacity'] ) ? $author_hero_bg['overlay_opacity'] : 0 ); ?>"
			<?php echo $author_hero_style ? 'style="' . esc_attr( $author_hero_style ) . '"' : ''; ?>
		>
			<div class="almaden-author-hero-inner" id="almaden-author-hero-inner" data-author-id="<?php echo esc_attr( absint( $author->ID ) ); ?>">
				<?php if ( $can_edit_author ) : ?>
					<button type="button" class="almaden-author-hero-edit-btn" id="almaden-author-hero-edit-btn" aria-label="<?php esc_attr_e( 'Editar fondo del hero', 'almaden-bookster' ); ?>">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
							<path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487a2.1 2.1 0 0 1 2.97 2.97L8.5 18.79 4 20l1.21-4.5L16.862 4.487Z" />
						</svg>
					</button>
				<?php endif; ?>
				<div class="almaden-author-grid" id="almaden-author-hero-grid" data-author-id="<?php echo esc_attr( absint( $author->ID ) ); ?>">
					<div class="almaden-photo-card" id="almaden-author-photo-card" data-author-id="<?php echo esc_attr( absint( $author->ID ) ); ?>" <?php echo $author_photo_url ? 'data-current-photo-url="' . esc_url( $author_photo_url ) . '"' : ''; ?>>
						<?php if ( $can_edit_author ) : ?>
							<button type="button" class="almaden-author-photo-action" id="almaden-author-photo-edit-btn" aria-label="<?php esc_attr_e( 'Subir o cambiar foto de perfil', 'almaden-bookster' ); ?>">
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
									<path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V7.5" />
									<path stroke-linecap="round" stroke-linejoin="round" d="M8.25 11.25 12 7.5l3.75 3.75" />
									<path stroke-linecap="round" stroke-linejoin="round" d="M5.25 16.5v1.5a1.5 1.5 0 0 0 1.5 1.5h10.5a1.5 1.5 0 0 0 1.5-1.5v-1.5" />
								</svg>
							</button>
						<?php endif; ?>
						<?php if ( $author_photo_id > 0 ) : ?>
							<?php echo wp_get_attachment_image( $author_photo_id, 'large', false, array( 'class' => 'almaden-author-photo', 'id' => 'almaden-author-photo-image' ) ); ?>
						<?php else : ?>
							<div class="almaden-author-initial" id="almaden-author-photo-initial"><?php echo esc_html( function_exists( 'mb_substr' ) ? mb_substr( strtoupper( $author_name ), 0, 1 ) : substr( strtoupper( $author_name ), 0, 1 ) ); ?></div>
						<?php endif; ?>
					</div>

						<div class="almaden-author-panel" id="almaden-author-panel" data-author-id="<?php echo esc_attr( absint( $author->ID ) ); ?>">
							<h1 class="almaden-author-title" id="almaden-author-name" data-author-id="<?php echo esc_attr( absint( $author->ID ) ); ?>"><?php echo esc_html( $author_name ); ?></h1>

							<div class="almaden-author-tabs" id="almaden-author-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Contenido del autor', 'almaden-bookster' ); ?>" data-author-id="<?php echo esc_attr( absint( $author->ID ) ); ?>">
								<button type="button" class="almaden-author-tab" id="almaden-author-tab-posts" role="tab" aria-selected="true" aria-controls="almaden-author-panel-posts" data-tab="posts" data-author-id="<?php echo esc_attr( absint( $author->ID ) ); ?>">POSTS</button>
								<button type="button" class="almaden-author-tab" id="almaden-author-tab-books" role="tab" aria-selected="false" aria-controls="almaden-author-panel-books" data-tab="books" data-author-id="<?php echo esc_attr( absint( $author->ID ) ); ?>">BOOKS</button>
								<button type="button" class="almaden-author-tab" id="almaden-author-tab-courses" role="tab" aria-selected="false" aria-controls="almaden-author-panel-courses" data-tab="courses" data-author-id="<?php echo esc_attr( absint( $author->ID ) ); ?>">CURSOS</button>
								<button type="button" class="almaden-author-tab" id="almaden-author-tab-bio" role="tab" aria-selected="false" aria-controls="almaden-author-panel-bio" data-tab="bio" data-author-id="<?php echo esc_attr( absint( $author->ID ) ); ?>">BIO</button>
							</div>

							<div class="almaden-author-tabpanel" id="almaden-author-panel-posts" role="tabpanel" aria-labelledby="almaden-author-tab-posts" data-author-id="<?php echo esc_attr( absint( $author->ID ) ); ?>">
								<div class="almaden-author-cards-grid" id="almaden-author-posts-grid" data-author-id="<?php echo esc_attr( absint( $author->ID ) ); ?>">
									<?php foreach ( $author_posts_dummy as $index => $post_item ) : ?>
										<a href="<?php echo esc_url( $post_item['url'] ); ?>" class="almaden-author-content-card" id="<?php echo esc_attr( 'almaden-author-post-' . ( $index + 1 ) ); ?>" data-author-id="<?php echo esc_attr( absint( $author->ID ) ); ?>">
											<p class="almaden-author-content-card-meta" id="<?php echo esc_attr( 'almaden-author-post-meta-' . ( $index + 1 ) ); ?>"><?php echo esc_html( $post_item['meta'] ); ?></p>
											<p class="almaden-author-content-card-title" id="<?php echo esc_attr( 'almaden-author-post-title-' . ( $index + 1 ) ); ?>"><?php echo esc_html( $post_item['title'] ); ?></p>
											<p class="almaden-author-content-card-excerpt" id="<?php echo esc_attr( 'almaden-author-post-excerpt-' . ( $index + 1 ) ); ?>"><?php echo esc_html( $post_item['excerpt'] ); ?></p>
										</a>
									<?php endforeach; ?>
								</div>
							</div>

							<div class="almaden-author-tabpanel" id="almaden-author-panel-books" role="tabpanel" aria-labelledby="almaden-author-tab-books" hidden data-author-id="<?php echo esc_attr( absint( $author->ID ) ); ?>">
								<?php if ( ! empty( $author_books ) ) : ?>
									<div class="almaden-author-cards-grid" id="almaden-author-books-grid" data-author-id="<?php echo esc_attr( absint( $author->ID ) ); ?>">
										<?php foreach ( $author_books as $book ) : ?>
											<a href="<?php echo esc_url( get_permalink( $book->ID ) ); ?>" class="almaden-author-content-card" id="<?php echo esc_attr( 'almaden-author-book-' . absint( $book->ID ) ); ?>" data-author-id="<?php echo esc_attr( absint( $author->ID ) ); ?>">
												<p class="almaden-author-content-card-meta" id="<?php echo esc_attr( 'almaden-author-book-meta-' . absint( $book->ID ) ); ?>"><?php esc_html_e( 'Libro publicado', 'almaden-bookster' ); ?></p>
												<p class="almaden-author-content-card-title" id="<?php echo esc_attr( 'almaden-author-book-title-' . absint( $book->ID ) ); ?>"><?php echo esc_html( get_the_title( $book->ID ) ); ?></p>
												<p class="almaden-author-content-card-excerpt" id="<?php echo esc_attr( 'almaden-author-book-excerpt-' . absint( $book->ID ) ); ?>"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $book->post_content ), 18 ) ); ?></p>
											</a>
										<?php endforeach; ?>
									</div>
								<?php else : ?>
									<div class="almaden-empty" id="almaden-author-books-empty"><?php esc_html_e( 'Todavía no hay libros vinculados a este autor.', 'almaden-bookster' ); ?></div>
								<?php endif; ?>
							</div>

							<div class="almaden-author-tabpanel" id="almaden-author-panel-courses" role="tabpanel" aria-labelledby="almaden-author-tab-courses" hidden data-author-id="<?php echo esc_attr( absint( $author->ID ) ); ?>">
								<div class="almaden-author-cards-grid" id="almaden-author-courses-grid" data-author-id="<?php echo esc_attr( absint( $author->ID ) ); ?>">
									<?php foreach ( $author_courses_dummy as $index => $course_item ) : ?>
										<a href="<?php echo esc_url( $course_item['url'] ); ?>" class="almaden-author-content-card" id="<?php echo esc_attr( 'almaden-author-course-' . ( $index + 1 ) ); ?>" data-author-id="<?php echo esc_attr( absint( $author->ID ) ); ?>">
											<p class="almaden-author-content-card-meta" id="<?php echo esc_attr( 'almaden-author-course-meta-' . ( $index + 1 ) ); ?>"><?php echo esc_html( $course_item['meta'] ); ?></p>
											<p class="almaden-author-content-card-title" id="<?php echo esc_attr( 'almaden-author-course-title-' . ( $index + 1 ) ); ?>"><?php echo esc_html( $course_item['title'] ); ?></p>
											<p class="almaden-author-content-card-excerpt" id="<?php echo esc_attr( 'almaden-author-course-excerpt-' . ( $index + 1 ) ); ?>"><?php echo esc_html( $course_item['excerpt'] ); ?></p>
										</a>
									<?php endforeach; ?>
								</div>
							</div>

							<div class="almaden-author-tabpanel" id="almaden-author-panel-bio" role="tabpanel" aria-labelledby="almaden-author-tab-bio" hidden data-author-id="<?php echo esc_attr( absint( $author->ID ) ); ?>">
								<div class="almaden-author-profile" id="almaden-author-profile" data-author-id="<?php echo esc_attr( absint( $author->ID ) ); ?>">
									<span class="almaden-author-chip" id="almaden-author-chip" data-author-id="<?php echo esc_attr( absint( $author->ID ) ); ?>"><?php esc_html_e( 'Autor', 'almaden-bookster' ); ?></span>
									<p class="almaden-author-email" id="almaden-author-email" data-author-id="<?php echo esc_attr( absint( $author->ID ) ); ?>"><?php echo esc_html( $author->user_email ); ?></p>

									<div class="almaden-author-bio" id="almaden-author-bio" data-author-id="<?php echo esc_attr( absint( $author->ID ) ); ?>">
									<?php if ( '' !== $author_bio ) : ?>
										<?php echo wp_kses_post( wpautop( $author_bio ) ); ?>
									<?php else : ?>
										<p>Este autor todavía no publicó una biografía.</p>
									<?php endif; ?>
								</div>

								<?php if ( ! empty( array_filter( $author_socials ) ) ) : ?>
									<div class="almaden-links" id="almaden-author-social-links" data-author-id="<?php echo esc_attr( absint( $author->ID ) ); ?>">
										<?php foreach ( $author_socials as $network => $url ) : ?>
											<?php if ( '' === trim( (string) $url ) ) { continue; } ?>
											<a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer" class="almaden-link" id="<?php echo esc_attr( 'almaden-author-social-link-' . sanitize_title( $network ) ); ?>" data-author-id="<?php echo esc_attr( absint( $author->ID ) ); ?>"><?php echo esc_html( ucfirst( $network ) ); ?></a>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>
							</div>
						</div>
					</div>
				</div>
			</div>
		</section>
	<?php else : ?>
		<section class="almaden-not-found" id="almaden-author-not-found">
			<div class="almaden-not-found-card" id="almaden-author-not-found-card">
				<p class="almaden-section-label" id="almaden-author-not-found-label"><?php esc_html_e( 'Autor', 'almaden-bookster' ); ?></p>
				<h1 style="margin:0; font-size: 2rem; letter-spacing:-0.04em; color:#0f172a;" id="almaden-author-not-found-title"><?php esc_html_e( 'Autor no encontrado', 'almaden-bookster' ); ?></h1>
				<p style="margin-top:0.85rem; color:#475569; line-height:1.7;" id="almaden-author-not-found-text"><?php esc_html_e( 'Puede que el enlace haya cambiado o que todavía no exista ese perfil.', 'almaden-bookster' ); ?></p>
			</div>
		</section>
	<?php endif; ?>
</main>

<?php if ( $can_edit_author ) : ?>
	<div class="almaden-photo-modal" id="almaden-author-photo-modal" hidden aria-labelledby="almaden-author-photo-modal-title" role="dialog" aria-modal="true">
		<div class="almaden-photo-modal-backdrop" id="almaden-author-photo-modal-backdrop" aria-hidden="true"></div>
		<div class="almaden-photo-modal-panel" id="almaden-author-photo-modal-panel">
			<div class="flex items-start justify-between gap-4">
				<div>
					<p class="almaden-section-label"><?php esc_html_e( 'Foto de perfil', 'almaden-bookster' ); ?></p>
					<h2 id="almaden-author-photo-modal-title" style="margin:0; font-size:1.6rem; letter-spacing:-0.04em; color:#0f172a;"><?php esc_html_e( 'Actualizar foto', 'almaden-bookster' ); ?></h2>
					<p style="margin-top:0.35rem; color:#64748b; line-height:1.6;"><?php esc_html_e( 'Arrastra una imagen, elige archivo y ajusta el recorte cuadrado antes de guardar.', 'almaden-bookster' ); ?></p>
				</div>
				<button type="button" id="almaden-author-photo-modal-close" class="almaden-photo-modal-btn" aria-label="<?php esc_attr_e( 'Cerrar', 'almaden-bookster' ); ?>">×</button>
			</div>

			<form id="almaden-author-photo-form" action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" data-upload-endpoint="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" method="post" enctype="multipart/form-data">
				<input type="hidden" name="action" value="almaden_update_author_photo">
				<input type="hidden" name="author_id" value="<?php echo esc_attr( absint( $author->ID ) ); ?>">
				<?php wp_nonce_field( 'almaden_update_author_photo', 'almaden_author_photo_nonce' ); ?>
				<input type="file" id="almaden-author-photo-file" name="author_profile_photo_file" accept="image/*" class="hidden">

				<div class="almaden-photo-modal-grid">
					<div class="almaden-photo-crop-stage">
						<div id="almaden-author-photo-dropzone" class="almaden-photo-dropzone">
							<div id="almaden-author-photo-dropzone-empty">
								<p style="margin:0; font-size:1rem; font-weight:700; color:#0f172a;"><?php esc_html_e( 'Suelta la foto aquí', 'almaden-bookster' ); ?></p>
								<p style="margin:0.4rem 0 0; color:#64748b;"><?php esc_html_e( 'o haz clic para seleccionar un archivo', 'almaden-bookster' ); ?></p>
							</div>
							<div id="almaden-author-photo-dropzone-preview" class="hidden w-full">
								<div class="almaden-photo-crop-canvas-wrap">
									<canvas id="almaden-author-photo-crop-canvas" class="almaden-photo-crop-canvas"></canvas>
								</div>
								<p style="margin:0.75rem 0 0; color:#64748b;"><?php esc_html_e( 'Arrastra sobre la imagen para ajustar el cuadrado.', 'almaden-bookster' ); ?></p>
							</div>
						</div>
					</div>

					<div class="almaden-photo-controls">
						<div>
							<p class="almaden-photo-control-label"><?php esc_html_e( 'Archivo', 'almaden-bookster' ); ?></p>
							<div class="almaden-photo-control-row">
								<button type="button" id="almaden-author-photo-choose-btn" class="almaden-photo-modal-btn"><?php esc_html_e( 'Elegir archivo', 'almaden-bookster' ); ?></button>
								<button type="button" id="almaden-author-photo-clear-btn" class="almaden-photo-modal-btn"><?php esc_html_e( 'Limpiar', 'almaden-bookster' ); ?></button>
							</div>
						</div>

						<div>
							<p class="almaden-photo-control-label"><?php esc_html_e( 'Zoom', 'almaden-bookster' ); ?></p>
							<input type="range" id="almaden-author-photo-zoom" class="almaden-photo-range" min="1" max="3" step="0.01" value="1">
						</div>

						<div>
							<p class="almaden-photo-control-label"><?php esc_html_e( 'Acciones', 'almaden-bookster' ); ?></p>
							<div class="almaden-photo-control-row">
								<button type="submit" class="almaden-photo-modal-btn almaden-photo-modal-btn--primary"><?php esc_html_e( 'Guardar', 'almaden-bookster' ); ?></button>
								<button type="button" id="almaden-author-photo-cancel-btn" class="almaden-photo-modal-btn"><?php esc_html_e( 'Cancelar', 'almaden-bookster' ); ?></button>
							</div>
						</div>
					</div>
				</div>
			</form>
		</div>
	</div>

	<div class="almaden-hero-modal" id="almaden-author-hero-modal" hidden aria-labelledby="almaden-author-hero-modal-title" role="dialog" aria-modal="true">
		<div class="almaden-hero-modal-backdrop" id="almaden-author-hero-modal-backdrop" aria-hidden="true"></div>
		<div class="almaden-hero-modal-panel" id="almaden-author-hero-modal-panel">
			<div class="flex items-start justify-between gap-4">
				<div>
					<p class="almaden-section-label"><?php esc_html_e( 'Hero', 'almaden-bookster' ); ?></p>
					<h2 id="almaden-author-hero-modal-title" style="margin:0; font-size:1.6rem; letter-spacing:-0.04em; color:#0f172a;"><?php esc_html_e( 'Fondo del hero', 'almaden-bookster' ); ?></h2>
					<p style="margin-top:0.35rem; color:#64748b; line-height:1.6;"><?php esc_html_e( 'Imagen, color o degradado.', 'almaden-bookster' ); ?></p>
				</div>
				<button type="button" id="almaden-author-hero-modal-close" class="almaden-hero-modal-btn" aria-label="<?php esc_attr_e( 'Cerrar', 'almaden-bookster' ); ?>">×</button>
			</div>

			<form id="almaden-author-hero-form" action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" data-upload-endpoint="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" method="post" enctype="multipart/form-data">
				<input type="hidden" name="action" value="almaden_update_author_hero_background">
				<input type="hidden" name="author_id" value="<?php echo esc_attr( absint( $author->ID ) ); ?>">
				<?php wp_nonce_field( 'almaden_update_author_hero_background', 'almaden_author_hero_nonce' ); ?>
				<input type="hidden" id="almaden-author-hero-background-mode" name="author_hero_background_mode" value="<?php echo esc_attr( isset( $author_hero_bg['type'] ) ? $author_hero_bg['type'] : 'color' ); ?>">
				<input type="hidden" id="almaden-author-hero-background-image-id" name="author_hero_background_image_id" value="<?php echo esc_attr( isset( $author_hero_bg['image_id'] ) ? absint( $author_hero_bg['image_id'] ) : 0 ); ?>">
				<input type="hidden" id="almaden-author-hero-overlay-color" name="author_hero_background_overlay_color" value="<?php echo esc_attr( isset( $author_hero_bg['overlay_color'] ) ? $author_hero_bg['overlay_color'] : '#000000' ); ?>">
				<input type="hidden" id="almaden-author-hero-overlay-opacity" name="author_hero_background_overlay_opacity" value="<?php echo esc_attr( isset( $author_hero_bg['overlay_opacity'] ) ? $author_hero_bg['overlay_opacity'] : 0 ); ?>">

				<div class="almaden-hero-modal-grid">
					<div class="almaden-hero-upload-stage">
						<div id="almaden-author-hero-dropzone" class="almaden-photo-dropzone almaden-hero-dropzone">
							<div id="almaden-author-hero-dropzone-empty">
								<p style="margin:0; font-size:1rem; font-weight:700; color:#0f172a;"><?php esc_html_e( 'Carga una imagen', 'almaden-bookster' ); ?></p>
								<p style="margin:0.35rem 0 0; color:#64748b;"><?php esc_html_e( 'o haz clic para elegir un archivo.', 'almaden-bookster' ); ?></p>
							</div>
						</div>
						<p class="almaden-hero-preview-note"><?php esc_html_e( 'Vista previa del hero', 'almaden-bookster' ); ?></p>
						<div class="almaden-photo-control-row almaden-hero-upload-actions">
							<button type="button" id="almaden-author-hero-choose-btn" class="almaden-hero-modal-btn"><?php esc_html_e( 'Elegir archivo', 'almaden-bookster' ); ?></button>
							<button type="button" id="almaden-author-hero-clear-btn" class="almaden-hero-modal-btn"><?php esc_html_e( 'Limpiar', 'almaden-bookster' ); ?></button>
						</div>
					</div>

					<div class="almaden-hero-controls">
						<div>
							<p class="almaden-photo-control-label"><?php esc_html_e( 'Tipo de fondo', 'almaden-bookster' ); ?></p>
							<div class="almaden-hero-mode-switch" role="tablist" aria-label="<?php esc_attr_e( 'Tipo de fondo del hero', 'almaden-bookster' ); ?>">
								<button type="button" class="almaden-hero-mode-btn" data-hero-mode="image"><?php esc_html_e( 'Imagen', 'almaden-bookster' ); ?></button>
								<button type="button" class="almaden-hero-mode-btn" data-hero-mode="color"><?php esc_html_e( 'Color', 'almaden-bookster' ); ?></button>
								<button type="button" class="almaden-hero-mode-btn" data-hero-mode="gradient"><?php esc_html_e( 'Gradient', 'almaden-bookster' ); ?></button>
							</div>
						</div>

						<div class="almaden-hero-mode-panel" data-hero-panel="image">
							<p class="almaden-photo-control-label"><?php esc_html_e( 'Imagen', 'almaden-bookster' ); ?></p>
							<input type="file" id="almaden-author-hero-file" name="author_hero_background_file" accept="image/*" class="hidden">
							<div class="almaden-hero-overlay-grid">
								<label class="almaden-hero-field almaden-hero-field--compact">
									<span><?php esc_html_e( 'Overlay', 'almaden-bookster' ); ?></span>
									<input type="color" id="almaden-author-hero-overlay-color-input" class="almaden-hero-color-input almaden-hero-color-input--small" value="<?php echo esc_attr( isset( $author_hero_bg['overlay_color'] ) ? $author_hero_bg['overlay_color'] : '#000000' ); ?>">
								</label>
								<label class="almaden-hero-field">
									<span><?php esc_html_e( 'Opacidad', 'almaden-bookster' ); ?></span>
									<input type="range" id="almaden-author-hero-overlay-opacity-input" class="almaden-photo-range" min="0" max="1" step="0.05" value="<?php echo esc_attr( isset( $author_hero_bg['overlay_opacity'] ) ? $author_hero_bg['overlay_opacity'] : 0 ); ?>">
								</label>
							</div>
						</div>

						<div class="almaden-hero-mode-panel hidden" data-hero-panel="color">
							<p class="almaden-photo-control-label"><?php esc_html_e( 'Color sólido', 'almaden-bookster' ); ?></p>
							<input type="color" id="almaden-author-hero-color" name="author_hero_background_color" class="almaden-hero-color-input" value="<?php echo esc_attr( isset( $author_hero_bg['color'] ) ? $author_hero_bg['color'] : '#ebff43' ); ?>">
						</div>

						<div class="almaden-hero-mode-panel hidden" data-hero-panel="gradient">
							<p class="almaden-photo-control-label"><?php esc_html_e( 'Degradado', 'almaden-bookster' ); ?></p>
							<div class="almaden-hero-gradient-grid">
								<label class="almaden-hero-field">
									<span><?php esc_html_e( 'Color inicial', 'almaden-bookster' ); ?></span>
									<input type="color" id="almaden-author-hero-gradient-from" name="author_hero_gradient_from" class="almaden-hero-color-input" value="<?php echo esc_attr( isset( $author_hero_bg['gradient_from'] ) ? $author_hero_bg['gradient_from'] : '#ebff43' ); ?>">
								</label>
								<label class="almaden-hero-field">
									<span><?php esc_html_e( 'Color final', 'almaden-bookster' ); ?></span>
									<input type="color" id="almaden-author-hero-gradient-to" name="author_hero_gradient_to" class="almaden-hero-color-input" value="<?php echo esc_attr( isset( $author_hero_bg['gradient_to'] ) ? $author_hero_bg['gradient_to'] : '#f5f5ef' ); ?>">
								</label>
								<label class="almaden-hero-field">
									<span><?php esc_html_e( 'Ángulo', 'almaden-bookster' ); ?></span>
									<input type="range" id="almaden-author-hero-gradient-angle" name="author_hero_gradient_angle" class="almaden-photo-range" min="0" max="360" step="1" value="<?php echo esc_attr( isset( $author_hero_bg['gradient_angle'] ) ? absint( $author_hero_bg['gradient_angle'] ) : 90 ); ?>">
								</label>
							</div>
						</div>

						<div>
							<p class="almaden-photo-control-label"><?php esc_html_e( 'Acciones', 'almaden-bookster' ); ?></p>
							<div class="almaden-photo-control-row">
								<button type="submit" class="almaden-hero-modal-btn almaden-hero-modal-btn--primary"><?php esc_html_e( 'Guardar', 'almaden-bookster' ); ?></button>
								<button type="button" id="almaden-author-hero-cancel-btn" class="almaden-hero-modal-btn"><?php esc_html_e( 'Cancelar', 'almaden-bookster' ); ?></button>
							</div>
						</div>
					</div>
				</div>
			</form>
		</div>
	</div>
<?php endif; ?>
