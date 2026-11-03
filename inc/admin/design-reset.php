<?php
/**
 * "Reset design" on the Design tab of Appearance → Sbek Theme, and the
 * inline two-step confirmation control it (and plugin tools) use.
 *
 * @package sbek
 */

defined( 'ABSPATH' ) || exit;

const SBEK_RESET_DESIGN_NONCE = 'sbek_reset_design';

/**
 * Restore the Design tab (colors + fonts) to the theme defaults. Nothing else
 * in Sbek Theme is touched.
 */
function sbek_reset_design(): void {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to do this.', 'sbek' ), 403 );
	}

	check_admin_referer( SBEK_RESET_DESIGN_NONCE );

	$settings = sbek_site_setting();
	$defaults = sbek_site_settings_defaults();

	foreach ( array( 'color_brand', 'color_top', 'color_footer', 'font_body', 'font_heading', 'font_latin' ) as $key ) {
		$settings[ $key ] = $defaults[ $key ];
	}

	update_option( SBEK_SETTINGS_OPTION, $settings );

	wp_safe_redirect( add_query_arg( 'design_reset', 1, sbek_theme_options_url() ) . '#design' );
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
