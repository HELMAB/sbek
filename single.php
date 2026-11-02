<?php
/**
 * Single article: blurred cover backdrop, breadcrumb, headline + meta, body
 * (cover image injected after the first paragraph, see
 * inc/frontend/content.php), source / author, share links, tags, related
 * stories; sidebar on the right.
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
	$sbek_sponsored = sbek_is_sponsored( $sbek_post );
	$sbek_share_txt = wp_strip_all_tags( $sbek_title );
	// "Updated" only for real edits, not typo fixes right after publishing.
	$sbek_updated   = get_post_modified_time( 'U', true ) - get_post_time( 'U', true ) > HOUR_IN_SECONDS;
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
								<a href="<?php echo esc_url( $sbek_source_to ); ?>" target="_blank" rel="<?php echo esc_attr( 'noopener noreferrer ' . ( $sbek_sponsored ? 'sponsored' : 'nofollow' ) ); ?>"><?php echo esc_html( $sbek_source ); ?></a>
							<?php else : ?>
								<?php echo esc_html( $sbek_source ); ?>
							<?php endif; ?>
						</p>
					<?php endif; ?>
					<?php if ( sbek_section_enabled( 'byline' ) ) : ?>
						<p>
							<?php
							/* translators: %s: author name. */
							printf( esc_html__( 'By: %s', 'sbek' ), '<a href="' . esc_url( get_author_posts_url( (int) $sbek_post->post_author ) ) . '">' . esc_html( get_the_author() ) . '</a>' );
							?>
						</p>
					<?php endif; ?>

					<?php
					$sbek_share = array(
						'fb'       => array( 'facebook', __( 'Share on Facebook', 'sbek' ), 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $sbek_url ) ),
						'telegram' => array( 'telegram', __( 'Share on Telegram', 'sbek' ), 'https://t.me/share/url?url=' . rawurlencode( $sbek_url ) . '&text=' . rawurlencode( $sbek_share_txt ) ),
						'x'        => array( 'x', __( 'Share on X', 'sbek' ), 'https://twitter.com/intent/tweet?url=' . rawurlencode( $sbek_url ) . '&text=' . rawurlencode( $sbek_share_txt ) ),
						'mail'     => array( 'at-sign', __( 'Share by email', 'sbek' ), 'mailto:?subject=' . rawurlencode( $sbek_share_txt ) . '&body=' . rawurlencode( $sbek_url ) ),
					);
					?>
					<ul class="share-links" aria-label="<?php esc_attr_e( 'Share', 'sbek' ); ?>">
						<li class="share-links__native-item" hidden>
							<button type="button" class="share-links__native" data-sbek-share data-url="<?php echo esc_url( $sbek_url ); ?>" data-title="<?php echo esc_attr( $sbek_share_txt ); ?>">
								<?php echo sbek_icon( 'share-2', array( 'width' => 15, 'height' => 15 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
								<span class="screen-reader-text"><?php esc_html_e( 'Share…', 'sbek' ); ?></span>
							</button>
						</li>
						<?php foreach ( $sbek_share as $sbek_share_key => $sbek_share_item ) : ?>
							<li>
								<a class="share-links__<?php echo esc_attr( $sbek_share_key ); ?>" href="<?php echo esc_url( $sbek_share_item[2] ); ?>"<?php echo 'mail' === $sbek_share_key ? '' : ' target="_blank" rel="noopener noreferrer"'; ?>>
									<?php echo sbek_icon( $sbek_share_item[0], array( 'width' => 15, 'height' => 15 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
									<span class="screen-reader-text"><?php echo esc_html( $sbek_share_item[1] ); ?></span>
								</a>
							</li>
						<?php endforeach; ?>
						<li>
							<button type="button" class="share-links__copy" data-sbek-copy="<?php echo esc_url( $sbek_url ); ?>" data-copied="<?php esc_attr_e( 'Link copied', 'sbek' ); ?>">
								<?php echo sbek_icon( 'link', array( 'width' => 15, 'height' => 15 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
								<span class="screen-reader-text"><?php esc_html_e( 'Copy link', 'sbek' ); ?></span>
							</button>
						</li>
					</ul>
					<p class="share-links__status screen-reader-text" role="status" data-sbek-copy-status></p>
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
