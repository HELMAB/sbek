<?php
/**
 * Right-hand column: "Popular" and "Latest" lists, then the Sidebar widget
 * area. Call with get_sidebar( null, array( 'boxed' => bool ) ) — article
 * pages box each list in grey, archives don't.
 *
 * @package sbek
 */

defined( 'ABSPATH' ) || exit;

$sbek_boxed   = ! empty( $args['boxed'] );
$sbek_exclude = is_singular( 'post' ) ? array( get_queried_object_id() ) : array();

$sbek_lists = array(
	'popular' => array( __( 'Popular articles', 'sbek' ), sbek_get_popular_posts( 6 ) ),
	'latest'  => array( __( 'Latest articles', 'sbek' ), sbek_get_latest_posts( 6, $sbek_exclude ) ),
);
?>
<aside class="sidebar<?php echo $sbek_boxed ? ' sidebar--boxed' : ''; ?>" aria-label="<?php esc_attr_e( 'Sidebar', 'sbek' ); ?>">
	<?php
	foreach ( $sbek_lists as $sbek_key => $sbek_list ) :
		if ( empty( $sbek_list[1] ) ) {
			continue;
		}
		?>
		<section class="sidebar__block" aria-labelledby="sbek-sidebar-<?php echo esc_attr( $sbek_key ); ?>">
			<h2 id="sbek-sidebar-<?php echo esc_attr( $sbek_key ); ?>" class="rule-title"><?php echo esc_html( $sbek_list[0] ); ?></h2>
			<ul class="media-list">
				<?php
				foreach ( $sbek_list[1] as $sbek_item ) {
					get_template_part( 'template-parts/card/media-row', null, array( 'post' => $sbek_item ) );
				}
				?>
			</ul>
		</section>
	<?php endforeach; ?>

	<?php if ( is_active_sidebar( 'sbek-sidebar' ) ) : ?>
		<div class="sidebar__block sidebar__widgets">
			<?php dynamic_sidebar( 'sbek-sidebar' ); ?>
		</div>
	<?php endif; ?>
</aside>
