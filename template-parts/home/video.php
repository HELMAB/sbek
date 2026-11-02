<?php
/**
 * Homepage "Video" block: posts in the Video post format. Desktop shows 3 large
 * + 8 small (with an optional 300px ad); mobile shows one swipe row.
 *
 * Args:
 *   posts  WP_Post[] (up to 11)
 *
 * @package sbek
 */

defined( 'ABSPATH' ) || exit;

$sbek_posts = (array) ( $args['posts'] ?? array() );
if ( ! $sbek_posts ) {
	return;
}

$sbek_video_url = get_post_format_link( 'video' );
?>
<section class="home-block" aria-label="<?php esc_attr_e( 'Video', 'sbek' ); ?>">
	<?php sbek_section_header( __( 'Video', 'sbek' ), sbek_brand_color(), $sbek_video_url ? $sbek_video_url : '' ); ?>
	<div class="home-block__body">
		<div class="video-block">
			<div class="video-block__top">
				<?php
				foreach ( array_slice( $sbek_posts, 0, 3 ) as $sbek_post ) {
					get_template_part(
						'template-parts/card/thumb',
						null,
						array(
							'post'        => $sbek_post,
							'size'        => 'sbek-wide',
							'sizes'       => '(max-width: 991px) 250px, 360px',
							'thumb_class' => 'thumb--video-top',
							'title_class' => 'news-thumb__title--lg',
						)
					);
				}
				?>
			</div>
			<div class="video-block__bottom">
				<div class="card-grid card-grid--video">
					<?php
					foreach ( array_slice( $sbek_posts, 3, 8 ) as $sbek_post ) {
						get_template_part( 'template-parts/card/thumb', null, array( 'post' => $sbek_post ) );
					}
					?>
				</div>
				<?php sbek_ad_area( 'sbek-ad-video', 'video-block__ad' ); ?>
			</div>
		</div>

		<div class="swipe-row" data-sbek-swipe>
			<div class="swipe-row__track">
				<?php
				foreach ( $sbek_posts as $sbek_post ) {
					get_template_part( 'template-parts/card/swipe', null, array( 'post' => $sbek_post ) );
				}
				?>
			</div>
		</div>
	</div>
</section>
