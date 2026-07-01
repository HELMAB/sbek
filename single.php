<?php get_header(); ?>

<main id="primary" class="site-main container mx-auto px-4 py-8">
    <div class="grid grid-cols-12 gap-8">
        <!-- Main Content -->
        <article id="post-<?php the_ID(); ?>" class="col-span-12 lg:col-span-8 bg-white p-6 lg:p-10 rounded-sm shadow-xs">
            <?php while ( have_posts() ) : the_post(); ?>
                <header class="entry-header mb-8">
                    <?php
                    $categories = get_the_category();
                    if ( ! empty( $categories ) ) {
                        echo '<span class="text-sbek-red font-bold uppercase text-sm">' . esc_html( $categories[0]->name ) . '</span>';
                    }
                    ?>
                    <h1 class="text-3xl lg:text-5xl font-bold mt-2 mb-4 leading-tight"><?php the_title(); ?></h1>

                    <?php
                    $sbek_share_url   = sbek_localize_url( get_permalink() );
                    $sbek_share_title = get_the_title();
                    ?>
                    <div class="flex items-center text-gray-500 text-sm mb-6 pb-6 border-b border-gray-100">
                        <a href="<?php echo esc_url( sbek_localize_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ) ); ?>" class="font-bold text-gray-900 mr-2 hover:text-sbek-red transition-colors"><?php the_author(); ?></a>
                        <span class="mr-2">•</span>
                        <span><?php echo get_the_date(); ?></span>
                        <span class="ml-auto flex items-center space-x-4">
                            <a href="<?php echo esc_url( 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $sbek_share_url ) ); ?>" target="_blank" rel="noopener noreferrer" aria-label="Facebook" class="text-blue-600 hover:text-blue-800">
                                <?php echo sbek_icon( 'facebook', [ 'width' => 18, 'height' => 18 ] ); ?>
                            </a>
                            <a href="<?php echo esc_url( 'https://twitter.com/intent/tweet?url=' . rawurlencode( $sbek_share_url ) . '&text=' . rawurlencode( $sbek_share_title ) ); ?>" target="_blank" rel="noopener noreferrer" aria-label="X (Twitter)" class="text-gray-900 hover:text-sbek-red">
                                <?php echo sbek_icon( 'x', [ 'width' => 18, 'height' => 18 ] ); ?>
                            </a>
                            <button type="button" class="sbek-copy-link relative text-gray-500 hover:text-sbek-red" data-url="<?php echo esc_attr( $sbek_share_url ); ?>" aria-label="<?php echo esc_attr( t( 'copy_link' ) ); ?>">
                                <?php echo sbek_icon( 'link', [ 'width' => 18, 'height' => 18 ] ); ?>
                                <span class="sbek-copy-feedback absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap bg-gray-900 text-white text-xs px-2 py-1 rounded-sm opacity-0 pointer-events-none transition-opacity"><?php echo esc_html( t( 'link_copied' ) ); ?></span>
                            </button>
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

                <?php
                $sbek_author_id  = get_the_author_meta( 'ID' );
                $sbek_author_bio = get_the_author_meta( 'description', $sbek_author_id );
                ?>
                <div class="author-bio flex items-start gap-4 mt-12 pt-8 border-t border-gray-100">
                    <?php echo get_avatar( $sbek_author_id, 64, '', '', array( 'class' => 'rounded-full shrink-0' ) ); ?>
                    <div>
                        <p class="text-xs text-gray-400 uppercase font-bold mb-1"><?php echo esc_html( t( 'about_author' ) ); ?></p>
                        <a href="<?php echo esc_url( sbek_localize_url( get_author_posts_url( $sbek_author_id ) ) ); ?>" class="font-bold text-gray-900 hover:text-sbek-red transition-colors">
                            <?php echo esc_html( get_the_author_meta( 'display_name', $sbek_author_id ) ); ?>
                        </a>
                        <?php if ( $sbek_author_bio ) : ?>
                            <p class="text-gray-500 text-sm mt-1"><?php echo esc_html( $sbek_author_bio ); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <?php
                $sbek_related_post_id = get_the_ID();
                $sbek_related_cat     = ! empty( $categories ) ? $categories[0]->term_id : 0;

                if ( $sbek_related_cat ) {
                    $sbek_related = sbek_get_cached_query( array(
                        'posts_per_page' => 3,
                        'cat'            => $sbek_related_cat,
                        'post__not_in'   => array( $sbek_related_post_id ),
                    ), 'sbek_related_' . $sbek_related_post_id . '_' . sbek_lang(), 3600 );

                    if ( $sbek_related->have_posts() ) :
                        ?>
                        <div class="related-posts mt-12 pt-8 border-t border-gray-100">
                            <h3 class="text-xl font-bold mb-6 uppercase"><?php echo esc_html( t( 'related_posts' ) ); ?></h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <?php
                                while ( $sbek_related->have_posts() ) :
                                    $sbek_related->the_post();
                                    get_template_part( 'template-parts/content-grid' );
                                endwhile;
                                wp_reset_postdata();
                                ?>
                            </div>
                        </div>
                        <?php
                    endif;
                }
                ?>

                <?php comments_template(); ?>
            <?php endwhile; ?>
        </article>

        <!-- Sidebar -->
        <aside class="col-span-12 lg:col-span-4 space-y-8">
            <div class="bg-white p-6 rounded-sm shadow-xs">
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
                            <div class="shrink-0 w-20 h-16 bg-gray-200 rounded-sm overflow-hidden relative">
                                <?php if ( has_post_thumbnail() ) the_post_thumbnail('thumbnail', ['class' => 'w-full h-full object-cover', 'loading' => 'lazy', 'decoding' => 'async']); ?>
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

            <?php get_sidebar(); ?>

            <!-- Ad Placeholder -->
            <div class="bg-gray-200 h-[600px] flex items-center justify-center text-gray-400 font-bold italic rounded-sm">
                <?php echo esc_html( t( 'advertisement' ) ); ?>
            </div>
        </aside>
    </div>
</main>

<?php get_footer(); ?>
