<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( post_password_required() ) {
	return;
}
?>

<section id="comments" class="mt-12 rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
	<?php if ( have_comments() ) : ?>
		<h2 class="mb-4 text-2xl font-semibold text-slate-950">
			<?php
			printf(
				esc_html( _n( '1 comentario', '%1$s comentarios', get_comments_number(), 'almaden-bookster' ) ),
				esc_html( number_format_i18n( get_comments_number() ) )
			);
			?>
		</h2>
		<ol class="space-y-4">
			<?php wp_list_comments( array( 'style' => 'ol', 'short_ping' => true ) ); ?>
		</ol>
		<?php the_comments_navigation(); ?>
	<?php endif; ?>

	<?php if ( comments_open() ) : ?>
		<div class="mt-8">
			<?php comment_form(); ?>
		</div>
	<?php endif; ?>
</section>

