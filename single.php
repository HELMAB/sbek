<?php
/**
 * Single article: blurred cover backdrop, breadcrumb, headline + meta, body
 * (cover image injected after the first paragraph, see
 * inc/frontend/content.php), byline, tags, related stories, comments;
 * sidebar on the right.
 *
 * Plugin hooks (the Sbek Companion plugin uses them for the video embed, the
 * source line and the share buttons):
 *   sbek_before_article_content( WP_Post $post )
 *   sbek_article_footer( WP_Post $post )
 *
 * @package sbek
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$sbek_post      = get_post();
	$sbek_title     = get_the_title();
	$sbek_sponsored = sbek_is_sponsored( $sbek_post );
	// "Updated" only for real edits, not typo fixes right after publishing.
	$sbek_updated  = get_post_modified_time( 'U', true ) - get_post_time( 'U', true ) > HOUR_IN_SECONDS;
	$sbek_tags     = get_the_tags();
	$sbek_has_zwsp = false !== strpos( $sbek_post->post_content, "\u{200B}" );
	?>
	<main id="main" class="site-main site-main--article">
		<?php if ( has_post_thumbnail() ) : ?>
			<div class="article-backdrop" aria-hidden="true">
				<?php
				// Blurred 10px anyway — the small card rendition is plenty.
				echo sbek_get_post_image( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper.
					$sbek_post,
					'sbek-card',
					array(
						'sizes'   => '360px',
						'loading' => 'eager',
						'class'   => 'article-backdrop__img',
					)
				);
				?>
			</div>
		<?php endif; ?>

		<div class="container layout-sidebar">
			<article id="post-<?php the_ID(); ?>" <?php post_class( 'article white-bg layout-sidebar__main' ); ?>>
				<header class="article__header">
					<?php sbek_breadcrumb( $sbek_post ); ?>
					<?php if ( $sbek_sponsored ) : ?>
						<p class="article__sponsored"><?php esc_html_e( 'Sponsored content', 'sbek' ); ?></p>
					<?php endif; ?>
					<h1 class="article__title"><?php echo esc_html( $sbek_title ); ?></h1>
					<ul class="article__meta">
						<li>
							<?php echo sbek_icon( 'clock', array( 'width' => 12, 'height' => 12 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
							<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( sbek_post_date() ); ?></time>
						</li>
						<?php if ( $sbek_updated ) : ?>
							<li class="article__updated">
								<?php
								printf(
									/* translators: %s: date and time of the last update. */
									esc_html__( 'Updated: %s', 'sbek' ),
									'<time datetime="' . esc_attr( get_the_modified_date( DATE_W3C ) ) . '">' . esc_html( get_the_modified_date( _x( 'l, j F Y H:i', 'post date format', 'sbek' ) ) ) . '</time>'
								);
								?>
							</li>
						<?php endif; ?>
					</ul>
				</header>

				<?php if ( $sbek_has_zwsp ) : ?>
					<div class="zwsp-toggle">
						<span id="sbek-zwsp-label" class="zwsp-toggle__label"><?php esc_html_e( 'Show word breaks', 'sbek' ); ?></span>
						<label class="switch">
							<input type="checkbox" class="switch__input" data-sbek-zwsp-toggle aria-labelledby="sbek-zwsp-label">
							<span class="switch__track" aria-hidden="true"></span>
						</label>
					</div>
				<?php endif; ?>

				<?php do_action( 'sbek_before_article_content', $sbek_post ); ?>

				<div class="article__content entry-content" data-sbek-article-body>
					<?php
					the_content();
					wp_link_pages(
						array(
							'before' => '<nav class="page-links" aria-label="' . esc_attr__( 'Article pages', 'sbek' ) . '">',
							'after'  => '</nav>',
						)
					);
					?>
				</div>

				<footer class="article__footer">
					<?php if ( sbek_section_enabled( 'byline' ) ) : ?>
						<p>
							<?php
							/* translators: %s: author name. */
							printf( esc_html__( 'By: %s', 'sbek' ), '<a href="' . esc_url( get_author_posts_url( (int) $sbek_post->post_author ) ) . '">' . esc_html( get_the_author() ) . '</a>' );
							?>
						</p>
					<?php endif; ?>
					<?php do_action( 'sbek_article_footer', $sbek_post ); ?>
				</footer>

				<?php if ( $sbek_tags ) : ?>
					<div class="article__section">
						<h2 class="article__section-title"><?php esc_html_e( 'Tags', 'sbek' ); ?></h2>
						<ul class="tag-list">
							<?php foreach ( $sbek_tags as $sbek_tag ) : ?>
								<li><a href="<?php echo esc_url( get_tag_link( $sbek_tag ) ); ?>" rel="tag"><?php echo esc_html( $sbek_tag->name ); ?></a></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>

				<div class="article__section">
					<?php get_template_part( 'template-parts/related', null, array( 'posts' => sbek_get_related_posts( $sbek_post->ID, 12 ) ) ); ?>
				</div>

				<?php
				if ( comments_open() || get_comments_number() ) {
					comments_template();
				}
				?>
			</article>

			<?php get_sidebar( null, array( 'boxed' => true ) ); ?>
		</div>
	</main>
	<?php
endwhile;

get_footer();
