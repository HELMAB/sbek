<?php
/**
 * Static pages (About, Privacy Policy…).
 *
 * @package sbek
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="main" class="site-main">
	<div class="container layout-sidebar">
		<?php while ( have_posts() ) : the_post(); ?>
			<article id="post-<?php the_ID(); ?>" <?php post_class( 'article white-bg layout-sidebar__main' ); ?>>
				<header class="article__header">
					<h1 class="article__title"><?php the_title(); ?></h1>
				</header>
				<div class="article__content entry-content">
					<?php
					the_content();
					wp_link_pages();
					?>
				</div>
				<?php
				if ( comments_open() || get_comments_number() ) {
					comments_template();
				}
				?>
			</article>
		<?php endwhile; ?>
		<?php get_sidebar( null, array( 'boxed' => true ) ); ?>
	</div>
</main>
<?php
get_footer();
