<article id="post-<?php the_ID(); ?>" <?php post_class( 'bg-white rounded shadow-sm overflow-hidden p-6 mb-6' ); ?>>
    <header class="entry-header mb-4">
        <?php
        $categories = get_the_category();
        if ( ! empty( $categories ) ) {
            echo '<a href="' . esc_url( sabay_localize_url( get_category_link( $categories[0]->term_id ) ) ) . '" class="text-sabay-red font-bold uppercase text-sm">' . esc_html( sabay_term_name( $categories[0] ) ) . '</a>';
        }
        ?>
        <h2 class="text-xl font-bold mt-1 mb-2 leading-snug">
            <a href="<?php echo esc_url( sabay_localize_url( get_permalink() ) ); ?>" class="hover:text-sabay-red transition-colors"><?php the_title(); ?></a>
        </h2>
        <span class="text-gray-500 text-xs"><?php echo get_the_date(); ?></span>
    </header>

    <?php if ( has_post_thumbnail() ) : ?>
        <a href="<?php echo esc_url( sabay_localize_url( get_permalink() ) ); ?>" class="block mb-4 rounded overflow-hidden">
            <?php the_post_thumbnail( 'medium', [ 'class' => 'w-full h-auto' ] ); ?>
        </a>
    <?php endif; ?>

    <div class="entry-summary text-gray-700 leading-relaxed">
        <?php the_excerpt(); ?>
    </div>
</article>
