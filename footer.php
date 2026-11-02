<?php
/**
 * Site footer: brand + legal, about + newsletter, social + contact.
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
?>
<footer id="footer" class="site-footer">
	<div class="container">
		<div class="site-footer__grid">
			<div class="site-footer__col site-footer__col--brand">
				<a href="<?php echo esc_url( sbek_home_url() ); ?>" class="site-footer__logo" rel="home">
					<?php echo sbek_get_logo( '', 60 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
				</a>
				<p><?php echo esc_html( sbek_copyright_text() ); ?></p>
				<?php if ( $sbek_privacy_url ) : ?>
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
				<h2 class="site-footer__title"><?php esc_html_e( 'About us', 'sbek' ); ?></h2>
				<?php if ( '' !== trim( $sbek_description ) ) : ?>
					<p><?php echo esc_html( $sbek_description ); ?></p>
				<?php endif; ?>
				<?php
				if ( sbek_section_enabled( 'newsletter' ) ) {
					get_template_part( 'template-parts/newsletter-form' );
				}
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
								<a href="<?php echo esc_url( $sbek_social['url'] ); ?>" target="_blank" rel="noopener noreferrer me" aria-label="<?php echo esc_attr( $sbek_platform ); ?>">
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
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
