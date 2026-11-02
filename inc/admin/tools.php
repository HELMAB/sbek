<?php
/**
 * Sbek Theme tools: demo content import/removal buttons and "reset design".
 *
 * Demo content runs in batches through admin-ajax.php (action `sbek_demo`):
 * the browser asks for `start`, then calls `next` until the job reports done,
 * showing a progress bar. Every request checks a nonce and manage_options,
 * and the job itself lives server-side (Sbek_Demo_Importer), so the client
 * can only say "continue" — it never supplies what to create or delete.
 *
 * @package sbek
 */

defined( 'ABSPATH' ) || exit;

const SBEK_DEMO_NONCE         = 'sbek_demo';
const SBEK_RESET_DESIGN_NONCE = 'sbek_reset_design';

// ─── Demo content (AJAX) ───────────────────────────────────────────────────────

function sbek_ajax_demo(): void {
	check_ajax_referer( SBEK_DEMO_NONCE );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'You do not have permission to do this.', 'sbek' ) ), 403 );
	}

	$task = isset( $_POST['task'] ) ? sanitize_key( wp_unslash( $_POST['task'] ) ) : '';
	$type = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';

	$importer = new Sbek_Demo_Importer();

	try {
		switch ( $task ) {
			case 'start':
				if ( ! in_array( $type, array( 'import', 'reset' ), true ) ) {
					throw new RuntimeException( 'Invalid request.' );
				}
				// "Remove" also clears an interrupted import before removing what it created.
				if ( 'reset' === $type && 'running' === Sbek_Demo_Importer::status()['state'] ) {
					$importer->abort();
				}
				$importer->start( $type, array( 'site_setup' => ! empty( $_POST['site_setup'] ) ) );
				wp_send_json_success(
					array(
						'done'     => false,
						'progress' => 0,
						'message'  => 'import' === $type ? __( 'Starting import…', 'sbek' ) : __( 'Starting removal…', 'sbek' ),
					)
				);
				break;

			case 'next':
				wp_send_json_success( $importer->run_batch() );
				break;

			case 'abort':
				$importer->abort();
				wp_send_json_success(
					array(
						'done'     => true,
						'progress' => 0,
						'message'  => __( 'Job cancelled. Anything already created can still be removed.', 'sbek' ),
					)
				);
				break;

			default:
				throw new RuntimeException( 'Invalid request.' );
		}
	} catch ( Throwable $e ) {
		wp_send_json_error( array( 'message' => $e->getMessage() ), 400 );
	}
}
add_action( 'wp_ajax_sbek_demo', 'sbek_ajax_demo' );

// ─── Reset design ──────────────────────────────────────────────────────────────

/**
 * Restore the Design tab (colors + fonts) to the theme defaults. Nothing else
 * in Sbek Theme is touched.
 */
function sbek_reset_design(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to do this.', 'sbek' ), 403 );
	}

	check_admin_referer( SBEK_RESET_DESIGN_NONCE );

	$settings = sbek_site_setting();
	$defaults = sbek_site_settings_defaults();

	foreach ( array( 'color_brand', 'color_top', 'color_footer', 'font_body', 'font_heading', 'font_latin' ) as $key ) {
		$settings[ $key ] = $defaults[ $key ];
	}

	update_option( SBEK_SETTINGS_OPTION, $settings );

	wp_safe_redirect( add_query_arg( array( 'page' => SBEK_SETTINGS_SLUG, 'design_reset' => 1 ), admin_url( 'admin.php' ) ) . '#design' );
	exit;
}
add_action( 'admin_post_sbek_reset_design', 'sbek_reset_design' );

/**
 * Whether the design differs from the defaults (to enable the reset button).
 */
function sbek_design_is_default(): bool {
	$design = sbek_design();

	return SBEK_BRAND_COLOR === $design['brand']
		&& SBEK_TOPBAR_COLOR === $design['top']
		&& SBEK_FOOTER_COLOR === $design['footer']
		&& 'battambang' === $design['font_body']
		&& 'koulen' === $design['font_heading']
		&& 'arial' === $design['font_latin'];
}

// ─── UI ────────────────────────────────────────────────────────────────────────

/**
 * Inline two-step confirmation ("Are you sure? Yes / Cancel") — no blocking
 * browser dialog. The trigger reveals the prompt; the real action button sits
 * inside it.
 *
 * @param string $trigger_label Label of the first button.
 * @param string $question      Confirmation question.
 * @param string $confirm_html  Trusted markup of the confirming button.
 * @param bool   $disabled      Disable the trigger.
 * @param string $dirty_note    Extra warning shown only while the page has unsaved changes.
 */
function sbek_confirm_control( string $trigger_label, string $question, string $confirm_html, bool $disabled = false, string $dirty_note = '' ): void {
	?>
	<div class="sbek-confirm" data-sbek-confirm>
		<button type="button" class="sbek-btn sbek-btn--danger-ghost" data-sbek-confirm-open<?php disabled( $disabled ); ?>><?php echo esc_html( $trigger_label ); ?></button>
		<div class="sbek-confirm__prompt" data-sbek-confirm-prompt hidden>
			<span class="sbek-confirm__question"><?php echo esc_html( $question ); ?></span>
			<?php if ( $dirty_note ) : ?>
				<span class="sbek-confirm__note" data-sbek-confirm-dirty hidden><?php echo esc_html( $dirty_note ); ?></span>
			<?php endif; ?>
			<?php echo $confirm_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted markup built by callers. ?>
			<button type="button" class="sbek-btn sbek-btn--plain" data-sbek-confirm-cancel><?php esc_html_e( 'Cancel', 'sbek' ); ?></button>
		</div>
	</div>
	<?php
}

/**
 * The Tools tab: demo content card.
 */
function sbek_render_demo_tools(): void {
	$status = Sbek_Demo_Importer::status();

	sbek_section_open( 'rocket', __( 'Demo content', 'sbek' ), __( 'Sample categories, about 140 Khmer and English posts with images, pages, menus and ad placeholders — handy for previewing the theme. Removing deletes only what the import created; your own content and Sbek Theme values are kept.', 'sbek' ) );
	?>
	<div class="sbek-demo" data-sbek-demo data-state="<?php echo esc_attr( $status['state'] ); ?>">
		<p class="sbek-demo__status">
			<?php
			if ( 'running' === $status['state'] ) {
				echo esc_html(
					sprintf(
						/* translators: 1: "import" or "removal", 2: percent. */
						__( 'An unfinished %1$s stopped at %2$d%%. Continue it, or remove what was created so far.', 'sbek' ),
						'import' === $status['type'] ? __( 'import', 'sbek' ) : __( 'removal', 'sbek' ),
						$status['progress']
					)
				);
			} elseif ( 'installed' === $status['state'] && $status['installed'] ) {
				echo esc_html(
					sprintf(
						/* translators: 1: date, 2: posts and pages, 3: images. */
						__( 'Installed %1$s: %2$d posts and pages, %3$d images.', 'sbek' ),
						wp_date( get_option( 'date_format' ), $status['installed'] ),
						$status['posts'],
						$status['images']
					)
				);
			} elseif ( 'installed' === $status['state'] ) {
				echo esc_html(
					sprintf(
						/* translators: 1: posts and pages, 2: images. */
						__( 'An incomplete import left %1$d posts and pages and %2$d images. Remove them before importing again.', 'sbek' ),
						$status['posts'],
						$status['images']
					)
				);
			} else {
				esc_html_e( 'No demo content is installed.', 'sbek' );
			}
			?>
		</p>

		<?php if ( 'none' === $status['state'] ) : ?>
			<?php
			sbek_option_toggle(
				'sbek_demo_site_setup',
				__( 'Also apply the demo site settings', 'sbek' ),
				__( 'Site title, Khmer language, Phnom Penh time zone, 8 posts per page, /article/123/ links, menus, ad placeholders, privacy page, and Sbek Theme details (only fields that are still empty). These are not undone by "Remove".', 'sbek' ),
				true
			);
			?>
		<?php endif; ?>

		<div class="sbek-demo__progress" data-sbek-demo-progress hidden>
			<div class="sbek-demo__track"><span class="sbek-demo__fill" data-sbek-demo-fill></span></div>
			<p class="sbek-demo__message" role="status" aria-live="polite" data-sbek-demo-message></p>
		</div>

		<div class="sbek-demo__actions">
			<?php if ( 'none' === $status['state'] ) : ?>
				<button type="button" class="sbek-btn sbek-btn--brand" data-sbek-demo-run="import" data-continue-label="<?php esc_attr_e( 'Continue', 'sbek' ); ?>"><?php esc_html_e( 'Import demo content', 'sbek' ); ?></button>
			<?php elseif ( 'running' === $status['state'] ) : ?>
				<button type="button" class="sbek-btn sbek-btn--brand" data-sbek-demo-run="continue"><?php esc_html_e( 'Continue', 'sbek' ); ?></button>
			<?php endif; ?>

			<?php
			if ( 'none' !== $status['state'] ) {
				sbek_confirm_control(
					__( 'Remove demo content', 'sbek' ),
					__( 'Delete all demo posts, pages, images, categories, menus and widgets?', 'sbek' ),
					'<button type="button" class="sbek-btn sbek-btn--danger" data-sbek-demo-run="reset">' . esc_html__( 'Yes, remove', 'sbek' ) . '</button>'
				);
			}
			?>
		</div>
	</div>
	<?php
	sbek_section_close();
}
