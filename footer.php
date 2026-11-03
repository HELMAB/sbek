<?php
/**
 * Site footer: brand + legal, about (+ `sbek_footer_about` hook), social +
 * contact, then a row of footer links (menu location from
 * sbek_menu_location( 'footer' ); until one is assigned, the privacy policy
 * link shows in the brand column instead).
 *
 * @package sbek
 */

defined( 'ABSPATH' ) || exit;

$sbek_socials     = (array) sbek_site_setting( 'socials', array() );
$sbek_phones      = (array) sbek_site_setting( 'phones', array() );
$sbek_email       = sanitize_email( (string) sbek_site_setting( 'email', '' ) );
$sbek_address     = sbek_site_text( 'address' );
$sbek_description = sbek_site_text( 'description' );
$sbek_privacy_url = get_privacy_policy_url();
$sbek_footer_menu = sbek_menu_location( 'footer' );
$sbek_has_links   = has_nav_menu( $sbek_footer_menu );
?>
<footer id="footer" class="site-footer">
	<div class="container">
		<div class="site-footer__grid">
			<div class="site-footer__col site-footer__col--brand">
				<a href="<?php echo esc_url( sbek_home_url() ); ?>" class="site-footer__logo" rel="home">
					<?php echo sbek_get_logo( '', 60 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
				</a>
				<p><?php echo esc_html( sbek_copyright_text() ); ?></p>
				<?php if ( $sbek_privacy_url && ! $sbek_has_links ) : ?>
					<p><strong><a href="<?php echo esc_url( $sbek_privacy_url ); ?>"><?php esc_html_e( 'Privacy Policy', 'sbek' ); ?></a></strong></p>
				<?php endif; ?>
				<?php if ( '' !== trim( $sbek_address ) ) : ?>
					<p>
						<strong><?php esc_html_e( 'Address', 'sbek' ); ?></strong><br>
						<?php echo nl2br( esc_html( $sbek_address ) ); ?>
					</p>
				<?php endif; ?>
			</div>

			<div class="site-footer__col site-footer__col--about">
				<?php if ( '' !== trim( $sbek_description ) ) : ?>
					<h2 class="site-footer__title"><?php esc_html_e( 'About us', 'sbek' ); ?></h2>
					<p><?php echo esc_html( $sbek_description ); ?></p>
				<?php endif; ?>
				<?php
				/**
				 * More content for the "About us" column (a plugin's newsletter
				 * signup, for example).
				 */
				do_action( 'sbek_footer_about' );
				?>
			</div>

			<div class="site-footer__col site-footer__col--social">
				<?php if ( $sbek_socials ) : ?>
					<h2 class="site-footer__title"><?php esc_html_e( 'Follow us', 'sbek' ); ?></h2>
					<ul class="social-links">
						<?php
						foreach ( $sbek_socials as $sbek_social ) :
							if ( empty( $sbek_social['url'] ) ) {
								continue;
							}
							$sbek_platform = (string) ( $sbek_social['platform'] ?? '' );
							?>
							<li>
								<?php
								$sbek_social_name = '' !== trim( $sbek_platform ) ? $sbek_platform : (string) wp_parse_url( $sbek_social['url'], PHP_URL_HOST );
								/* translators: %s: social network name, e.g. "Facebook". */
								$sbek_social_label = sprintf( __( '%s (opens in a new tab)', 'sbek' ), $sbek_social_name );
								?>
								<a href="<?php echo esc_url( $sbek_social['url'] ); ?>" target="_blank" rel="noopener noreferrer me" aria-label="<?php echo esc_attr( $sbek_social_label ); ?>">
									<?php echo sbek_icon( sbek_social_icon_name( $sbek_platform ), array( 'width' => 24, 'height' => 24 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<?php if ( $sbek_email || $sbek_phones ) : ?>
					<p class="site-footer__contact">
						<strong><?php esc_html_e( 'Contact', 'sbek' ); ?></strong>
						<?php if ( $sbek_email ) : ?>
							<br><a href="<?php echo esc_attr( 'mailto:' . antispambot( $sbek_email ) ); ?>"><?php echo esc_html( antispambot( $sbek_email ) ); ?></a>
						<?php endif; ?>
						<?php
						foreach ( $sbek_phones as $sbek_phone ) :
							if ( empty( $sbek_phone['number'] ) ) {
								continue;
							}
							?>
							<br><a href="<?php echo esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', (string) $sbek_phone['number'] ) ); ?>">
								<?php echo esc_html( trim( ( ! empty( $sbek_phone['label'] ) ? $sbek_phone['label'] . ': ' : '' ) . $sbek_phone['number'] ) ); ?>
							</a>
						<?php endforeach; ?>
					</p>
				<?php endif; ?>
			</div>
		</div>
		<?php if ( $sbek_has_links ) : ?>
			<nav class="site-footer__links" aria-label="<?php esc_attr_e( 'Footer', 'sbek' ); ?>">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => $sbek_footer_menu,
						'container'      => false,
						'menu_class'     => 'site-footer__menu',
						'depth'          => 1,
						'fallback_cb'    => false,
					)
				);
				?>
			</nav>
		<?php endif; ?>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
