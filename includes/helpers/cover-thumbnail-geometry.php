<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function almaden_get_thumbnail_fonts_url() {
    require_once dirname(__FILE__) . '/../admin/admin-fonts.php';
    if ( ! function_exists( 'almaden_bookster_get_available_fonts_list' ) || ! function_exists( 'almaden_bookster_build_google_fonts_url' ) ) {
        return '';
    }

    return almaden_bookster_build_google_fonts_url( almaden_bookster_get_available_fonts_list() );
}

function almaden_bookster_round_up_mm( $value ) {
    $value = floatval( $value );
    if ( $value <= 0 ) {
        return 0;
    }

    return (int) ceil( $value );
}

function almaden_bookster_get_cover_spine_width_mm( $cover_settings, $pages ) {
    $pages = intval( $pages );
    if ( $pages < 20 ) {
        $pages = 20;
    }

    $thickness = isset( $cover_settings['paper_type'] ) ? floatval( $cover_settings['paper_type'] ) : 0.06;
    $auto_spine_width_mm = $thickness * $pages;

    $spine_mode = isset( $cover_settings['spine_width_mode'] ) ? sanitize_text_field( $cover_settings['spine_width_mode'] ) : 'auto';
    $manual_spine_width_mm = isset( $cover_settings['spine_width_mm'] ) ? floatval( $cover_settings['spine_width_mm'] ) : 0;

    if ( $spine_mode === 'manual' && $manual_spine_width_mm > 0 ) {
        return almaden_bookster_round_up_mm( $manual_spine_width_mm );
    }

    return almaden_bookster_round_up_mm( $auto_spine_width_mm );
}

function almaden_bookster_get_cover_fold_x_mm( $cover_settings ) {
    $fold_x_mm = isset( $cover_settings['fold_x'] ) ? floatval( $cover_settings['fold_x'] ) : ( isset( $cover_settings['fold_x_mm'] ) ? floatval( $cover_settings['fold_x_mm'] ) : 0 );
    return $fold_x_mm > 0 ? almaden_bookster_round_up_mm( $fold_x_mm ) : 0;
}

function almaden_bookster_cover_settings_table_exists() {
    global $wpdb;

    static $table_exists = null;

    if ( null !== $table_exists ) {
        return $table_exists;
    }

    $settings_table = $wpdb->prefix . 'almaden_book_settings';
    $table_exists = almaden_bookster_table_exists( $settings_table );

    return $table_exists;
}

function almaden_bookster_get_cover_settings_row( $book_id ) {
    global $wpdb, $almaden_bookster_cover_settings_cache;
    $book_id = absint( $book_id );

    if ( ! is_array( $almaden_bookster_cover_settings_cache ?? null ) ) {
        $almaden_bookster_cover_settings_cache = array();
    }

    if ( $book_id <= 0 ) {
        return array();
    }

    if ( array_key_exists( $book_id, $almaden_bookster_cover_settings_cache ) ) {
        return is_array( $almaden_bookster_cover_settings_cache[ $book_id ] ) ? $almaden_bookster_cover_settings_cache[ $book_id ] : array();
    }

    if ( ! almaden_bookster_cover_settings_table_exists() ) {
        $almaden_bookster_cover_settings_cache[ $book_id ] = array();
        return array();
    }

    $settings_table = $wpdb->prefix . 'almaden_book_settings';
    $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $settings_table WHERE book_id = %d", $book_id ), ARRAY_A );
    $almaden_bookster_cover_settings_cache[ $book_id ] = is_array( $row ) ? $row : array();

    return $almaden_bookster_cover_settings_cache[ $book_id ];
}

function almaden_bookster_prime_cover_settings_cache( array $book_ids ) {
    global $wpdb, $almaden_bookster_cover_settings_cache;

    if ( ! is_array( $almaden_bookster_cover_settings_cache ?? null ) ) {
        $almaden_bookster_cover_settings_cache = array();
    }

    $book_ids = array_values( array_unique( array_filter( array_map( 'absint', $book_ids ) ) ) );
    if ( empty( $book_ids ) ) {
        return $almaden_bookster_cover_settings_cache;
    }

    $missing_ids = array();
    foreach ( $book_ids as $book_id ) {
        if ( ! array_key_exists( $book_id, $almaden_bookster_cover_settings_cache ) ) {
            $missing_ids[] = $book_id;
        }
    }

    if ( empty( $missing_ids ) ) {
        return $almaden_bookster_cover_settings_cache;
    }

    if ( ! almaden_bookster_cover_settings_table_exists() ) {
        foreach ( $missing_ids as $book_id ) {
            $almaden_bookster_cover_settings_cache[ $book_id ] = array();
        }
        return $almaden_bookster_cover_settings_cache;
    }

    $settings_table = $wpdb->prefix . 'almaden_book_settings';
    $placeholders = implode( ',', array_fill( 0, count( $missing_ids ), '%d' ) );
    $query = $wpdb->prepare(
        "SELECT * FROM $settings_table WHERE book_id IN ($placeholders)",
        $missing_ids
    );
    $rows = $wpdb->get_results( $query, ARRAY_A );

    foreach ( $missing_ids as $book_id ) {
        $almaden_bookster_cover_settings_cache[ $book_id ] = array();
    }

    if ( is_array( $rows ) ) {
        foreach ( $rows as $row ) {
            if ( isset( $row['book_id'] ) ) {
                $almaden_bookster_cover_settings_cache[ absint( $row['book_id'] ) ] = $row;
            }
        }
    }

    return $almaden_bookster_cover_settings_cache;
}
