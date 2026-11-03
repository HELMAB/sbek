<?php
/**
 * Search results, with the search box and a category filter on top.
 *
 * @package sbek
 */

defined( 'ABSPATH' ) || exit;

// The search box + category filter (searchform.php, "filter" variant).
$sbek_intro = (string) get_search_form(
	array(
		'echo'         => false,
		'sbek_variant' => 'filter',
	)
);

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
