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
    
    if ( has_post_thumbnail( $post->ID ) ) {
        $img_src = wp_get_attachment_image_url( get_post_thumbnail_id( $post->ID ), 'large' );
        echo '<meta property="og:image" content="' . esc_url( $img_src ) . '" />' . "\n";
    }

    echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
    
    // Description from excerpt or content
    $excerpt = has_excerpt( $post->ID ) ? $post->post_excerpt : wp_trim_words( $post->post_content, 30 );
    echo '<meta property="og:description" content="' . esc_attr( strip_tags( $excerpt ) ) . '" />' . "\n";
}
add_action( 'wp_head', 'sabay_seo_meta_tags' );

