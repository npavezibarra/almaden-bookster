<?php

use AlmadenBookster\BlogPost\Editor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_user = wp_get_current_user();
$page_title = function_exists( 'almaden_bookster_get_blog_creator_title' ) ? almaden_bookster_get_blog_creator_title() : __( 'Blog', 'almaden-bookster' );

almaden_bookster_render_app_shell_start(
	array(
		'title'          => $page_title . ' - Almaden',
		'body_class'     => array( 'min-h-screen', 'flex', 'flex-col', 'theme-light', 'almaden-blog-post-app-body' ),
		'active_nav_key' => 'blog_creator',
		'logo_text'      => 'almaden',
		'extra_head_html' => '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Newsreader:opsz,wght@6..72,200;6..72,300;6..72,400;6..72,500;6..72,600&display=swap" rel="stylesheet">',
	)
);
?>
<main id="almaden-blog-post-app" class="almaden-app-content-shell flex-1 pb-10">
	<div class="grid gap-6 xl:grid-cols-[320px_minmax(0,1fr)]">
		<aside class="almaden-blog-sidebar rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm">
			<div class="flex items-center gap-4">
				<?php echo get_avatar( (int) $current_user->ID, 72, '', (string) ( $current_user->display_name ?? '' ), array( 'class' => 'h-18 w-18 rounded-full object-cover' ) ); ?>
				<div class="min-w-0">
					<p class="truncate text-sm font-semibold text-slate-950"><?php echo esc_html( (string) ( $current_user->display_name ?? $current_user->user_login ?? '' ) ); ?></p>
					<p class="text-xs uppercase tracking-[0.22em] text-slate-400"><?php esc_html_e( 'Escritos', 'almaden-bookster' ); ?></p>
				</div>
			</div>

			<button type="button" id="almaden-blog-open-create" class="mt-6 inline-flex w-full items-center justify-center rounded-2xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
				<?php esc_html_e( 'Nuevo post', 'almaden-bookster' ); ?>
			</button>

			<div class="mt-8">
				<div class="flex items-end justify-between gap-3">
					<div>
						<p class="text-xs uppercase tracking-[0.24em] text-slate-400"><?php esc_html_e( 'Tus posts', 'almaden-bookster' ); ?></p>
						<h2 class="mt-1 text-xl font-semibold text-slate-950"><?php esc_html_e( 'Mis escritos', 'almaden-bookster' ); ?></h2>
					</div>
				</div>

				<div id="almaden-blog-post-list" class="mt-4 space-y-3">
					<div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
						<?php esc_html_e( 'Cargando tus posts...', 'almaden-bookster' ); ?>
					</div>
				</div>
			</div>
		</aside>

		<section id="almaden-blog-editor-panel" class="hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
			<form id="almaden-blog-post-form" class="flex min-h-[72vh] flex-col">
				<input type="hidden" id="almaden-blog-post-id" value="0">
				<input type="hidden" id="almaden-blog-post-thumbnail-id" value="0">

				<header class="sticky top-0 z-10 border-b border-slate-200 bg-white/95 px-5 py-4 backdrop-blur">
					<div class="flex flex-wrap items-center gap-2">
						<button type="button" class="almaden-blog-tool-btn" data-cmd="back" title="<?php esc_attr_e( 'Volver', 'almaden-bookster' ); ?>">←</button>
						<div class="h-6 w-px bg-slate-200"></div>
						<button type="button" class="almaden-blog-tool-btn" data-cmd="bold"><strong>B</strong></button>
						<button type="button" class="almaden-blog-tool-btn" data-cmd="italic"><em>I</em></button>
						<div class="h-6 w-px bg-slate-200"></div>
						<button type="button" class="almaden-blog-tool-btn" data-cmd="justifyLeft"><?php esc_html_e( 'Izq', 'almaden-bookster' ); ?></button>
						<button type="button" class="almaden-blog-tool-btn" data-cmd="justifyCenter"><?php esc_html_e( 'Cen', 'almaden-bookster' ); ?></button>
						<button type="button" class="almaden-blog-tool-btn" data-cmd="justifyRight"><?php esc_html_e( 'Der', 'almaden-bookster' ); ?></button>
						<button type="button" class="almaden-blog-tool-btn" data-cmd="justifyFull"><?php esc_html_e( 'Just', 'almaden-bookster' ); ?></button>
						<div class="h-6 w-px bg-slate-200"></div>
						<div class="relative" data-blog-heading-dropdown>
							<button type="button" class="almaden-blog-tool-btn" data-cmd="heading"><?php esc_html_e( 'H', 'almaden-bookster' ); ?></button>
							<div class="absolute left-0 top-full z-20 mt-2 hidden w-44 rounded-2xl border border-slate-200 bg-white p-2 shadow-lg" data-blog-heading-menu>
								<button type="button" class="blog-heading-item" data-tag="h1">H1</button>
								<button type="button" class="blog-heading-item" data-tag="h2">H2</button>
								<button type="button" class="blog-heading-item" data-tag="h3">H3</button>
								<button type="button" class="blog-heading-item" data-tag="p"><?php esc_html_e( 'Párrafo', 'almaden-bookster' ); ?></button>
							</div>
						</div>
						<div class="h-6 w-px bg-slate-200"></div>
						<button type="button" class="almaden-blog-tool-btn" data-cmd="insertImage"><?php esc_html_e( 'Imagen', 'almaden-bookster' ); ?></button>

						<div class="ml-auto flex flex-wrap items-center gap-2">
							<a id="almaden-blog-post-preview" href="#" target="_blank" class="hidden rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-amber-300 hover:text-slate-950"><?php esc_html_e( 'Vista previa', 'almaden-bookster' ); ?></a>
							<button type="button" class="rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300" data-blog-action="draft"><?php esc_html_e( 'Guardar', 'almaden-bookster' ); ?></button>
							<button type="button" class="rounded-2xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800" data-blog-action="publish"><?php esc_html_e( 'Publicar', 'almaden-bookster' ); ?></button>
						</div>
					</div>
				</header>

				<div class="flex-1 px-5 py-6">
					<div class="mx-auto max-w-4xl">
						<textarea id="almaden-blog-post-title" class="block w-full resize-none border-0 bg-transparent font-[Newsreader] text-5xl font-semibold tracking-tight text-slate-950 outline-none placeholder:text-slate-300 focus:ring-0" rows="1" placeholder="<?php esc_attr_e( 'The title...', 'almaden-bookster' ); ?>"></textarea>

						<div class="mt-8 rounded-[2rem] border border-dashed border-slate-200 bg-slate-50 p-5">
							<div id="almaden-blog-cover-preview" class="hidden overflow-hidden rounded-[1.5rem]">
								<img src="" alt="" class="block h-auto w-full object-cover">
							</div>
							<button type="button" id="almaden-blog-cover-upload" class="flex w-full items-center justify-center gap-3 rounded-[1.5rem] border border-slate-200 bg-white px-5 py-7 text-sm font-semibold uppercase tracking-[0.2em] text-slate-400 transition hover:border-amber-300 hover:text-slate-700">
								<span>+</span>
								<span><?php esc_html_e( 'Subir portada del artículo', 'almaden-bookster' ); ?></span>
							</button>
							<button type="button" id="almaden-blog-cover-remove" class="mt-3 hidden text-sm font-medium text-slate-500 underline decoration-slate-300 underline-offset-4"><?php esc_html_e( 'Quitar portada', 'almaden-bookster' ); ?></button>
						</div>

						<div class="mt-8 relative">
							<div id="almaden-blog-placeholder" class="pointer-events-none absolute left-0 top-0 z-10 text-2xl font-light text-slate-300">
								<?php esc_html_e( 'Escribe tu artículo aquí...', 'almaden-bookster' ); ?>
							</div>
							<div id="almaden-blog-editor" class="min-h-[44rem] rounded-[2rem] border border-slate-200 bg-white px-1 py-1 text-[20px] leading-[1.8] text-slate-800 outline-none" contenteditable="true" spellcheck="true"></div>
						</div>

						<textarea id="almaden-blog-post-excerpt" class="mt-6 hidden w-full rounded-2xl border border-slate-200 px-4 py-3 text-base" placeholder="<?php esc_attr_e( 'Excerpt', 'almaden-bookster' ); ?>"></textarea>
					</div>
				</div>
			</form>
		</section>
	</div>
</main>
<?php
almaden_bookster_render_app_shell_end();

