<?php
/**
 * "Theme Option" admin page — one place to edit all site-wide data the theme
 * displays, organised in tabs:
 *
 *   General          Site title / tagline (Khmer = core options, English =
 *                    theme option), logo, site icon, default share image.
 *   Design           Brand / top-bar / footer colors and fonts (inc/design.php).
 *   Homepage         Section toggles (ticker, video block, newsletter) and the
 *                    category blocks: color, English name, homepage position.
 *   Contact & Footer About text, email, phones, address, map location,
 *                    copyright line (each text in Khmer + English).
 *   Social           Social profile links.
 *   Integrations     GA4 / GTM IDs and raw header / footer scripts.
 *   Tools            Demo content import / removal (inc/admin/tools.php).
 *
 * Storage — the page writes three places in one save:
 *   - option `sbek_site_settings` (theme data; read with sbek_site_setting()),
 *     sanitized by the callback registered with register_setting();
 *   - core options `blogname`, `blogdescription`, `site_icon`;
 *   - category term meta (`sbek_color`, `sbek_name_en`, `sbek_home_order`),
 *     sanitized by the callbacks registered in inc/admin/term-meta.php.
 * That's why it saves through admin-post.php (nonce + manage_options) rather
 * than options.php, which can only save registered options of one group.
 *
 * @package sbek
 */

defined( 'ABSPATH' ) || exit;

const SBEK_SETTINGS_OPTION = 'sbek_site_settings';
const SBEK_SETTINGS_SLUG   = 'sbek-theme-options';
const SBEK_SETTINGS_ACTION = 'sbek_save_theme_options';

// ─── 1. Data: defaults, sanitizing, accessors ──────────────────────────────────

/**
 * Default shape of the settings array.
 *
 * @return array<string,mixed>
 */
function sbek_site_settings_defaults(): array {
	return array(
		// General.
		'name_en'         => '',
		'tagline_en'      => '',
		'logo'            => 0,
		'og_image'        => 0,
		// Design (validated again on output by sbek_design()).
		'color_brand'     => SBEK_BRAND_COLOR,
		'color_top'       => SBEK_TOPBAR_COLOR,
		'color_footer'    => SBEK_FOOTER_COLOR,
		'font_body'       => 'battambang',
		'font_heading'    => 'koulen',
		'font_latin'      => 'arial',
		// Homepage.
		'show_ticker'     => 1,
		'show_video'      => 1,
		'show_newsletter' => 1,
		// Contact & footer.
		'description'     => '',
		'description_en'  => '',
		'email'           => '',
		'phones'          => array(),
		'address'         => '',
		'address_en'      => '',
		'lat'             => '',
		'lng'             => '',
		'copyright'       => '',
		'copyright_en'    => '',
		// Social.
		'socials'         => array(),
		// Integrations.
		'ga4_id'          => '',
		'gtm_id'          => '',
		'header_scripts'  => '',
		'footer_scripts'  => '',
	);
}

function sbek_register_site_settings(): void {
	register_setting(
		'sbek_theme_options',
		SBEK_SETTINGS_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'sbek_sanitize_site_settings',
			'default'           => sbek_site_settings_defaults(),
			'show_in_rest'      => false,
		)
	);
}
// On `init` (not admin_init) so the sanitizer also runs for update_option()
// calls made outside wp-admin (WP-CLI, the demo importer).
add_action( 'init', 'sbek_register_site_settings' );

/**
 * Sanitize the whole settings array before it is stored. Expects unslashed
 * input. Keys that are absent keep their default.
 *
 * @param mixed $input Raw value.
 * @return array<string,mixed>
 */
function sbek_sanitize_site_settings( $input ): array {
	$input = is_array( $input ) ? $input : array();
	$clean = sbek_site_settings_defaults();

	foreach ( array( 'name_en', 'tagline_en', 'copyright', 'copyright_en', 'ga4_id', 'gtm_id' ) as $key ) {
		$clean[ $key ] = sanitize_text_field( (string) ( $input[ $key ] ?? '' ) );
	}

	foreach ( array( 'description', 'description_en', 'address', 'address_en' ) as $key ) {
		$clean[ $key ] = sanitize_textarea_field( (string) ( $input[ $key ] ?? '' ) );
	}

	// Images must be image attachments.
	foreach ( array( 'logo', 'og_image' ) as $key ) {
		$id            = absint( $input[ $key ] ?? 0 );
		$clean[ $key ] = ( $id && wp_attachment_is_image( $id ) ) ? $id : 0;
	}

	// Design: hex colors only; fonts only from the whitelist for their role.
	$color_defaults = array(
		'color_brand'  => SBEK_BRAND_COLOR,
		'color_top'    => SBEK_TOPBAR_COLOR,
		'color_footer' => SBEK_FOOTER_COLOR,
	);
	foreach ( $color_defaults as $key => $default ) {
		$clean[ $key ] = sbek_normalize_hex( (string) ( $input[ $key ] ?? '' ) ) ?: $default;
	}

	$fonts = sbek_font_choices();
	foreach ( array( 'font_body' => array( 'body', 'battambang' ), 'font_heading' => array( 'heading', 'koulen' ) ) as $key => list( $role, $default ) ) {
		$slug          = sanitize_key( (string) ( $input[ $key ] ?? '' ) );
		$clean[ $key ] = isset( $fonts[ $slug ] ) && in_array( $role, $fonts[ $slug ]['roles'], true ) ? $slug : $default;
	}
	$latin               = sanitize_key( (string) ( $input['font_latin'] ?? '' ) );
	$clean['font_latin'] = isset( sbek_latin_font_choices()[ $latin ] ) ? $latin : 'arial';

	// Toggles: the form always sends 0/1 (hidden input + checkbox); a missing
	// key (e.g. a partial update from code) keeps the default "on".
	foreach ( array( 'show_ticker', 'show_video', 'show_newsletter' ) as $key ) {
		$clean[ $key ] = array_key_exists( $key, $input ) ? (int) (bool) $input[ $key ] : 1;
	}

	$email          = sanitize_email( (string) ( $input['email'] ?? '' ) );
	$clean['email'] = is_email( $email ) ? $email : '';

	// Coordinates: valid ranges only.
	$lat          = $input['lat'] ?? '';
	$lng          = $input['lng'] ?? '';
	$clean['lat'] = is_numeric( $lat ) && abs( (float) $lat ) <= 90 ? (string) (float) $lat : '';
	$clean['lng'] = is_numeric( $lng ) && abs( (float) $lng ) <= 180 ? (string) (float) $lng : '';

	foreach ( (array) ( $input['phones'] ?? array() ) as $row ) {
		$number = sanitize_text_field( (string) ( $row['number'] ?? '' ) );
		if ( '' !== $number ) {
			$clean['phones'][] = array(
				'label'  => sanitize_text_field( (string) ( $row['label'] ?? '' ) ),
				'number' => $number,
			);
		}
	}

	foreach ( (array) ( $input['socials'] ?? array() ) as $row ) {
		$url = esc_url_raw( (string) ( $row['url'] ?? '' ), array( 'http', 'https' ) );
		if ( '' !== $url ) {
			$clean['socials'][] = array(
				'platform' => sanitize_text_field( (string) ( $row['platform'] ?? '' ) ),
				'url'      => $url,
			);
		}
	}

	// Raw scripts only for users allowed to post unfiltered HTML (admins on
	// single site, super admins on multisite); others get them kses-filtered.
	$can_raw = current_user_can( 'unfiltered_html' );
	foreach ( array( 'header_scripts', 'footer_scripts' ) as $key ) {
		$value         = (string) ( $input[ $key ] ?? '' );
		$clean[ $key ] = $can_raw ? $value : wp_kses_post( $value );
	}

	return $clean;
}

/**
 * Read a site setting (or the whole array when $key is null).
 *
 * @param string|null $key     Setting key.
 * @param mixed       $default Fallback when unset.
 * @return mixed
 */
function sbek_site_setting( ?string $key = null, $default = null ) {
	$settings = wp_parse_args( (array) get_option( SBEK_SETTINGS_OPTION, array() ), sbek_site_settings_defaults() );

	if ( null === $key ) {
		return $settings;
	}

	return $settings[ $key ] ?? $default;
}

/**
 * URL of a media setting (logo, og_image), or '' when unset.
 */
function sbek_site_setting_image_url( string $key, string $size = 'full' ): string {
	$id = absint( sbek_site_setting( $key, 0 ) );

	return $id ? (string) wp_get_attachment_image_url( $id, $size ) : '';
}

/**
 * Whether a homepage/footer section is switched on in Theme Option.
 *
 * @param string $section ticker | video | newsletter.
 */
function sbek_section_enabled( string $section ): bool {
	return (bool) sbek_site_setting( 'show_' . $section, 1 );
}

/**
 * Footer copyright line for the current language. `{year}` and `{site}` are
 * replaced; empty falls back to the translated default.
 */
function sbek_copyright_text(): string {
	$custom = trim( sbek_site_text( 'copyright' ) );

	if ( '' === $custom ) {
		/* translators: 1: year, 2: site name. */
		return sprintf( __( '© %1$s %2$s. All rights reserved.', 'sbek' ), wp_date( 'Y' ), get_bloginfo( 'name' ) );
	}

	return strtr(
		$custom,
		array(
			'{year}' => wp_date( 'Y' ),
			'{site}' => get_bloginfo( 'name' ),
		)
	);
}

// ─── 2. Save handler ───────────────────────────────────────────────────────────

/**
 * Tabs shown on the page: slug => [ label, icon ].
 *
 * @return array<string,array{0:string,1:string}>
 */
function sbek_theme_option_tabs(): array {
	return array(
		'general'      => array( __( 'General', 'sbek' ), 'sliders' ),
		'design'       => array( __( 'Design', 'sbek' ), 'palette' ),
		'homepage'     => array( __( 'Homepage', 'sbek' ), 'layout' ),
		'contact'      => array( __( 'Contact & Footer', 'sbek' ), 'map-pin' ),
		'social'       => array( __( 'Social', 'sbek' ), 'share-2' ),
		'integrations' => array( __( 'Integrations', 'sbek' ), 'trending-up' ),
		'tools'        => array( __( 'Tools', 'sbek' ), 'rocket' ),
	);
}

function sbek_save_theme_options(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to change these settings.', 'sbek' ), 403 );
	}

	check_admin_referer( SBEK_SETTINGS_ACTION );

	// 1. Theme settings (sanitized by sbek_sanitize_site_settings via update_option()).
	$settings = isset( $_POST[ SBEK_SETTINGS_OPTION ] ) && is_array( $_POST[ SBEK_SETTINGS_OPTION ] )
		? wp_unslash( $_POST[ SBEK_SETTINGS_OPTION ] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized by the registered callback.
		: array();
	update_option( SBEK_SETTINGS_OPTION, $settings );

	// 2. Core site identity.
	$core = isset( $_POST['sbek_core'] ) && is_array( $_POST['sbek_core'] ) ? wp_unslash( $_POST['sbek_core'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized per key below.

	if ( isset( $core['blogname'] ) ) {
		update_option( 'blogname', sanitize_text_field( (string) $core['blogname'] ) );
	}
	if ( isset( $core['blogdescription'] ) ) {
		update_option( 'blogdescription', sanitize_text_field( (string) $core['blogdescription'] ) );
	}
	if ( isset( $core['site_icon'] ) ) {
		$icon = absint( $core['site_icon'] );
		update_option( 'site_icon', ( $icon && wp_attachment_is_image( $icon ) ) ? $icon : 0 );
	}

	// 3. Category blocks (term meta; registered sanitizers apply).
	$terms = isset( $_POST['sbek_terms'] ) && is_array( $_POST['sbek_terms'] ) ? wp_unslash( $_POST['sbek_terms'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized per field below.

	foreach ( $terms as $term_id => $fields ) {
		$term_id = absint( $term_id );
		$term    = get_term( $term_id, 'category' );

		if ( ! $term instanceof WP_Term || ! is_array( $fields ) || ! current_user_can( 'edit_term', $term_id ) ) {
			continue;
		}

		$values = array(
			'sbek_color'      => (string) sanitize_hex_color( (string) ( $fields['color'] ?? '' ) ),
			'sbek_name_en'    => sanitize_text_field( (string) ( $fields['name_en'] ?? '' ) ),
			'sbek_home_order' => absint( $fields['home_order'] ?? 0 ),
		);

		foreach ( $values as $key => $value ) {
			if ( '' === $value || 0 === $value ) {
				delete_term_meta( $term_id, $key );
			} else {
				update_term_meta( $term_id, $key, $value );
			}
		}
	}

	if ( function_exists( 'sbek_bump_cache_version' ) ) {
		sbek_bump_cache_version(); // Homepage blocks read these values.
	}

	$tab = isset( $_POST['sbek_tab'] ) ? sanitize_key( wp_unslash( $_POST['sbek_tab'] ) ) : '';
	$tab = isset( sbek_theme_option_tabs()[ $tab ] ) ? $tab : 'general';

	wp_safe_redirect( add_query_arg( array( 'page' => SBEK_SETTINGS_SLUG, 'updated' => 1 ), admin_url( 'admin.php' ) ) . '#' . $tab );
	exit;
}
add_action( 'admin_post_' . SBEK_SETTINGS_ACTION, 'sbek_save_theme_options' );

// ─── 3. Menu, legacy URL, assets ───────────────────────────────────────────────

function sbek_theme_options_menu(): void {
	$hook = add_menu_page(
		__( 'Theme Option', 'sbek' ),
		__( 'Theme Option', 'sbek' ),
		'manage_options',
		SBEK_SETTINGS_SLUG,
		'sbek_render_theme_options_page',
		'dashicons-admin-customizer',
		61 // Right after Appearance.
	);

	add_action( "admin_print_styles-{$hook}", 'sbek_theme_options_assets' );

	// Old bookmarks (Settings → Options page) land on the new page. The slug
	// must stay registered — wp-admin refuses unknown pages before any hook we
	// could redirect from — so register it, redirect on its load hook (before
	// output), and hide it from the menu.
	$legacy = add_submenu_page( 'options-general.php', __( 'Theme Option', 'sbek' ), __( 'Theme Option', 'sbek' ), 'manage_options', 'sbek-site-settings', '__return_null' );
	add_action( "load-{$legacy}", 'sbek_redirect_legacy_options_url' );
	remove_submenu_page( 'options-general.php', 'sbek-site-settings' );
}
add_action( 'admin_menu', 'sbek_theme_options_menu' );

function sbek_redirect_legacy_options_url(): void {
	wp_safe_redirect( sbek_theme_options_url() );
	exit;
}

function sbek_theme_options_url( string $tab = '' ): string {
	return admin_url( 'admin.php?page=' . SBEK_SETTINGS_SLUG ) . ( $tab ? '#' . $tab : '' );
}

function sbek_theme_options_assets(): void {
	wp_enqueue_media(); // wp.media for the image pickers.

	$css = 'assets/css/admin.css';
	$js  = 'assets/js/admin-theme-options.js';

	wp_enqueue_style( 'sbek-admin', SBEK_URI . '/' . $css, array(), (string) ( file_exists( SBEK_DIR . '/' . $css ) ? filemtime( SBEK_DIR . '/' . $css ) : SBEK_VERSION ) );
	wp_enqueue_script( 'sbek-theme-options', SBEK_URI . '/' . $js, array( 'media-editor' ), (string) ( file_exists( SBEK_DIR . '/' . $js ) ? filemtime( SBEK_DIR . '/' . $js ) : SBEK_VERSION ), true );
	wp_localize_script(
		'sbek-theme-options',
		'sbekThemeOptions',
		array(
			'mediaTitle'  => __( 'Select image', 'sbek' ),
			'mediaButton' => __( 'Use this image', 'sbek' ),
			'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
			'demoNonce'   => wp_create_nonce( SBEK_DEMO_NONCE ),
			'demoFailed'  => __( 'Something went wrong. You can continue the job or remove what was created.', 'sbek' ),
			'demoDone'    => __( 'Done! Reloading…', 'sbek' ),
		)
	);
}

// ─── 4. Field helpers ──────────────────────────────────────────────────────────

/**
 * Open a settings card (icon, title, description; optional trusted action
 * HTML on the right). Close with sbek_section_close().
 */
function sbek_section_open( string $icon, string $title, string $description, string $action = '' ): void {
	?>
	<section class="sbek-card">
		<header class="sbek-card__head">
			<span class="sbek-card__icon"><?php echo sbek_icon( $icon, array( 'width' => 20, 'height' => 20 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></span>
			<div class="sbek-card__heading">
				<h2 class="sbek-card__title"><?php echo esc_html( $title ); ?></h2>
				<p class="sbek-card__desc"><?php echo esc_html( $description ); ?></p>
			</div>
			<?php if ( $action ) : ?>
				<div class="sbek-card__action"><?php echo $action; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted markup built by callers. ?></div>
			<?php endif; ?>
		</header>
		<div class="sbek-card__body">
	<?php
}

function sbek_section_close(): void {
	echo '</div></section>';
}

/**
 * Text / email / url / number input bound to a settings key.
 *
 * @param string               $name  Full input name.
 * @param string               $label Label.
 * @param string               $value Current value.
 * @param array<string,string> $args  type, placeholder, help, class.
 */
function sbek_option_input( string $name, string $label, string $value, array $args = array() ): void {
	$id = 'sbek-' . md5( $name );
	?>
	<div class="sbek-field <?php echo esc_attr( $args['class'] ?? '' ); ?>">
		<label class="sbek-field__label" for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
		<input class="sbek-input" type="<?php echo esc_attr( $args['type'] ?? 'text' ); ?>" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php echo esc_attr( $args['placeholder'] ?? '' ); ?>"<?php echo isset( $args['dir'] ) ? ' dir="' . esc_attr( $args['dir'] ) . '"' : ''; ?>>
		<?php if ( ! empty( $args['help'] ) ) : ?>
			<p class="sbek-field__help"><?php echo esc_html( $args['help'] ); ?></p>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Textarea bound to a settings key.
 *
 * @param string               $name  Full input name.
 * @param string               $label Label.
 * @param string               $value Current value.
 * @param array<string,string> $args  rows, placeholder, help, class, code.
 */
function sbek_option_textarea( string $name, string $label, string $value, array $args = array() ): void {
	$id = 'sbek-' . md5( $name );
	?>
	<div class="sbek-field <?php echo esc_attr( $args['class'] ?? '' ); ?>">
		<label class="sbek-field__label" for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
		<textarea class="sbek-input<?php echo empty( $args['code'] ) ? '' : ' sbek-input--code'; ?>" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" rows="<?php echo esc_attr( $args['rows'] ?? '3' ); ?>" placeholder="<?php echo esc_attr( $args['placeholder'] ?? '' ); ?>"<?php echo empty( $args['code'] ) ? '' : ' spellcheck="false"'; ?>><?php echo esc_textarea( $value ); ?></textarea>
		<?php if ( ! empty( $args['help'] ) ) : ?>
			<p class="sbek-field__help"><?php echo esc_html( $args['help'] ); ?></p>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * On/off switch. A hidden 0 precedes the checkbox so "off" is submitted too.
 */
function sbek_option_toggle( string $name, string $label, string $help, bool $checked ): void {
	$id = 'sbek-' . md5( $name );
	?>
	<div class="sbek-toggle">
		<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="0">
		<input type="checkbox" class="sbek-toggle__input" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="1" <?php checked( $checked ); ?>>
		<label class="sbek-toggle__label" for="<?php echo esc_attr( $id ); ?>">
			<span class="sbek-toggle__switch" aria-hidden="true"></span>
			<span>
				<span class="sbek-toggle__title"><?php echo esc_html( $label ); ?></span>
				<span class="sbek-toggle__help"><?php echo esc_html( $help ); ?></span>
			</span>
		</label>
	</div>
	<?php
}

/**
 * Color picker with a synced hex box and a "Reset" button (JS-enhanced; the
 * color input alone is submitted).
 */
function sbek_option_color( string $name, string $label, string $value, string $default, string $help, string $preview_var ): void {
	$id = 'sbek-' . md5( $name );
	?>
	<div class="sbek-field">
		<label class="sbek-field__label" for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
		<div class="sbek-colorfield" data-sbek-color-field data-preview-var="<?php echo esc_attr( $preview_var ); ?>">
			<input type="color" class="sbek-color" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" data-sbek-color data-default="<?php echo esc_attr( $default ); ?>">
			<input type="text" class="sbek-input sbek-colorfield__hex" value="<?php echo esc_attr( $value ); ?>" maxlength="7" pattern="#[0-9a-fA-F]{6}" spellcheck="false" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: field label. */ __( '%s (hex)', 'sbek' ), $label ) ); ?>" data-sbek-color-hex>
			<button type="button" class="sbek-colorfield__reset" data-sbek-color-reset><?php esc_html_e( 'Reset', 'sbek' ); ?></button>
		</div>
		<p class="sbek-field__help"><?php echo esc_html( $help ); ?></p>
	</div>
	<?php
}

/**
 * Font <select> (options carry their CSS family for the live preview).
 *
 * @param string                                    $name    Input name.
 * @param string                                    $label   Label.
 * @param string                                    $value   Selected slug.
 * @param array<string,array{label:string,css:string,note?:string}> $choices slug => data.
 * @param string                                    $target  Preview role (body|heading|latin).
 */
function sbek_option_font( string $name, string $label, string $value, array $choices, string $target ): void {
	$id = 'sbek-' . md5( $name );
	?>
	<div class="sbek-field">
		<label class="sbek-field__label" for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
		<select class="sbek-input" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" data-sbek-font="<?php echo esc_attr( $target ); ?>">
			<?php foreach ( $choices as $slug => $choice ) : ?>
				<option value="<?php echo esc_attr( $slug ); ?>" data-css="<?php echo esc_attr( $choice['css'] ); ?>" data-weight="<?php echo esc_attr( $choice['weight'] ?? '400' ); ?>" <?php selected( $value, $slug ); ?>>
					<?php echo esc_html( $choice['label'] . ( empty( $choice['note'] ) ? '' : ' — ' . $choice['note'] ) ); ?>
				</option>
			<?php endforeach; ?>
		</select>
	</div>
	<?php
}

/**
 * Media-library picker (dropzone when empty, preview + Replace/Remove when set).
 *
 * @param string $name          Full input name.
 * @param int    $attachment_id Current attachment ID.
 * @param string $select_label  Empty-state call to action.
 * @param string $hint          File-type / size hint.
 */
function sbek_render_media_field( string $name, int $attachment_id, string $select_label, string $hint = '' ): void {
	$url = $attachment_id ? (string) wp_get_attachment_image_url( $attachment_id, 'medium' ) : '';
	?>
	<div class="sbek-media" data-sbek-media-field data-state="<?php echo esc_attr( $url ? 'filled' : 'empty' ); ?>">
		<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $attachment_id ? (string) $attachment_id : '' ); ?>" data-sbek-media-input>
		<button type="button" class="sbek-media-dropzone" data-sbek-media-select>
			<span class="sbek-media-dropzone__icon"><?php echo sbek_icon( 'image-plus', array( 'width' => 26, 'height' => 26 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></span>
			<span class="sbek-media-dropzone__label"><?php echo esc_html( $select_label ); ?></span>
			<?php if ( $hint ) : ?>
				<span class="sbek-media-dropzone__hint"><?php echo esc_html( $hint ); ?></span>
			<?php endif; ?>
		</button>
		<div class="sbek-media-preview">
			<div class="sbek-media-preview__frame sbek-checkerboard">
				<img src="<?php echo esc_url( $url ); ?>" alt="" class="sbek-media-preview__img" data-sbek-media-preview>
			</div>
			<div class="sbek-media-preview__actions">
				<button type="button" class="sbek-media-btn sbek-media-btn--change" data-sbek-media-select>
					<?php echo sbek_icon( 'image-up', array( 'width' => 15, 'height' => 15 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
					<?php esc_html_e( 'Replace', 'sbek' ); ?>
				</button>
				<button type="button" class="sbek-media-btn sbek-media-btn--remove" data-sbek-media-remove>
					<?php echo sbek_icon( 'trash-2', array( 'width' => 15, 'height' => 15 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
					<?php esc_html_e( 'Remove', 'sbek' ); ?>
				</button>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Repeater "Add" button for a card header.
 */
function sbek_add_button( string $group ): string {
	return sprintf(
		'<button type="button" class="sbek-card__add" data-sbek-add="%s">%s<span>%s</span></button>',
		esc_attr( $group ),
		sbek_icon( 'plus', array( 'width' => 15, 'height' => 15 ) ),
		esc_html__( 'Add', 'sbek' )
	);
}

/**
 * One repeater row (phones / socials). `$index` null renders the template
 * variant with `data-name` + `__i__` placeholders for the JS to fill in.
 *
 * @param string               $group  phones | socials.
 * @param int|null             $index  Row index or null for the template.
 * @param array<string,string> $row    Values.
 */
function sbek_repeater_row( string $group, ?int $index, array $row = array() ): void {
	$fields = 'phones' === $group
		? array(
			'label'  => array( __( 'Label (e.g. Newsroom)', 'sbek' ), 'text', '' ),
			'number' => array( '+855 12 345 678', 'tel', '' ),
		)
		: array(
			'platform' => array( __( 'Platform (e.g. Facebook)', 'sbek' ), 'text', '' ),
			'url'      => array( 'https://facebook.com/yourpage', 'url', '' ),
		);

	$attr = null === $index ? 'data-name' : 'name';
	$slot = null === $index ? '__i__' : (string) $index;
	?>
	<div class="sbek-repeater__row" data-sbek-row>
		<?php if ( 'socials' === $group ) : ?>
			<span class="sbek-repeater__icon" aria-hidden="true"><?php echo sbek_icon( sbek_social_icon_name( (string) ( $row['platform'] ?? '' ) ), array( 'width' => 18, 'height' => 18 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></span>
		<?php endif; ?>
		<?php foreach ( $fields as $key => list( $placeholder, $type ) ) : ?>
			<input class="sbek-input" type="<?php echo esc_attr( $type ); ?>" <?php echo esc_attr( $attr ); ?>="<?php echo esc_attr( SBEK_SETTINGS_OPTION . "[{$group}][{$slot}][{$key}]" ); ?>" value="<?php echo esc_attr( (string) ( $row[ $key ] ?? '' ) ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" aria-label="<?php echo esc_attr( $placeholder ); ?>">
		<?php endforeach; ?>
		<button type="button" class="sbek-repeater__remove" data-sbek-remove aria-label="<?php esc_attr_e( 'Remove', 'sbek' ); ?>">
			<?php echo sbek_icon( 'trash-2', array( 'width' => 18, 'height' => 18 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
		</button>
	</div>
	<?php
}

// ─── 5. Page ───────────────────────────────────────────────────────────────────

function sbek_render_theme_options_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'sbek' ) );
	}

	$s    = sbek_site_setting();
	$opt  = SBEK_SETTINGS_OPTION;
	$tabs = sbek_theme_option_tabs();

	$categories = get_terms(
		array(
			'taxonomy'   => 'category',
			'hide_empty' => false,
			'exclude'    => array( (int) get_option( 'default_category' ) ),
		)
	);
	$categories = is_wp_error( $categories ) ? array() : $categories;

	// Homepage blocks first, in their order; the rest alphabetically.
	usort(
		$categories,
		static function ( WP_Term $a, WP_Term $b ): int {
			$oa = (int) get_term_meta( $a->term_id, 'sbek_home_order', true ) ?: PHP_INT_MAX;
			$ob = (int) get_term_meta( $b->term_id, 'sbek_home_order', true ) ?: PHP_INT_MAX;

			return $oa <=> $ob ?: strcmp( $a->name, $b->name );
		}
	);
	?>
	<div class="wrap sbek-options">
		<?php
		// Decorative welcome banner (source: docs/brand/source/options-banner.html); the tabs below carry the same information.
		$banner = 'assets/images/theme-options-banner.png';
		if ( file_exists( SBEK_DIR . '/' . $banner ) ) :
			?>
			<img class="sbek-options__banner" src="<?php echo esc_url( add_query_arg( 'ver', filemtime( SBEK_DIR . '/' . $banner ), SBEK_URI . '/' . $banner ) ); ?>" width="2400" height="480" alt="" decoding="async">
		<?php endif; ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sbek-options__form" data-sbek-options-form>
			<input type="hidden" name="action" value="<?php echo esc_attr( SBEK_SETTINGS_ACTION ); ?>">
			<input type="hidden" name="sbek_tab" value="general" data-sbek-current-tab>
			<?php wp_nonce_field( SBEK_SETTINGS_ACTION ); ?>

			<header class="sbek-options__header">
				<div class="sbek-options__titles">
					<h1 class="sbek-options__title"><?php esc_html_e( 'Theme Option', 'sbek' ); ?></h1>
					<p class="sbek-options__subtitle"><?php esc_html_e( 'Everything your site shows outside of articles — identity, homepage sections, contact details, social links and tracking — in one place.', 'sbek' ); ?></p>
				</div>
				<div class="sbek-options__actions">
					<a class="sbek-btn sbek-btn--ghost" href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener">
						<?php echo sbek_icon( 'external-link', array( 'width' => 15, 'height' => 15 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
						<?php esc_html_e( 'View site', 'sbek' ); ?>
					</a>
					<button type="submit" class="sbek-btn sbek-btn--primary"><?php esc_html_e( 'Save changes', 'sbek' ); ?></button>
				</div>
			</header>
			<hr class="wp-header-end"><?php // Core moves admin notices here instead of into the header. ?>

			<?php
			if ( isset( $_GET['updated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display-only flag.
				wp_admin_notice(
					__( 'Theme options saved.', 'sbek' ),
					array(
						'type'        => 'success',
						'dismissible' => true,
					)
				);
			}
			if ( isset( $_GET['design_reset'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display-only flag.
				wp_admin_notice(
					__( 'Design reset to the default colors and fonts.', 'sbek' ),
					array(
						'type'        => 'success',
						'dismissible' => true,
					)
				);
			}
			?>

			<div class="sbek-options__layout">
				<nav class="sbek-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Theme Option sections', 'sbek' ); ?>" data-sbek-tabs>
					<?php foreach ( $tabs as $slug => list( $label, $icon ) ) : ?>
						<a class="sbek-tabs__tab" href="#<?php echo esc_attr( $slug ); ?>" role="tab" id="sbek-tab-<?php echo esc_attr( $slug ); ?>" aria-controls="sbek-panel-<?php echo esc_attr( $slug ); ?>" data-sbek-tab="<?php echo esc_attr( $slug ); ?>">
							<?php echo sbek_icon( $icon, array( 'width' => 18, 'height' => 18 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
							<span><?php echo esc_html( $label ); ?></span>
						</a>
					<?php endforeach; ?>
				</nav>

				<div class="sbek-options__panels">

					<?php // ── General ───────────────────────────────────────────── ?>
					<section class="sbek-panel" id="sbek-panel-general" role="tabpanel" aria-labelledby="sbek-tab-general" data-sbek-panel="general">
						<?php sbek_section_open( 'globe', __( 'Site identity', 'sbek' ), __( 'The name and tagline shown in browser tabs, search results and the footer. English versions are used on /en/ pages; leave them empty to reuse the Khmer text.', 'sbek' ) ); ?>
							<div class="sbek-grid sbek-grid--2">
								<?php
								sbek_option_input( 'sbek_core[blogname]', __( 'Site title (Khmer)', 'sbek' ), (string) get_option( 'blogname' ) );
								sbek_option_input( "{$opt}[name_en]", __( 'Site title (English)', 'sbek' ), (string) $s['name_en'], array( 'placeholder' => (string) get_option( 'blogname' ) ) );
								sbek_option_input( 'sbek_core[blogdescription]', __( 'Tagline (Khmer)', 'sbek' ), (string) get_option( 'blogdescription' ) );
								sbek_option_input( "{$opt}[tagline_en]", __( 'Tagline (English)', 'sbek' ), (string) $s['tagline_en'] );
								?>
							</div>
						<?php sbek_section_close(); ?>

						<?php sbek_section_open( 'image', __( 'Branding', 'sbek' ), __( 'Logo for the header and footer, the browser/app icon, and the image used when a page without its own picture is shared.', 'sbek' ) ); ?>
							<div class="sbek-grid sbek-grid--3">
								<div class="sbek-field">
									<span class="sbek-field__label"><?php esc_html_e( 'Logo', 'sbek' ); ?></span>
									<?php sbek_render_media_field( "{$opt}[logo]", (int) $s['logo'], __( 'Choose a logo', 'sbek' ), __( 'PNG or JPG, square works best', 'sbek' ) ); ?>
								</div>
								<div class="sbek-field">
									<span class="sbek-field__label"><?php esc_html_e( 'Site icon', 'sbek' ); ?></span>
									<?php sbek_render_media_field( 'sbek_core[site_icon]', (int) get_option( 'site_icon' ), __( 'Choose an icon', 'sbek' ), __( 'Square, at least 512×512px', 'sbek' ) ); ?>
								</div>
								<div class="sbek-field">
									<span class="sbek-field__label"><?php esc_html_e( 'Default share image', 'sbek' ); ?></span>
									<?php sbek_render_media_field( "{$opt}[og_image]", (int) $s['og_image'], __( 'Choose an image', 'sbek' ), __( 'JPG or PNG, 1200×630px', 'sbek' ) ); ?>
								</div>
							</div>
						<?php sbek_section_close(); ?>
					</section>

					<?php
					// ── Design ──────────────────────────────────────────────
					$design       = sbek_design();
					$font_choices = array(
						'body'    => array(),
						'heading' => array(),
						'latin'   => array(),
					);
					foreach ( sbek_font_choices() as $slug => $font ) {
						foreach ( $font['roles'] as $role ) {
							$font_choices[ $role ][ $slug ] = array(
								'label' => $font['label'],
								'css'    => '"' . $font['family'] . '"',
								'note'   => $font['note'],
								'weight' => $font['display'] ? '400' : '700',
							);
						}
					}
					foreach ( sbek_latin_font_choices() as $slug => $font ) {
						$font_choices['latin'][ $slug ] = array(
							'label' => $font['label'],
							'css'   => $font['stack'],
						);
					}
					$latin_stack  = sbek_latin_font_choices()[ $design['font_latin'] ]['stack'];
					$preview_vars = sprintf(
						'--p-brand:%1$s;--p-brand-dark:%2$s;--p-on-brand:%3$s;--p-top:%4$s;--p-on-top:%5$s;--p-footer:%6$s;--p-on-footer:%7$s;--p-body:"%8$s", %10$s;--p-heading:"%9$s", %10$s;--p-heading-weight:%11$s;',
						$design['brand'],
						$design['brand_dark'],
						sbek_readable_on( $design['brand'] ),
						$design['top'],
						sbek_readable_on( $design['top'] ),
						$design['footer'],
						'#ffffff' === sbek_readable_on( $design['footer'] ) ? '#abaaab' : '#555555',
						sbek_font_choices()[ $design['font_body'] ]['family'],
						sbek_font_choices()[ $design['font_heading'] ]['family'],
						$latin_stack,
						sbek_font_choices()[ $design['font_heading'] ]['display'] ? '400' : '700'
					);
					?>
					<section class="sbek-panel" id="sbek-panel-design" role="tabpanel" aria-labelledby="sbek-tab-design" data-sbek-panel="design">
						<div class="sbek-preview" data-sbek-preview data-default-brand="<?php echo esc_attr( SBEK_BRAND_COLOR ); ?>" data-default-brand-dark="<?php echo esc_attr( SBEK_BRAND_DARK ); ?>" style="<?php echo esc_attr( $preview_vars ); ?>" aria-label="<?php esc_attr_e( 'Live preview', 'sbek' ); ?>">
							<div class="sbek-preview__top">
								<span class="sbek-preview__logo"><?php echo esc_html( mb_substr( (string) get_option( 'blogname' ), 0, 2 ) ); ?></span>
								<span class="sbek-preview__ad"></span>
							</div>
							<div class="sbek-preview__menu">
								<span class="sbek-preview__item"><?php echo sbek_icon( 'home', array( 'width' => 14, 'height' => 14 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></span>
								<span class="sbek-preview__item is-active">កម្សាន្ត</span>
								<span class="sbek-preview__item">បច្ចេកវិទ្យា</span>
								<span class="sbek-preview__item">កីឡា</span>
								<span class="sbek-preview__item sbek-preview__item--latin">AUTO TALK</span>
							</div>
							<div class="sbek-preview__body">
								<div class="sbek-preview__tab"><span>វីដេអូ ›</span></div>
								<p class="sbek-preview__title">ព័ត៌មានថ្មីៗប្រចាំថ្ងៃ — Latest news of the day</p>
								<p class="sbek-preview__text">នេះជាអត្ថបទគំរូសម្រាប់បង្ហាញរចនាបថ។ This sample shows body text in Khmer and English.</p>
								<span class="sbek-preview__button"><?php esc_html_e( 'Subscribe', 'sbek' ); ?></span>
							</div>
							<div class="sbek-preview__footer">© <?php echo esc_html( wp_date( 'Y' ) . ' ' . get_option( 'blogname' ) ); ?></div>
						</div>

						<?php sbek_section_open( 'palette', __( 'Colors', 'sbek' ), __( 'Text on these colors switches between white and dark automatically, so it always stays readable.', 'sbek' ) ); ?>
							<div class="sbek-grid sbek-grid--3">
								<?php
								sbek_option_color( "{$opt}[color_brand]", __( 'Brand color', 'sbek' ), $design['brand'], SBEK_BRAND_COLOR, __( 'Menu bar, buttons, ticker label, links and accents. The darker hover shade is derived from it.', 'sbek' ), '--p-brand' );
								sbek_option_color( "{$opt}[color_top]", __( 'Top bar', 'sbek' ), $design['top'], SBEK_TOPBAR_COLOR, __( 'Logo bar on desktop and the menu bar on mobile.', 'sbek' ), '--p-top' );
								sbek_option_color( "{$opt}[color_footer]", __( 'Footer', 'sbek' ), $design['footer'], SBEK_FOOTER_COLOR, __( 'Footer background.', 'sbek' ), '--p-footer' );
								?>
							</div>
						<?php sbek_section_close(); ?>

						<?php sbek_section_open( 'sliders', __( 'Fonts', 'sbek' ), __( 'Khmer fonts are hosted on this site (no Google requests); visitors only download the fonts you pick. Latin text (English, numbers) uses the Latin font.', 'sbek' ) ); ?>
							<div class="sbek-grid sbek-grid--3">
								<?php
								sbek_option_font( "{$opt}[font_body]", __( 'Khmer body text', 'sbek' ), $design['font_body'], $font_choices['body'], 'body' );
								sbek_option_font( "{$opt}[font_heading]", __( 'Khmer headings & menu', 'sbek' ), $design['font_heading'], $font_choices['heading'], 'heading' );
								sbek_option_font( "{$opt}[font_latin]", __( 'Latin text', 'sbek' ), $design['font_latin'], $font_choices['latin'], 'latin' );
								?>
							</div>
						<?php sbek_section_close(); ?>

						<?php sbek_section_open( 'copyright', __( 'Reset design', 'sbek' ), __( 'Restore the original Sabay red, Battambang and Koulen. Only colors and fonts are reset; everything else in Theme Option stays as it is.', 'sbek' ) ); ?>
							<?php
							$is_default = sbek_design_is_default();
							sbek_confirm_control(
								__( 'Reset design to defaults', 'sbek' ),
								__( 'Replace your colors and fonts with the defaults?', 'sbek' ),
								'<button type="submit" form="sbek-reset-design-form" class="sbek-btn sbek-btn--danger">' . esc_html__( 'Yes, reset', 'sbek' ) . '</button>',
								$is_default
							);
							?>
							<?php if ( $is_default ) : ?>
								<p class="sbek-field__help"><?php esc_html_e( 'The design already uses the defaults.', 'sbek' ); ?></p>
							<?php endif; ?>
						<?php sbek_section_close(); ?>
					</section>

					<?php // ── Homepage ──────────────────────────────────────────── ?>
					<section class="sbek-panel" id="sbek-panel-homepage" role="tabpanel" aria-labelledby="sbek-tab-homepage" data-sbek-panel="homepage">
						<?php sbek_section_open( 'toggle', __( 'Sections', 'sbek' ), __( 'Turn site-wide sections on or off. The hero grid shows sticky posts (then the latest).', 'sbek' ) ); ?>
							<div class="sbek-stack">
								<?php
								sbek_option_toggle( "{$opt}[show_ticker]", __( 'Breaking-news ticker', 'sbek' ), __( 'Scrolling bar under the menu with posts marked "Breaking news".', 'sbek' ), (bool) $s['show_ticker'] );
								sbek_option_toggle( "{$opt}[show_video]", __( 'Video block', 'sbek' ), __( 'Homepage block listing posts in the "Video" post format.', 'sbek' ), (bool) $s['show_video'] );
								sbek_option_toggle( "{$opt}[show_newsletter]", __( 'Newsletter signup', 'sbek' ), __( 'Email signup form in the footer.', 'sbek' ), (bool) $s['show_newsletter'] );
								?>
							</div>
						<?php sbek_section_close(); ?>

						<?php sbek_section_open( 'palette', __( 'Category blocks', 'sbek' ), __( 'Each category\'s accent color (badges, headers, arrows), its English name, and its position on the homepage. Position 1, 2, 3… shows it as a block in that order; leave empty to hide it.', 'sbek' ) ); ?>
							<?php if ( $categories ) : ?>
								<div class="sbek-table-wrap">
									<table class="sbek-table">
										<thead>
											<tr>
												<th scope="col"><?php esc_html_e( 'Category', 'sbek' ); ?></th>
												<th scope="col"><?php esc_html_e( 'Color', 'sbek' ); ?></th>
												<th scope="col"><?php esc_html_e( 'English name', 'sbek' ); ?></th>
												<th scope="col"><?php esc_html_e( 'Homepage position', 'sbek' ); ?></th>
											</tr>
										</thead>
										<tbody>
											<?php
											foreach ( $categories as $category ) :
												$base  = 'sbek_terms[' . $category->term_id . ']';
												$color = sanitize_hex_color( (string) get_term_meta( $category->term_id, 'sbek_color', true ) );
												$order = (int) get_term_meta( $category->term_id, 'sbek_home_order', true );
												?>
												<tr>
													<th scope="row">
														<span class="sbek-table__name"><?php echo esc_html( $category->name ); ?></span>
														<span class="sbek-table__meta">
															<?php
															/* translators: %s: number of posts. */
															echo esc_html( sprintf( _n( '%s post', '%s posts', $category->count, 'sbek' ), number_format_i18n( $category->count ) ) );
															?>
															· <a href="<?php echo esc_url( get_edit_term_link( $category, 'category' ) ); ?>"><?php esc_html_e( 'Edit', 'sbek' ); ?></a>
														</span>
													</th>
													<td>
														<input type="color" class="sbek-color" name="<?php echo esc_attr( $base . '[color]' ); ?>" value="<?php echo esc_attr( $color ? $color : sbek_brand_color() ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: category. */ __( 'Color for %s', 'sbek' ), $category->name ) ); ?>">
													</td>
													<td>
														<input type="text" class="sbek-input" name="<?php echo esc_attr( $base . '[name_en]' ); ?>" value="<?php echo esc_attr( (string) get_term_meta( $category->term_id, 'sbek_name_en', true ) ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: category. */ __( 'English name for %s', 'sbek' ), $category->name ) ); ?>">
													</td>
													<td>
														<input type="number" min="0" step="1" class="sbek-input sbek-input--short" name="<?php echo esc_attr( $base . '[home_order]' ); ?>" value="<?php echo esc_attr( $order ? (string) $order : '' ); ?>" placeholder="—" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: category. */ __( 'Homepage position for %s', 'sbek' ), $category->name ) ); ?>">
													</td>
												</tr>
											<?php endforeach; ?>
										</tbody>
									</table>
								</div>
							<?php else : ?>
								<p class="sbek-empty"><?php esc_html_e( 'No categories yet.', 'sbek' ); ?> <a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=category' ) ); ?>"><?php esc_html_e( 'Add one', 'sbek' ); ?></a></p>
							<?php endif; ?>
						<?php sbek_section_close(); ?>

						<?php sbek_section_open( 'external-link', __( 'Elsewhere in WordPress', 'sbek' ), __( 'Site data that has its own dedicated screen.', 'sbek' ) ); ?>
							<ul class="sbek-links">
								<li><a href="<?php echo esc_url( admin_url( 'nav-menus.php' ) ); ?>"><?php esc_html_e( 'Menus (Khmer and English)', 'sbek' ); ?></a></li>
								<li><a href="<?php echo esc_url( admin_url( 'widgets.php' ) ); ?>"><?php esc_html_e( 'Ad slots and sidebar widgets', 'sbek' ); ?></a></li>
								<li><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=post&show_sticky=1' ) ); ?>"><?php esc_html_e( 'Sticky posts (homepage hero)', 'sbek' ); ?></a></li>
								<li><a href="<?php echo esc_url( admin_url( 'options-general.php' ) ); ?>"><?php esc_html_e( 'Language, time zone and date format', 'sbek' ); ?></a></li>
								<li><a href="<?php echo esc_url( admin_url( 'options-permalink.php' ) ); ?>"><?php esc_html_e( 'Permalinks', 'sbek' ); ?></a></li>
							</ul>
						<?php sbek_section_close(); ?>
					</section>

					<?php // ── Contact & footer ──────────────────────────────────── ?>
					<section class="sbek-panel" id="sbek-panel-contact" role="tabpanel" aria-labelledby="sbek-tab-contact" data-sbek-panel="contact">
						<?php sbek_section_open( 'info', __( 'About', 'sbek' ), __( 'Short description in the footer\'s "About us" column, also used for SEO and structured data.', 'sbek' ) ); ?>
							<div class="sbek-grid sbek-grid--2">
								<?php
								sbek_option_textarea( "{$opt}[description]", __( 'Description (Khmer)', 'sbek' ), (string) $s['description'], array( 'rows' => '4' ) );
								sbek_option_textarea( "{$opt}[description_en]", __( 'Description (English)', 'sbek' ), (string) $s['description_en'], array( 'rows' => '4' ) );
								?>
							</div>
						<?php sbek_section_close(); ?>

						<?php sbek_section_open( 'phone', __( 'Contact', 'sbek' ), __( 'Shown in the footer and in structured data. Add as many phone numbers as you need.', 'sbek' ), sbek_add_button( 'phones' ) ); ?>
							<?php sbek_option_input( "{$opt}[email]", __( 'Contact email', 'sbek' ), (string) $s['email'], array( 'type' => 'email', 'placeholder' => 'newsroom@example.com' ) ); ?>
							<div class="sbek-field">
								<span class="sbek-field__label"><?php esc_html_e( 'Phone numbers', 'sbek' ); ?></span>
								<div class="sbek-repeater" data-sbek-rows="phones">
									<?php
									$phones = $s['phones'] ? $s['phones'] : array( array() );
									foreach ( array_values( $phones ) as $i => $row ) {
										sbek_repeater_row( 'phones', $i, (array) $row );
									}
									?>
								</div>
							</div>
						<?php sbek_section_close(); ?>

						<?php sbek_section_open( 'map-pin', __( 'Address & location', 'sbek' ), __( 'Postal address for the footer; coordinates for maps and structured data.', 'sbek' ) ); ?>
							<div class="sbek-grid sbek-grid--2">
								<?php
								sbek_option_textarea( "{$opt}[address]", __( 'Address (Khmer)', 'sbek' ), (string) $s['address'] );
								sbek_option_textarea( "{$opt}[address_en]", __( 'Address (English)', 'sbek' ), (string) $s['address_en'] );
								sbek_option_input( "{$opt}[lat]", __( 'Latitude', 'sbek' ), (string) $s['lat'], array( 'placeholder' => '11.5564' ) );
								sbek_option_input( "{$opt}[lng]", __( 'Longitude', 'sbek' ), (string) $s['lng'], array( 'placeholder' => '104.9282' ) );
								?>
							</div>
						<?php sbek_section_close(); ?>

						<?php sbek_section_open( 'copyright', __( 'Copyright line', 'sbek' ), __( 'Leave empty for the default. You can use {year} and {site}.', 'sbek' ) ); ?>
							<div class="sbek-grid sbek-grid--2">
								<?php
								sbek_option_input( "{$opt}[copyright]", __( 'Copyright (Khmer)', 'sbek' ), (string) $s['copyright'], array( 'placeholder' => '© រក្សាសិទ្ធិគ្រប់យ៉ាងដោយ {site} ឆ្នាំ{year}' ) );
								sbek_option_input( "{$opt}[copyright_en]", __( 'Copyright (English)', 'sbek' ), (string) $s['copyright_en'], array( 'placeholder' => '© {year} {site}. All rights reserved.' ) );
								?>
							</div>
						<?php sbek_section_close(); ?>
					</section>

					<?php // ── Social ────────────────────────────────────────────── ?>
					<section class="sbek-panel" id="sbek-panel-social" role="tabpanel" aria-labelledby="sbek-tab-social" data-sbek-panel="social">
						<?php sbek_section_open( 'share-2', __( 'Social profiles', 'sbek' ), __( 'Icons in the footer and "sameAs" links in structured data. Known platforms (Facebook, Instagram, YouTube, TikTok, Telegram, X, LinkedIn, WhatsApp) get their own icon.', 'sbek' ), sbek_add_button( 'socials' ) ); ?>
							<div class="sbek-repeater" data-sbek-rows="socials">
								<?php
								$socials = $s['socials'] ? $s['socials'] : array( array() );
								foreach ( array_values( $socials ) as $i => $row ) {
									sbek_repeater_row( 'socials', $i, (array) $row );
								}
								?>
							</div>
						<?php sbek_section_close(); ?>
					</section>

					<?php // ── Integrations ──────────────────────────────────────── ?>
					<section class="sbek-panel" id="sbek-panel-integrations" role="tabpanel" aria-labelledby="sbek-tab-integrations" data-sbek-panel="integrations">
						<?php sbek_section_open( 'trending-up', __( 'Analytics', 'sbek' ), __( 'Google Analytics 4 loads only when no Tag Manager container is set.', 'sbek' ) ); ?>
							<div class="sbek-grid sbek-grid--2">
								<?php
								sbek_option_input( "{$opt}[ga4_id]", __( 'Google Analytics 4 ID', 'sbek' ), (string) $s['ga4_id'], array( 'placeholder' => 'G-XXXXXXXXXX', 'dir' => 'ltr' ) );
								sbek_option_input( "{$opt}[gtm_id]", __( 'Google Tag Manager ID', 'sbek' ), (string) $s['gtm_id'], array( 'placeholder' => 'GTM-XXXXXXX', 'dir' => 'ltr' ) );
								?>
							</div>
						<?php sbek_section_close(); ?>

						<?php sbek_section_open( 'sliders', __( 'Custom code', 'sbek' ), __( 'Raw HTML/JS added to every public page. Only paste code from sources you trust.', 'sbek' ) ); ?>
							<?php
							sbek_option_textarea( "{$opt}[header_scripts]", __( 'Before </head>', 'sbek' ), (string) $s['header_scripts'], array( 'rows' => '5', 'code' => '1' ) );
							sbek_option_textarea( "{$opt}[footer_scripts]", __( 'Before </body>', 'sbek' ), (string) $s['footer_scripts'], array( 'rows' => '5', 'code' => '1' ) );
							?>
							<?php if ( ! current_user_can( 'unfiltered_html' ) ) : ?>
								<p class="sbek-field__help sbek-field__help--warn"><?php esc_html_e( 'Your role cannot save raw scripts; unsafe markup will be stripped on save.', 'sbek' ); ?></p>
							<?php endif; ?>
						<?php sbek_section_close(); ?>
					</section>

					<?php // ── Tools ─────────────────────────────────────────────── ?>
					<section class="sbek-panel" id="sbek-panel-tools" role="tabpanel" aria-labelledby="sbek-tab-tools" data-sbek-panel="tools">
						<?php sbek_render_demo_tools(); ?>
					</section>
				</div>
			</div>

			<div class="sbek-savebar">
				<span class="sbek-savebar__hint" data-sbek-dirty-hint hidden><?php esc_html_e( 'You have unsaved changes.', 'sbek' ); ?></span>
				<button type="submit" class="sbek-btn sbek-btn--primary"><?php esc_html_e( 'Save changes', 'sbek' ); ?></button>
			</div>
		</form>

		<?php // Separate form: forms can't nest, so the Design tab's button targets it via form="…". ?>
		<form id="sbek-reset-design-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" hidden>
			<input type="hidden" name="action" value="sbek_reset_design">
			<?php wp_nonce_field( SBEK_RESET_DESIGN_NONCE ); ?>
		</form>
	</div>

	<template id="sbek-tmpl-phones"><?php sbek_repeater_row( 'phones', null ); ?></template>
	<template id="sbek-tmpl-socials"><?php sbek_repeater_row( 'socials', null ); ?></template>
	<?php
}
