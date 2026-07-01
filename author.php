<?php get_header(); ?>

<main id="primary" class="site-main container mx-auto px-4 py-8">
    <?php
    $sbek_author_id  = get_queried_object_id();
    $sbek_author_bio = get_the_author_meta( 'description', $sbek_author_id );
    ?>
    <div class="flex items-center gap-4 border-b-2 border-sbek-red mb-6 pb-4">
        <?php echo get_avatar( $sbek_author_id, 64, '', '', array( 'class' => 'rounded-full flex-shrink-0' ) ); ?>
        <div>
            <h1 class="text-lg font-bold uppercase">
                <?php echo esc_html( t( 'posts_by_author', get_the_author_meta( 'display_name', $sbek_author_id ) ) ); ?>
            </h1>
            <?php if ( $sbek_author_bio ) : ?>
                <p class="text-gray-500 text-sm mt-1"><?php echo esc_html( $sbek_author_bio ); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <?php if ( have_posts() ) : ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
            <?php
            while ( have_posts() ) :
                the_post();
                get_template_part( 'template-parts/content-grid' );
            endwhile;
            ?>
        </div>

        <div class="pagination flex justify-center gap-2">
            <?php
            the_posts_pagination( array(
                'mid_size'  => 2,
                'prev_text' => '&larr;',
                'next_text' => '&rarr;',
                'class'     => 'flex gap-2',
            ) );
            ?>
        </div>
    <?php else : ?>
        <?php get_template_part( 'template-parts/content-none' ); ?>
    <?php endif; ?>
</main>

<?php get_footer(); ?>
