<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function almaden_bookster_get_cover_thumbnail_snapshot_meta_keys() {
    return array(
        'snapshot_id'        => '_almaden_cover_thumbnail_snapshot',
        'version'            => '_almaden_cover_thumbnail_snapshot_version',
        'width'              => '_almaden_cover_thumbnail_snapshot_width',
        'height'             => '_almaden_cover_thumbnail_snapshot_height',
        'mime'               => '_almaden_cover_thumbnail_snapshot_mime',
        'generated_at'       => '_almaden_cover_thumbnail_snapshot_generated_at',
    );
}

function almaden_bookster_get_cover_thumbnail_snapshot_metadata( $book_id ) {
    $book_id = absint( $book_id );
    if ( $book_id <= 0 ) {
        return array();
    }

    $keys = almaden_bookster_get_cover_thumbnail_snapshot_meta_keys();
    $attachment_id = absint( get_post_meta( $book_id, $keys['snapshot_id'], true ) );

    if ( $attachment_id <= 0 ) {
        return array();
    }

    $width = absint( get_post_meta( $book_id, $keys['width'], true ) );
    $height = absint( get_post_meta( $book_id, $keys['height'], true ) );
    $version = sanitize_text_field( (string) get_post_meta( $book_id, $keys['version'], true ) );
    $mime = sanitize_text_field( (string) get_post_meta( $book_id, $keys['mime'], true ) );
    $generated_at = sanitize_text_field( (string) get_post_meta( $book_id, $keys['generated_at'], true ) );

    $url = '';
    if ( function_exists( 'wp_get_attachment_image_url' ) ) {
        $url = wp_get_attachment_image_url( $attachment_id, 'full' );
    }

    if ( empty( $url ) ) {
        $url = wp_get_attachment_url( $attachment_id );
    }

    $path = '';
    if ( function_exists( 'get_attached_file' ) ) {
        $path = get_attached_file( $attachment_id );
    }

    if ( ! empty( $path ) && ! file_exists( $path ) ) {
        $path = '';
    }

	// Snapshots are stored locally. Rendering a stale attachment URL produces a
	// broken image in the book list, so fall back to the lightweight cover preview.
	if ( empty( $path ) ) {
		return array();
	}

    return array(
        'attachment_id' => $attachment_id,
        'version'       => $version,
        'width'         => $width,
        'height'        => $height,
        'mime'          => $mime,
        'generated_at'  => $generated_at,
        'url'           => $url ? esc_url_raw( $url ) : '',
        'path'          => $path ? wp_normalize_path( $path ) : '',
    );
}

function almaden_bookster_get_cover_thumbnail_snapshot_url( $book_id ) {
    $snapshot = almaden_bookster_get_cover_thumbnail_snapshot_metadata( $book_id );
    return isset( $snapshot['url'] ) ? $snapshot['url'] : '';
}

function almaden_bookster_get_cover_thumbnail_snapshot_path( $book_id ) {
    $snapshot = almaden_bookster_get_cover_thumbnail_snapshot_metadata( $book_id );
    return isset( $snapshot['path'] ) ? $snapshot['path'] : '';
}

function almaden_bookster_has_cover_thumbnail_snapshot( $book_id ) {
    $snapshot = almaden_bookster_get_cover_thumbnail_snapshot_metadata( $book_id );
    return ! empty( $snapshot['url'] ) || ! empty( $snapshot['path'] );
}

function almaden_bookster_render_cover_thumbnail_snapshot_html( $book_id, array $snapshot ) {
    $book_id = absint( $book_id );
    if ( $book_id <= 0 || empty( $snapshot['url'] ) ) {
        return '';
    }

    $width = ! empty( $snapshot['width'] ) ? absint( $snapshot['width'] ) : 0;
    $height = ! empty( $snapshot['height'] ) ? absint( $snapshot['height'] ) : 0;
    $aspect_ratio = ( $width > 0 && $height > 0 ) ? round( $width / $height, 4 ) : 0;
    $ratio_style = $aspect_ratio > 0 ? 'aspect-ratio: ' . $aspect_ratio . ';' : '';

    ob_start();
    ?>
    <div class="cover-thumbnail-wrapper w-full bg-white overflow-hidden relative border-b border-gray-200" data-cover-thumbnail-source="snapshot" data-cover-thumbnail-id="<?php echo esc_attr( $book_id ); ?>" <?php echo $ratio_style ? 'style="' . esc_attr( $ratio_style ) . '"' : ''; ?>>
        <img
            src="<?php echo esc_url( $snapshot['url'] ); ?>"
            alt=""
            class="block h-full w-full object-cover"
            <?php echo $width > 0 ? 'width="' . esc_attr( $width ) . '"' : ''; ?>
            <?php echo $height > 0 ? 'height="' . esc_attr( $height ) . '"' : ''; ?>
            loading="lazy"
            decoding="async"
        />
    </div>
    <?php
    return ob_get_clean();
}

function almaden_bookster_set_cover_thumbnail_snapshot_metadata( $book_id, array $snapshot_data ) {
    $book_id = absint( $book_id );
    if ( $book_id <= 0 ) {
        return false;
    }

    $keys = almaden_bookster_get_cover_thumbnail_snapshot_meta_keys();
    $attachment_id = isset( $snapshot_data['attachment_id'] ) ? absint( $snapshot_data['attachment_id'] ) : 0;

    if ( $attachment_id > 0 ) {
        update_post_meta( $book_id, $keys['snapshot_id'], $attachment_id );
    } else {
        delete_post_meta( $book_id, $keys['snapshot_id'] );
    }

    $version = isset( $snapshot_data['version'] ) ? sanitize_text_field( (string) $snapshot_data['version'] ) : '';
    $width = isset( $snapshot_data['width'] ) ? absint( $snapshot_data['width'] ) : 0;
    $height = isset( $snapshot_data['height'] ) ? absint( $snapshot_data['height'] ) : 0;
    $mime = isset( $snapshot_data['mime'] ) ? sanitize_text_field( (string) $snapshot_data['mime'] ) : '';
    $generated_at = isset( $snapshot_data['generated_at'] ) ? sanitize_text_field( (string) $snapshot_data['generated_at'] ) : '';

    if ( '' !== $version ) {
        update_post_meta( $book_id, $keys['version'], $version );
    } else {
        delete_post_meta( $book_id, $keys['version'] );
    }

    if ( $width > 0 ) {
        update_post_meta( $book_id, $keys['width'], $width );
    } else {
        delete_post_meta( $book_id, $keys['width'] );
    }

    if ( $height > 0 ) {
        update_post_meta( $book_id, $keys['height'], $height );
    } else {
        delete_post_meta( $book_id, $keys['height'] );
    }

    if ( '' !== $mime ) {
        update_post_meta( $book_id, $keys['mime'], $mime );
    } else {
        delete_post_meta( $book_id, $keys['mime'] );
    }

    if ( '' !== $generated_at ) {
        update_post_meta( $book_id, $keys['generated_at'], $generated_at );
    } else {
        delete_post_meta( $book_id, $keys['generated_at'] );
    }

    return true;
}

function almaden_bookster_clear_cover_thumbnail_snapshot_metadata( $book_id ) {
    $book_id = absint( $book_id );
    if ( $book_id <= 0 ) {
        return false;
    }

    $keys = almaden_bookster_get_cover_thumbnail_snapshot_meta_keys();
    foreach ( $keys as $meta_key ) {
        delete_post_meta( $book_id, $meta_key );
    }

    return true;
}

function almaden_bookster_delete_cover_thumbnail_snapshot( $book_id ) {
    $book_id = absint( $book_id );
    if ( $book_id <= 0 ) {
        return false;
    }

    $snapshot = almaden_bookster_get_cover_thumbnail_snapshot_metadata( $book_id );
    if ( ! empty( $snapshot['attachment_id'] ) ) {
        wp_delete_attachment( (int) $snapshot['attachment_id'], true );
    } elseif ( ! empty( $snapshot['path'] ) && file_exists( $snapshot['path'] ) ) {
        @unlink( $snapshot['path'] );
    }

    almaden_bookster_clear_cover_thumbnail_snapshot_metadata( $book_id );
    return true;
}
