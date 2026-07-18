<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$is_single_publisher = is_array( $publisher ) && ! empty( $publisher );
$page_title = $is_single_publisher ? $publisher['name'] : __( 'Editoriales', 'almaden-bookster' );
$page_subtitle = $is_single_publisher
	? __( 'Perfil público de la editorial, sus libros y su equipo.', 'almaden-bookster' )
	: __( 'Directorio público de editoriales alojadas en Almaden Bookster.', 'almaden-bookster' );
$all_publishers = $is_single_publisher ? array() : almaden_bookster_get_publishers();
$publisher_books = $is_single_publisher ? almaden_bookster_get_publisher_books( $publisher['id'] ) : array();
$publisher_members = $is_single_publisher ? almaden_bookster_get_publisher_members( $publisher['id'] ) : array();
$can_manage_publisher = $is_single_publisher && function_exists( 'almaden_bookster_user_can_manage_publisher' ) ? almaden_bookster_user_can_manage_publisher( $publisher['id'] ) : false;
$publisher_settings_url = $is_single_publisher && function_exists( 'almaden_bookster_get_publisher_settings_url' ) ? almaden_bookster_get_publisher_settings_url( $publisher['slug'] ) : '';

$authors = array();
$other_members = array();
if ( $is_single_publisher && ! empty( $publisher_members ) ) {
	foreach ( $publisher_members as $member ) {
		if ( in_array( sanitize_key( $member['role'] ), array( 'author', 'owner', 'editor' ), true ) ) {
			$authors[] = $member;
		} else {
			$other_members[] = $member;
		}
	}
}

?>
<style id="almaden-publisher-page-style">
	#almaden-publisher-page {
		max-width: var(--almaden-app-max-width, 80rem);
		margin: 0 auto;
		padding: 0 1rem 4rem;
		width: 100%;
		box-sizing: border-box;
	}
	#almaden-publisher-page .almaden-shell {
		display: grid;
		gap: 1.5rem;
	}
	#almaden-publisher-page .almaden-hero {
		background: linear-gradient(135deg, #0f172a 0%, #1e293b 55%, #334155 100%);
		color: #f8fafc;
		border-radius: 1.75rem;
		padding: 2rem;
		box-shadow: 0 24px 60px rgba(15, 23, 42, 0.16);
	}
	#almaden-publisher-page .almaden-hero h1 {
		margin: 0;
		font-size: clamp(2rem, 4vw, 3.6rem);
		line-height: 1.05;
		letter-spacing: -0.03em;
	}
	#almaden-publisher-page .almaden-hero p {
		margin: 0.75rem 0 0;
		max-width: 60ch;
		font-size: 1.05rem;
		color: rgba(248, 250, 252, 0.82);
	}
	#almaden-publisher-page .almaden-pill {
		display: inline-flex;
		align-items: center;
		gap: 0.5rem;
		padding: 0.4rem 0.75rem;
		border-radius: 999px;
		background: rgba(255, 255, 255, 0.12);
		font-size: 0.82rem;
		font-weight: 700;
		letter-spacing: 0.02em;
		text-transform: uppercase;
	}
	#almaden-publisher-page .almaden-grid {
		display: grid;
		grid-template-columns: minmax(0, 1.4fr) minmax(280px, 0.8fr);
		gap: 1.5rem;
		align-items: start;
	}
	#almaden-publisher-page .almaden-card {
		background: #ffffff;
		border: 1px solid rgba(15, 23, 42, 0.08);
		border-radius: 1.5rem;
		padding: 1.5rem;
		box-shadow: 0 18px 40px rgba(15, 23, 42, 0.05);
	}
	#almaden-publisher-page .almaden-card h2,
	#almaden-publisher-page .almaden-card h3 {
		margin: 0 0 1rem;
		color: #0f172a;
		letter-spacing: -0.02em;
	}
	#almaden-publisher-page .almaden-meta {
		display: grid;
		grid-template-columns: repeat(2, minmax(0, 1fr));
		gap: 0.85rem;
		margin-top: 1.25rem;
	}
	#almaden-publisher-page .almaden-meta-item {
		padding: 0.9rem 1rem;
		border-radius: 1rem;
		background: #f8fafc;
		border: 1px solid #e2e8f0;
	}
	#almaden-publisher-page .almaden-meta-item span {
		display: block;
		font-size: 0.75rem;
		font-weight: 700;
		text-transform: uppercase;
		letter-spacing: 0.08em;
		color: #64748b;
		margin-bottom: 0.35rem;
	}
	#almaden-publisher-page .almaden-meta-item strong {
		font-size: 0.95rem;
		color: #0f172a;
	}
	#almaden-publisher-page .almaden-list {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
		gap: 1rem;
	}
	#almaden-publisher-page .almaden-book,
	#almaden-publisher-page .almaden-person {
		display: block;
		padding: 1rem;
		border-radius: 1rem;
		background: #f8fafc;
		border: 1px solid #e2e8f0;
		text-decoration: none;
		color: inherit;
	}
	#almaden-publisher-page .almaden-book:hover,
	#almaden-publisher-page .almaden-person:hover {
		border-color: #cbd5e1;
		box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
	}
	#almaden-publisher-page .almaden-book-title,
	#almaden-publisher-page .almaden-person-name {
		margin: 0 0 0.35rem;
		font-weight: 700;
		font-size: 1.02rem;
		color: #0f172a;
	}
	#almaden-publisher-page .almaden-book-excerpt,
	#almaden-publisher-page .almaden-person-role,
	#almaden-publisher-page .almaden-muted {
		margin: 0;
		font-size: 0.92rem;
		color: #475569;
		line-height: 1.5;
	}
	#almaden-publisher-page .almaden-cover {
		width: 100%;
		aspect-ratio: 4 / 5;
		border-radius: 0.9rem;
		background: linear-gradient(135deg, #e2e8f0 0%, #f8fafc 100%);
		overflow: hidden;
		margin-bottom: 0.85rem;
		display: flex;
		align-items: center;
		justify-content: center;
	}
	#almaden-publisher-page .almaden-cover img {
		width: 100%;
		height: 100%;
		object-fit: cover;
		display: block;
	}
	#almaden-publisher-page .almaden-empty,
	#almaden-publisher-page .almaden-not-found {
		padding: 1.5rem;
		border-radius: 1rem;
		background: #fff7ed;
		border: 1px solid #fed7aa;
		color: #9a3412;
	}
	@media (max-width: 900px) {
		#almaden-publisher-page .almaden-grid {
			grid-template-columns: 1fr;
		}
	}
</style>

<main id="almaden-publisher-page" class="almaden-app-content-shell">
	<div class="almaden-shell">
		<section class="almaden-hero">
			<span class="almaden-pill"><?php echo esc_html( $is_single_publisher ? __( 'Editorial', 'almaden-bookster' ) : __( 'Directorio', 'almaden-bookster' ) ); ?></span>
			<h1><?php echo esc_html( $page_title ); ?></h1>
			<p><?php echo esc_html( $page_subtitle ); ?></p>
			<?php if ( $can_manage_publisher && '' !== $publisher_settings_url ) : ?>
				<p style="margin-top: 1rem;">
					<a href="<?php echo esc_url( $publisher_settings_url ); ?>" style="display:inline-flex;align-items:center;gap:.5rem;padding:.7rem 1rem;border-radius:999px;background:rgba(255,255,255,.12);color:#fff;text-decoration:none;font-weight:700;">
						<?php esc_html_e( 'Abrir ajustes', 'almaden-bookster' ); ?>
					</a>
				</p>
			<?php endif; ?>
		</section>

		<?php if ( $is_single_publisher ) : ?>
			<div class="almaden-grid">
				<section class="almaden-card">
					<h2><?php echo esc_html( $publisher['name'] ); ?></h2>
					<?php if ( ! empty( $publisher['description'] ) ) : ?>
						<div class="almaden-muted"><?php echo wp_kses_post( wpautop( $publisher['description'] ) ); ?></div>
					<?php else : ?>
						<p class="almaden-muted"><?php esc_html_e( 'Esta editorial todavía no publicó una descripción.', 'almaden-bookster' ); ?></p>
					<?php endif; ?>

					<div class="almaden-meta">
						<div class="almaden-meta-item">
							<span><?php esc_html_e( 'Sitio', 'almaden-bookster' ); ?></span>
							<strong><?php echo ! empty( $publisher['website'] ) ? esc_html( $publisher['website'] ) : esc_html__( 'No informado', 'almaden-bookster' ); ?></strong>
						</div>
						<div class="almaden-meta-item">
							<span><?php esc_html_e( 'Correo', 'almaden-bookster' ); ?></span>
							<strong><?php echo ! empty( $publisher['email'] ) ? esc_html( $publisher['email'] ) : esc_html__( 'No informado', 'almaden-bookster' ); ?></strong>
						</div>
						<div class="almaden-meta-item">
							<span><?php esc_html_e( 'Teléfono', 'almaden-bookster' ); ?></span>
							<strong><?php echo ! empty( $publisher['phone'] ) ? esc_html( $publisher['phone'] ) : esc_html__( 'No informado', 'almaden-bookster' ); ?></strong>
						</div>
						<div class="almaden-meta-item">
							<span><?php esc_html_e( 'RUT', 'almaden-bookster' ); ?></span>
							<strong><?php echo ! empty( $publisher['rut'] ) ? esc_html( $publisher['rut'] ) : esc_html__( 'No informado', 'almaden-bookster' ); ?></strong>
						</div>
					</div>
				</section>

				<aside class="almaden-card">
					<h3><?php esc_html_e( 'Equipo', 'almaden-bookster' ); ?></h3>
					<?php if ( ! empty( $authors ) ) : ?>
						<div class="almaden-list">
							<?php foreach ( $authors as $member ) : ?>
								<div class="almaden-person">
									<p class="almaden-person-name"><?php echo esc_html( get_userdata( (int) $member['user_id'] ) ? get_userdata( (int) $member['user_id'] )->display_name : __( 'Usuario', 'almaden-bookster' ) ); ?></p>
									<p class="almaden-person-role"><?php echo esc_html( almaden_bookster_get_publisher_role_label( $member['role'] ) ); ?></p>
								</div>
							<?php endforeach; ?>
						</div>
					<?php else : ?>
						<p class="almaden-muted"><?php esc_html_e( 'Todavía no hay miembros asociados con este perfil.', 'almaden-bookster' ); ?></p>
					<?php endif; ?>
				</aside>
			</div>

			<section class="almaden-card">
				<h2><?php esc_html_e( 'Libros', 'almaden-bookster' ); ?></h2>
				<?php if ( ! empty( $publisher_books ) ) : ?>
					<div class="almaden-list">
						<?php foreach ( $publisher_books as $book ) : ?>
							<a class="almaden-book" href="<?php echo esc_url( get_permalink( $book ) ); ?>">
								<div class="almaden-cover">
									<?php if ( has_post_thumbnail( $book ) ) : ?>
										<?php echo get_the_post_thumbnail( $book, 'medium_large' ); ?>
									<?php else : ?>
										<span class="almaden-muted"><?php esc_html_e( 'Sin portada', 'almaden-bookster' ); ?></span>
									<?php endif; ?>
								</div>
								<p class="almaden-book-title"><?php echo esc_html( get_the_title( $book ) ); ?></p>
								<p class="almaden-book-excerpt"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( get_post_field( 'post_content', $book ) ), 24 ) ); ?></p>
							</a>
						<?php endforeach; ?>
					</div>
				<?php else : ?>
					<p class="almaden-empty"><?php esc_html_e( 'Esta editorial todavía no tiene libros publicados.', 'almaden-bookster' ); ?></p>
				<?php endif; ?>
			</section>
		<?php else : ?>
			<section class="almaden-card">
				<h2><?php esc_html_e( 'Editoriales activas', 'almaden-bookster' ); ?></h2>
				<?php if ( ! empty( $all_publishers ) ) : ?>
					<div class="almaden-list">
						<?php foreach ( $all_publishers as $item ) : ?>
							<a class="almaden-book" href="<?php echo esc_url( almaden_bookster_get_publisher_page_url( $item['slug'] ) ); ?>">
								<p class="almaden-book-title"><?php echo esc_html( $item['name'] ); ?></p>
								<p class="almaden-book-excerpt"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $item['description'] ), 22 ) ); ?></p>
							</a>
						<?php endforeach; ?>
					</div>
				<?php else : ?>
					<p class="almaden-empty"><?php esc_html_e( 'Aún no hay editoriales activas registradas.', 'almaden-bookster' ); ?></p>
				<?php endif; ?>
			</section>
		<?php endif; ?>
	</div>
</main>
