<?php
/**
 * Category, tag, post-format, author and date archives. Above the posts:
 * the term description and subcategory links, or the author's bio.
 *
 * @package sbek
 */

defined( 'ABSPATH' ) || exit;

$sbek_object = get_queried_object();
$sbek_color  = sbek_brand_color();
$sbek_url    = '';

if ( $sbek_object instanceof WP_Term ) {
	$sbek_title = $sbek_object->name;
	$sbek_link  = get_term_link( $sbek_object );
	$sbek_url   = is_wp_error( $sbek_link ) ? '' : $sbek_link;

	if ( 'post_format' === $sbek_object->taxonomy ) {
		$sbek_title = get_post_format_string( str_replace( 'post-format-', '', $sbek_object->slug ) );
		$sbek_color = sbek_brand_color();
		if ( 'post-format-video' === $sbek_object->slug ) {
			$sbek_title = __( 'Video', 'sbek' );
		}
	} else {
		$sbek_color = sbek_term_color( $sbek_object );
	}
} else {
	$sbek_title = wp_strip_all_tags( get_the_archive_title() );
}

// Intro shown on the first page only.
ob_start();
if ( ! is_paged() && $sbek_object instanceof WP_Term && in_array( $sbek_object->taxonomy, array( 'category', 'post_tag' ), true ) ) {
	$sbek_description = trim( sbek_term_description( $sbek_object ) );
	$sbek_children    = 'category' === $sbek_object->taxonomy
		? get_terms(
			array(
				'taxonomy'   => 'category',
				'parent'     => $sbek_object->term_id,
				'hide_empty' => true,
			)
		)
		: array();

	if ( '' !== $sbek_description || ( $sbek_children && ! is_wp_error( $sbek_children ) ) ) :
		?>
		<div class="archive-intro">
			<?php if ( '' !== $sbek_description ) : ?>
				<div class="archive-intro__text"><?php echo wp_kses_post( wpautop( $sbek_description ) ); ?></div>
			<?php endif; ?>
			<?php if ( $sbek_children && ! is_wp_error( $sbek_children ) ) : ?>
				<nav class="archive-intro__subnav" aria-label="<?php esc_attr_e( 'Subcategories', 'sbek' ); ?>">
					<ul class="tag-list">
						<?php foreach ( $sbek_children as $sbek_child ) : ?>
							<li><a href="<?php echo esc_url( get_term_link( $sbek_child ) ); ?>"><?php echo esc_html( $sbek_child->name ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</nav>
			<?php endif; ?>
		</div>
		<?php
	endif;
} elseif ( ! is_paged() && $sbek_object instanceof WP_User ) {
	$sbek_bio = trim( (string) get_the_author_meta( 'description', $sbek_object->ID ) );
	?>
	<div class="archive-intro archive-intro--author">
		<?php echo get_avatar( $sbek_object->ID, 72, '', '', array( 'class' => 'archive-intro__avatar' ) ); ?>
		<div class="archive-intro__text">
			<p class="archive-intro__name"><?php echo esc_html( $sbek_object->display_name ); ?></p>
			<?php if ( '' !== $sbek_bio ) : ?>
				<?php echo wp_kses_post( wpautop( $sbek_bio ) ); ?>
			<?php endif; ?>
		</div>
	</div>
	<?php
}
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
					'title' => $sbek_title,
					'color' => $sbek_color,
					'url'   => $sbek_url,
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
