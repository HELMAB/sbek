<?php get_header(); ?>

<main id="primary" class="site-main container mx-auto px-4 py-8">

    <?php get_template_part( 'template-parts/archive-header' ); ?>

    <?php if ( have_posts() ) : ?>
        <div class="mb-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <?php
            while ( have_posts() ) :
                the_post();
                get_template_part( 'template-parts/content-grid' );
            endwhile;
            ?>
        </div>

        <div class="pagination">
            <?php
            the_posts_pagination( array(
                'mid_size'  => 1,
                'prev_text' => '&larr;',
                'next_text' => '&rarr;',
            ) );
            ?>
        </div>
    <?php else : ?>
        <?php get_template_part( 'template-parts/content-none' ); ?>
    <?php endif; ?>
</main>

<?php get_footer(); ?>
