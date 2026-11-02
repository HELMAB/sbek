<?php
/**
 * Category, tag, post-format, author and date archives.
 *
 * @package sbek
 */

defined( 'ABSPATH' ) || exit;

$sbek_object = get_queried_object();
$sbek_color  = SBEK_BRAND_COLOR;
$sbek_url    = '';

if ( $sbek_object instanceof WP_Term ) {
	$sbek_title = $sbek_object->name;
	$sbek_link  = get_term_link( $sbek_object );
	$sbek_url   = is_wp_error( $sbek_link ) ? '' : $sbek_link;

	if ( 'post_format' === $sbek_object->taxonomy ) {
		$sbek_title = get_post_format_string( str_replace( 'post-format-', '', $sbek_object->slug ) );
		$sbek_color = '#f2272f';
		if ( 'post-format-video' === $sbek_object->slug ) {
			$sbek_title = __( 'Video', 'sbek' );
		}
	} else {
		$sbek_color = sbek_term_color( $sbek_object );
	}
} else {
	$sbek_title = wp_strip_all_tags( get_the_archive_title() );
}

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
					'title' => $sbek_title,
					'color' => $sbek_color,
					'url'   => $sbek_url,
				)
			);
			?>
		</div>
		<?php get_sidebar( null, array( 'boxed' => false ) ); ?>
	</div>
</main>
<?php
get_footer();
