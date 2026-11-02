<?php
/**
 * Single article: blurred cover backdrop, headline + meta, body (cover image
 * injected after the first paragraph, see inc/frontend/content.php), source /
 * author, share links, tags, related stories; sidebar on the right.
 *
 * @package sbek
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$sbek_post      = get_post();
	$sbek_url       = get_permalink();
	$sbek_title     = get_the_title();
	$sbek_source    = (string) get_post_meta( $sbek_post->ID, '_sbek_source_name', true );
	$sbek_source_to = (string) get_post_meta( $sbek_post->ID, '_sbek_source_url', true );
	$sbek_video     = (string) get_post_meta( $sbek_post->ID, '_sbek_video_url', true );
	$sbek_sponsored = (bool) get_post_meta( $sbek_post->ID, '_sbek_is_sponsored', true );
	$sbek_tags      = get_the_tags();
	$sbek_has_zwsp  = false !== strpos( $sbek_post->post_content, "\u{200B}" );
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
					<?php if ( $sbek_sponsored ) : ?>
						<p class="article__sponsored"><?php esc_html_e( 'Sponsored content', 'sbek' ); ?></p>
					<?php endif; ?>
					<h1 class="article__title"><?php echo esc_html( $sbek_title ); ?></h1>
					<ul class="article__meta">
						<li>
							<?php echo sbek_icon( 'clock', array( 'width' => 12, 'height' => 12 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
							<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( sbek_post_date() ); ?></time>
						</li>
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

				<?php if ( $sbek_video ) : ?>
					<?php $sbek_embed = wp_oembed_get( $sbek_video, array( 'width' => 730 ) ); ?>
					<figure class="article__video">
						<?php if ( $sbek_embed ) : ?>
							<?php echo $sbek_embed; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- oEmbed HTML from WordPress' provider allow-list. ?>
						<?php else : ?>
							<a href="<?php echo esc_url( $sbek_video ); ?>" target="_blank" rel="noopener noreferrer" class="btn"><?php esc_html_e( 'Watch the video', 'sbek' ); ?></a>
						<?php endif; ?>
					</figure>
				<?php endif; ?>

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
					<?php if ( $sbek_source ) : ?>
						<p>
							<?php esc_html_e( 'Source:', 'sbek' ); ?>
							<?php if ( $sbek_source_to ) : ?>
								<a href="<?php echo esc_url( $sbek_source_to ); ?>" target="_blank" rel="noopener noreferrer nofollow"><?php echo esc_html( $sbek_source ); ?></a>
							<?php else : ?>
								<?php echo esc_html( $sbek_source ); ?>
							<?php endif; ?>
						</p>
					<?php endif; ?>
					<p>
						<?php
						/* translators: %s: author name. */
						printf( esc_html__( 'By: %s', 'sbek' ), '<a href="' . esc_url( get_author_posts_url( (int) $sbek_post->post_author ) ) . '">' . esc_html( get_the_author() ) . '</a>' );
						?>
					</p>

					<ul class="share-links" aria-label="<?php esc_attr_e( 'Share', 'sbek' ); ?>">
						<li>
							<a class="share-links__fb" href="<?php echo esc_url( 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $sbek_url ) ); ?>" target="_blank" rel="noopener noreferrer">
								<?php echo sbek_icon( 'facebook', array( 'width' => 13, 'height' => 13 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
								<span class="screen-reader-text"><?php esc_html_e( 'Share on Facebook', 'sbek' ); ?></span>
							</a>
						</li>
						<li>
							<a class="share-links__x" href="<?php echo esc_url( 'https://twitter.com/intent/tweet?url=' . rawurlencode( $sbek_url ) . '&text=' . rawurlencode( wp_strip_all_tags( $sbek_title ) ) ); ?>" target="_blank" rel="noopener noreferrer">
								<?php echo sbek_icon( 'x', array( 'width' => 12, 'height' => 12 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
								<span class="screen-reader-text"><?php esc_html_e( 'Share on X', 'sbek' ); ?></span>
							</a>
						</li>
						<li>
							<a class="share-links__mail" href="<?php echo esc_url( 'mailto:?subject=' . rawurlencode( wp_strip_all_tags( $sbek_title ) ) . '&body=' . rawurlencode( $sbek_url ) ); ?>">
								<?php echo sbek_icon( 'at-sign', array( 'width' => 13, 'height' => 13 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
								<span class="screen-reader-text"><?php esc_html_e( 'Share by email', 'sbek' ); ?></span>
							</a>
						</li>
					</ul>
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
			</article>

			<?php get_sidebar( null, array( 'boxed' => true ) ); ?>
		</div>
	</main>
	<?php
endwhile;

get_footer();
