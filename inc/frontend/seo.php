<?php
/**
 * SEO & Open Graph Tags
 */

function sabay_seo_meta_tags() {
    global $post;

    if ( ! is_singular() ) return;

    echo '<!-- Open Graph Meta Tags -->' . "\n";
    echo '<meta property="og:title" content="' . get_the_title() . '" />' . "\n";
    echo '<meta property="og:type" content="article" />' . "\n";
    echo '<meta property="og:url" content="' . get_permalink() . '" />' . "\n";
    echo '<meta property="og:site_name" content="' . get_bloginfo('name') . '" />' . "\n";
    
    // Featured image, falling back to the site-wide default share image.
    $img_src = has_post_thumbnail( $post->ID )
        ? wp_get_attachment_image_url( get_post_thumbnail_id( $post->ID ), 'large' )
        : ( function_exists( 'sabay_site_setting_image_url' ) ? sabay_site_setting_image_url( 'og_image', 'large' ) : '' );

    if ( $img_src ) {
        echo '<meta property="og:image" content="' . esc_url( $img_src ) . '" />' . "\n";
    }

    echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
    
    // Description from excerpt or content
    $excerpt = has_excerpt( $post->ID ) ? $post->post_excerpt : wp_trim_words( $post->post_content, 30 );
    echo '<meta property="og:description" content="' . esc_attr( strip_tags( $excerpt ) ) . '" />' . "\n";
}
add_action( 'wp_head', 'sabay_seo_meta_tags' );

/**
 * Output NewsMediaOrganization JSON-LD structured data (site-wide).
 *
 * Built from the values collected on the Options page: description, logo,
 * socials (sameAs), phones (contactPoint), and lat/lng (location).
 */
function sabay_organization_jsonld() {
    if ( ! function_exists( 'sabay_site_setting' ) ) {
        return;
    }

    $settings = sabay_site_setting();

    $data = [
        '@context' => 'https://schema.org',
        '@type'    => 'NewsMediaOrganization',
        'name'     => get_bloginfo( 'name' ),
        'url'      => home_url( '/' ),
    ];

    if ( ! empty( $settings['description'] ) ) {
        $data['description'] = $settings['description'];
    }

    $logo = sabay_site_setting_image_url( 'logo', 'full' );
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
add_action( 'wp_head', 'sabay_organization_jsonld' );

