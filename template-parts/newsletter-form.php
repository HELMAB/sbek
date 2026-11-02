<?php
/**
 * Footer newsletter form (handled by inc/frontend/newsletter.php).
 *
 * @package sbek
 */

defined( 'ABSPATH' ) || exit;

$sbek_notice = sbek_subscribe_notice();
?>
<div class="newsletter" id="newsletter">
	<p class="newsletter__title"><strong><?php esc_html_e( 'Newsletter', 'sbek' ); ?></strong></p>
	<p><?php esc_html_e( 'Get the top stories in your inbox.', 'sbek' ); ?></p>

	<?php if ( $sbek_notice ) : ?>
		<p class="newsletter__notice<?php echo $sbek_notice['error'] ? ' is-error' : ''; ?>" role="status"><?php echo esc_html( $sbek_notice['message'] ); ?></p>
	<?php endif; ?>

	<form class="newsletter__form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
		<input type="hidden" name="action" value="sbek_subscribe">
		<input type="hidden" name="lang" value="<?php echo esc_attr( sbek_lang() ); ?>">
		<?php wp_nonce_field( 'sbek_subscribe', 'sbek_newsletter_nonce' ); ?>
		<p class="newsletter__hp" aria-hidden="true">
			<label>Website <input type="text" name="sbek_website" tabindex="-1" autocomplete="off"></label>
		</p>
		<label for="sbek-newsletter-email" class="screen-reader-text"><?php esc_html_e( 'Email address', 'sbek' ); ?></label>
		<input type="email" id="sbek-newsletter-email" name="email" required autocomplete="email" placeholder="<?php esc_attr_e( 'Email address', 'sbek' ); ?>" class="newsletter__input">
		<button type="submit" class="newsletter__submit"><?php esc_html_e( 'Subscribe', 'sbek' ); ?></button>
	</form>
</div>
