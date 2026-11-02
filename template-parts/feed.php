<?php
/**
 * Shared feed layout for archives, search results and the blog index:
 * colored header with a list/grid switch, two lead stories on the first
 * page, then the list. Works as plain paginated HTML; theme.js upgrades the
 * pagination into infinite scroll by fetching the `rel=next` page and
 * appending its [data-feed-item] cards. After two automatic pages it switches
 * to a "Load more" button so the footer stays reachable.
 *
 * Runs the main query loop.
 *
 * Args:
 *   title   Heading text (required).
 *   color   Accent hex color.
 *   url     Heading link ('' for none).
 *   intro   Trusted, escaped HTML shown above the posts (e.g. search form).
 *
 * @package sbek
 */

defined( 'ABSPATH' ) || exit;

$sbek_title = (string) ( $args['title'] ?? '' );
$sbek_color = (string) ( $args['color'] ?? sbek_brand_color() );

ob_start();
?>
<div class="view-switch" role="group" aria-label="<?php esc_attr_e( 'Display', 'sbek' ); ?>">
	<button type="button" class="view-switch__btn" data-view="list" aria-pressed="true">
		<?php echo sbek_icon( 'list', array( 'width' => 16, 'height' => 16 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
		<span class="screen-reader-text"><?php esc_html_e( 'List view', 'sbek' ); ?></span>
	</button>
	<button type="button" class="view-switch__btn" data-view="grid" aria-pressed="false">
		<?php echo sbek_icon( 'grid', array( 'width' => 16, 'height' => 16 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
		<span class="screen-reader-text"><?php esc_html_e( 'Grid view', 'sbek' ); ?></span>
	</button>
</div>
<?php
$sbek_switch = (string) ob_get_clean();
?>
<section class="feed" data-sbek-feed>
	<?php sbek_section_header( $sbek_title, $sbek_color, (string) ( $args['url'] ?? '' ), have_posts() ? $sbek_switch : '', 'h1' ); ?>

	<div class="feed__body white-bg">
		<?php echo $args['intro'] ?? ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- documented as trusted. ?>

		<?php if ( have_posts() ) : ?>
			<?php if ( ! is_paged() ) : ?>
				<div class="feed-lead">
					<?php
					$sbek_lead_count = 0;
					while ( $sbek_lead_count < 2 && have_posts() ) :
						the_post();
						++$sbek_lead_count;
						get_template_part(
							'template-parts/card/thumb',
							null,
							array(
								'post'        => get_post(),
								'class'       => 'feed-lead__item',
								'size'        => 'sbek-wide',
								'sizes'       => '(max-width: 767px) 50vw, 370px',
								'title_class' => 'news-thumb__title--feed-lead',
								'date'        => true,
							)
						);
					endwhile;
					?>
				</div>
			<?php endif; ?>

			<div class="feed__list" data-feed-list>
				<?php
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/card/feed', null, array( 'post' => get_post() ) );
				endwhile;
				?>
			</div>

			<div class="feed__pagination" data-feed-pagination>
				<?php
				the_posts_pagination(
					array(
						'mid_size'           => 1,
						'prev_text'          => sbek_icon( 'chevron-left', array( 'width' => 16, 'height' => 16 ) ) . '<span class="screen-reader-text">' . esc_html__( 'Previous page', 'sbek' ) . '</span>',
						'next_text'          => sbek_icon( 'chevron-right', array( 'width' => 16, 'height' => 16 ) ) . '<span class="screen-reader-text">' . esc_html__( 'Next page', 'sbek' ) . '</span>',
						'screen_reader_text' => __( 'Posts navigation', 'sbek' ),
					)
				);
				?>
			</div>
			<div class="feed__loader" data-feed-loader hidden>
				<?php echo sbek_icon( 'loader', array( 'width' => 42, 'height' => 42, 'class' => 'spin' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
				<span class="screen-reader-text"><?php esc_html_e( 'Loading…', 'sbek' ); ?></span>
			</div>
			<div class="feed__more">
				<button type="button" class="btn" data-feed-more hidden><?php esc_html_e( 'Load more stories', 'sbek' ); ?></button>
			</div>
			<p class="feed__end" data-feed-end hidden><?php esc_html_e( 'You have reached the end of the list.', 'sbek' ); ?></p>
			<?php /* translators: %d: number of stories just added to the list. */ ?>
			<p class="screen-reader-text" role="status" data-feed-status data-loaded="<?php echo esc_attr__( '%d more stories loaded.', 'sbek' ); ?>"></p>
		<?php else : ?>
			<?php get_template_part( 'template-parts/content-none' ); ?>
		<?php endif; ?>
	</div>
</section>
