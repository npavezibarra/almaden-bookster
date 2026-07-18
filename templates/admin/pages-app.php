<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap" id="almaden-pages-app">
	<h1>Pages - AlmadenBookster</h1>
	<?php if ( ! empty( $success_flag ) ) : ?>
		<div class="notice notice-success is-dismissible">
			<p>Las rutas del plugin se guardaron correctamente.</p>
		</div>
	<?php endif; ?>

		<p class="description" style="max-width: 860px;">
			Define aquí las rutas internas del plugin. Estas páginas están pensadas para administradores y editores de libros, no para usuarios finales.
		</p>

	<div class="card" style="max-width: 860px; padding: 24px; margin-top: 20px;">
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="almaden_bookster_save_pages_settings" />
			<input type="hidden" name="creator_page_id" value="<?php echo esc_attr( isset( $settings['creator_page_id'] ) ? absint( $settings['creator_page_id'] ) : 0 ); ?>" />
			<input type="hidden" name="course_creator_page_id" value="<?php echo esc_attr( isset( $settings['course_creator_page_id'] ) ? absint( $settings['course_creator_page_id'] ) : 0 ); ?>" />
			<input type="hidden" name="course_archive_page_id" value="<?php echo esc_attr( isset( $settings['course_archive_page_id'] ) ? absint( $settings['course_archive_page_id'] ) : 0 ); ?>" />
			<input type="hidden" name="authors_page_id" value="<?php echo esc_attr( isset( $settings['authors_page_id'] ) ? absint( $settings['authors_page_id'] ) : 0 ); ?>" />
			<input type="hidden" name="store_page_id" value="<?php echo esc_attr( isset( $settings['store_page_id'] ) ? absint( $settings['store_page_id'] ) : 0 ); ?>" />
			<?php wp_nonce_field( 'almaden_bookster_pages_settings', 'almaden_pages_nonce' ); ?>

			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row"><label for="creator_title">Título de la página</label></th>
						<td>
							<input type="text" class="regular-text" id="creator_title" name="creator_title" value="<?php echo esc_attr( $settings['creator_title'] ); ?>" />
							<p class="description">Título visible de la página interna del creador.</p>
						</td>
					</tr>
			<tr>
				<th scope="row"><label for="creator_slug">Slug de la página</label></th>
				<td>
					<input type="text" class="regular-text" id="creator_slug" name="creator_slug" value="<?php echo esc_attr( $settings['creator_slug'] ); ?>" />
					<p class="description">Solo el slug, sin barras. Ejemplo: <code>almaden-booklist</code>.</p>
				</td>
			</tr>
					<tr>
						<th scope="row">URL actual</th>
						<td>
							<code><?php echo esc_html( $creator_url ); ?></code>
						</td>
					</tr>
					<tr>
						<th scope="row">Acceso</th>
						<td>
							Admins y editores de libros con la capability <code>almaden_manage_books</code>.
						</td>
					</tr>
			<tr><th colspan="2"><hr style="margin: 16px 0; border-color: #e5e7eb;"></th></tr>
			<tr>
				<th scope="row"><label for="course_creator_title">Título de cursos</label></th>
				<td>
					<input type="text" class="regular-text" id="course_creator_title" name="course_creator_title" value="<?php echo esc_attr( $settings['course_creator_title'] ); ?>" />
					<p class="description">Título visible de la página interna para crear cursos.</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="course_creator_slug">Slug de cursos</label></th>
				<td>
					<input type="text" class="regular-text" id="course_creator_slug" name="course_creator_slug" value="<?php echo esc_attr( $settings['course_creator_slug'] ); ?>" />
					<p class="description">Ejemplo: <code>almaden-cursos</code>.</p>
				</td>
			</tr>
			<tr>
				<th scope="row">URL de cursos</th>
				<td>
					<code><?php echo esc_html( function_exists( 'almaden_bookster_get_course_creator_page_url' ) ? almaden_bookster_get_course_creator_page_url() : '' ); ?></code>
				</td>
			</tr>
			<tr><th colspan="2"><hr style="margin: 16px 0; border-color: #e5e7eb;"></th></tr>
			<tr>
				<th scope="row"><label for="course_archive_title">Título de la sala de clases</label></th>
				<td>
					<input type="text" class="regular-text" id="course_archive_title" name="course_archive_title" value="<?php echo esc_attr( $settings['course_archive_title'] ); ?>" />
					<p class="description">Título visible del archive público de cursos publicados.</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="course_archive_slug">Slug de la sala de clases</label></th>
				<td>
					<input type="text" class="regular-text" id="course_archive_slug" name="course_archive_slug" value="<?php echo esc_attr( $settings['course_archive_slug'] ); ?>" />
					<p class="description">Ejemplo: <code>sala-de-clases</code>.</p>
				</td>
			</tr>
			<tr>
				<th scope="row">URL de la sala de clases</th>
				<td>
					<code><?php echo esc_html( function_exists( 'almaden_bookster_get_course_archive_page_url' ) ? almaden_bookster_get_course_archive_page_url() : '' ); ?></code>
				</td>
			</tr>
			<tr>
				<th scope="row">Acceso a cursos</th>
				<td>
					Administradores, editores y usuarios con permisos nativos del módulo Learni.
				</td>
			</tr>
			<tr><th colspan="2"><hr style="margin: 16px 0; border-color: #e5e7eb;"></th></tr>
			<tr>
				<th scope="row"><label for="authors_title">Título de autores</label></th>
				<td>
							<input type="text" class="regular-text" id="authors_title" name="authors_title" value="<?php echo esc_attr( $settings['authors_title'] ); ?>" />
							<p class="description">Título visible de la página interna de autores.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="authors_slug">Slug de autores</label></th>
						<td>
							<input type="text" class="regular-text" id="authors_slug" name="authors_slug" value="<?php echo esc_attr( $settings['authors_slug'] ); ?>" />
							<p class="description">Solo el slug, sin barras. Ejemplo: <code>autores</code>.</p>
						</td>
					</tr>
					<tr>
						<th scope="row">URL de autores</th>
						<td>
							<code><?php echo esc_html( almaden_bookster_get_authors_page_url() ); ?></code>
						</td>
					</tr>
					<tr>
						<th scope="row">Acceso a autores</th>
						<td>
							Administradores y editores con la capability <code>almaden_manage_books</code>.
						</td>
					</tr>
					<tr><th colspan="2"><hr style="margin: 16px 0; border-color: #e5e7eb;"></th></tr>
					<tr>
						<th scope="row"><label for="store_title">Título del catálogo</label></th>
						<td>
							<input type="text" class="regular-text" id="store_title" name="store_title" value="<?php echo esc_attr( $settings['store_title'] ); ?>" />
							<p class="description">Título visible de la página pública del catálogo de ebooks.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="store_slug">Slug del catálogo</label></th>
						<td>
							<input type="text" class="regular-text" id="store_slug" name="store_slug" value="<?php echo esc_attr( $settings['store_slug'] ); ?>" />
							<p class="description">Ejemplo: <code>bookshelf</code> o cualquier slug de tu sitio.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="store_menu_label">Etiqueta del menú</label></th>
						<td>
							<input type="text" class="regular-text" id="store_menu_label" name="store_menu_label" value="<?php echo esc_attr( $settings['store_menu_label'] ); ?>" />
							<p class="description">Texto del enlace que se inyecta en el menú público del sitio.</p>
						</td>
					</tr>
					<tr>
						<th scope="row">Mostrar en el menú</th>
						<td>
							<label>
								<input type="checkbox" name="store_menu_enabled" value="1" <?php checked( ! empty( $settings['store_menu_enabled'] ) ); ?> />
								Activar inserción automática de <strong>Ebook Store</strong> en el menú del sitio.
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row">URL del catálogo</th>
						<td>
							<code><?php echo esc_html( almaden_bookster_get_store_page_url() ); ?></code>
						</td>
					</tr>
				</tbody>
			</table>

			<p class="submit" style="padding-left: 0;">
				<button type="submit" class="button button-primary">Guardar cambios</button>
			</p>
		</form>
	</div>
</div>
