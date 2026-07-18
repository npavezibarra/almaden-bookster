<?php

namespace AlmadenBookster\Learni\QuizEditor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Permissions {
	public static function can_access( int $course_id = 0, int $quiz_id = 0 ): bool {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		if ( current_user_can( 'manage_options' ) || current_user_can( 'manage_almaden_learni' ) ) {
			return true;
		}

		$user_id = get_current_user_id();

		if ( $quiz_id > 0 ) {
			$course_id = $course_id > 0 ? $course_id : QuizRepository::get_course_id_by_quiz_id( $quiz_id );
		}

		if ( $course_id > 0 ) {
			$author_id = (int) get_post_field( 'post_author', $course_id );
			if ( $author_id === $user_id ) {
				return true;
			}
		}

		return false;
	}
}

