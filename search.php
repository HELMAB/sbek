<?php get_header(); ?>

<main id="primary" class="site-main container mx-auto px-4 py-8">

    <header class="mb-8 border-b-2 border-ink pb-5">
        <p class="mb-2 text-sm font-bold uppercase tracking-wider text-sbek-red"><?php echo esc_html( t( 'search_results_label' ) ); ?></p>
        <h1 class="flex items-center gap-3 font-display text-3xl font-extrabold tracking-tight text-ink sm:text-4xl">
            <span class="inline-block h-8 w-1.5 shrink-0 bg-sbek-red sm:h-9"></span>
            <?php echo esc_html( get_search_query() ); ?>
        </h1>

        <div class="mt-5 flex flex-wrap items-center justify-between gap-4">
            <p class="text-sm text-gray-500">
                <?php
                global $wp_query;
                echo esc_html( sprintf( _n( '%s result', '%s results', (int) $wp_query->found_posts, 'sbek' ), number_format_i18n( (int) $wp_query->found_posts ) ) );
                ?>
            </p>

            <form method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" class="flex items-center gap-2">
                <input type="hidden" name="s" value="<?php echo esc_attr( get_search_query() ); ?>">
                <label for="sbek-search-cat" class="text-sm font-semibold text-gray-600"><?php echo esc_html( t( 'filter_by_category' ) ); ?></label>
                <select id="sbek-search-cat" name="cat" onchange="this.form.submit()" class="rounded-sm border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-ink focus:border-sbek-red focus:outline-none focus:ring-1 focus:ring-sbek-red">
                    <option value=""><?php echo esc_html( t( 'all_categories' ) ); ?></option>
                    <?php
                    $sbek_search_cat = isset( $_GET['cat'] ) ? absint( $_GET['cat'] ) : 0;
                    foreach ( get_categories( array( 'number' => 30 ) ) as $sbek_search_category ) :
                        ?>
                        <option value="<?php echo esc_attr( $sbek_search_category->term_id ); ?>" <?php selected( $sbek_search_cat, $sbek_search_category->term_id ); ?>>
                            <?php echo esc_html( sbek_term_name( $sbek_search_category ) ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
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

<?php get_footer(); ?>
