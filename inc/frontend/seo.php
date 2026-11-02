<?php
/**
 * Open Graph / Twitter tags and schema.org JSON-LD.
 *
 * Steps aside when a dedicated SEO plugin (Yoast, Rank Math, SEOPress, AIOSEO)
 * is active, so pages never carry two competing sets of tags.
 *
 * @package sbek
 */

defined( 'ABSPATH' ) || exit;

function sbek_seo_plugin_active(): bool {
    return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'SEOPRESS_VERSION' ) || defined( 'AIOSEO_VERSION' );
}

function sbek_seo_meta_tags() {
    global $post;

    if ( ! is_singular() || sbek_seo_plugin_active() ) {
        return;
    }

    echo '<!-- Open Graph Meta Tags -->' . "\n";
    echo '<meta property="og:title" content="' . esc_attr( wp_strip_all_tags( get_the_title() ) ) . '" />' . "\n";
    echo '<meta property="og:type" content="article" />' . "\n";
    echo '<meta property="og:url" content="' . esc_url( get_permalink() ) . '" />' . "\n";
    echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '" />' . "\n";
    echo '<meta property="og:locale" content="' . esc_attr( str_replace( '-', '_', get_bloginfo( 'language' ) ) ) . '" />' . "\n";

    // Featured image, falling back to the site-wide default share image.
    $img_src = has_post_thumbnail( $post->ID )
        ? wp_get_attachment_image_url( get_post_thumbnail_id( $post->ID ), 'large' )
        : sbek_site_setting_image_url( 'og_image', 'large' );

    if ( $img_src ) {
        echo '<meta property="og:image" content="' . esc_url( $img_src ) . '" />' . "\n";
    }

    echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
    echo '<meta property="og:description" content="' . esc_attr( sbek_seo_description( $post ) ) . '" />' . "\n";
}
add_action( 'wp_head', 'sbek_seo_meta_tags' );

/**
 * Shared excerpt/description text for OG tags and NewsArticle JSON-LD.
 */
function sbek_seo_description( WP_Post $post ): string {
    $excerpt = has_excerpt( $post->ID ) ? $post->post_excerpt : wp_trim_words( $post->post_content, 30 );
    $excerpt = trim( preg_replace( '/[\s\x{200B}]+/u', ' ', wp_strip_all_tags( strip_shortcodes( $excerpt ) ) ) );

    // Khmer has no spaces between words, so word trimming can't shorten it — cap by characters.
    return mb_strlen( $excerpt ) > 200 ? mb_substr( $excerpt, 0, 199 ) . '…' : $excerpt;
}

/**
 * Output NewsMediaOrganization JSON-LD structured data (site-wide).
 *
 * Built from the values collected on the Options page: description, logo,
 * socials (sameAs), phones (contactPoint), and lat/lng (location).
 */
function sbek_organization_jsonld() {
    if ( ! is_front_page() || sbek_seo_plugin_active() ) {
        return; // Once, on the home page, is what search engines need.
    }

    $settings = sbek_site_setting();

    $data = [
        '@context' => 'https://schema.org',
        '@type'    => 'NewsMediaOrganization',
        'name'     => get_bloginfo( 'name' ),
        'url'      => sbek_home_url(),
    ];

    $description = sbek_site_text( 'description' );
    if ( '' !== $description ) {
        $data['description'] = $description;
    }

    $logo = sbek_site_setting_image_url( 'logo', 'full' );
    if ( $logo ) {
        $data['logo'] = $logo;
    }

    // Social profiles → sameAs.
    if ( ! empty( $settings['socials'] ) ) {
        $same_as = array_values( array_filter( wp_list_pluck( $settings['socials'], 'url' ) ) );
        if ( $same_as ) {
            $data['sameAs'] = $same_as;
        }
    }

    // Phone numbers → contactPoint.
    if ( ! empty( $settings['phones'] ) ) {
        $contacts = [];
        foreach ( $settings['phones'] as $phone ) {
            if ( empty( $phone['number'] ) ) {
                continue;
            }
            $contact = [
                '@type'     => 'ContactPoint',
                'telephone' => $phone['number'],
            ];
            if ( ! empty( $phone['label'] ) ) {
                $contact['contactType'] = $phone['label'];
            }
            $contacts[] = $contact;
        }
        if ( $contacts ) {
            $data['contactPoint'] = $contacts;
        }
    }

    // Coordinates → location/geo.
    if ( '' !== $settings['lat'] && '' !== $settings['lng'] ) {
        $data['location'] = [
            '@type' => 'Place',
            'geo'   => [
                '@type'     => 'GeoCoordinates',
                'latitude'  => $settings['lat'],
                'longitude' => $settings['lng'],
            ],
        ];
    }

    echo '<script type="application/ld+json">'
        . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
        . '</script>' . "\n";
}
add_action( 'wp_head', 'sbek_organization_jsonld' );

/**
 * Output NewsArticle JSON-LD structured data on single posts.
 *
 * This is what Google News / Discover crawlers key off — headline, images,
 * publish/modified dates, author, and a publisher block matching the
 * NewsMediaOrganization data above.
 */
function sbek_article_jsonld() {
    if ( ! is_singular( 'post' ) || sbek_seo_plugin_active() ) {
        return;
    }

    global $post;

    $data = [
        '@context'         => 'https://schema.org',
        '@type'            => 'NewsArticle',
        'headline'         => wp_strip_all_tags( get_the_title( $post ) ),
        'inLanguage'       => sbek_languages()[ sbek_lang() ]['hreflang'],
        'description'      => sbek_seo_description( $post ),
        'datePublished'    => get_the_date( 'c', $post ),
        'dateModified'     => get_the_modified_date( 'c', $post ),
        'mainEntityOfPage' => [
            '@type' => 'WebPage',
            '@id'   => get_permalink( $post ),
        ],
        'author'           => [
            '@type' => 'Person',
            'name'  => get_the_author_meta( 'display_name', $post->post_author ),
        ],
        'publisher'        => [
            '@type' => 'NewsMediaOrganization',
            'name'  => get_bloginfo( 'name' ),
        ],
    ];

    $logo = sbek_site_setting_image_url( 'logo', 'full' );
    if ( $logo ) {
        $data['publisher']['logo'] = [
            '@type' => 'ImageObject',
            'url'   => $logo,
        ];
    }

    $image_src = has_post_thumbnail( $post->ID )
        ? wp_get_attachment_image_url( get_post_thumbnail_id( $post->ID ), 'full' )
        : sbek_site_setting_image_url( 'og_image', 'full' );

    if ( $image_src ) {
        $data['image'] = [ $image_src ];
    }

    $category = sbek_primary_category( $post );
    if ( $category ) {
        $data['articleSection'] = $category->name;
    }

    echo '<script type="application/ld+json">'
        . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
        . '</script>' . "\n";
}
add_action( 'wp_head', 'sbek_article_jsonld' );

/**
 * Output BreadcrumbList JSON-LD on single posts (Home → Section → Article),
 * mirroring the visible breadcrumb trail rendered by single.php.
 */
function sbek_breadcrumb_jsonld() {
    if ( ! is_singular( 'post' ) || sbek_seo_plugin_active() ) {
        return;
    }

    global $post;

    $items = [
        [
            '@type'    => 'ListItem',
            'position' => 1,
            'name'     => __( 'Home', 'sbek' ),
            'item'     => sbek_home_url(),
        ],
    ];

    $position = 2;
    $category = sbek_primary_category( $post );

    if ( $category ) {
        $items[] = [
            '@type'    => 'ListItem',
            'position' => $position++,
            'name'     => $category->name,
            'item'     => get_category_link( $category->term_id ),
        ];
    }

    $items[] = [
        '@type'    => 'ListItem',
        'position' => $position,
        'name'     => wp_strip_all_tags( get_the_title( $post ) ),
        'item'     => get_permalink( $post ),
    ];

    echo '<script type="application/ld+json">'
        . wp_json_encode( [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $items,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
        . '</script>' . "\n";
}
add_action( 'wp_head', 'sbek_breadcrumb_jsonld' );

/**
 * Point crawlers at every language's sitemap. WordPress core already lists
 * the default (English) sitemap on virtual robots.txt via wp_sitemaps_robots();
 * this adds the /jp/ variant, which core has no awareness of. The front-end
 * language pre_get_posts filter in inc/i18n.php (scoped only by is_admin())
 * already makes wp-sitemap.xml itself language-aware per the active prefix.
 */
function sbek_add_localized_sitemaps_to_robots( $output, $public ) {
    if ( '1' !== (string) $public ) {
        return $output;
    }

    foreach ( array_keys( sbek_languages() ) as $lang ) {
        if ( SBEK_DEFAULT_LANGUAGE === $lang ) {
            continue; // Already added by WordPress core.
        }
        $output .= 'Sitemap: ' . esc_url( home_url( "/{$lang}/wp-sitemap.xml" ) ) . "\n";
    }

    return $output;
}
add_filter( 'robots_txt', 'sbek_add_localized_sitemaps_to_robots', 10, 2 );

