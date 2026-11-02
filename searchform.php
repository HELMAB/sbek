<?php
/**
 * Search form (get_search_form()): menu search panel and the 404 page.
 * Submits to the current language's home so results stay in that language.
 *
 * @package sbek
 */

defined( 'ABSPATH' ) || exit;

$sbek_search_id = 'sbek-search-' . wp_unique_id();
?>
<form role="search" method="get" class="search-form" action="<?php echo esc_url( sbek_home_url() ); ?>">
	<label for="<?php echo esc_attr( $sbek_search_id ); ?>" class="screen-reader-text"><?php esc_html_e( 'Search for:', 'sbek' ); ?></label>
	<input type="search" id="<?php echo esc_attr( $sbek_search_id ); ?>" class="search-form__input" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Search news…', 'sbek' ); ?>" required>
	<button type="submit" class="search-form__submit">
		<?php echo sbek_icon( 'search', array( 'width' => 18, 'height' => 18 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
		<span class="screen-reader-text"><?php esc_html_e( 'Search', 'sbek' ); ?></span>
	</button>
</form>
