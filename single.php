<?php get_header(); ?>

<main id="primary" class="site-main container mx-auto px-4 py-8">
    <div class="grid grid-cols-12 gap-8">
        <!-- Main Content -->
        <article id="post-<?php the_ID(); ?>" class="col-span-12 lg:col-span-8 bg-white p-6 lg:p-10 rounded shadow-sm">
            <?php while ( have_posts() ) : the_post(); ?>
                <header class="entry-header mb-8">
                    <?php
                    $categories = get_the_category();
                    if ( ! empty( $categories ) ) {
                        echo '<span class="text-sbek-red font-bold uppercase text-sm">' . esc_html( $categories[0]->name ) . '</span>';
                    }
                    ?>
                    <h1 class="text-3xl lg:text-5xl font-bold mt-2 mb-4 leading-tight"><?php the_title(); ?></h1>
                    
                    <div class="flex items-center text-gray-500 text-sm mb-6 pb-6 border-b border-gray-100">
                        <span class="font-bold text-gray-900 mr-2"><?php the_author(); ?></span>
                        <span class="mr-2">•</span>
                        <span><?php echo get_the_date(); ?></span>
                        <span class="ml-auto flex space-x-4">
                            <!-- Placeholder for Social Share -->
                            <span class="text-blue-600 font-bold cursor-pointer">Facebook</span>
                            <span class="text-sky-400 font-bold cursor-pointer">Twitter</span>
                        </span>
                    </div>

                    <?php if ( has_post_thumbnail() ) : ?>
                        <div class="featured-image mb-8 rounded-lg overflow-hidden">
                            <?php the_post_thumbnail('full', ['class' => 'w-full h-auto']); ?>
                        </div>
                    <?php endif; ?>
                </header>

                <div class="entry-content prose prose-lg max-w-none text-gray-800 leading-relaxed">
                    <?php the_content(); ?>
                </div>

                <footer class="entry-footer mt-12 pt-8 border-t border-gray-100">
                    <div class="tags">
                        <?php the_tags('<span class="text-gray-500 mr-2">Tags:</span>', ' ', ''); ?>
                    </div>
                </footer>

                <?php comments_template(); ?>
            <?php endwhile; ?>
        </article>

        <!-- Sidebar -->
        <aside class="col-span-12 lg:col-span-4 space-y-8">
            <div class="bg-white p-6 rounded shadow-sm">
                <h3 class="text-lg font-bold border-b-2 border-sbek-red pb-2 mb-4 uppercase"><?php echo esc_html( t( 'trending' ) ); ?></h3>
                <div class="space-y-4">
                    <?php
                    $trending = sbek_get_cached_query( array(
                        'posts_per_page' => 5,
                        'meta_key'       => '_sbek_trending_score',
                        'orderby'        => 'meta_value_num',
                        'order'          => 'DESC',
                    ), 'sbek_trending_sidebar_' . sbek_lang(), 3600 ); // Cache per language, 1 hour
                    
                    if ( $trending->have_posts() ) : while ( $trending->have_posts() ) : $trending->the_post();
                    ?>
                        <div class="flex space-x-3 group">
                            <div class="flex-shrink-0 w-20 h-16 bg-gray-200 rounded overflow-hidden relative">
                                <?php if ( has_post_thumbnail() ) the_post_thumbnail('thumbnail', ['class' => 'w-full h-full object-cover']); ?>
                                <?php if ( get_post_meta( get_the_ID(), '_sbek_video_url', true ) ) : ?>
                                    <div class="absolute inset-0 flex items-center justify-center bg-black/30">
                                        <div class="w-6 h-6 bg-white/80 rounded-full flex items-center justify-center pl-0.5">
                                            <svg class="w-3 h-3 text-sbek-red fill-current" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <h4 class="text-sm font-bold group-hover:text-sbek-red transition-colors line-clamp-3">
                                <a href="<?php echo esc_url( sbek_localize_url( get_permalink() ) ); ?>"><?php the_title(); ?></a>
                            </h4>
                        </div>
                    <?php endwhile; wp_reset_postdata(); endif; ?>
                </div>
            </div>

            <!-- Ad Placeholder -->
            <div class="bg-gray-200 h-[600px] flex items-center justify-center text-gray-400 font-bold italic rounded">
                <?php echo esc_html( t( 'advertisement' ) ); ?>
            </div>
        </aside>
    </div>
</main>

<?php get_footer(); ?>
