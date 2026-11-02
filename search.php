<?php
/**
 * Search results, with the search box and a category filter on top.
 *
 * @package sbek
 */

defined( 'ABSPATH' ) || exit;

global $wp_query;

$sbek_selected_cat = isset( $_GET['cat'] ) ? absint( $_GET['cat'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only filter.
$sbek_categories   = get_categories(
	array(
		'hide_empty' => true,
		'number'     => 50,
	)
);

ob_start();
?>
<form role="search" method="get" class="search-filter" action="<?php echo esc_url( sbek_home_url() ); ?>">
	<label class="screen-reader-text" for="sbek-search-query"><?php esc_html_e( 'Search for:', 'sbek' ); ?></label>
	<input type="search" id="sbek-search-query" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" class="search-filter__input" placeholder="<?php esc_attr_e( 'Search news…', 'sbek' ); ?>" required>
	<label class="screen-reader-text" for="sbek-search-cat"><?php esc_html_e( 'Category', 'sbek' ); ?></label>
	<select id="sbek-search-cat" name="cat" class="search-filter__select">
		<option value=""><?php esc_html_e( 'All categories', 'sbek' ); ?></option>
		<?php foreach ( $sbek_categories as $sbek_category ) : ?>
			<option value="<?php echo esc_attr( (string) $sbek_category->term_id ); ?>" <?php selected( $sbek_selected_cat, $sbek_category->term_id ); ?>><?php echo esc_html( $sbek_category->name ); ?></option>
		<?php endforeach; ?>
	</select>
	<button type="submit" class="btn"><?php esc_html_e( 'Search', 'sbek' ); ?></button>
	<p class="search-filter__count">
		<?php
		$sbek_found = (int) $wp_query->found_posts;
		/* translators: %s: number of results. */
		echo esc_html( sprintf( _n( '%s result', '%s results', $sbek_found, 'sbek' ), number_format_i18n( $sbek_found ) ) );
		?>
	</p>
</form>
<?php
$sbek_intro = (string) ob_get_clean();

get_header();
?>
<main id="main" class="site-main">
	<div class="container layout-sidebar">
		<div class="layout-sidebar__main">
			<?php
			get_template_part(
				'template-parts/feed',
				null,
				array(
					'title' => '' !== trim( get_search_query( false ) )
						/* translators: %s: search query. */
						? sprintf( __( 'Search: %s', 'sbek' ), get_search_query( false ) )
						: __( 'Search', 'sbek' ),
					'intro' => $sbek_intro,
				)
			);
			?>
		</div>
		<?php get_sidebar( null, array( 'boxed' => false ) ); ?>
	</div>
</main>
<?php
get_footer();
