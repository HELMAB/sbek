<?php
/**
 * Admin dashboard widget: "Theme Setup" checklist.
 *
 * Shows, at a glance, which Sbek Theme fields are still empty so the user
 * knows what is left to configure (logo, share image, location, etc.).
 *
 * @package sbek
 */

defined( 'ABSPATH' ) || exit;

/**
 * Build the checklist of setup items, their completion state and where to
 * fix each one.
 *
 * @return array<int,array{label:string,done:bool,url:string}>
 */
function sbek_setup_checklist(): array {
	$s = sbek_site_setting();

	$has_home_blocks = (bool) get_terms(
		array(
			'taxonomy'   => 'category',
			'hide_empty' => false,
			'number'     => 1,
			'fields'     => 'ids',
			'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => 'sbek_home_order',
					'value'   => 0,
					'compare' => '>',
					'type'    => 'NUMERIC',
				),
			),
		)
	);

	return [
		[
			'label' => __( 'Add the English site title', 'sbek' ),
			'done'  => '' !== trim( (string) ( $s['name_en'] ?? '' ) ),
			'url'   => sbek_theme_options_url( 'general' ),
		],
		[
			'label' => __( 'Upload a site logo', 'sbek' ),
			'done'  => ! empty( $s['logo'] ),
			'url'   => sbek_theme_options_url( 'general' ),
		],
		[
			'label' => __( 'Add a site icon', 'sbek' ),
			'done'  => (bool) get_option( 'site_icon' ),
			'url'   => sbek_theme_options_url( 'general' ),
		],
		[
			'label' => __( 'Add a default share image', 'sbek' ),
			'done'  => ! empty( $s['og_image'] ),
			'url'   => sbek_theme_options_url( 'general' ),
		],
		[
			'label' => __( 'Choose the homepage category blocks', 'sbek' ),
			'done'  => $has_home_blocks,
			'url'   => sbek_theme_options_url( 'homepage' ),
		],
		[
			'label' => __( 'Assign the Khmer and English menus', 'sbek' ),
			'done'  => has_nav_menu( 'primary' ) && has_nav_menu( 'primary-en' ),
			'url'   => admin_url( 'nav-menus.php?action=locations' ),
		],
		[
			'label' => __( 'Write a site description', 'sbek' ),
			'done'  => '' !== trim( (string) ( $s['description'] ?? '' ) ),
			'url'   => sbek_theme_options_url( 'contact' ),
		],
		[
			'label' => __( 'Add a contact email', 'sbek' ),
			'done'  => '' !== ( $s['email'] ?? '' ),
			'url'   => sbek_theme_options_url( 'contact' ),
		],
		[
			'label' => __( 'Add a contact phone number', 'sbek' ),
			'done'  => ! empty( $s['phones'] ),
			'url'   => sbek_theme_options_url( 'contact' ),
		],
		[
			'label' => __( 'Set the newsroom location', 'sbek' ),
			'done'  => '' !== ( $s['lat'] ?? '' ) && '' !== ( $s['lng'] ?? '' ),
			'url'   => sbek_theme_options_url( 'contact' ),
		],
		[
			'label' => __( 'Link a social profile', 'sbek' ),
			'done'  => ! empty( $s['socials'] ),
			'url'   => sbek_theme_options_url( 'social' ),
		],
		[
			'label' => __( 'Connect analytics (GA4 or GTM)', 'sbek' ),
			'done'  => '' !== ( $s['ga4_id'] ?? '' ) || '' !== ( $s['gtm_id'] ?? '' ),
			'url'   => sbek_theme_options_url( 'integrations' ),
		],
	];
}

/**
 * Register the dashboard widget.
 */
add_action( 'wp_dashboard_setup', function (): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	wp_add_dashboard_widget(
		'sbek_setup_widget',
		__( 'Theme Setup', 'sbek' ),
		'sbek_render_setup_widget'
	);
} );

/**
 * Load the admin stylesheet on the dashboard so the widget is styled.
 */
add_action( 'admin_enqueue_scripts', function ( string $hook ): void {
	if ( 'index.php' !== $hook ) {
		return;
	}

	$css = 'assets/css/admin.css';
	wp_enqueue_style(
		'sbek-admin',
		SBEK_URI . '/' . $css,
		[],
		(string) ( file_exists( SBEK_DIR . '/' . $css ) ? filemtime( SBEK_DIR . '/' . $css ) : SBEK_VERSION )
	);
} );

/**
 * Render the setup checklist widget.
 */
function sbek_render_setup_widget(): void {
	$items = sbek_setup_checklist();
	$total = count( $items );
	$done  = count( array_filter( $items, static fn( $i ) => $i['done'] ) );
	$pct   = $total ? (int) round( ( $done / $total ) * 100 ) : 0;
	$all   = $done === $total;

	$settings_url = sbek_theme_options_url();
	?>
	<div class="sbek-setup" style="<?php echo esc_attr( sbek_admin_accent_style() ); ?>">
		<p class="sbek-setup__intro">
			<?php esc_html_e( 'Finish setting up your site. These details power your header, homepage, SEO tags, structured data and footer. Click an item to go straight to it.', 'sbek' ); ?>
		</p>

		<div class="sbek-setup__summary">
			<div class="sbek-setup__track" role="progressbar" aria-valuenow="<?php echo esc_attr( (string) $pct ); ?>" aria-valuemin="0" aria-valuemax="100">
				<span class="sbek-setup__fill<?php echo $all ? ' is-complete' : ''; ?>" style="width:<?php echo esc_attr( (string) $pct ); ?>%"></span>
			</div>
			<span class="sbek-setup__count">
				<?php
				/* translators: 1: completed count, 2: total count. */
				echo esc_html( sprintf( __( '%1$d of %2$d complete', 'sbek' ), $done, $total ) );
				?>
			</span>
		</div>

		<?php if ( $all ) : ?>
			<p class="sbek-setup__done">
				<?php echo sbek_icon( 'rocket', [ 'width' => 18, 'height' => 18 ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
				<?php esc_html_e( 'All set — your site is fully configured!', 'sbek' ); ?>
			</p>
		<?php endif; ?>

		<ul class="sbek-setup__list">
			<?php foreach ( $items as $item ) : ?>
				<li class="sbek-setup__item<?php echo $item['done'] ? ' is-done' : ''; ?>">
					<span class="sbek-setup__icon">
						<?php echo sbek_icon( $item['done'] ? 'circle-check' : 'circle', [ 'width' => 18, 'height' => 18 ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
					</span>
					<a class="sbek-setup__label" href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['label'] ); ?></a>
				</li>
			<?php endforeach; ?>
		</ul>

		<a class="sbek-setup__cta" href="<?php echo esc_url( $settings_url ); ?>">
			<?php echo $all ? esc_html__( 'Review settings', 'sbek' ) : esc_html__( 'Go to Sbek Theme', 'sbek' ); ?>
			<?php echo sbek_icon( 'arrow-right', [ 'width' => 15, 'height' => 15 ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
		</a>
	</div>
	<?php
}
