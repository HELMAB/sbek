<?php get_header(); ?>

<main id="primary" class="site-main container mx-auto px-4 py-8">
    <div class="flex items-center justify-between border-b-2 border-sbek-red mb-6">
        <h1 class="bg-sbek-red text-white px-4 py-1 text-lg font-bold uppercase">
            <?php echo esc_html( t( 'search_results', get_search_query() ) ); ?>
        </h1>
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
            the_posts_pagination( [
                'mid_size'  => 2,
                'prev_text' => '&larr;',
                'next_text' => '&rarr;',
                'class'     => 'flex gap-2',
            ] );
            ?>
        </div>
    <?php else : ?>
        <?php get_template_part( 'template-parts/content-none' ); ?>
    <?php endif; ?>
</main>

<?php get_footer(); ?>
