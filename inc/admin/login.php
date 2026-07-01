<?php
/**
 * Custom-branded wp-login.php — split layout (brand panel left, form right).
 * Pure hooks/CSS, no core files touched, so it survives WordPress updates.
 */

function sbek_login_enqueue(): void {
    wp_enqueue_style( 'sbek-fonts', 'https://fonts.googleapis.com/css2?family=Battambang:wght@400;700&family=Kantumruy+Pro:wght@400;700&display=swap', array(), null );
    wp_enqueue_style(
        'sbek-login',
        get_template_directory_uri() . '/assets/css/login.css',
        array( 'login' ),
        wp_get_theme()->get( 'Version' )
    );
}
add_action( 'login_enqueue_scripts', 'sbek_login_enqueue' );

/**
 * Language for the login screen only. This drives the t()-based copy this
 * file adds (headline, intro, placeholders, switcher) — see
 * sbek_login_translate_core_strings() below for WordPress core's own field
 * labels/buttons, which are a separate mechanism since they don't go through
 * t() at all. The front-end's /jp/ URL-prefix scheme doesn't apply to a
 * literal wp-login.php request, so a `?lang=` query var is used instead,
 * persisted in a cookie for the rest of the login flow (lostpassword, …).
 *
 * `login_init` fires after `init` — where inc/i18n.php already defaulted
 * $GLOBALS['sbek_lang'] to 'en' — so this safely overrides it.
 */
function sbek_login_set_language(): void {
    $requested = isset( $_GET['lang'] ) ? sanitize_key( wp_unslash( $_GET['lang'] ) ) : '';

    if ( $requested && in_array( $requested, SBEK_LANGUAGES, true ) ) {
        $GLOBALS['sbek_lang'] = $requested;
        setcookie( 'sbek_login_lang', $requested, time() + DAY_IN_SECONDS, '/', '', is_ssl(), true );
    } else {
        $cookie = isset( $_COOKIE['sbek_login_lang'] ) ? sanitize_key( wp_unslash( $_COOKIE['sbek_login_lang'] ) ) : '';

        if ( $cookie && in_array( $cookie, SBEK_LANGUAGES, true ) ) {
            $GLOBALS['sbek_lang'] = $cookie;
        }
    }

    if ( 'jp' === sbek_lang() ) {
        add_filter( 'gettext', 'sbek_login_translate_core_strings', 10, 3 );
        add_filter( 'gettext_with_context', 'sbek_login_translate_core_strings_with_context', 10, 4 );
    }
}
add_action( 'login_init', 'sbek_login_set_language' );

/**
 * WordPress core hardcodes the login screen's field labels/buttons with
 * plain __()/_e() calls against its own 'default' textdomain — they never go
 * through this theme's t() system, so switching sbek_lang() alone doesn't
 * touch them. This swaps the known login/lostpassword/register-screen
 * strings when Japanese is active, scoped to the 'default' domain only so
 * nothing outside wp-login.php is affected.
 */
function sbek_login_translate_core_strings( string $translation, string $text, string $domain ): string {
    if ( 'default' !== $domain ) {
        return $translation;
    }

    $map = array(
        'Username or Email Address' => 'ユーザー名またはメールアドレス',
        'Password'                  => 'パスワード',
        'Remember Me'               => 'ログイン状態を保存する',
        'Log In'                    => 'ログイン',
        'Show password'             => 'パスワードを表示',
        'Hide password'             => 'パスワードを隠す',
        'Lost your password?'       => 'パスワードをお忘れですか?',
        'Lost Password'             => 'パスワードの再設定',
        'Get New Password'          => '新しいパスワードを取得',
        'Register'                  => '登録',
        'Register For This Site'    => 'このサイトに登録',
        'Please enter your username or email address. You will receive an email message with instructions on how to reset your password.' => 'ユーザー名またはメールアドレスを入力してください。パスワード再設定の手順を記載したメールをお送りします。',
    );

    return $map[ $text ] ?? $translation;
}

function sbek_login_translate_core_strings_with_context( string $translation, string $text, string $context, string $domain ): string {
    if ( 'default' === $domain && 'site' === $context && '&larr; Go to %s' === $text ) {
        return '&larr; %s に戻る';
    }

    return $translation;
}

/**
 * Top-right EN / JP switcher. Preserves the current screen (login,
 * lostpassword, register …) and any other query args already on the URL.
 */
function sbek_login_language_switcher(): void {
    $current = sbek_lang();
    $links   = [];

    foreach ( SBEK_LANGUAGES as $lang ) {
        $links[] = sprintf(
            '<a href="%s" class="sbek-login-lang-switch__link%s"%s>%s</a>',
            esc_url( add_query_arg( 'lang', $lang ) ),
            $lang === $current ? ' is-active' : '',
            $lang === $current ? ' aria-current="true"' : '',
            esc_html( strtoupper( $lang ) )
        );
    }
    ?>
    <div class="sbek-login-lang-switch">
        <?php echo implode( '<span class="sbek-login-lang-switch__divider">/</span>', $links ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- each link built from esc_url()/esc_html() above ?>
    </div>
    <?php
}
add_action( 'login_header', 'sbek_login_language_switcher' );

function sbek_login_logo_url(): string {
    return home_url( '/' );
}
add_filter( 'login_headerurl', 'sbek_login_logo_url' );

function sbek_login_logo_title(): string {
    return get_bloginfo( 'name' );
}
add_filter( 'login_headertext', 'sbek_login_logo_title' );

/**
 * Give WordPress's own #login h1.wp-login-logo (already positioned at the
 * top of the form card by core) our site logo instead of the WordPress mark.
 * There's no filter for this markup, so a tiny inline <style> block is the
 * standard, update-safe way to swap the background-image WP renders it with.
 */
function sbek_login_logo_styles(): void {
    $logo = function_exists( 'sbek_site_setting_image_url' ) ? sbek_site_setting_image_url( 'logo', 'medium' ) : '';

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
add_action( 'login_head', 'sbek_login_logo_styles' );

/**
 * Site name + short description, rendered below the logo. `login_message`
 * fires right after the h1 logo and before the login form/error messages —
 * the standard hook for content in exactly this spot.
 */
function sbek_login_message( string $message ): string {
    ob_start();
    ?>
    <div class="sbek-login-intro text-center">
        <p class="sbek-login-intro__site"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></p>
        <p class="sbek-login-intro__desc"><?php echo esc_html( t( 'login_welcome' ) ); ?></p>
    </div>
    <?php
    return ob_get_clean() . $message;
}
add_filter( 'login_message', 'sbek_login_message' );

/**
 * Placeholder text on the username/password fields. WordPress core hardcodes
 * that markup with no filter, so the documented approach is a small inline
 * script (fired on login_footer, after the fields exist in the DOM).
 */
function sbek_login_footer_script(): void {
    ?>
    <script>
    ( function () {
        var user = document.getElementById( 'user_login' );
        if ( user ) {
            user.setAttribute( 'placeholder', <?php echo wp_json_encode( t( 'login_user_placeholder' ) ); ?> );
        }
        var pass = document.getElementById( 'user_pass' );
        if ( pass ) {
            pass.setAttribute( 'placeholder', <?php echo wp_json_encode( t( 'login_pass_placeholder' ) ); ?> );
        }
    } )();
    </script>
    <?php
}
add_action( 'login_footer', 'sbek_login_footer_script' );

/**
 * Fires right after <body> opens, before #login — gives us a sibling div to
 * lay out as the left brand panel via CSS flexbox in login.css.
 */
function sbek_login_brand_panel(): void {
    ?>
    <div class="sbek-login-panel">
        <div class="sbek-login-panel__grid" aria-hidden="true"></div>
        <span class="sbek-login-shape sbek-login-shape--1" aria-hidden="true"></span>
        <span class="sbek-login-shape sbek-login-shape--2" aria-hidden="true"></span>
        <span class="sbek-login-shape sbek-login-shape--3" aria-hidden="true"></span>

        <div class="sbek-login-panel__content">
            <h1 class="sbek-login-panel__title"><?php echo esc_html( t( 'login_headline' ) ); ?></h1>
            <p class="sbek-login-panel__subtitle"><?php echo esc_html( t( 'login_subheadline' ) ); ?></p>
        </div>
    </div>
    <?php
}
add_action( 'login_header', 'sbek_login_brand_panel' );
