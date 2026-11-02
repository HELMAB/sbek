<?php
/**
 * Not found.
 *
 * @package sbek
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="main" class="site-main">
	<div class="container">
		<section class="not-found white-bg">
			<p class="not-found__code" aria-hidden="true">404</p>
			<h1 class="not-found__title"><?php esc_html_e( 'The page you are looking for could not be found.', 'sbek' ); ?></h1>
			<div class="not-found__search"><?php get_search_form(); ?></div>
			<a href="<?php echo esc_url( sbek_home_url() ); ?>" class="btn"><?php esc_html_e( 'Back to the home page', 'sbek' ); ?></a>
		</section>

		<?php $sbek_popular = sbek_get_popular_posts( 8 ); ?>
		<?php if ( $sbek_popular ) : ?>
			<section class="home-block">
				<?php sbek_section_header( __( 'Popular articles', 'sbek' ), sbek_brand_color() ); ?>
				<div class="home-block__body">
					<div class="card-grid card-grid--four">
						<?php
						foreach ( $sbek_popular as $sbek_post ) {
							get_template_part( 'template-parts/card/thumb', null, array( 'post' => $sbek_post ) );
						}
						?>
					</div>
				</div>
			</section>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();
