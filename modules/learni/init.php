<?php
/**
 * Module: Learni
 *
 * Native LMS module for Almaden Bookster.
 */

namespace AlmadenBookster\Learni;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'ALMADEN_BOOKSTER_LEARNI_VERSION' ) ) {
	define( 'ALMADEN_BOOKSTER_LEARNI_VERSION', '0.1.0' );
}

if ( ! defined( 'ALMADEN_BOOKSTER_LEARNI_DB_VERSION' ) ) {
	define( 'ALMADEN_BOOKSTER_LEARNI_DB_VERSION', 1 );
}

if ( ! defined( 'ALMADEN_BOOKSTER_LEARNI_PLUGIN_FILE' ) ) {
	define( 'ALMADEN_BOOKSTER_LEARNI_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'ALMADEN_BOOKSTER_LEARNI_PLUGIN_DIR' ) ) {
	define( 'ALMADEN_BOOKSTER_LEARNI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'ALMADEN_BOOKSTER_LEARNI_PLUGIN_URL' ) ) {
	define( 'ALMADEN_BOOKSTER_LEARNI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

require_once ALMADEN_BOOKSTER_LEARNI_PLUGIN_DIR . 'includes/Database/Installer.php';
require_once ALMADEN_BOOKSTER_LEARNI_PLUGIN_DIR . 'includes/PostTypes/Course.php';
require_once ALMADEN_BOOKSTER_LEARNI_PLUGIN_DIR . 'includes/PostTypes/Lesson.php';
require_once ALMADEN_BOOKSTER_LEARNI_PLUGIN_DIR . 'includes/Dashboard/class-creator-dashboard.php';
require_once ALMADEN_BOOKSTER_LEARNI_PLUGIN_DIR . 'includes/Dashboard/class-course-editor-handler.php';
require_once ALMADEN_BOOKSTER_LEARNI_PLUGIN_DIR . 'includes/QuizEditor/Permissions.php';
require_once ALMADEN_BOOKSTER_LEARNI_PLUGIN_DIR . 'includes/QuizEditor/QuizRepository.php';
require_once ALMADEN_BOOKSTER_LEARNI_PLUGIN_DIR . 'includes/QuizEditor/QuizEditor.php';

use AlmadenBookster\Learni\Database\Installer;
use AlmadenBookster\Learni\Dashboard\CreatorDashboard;
use AlmadenBookster\Learni\Dashboard\CourseEditorHandler;
use AlmadenBookster\Learni\PostTypes\Course;
use AlmadenBookster\Learni\PostTypes\Lesson;
use AlmadenBookster\Learni\QuizEditor\QuizEditor;

if ( ! class_exists( __NAMESPACE__ . '\\Module', false ) ) {
	final class Module {
		public static function activate(): void {
			Installer::activate();
			self::register_post_types();
		}

		public static function init(): void {
			add_action( 'plugins_loaded', array( __CLASS__, 'maybe_upgrade' ), 5 );
			add_action( 'init', array( __CLASS__, 'register_post_types' ), 0 );
			add_action( 'init', array( __CLASS__, 'register_dashboard' ), 1 );
			add_action( 'init', array( __CLASS__, 'register_course_editor_handler' ), 2 );
			add_action( 'init', array( __CLASS__, 'register_quiz_editor' ), 5 );
		}

		public static function maybe_upgrade(): void {
			Installer::maybe_upgrade();
		}

		public static function register_post_types(): void {
			Course::register();
			Lesson::register();
		}

		public static function register_dashboard(): void {
			CreatorDashboard::init();
		}

		public static function register_course_editor_handler(): void {
			CourseEditorHandler::init();
		}

		public static function register_quiz_editor(): void {
			QuizEditor::init();
		}
	}
}

Module::init();
