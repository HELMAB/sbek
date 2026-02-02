<?php
/**
 * Custom Meta Fields for Sabay Clone
 * Handles Trending Score, Sponsored Status, and Video URL.
 */

function sabay_add_custom_meta_boxes() {
    add_meta_box(
        'sabay_news_meta',
        'News Metadata',
        'sabay_render_news_meta_box',
        'post',
        'side',
        'high'
    );
}
add_action( 'add_meta_boxes', 'sabay_add_custom_meta_boxes' );

function sabay_render_news_meta_box( $post ) {
    // Nonce for verification
    wp_nonce_field( 'sabay_save_news_meta', 'sabay_news_meta_nonce' );

    $trending_score = get_post_meta( $post->ID, '_sabay_trending_score', true );
    $is_sponsored   = get_post_meta( $post->ID, '_sabay_is_sponsored', true );
    $video_url      = get_post_meta( $post->ID, '_sabay_video_url', true );
    ?>
    <div style="margin-top: 10px;">
        <label for="sabay_trending_score" style="font-weight:bold; display:block;">Trending Score (0-100)</label>
        <input type="number" id="sabay_trending_score" name="sabay_trending_score" value="<?php echo esc_attr( $trending_score ); ?>" style="width:100%;" placeholder="0">
        <p class="description">Higher numbers appear in "Trending" sidebar.</p>
    </div>

    <div style="margin-top: 15px; border-top:1px solid #eee; padding-top:10px;">
        <label for="sabay_video_url" style="font-weight:bold; display:block;">Video URL (Embed)</label>
        <input type="url" id="sabay_video_url" name="sabay_video_url" value="<?php echo esc_attr( $video_url ); ?>" style="width:100%;" placeholder="https://youtube.com/...">
    </div>

    <div style="margin-top: 15px; border-top:1px solid #eee; padding-top:10px;">
        <label style="font-weight:bold;">
            <input type="checkbox" name="sabay_is_sponsored" value="1" <?php checked( $is_sponsored, 1 ); ?> />
            Mark as Sponsored / Advertorial
        </label>
    </div>
    <?php
}

function sabay_save_news_meta( $post_id ) {
    // Check nonce
    if ( ! isset( $_POST['sabay_news_meta_nonce'] ) ) return;
    if ( ! wp_verify_nonce( $_POST['sabay_news_meta_nonce'], 'sabay_save_news_meta' ) ) return;

    // Check autosave
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

    // Check permissions
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    // Save Trending Score
    if ( isset( $_POST['sabay_trending_score'] ) ) {
        update_post_meta( $post_id, '_sabay_trending_score', sanitize_text_field( $_POST['sabay_trending_score'] ) );
    }

    // Save Video URL
    if ( isset( $_POST['sabay_video_url'] ) ) {
        update_post_meta( $post_id, '_sabay_video_url', esc_url_raw( $_POST['sabay_video_url'] ) );
    }

    // Save Sponsored (Checkbox logic: if missing in POST, it's false)
    $is_sponsored = isset( $_POST['sabay_is_sponsored'] ) ? 1 : 0;
    update_post_meta( $post_id, '_sabay_is_sponsored', $is_sponsored );
}
add_action( 'save_post', 'sabay_save_news_meta' );
