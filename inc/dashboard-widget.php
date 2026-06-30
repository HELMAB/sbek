<?php
/**
 * Admin dashboard widget: "Theme Setup" checklist.
 *
 * Shows, at a glance, which Options page fields are still empty so the user
 * knows what is left to configure (logo, share image, location, etc.).
 *
 * @package sabay-clone
 */

defined( 'ABSPATH' ) || exit;

/**
 * Build the checklist of setup items and their completion state.
 *
 * @return array<int,array{label:string,done:bool}>
 */
function sabay_setup_checklist(): array {
	$s = sabay_site_setting();

	return [
		[
			'label' => __( 'Upload a site logo', 'sabay-clone' ),
			'done'  => ! empty( $s['logo'] ),
		],
		[
			'label' => __( 'Add a default share image', 'sabay-clone' ),
			'done'  => ! empty( $s['og_image'] ),
		],
		[
			'label' => __( 'Set the company location', 'sabay-clone' ),
			'done'  => '' !== ( $s['lat'] ?? '' ) && '' !== ( $s['lng'] ?? '' ),
		],
		[
			'label' => __( 'Write a company description', 'sabay-clone' ),
			'done'  => '' !== trim( (string) ( $s['description'] ?? '' ) ),
		],
		[
			'label' => __( 'Add a contact phone number', 'sabay-clone' ),
			'done'  => ! empty( $s['phones'] ),
		],
		[
			'label' => __( 'Link a social profile', 'sabay-clone' ),
			'done'  => ! empty( $s['socials'] ),
		],
		[
			'label' => __( 'Connect analytics (GA4 or GTM)', 'sabay-clone' ),
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
		'sabay_setup_widget',
		__( 'Theme Setup', 'sabay-clone' ),
		'sabay_render_setup_widget'
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
		'sabay-admin-settings',
		get_template_directory_uri() . '/assets/css/admin.css',
		[],
		$version
	);
} );

/**
 * Render the setup checklist widget.
 */
function sabay_render_setup_widget(): void {
	$items = sabay_setup_checklist();
	$total = count( $items );
	$done  = count( array_filter( $items, static fn( $i ) => $i['done'] ) );
	$pct   = $total ? (int) round( ( $done / $total ) * 100 ) : 0;
	$all   = $done === $total;

	$settings_url = menu_page_url( SABAY_SETTINGS_SLUG, false );
	if ( ! $settings_url ) {
		$settings_url = admin_url( 'options-general.php?page=' . SABAY_SETTINGS_SLUG );
	}
	?>
	<div class="sabay-setup">
		<p class="sabay-setup__intro">
			<?php esc_html_e( 'Finish setting up your site identity. These details power your header, SEO tags, structured data, and footer.', 'sabay-clone' ); ?>
		</p>

		<div class="sabay-setup__summary">
			<div class="sabay-setup__track" role="progressbar" aria-valuenow="<?php echo esc_attr( (string) $pct ); ?>" aria-valuemin="0" aria-valuemax="100">
				<span class="sabay-setup__fill<?php echo $all ? ' is-complete' : ''; ?>" style="width:<?php echo esc_attr( (string) $pct ); ?>%"></span>
			</div>
			<span class="sabay-setup__count">
				<?php
				/* translators: 1: completed count, 2: total count. */
				echo esc_html( sprintf( __( '%1$d of %2$d complete', 'sabay-clone' ), $done, $total ) );
				?>
			</span>
		</div>

		<?php if ( $all ) : ?>
			<p class="sabay-setup__done">
				<?php echo sabay_icon( 'rocket', [ 'width' => 18, 'height' => 18 ] ); ?>
				<?php esc_html_e( 'All set — your site identity is fully configured!', 'sabay-clone' ); ?>
			</p>
		<?php endif; ?>

		<ul class="sabay-setup__list">
			<?php foreach ( $items as $item ) : ?>
				<li class="sabay-setup__item<?php echo $item['done'] ? ' is-done' : ''; ?>">
					<span class="sabay-setup__icon">
						<?php echo sabay_icon( $item['done'] ? 'circle-check' : 'circle', [ 'width' => 18, 'height' => 18 ] ); ?>
					</span>
					<span class="sabay-setup__label"><?php echo esc_html( $item['label'] ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>

		<a class="sabay-setup__cta" href="<?php echo esc_url( $settings_url ); ?>">
			<?php echo $all ? esc_html__( 'Review settings', 'sabay-clone' ) : esc_html__( 'Go to Options page', 'sabay-clone' ); ?>
			<?php echo sabay_icon( 'arrow-right', [ 'width' => 15, 'height' => 15 ] ); ?>
		</a>
	</div>
	<?php
}
