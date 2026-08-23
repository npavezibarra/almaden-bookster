<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'almaden_bookster_dashboard_format_money' ) ) {
	function almaden_bookster_dashboard_format_money( $amount ) {
		$amount = (float) $amount;
		if ( function_exists( 'wc_price' ) ) {
			return wc_price( $amount );
		}

		return esc_html( number_format_i18n( $amount, 2 ) );
	}
}

if ( ! function_exists( 'almaden_bookster_dashboard_format_duration' ) ) {
	function almaden_bookster_dashboard_format_duration( $seconds ) {
		$seconds = max( 0, (int) $seconds );
		if ( $seconds <= 0 ) {
			return '0 min';
		}

		$minutes = (int) round( $seconds / 60 );
		if ( $minutes < 60 ) {
			return sprintf( '%d min', $minutes );
		}

		$hours = floor( $minutes / 60 );
		$remaining_minutes = $minutes % 60;

		return $remaining_minutes > 0 ? sprintf( '%dh %02d', $hours, $remaining_minutes ) : sprintf( '%dh', $hours );
	}
}

if ( ! function_exists( 'almaden_bookster_dashboard_trend_label' ) ) {
	function almaden_bookster_dashboard_trend_label( $datetime ) {
		$timestamp = $datetime ? strtotime( (string) $datetime ) : 0;
		if ( ! $timestamp ) {
			return __( 'Sin actividad', 'almaden-bookster' );
		}

		$days = floor( ( time() - $timestamp ) / DAY_IN_SECONDS );
		if ( $days <= 7 ) {
			return __( 'Activo', 'almaden-bookster' );
		}
		if ( $days <= 30 ) {
			return __( 'Reciente', 'almaden-bookster' );
		}

		return __( 'Estable', 'almaden-bookster' );
	}
}

if ( ! function_exists( 'almaden_bookster_dashboard_get_variation_format' ) ) {
	function almaden_bookster_dashboard_get_variation_format( $product ) {
		if ( ! $product || ! is_callable( array( $product, 'get_attributes' ) ) ) {
			return '';
		}

		$attributes = $product->get_attributes();
		foreach ( array( 'pa_formato', 'formato', 'attribute_pa_formato', 'attribute_formato' ) as $key ) {
			if ( empty( $attributes[ $key ] ) ) {
				continue;
			}

			$value = is_array( $attributes[ $key ] ) ? implode( ' ', $attributes[ $key ] ) : (string) $attributes[ $key ];
			$value = sanitize_title( $value );
			if ( false !== strpos( $value, 'ebook' ) ) {
				return 'ebook';
			}
			if ( false !== strpos( $value, 'fisico' ) || false !== strpos( $value, 'physical' ) ) {
				return 'physical';
			}
			if ( false !== strpos( $value, 'ambos' ) || false !== strpos( $value, 'both' ) ) {
				return 'both';
			}
		}

		return '';
	}
}

if ( ! function_exists( 'almaden_bookster_dashboard_count_course_quizzes' ) ) {
	function almaden_bookster_dashboard_count_course_quizzes( $course_id ) {
		global $wpdb;

		$course_id = absint( $course_id );
		if ( $course_id <= 0 || ! function_exists( 'almaden_bookster_table_exists' ) ) {
			return 0;
		}

		$table = $wpdb->prefix . 'almaden_learni_quizzes';
		if ( almaden_bookster_table_exists( $table ) ) {
			return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(1) FROM {$table} WHERE course_post_id = %d", $course_id ) );
		}

		$legacy_table = $wpdb->prefix . 'learni_quizzes';
		if ( almaden_bookster_table_exists( $legacy_table ) ) {
			return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(1) FROM {$legacy_table} WHERE course_post_id = %d", $course_id ) );
		}

		return 0;
	}
}

if ( ! function_exists( 'almaden_bookster_dashboard_get_engagement_report' ) ) {
	function almaden_bookster_dashboard_get_engagement_report() {
		global $wpdb;

		$report = array(
			'kpis' => array(
				'readings_started'      => 0,
				'ebooks_completed'      => 0,
				'courses_started'       => 0,
				'courses_completed'     => 0,
				'avg_reading_seconds'   => 0,
				'avg_study_seconds'     => 0,
			),
			'ebooks'  => array(),
			'courses' => array(),
		);

		$book_activity = array();
		$course_activity = array();
		$book_user_activity = array();
		$course_user_activity = array();
		$book_passed_quizzes = array();
		$course_passed_quizzes = array();

		$ensure_item = static function( &$collection, $post_id, $fallback_prefix ) {
			$post_id = absint( $post_id );
			if ( $post_id <= 0 ) {
				return;
			}

			if ( ! isset( $collection[ $post_id ] ) ) {
				$title = get_the_title( $post_id );
				$collection[ $post_id ] = array(
					'id'           => $post_id,
					'title'        => $title ? $title : sprintf( '%s #%d', $fallback_prefix, $post_id ),
					'interactions' => 0,
					'completed'    => 0,
					'latest_at'    => '',
				);
			}
		};

		$register_latest = static function( &$collection, $post_id, $created_at ) {
			$post_id = absint( $post_id );
			$created_at = (string) $created_at;
			if ( $post_id <= 0 || empty( $collection[ $post_id ] ) || '' === $created_at ) {
				return;
			}

			$current = (string) $collection[ $post_id ]['latest_at'];
			if ( '' === $current || strtotime( $created_at ) > strtotime( $current ) ) {
				$collection[ $post_id ]['latest_at'] = $created_at;
			}
		};

		$highlight_table = function_exists( 'almaden_bookster_get_highlights_table_name' ) ? almaden_bookster_get_highlights_table_name() : '';
		if ( '' !== $highlight_table && function_exists( 'almaden_bookster_table_exists' ) && almaden_bookster_table_exists( $highlight_table ) ) {
			$highlight_rows = $wpdb->get_results( "SELECT user_id, book_id, created_at FROM {$highlight_table} WHERE status = 'active'", ARRAY_A );
			foreach ( is_array( $highlight_rows ) ? $highlight_rows : array() as $row ) {
				$book_id = isset( $row['book_id'] ) ? absint( $row['book_id'] ) : 0;
				$user_id = isset( $row['user_id'] ) ? absint( $row['user_id'] ) : 0;
				if ( $book_id <= 0 || 'almaden-books' !== get_post_type( $book_id ) ) {
					continue;
				}

				$ensure_item( $book_activity, $book_id, 'Ebook' );
				$book_activity[ $book_id ]['interactions']++;
				$register_latest( $book_activity, $book_id, $row['created_at'] ?? '' );
				if ( $user_id > 0 ) {
					$book_user_activity[ $book_id . ':' . $user_id ] = true;
				}
			}
		}

		$attempt_table = function_exists( 'almaden_bookster_get_quiz_attempts_table_name' ) ? almaden_bookster_get_quiz_attempts_table_name() : '';
		if ( '' !== $attempt_table && function_exists( 'almaden_bookster_table_exists' ) && almaden_bookster_table_exists( $attempt_table ) ) {
			$attempt_rows = $wpdb->get_results( "SELECT user_id, book_id, quiz_id, passed, created_at FROM {$attempt_table}", ARRAY_A );
			foreach ( is_array( $attempt_rows ) ? $attempt_rows : array() as $row ) {
				$post_id = isset( $row['book_id'] ) ? absint( $row['book_id'] ) : 0;
				$user_id = isset( $row['user_id'] ) ? absint( $row['user_id'] ) : 0;
				$quiz_id = isset( $row['quiz_id'] ) ? absint( $row['quiz_id'] ) : 0;
				$post_type = $post_id > 0 ? get_post_type( $post_id ) : '';

				if ( 'almaden-books' === $post_type ) {
					$ensure_item( $book_activity, $post_id, 'Ebook' );
					$book_activity[ $post_id ]['interactions']++;
					$register_latest( $book_activity, $post_id, $row['created_at'] ?? '' );
					if ( $user_id > 0 ) {
						$book_user_activity[ $post_id . ':' . $user_id ] = true;
					}
					if ( ! empty( $row['passed'] ) && $user_id > 0 && $quiz_id > 0 ) {
						$book_passed_quizzes[ $post_id ][ $user_id ][ $quiz_id ] = true;
					}
				} elseif ( 'almdn_learni_course' === $post_type ) {
					$ensure_item( $course_activity, $post_id, 'Curso' );
					$course_activity[ $post_id ]['interactions']++;
					$register_latest( $course_activity, $post_id, $row['created_at'] ?? '' );
					if ( $user_id > 0 ) {
						$course_user_activity[ $post_id . ':' . $user_id ] = true;
					}
					if ( ! empty( $row['passed'] ) && $user_id > 0 && $quiz_id > 0 ) {
						$course_passed_quizzes[ $post_id ][ $user_id ][ $quiz_id ] = true;
					}
				}
			}
		}

		foreach ( $book_passed_quizzes as $book_id => $user_quizzes ) {
			$total_quizzes = function_exists( 'almaden_bookster_get_book_quiz_entries' ) ? count( almaden_bookster_get_book_quiz_entries( $book_id ) ) : 0;
			if ( $total_quizzes <= 0 ) {
				continue;
			}
			foreach ( $user_quizzes as $quiz_ids ) {
				if ( count( $quiz_ids ) >= $total_quizzes ) {
					$report['kpis']['ebooks_completed']++;
					if ( isset( $book_activity[ $book_id ] ) ) {
						$book_activity[ $book_id ]['completed']++;
					}
				}
			}
		}

		foreach ( $course_passed_quizzes as $course_id => $user_quizzes ) {
			$total_quizzes = almaden_bookster_dashboard_count_course_quizzes( $course_id );
			if ( $total_quizzes <= 0 ) {
				continue;
			}
			foreach ( $user_quizzes as $quiz_ids ) {
				if ( count( $quiz_ids ) >= $total_quizzes ) {
					$report['kpis']['courses_completed']++;
					if ( isset( $course_activity[ $course_id ] ) ) {
						$course_activity[ $course_id ]['completed']++;
					}
				}
			}
		}

		$report['kpis']['readings_started'] = count( $book_user_activity );
		$report['kpis']['courses_started'] = count( $course_user_activity );

		$sort_activity = static function( $a, $b ) {
			if ( (int) $a['interactions'] === (int) $b['interactions'] ) {
				return strtotime( (string) $b['latest_at'] ) <=> strtotime( (string) $a['latest_at'] );
			}
			return (int) $b['interactions'] <=> (int) $a['interactions'];
		};

		usort( $book_activity, $sort_activity );
		usort( $course_activity, $sort_activity );

		$report['ebooks'] = array_slice( $book_activity, 0, 8 );
		$report['courses'] = array_slice( $course_activity, 0, 8 );

		return $report;
	}
}

if ( ! function_exists( 'almaden_bookster_dashboard_get_product_type' ) ) {
	function almaden_bookster_dashboard_get_product_type( $product ) {
		if ( ! $product ) {
			return array( 'key' => 'unknown', 'label' => __( 'Producto', 'almaden-bookster' ) );
		}

		$product_id = absint( $product->get_id() );
		$parent_id = is_callable( array( $product, 'get_parent_id' ) ) ? absint( $product->get_parent_id() ) : 0;
		$ids = array_values( array_unique( array_filter( array( $product_id, $parent_id ) ) ) );

		foreach ( $ids as $id ) {
			$course_id = absint( get_post_meta( $id, '_almaden_learni_course_id', true ) );
			$course_id = $course_id ?: absint( get_post_meta( $id, 'almaden_learni_course_id', true ) );
			$course_id = $course_id ?: absint( get_post_meta( $id, '_almaden_course_id', true ) );
			if ( $course_id ) {
				return array( 'key' => 'course', 'label' => __( 'Curso', 'almaden-bookster' ) );
			}
		}

		$format = almaden_bookster_dashboard_get_variation_format( $product );
		if ( 'ebook' === $format ) {
			return array( 'key' => 'ebook', 'label' => __( 'Libro ebook', 'almaden-bookster' ) );
		}
		if ( 'physical' === $format ) {
			return array( 'key' => 'physical', 'label' => __( 'Libro físico', 'almaden-bookster' ) );
		}
		if ( 'both' === $format ) {
			return array( 'key' => 'both', 'label' => __( 'Libro físico + ebook', 'almaden-bookster' ) );
		}

		foreach ( $ids as $id ) {
			$book_id = absint( get_post_meta( $id, '_almaden_book_product_book_id', true ) );
			$book_id = $book_id ?: absint( get_post_meta( $id, '_almaden_book_id', true ) );
			if ( $book_id ) {
				if ( is_callable( array( $product, 'is_virtual' ) ) && $product->is_virtual() ) {
					return array( 'key' => 'ebook', 'label' => __( 'Libro ebook', 'almaden-bookster' ) );
				}

				return array( 'key' => 'physical', 'label' => __( 'Libro físico', 'almaden-bookster' ) );
			}
		}

		if ( 'almdn_learni_course' === get_post_type( $product_id ) ) {
			return array( 'key' => 'course', 'label' => __( 'Curso', 'almaden-bookster' ) );
		}

		return array( 'key' => 'unknown', 'label' => __( 'Producto', 'almaden-bookster' ) );
	}
}

if ( ! function_exists( 'almaden_bookster_dashboard_get_sales_report' ) ) {
	function almaden_bookster_dashboard_get_sales_report() {
		$report = array(
			'available' => function_exists( 'wc_get_orders' ),
			'totals'    => array(
				'all'      => 0.0,
				'ebook'    => 0.0,
				'physical' => 0.0,
				'course'   => 0.0,
			),
			'rows'      => array(),
		);

		if ( ! $report['available'] ) {
			return $report;
		}

		$orders = wc_get_orders(
			array(
				'limit'   => 200,
				'orderby' => 'date',
				'order'   => 'DESC',
				'status'  => array_keys( wc_get_order_statuses() ),
			)
		);

		foreach ( $orders as $order ) {
			if ( ! $order || ! is_callable( array( $order, 'get_items' ) ) ) {
				continue;
			}

			$is_paid = is_callable( array( $order, 'is_paid' ) ) ? $order->is_paid() : false;
			foreach ( $order->get_items( 'line_item' ) as $item ) {
				$product = is_callable( array( $item, 'get_product' ) ) ? $item->get_product() : null;
				$type = almaden_bookster_dashboard_get_product_type( $product );
				if ( 'unknown' === $type['key'] ) {
					continue;
				}

				$line_total = (float) $item->get_total();
				if ( $is_paid ) {
					$report['totals']['all'] += $line_total;
					if ( isset( $report['totals'][ $type['key'] ] ) ) {
						$report['totals'][ $type['key'] ] += $line_total;
					}
				}

				$report['rows'][] = array(
					'order_number' => $order->get_order_number(),
					'product_name' => $item->get_name(),
					'type_label'   => $type['label'],
					'price'        => $line_total,
					'email'        => $order->get_billing_email(),
					'status'       => wc_get_order_status_name( $order->get_status() ),
				);
			}
		}

		return $report;
	}
}

$sales_report = almaden_bookster_dashboard_get_sales_report();
$sales_totals = $sales_report['totals'];
$engagement_report = almaden_bookster_dashboard_get_engagement_report();
$engagement_kpis = $engagement_report['kpis'];
$dashboard_view = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : 'sales';
if ( ! in_array( $dashboard_view, array( 'sales', 'engagement' ), true ) ) {
	$dashboard_view = 'sales';
}
$dashboard_url = function_exists( 'almaden_bookster_get_dashboard_page_url' ) ? almaden_bookster_get_dashboard_page_url() : home_url( '/dashboard/' );
$sales_url = remove_query_arg( 'view', $dashboard_url );
$engagement_url = add_query_arg( 'view', 'engagement', $dashboard_url );

almaden_bookster_render_app_shell_start(
	array(
		'title'          => almaden_bookster_get_dashboard_title() . ' - Almaden',
		'body_id'        => 'almaden-dashboard-app-body',
		'active_nav_key' => 'dashboard',
	)
);
?>
<main id="almaden-dashboard-page" class="almaden-app-content-shell flex-1 pb-16" style="min-height: 60vh;">
	<nav class="mb-6 border-b border-gray-200" aria-label="<?php esc_attr_e( 'Submenú del dashboard', 'almaden-bookster' ); ?>">
		<div class="flex items-center gap-2 overflow-x-auto">
			<a href="<?php echo esc_url( $sales_url ); ?>" class="<?php echo esc_attr( 'border-b-2 px-1 pb-3 text-sm font-semibold transition ' . ( 'sales' === $dashboard_view ? 'border-black text-black' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-black' ) ); ?>">
				<?php esc_html_e( 'Ventas', 'almaden-bookster' ); ?>
			</a>
			<a href="<?php echo esc_url( $engagement_url ); ?>" class="<?php echo esc_attr( 'border-b-2 px-1 pb-3 text-sm font-semibold transition ' . ( 'engagement' === $dashboard_view ? 'border-black text-black' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-black' ) ); ?>">
				<?php esc_html_e( 'Engagement', 'almaden-bookster' ); ?>
			</a>
		</div>
	</nav>

	<?php if ( 'sales' === $dashboard_view ) : ?>
	<section id="almaden-dashboard-sales" class="space-y-6">
		<div>
			<p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-400"><?php esc_html_e( 'Dashboard', 'almaden-bookster' ); ?></p>
			<h1 class="mt-2 text-3xl font-semibold tracking-tight text-gray-950"><?php esc_html_e( 'Ventas', 'almaden-bookster' ); ?></h1>
		</div>

		<?php if ( ! $sales_report['available'] ) : ?>
			<div class="rounded-lg border border-amber-200 bg-amber-50 px-5 py-4 text-sm font-medium text-amber-900">
				<?php esc_html_e( 'WooCommerce no está disponible, por eso no se pueden leer ventas todavía.', 'almaden-bookster' ); ?>
			</div>
		<?php endif; ?>

		<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
			<?php
			$cards = array(
				array( 'label' => __( 'Total ventas productos', 'almaden-bookster' ), 'value' => $sales_totals['all'] ),
				array( 'label' => __( 'Ventas ebooks', 'almaden-bookster' ), 'value' => $sales_totals['ebook'] ),
				array( 'label' => __( 'Ventas físico', 'almaden-bookster' ), 'value' => $sales_totals['physical'] ),
				array( 'label' => __( 'Ventas cursos', 'almaden-bookster' ), 'value' => $sales_totals['course'] ),
			);
			?>
			<?php foreach ( $cards as $card ) : ?>
				<div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
					<p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400"><?php echo esc_html( $card['label'] ); ?></p>
					<p class="mt-3 text-3xl font-semibold tracking-tight text-gray-950"><?php echo wp_kses_post( almaden_bookster_dashboard_format_money( $card['value'] ) ); ?></p>
				</div>
			<?php endforeach; ?>
		</div>

		<div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
			<div class="border-b border-gray-200 px-5 py-4">
				<h2 class="text-base font-semibold text-gray-950"><?php esc_html_e( 'Órdenes recientes', 'almaden-bookster' ); ?></h2>
			</div>
			<div class="overflow-x-auto">
				<table class="min-w-full divide-y divide-gray-200 text-sm">
					<thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-[0.16em] text-gray-500">
						<tr>
							<th scope="col" class="px-5 py-3"><?php esc_html_e( 'Orden', 'almaden-bookster' ); ?></th>
							<th scope="col" class="px-5 py-3"><?php esc_html_e( 'Producto', 'almaden-bookster' ); ?></th>
							<th scope="col" class="px-5 py-3"><?php esc_html_e( 'Tipo', 'almaden-bookster' ); ?></th>
							<th scope="col" class="px-5 py-3"><?php esc_html_e( 'Precio', 'almaden-bookster' ); ?></th>
							<th scope="col" class="px-5 py-3"><?php esc_html_e( 'Email cliente', 'almaden-bookster' ); ?></th>
							<th scope="col" class="px-5 py-3"><?php esc_html_e( 'Estado', 'almaden-bookster' ); ?></th>
						</tr>
					</thead>
					<tbody class="divide-y divide-gray-100 bg-white">
						<?php if ( ! empty( $sales_report['rows'] ) ) : ?>
							<?php foreach ( $sales_report['rows'] as $row ) : ?>
								<tr class="text-gray-700">
									<td class="whitespace-nowrap px-5 py-4 font-semibold text-gray-950">#<?php echo esc_html( $row['order_number'] ); ?></td>
									<td class="min-w-[14rem] px-5 py-4"><?php echo esc_html( $row['product_name'] ); ?></td>
									<td class="whitespace-nowrap px-5 py-4"><?php echo esc_html( $row['type_label'] ); ?></td>
									<td class="whitespace-nowrap px-5 py-4 font-semibold text-gray-950"><?php echo wp_kses_post( almaden_bookster_dashboard_format_money( $row['price'] ) ); ?></td>
									<td class="whitespace-nowrap px-5 py-4"><?php echo esc_html( $row['email'] ); ?></td>
									<td class="whitespace-nowrap px-5 py-4"><?php echo esc_html( $row['status'] ); ?></td>
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr>
								<td colspan="6" class="px-5 py-10 text-center text-sm text-gray-500">
									<?php esc_html_e( 'Todavía no hay órdenes de libros o cursos para mostrar.', 'almaden-bookster' ); ?>
								</td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
	</section>
	<?php endif; ?>

	<?php if ( 'engagement' === $dashboard_view ) : ?>
	<section id="almaden-dashboard-engagement" class="space-y-6">
		<div>
			<p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-400"><?php esc_html_e( 'Audiencia y contenido', 'almaden-bookster' ); ?></p>
			<h1 class="mt-2 text-3xl font-semibold tracking-tight text-gray-950"><?php esc_html_e( 'Engagement', 'almaden-bookster' ); ?></h1>
			<p class="mt-2 max-w-2xl text-sm leading-6 text-gray-500">
				<?php esc_html_e( 'Interacciones de lectura y estudio sin datos financieros.', 'almaden-bookster' ); ?>
			</p>
		</div>

		<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
			<?php
			$engagement_cards = array(
				array( 'label' => __( 'Lecturas iniciadas', 'almaden-bookster' ), 'value' => number_format_i18n( (int) $engagement_kpis['readings_started'] ) ),
				array( 'label' => __( 'Ebooks completados', 'almaden-bookster' ), 'value' => number_format_i18n( (int) $engagement_kpis['ebooks_completed'] ) ),
				array( 'label' => __( 'Cursos iniciados', 'almaden-bookster' ), 'value' => number_format_i18n( (int) $engagement_kpis['courses_started'] ) ),
				array( 'label' => __( 'Cursos completados', 'almaden-bookster' ), 'value' => number_format_i18n( (int) $engagement_kpis['courses_completed'] ) ),
				array( 'label' => __( 'Tiempo prom. lectura', 'almaden-bookster' ), 'value' => almaden_bookster_dashboard_format_duration( $engagement_kpis['avg_reading_seconds'] ) ),
				array( 'label' => __( 'Tiempo prom. estudio', 'almaden-bookster' ), 'value' => almaden_bookster_dashboard_format_duration( $engagement_kpis['avg_study_seconds'] ) ),
			);
			?>
			<?php foreach ( $engagement_cards as $card ) : ?>
				<div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
					<p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400"><?php echo esc_html( $card['label'] ); ?></p>
					<p class="mt-3 text-3xl font-semibold tracking-tight text-gray-950"><?php echo esc_html( $card['value'] ); ?></p>
				</div>
			<?php endforeach; ?>
		</div>

		<div class="grid gap-6 xl:grid-cols-2">
			<div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
				<div class="border-b border-gray-200 px-5 py-4">
					<h2 class="text-base font-semibold text-gray-950"><?php esc_html_e( 'Ranking de ebooks', 'almaden-bookster' ); ?></h2>
				</div>
				<div class="overflow-x-auto">
					<table class="min-w-full divide-y divide-gray-200 text-sm">
						<thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-[0.16em] text-gray-500">
							<tr>
								<th scope="col" class="px-5 py-3"><?php esc_html_e( 'Ebook', 'almaden-bookster' ); ?></th>
								<th scope="col" class="px-5 py-3"><?php esc_html_e( 'Consumo', 'almaden-bookster' ); ?></th>
								<th scope="col" class="px-5 py-3"><?php esc_html_e( 'Tendencia', 'almaden-bookster' ); ?></th>
							</tr>
						</thead>
						<tbody class="divide-y divide-gray-100 bg-white">
							<?php if ( ! empty( $engagement_report['ebooks'] ) ) : ?>
								<?php foreach ( $engagement_report['ebooks'] as $ebook ) : ?>
									<tr class="text-gray-700">
										<td class="min-w-[14rem] px-5 py-4 font-semibold text-gray-950"><?php echo esc_html( $ebook['title'] ); ?></td>
										<td class="whitespace-nowrap px-5 py-4">
											<?php
											echo esc_html(
												sprintf(
													/* translators: 1: interactions count, 2: completed count. */
													__( '%1$s interacciones / %2$s completados', 'almaden-bookster' ),
													number_format_i18n( (int) $ebook['interactions'] ),
													number_format_i18n( (int) $ebook['completed'] )
												)
											);
											?>
										</td>
										<td class="whitespace-nowrap px-5 py-4"><?php echo esc_html( almaden_bookster_dashboard_trend_label( $ebook['latest_at'] ) ); ?></td>
									</tr>
								<?php endforeach; ?>
							<?php else : ?>
								<tr>
									<td colspan="3" class="px-5 py-10 text-center text-sm text-gray-500">
										<?php esc_html_e( 'Todavía no hay consumo de ebooks para mostrar.', 'almaden-bookster' ); ?>
									</td>
								</tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>

			<div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
				<div class="border-b border-gray-200 px-5 py-4">
					<h2 class="text-base font-semibold text-gray-950"><?php esc_html_e( 'Ranking de cursos', 'almaden-bookster' ); ?></h2>
				</div>
				<div class="overflow-x-auto">
					<table class="min-w-full divide-y divide-gray-200 text-sm">
						<thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-[0.16em] text-gray-500">
							<tr>
								<th scope="col" class="px-5 py-3"><?php esc_html_e( 'Curso', 'almaden-bookster' ); ?></th>
								<th scope="col" class="px-5 py-3"><?php esc_html_e( 'Consumo', 'almaden-bookster' ); ?></th>
								<th scope="col" class="px-5 py-3"><?php esc_html_e( 'Tendencia', 'almaden-bookster' ); ?></th>
							</tr>
						</thead>
						<tbody class="divide-y divide-gray-100 bg-white">
							<?php if ( ! empty( $engagement_report['courses'] ) ) : ?>
								<?php foreach ( $engagement_report['courses'] as $course ) : ?>
									<tr class="text-gray-700">
										<td class="min-w-[14rem] px-5 py-4 font-semibold text-gray-950"><?php echo esc_html( $course['title'] ); ?></td>
										<td class="whitespace-nowrap px-5 py-4">
											<?php
											echo esc_html(
												sprintf(
													/* translators: 1: interactions count, 2: completed count. */
													__( '%1$s interacciones / %2$s completados', 'almaden-bookster' ),
													number_format_i18n( (int) $course['interactions'] ),
													number_format_i18n( (int) $course['completed'] )
												)
											);
											?>
										</td>
										<td class="whitespace-nowrap px-5 py-4"><?php echo esc_html( almaden_bookster_dashboard_trend_label( $course['latest_at'] ) ); ?></td>
									</tr>
								<?php endforeach; ?>
							<?php else : ?>
								<tr>
									<td colspan="3" class="px-5 py-10 text-center text-sm text-gray-500">
										<?php esc_html_e( 'Todavía no hay consumo de cursos para mostrar.', 'almaden-bookster' ); ?>
									</td>
								</tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</section>
	<?php endif; ?>
</main>
<?php
almaden_bookster_render_app_shell_end();
