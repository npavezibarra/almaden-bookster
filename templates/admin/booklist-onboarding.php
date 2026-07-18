<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$checklist_items = function_exists( 'almaden_bookster_get_publisher_tour_checklist_items' ) ? almaden_bookster_get_publisher_tour_checklist_items() : array();
$quick_actions   = function_exists( 'almaden_bookster_get_publisher_tour_quick_actions' ) ? almaden_bookster_get_publisher_tour_quick_actions() : array();
?>
<style id="almaden-booklist-onboarding-style">
	#almaden-booklist-onboarding {
		position: relative;
		overflow: hidden;
		margin: 0 0 1.5rem;
		padding: 1.5rem;
		border: 1px solid #e5e7eb;
		border-radius: 1.25rem;
		background:
			radial-gradient(circle at top right, rgba(34, 197, 94, 0.15), transparent 34%),
			linear-gradient(135deg, #0f172a 0%, #111827 50%, #1f2937 100%);
		color: #fff;
		box-shadow: 0 22px 50px rgba(15, 23, 42, 0.18);
	}
	#almaden-booklist-onboarding .almaden-onboarding-grid {
		display: grid;
		grid-template-columns: minmax(0, 1.1fr) minmax(280px, 0.9fr);
		gap: 1.25rem;
		align-items: start;
	}
	#almaden-booklist-onboarding .almaden-onboarding-badge {
		display: inline-flex;
		align-items: center;
		gap: 0.4rem;
		padding: 0.4rem 0.75rem;
		border-radius: 999px;
		background: rgba(255, 255, 255, 0.12);
		color: rgba(255, 255, 255, 0.88);
		font-size: 0.75rem;
		font-weight: 800;
		letter-spacing: 0.08em;
		text-transform: uppercase;
	}
	#almaden-booklist-onboarding h2 {
		margin: 0.9rem 0 0;
		font-size: clamp(1.8rem, 3vw, 2.6rem);
		line-height: 1.02;
		letter-spacing: -0.04em;
	}
	#almaden-booklist-onboarding p {
		margin: 0.85rem 0 0;
		max-width: 60ch;
		color: rgba(255, 255, 255, 0.8);
		line-height: 1.6;
	}
	#almaden-booklist-onboarding .almaden-onboarding-actions,
	#almaden-booklist-onboarding .almaden-onboarding-footer {
		display: flex;
		flex-wrap: wrap;
		gap: 0.75rem;
		align-items: center;
	}
	#almaden-booklist-onboarding .almaden-onboarding-actions {
		margin-top: 1.2rem;
	}
	#almaden-booklist-onboarding .almaden-onboarding-footer {
		margin-top: 1rem;
		justify-content: space-between;
	}
	#almaden-booklist-onboarding .almaden-onboarding-card {
		padding: 1rem;
		border-radius: 1rem;
		background: rgba(255, 255, 255, 0.08);
		border: 1px solid rgba(255, 255, 255, 0.1);
	}
	#almaden-booklist-onboarding .almaden-onboarding-list {
		display: grid;
		gap: 0.75rem;
		margin: 0;
		padding: 0;
		list-style: none;
	}
	#almaden-booklist-onboarding .almaden-onboarding-list li {
		padding: 0.85rem 0.95rem;
		border-radius: 0.95rem;
		background: rgba(255, 255, 255, 0.08);
		border: 1px solid rgba(255, 255, 255, 0.08);
	}
	#almaden-booklist-onboarding .almaden-onboarding-list strong {
		display: block;
		margin-bottom: 0.2rem;
		font-size: 0.95rem;
	}
	#almaden-booklist-onboarding .almaden-onboarding-list span {
		color: rgba(255, 255, 255, 0.75);
		font-size: 0.88rem;
		line-height: 1.45;
	}
	#almaden-booklist-onboarding .almaden-btn {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		padding: 0.9rem 1.1rem;
		border-radius: 999px;
		font-size: 0.9rem;
		font-weight: 800;
		text-decoration: none;
		border: 0;
		cursor: pointer;
	}
	#almaden-booklist-onboarding .almaden-btn--primary {
		background: #ffffff;
		color: #111827;
	}
	#almaden-booklist-onboarding .almaden-btn--ghost {
		background: rgba(255, 255, 255, 0.08);
		color: #fff;
		border: 1px solid rgba(255, 255, 255, 0.15);
	}
	#almaden-booklist-onboarding .almaden-checklist {
		display: grid;
		gap: 0.7rem;
	}
	#almaden-booklist-onboarding .almaden-checklist-item {
		display: flex;
		gap: 0.75rem;
		align-items: flex-start;
		padding: 0.85rem 0.95rem;
		border-radius: 0.95rem;
		background: rgba(15, 23, 42, 0.48);
		border: 1px solid rgba(255, 255, 255, 0.08);
	}
	#almaden-booklist-onboarding .almaden-checklist-item .dot {
		flex: 0 0 auto;
		width: 0.8rem;
		height: 0.8rem;
		margin-top: 0.35rem;
		border-radius: 999px;
		background: #22c55e;
		box-shadow: 0 0 0 6px rgba(34, 197, 94, 0.12);
	}
	#almaden-booklist-onboarding .almaden-muted {
		font-size: 0.84rem;
		color: rgba(255, 255, 255, 0.65);
	}
	@media (max-width: 980px) {
		#almaden-booklist-onboarding .almaden-onboarding-grid {
			grid-template-columns: 1fr;
		}
	}
</style>

<section id="almaden-booklist-onboarding">
	<div class="almaden-onboarding-grid">
		<div>
			<span class="almaden-onboarding-badge"><?php esc_html_e( 'Onboarding editorial', 'almaden-bookster' ); ?></span>
			<h2><?php esc_html_e( 'Bienvenido al taller. Vamos a crear tu primer libro.', 'almaden-bookster' ); ?></h2>
			<p><?php esc_html_e( 'Te dejamos un camino corto y guiado para que no tengas que adivinar qué hacer primero. Empieza por el libro inicial, luego ajusta portada y contenido, y finalmente publica o importa tu catálogo.', 'almaden-bookster' ); ?></p>

			<div class="almaden-onboarding-actions">
				<a href="#" class="almaden-btn almaden-btn--primary" onclick="var btn=document.getElementById('open-modal-btn'); if (btn) { btn.click(); } return false;"><?php esc_html_e( 'Crear mi primer libro', 'almaden-bookster' ); ?></a>
				<a href="#booklist-empty-state" class="almaden-btn almaden-btn--ghost"><?php esc_html_e( 'Ver el taller', 'almaden-bookster' ); ?></a>
			</div>

			<div class="almaden-onboarding-footer">
				<div class="almaden-muted"><?php esc_html_e( 'Esto aparece solo durante la activación inicial de tu cuenta.', 'almaden-bookster' ); ?></div>
				<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST" style="margin: 0;">
					<input type="hidden" name="action" value="almaden_complete_publisher_tour">
					<?php wp_nonce_field( 'almaden_publisher_tour_complete', 'almaden_publisher_tour_nonce' ); ?>
					<button type="submit" class="almaden-btn almaden-btn--ghost"><?php esc_html_e( 'Marcar como completado', 'almaden-bookster' ); ?></button>
				</form>
			</div>
		</div>

		<div class="almaden-onboarding-card">
			<h3 style="margin: 0 0 0.9rem;"><?php esc_html_e( 'Checklist inicial', 'almaden-bookster' ); ?></h3>
			<div class="almaden-checklist">
				<?php foreach ( $checklist_items as $item ) : ?>
					<div class="almaden-checklist-item">
						<span class="dot" aria-hidden="true"></span>
						<div>
							<strong><?php echo esc_html( $item['title'] ); ?></strong>
							<span><?php echo esc_html( $item['description'] ); ?></span>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<div style="margin-top: 1rem;" class="almaden-muted"><?php esc_html_e( 'Tip: si ya tienes un libro listo para cargar, usa el modal de creación para no perder el contexto del taller.', 'almaden-bookster' ); ?></div>
		</div>
	</div>
</section>
