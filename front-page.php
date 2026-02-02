<?php get_header(); ?>

<main id="primary" class="site-main">
    <!-- Hero Section -->
    <section class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-12 gap-4">
            <!-- Large Featured Post -->
            <div class="col-span-12 lg:col-span-6 bg-white rounded shadow-sm overflow-hidden group">
                <?php
                $hero_query = new WP_Query( array( 'posts_per_page' => 1 ) );
                if ( $hero_query->have_posts() ) : while ( $hero_query->have_posts() ) : $hero_query->the_post();
                ?>
                    <div class="relative h-64 lg:h-96 overflow-hidden">
                        <?php if ( has_post_thumbnail() ) : ?>
                            <?php the_post_thumbnail('large', ['loading' => 'eager', 'class' => 'w-full h-full object-cover group-hover:scale-105 transition-transform duration-500']); ?>
                        <?php else : ?>
                            <div class="w-full h-full bg-gray-200"></div>
                        <?php endif; ?>
                        <div class="absolute bottom-0 left-0 right-0 p-6 bg-gradient-to-t from-black/80 to-transparent">
                            <span class="bg-sabay-red text-white text-xs font-bold px-2 py-1 uppercase mb-2 inline-block">Featured</span>
                            <h2 class="text-white text-2xl lg:text-4xl font-bold leading-tight">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h2>
                        </div>
                    </div>
                <?php endwhile; wp_reset_postdata(); endif; ?>
            </div>

            <!-- Side Featured Posts -->
            <div class="col-span-12 lg:col-span-6 grid grid-cols-2 gap-4">
                <?php
                $side_query = new WP_Query( array( 'posts_per_page' => 4, 'offset' => 1 ) );
                if ( $side_query->have_posts() ) : while ( $side_query->have_posts() ) : $side_query->the_post();
                ?>
                    <div class="bg-white rounded shadow-sm overflow-hidden group">
                        <div class="relative h-32 lg:h-44 overflow-hidden">
                            <?php if ( has_post_thumbnail() ) : ?>
                                <?php the_post_thumbnail('medium', ['class' => 'w-full h-full object-cover group-hover:scale-105 transition-transform duration-500']); ?>
                            <?php else : ?>
                                <div class="w-full h-full bg-gray-200"></div>
                            <?php endif; ?>
                        </div>
                        <div class="p-3">
                            <h3 class="text-sm lg:text-base font-bold line-clamp-2 leading-snug">
                                <a href="<?php the_permalink(); ?>" class="hover:text-sabay-red transition-colors"><?php the_title(); ?></a>
                            </h3>
                        </div>
                    </div>
                <?php endwhile; wp_reset_postdata(); endif; ?>
            </div>
        </div>
    </section>

    <!-- News Sections by Category -->
    <?php
    $categories = get_categories( array('number' => 3) );
    foreach ( $categories as $category ) :
    ?>
    <section class="scroll-reveal container mx-auto px-4 py-8">
        <div class="flex items-center justify-between border-b-2 border-sabay-red mb-6">
            <h2 class="bg-sabay-red text-white px-4 py-1 text-lg font-bold uppercase"><?php echo $category->name; ?></h2>
            <a href="<?php echo get_category_link($category->term_id); ?>" class="text-sabay-red text-sm font-bold hover:underline">View All &rarr;</a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php
            $cat_posts = new WP_Query( array(
                'cat' => $category->term_id,
                'posts_per_page' => 4
            ) );
            if ( $cat_posts->have_posts() ) : while ( $cat_posts->have_posts() ) : $cat_posts->the_post();
                get_template_part( 'template-parts/content-grid' );
            endwhile; wp_reset_postdata(); endif;
            ?>
        </div>
    </section>
    <?php endforeach; ?>

    <!-- Latest News (Infinite Scroll) -->
    <section class="container mx-auto px-4 py-8 mb-12">
        <div class="flex items-center justify-between border-b-2 border-gray-900 mb-6">
            <h2 class="bg-gray-900 text-white px-4 py-1 text-lg font-bold uppercase">Latest News</h2>
        </div>
        
        <div id="news-grid-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php
            $latest = new WP_Query( array(
                'posts_per_page' => 4,
                'paged'          => 1
            ) );
            if ( $latest->have_posts() ) : while ( $latest->have_posts() ) : $latest->the_post();
                get_template_part( 'template-parts/content-grid' );
            endwhile; wp_reset_postdata(); endif;
            ?>
        </div>

        <div class="text-center mt-12">
            <button id="load-more-posts" class="bg-gray-200 hover:bg-sabay-red hover:text-white text-gray-800 font-bold py-3 px-8 rounded-full transition-colors duration-300">
                Load More News
            </button>
        </div>
    </section>

</main>

<?php get_footer(); ?>
