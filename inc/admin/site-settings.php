<?php
/**
 * Site Settings admin page.
 *
 * Adds an "Options page" submenu under Settings storing global company info in a single
 * option array (`sbek_site_settings`):
 *   - lat / lng        Company location coordinates.
 *   - description      Company description.
 *   - phones[]         Repeatable contact phone numbers (label + number).
 *   - socials[]        Repeatable social links (platform + url).
 *
 * Read anywhere in the theme with sbek_site_setting( 'key' ).
 *
 * @package sbek
 */

defined( 'ABSPATH' ) || exit;

const SBEK_SETTINGS_OPTION = 'sbek_site_settings';
const SBEK_SETTINGS_GROUP  = 'sbek_site_settings_group';
const SBEK_SETTINGS_SLUG   = 'sbek-site-settings';

// ─── 1. Register the option + sanitize callback ──────────────────────────────

add_action( 'admin_init', function (): void {
	register_setting(
		SBEK_SETTINGS_GROUP,
		SBEK_SETTINGS_OPTION,
		[
			'type'              => 'array',
			'sanitize_callback' => 'sbek_sanitize_site_settings',
			'default'           => sbek_site_settings_defaults(),
		]
	);
} );

/**
 * Default shape for the settings array.
 *
 * @return array<string,mixed>
 */
function sbek_site_settings_defaults(): array {
	return [
		'logo'           => 0,
		'og_image'       => 0,
		'lat'            => '',
		'lng'            => '',
		'description'    => '',
		'phones'         => [],
		'socials'        => [],
		'ga4_id'         => '',
		'gtm_id'         => '',
		'header_scripts' => '',
		'footer_scripts' => '',
	];
}

/**
 * Sanitize the whole settings array before it is stored.
 *
 * @param mixed $input Raw $_POST value.
 * @return array<string,mixed>
 */
function sbek_sanitize_site_settings( $input ): array {
	$input = is_array( $input ) ? $input : [];
	$clean = sbek_site_settings_defaults();

	// Media library attachment IDs.
	$clean['logo']     = absint( $input['logo'] ?? 0 );
	$clean['og_image'] = absint( $input['og_image'] ?? 0 );

	// Coordinates — keep only valid floats, blank otherwise.
	$clean['lat'] = isset( $input['lat'] ) && is_numeric( $input['lat'] ) ? (string) (float) $input['lat'] : '';
	$clean['lng'] = isset( $input['lng'] ) && is_numeric( $input['lng'] ) ? (string) (float) $input['lng'] : '';

	$clean['description'] = sanitize_textarea_field( $input['description'] ?? '' );

	// Repeatable phones: drop rows with an empty number.
	if ( ! empty( $input['phones'] ) && is_array( $input['phones'] ) ) {
		foreach ( $input['phones'] as $row ) {
			$number = sanitize_text_field( $row['number'] ?? '' );
			if ( '' === $number ) {
				continue;
			}
			$clean['phones'][] = [
				'label'  => sanitize_text_field( $row['label'] ?? '' ),
				'number' => $number,
			];
		}
	}

	// Repeatable socials: drop rows with an empty URL.
	if ( ! empty( $input['socials'] ) && is_array( $input['socials'] ) ) {
		foreach ( $input['socials'] as $row ) {
			$url = esc_url_raw( $row['url'] ?? '' );
			if ( '' === $url ) {
				continue;
			}
			$clean['socials'][] = [
				'platform' => sanitize_text_field( $row['platform'] ?? '' ),
				'url'      => $url,
			];
		}
	}

	// Analytics IDs.
	$clean['ga4_id'] = sanitize_text_field( $input['ga4_id'] ?? '' );
	$clean['gtm_id'] = sanitize_text_field( $input['gtm_id'] ?? '' );

	// Custom scripts: store raw only for users allowed to post unfiltered HTML
	// (admins on single-site, super admins on multisite); otherwise strip unsafe markup.
	$can_raw                  = current_user_can( 'unfiltered_html' );
	$clean['header_scripts']  = $can_raw ? (string) ( $input['header_scripts'] ?? '' ) : wp_kses_post( $input['header_scripts'] ?? '' );
	$clean['footer_scripts']  = $can_raw ? (string) ( $input['footer_scripts'] ?? '' ) : wp_kses_post( $input['footer_scripts'] ?? '' );

	return $clean;
}

// ─── 2. Accessor helper ───────────────────────────────────────────────────────

/**
 * Read a site setting (or the full array when $key is null).
 *
 * @param string|null $key     Setting key, or null for the whole array.
 * @param mixed       $default Fallback when the key is unset.
 * @return mixed
 */
function sbek_site_setting( ?string $key = null, $default = null ) {
	$settings = wp_parse_args(
		(array) get_option( SBEK_SETTINGS_OPTION, [] ),
		sbek_site_settings_defaults()
	);

	if ( null === $key ) {
		return $settings;
	}

	return $settings[ $key ] ?? $default;
}

/**
 * Resolve a media-library setting (logo, og_image) to a URL.
 *
 * @param string $key  Setting key holding an attachment ID.
 * @param string $size Image size.
 * @return string Image URL, or '' when unset/missing.
 */
function sbek_site_setting_image_url( string $key, string $size = 'full' ): string {
	$id = absint( sbek_site_setting( $key, 0 ) );

	if ( ! $id ) {
		return '';
	}

	return (string) wp_get_attachment_image_url( $id, $size );
}

// ─── 3. Register the admin menu ────────────────────────────────────────────────

add_action( 'admin_menu', function (): void {
	$hook = add_options_page(
		__( 'Options page', 'sbek' ),
		__( 'Options page', 'sbek' ),
		'manage_options',
		SBEK_SETTINGS_SLUG,
		'sbek_render_site_settings_page'
	);

	// Load page-specific assets only on this screen.
	add_action( "admin_print_styles-{$hook}", 'sbek_site_settings_assets' );
} );

/**
 * Enqueue the (preflight-free) Tailwind admin build + repeater JS for this page.
 */
function sbek_site_settings_assets(): void {
	$theme   = wp_get_theme();
	$version = $theme->get( 'Version' ) ?: '1.0.0';

	// WordPress media modal (wp.media) for the logo / OG-image pickers.
	wp_enqueue_media();

	wp_enqueue_style(
		'sbek-admin-settings',
		get_template_directory_uri() . '/assets/css/admin.css',
		[],
		$version
	);

	wp_enqueue_script(
		'sbek-admin-settings',
		get_template_directory_uri() . '/assets/js/admin-site-settings.js',
		[ 'jquery', 'media-editor' ],
		$version,
		true
	);
}

// ─── 4. Render the page ─────────────────────────────────────────────────────

/**
 * Render a media-library picker field as a dropzone with empty / filled states.
 *
 * @param string $opt           Option name.
 * @param string $key           Setting key holding the attachment ID.
 * @param int    $attachment_id Currently stored attachment ID.
 * @param string $select_label  Call-to-action shown in the empty state.
 * @param string $hint          Optional helper text (file types, dimensions…).
 */
function sbek_render_media_field( string $opt, string $key, int $attachment_id, string $select_label, string $hint = '' ): void {
	$url   = $attachment_id ? wp_get_attachment_image_url( $attachment_id, 'medium' ) : '';
	$state = $url ? 'filled' : 'empty';
	?>
	<div class="sbek-media" data-sbek-media-field data-state="<?php echo esc_attr( $state ); ?>">
		<input type="hidden" name="<?php echo esc_attr( $opt ); ?>[<?php echo esc_attr( $key ); ?>]"
			value="<?php echo esc_attr( $attachment_id ?: '' ); ?>" data-sbek-media-input>

		<?php // Empty state — full-area dropzone (click opens the media modal). ?>
		<button type="button" class="sbek-media-dropzone" data-sbek-media-select>
			<span class="sbek-media-dropzone__icon">
				<?php echo sbek_icon( 'image-plus', [ 'width' => 26, 'height' => 26 ] ); ?>
			</span>
			<span class="sbek-media-dropzone__label"><?php echo esc_html( $select_label ); ?></span>
			<?php if ( $hint ) : ?>
				<span class="sbek-media-dropzone__hint"><?php echo esc_html( $hint ); ?></span>
			<?php endif; ?>
		</button>

		<?php // Filled state — framed preview + actions. ?>
		<div class="sbek-media-preview">
			<div class="sbek-media-preview__frame sbek-checkerboard">
				<img src="<?php echo esc_url( $url ); ?>" alt="" class="sbek-media-preview__img" data-sbek-media-preview>
			</div>
			<div class="sbek-media-preview__actions">
				<button type="button" class="sbek-media-btn sbek-media-btn--change" data-sbek-media-select>
					<?php echo sbek_icon( 'image-up', [ 'width' => 15, 'height' => 15 ] ); ?>
					<?php esc_html_e( 'Replace', 'sbek' ); ?>
				</button>
				<button type="button" class="sbek-media-btn sbek-media-btn--remove" data-sbek-media-remove>
					<?php echo sbek_icon( 'trash-2', [ 'width' => 15, 'height' => 15 ] ); ?>
					<?php esc_html_e( 'Remove', 'sbek' ); ?>
				</button>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Open a settings card: a header layer (icon, title, description) followed by
 * an open body layer for the form. Pair with sbek_section_close().
 *
 * @param string $icon        Icon name from sbek_icon_paths().
 * @param string $title       Section title.
 * @param string $description Short description shown under the title.
 * @param string $action      Optional trusted HTML aligned to the header's right (e.g. an Add button).
 */
function sbek_section_open( string $icon, string $title, string $description, string $action = '' ): void {
	?>
	<section class="sbek-card">
		<header class="sbek-card__head">
			<span class="sbek-card__icon"><?php echo sbek_icon( $icon, [ 'width' => 20, 'height' => 20 ] ); ?></span>
			<div class="sbek-card__heading">
				<h2 class="sbek-card__title"><?php echo esc_html( $title ); ?></h2>
				<p class="sbek-card__desc"><?php echo esc_html( $description ); ?></p>
			</div>
			<?php if ( $action ) : ?>
				<div class="sbek-card__action"><?php echo $action; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted markup ?></div>
			<?php endif; ?>
		</header>
		<div class="sbek-card__body">
	<?php
}

/**
 * Close a settings card opened with sbek_section_open().
 */
function sbek_section_close(): void {
	?>
		</div>
	</section>
	<?php
}

/**
 * Build a repeater "Add" button for a header action slot.
 *
 * @param string $group Repeater group key (e.g. "phones").
 * @return string Button markup.
 */
function sbek_add_button( string $group ): string {
	return sprintf(
		'<button type="button" class="sbek-card__add" data-sbek-add="%s">%s<span>%s</span></button>',
		esc_attr( $group ),
		sbek_icon( 'plus', [ 'width' => 15, 'height' => 15 ] ),
		esc_html__( 'Add', 'sbek' )
	);
}

function sbek_render_site_settings_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'sbek' ) );
	}

	$settings = sbek_site_setting();
	$opt      = SBEK_SETTINGS_OPTION;
	?>
	<div class="wrap">
		<div class="sbek-admin max-w-3xl mx-auto font-sans text-gray-800">
			<h1 class="text-2xl font-bold mb-1"><?php esc_html_e( 'Options page', 'sbek' ); ?></h1>
			<p class="text-sm text-gray-500 mb-6"><?php esc_html_e( 'Global company information used across the site.', 'sbek' ); ?></p>

			<form method="post" action="options.php" class="space-y-6">
				<?php settings_fields( SBEK_SETTINGS_GROUP ); ?>

				<!-- Branding -->
				<?php sbek_section_open( 'image', __( 'Branding', 'sbek' ), __( 'Your logo and the default image shown when pages are shared on social media.', 'sbek' ) ); ?>
					<div class="space-y-5">
						<div>
							<span class="block text-sm font-semibold mb-1"><?php esc_html_e( 'Site Logo', 'sbek' ); ?></span>
							<p class="text-xs text-gray-500 mb-2"><?php esc_html_e( 'Shown in the site header and used in structured data.', 'sbek' ); ?></p>
							<?php sbek_render_media_field( $opt, 'logo', (int) $settings['logo'], __( 'Upload or choose a logo', 'sbek' ), __( 'PNG, JPG or SVG · transparent background recommended', 'sbek' ) ); ?>
						</div>
						<div class="border-t border-solid border-gray-100 pt-5">
							<span class="block text-sm font-semibold mb-1"><?php esc_html_e( 'Default Share Image', 'sbek' ); ?></span>
							<p class="text-xs text-gray-500 mb-2"><?php esc_html_e( 'Fallback Open Graph image for pages with no featured image.', 'sbek' ); ?></p>
							<?php sbek_render_media_field( $opt, 'og_image', (int) $settings['og_image'], __( 'Upload or choose an image', 'sbek' ), __( 'JPG or PNG · 1200×630px recommended', 'sbek' ) ); ?>
						</div>
					</div>
				<?php sbek_section_close(); ?>

				<!-- Location -->
				<?php sbek_section_open( 'map-pin', __( 'Company Location', 'sbek' ), __( 'Coordinates of your office, used in maps and structured data.', 'sbek' ) ); ?>
					<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
						<label class="block">
							<span class="block text-sm font-semibold mb-1"><?php esc_html_e( 'Latitude', 'sbek' ); ?></span>
							<input type="text" inputmode="decimal" name="<?php echo esc_attr( $opt ); ?>[lat]"
								value="<?php echo esc_attr( $settings['lat'] ); ?>" placeholder="11.5564"
								class="w-full rounded-md border border-solid border-gray-300 px-3 py-2 text-sm focus:border-sbek-blue focus:outline-hidden">
						</label>
						<label class="block">
							<span class="block text-sm font-semibold mb-1"><?php esc_html_e( 'Longitude', 'sbek' ); ?></span>
							<input type="text" inputmode="decimal" name="<?php echo esc_attr( $opt ); ?>[lng]"
								value="<?php echo esc_attr( $settings['lng'] ); ?>" placeholder="104.9282"
								class="w-full rounded-md border border-solid border-gray-300 px-3 py-2 text-sm focus:border-sbek-blue focus:outline-hidden">
						</label>
					</div>
				<?php sbek_section_close(); ?>

				<!-- Description -->
				<?php sbek_section_open( 'info', __( 'Company Description', 'sbek' ), __( 'A short summary of your organization for SEO and structured data.', 'sbek' ) ); ?>
					<textarea name="<?php echo esc_attr( $opt ); ?>[description]" rows="4"
						class="w-full rounded-md border border-solid border-gray-300 px-3 py-2 text-sm focus:border-sbek-blue focus:outline-hidden"
						placeholder="<?php esc_attr_e( 'A short description of the company…', 'sbek' ); ?>"><?php echo esc_textarea( $settings['description'] ); ?></textarea>
				<?php sbek_section_close(); ?>

				<!-- Phone numbers (repeatable) -->
				<?php sbek_section_open( 'phone', __( 'Contact Phone Numbers', 'sbek' ), __( 'Public contact numbers shown across the site. Add as many as you need.', 'sbek' ), sbek_add_button( 'phones' ) ); ?>
					<div data-sbek-rows="phones" class="space-y-3">
						<?php
						$phones = ! empty( $settings['phones'] ) ? $settings['phones'] : [ [ 'label' => '', 'number' => '' ] ];
						foreach ( $phones as $i => $row ) :
							?>
							<div class="flex flex-wrap items-center gap-2" data-sbek-row>
								<input type="text" name="<?php echo esc_attr( $opt ); ?>[phones][<?php echo (int) $i; ?>][label]"
									value="<?php echo esc_attr( $row['label'] ?? '' ); ?>"
									placeholder="<?php esc_attr_e( 'Label (e.g. Sales)', 'sbek' ); ?>"
									class="flex-1 min-w-32 rounded-md border border-solid border-gray-300 px-3 py-2 text-sm focus:border-sbek-blue focus:outline-hidden">
								<input type="text" name="<?php echo esc_attr( $opt ); ?>[phones][<?php echo (int) $i; ?>][number]"
									value="<?php echo esc_attr( $row['number'] ?? '' ); ?>"
									placeholder="+855 12 345 678"
									class="flex-1 min-w-40 rounded-md border border-solid border-gray-300 px-3 py-2 text-sm focus:border-sbek-blue focus:outline-hidden">
								<button type="button" data-sbek-remove
									class="cursor-pointer shrink-0 text-gray-400 hover:text-sbek-red bg-transparent border-0 p-1" aria-label="<?php esc_attr_e( 'Remove', 'sbek' ); ?>">
									<?php echo sbek_icon( 'trash-2', [ 'width' => 18, 'height' => 18 ] ); ?>
								</button>
							</div>
						<?php endforeach; ?>
					</div>
				<?php sbek_section_close(); ?>

				<!-- Social links (repeatable) -->
				<?php sbek_section_open( 'share-2', __( 'Social Links', 'sbek' ), __( 'Links to your social profiles, displayed in the site footer.', 'sbek' ), sbek_add_button( 'socials' ) ); ?>
					<div data-sbek-rows="socials" class="space-y-3">
						<?php
						$socials = ! empty( $settings['socials'] ) ? $settings['socials'] : [ [ 'platform' => '', 'url' => '' ] ];
						foreach ( $socials as $i => $row ) :
							?>
							<div class="flex flex-wrap items-center gap-2" data-sbek-row>
								<input type="text" name="<?php echo esc_attr( $opt ); ?>[socials][<?php echo (int) $i; ?>][platform]"
									value="<?php echo esc_attr( $row['platform'] ?? '' ); ?>"
									placeholder="<?php esc_attr_e( 'Platform (e.g. Facebook)', 'sbek' ); ?>"
									class="flex-1 min-w-32 rounded-md border border-solid border-gray-300 px-3 py-2 text-sm focus:border-sbek-blue focus:outline-hidden">
								<input type="url" name="<?php echo esc_attr( $opt ); ?>[socials][<?php echo (int) $i; ?>][url]"
									value="<?php echo esc_attr( $row['url'] ?? '' ); ?>"
									placeholder="https://facebook.com/yourpage"
									class="flex-1 min-w-48 rounded-md border border-solid border-gray-300 px-3 py-2 text-sm focus:border-sbek-blue focus:outline-hidden">
								<button type="button" data-sbek-remove
									class="cursor-pointer shrink-0 text-gray-400 hover:text-sbek-red bg-transparent border-0 p-1" aria-label="<?php esc_attr_e( 'Remove', 'sbek' ); ?>">
									<?php echo sbek_icon( 'trash-2', [ 'width' => 18, 'height' => 18 ] ); ?>
								</button>
							</div>
						<?php endforeach; ?>
					</div>
				<?php sbek_section_close(); ?>

				<!-- Analytics & Scripts -->
				<?php sbek_section_open( 'trending-up', __( 'Analytics & Scripts', 'sbek' ), __( 'Tracking IDs and custom code injected site-wide.', 'sbek' ) ); ?>
					<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-5">
						<label class="block">
							<span class="block text-sm font-semibold mb-1"><?php esc_html_e( 'Google Analytics 4 ID', 'sbek' ); ?></span>
							<input type="text" name="<?php echo esc_attr( $opt ); ?>[ga4_id]"
								value="<?php echo esc_attr( $settings['ga4_id'] ); ?>" placeholder="G-XXXXXXXXXX"
								class="w-full rounded-md border border-solid border-gray-300 px-3 py-2 text-sm focus:border-sbek-blue focus:outline-hidden">
						</label>
						<label class="block">
							<span class="block text-sm font-semibold mb-1"><?php esc_html_e( 'Google Tag Manager ID', 'sbek' ); ?></span>
							<input type="text" name="<?php echo esc_attr( $opt ); ?>[gtm_id]"
								value="<?php echo esc_attr( $settings['gtm_id'] ); ?>" placeholder="GTM-XXXXXXX"
								class="w-full rounded-md border border-solid border-gray-300 px-3 py-2 text-sm focus:border-sbek-blue focus:outline-hidden">
						</label>
					</div>
					<label class="block mb-5">
						<span class="block text-sm font-semibold mb-1"><?php esc_html_e( 'Header Scripts', 'sbek' ); ?></span>
						<p class="text-xs text-gray-500 mb-2"><?php esc_html_e( 'Raw HTML/JS injected into <head> on every page.', 'sbek' ); ?></p>
						<textarea name="<?php echo esc_attr( $opt ); ?>[header_scripts]" rows="4"
							class="w-full font-mono text-xs rounded-md border border-solid border-gray-300 px-3 py-2 focus:border-sbek-blue focus:outline-hidden"><?php echo esc_textarea( $settings['header_scripts'] ); ?></textarea>
					</label>
					<label class="block">
						<span class="block text-sm font-semibold mb-1"><?php esc_html_e( 'Footer Scripts', 'sbek' ); ?></span>
						<p class="text-xs text-gray-500 mb-2"><?php esc_html_e( 'Raw HTML/JS injected before the closing </body> tag.', 'sbek' ); ?></p>
						<textarea name="<?php echo esc_attr( $opt ); ?>[footer_scripts]" rows="4"
							class="w-full font-mono text-xs rounded-md border border-solid border-gray-300 px-3 py-2 focus:border-sbek-blue focus:outline-hidden"><?php echo esc_textarea( $settings['footer_scripts'] ); ?></textarea>
						<?php if ( ! current_user_can( 'unfiltered_html' ) ) : ?>
							<p class="text-xs text-amber-600 mt-2"><?php esc_html_e( 'Your role cannot save raw scripts; unsafe markup will be stripped on save.', 'sbek' ); ?></p>
						<?php endif; ?>
					</label>
				<?php sbek_section_close(); ?>

				<div>
					<button type="submit"
						class="cursor-pointer text-sm font-bold text-white bg-sbek-red hover:opacity-90 border-0 rounded-md px-5 py-2.5">
						<?php esc_html_e( 'Save Settings', 'sbek' ); ?>
					</button>
				</div>
			</form>
		</div>
	</div>

	<?php // Row templates for the JS repeater (rendered once, hidden). ?>
	<template id="sbek-tmpl-phones">
		<div class="flex flex-wrap items-center gap-2" data-sbek-row>
			<input type="text" data-name="<?php echo esc_attr( $opt ); ?>[phones][__i__][label]"
				placeholder="<?php esc_attr_e( 'Label (e.g. Sales)', 'sbek' ); ?>"
				class="flex-1 min-w-32 rounded-md border border-solid border-gray-300 px-3 py-2 text-sm focus:border-sbek-blue focus:outline-hidden">
			<input type="text" data-name="<?php echo esc_attr( $opt ); ?>[phones][__i__][number]"
				placeholder="+855 12 345 678"
				class="flex-1 min-w-40 rounded-md border border-solid border-gray-300 px-3 py-2 text-sm focus:border-sbek-blue focus:outline-hidden">
			<button type="button" data-sbek-remove
				class="cursor-pointer shrink-0 text-gray-400 hover:text-sbek-red bg-transparent border-0 p-1" aria-label="<?php esc_attr_e( 'Remove', 'sbek' ); ?>">
				<?php echo sbek_icon( 'trash-2', [ 'width' => 18, 'height' => 18 ] ); ?>
			</button>
		</div>
	</template>

	<template id="sbek-tmpl-socials">
		<div class="flex flex-wrap items-center gap-2" data-sbek-row>
			<input type="text" data-name="<?php echo esc_attr( $opt ); ?>[socials][__i__][platform]"
				placeholder="<?php esc_attr_e( 'Platform (e.g. Facebook)', 'sbek' ); ?>"
				class="flex-1 min-w-32 rounded-md border border-solid border-gray-300 px-3 py-2 text-sm focus:border-sbek-blue focus:outline-hidden">
			<input type="url" data-name="<?php echo esc_attr( $opt ); ?>[socials][__i__][url]"
				placeholder="https://facebook.com/yourpage"
				class="flex-1 min-w-48 rounded-md border border-solid border-gray-300 px-3 py-2 text-sm focus:border-sbek-blue focus:outline-hidden">
			<button type="button" data-sbek-remove
				class="cursor-pointer shrink-0 text-gray-400 hover:text-sbek-red bg-transparent border-0 p-1" aria-label="<?php esc_attr_e( 'Remove', 'sbek' ); ?>">
				<?php echo sbek_icon( 'trash-2', [ 'width' => 18, 'height' => 18 ] ); ?>
			</button>
		</div>
	</template>
	<?php
}
