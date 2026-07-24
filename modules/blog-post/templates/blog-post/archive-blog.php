<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'almaden_blog_archive_lead' ) ) {
	function almaden_blog_archive_lead( WP_Post $post, int $words = 26 ): string {
		$excerpt = trim( (string) $post->post_excerpt );
		if ( '' !== $excerpt ) {
			return wp_trim_words( $excerpt, $words, '...' );
		}

		return wp_trim_words( wp_strip_all_tags( (string) $post->post_content ), $words, '...' );
	}
}

$posts = get_posts(
	array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => 24,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'no_found_rows'  => true,
	)
);

get_header();
?>

<main class="almaden-blog-archive">
	<div class="almaden-blog-archive-hero">
		<p class="text-xs uppercase tracking-[0.26em] text-slate-400"><?php esc_html_e( 'Blog', 'almaden-bookster' ); ?></p>
		<h1 class="font-[Newsreader] text-5xl font-semibold tracking-tight text-slate-950 sm:text-7xl"><?php esc_html_e( 'Ideas, notas y artículos', 'almaden-bookster' ); ?></h1>
		<p class="max-w-3xl text-lg leading-8 text-slate-600"><?php esc_html_e( 'Un archivo limpio para leer lo último publicado.', 'almaden-bookster' ); ?></p>
	</div>

	<div class="almaden-blog-archive-grid">
		<?php foreach ( $posts as $post ) : ?>
			<?php
			if ( ! $post instanceof WP_Post ) {
				continue;
			}
			$thumb = get_the_post_thumbnail_url( $post, 'large' );
			?>
			<a href="<?php echo esc_url( get_permalink( $post ) ); ?>" class="almaden-blog-archive-card">
				<?php if ( is_string( $thumb ) && '' !== $thumb ) : ?>
					<img src="<?php echo esc_url( $thumb ); ?>" alt="">
				<?php endif; ?>
				<h3><?php echo esc_html( get_the_title( $post ) ); ?></h3>
				<p><?php echo esc_html( almaden_blog_archive_lead( $post, 26 ) ); ?></p>
			</a>
		<?php endforeach; ?>
	</div>
</main>

<?php
get_footer();

