<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $post;
if ( ! ( $post instanceof WP_Post ) ) {
	exit;
}

if ( ! function_exists( 'almaden_blog_post_author_name' ) ) {
	function almaden_blog_post_author_name( int $user_id ): string {
		$first = (string) get_user_meta( $user_id, 'first_name', true );
		$last = (string) get_user_meta( $user_id, 'last_name', true );
		$full = trim( trim( $first ) . ' ' . trim( $last ) );
		if ( '' !== $full ) {
			return $full;
		}

		$display = (string) get_the_author_meta( 'display_name', $user_id );
		return '' !== $display ? $display : 'Almaden';
	}
}

if ( ! function_exists( 'almaden_blog_post_lead_text' ) ) {
	function almaden_blog_post_lead_text( WP_Post $post, int $words = 40 ): string {
		$excerpt = trim( (string) $post->post_excerpt );
		if ( '' !== $excerpt ) {
			return wp_trim_words( $excerpt, $words, '...' );
		}

		$content = wp_strip_all_tags( (string) strip_shortcodes( (string) $post->post_content ) );
		return wp_trim_words( $content, $words, '...' );
	}
}

$featured_img = get_the_post_thumbnail_url( $post, 'full' );
$author_name = almaden_blog_post_author_name( (int) $post->post_author );
$published = get_the_date( 'j \d\e F, Y', $post );
$lead = almaden_blog_post_lead_text( $post, 40 );
$related_posts = get_posts(
	array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => 4,
		'post__not_in'   => array( (int) $post->ID ),
		'orderby'        => 'date',
		'order'          => 'DESC',
		'no_found_rows'  => true,
	)
);

get_header();
?>

<main class="almaden-blog-post-shell">
	<article class="almaden-blog-post-article">
		<header class="almaden-blog-post-hero">
			<?php if ( is_string( $featured_img ) && '' !== $featured_img ) : ?>
				<div class="almaden-blog-post-cover">
					<img src="<?php echo esc_url( $featured_img ); ?>" alt="">
				</div>
			<?php endif; ?>

			<div class="space-y-5">
				<p class="text-xs uppercase tracking-[0.26em] text-slate-400"><?php echo esc_html( get_the_category_list( ', ', '', $post->ID ) ? wp_strip_all_tags( get_the_category_list( ', ', '', $post->ID ) ) : 'Blog' ); ?></p>
				<h1 class="almaden-blog-post-title"><?php echo esc_html( get_the_title( $post ) ); ?></h1>
				<div class="almaden-blog-post-meta">
					<span><?php echo esc_html( $author_name ); ?></span>
					<span><?php echo esc_html( $published ); ?></span>
				</div>
				<p class="max-w-3xl text-lg leading-8 text-slate-600"><?php echo esc_html( $lead ); ?></p>
			</div>
		</header>

		<div class="almaden-blog-post-content prose max-w-none">
			<?php echo apply_filters( 'the_content', (string) $post->post_content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>

		<?php if ( ! empty( $related_posts ) ) : ?>
			<section class="almaden-blog-post-related">
				<div class="mb-4 flex items-end justify-between gap-4">
					<div>
						<p class="text-xs uppercase tracking-[0.24em] text-slate-400"><?php esc_html_e( 'Más lecturas', 'almaden-bookster' ); ?></p>
						<h2 class="mt-1 text-2xl font-semibold text-slate-950"><?php esc_html_e( 'Posts relacionados', 'almaden-bookster' ); ?></h2>
					</div>
				</div>
				<div class="almaden-blog-post-related-grid">
					<?php foreach ( $related_posts as $related_post ) : ?>
						<?php
						if ( ! $related_post instanceof WP_Post ) {
							continue;
						}
						$thumb = get_the_post_thumbnail_url( $related_post, 'medium_large' );
						$excerpt = wp_trim_words( wp_strip_all_tags( (string) $related_post->post_excerpt ?: (string) $related_post->post_content ), 22, '...' );
						?>
						<a href="<?php echo esc_url( get_permalink( $related_post ) ); ?>" class="almaden-blog-post-related-card">
							<?php if ( is_string( $thumb ) && '' !== $thumb ) : ?>
								<img src="<?php echo esc_url( $thumb ); ?>" alt="">
							<?php endif; ?>
							<h3><?php echo esc_html( get_the_title( $related_post ) ); ?></h3>
							<p><?php echo esc_html( $excerpt ); ?></p>
						</a>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endif; ?>
	</article>

	<?php comments_template(); ?>
</main>

<?php
get_footer();

