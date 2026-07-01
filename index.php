<?php
// Generic fallback listing (rarely reached — front-page/home/archive/single
// cover the common views). Mirrors the archive grid for visual consistency.
get_header();
?>

<main id="primary" class="site-main container mx-auto px-4 py-8">

    <header class="mb-8 border-b-2 border-ink pb-5">
        <h1 class="flex items-center gap-3 font-display text-3xl font-extrabold uppercase tracking-tight text-ink sm:text-4xl">
            <span class="inline-block h-8 w-1.5 shrink-0 bg-sbek-red sm:h-9"></span>
            <?php echo esc_html( t( 'latest_news' ) ); ?>
        </h1>
    </header>

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

<?php
get_footer();
