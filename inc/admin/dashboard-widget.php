<?php
/**
 * Admin dashboard widget: "Theme Setup" checklist.
 *
 * Shows, at a glance, which Theme Option fields are still empty so the user
 * knows what is left to configure (logo, share image, location, etc.).
 *
 * @package sbek
 */

defined( 'ABSPATH' ) || exit;

/**
 * Build the checklist of setup items and their completion state.
 *
 * @return array<int,array{label:string,done:bool}>
 */
function sbek_setup_checklist(): array {
	$s = sbek_site_setting();

	return [
		[
			'label' => __( 'Upload a site logo', 'sbek' ),
			'done'  => ! empty( $s['logo'] ),
		],
		[
			'label' => __( 'Add a default share image', 'sbek' ),
			'done'  => ! empty( $s['og_image'] ),
		],
		[
			'label' => __( 'Set the company location', 'sbek' ),
			'done'  => '' !== ( $s['lat'] ?? '' ) && '' !== ( $s['lng'] ?? '' ),
		],
		[
			'label' => __( 'Write a company description', 'sbek' ),
			'done'  => '' !== trim( (string) ( $s['description'] ?? '' ) ),
		],
		[
			'label' => __( 'Add a site icon', 'sbek' ),
			'done'  => (bool) get_option( 'site_icon' ),
		],
		[
			'label' => __( 'Add a contact email', 'sbek' ),
			'done'  => '' !== ( $s['email'] ?? '' ),
		],
		[
			'label' => __( 'Add a contact phone number', 'sbek' ),
			'done'  => ! empty( $s['phones'] ),
		],
		[
			'label' => __( 'Link a social profile', 'sbek' ),
			'done'  => ! empty( $s['socials'] ),
		],
		[
			'label' => __( 'Connect analytics (GA4 or GTM)', 'sbek' ),
			'done'  => '' !== ( $s['ga4_id'] ?? '' ) || '' !== ( $s['gtm_id'] ?? '' ),
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

	$version = wp_get_theme()->get( 'Version' ) ?: '1.0.0';
	wp_enqueue_style(
		'sbek-admin-settings',
		get_template_directory_uri() . '/assets/css/admin.css',
		[],
		$version
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
	<div class="sbek-setup">
		<p class="sbek-setup__intro">
			<?php esc_html_e( 'Finish setting up your site identity. These details power your header, SEO tags, structured data, and footer.', 'sbek' ); ?>
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
				<?php echo sbek_icon( 'rocket', [ 'width' => 18, 'height' => 18 ] ); ?>
				<?php esc_html_e( 'All set — your site identity is fully configured!', 'sbek' ); ?>
			</p>
		<?php endif; ?>

		<ul class="sbek-setup__list">
			<?php foreach ( $items as $item ) : ?>
				<li class="sbek-setup__item<?php echo $item['done'] ? ' is-done' : ''; ?>">
					<span class="sbek-setup__icon">
						<?php echo sbek_icon( $item['done'] ? 'circle-check' : 'circle', [ 'width' => 18, 'height' => 18 ] ); ?>
					</span>
					<span class="sbek-setup__label"><?php echo esc_html( $item['label'] ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>

		<a class="sbek-setup__cta" href="<?php echo esc_url( $settings_url ); ?>">
			<?php echo $all ? esc_html__( 'Review settings', 'sbek' ) : esc_html__( 'Go to Theme Option', 'sbek' ); ?>
			<?php echo sbek_icon( 'arrow-right', [ 'width' => 15, 'height' => 15 ] ); ?>
		</a>
	</div>
	<?php
}
