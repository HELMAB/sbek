<?php get_header(); ?>

<main id="primary" class="site-main container mx-auto px-4 py-8">
    <div class="flex items-center justify-between border-b-2 border-sbek-red mb-6">
        <h1 class="bg-sbek-red text-white px-4 py-1 text-lg font-bold uppercase">
            <?php echo esc_html( t( 'search_results', get_search_query() ) ); ?>
        </h1>
    </div>

    <form method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" class="flex flex-wrap items-center gap-2 mb-8">
        <input type="hidden" name="s" value="<?php echo esc_attr( get_search_query() ); ?>">
        <label for="sbek-search-cat" class="sr-only"><?php echo esc_html( t( 'filter_by_category' ) ); ?></label>
        <select id="sbek-search-cat" name="cat" onchange="this.form.submit()" class="border border-gray-300 rounded-sm px-3 py-2 text-sm focus:ring-1 focus:ring-sbek-red">
            <option value=""><?php echo esc_html( t( 'all_categories' ) ); ?></option>
            <?php
            $sbek_search_cat = isset( $_GET['cat'] ) ? absint( $_GET['cat'] ) : 0;
            foreach ( get_categories( array( 'number' => 20 ) ) as $sbek_search_category ) :
                ?>
                <option value="<?php echo esc_attr( $sbek_search_category->term_id ); ?>" <?php selected( $sbek_search_cat, $sbek_search_category->term_id ); ?>>
                    <?php echo esc_html( sbek_term_name( $sbek_search_category ) ); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

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
