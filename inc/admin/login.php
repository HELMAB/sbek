<?php
/**
 * Custom-branded wp-login.php — split layout (brand panel left, form right).
 * Pure hooks/CSS, no core files touched, so it survives WordPress updates.
 */

function sabay_login_enqueue(): void {
    wp_enqueue_style( 'sabay-clone-fonts', 'https://fonts.googleapis.com/css2?family=Battambang:wght@400;700&family=Kantumruy+Pro:wght@400;700&display=swap', array(), null );
    wp_enqueue_style(
        'sabay-login',
        get_template_directory_uri() . '/assets/css/login.css',
        array( 'login' ),
        wp_get_theme()->get( 'Version' )
    );
}
add_action( 'login_enqueue_scripts', 'sabay_login_enqueue' );

function sabay_login_logo_url(): string {
    return home_url( '/' );
}
add_filter( 'login_headerurl', 'sabay_login_logo_url' );

function sabay_login_logo_title(): string {
    return get_bloginfo( 'name' );
}
add_filter( 'login_headertext', 'sabay_login_logo_title' );

/**
 * Give WordPress's own #login h1.wp-login-logo (already positioned at the
 * top of the form card by core) our site logo instead of the WordPress mark.
 * There's no filter for this markup, so a tiny inline <style> block is the
 * standard, update-safe way to swap the background-image WP renders it with.
 */
function sabay_login_logo_styles(): void {
    $logo = function_exists( 'sabay_site_setting_image_url' ) ? sabay_site_setting_image_url( 'logo', 'medium' ) : '';

    if ( $logo ) {
        printf(
            '<style>#login h1.wp-login-logo a{background-image:url(%s);}</style>',
            esc_url( $logo )
        );
    } else {
        // No logo uploaded yet — fall back to a text wordmark instead of a broken image.
        printf(
            '<style>#login h1.wp-login-logo a{background-image:none;text-indent:0;font-size:1.375rem;font-weight:800;color:%s;text-transform:uppercase;letter-spacing:.02em;}</style>',
            '#e31e24'
        );
    }
}
add_action( 'login_head', 'sabay_login_logo_styles' );

/**
 * Site name + short description, rendered below the logo. `login_message`
 * fires right after the h1 logo and before the login form/error messages —
 * the standard hook for content in exactly this spot.
 */
function sabay_login_message( string $message ): string {
    ob_start();
    ?>
    <div class="sabay-login-intro text-center">
        <p class="sabay-login-intro__site"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></p>
        <p class="sabay-login-intro__desc"><?php esc_html_e( 'Welcome back! Sign in to manage your site.', 'sabay-clone' ); ?></p>
    </div>
    <?php
    return ob_get_clean() . $message;
}
add_filter( 'login_message', 'sabay_login_message' );

/**
 * Placeholder text on the username/password fields. WordPress core hardcodes
 * that markup with no filter, so the documented approach is a small inline
 * script (fired on login_footer, after the fields exist in the DOM).
 */
function sabay_login_footer_script(): void {
    ?>
    <script>
    ( function () {
        var user = document.getElementById( 'user_login' );
        if ( user ) {
            user.setAttribute( 'placeholder', <?php echo wp_json_encode( __( 'you@example.com', 'sabay-clone' ) ); ?> );
        }
        var pass = document.getElementById( 'user_pass' );
        if ( pass ) {
            pass.setAttribute( 'placeholder', <?php echo wp_json_encode( __( 'Enter your password', 'sabay-clone' ) ); ?> );
        }
    } )();
    </script>
    <?php
}
add_action( 'login_footer', 'sabay_login_footer_script' );

/**
 * Fires right after <body> opens, before #login — gives us a sibling div to
 * lay out as the left brand panel via CSS flexbox in login.css.
 */
function sabay_login_brand_panel(): void {
    ?>
    <div class="sabay-login-panel">
        <div class="sabay-login-panel__grid" aria-hidden="true"></div>
        <span class="sabay-login-shape sabay-login-shape--1" aria-hidden="true"></span>
        <span class="sabay-login-shape sabay-login-shape--2" aria-hidden="true"></span>
        <span class="sabay-login-shape sabay-login-shape--3" aria-hidden="true"></span>

        <div class="sabay-login-panel__content">
            <h1 class="sabay-login-panel__title"><?php esc_html_e( 'Stay ahead of the story.', 'sabay-clone' ); ?></h1>
            <p class="sabay-login-panel__subtitle"><?php esc_html_e( 'Sign in to publish, edit, and manage the news your readers trust.', 'sabay-clone' ); ?></p>
        </div>
    </div>
    <?php
}
add_action( 'login_header', 'sabay_login_brand_panel' );
