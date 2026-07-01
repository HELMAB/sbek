<?php
/**
 * Archive header: kicker, big title, optional description, and — for category
 * archives — quick chips to jump between sibling/child sub-sections.
 *
 * Used by archive.php (and shareable with other listing templates).
 */
$sbek_obj    = get_queried_object();
$sbek_is_cat = $sbek_obj instanceof WP_Term && 'category' === $sbek_obj->taxonomy;

$sbek_title = $sbek_obj instanceof WP_Term
	? sbek_term_name( $sbek_obj )
	: wp_strip_all_tags( get_the_archive_title() );

// Kicker: link back to the parent section for a child category.
$sbek_kicker_html = '';
if ( $sbek_is_cat && $sbek_obj->parent ) {
	$sbek_parent = get_term( $sbek_obj->parent, 'category' );
	if ( $sbek_parent && ! is_wp_error( $sbek_parent ) ) {
		$sbek_kicker_html = '<a href="' . esc_url( sbek_localize_url( get_category_link( $sbek_parent->term_id ) ) ) . '" class="text-sm font-bold uppercase tracking-wider text-sbek-red hover:underline">' . esc_html( sbek_term_name( $sbek_parent ) ) . '</a>';
	}
}

$sbek_desc = $sbek_obj instanceof WP_Term ? term_description( $sbek_obj ) : '';

// Sub-section chips: children of this category, or siblings if it's a leaf.
$sbek_chips = array();
if ( $sbek_is_cat ) {
	$sbek_chips = get_terms( array(
		'taxonomy'   => 'category',
		'parent'     => $sbek_obj->term_id,
		'hide_empty' => true,
	) );
	if ( ( is_wp_error( $sbek_chips ) || empty( $sbek_chips ) ) && $sbek_obj->parent ) {
		$sbek_chips = get_terms( array(
			'taxonomy'   => 'category',
			'parent'     => $sbek_obj->parent,
			'hide_empty' => true,
			'exclude'    => array( $sbek_obj->term_id ),
		) );
	}
}
?>
<header class="mb-8 border-b-2 border-ink pb-5">
    <?php if ( $sbek_kicker_html ) : ?>
        <div class="mb-2"><?php echo $sbek_kicker_html; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
    <?php endif; ?>

    <h1 class="flex items-center gap-3 font-display text-3xl font-extrabold uppercase tracking-tight text-ink sm:text-4xl">
        <span class="inline-block h-8 w-1.5 shrink-0 bg-sbek-red sm:h-9"></span>
        <?php echo esc_html( $sbek_title ); ?>
    </h1>

    <?php if ( $sbek_desc ) : ?>
        <p class="mt-3 max-w-2xl text-[15px] leading-relaxed text-gray-600"><?php echo esc_html( $sbek_desc ); ?></p>
    <?php endif; ?>

    <?php if ( ! is_wp_error( $sbek_chips ) && ! empty( $sbek_chips ) ) : ?>
        <div class="mt-5 flex flex-wrap gap-2">
            <?php foreach ( $sbek_chips as $sbek_chip ) : ?>
                <a href="<?php echo esc_url( sbek_localize_url( get_category_link( $sbek_chip->term_id ) ) ); ?>" class="rounded-full border border-gray-300 px-3.5 py-1.5 text-sm font-semibold text-gray-700 transition-colors hover:border-sbek-red hover:bg-sbek-red hover:text-white"><?php echo esc_html( sbek_term_name( $sbek_chip ) ); ?></a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</header>
