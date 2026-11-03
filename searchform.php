<?php
/**
 * Search form (get_search_form()). Two variants:
 *   default  compact box: menu search panel and the 404 page;
 *   filter   `get_search_form( array( 'sbek_variant' => 'filter' ) )` — the
 *            box, a category filter and the result count, on search results.
 * Both submit to the current language's home so results stay in it.
 *
 * @package sbek
 */

defined( 'ABSPATH' ) || exit;

$sbek_search_id = 'sbek-search-' . wp_unique_id();

if ( 'filter' === ( $args['sbek_variant'] ?? '' ) ) :
	global $wp_query;

	$sbek_selected_cat = isset( $_GET['cat'] ) ? absint( $_GET['cat'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only filter.
	$sbek_categories   = get_categories(
		array(
			'hide_empty' => true,
			'number'     => 50,
		)
	);
	$sbek_found        = (int) $wp_query->found_posts;
	?>
	<form role="search" method="get" class="search-filter" action="<?php echo esc_url( sbek_home_url() ); ?>">
		<label class="screen-reader-text" for="<?php echo esc_attr( $sbek_search_id ); ?>"><?php esc_html_e( 'Search for:', 'sbek' ); ?></label>
		<input type="search" id="<?php echo esc_attr( $sbek_search_id ); ?>" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" class="search-filter__input" placeholder="<?php esc_attr_e( 'Search news…', 'sbek' ); ?>" required>
		<label class="screen-reader-text" for="<?php echo esc_attr( $sbek_search_id . '-cat' ); ?>"><?php esc_html_e( 'Category', 'sbek' ); ?></label>
		<select id="<?php echo esc_attr( $sbek_search_id . '-cat' ); ?>" name="cat" class="search-filter__select">
			<option value=""><?php esc_html_e( 'All categories', 'sbek' ); ?></option>
			<?php foreach ( $sbek_categories as $sbek_category ) : ?>
				<option value="<?php echo esc_attr( (string) $sbek_category->term_id ); ?>" <?php selected( $sbek_selected_cat, $sbek_category->term_id ); ?>><?php echo esc_html( $sbek_category->name ); ?></option>
			<?php endforeach; ?>
		</select>
		<button type="submit" class="btn"><?php esc_html_e( 'Search', 'sbek' ); ?></button>
		<p class="search-filter__count">
			<?php
			/* translators: %s: number of results. */
			echo esc_html( sprintf( _n( '%s result', '%s results', $sbek_found, 'sbek' ), number_format_i18n( $sbek_found ) ) );
			?>
		</p>
	</form>
	<?php
	return;
endif;
?>
<form role="search" method="get" class="search-form" action="<?php echo esc_url( sbek_home_url() ); ?>">
	<label for="<?php echo esc_attr( $sbek_search_id ); ?>" class="screen-reader-text"><?php esc_html_e( 'Search for:', 'sbek' ); ?></label>
	<input type="search" id="<?php echo esc_attr( $sbek_search_id ); ?>" class="search-form__input" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Search news…', 'sbek' ); ?>" required>
	<button type="submit" class="search-form__submit">
		<?php echo sbek_icon( 'search', array( 'width' => 18, 'height' => 18 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
		<span class="screen-reader-text"><?php esc_html_e( 'Search', 'sbek' ); ?></span>
	</button>
</form>
