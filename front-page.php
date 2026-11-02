<?php
/**
 * Homepage: hero, video block, then one block per homepage category.
 *
 * @package sbek
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="main" class="site-main site-main--home">
	<h1 class="screen-reader-text"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></h1>

	<?php get_template_part( 'template-parts/home/featured', null, array( 'posts' => sbek_get_featured_posts( 4 ) ) ); ?>

	<div class="container">
		<?php
		if ( sbek_section_enabled( 'video' ) ) {
			get_template_part( 'template-parts/home/video', null, array( 'posts' => sbek_get_video_posts( 11 ) ) );
		}

		foreach ( sbek_get_home_categories() as $sbek_category ) {
			get_template_part(
				'template-parts/home/category',
				null,
				array(
					'term'  => $sbek_category,
					'posts' => sbek_get_category_posts( $sbek_category->term_id, 27 ),
				)
			);
		}
		?>
	</div>
</main>
<?php
get_footer();
