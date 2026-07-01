<?php
/**
 * Themed search form used by get_search_form() — the 404 page and the sidebar
 * search widget. Posts to the current-language home so results stay in context.
 */
$sbek_search_id = 'sbek-search-' . wp_unique_id();
?>
<form role="search" method="get" class="flex w-full" action="<?php echo esc_url( sbek_localize_url( home_url( '/' ) ) ); ?>">
    <label for="<?php echo esc_attr( $sbek_search_id ); ?>" class="sr-only"><?php echo esc_html( t( 'search' ) ); ?></label>
    <input
        type="search"
        id="<?php echo esc_attr( $sbek_search_id ); ?>"
        name="s"
        value="<?php echo esc_attr( get_search_query() ); ?>"
        placeholder="<?php echo esc_attr( t( 'search_placeholder' ) ); ?>"
        class="w-full rounded-l-sm border border-r-0 border-gray-300 bg-white px-4 py-2.5 text-sm text-ink placeholder:text-gray-400 focus:border-sbek-red focus:outline-none focus:ring-1 focus:ring-sbek-red"
    >
    <button type="submit" aria-label="<?php echo esc_attr( t( 'search' ) ); ?>" class="flex shrink-0 items-center justify-center rounded-r-sm bg-ink px-4 py-2.5 text-white transition-colors hover:bg-sbek-red">
        <?php echo sbek_icon( 'search', [ 'width' => 18, 'height' => 18 ] ); ?>
    </button>
</form>
