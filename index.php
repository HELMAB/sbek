<?php
/**
 * Fallback template (e.g. the posts page when a static front page is set).
 *
 * @package sbek
 */

defined( 'ABSPATH' ) || exit;

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
					'title' => is_home() && ! is_front_page() ? single_post_title( '', false ) : __( 'Latest news', 'sbek' ),
				)
			);
			?>
		</div>
		<?php get_sidebar( null, array( 'boxed' => false ) ); ?>
	</div>
</main>
<?php
get_footer();
