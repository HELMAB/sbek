<?php
/**
 * Custom Meta Fields for Sbek Clone
 * Handles Trending Score, Sponsored Status, and Video URL.
 */

function sbek_add_custom_meta_boxes() {
    add_meta_box(
        'sbek_news_meta',
        'News Metadata',
        'sbek_render_news_meta_box',
        'post',
        'side',
        'high'
    );
}
add_action( 'add_meta_boxes', 'sbek_add_custom_meta_boxes' );

function sbek_render_news_meta_box( $post ) {
    // Nonce for verification
    wp_nonce_field( 'sbek_save_news_meta', 'sbek_news_meta_nonce' );

    $trending_score = get_post_meta( $post->ID, '_sbek_trending_score', true );
    $is_sponsored   = get_post_meta( $post->ID, '_sbek_is_sponsored', true );
    $video_url      = get_post_meta( $post->ID, '_sbek_video_url', true );
    ?>
    <div style="margin-top: 10px;">
        <label for="sbek_trending_score" style="font-weight:bold; display:block;">Trending Score (0-100)</label>
        <input type="number" id="sbek_trending_score" name="sbek_trending_score" value="<?php echo esc_attr( $trending_score ); ?>" style="width:100%;" placeholder="0">
        <p class="description">Higher numbers appear in "Trending" sidebar.</p>
    </div>

    <div style="margin-top: 15px; border-top:1px solid #eee; padding-top:10px;">
        <label for="sbek_video_url" style="font-weight:bold; display:block;">Video URL (Embed)</label>
        <input type="url" id="sbek_video_url" name="sbek_video_url" value="<?php echo esc_attr( $video_url ); ?>" style="width:100%;" placeholder="https://youtube.com/...">
    </div>

    <div style="margin-top: 15px; border-top:1px solid #eee; padding-top:10px;">
        <label style="font-weight:bold;">
            <input type="checkbox" name="sbek_is_sponsored" value="1" <?php checked( $is_sponsored, 1 ); ?> />
            Mark as Sponsored / Advertorial
        </label>
    </div>
    <?php
}

function sbek_save_news_meta( $post_id ) {
    // Check nonce
    if ( ! isset( $_POST['sbek_news_meta_nonce'] ) ) return;
    if ( ! wp_verify_nonce( $_POST['sbek_news_meta_nonce'], 'sbek_save_news_meta' ) ) return;

    // Check autosave
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

    // Check permissions
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    // Save Trending Score
    if ( isset( $_POST['sbek_trending_score'] ) ) {
        update_post_meta( $post_id, '_sbek_trending_score', sanitize_text_field( $_POST['sbek_trending_score'] ) );
    }

    // Save Video URL
    if ( isset( $_POST['sbek_video_url'] ) ) {
        update_post_meta( $post_id, '_sbek_video_url', esc_url_raw( $_POST['sbek_video_url'] ) );
    }

    // Save Sponsored (Checkbox logic: if missing in POST, it's false)
    $is_sponsored = isset( $_POST['sbek_is_sponsored'] ) ? 1 : 0;
    update_post_meta( $post_id, '_sbek_is_sponsored', $is_sponsored );
}
add_action( 'save_post', 'sbek_save_news_meta' );
