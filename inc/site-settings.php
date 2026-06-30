<?php
/**
 * Site Settings admin page.
 *
 * Adds an "Options page" submenu under Settings storing global company info in a single
 * option array (`sabay_site_settings`):
 *   - lat / lng        Company location coordinates.
 *   - description      Company description.
 *   - phones[]         Repeatable contact phone numbers (label + number).
 *   - socials[]        Repeatable social links (platform + url).
 *
 * Read anywhere in the theme with sabay_site_setting( 'key' ).
 *
 * @package sabay-clone
 */

defined( 'ABSPATH' ) || exit;

const SABAY_SETTINGS_OPTION = 'sabay_site_settings';
const SABAY_SETTINGS_GROUP  = 'sabay_site_settings_group';
const SABAY_SETTINGS_SLUG   = 'sabay-site-settings';

// ─── 1. Register the option + sanitize callback ──────────────────────────────

add_action( 'admin_init', function (): void {
	register_setting(
		SABAY_SETTINGS_GROUP,
		SABAY_SETTINGS_OPTION,
		[
			'type'              => 'array',
			'sanitize_callback' => 'sabay_sanitize_site_settings',
			'default'           => sabay_site_settings_defaults(),
		]
	);
} );

/**
 * Default shape for the settings array.
 *
 * @return array<string,mixed>
 */
function sabay_site_settings_defaults(): array {
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
function sabay_sanitize_site_settings( $input ): array {
	$input = is_array( $input ) ? $input : [];
	$clean = sabay_site_settings_defaults();

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
function sabay_site_setting( ?string $key = null, $default = null ) {
	$settings = wp_parse_args(
		(array) get_option( SABAY_SETTINGS_OPTION, [] ),
		sabay_site_settings_defaults()
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
function sabay_site_setting_image_url( string $key, string $size = 'full' ): string {
	$id = absint( sabay_site_setting( $key, 0 ) );

	if ( ! $id ) {
		return '';
	}

	return (string) wp_get_attachment_image_url( $id, $size );
}

// ─── 3. Register the admin menu ────────────────────────────────────────────────

add_action( 'admin_menu', function (): void {
	$hook = add_options_page(
		__( 'Options page', 'sabay-clone' ),
		__( 'Options page', 'sabay-clone' ),
		'manage_options',
		SABAY_SETTINGS_SLUG,
		'sabay_render_site_settings_page'
	);

	// Load page-specific assets only on this screen.
	add_action( "admin_print_styles-{$hook}", 'sabay_site_settings_assets' );
} );

/**
 * Enqueue the (preflight-free) Tailwind admin build + repeater JS for this page.
 */
function sabay_site_settings_assets(): void {
	$theme   = wp_get_theme();
	$version = $theme->get( 'Version' ) ?: '1.0.0';

	// WordPress media modal (wp.media) for the logo / OG-image pickers.
	wp_enqueue_media();

	wp_enqueue_style(
		'sabay-admin-settings',
		get_template_directory_uri() . '/assets/css/admin.css',
		[],
		$version
	);

	wp_enqueue_script(
		'sabay-admin-settings',
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
function sabay_render_media_field( string $opt, string $key, int $attachment_id, string $select_label, string $hint = '' ): void {
	$url   = $attachment_id ? wp_get_attachment_image_url( $attachment_id, 'medium' ) : '';
	$state = $url ? 'filled' : 'empty';
	?>
	<div class="sabay-media" data-sabay-media-field data-state="<?php echo esc_attr( $state ); ?>">
		<input type="hidden" name="<?php echo esc_attr( $opt ); ?>[<?php echo esc_attr( $key ); ?>]"
			value="<?php echo esc_attr( $attachment_id ?: '' ); ?>" data-sabay-media-input>

		<?php // Empty state — full-area dropzone (click opens the media modal). ?>
		<button type="button" class="sabay-media-dropzone" data-sabay-media-select>
			<span class="sabay-media-dropzone__icon">
				<?php echo sabay_icon( 'image-plus', [ 'width' => 26, 'height' => 26 ] ); ?>
			</span>
			<span class="sabay-media-dropzone__label"><?php echo esc_html( $select_label ); ?></span>
			<?php if ( $hint ) : ?>
				<span class="sabay-media-dropzone__hint"><?php echo esc_html( $hint ); ?></span>
			<?php endif; ?>
		</button>

		<?php // Filled state — framed preview + actions. ?>
		<div class="sabay-media-preview">
			<div class="sabay-media-preview__frame sabay-checkerboard">
				<img src="<?php echo esc_url( $url ); ?>" alt="" class="sabay-media-preview__img" data-sabay-media-preview>
			</div>
			<div class="sabay-media-preview__actions">
				<button type="button" class="sabay-media-btn sabay-media-btn--change" data-sabay-media-select>
					<?php echo sabay_icon( 'image-up', [ 'width' => 15, 'height' => 15 ] ); ?>
					<?php esc_html_e( 'Replace', 'sabay-clone' ); ?>
				</button>
				<button type="button" class="sabay-media-btn sabay-media-btn--remove" data-sabay-media-remove>
					<?php echo sabay_icon( 'trash-2', [ 'width' => 15, 'height' => 15 ] ); ?>
					<?php esc_html_e( 'Remove', 'sabay-clone' ); ?>
				</button>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Open a settings card: a header layer (icon, title, description) followed by
 * an open body layer for the form. Pair with sabay_section_close().
 *
 * @param string $icon        Icon name from sabay_icon_paths().
 * @param string $title       Section title.
 * @param string $description Short description shown under the title.
 * @param string $action      Optional trusted HTML aligned to the header's right (e.g. an Add button).
 */
function sabay_section_open( string $icon, string $title, string $description, string $action = '' ): void {
	?>
	<section class="sabay-card">
		<header class="sabay-card__head">
			<span class="sabay-card__icon"><?php echo sabay_icon( $icon, [ 'width' => 20, 'height' => 20 ] ); ?></span>
			<div class="sabay-card__heading">
				<h2 class="sabay-card__title"><?php echo esc_html( $title ); ?></h2>
				<p class="sabay-card__desc"><?php echo esc_html( $description ); ?></p>
			</div>
			<?php if ( $action ) : ?>
				<div class="sabay-card__action"><?php echo $action; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted markup ?></div>
			<?php endif; ?>
		</header>
		<div class="sabay-card__body">
	<?php
}

/**
 * Close a settings card opened with sabay_section_open().
 */
function sabay_section_close(): void {
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
function sabay_add_button( string $group ): string {
	return sprintf(
		'<button type="button" class="sabay-card__add" data-sabay-add="%s">%s<span>%s</span></button>',
		esc_attr( $group ),
		sabay_icon( 'plus', [ 'width' => 15, 'height' => 15 ] ),
		esc_html__( 'Add', 'sabay-clone' )
	);
}

function sabay_render_site_settings_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'sabay-clone' ) );
	}

	$settings = sabay_site_setting();
	$opt      = SABAY_SETTINGS_OPTION;
	?>
	<div class="wrap">
		<div class="sabay-admin max-w-3xl mx-auto font-sans text-gray-800">
			<h1 class="text-2xl font-bold mb-1"><?php esc_html_e( 'Options page', 'sabay-clone' ); ?></h1>
			<p class="text-sm text-gray-500 mb-6"><?php esc_html_e( 'Global company information used across the site.', 'sabay-clone' ); ?></p>

			<form method="post" action="options.php" class="space-y-6">
				<?php settings_fields( SABAY_SETTINGS_GROUP ); ?>

				<!-- Branding -->
				<?php sabay_section_open( 'image', __( 'Branding', 'sabay-clone' ), __( 'Your logo and the default image shown when pages are shared on social media.', 'sabay-clone' ) ); ?>
					<div class="space-y-5">
						<div>
							<span class="block text-sm font-semibold mb-1"><?php esc_html_e( 'Site Logo', 'sabay-clone' ); ?></span>
							<p class="text-xs text-gray-500 mb-2"><?php esc_html_e( 'Shown in the site header and used in structured data.', 'sabay-clone' ); ?></p>
							<?php sabay_render_media_field( $opt, 'logo', (int) $settings['logo'], __( 'Upload or choose a logo', 'sabay-clone' ), __( 'PNG, JPG or SVG · transparent background recommended', 'sabay-clone' ) ); ?>
						</div>
						<div class="border-t border-solid border-gray-100 pt-5">
							<span class="block text-sm font-semibold mb-1"><?php esc_html_e( 'Default Share Image', 'sabay-clone' ); ?></span>
							<p class="text-xs text-gray-500 mb-2"><?php esc_html_e( 'Fallback Open Graph image for pages with no featured image.', 'sabay-clone' ); ?></p>
							<?php sabay_render_media_field( $opt, 'og_image', (int) $settings['og_image'], __( 'Upload or choose an image', 'sabay-clone' ), __( 'JPG or PNG · 1200×630px recommended', 'sabay-clone' ) ); ?>
						</div>
					</div>
				<?php sabay_section_close(); ?>

				<!-- Location -->
				<?php sabay_section_open( 'map-pin', __( 'Company Location', 'sabay-clone' ), __( 'Coordinates of your office, used in maps and structured data.', 'sabay-clone' ) ); ?>
					<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
						<label class="block">
							<span class="block text-sm font-semibold mb-1"><?php esc_html_e( 'Latitude', 'sabay-clone' ); ?></span>
							<input type="text" inputmode="decimal" name="<?php echo esc_attr( $opt ); ?>[lat]"
								value="<?php echo esc_attr( $settings['lat'] ); ?>" placeholder="11.5564"
								class="w-full rounded-md border border-solid border-gray-300 px-3 py-2 text-sm focus:border-sabay-blue focus:outline-none">
						</label>
						<label class="block">
							<span class="block text-sm font-semibold mb-1"><?php esc_html_e( 'Longitude', 'sabay-clone' ); ?></span>
							<input type="text" inputmode="decimal" name="<?php echo esc_attr( $opt ); ?>[lng]"
								value="<?php echo esc_attr( $settings['lng'] ); ?>" placeholder="104.9282"
								class="w-full rounded-md border border-solid border-gray-300 px-3 py-2 text-sm focus:border-sabay-blue focus:outline-none">
						</label>
					</div>
				<?php sabay_section_close(); ?>

				<!-- Description -->
				<?php sabay_section_open( 'info', __( 'Company Description', 'sabay-clone' ), __( 'A short summary of your organization for SEO and structured data.', 'sabay-clone' ) ); ?>
					<textarea name="<?php echo esc_attr( $opt ); ?>[description]" rows="4"
						class="w-full rounded-md border border-solid border-gray-300 px-3 py-2 text-sm focus:border-sabay-blue focus:outline-none"
						placeholder="<?php esc_attr_e( 'A short description of the company…', 'sabay-clone' ); ?>"><?php echo esc_textarea( $settings['description'] ); ?></textarea>
				<?php sabay_section_close(); ?>

				<!-- Phone numbers (repeatable) -->
				<?php sabay_section_open( 'phone', __( 'Contact Phone Numbers', 'sabay-clone' ), __( 'Public contact numbers shown across the site. Add as many as you need.', 'sabay-clone' ), sabay_add_button( 'phones' ) ); ?>
					<div data-sabay-rows="phones" class="space-y-3">
						<?php
						$phones = ! empty( $settings['phones'] ) ? $settings['phones'] : [ [ 'label' => '', 'number' => '' ] ];
						foreach ( $phones as $i => $row ) :
							?>
							<div class="flex flex-wrap items-center gap-2" data-sabay-row>
								<input type="text" name="<?php echo esc_attr( $opt ); ?>[phones][<?php echo (int) $i; ?>][label]"
									value="<?php echo esc_attr( $row['label'] ?? '' ); ?>"
									placeholder="<?php esc_attr_e( 'Label (e.g. Sales)', 'sabay-clone' ); ?>"
									class="flex-1 min-w-[8rem] rounded-md border border-solid border-gray-300 px-3 py-2 text-sm focus:border-sabay-blue focus:outline-none">
								<input type="text" name="<?php echo esc_attr( $opt ); ?>[phones][<?php echo (int) $i; ?>][number]"
									value="<?php echo esc_attr( $row['number'] ?? '' ); ?>"
									placeholder="+855 12 345 678"
									class="flex-1 min-w-[10rem] rounded-md border border-solid border-gray-300 px-3 py-2 text-sm focus:border-sabay-blue focus:outline-none">
								<button type="button" data-sabay-remove
									class="cursor-pointer shrink-0 text-gray-400 hover:text-sabay-red bg-transparent border-0 p-1" aria-label="<?php esc_attr_e( 'Remove', 'sabay-clone' ); ?>">
									<?php echo sabay_icon( 'trash-2', [ 'width' => 18, 'height' => 18 ] ); ?>
								</button>
							</div>
						<?php endforeach; ?>
					</div>
				<?php sabay_section_close(); ?>

				<!-- Social links (repeatable) -->
				<?php sabay_section_open( 'share-2', __( 'Social Links', 'sabay-clone' ), __( 'Links to your social profiles, displayed in the site footer.', 'sabay-clone' ), sabay_add_button( 'socials' ) ); ?>
					<div data-sabay-rows="socials" class="space-y-3">
						<?php
						$socials = ! empty( $settings['socials'] ) ? $settings['socials'] : [ [ 'platform' => '', 'url' => '' ] ];
						foreach ( $socials as $i => $row ) :
							?>
							<div class="flex flex-wrap items-center gap-2" data-sabay-row>
								<input type="text" name="<?php echo esc_attr( $opt ); ?>[socials][<?php echo (int) $i; ?>][platform]"
									value="<?php echo esc_attr( $row['platform'] ?? '' ); ?>"
									placeholder="<?php esc_attr_e( 'Platform (e.g. Facebook)', 'sabay-clone' ); ?>"
									class="flex-1 min-w-[8rem] rounded-md border border-solid border-gray-300 px-3 py-2 text-sm focus:border-sabay-blue focus:outline-none">
								<input type="url" name="<?php echo esc_attr( $opt ); ?>[socials][<?php echo (int) $i; ?>][url]"
									value="<?php echo esc_attr( $row['url'] ?? '' ); ?>"
									placeholder="https://facebook.com/yourpage"
									class="flex-1 min-w-[12rem] rounded-md border border-solid border-gray-300 px-3 py-2 text-sm focus:border-sabay-blue focus:outline-none">
								<button type="button" data-sabay-remove
									class="cursor-pointer shrink-0 text-gray-400 hover:text-sabay-red bg-transparent border-0 p-1" aria-label="<?php esc_attr_e( 'Remove', 'sabay-clone' ); ?>">
									<?php echo sabay_icon( 'trash-2', [ 'width' => 18, 'height' => 18 ] ); ?>
								</button>
							</div>
						<?php endforeach; ?>
					</div>
				<?php sabay_section_close(); ?>

				<!-- Analytics & Scripts -->
				<?php sabay_section_open( 'trending-up', __( 'Analytics & Scripts', 'sabay-clone' ), __( 'Tracking IDs and custom code injected site-wide.', 'sabay-clone' ) ); ?>
					<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-5">
						<label class="block">
							<span class="block text-sm font-semibold mb-1"><?php esc_html_e( 'Google Analytics 4 ID', 'sabay-clone' ); ?></span>
							<input type="text" name="<?php echo esc_attr( $opt ); ?>[ga4_id]"
								value="<?php echo esc_attr( $settings['ga4_id'] ); ?>" placeholder="G-XXXXXXXXXX"
								class="w-full rounded-md border border-solid border-gray-300 px-3 py-2 text-sm focus:border-sabay-blue focus:outline-none">
						</label>
						<label class="block">
							<span class="block text-sm font-semibold mb-1"><?php esc_html_e( 'Google Tag Manager ID', 'sabay-clone' ); ?></span>
							<input type="text" name="<?php echo esc_attr( $opt ); ?>[gtm_id]"
								value="<?php echo esc_attr( $settings['gtm_id'] ); ?>" placeholder="GTM-XXXXXXX"
								class="w-full rounded-md border border-solid border-gray-300 px-3 py-2 text-sm focus:border-sabay-blue focus:outline-none">
						</label>
					</div>
					<label class="block mb-5">
						<span class="block text-sm font-semibold mb-1"><?php esc_html_e( 'Header Scripts', 'sabay-clone' ); ?></span>
						<p class="text-xs text-gray-500 mb-2"><?php esc_html_e( 'Raw HTML/JS injected into <head> on every page.', 'sabay-clone' ); ?></p>
						<textarea name="<?php echo esc_attr( $opt ); ?>[header_scripts]" rows="4"
							class="w-full font-mono text-xs rounded-md border border-solid border-gray-300 px-3 py-2 focus:border-sabay-blue focus:outline-none"><?php echo esc_textarea( $settings['header_scripts'] ); ?></textarea>
					</label>
					<label class="block">
						<span class="block text-sm font-semibold mb-1"><?php esc_html_e( 'Footer Scripts', 'sabay-clone' ); ?></span>
						<p class="text-xs text-gray-500 mb-2"><?php esc_html_e( 'Raw HTML/JS injected before the closing </body> tag.', 'sabay-clone' ); ?></p>
						<textarea name="<?php echo esc_attr( $opt ); ?>[footer_scripts]" rows="4"
							class="w-full font-mono text-xs rounded-md border border-solid border-gray-300 px-3 py-2 focus:border-sabay-blue focus:outline-none"><?php echo esc_textarea( $settings['footer_scripts'] ); ?></textarea>
						<?php if ( ! current_user_can( 'unfiltered_html' ) ) : ?>
							<p class="text-xs text-amber-600 mt-2"><?php esc_html_e( 'Your role cannot save raw scripts; unsafe markup will be stripped on save.', 'sabay-clone' ); ?></p>
						<?php endif; ?>
					</label>
				<?php sabay_section_close(); ?>

				<div>
					<button type="submit"
						class="cursor-pointer text-sm font-bold text-white bg-sabay-red hover:opacity-90 border-0 rounded-md px-5 py-2.5">
						<?php esc_html_e( 'Save Settings', 'sabay-clone' ); ?>
					</button>
				</div>
			</form>
		</div>
	</div>

	<?php // Row templates for the JS repeater (rendered once, hidden). ?>
	<template id="sabay-tmpl-phones">
		<div class="flex flex-wrap items-center gap-2" data-sabay-row>
			<input type="text" data-name="<?php echo esc_attr( $opt ); ?>[phones][__i__][label]"
				placeholder="<?php esc_attr_e( 'Label (e.g. Sales)', 'sabay-clone' ); ?>"
				class="flex-1 min-w-[8rem] rounded-md border border-solid border-gray-300 px-3 py-2 text-sm focus:border-sabay-blue focus:outline-none">
			<input type="text" data-name="<?php echo esc_attr( $opt ); ?>[phones][__i__][number]"
				placeholder="+855 12 345 678"
				class="flex-1 min-w-[10rem] rounded-md border border-solid border-gray-300 px-3 py-2 text-sm focus:border-sabay-blue focus:outline-none">
			<button type="button" data-sabay-remove
				class="cursor-pointer shrink-0 text-gray-400 hover:text-sabay-red bg-transparent border-0 p-1" aria-label="<?php esc_attr_e( 'Remove', 'sabay-clone' ); ?>">
				<?php echo sabay_icon( 'trash-2', [ 'width' => 18, 'height' => 18 ] ); ?>
			</button>
		</div>
	</template>

	<template id="sabay-tmpl-socials">
		<div class="flex flex-wrap items-center gap-2" data-sabay-row>
			<input type="text" data-name="<?php echo esc_attr( $opt ); ?>[socials][__i__][platform]"
				placeholder="<?php esc_attr_e( 'Platform (e.g. Facebook)', 'sabay-clone' ); ?>"
				class="flex-1 min-w-[8rem] rounded-md border border-solid border-gray-300 px-3 py-2 text-sm focus:border-sabay-blue focus:outline-none">
			<input type="url" data-name="<?php echo esc_attr( $opt ); ?>[socials][__i__][url]"
				placeholder="https://facebook.com/yourpage"
				class="flex-1 min-w-[12rem] rounded-md border border-solid border-gray-300 px-3 py-2 text-sm focus:border-sabay-blue focus:outline-none">
			<button type="button" data-sabay-remove
				class="cursor-pointer shrink-0 text-gray-400 hover:text-sabay-red bg-transparent border-0 p-1" aria-label="<?php esc_attr_e( 'Remove', 'sabay-clone' ); ?>">
				<?php echo sabay_icon( 'trash-2', [ 'width' => 18, 'height' => 18 ] ); ?>
			</button>
		</div>
	</template>
	<?php
}
