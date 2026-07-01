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
 * Fires right after <body> opens, before #login — gives us a sibling div to
 * lay out as the left brand panel via CSS flexbox in login.css.
 */
function sabay_login_brand_panel(): void {
    $logo = function_exists( 'sabay_site_setting_image_url' ) ? sabay_site_setting_image_url( 'logo', 'medium' ) : '';
    ?>
    <div class="sabay-login-panel">
        <span class="sabay-login-shape sabay-login-shape--1" aria-hidden="true"></span>
        <span class="sabay-login-shape sabay-login-shape--2" aria-hidden="true"></span>
        <span class="sabay-login-shape sabay-login-shape--3" aria-hidden="true"></span>

        <div class="sabay-login-panel__content">
            <?php if ( $logo ) : ?>
                <img src="<?php echo esc_url( $logo ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" class="sabay-login-panel__logo">
            <?php else : ?>
                <span class="sabay-login-panel__brand"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
            <?php endif; ?>

            <h1 class="sabay-login-panel__title"><?php esc_html_e( 'Stay ahead of the story.', 'sabay-clone' ); ?></h1>
            <p class="sabay-login-panel__subtitle"><?php esc_html_e( 'Sign in to publish, edit, and manage the news your readers trust.', 'sabay-clone' ); ?></p>
        </div>
    </div>
    <?php
}
add_action( 'login_header', 'sabay_login_brand_panel' );
