<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function almaden_bookster_filesize_sort_url( $base_url, $current_orderby, $current_order, $target_orderby ) {
	$target_order = 'asc';

	if ( $current_orderby === $target_orderby ) {
		$target_order = ( 'asc' === $current_order ) ? 'desc' : 'asc';
	}

	return add_query_arg(
		array(
			'orderby' => $target_orderby,
			'order'   => $target_order,
		),
		$base_url
	);
}

function almaden_bookster_filesize_sort_indicator( $current_orderby, $current_order, $target_orderby ) {
	if ( $current_orderby !== $target_orderby ) {
		return '';
	}

	return 'asc' === $current_order ? ' ↑' : ' ↓';
}
?>
<div class="wrap almaden-filesize-wrap">
	<div class="almaden-filesize-hero">
		<div>
			<p class="almaden-filesize-kicker">Admin report</p>
			<h1>FileSize - AlmadenBookster</h1>
			<p class="description">Auditoría rápida de archivos del plugin. El reporte muestra el nombre del archivo, el total de líneas y su peso en KB.</p>
		</div>
		<div class="almaden-filesize-pill">
			<span class="dashicons dashicons-chart-bar"></span>
			<span><?php echo esc_html( number_format_i18n( $summary['files'] ) ); ?> archivos</span>
		</div>
	</div>

	<div class="almaden-filesize-stats">
		<div class="almaden-filesize-stat-card">
			<span class="almaden-filesize-stat-label">Archivos analizados</span>
			<strong><?php echo esc_html( number_format_i18n( $summary['files'] ) ); ?></strong>
		</div>
		<div class="almaden-filesize-stat-card">
			<span class="almaden-filesize-stat-label">Líneas totales</span>
			<strong><?php echo esc_html( number_format_i18n( $summary['lines'] ) ); ?></strong>
		</div>
		<div class="almaden-filesize-stat-card">
			<span class="almaden-filesize-stat-label">Peso total</span>
			<strong><?php echo esc_html( number_format_i18n( $summary['kb'], 2 ) ); ?> KB</strong>
		</div>
	</div>

	<div class="almaden-filesize-panel">
		<div class="almaden-filesize-panel-header">
			<h2>Detalle por archivo</h2>
			<p>Ordena por número de líneas o por tamaño del archivo.</p>
		</div>

		<?php if ( empty( $report ) ) : ?>
			<div class="notice notice-warning inline">
				<p>No se encontraron archivos para analizar.</p>
			</div>
		<?php else : ?>
			<div class="almaden-filesize-table-wrap">
				<table class="widefat fixed striped almaden-filesize-table">
					<thead>
						<tr>
							<th scope="col">Archivo</th>
							<th scope="col" class="almaden-filesize-col-number">
								<a href="<?php echo esc_url( almaden_bookster_filesize_sort_url( $base_url, $orderby, $order, 'lines' ) ); ?>">
									Líneas de código<?php echo esc_html( almaden_bookster_filesize_sort_indicator( $orderby, $order, 'lines' ) ); ?>
								</a>
							</th>
							<th scope="col" class="almaden-filesize-col-number">
								<a href="<?php echo esc_url( almaden_bookster_filesize_sort_url( $base_url, $orderby, $order, 'size' ) ); ?>">
									Peso (KB)<?php echo esc_html( almaden_bookster_filesize_sort_indicator( $orderby, $order, 'size' ) ); ?>
								</a>
							</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $report as $item ) : ?>
							<tr>
								<td class="almaden-filesize-file">
									<code><?php echo esc_html( $item['file'] ); ?></code>
								</td>
								<td class="almaden-filesize-col-number"><?php echo esc_html( number_format_i18n( $item['lines'] ) ); ?></td>
								<td class="almaden-filesize-col-number"><?php echo esc_html( number_format_i18n( $item['kb'], 2 ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
	</div>
</div>
