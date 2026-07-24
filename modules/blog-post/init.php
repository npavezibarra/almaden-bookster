<?php
/**
 * Module: Blog Post
 *
 * Native blog editor and blog templates for Almaden Bookster.
 */

namespace AlmadenBookster\BlogPost;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'ALMADEN_BOOKSTER_BLOG_POST_PLUGIN_FILE' ) ) {
	define( 'ALMADEN_BOOKSTER_BLOG_POST_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'ALMADEN_BOOKSTER_BLOG_POST_PLUGIN_DIR' ) ) {
	define( 'ALMADEN_BOOKSTER_BLOG_POST_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'ALMADEN_BOOKSTER_BLOG_POST_PLUGIN_URL' ) ) {
	define( 'ALMADEN_BOOKSTER_BLOG_POST_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

require_once ALMADEN_BOOKSTER_BLOG_POST_PLUGIN_DIR . 'includes/class-blog-post-editor.php';
require_once ALMADEN_BOOKSTER_BLOG_POST_PLUGIN_DIR . 'includes/class-blog-post-template.php';

Editor::init();
Template::init();

