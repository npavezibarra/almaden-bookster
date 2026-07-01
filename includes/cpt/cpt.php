<?php
// Registrar el Custom Post Type: Libros (almaden-books)
function almaden_bookster_register_cpt_books() {
	$labels = array(
		'name'                  => _x( 'Libros', 'Post Type General Name', 'almaden-bookster' ),
		'singular_name'         => _x( 'Libro', 'Post Type Singular Name', 'almaden-bookster' ),
		'menu_name'             => __( 'Libros', 'almaden-bookster' ),
		'name_admin_bar'        => __( 'Libro', 'almaden-bookster' ),
		'archives'              => __( 'Archivos de Libros', 'almaden-bookster' ),
		'attributes'            => __( 'Atributos de Libro', 'almaden-bookster' ),
		'parent_item_colon'     => __( 'Libro Padre:', 'almaden-bookster' ),
		'all_items'             => __( 'Todos los Libros', 'almaden-bookster' ),
		'add_new_item'          => __( 'Añadir Nuevo Libro', 'almaden-bookster' ),
		'add_new'               => __( 'Añadir Nuevo', 'almaden-bookster' ),
		'new_item'              => __( 'Nuevo Libro', 'almaden-bookster' ),
		'edit_item'             => __( 'Editar Libro', 'almaden-bookster' ),
		'update_item'           => __( 'Actualizar Libro', 'almaden-bookster' ),
		'view_item'             => __( 'Ver Libro', 'almaden-bookster' ),
		'view_items'            => __( 'Ver Libros', 'almaden-bookster' ),
		'search_items'          => __( 'Buscar Libro', 'almaden-bookster' ),
		'not_found'             => __( 'No encontrado', 'almaden-bookster' ),
		'not_found_in_trash'    => __( 'No encontrado en la Papelera', 'almaden-bookster' ),
		'featured_image'        => __( 'Imagen Destacada', 'almaden-bookster' ),
		'set_featured_image'    => __( 'Establecer imagen destacada', 'almaden-bookster' ),
		'remove_featured_image' => __( 'Quitar imagen destacada', 'almaden-bookster' ),
		'use_featured_image'    => __( 'Usar como imagen destacada', 'almaden-bookster' ),
		'insert_into_item'      => __( 'Insertar en el libro', 'almaden-bookster' ),
		'uploaded_to_this_item' => __( 'Subido a este libro', 'almaden-bookster' ),
		'items_list'            => __( 'Lista de libros', 'almaden-bookster' ),
		'items_list_navigation' => __( 'Navegación de lista de libros', 'almaden-bookster' ),
		'filter_items_list'     => __( 'Filtrar lista de libros', 'almaden-bookster' ),
	);
	$args = array(
		'label'                 => __( 'Libro', 'almaden-bookster' ),
		'description'           => __( 'Libros físicos y digitales', 'almaden-bookster' ),
		'labels'                => $labels,
		'supports'              => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'page-attributes' ),
		'taxonomies'            => array( 'category', 'post_tag' ),
		'hierarchical'          => false,
		'public'                => true,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'menu_position'         => 5,
		'menu_icon'             => 'dashicons-book-alt',
		'show_in_admin_bar'     => true,
		'show_in_nav_menus'     => true,
		'can_export'            => true,
		'has_archive'           => true,
		'exclude_from_search'   => false,
		'publicly_queryable'    => true,
		'capability_type'       => 'post',
		'show_in_rest'          => true, // Habilita Gutenberg
	);
	register_post_type( 'almaden-books', $args );
}
add_action( 'init', 'almaden_bookster_register_cpt_books', 0 );

function almaden_bookster_register_cpt_chapters() {
	$labels = array(
		'name'                  => _x( 'Capítulos', 'Post Type General Name', 'almaden-bookster' ),
		'singular_name'         => _x( 'Capítulo', 'Post Type Singular Name', 'almaden-bookster' ),
		'menu_name'             => __( 'Capítulos', 'almaden-bookster' ),
		'name_admin_bar'        => __( 'Capítulo', 'almaden-bookster' ),
		'archives'              => __( 'Archivo de Capítulos', 'almaden-bookster' ),
		'attributes'            => __( 'Atributos del Capítulo', 'almaden-bookster' ),
		'parent_item_colon'     => __( 'Libro Padre:', 'almaden-bookster' ),
		'all_items'             => __( 'Todos los Capítulos', 'almaden-bookster' ),
		'add_new_item'          => __( 'Añadir Nuevo Capítulo', 'almaden-bookster' ),
		'add_new'               => __( 'Añadir Nuevo', 'almaden-bookster' ),
		'new_item'              => __( 'Nuevo Capítulo', 'almaden-bookster' ),
		'edit_item'             => __( 'Editar Capítulo', 'almaden-bookster' ),
		'update_item'           => __( 'Actualizar Capítulo', 'almaden-bookster' ),
		'view_item'             => __( 'Ver Capítulo', 'almaden-bookster' ),
		'view_items'            => __( 'Ver Capítulos', 'almaden-bookster' ),
		'search_items'          => __( 'Buscar Capítulo', 'almaden-bookster' ),
		'not_found'             => __( 'No encontrado', 'almaden-bookster' ),
		'not_found_in_trash'    => __( 'No encontrado en la Papelera', 'almaden-bookster' ),
	);
	$args = array(
		'label'                 => __( 'Capítulo', 'almaden-bookster' ),
		'description'           => __( 'Capítulos de libros', 'almaden-bookster' ),
		'labels'                => $labels,
		'supports'              => array( 'title', 'editor', 'revisions', 'page-attributes', 'custom-fields' ),
		'hierarchical'          => false,
		'public'                => true,
		'show_ui'               => true,
		'show_in_menu'          => 'edit.php?post_type=almaden-books', // Submenú de Libros
		'menu_position'         => 5,
		'show_in_admin_bar'     => true,
		'show_in_nav_menus'     => true,
		'can_export'            => true,
		'has_archive'           => false,
		'exclude_from_search'   => false,
		'publicly_queryable'    => true,
		'capability_type'       => 'post',
		'show_in_rest'          => true, // Habilita Gutenberg
	);
	register_post_type( 'book_chapter', $args );
}
add_action( 'init', 'almaden_bookster_register_cpt_chapters', 0 );

function almaden_bookster_register_book_access_metabox() {
	add_meta_box(
		'almaden-book-access',
		__( 'Acceso al Ebook', 'almaden-bookster' ),
		'almaden_bookster_render_book_access_metabox',
		'almaden-books',
		'side',
		'default'
	);
}
add_action( 'add_meta_boxes_almaden-books', 'almaden_bookster_register_book_access_metabox' );

function almaden_bookster_render_book_access_metabox( $post ) {
	wp_nonce_field( 'almaden_book_access_meta_' . $post->ID, 'almaden_book_access_meta_nonce' );
	$product_id = (int) get_post_meta( $post->ID, '_almaden_wc_product_id', true );
	?>
	<p>
		<label for="almaden_wc_product_id" style="display:block; font-weight:600; margin-bottom:6px;">
			<?php esc_html_e( 'WooCommerce Product ID', 'almaden-bookster' ); ?>
		</label>
		<input
			type="number"
			min="0"
			step="1"
			id="almaden_wc_product_id"
			name="almaden_wc_product_id"
			value="<?php echo esc_attr( $product_id ); ?>"
			style="width:100%;"
			placeholder="123"
		/>
	</p>
	<p style="margin-bottom:0; color:#6b7280;">
		<?php esc_html_e( 'Si se configura, solo podrán abrir el libro los usuarios que hayan comprado este producto.', 'almaden-bookster' ); ?>
	</p>
	<?php
}

function almaden_bookster_save_book_access_metabox( $post_id ) {
	if ( get_post_type( $post_id ) !== 'almaden-books' ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! isset( $_POST['almaden_book_access_meta_nonce'] ) || ! wp_verify_nonce( $_POST['almaden_book_access_meta_nonce'], 'almaden_book_access_meta_' . $post_id ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$product_id = isset( $_POST['almaden_wc_product_id'] ) ? absint( $_POST['almaden_wc_product_id'] ) : 0;
	if ( $product_id > 0 ) {
		update_post_meta( $post_id, '_almaden_wc_product_id', $product_id );
	} else {
		delete_post_meta( $post_id, '_almaden_wc_product_id' );
	}
}
add_action( 'save_post', 'almaden_bookster_save_book_access_metabox' );
