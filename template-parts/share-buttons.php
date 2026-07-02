<?php
/**
 * Share buttons row: Facebook / X / copy link / native share sheet.
 *
 * Args (both optional, default to the current post):
 * - 'url'   Localized permalink to share.
 * - 'title' Share title.
 *
 * The native-share button ships hidden; assets/js/motion.js reveals it only
 * when navigator.share exists (mobile share sheet). The copy button's
 * feedback bubble is an aria-live region filled by JS on success.
 */

$sbek_share_url   = $args['url'] ?? sbek_localize_url( get_permalink() );
$sbek_share_title = $args['title'] ?? get_the_title();
$sbek_share_btn   = 'flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-gray-600 transition-colors hover:bg-sbek-red hover:text-white';
?>
<div class="flex items-center gap-3">
    <a href="<?php echo esc_url( 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $sbek_share_url ) ); ?>" target="_blank" rel="noopener noreferrer" aria-label="Facebook" class="<?php echo esc_attr( $sbek_share_btn ); ?>">
        <?php echo sbek_icon( 'facebook', [ 'width' => 16, 'height' => 16 ] ); ?>
    </a>
    <a href="<?php echo esc_url( 'https://twitter.com/intent/tweet?url=' . rawurlencode( $sbek_share_url ) . '&text=' . rawurlencode( $sbek_share_title ) ); ?>" target="_blank" rel="noopener noreferrer" aria-label="X (Twitter)" class="<?php echo esc_attr( $sbek_share_btn ); ?>">
        <?php echo sbek_icon( 'x', [ 'width' => 15, 'height' => 15 ] ); ?>
    </a>
    <button type="button" class="sbek-copy-link relative <?php echo esc_attr( $sbek_share_btn ); ?>" data-url="<?php echo esc_attr( $sbek_share_url ); ?>" aria-label="<?php echo esc_attr( t( 'copy_link' ) ); ?>">
        <?php echo sbek_icon( 'link', [ 'width' => 16, 'height' => 16 ] ); ?>
        <span class="sbek-copy-feedback pointer-events-none absolute -top-9 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-sm bg-ink px-2 py-1 text-xs text-white opacity-0 transition-opacity" role="status" data-copied-text="<?php echo esc_attr( t( 'link_copied' ) ); ?>"></span>
    </button>
    <button type="button" class="sbek-native-share hidden <?php echo esc_attr( $sbek_share_btn ); ?>" data-url="<?php echo esc_attr( $sbek_share_url ); ?>" data-title="<?php echo esc_attr( $sbek_share_title ); ?>" aria-label="<?php echo esc_attr( t( 'share' ) ); ?>">
        <?php echo sbek_icon( 'share-2', [ 'width' => 16, 'height' => 16 ] ); ?>
    </button>
</div>
