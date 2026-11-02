<?php
/**
 * Empty feed state.
 *
 * @package sbek
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="empty-state">
	<p class="empty-state__title"><?php esc_html_e( 'Nothing found.', 'sbek' ); ?></p>
	<?php if ( is_search() ) : ?>
		<p><?php esc_html_e( 'Try different keywords or another category.', 'sbek' ); ?></p>
	<?php else : ?>
		<a href="<?php echo esc_url( sbek_home_url() ); ?>" class="btn"><?php esc_html_e( 'Back to the home page', 'sbek' ); ?></a>
	<?php endif; ?>
</div>
