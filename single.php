<?php get_header(); ?>

<main id="primary" class="site-main">
    <div class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-12 gap-8 lg:gap-12">
            <!-- Article -->
            <article id="post-<?php the_ID(); ?>" class="col-span-12 lg:col-span-8">
                <?php while ( have_posts() ) : the_post(); ?>
                    <?php
                    $sbek_cats        = get_the_category();
                    $sbek_share_url   = sbek_localize_url( get_permalink() );
                    $sbek_share_title = get_the_title();
                    $sbek_author_id   = (int) get_the_author_meta( 'ID' );
                    ?>

                    <header class="entry-header">
                        <?php if ( ! empty( $sbek_cats ) ) : ?>
                            <a href="<?php echo esc_url( sbek_localize_url( get_category_link( $sbek_cats[0]->term_id ) ) ); ?>" class="text-sm font-bold uppercase tracking-wider text-sbek-red hover:underline"><?php echo esc_html( sbek_term_name( $sbek_cats[0] ) ); ?></a>
                        <?php endif; ?>

                        <h1 class="mt-3 font-display text-3xl font-extrabold leading-tight text-ink sm:text-4xl lg:text-5xl"><?php the_title(); ?></h1>

                        <?php if ( has_excerpt() ) : ?>
                            <p class="mt-4 text-lg leading-relaxed text-gray-600 sm:text-xl"><?php echo esc_html( get_the_excerpt() ); ?></p>
                        <?php endif; ?>

                        <div class="mt-6 flex flex-wrap items-center gap-x-4 gap-y-3 border-y border-gray-200 py-4">
                            <?php echo get_avatar( $sbek_author_id, 44, '', '', array( 'class' => 'rounded-full shrink-0' ) ); ?>
                            <div class="mr-auto">
                                <a href="<?php echo esc_url( sbek_localize_url( get_author_posts_url( $sbek_author_id ) ) ); ?>" class="block font-bold text-ink hover:text-sbek-red transition-colors"><?php the_author(); ?></a>
                                <div class="flex items-center gap-2 text-xs text-gray-500">
                                    <span><?php echo esc_html( get_the_date() ); ?></span>
                                    <span aria-hidden="true">•</span>
                                    <span><?php echo esc_html( t( 'min_read', sbek_reading_time() ) ); ?></span>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <a href="<?php echo esc_url( 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $sbek_share_url ) ); ?>" target="_blank" rel="noopener noreferrer" aria-label="Facebook" class="flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-gray-600 transition-colors hover:bg-sbek-red hover:text-white">
                                    <?php echo sbek_icon( 'facebook', [ 'width' => 16, 'height' => 16 ] ); ?>
                                </a>
                                <a href="<?php echo esc_url( 'https://twitter.com/intent/tweet?url=' . rawurlencode( $sbek_share_url ) . '&text=' . rawurlencode( $sbek_share_title ) ); ?>" target="_blank" rel="noopener noreferrer" aria-label="X (Twitter)" class="flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-gray-600 transition-colors hover:bg-sbek-red hover:text-white">
                                    <?php echo sbek_icon( 'x', [ 'width' => 15, 'height' => 15 ] ); ?>
                                </a>
                                <button type="button" class="sbek-copy-link relative flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-gray-600 transition-colors hover:bg-sbek-red hover:text-white" data-url="<?php echo esc_attr( $sbek_share_url ); ?>" aria-label="<?php echo esc_attr( t( 'copy_link' ) ); ?>">
                                    <?php echo sbek_icon( 'link', [ 'width' => 16, 'height' => 16 ] ); ?>
                                    <span class="sbek-copy-feedback pointer-events-none absolute -top-9 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-sm bg-ink px-2 py-1 text-xs text-white opacity-0 transition-opacity"><?php echo esc_html( t( 'link_copied' ) ); ?></span>
                                </button>
                            </div>
                        </div>
                    </header>

                    <?php if ( has_post_thumbnail() ) : ?>
                        <figure class="mt-8">
                            <div class="overflow-hidden rounded-sm bg-gray-100">
                                <?php the_post_thumbnail( 'full', [ 'class' => 'h-auto w-full', 'fetchpriority' => 'high' ] ); ?>
                            </div>
                            <?php if ( get_the_post_thumbnail_caption() ) : ?>
                                <figcaption class="mt-2 text-center text-xs text-gray-500"><?php echo esc_html( get_the_post_thumbnail_caption() ); ?></figcaption>
                            <?php endif; ?>
                        </figure>
                    <?php endif; ?>

                    <div class="sbek-article entry-content mt-8 max-w-[68ch]">
                        <?php the_content(); ?>
                        <?php
                        wp_link_pages( array(
                            'before' => '<div class="mt-6 flex flex-wrap gap-2 text-sm font-bold">',
                            'after'  => '</div>',
                        ) );
                        ?>
                    </div>

                    <?php if ( has_tag() ) : ?>
                        <div class="mt-10 flex flex-wrap items-center gap-2">
                            <span class="text-xs font-bold uppercase tracking-wider text-gray-400"><?php echo esc_html( t( 'tags' ) ); ?></span>
                            <?php foreach ( get_the_tags() as $sbek_tag ) : ?>
                                <a href="<?php echo esc_url( sbek_localize_url( get_tag_link( $sbek_tag->term_id ) ) ); ?>" class="rounded-full border border-gray-200 px-3 py-1 text-sm font-medium text-gray-700 transition-colors hover:border-sbek-red hover:text-sbek-red"><?php echo esc_html( $sbek_tag->name ); ?></a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php
                    $sbek_author_bio = get_the_author_meta( 'description', $sbek_author_id );
                    if ( $sbek_author_bio ) :
                    ?>
                        <div class="mt-12 flex items-start gap-4 rounded-sm border border-gray-200 bg-white p-6">
                            <?php echo get_avatar( $sbek_author_id, 64, '', '', array( 'class' => 'rounded-full shrink-0' ) ); ?>
                            <div>
                                <p class="mb-1 text-xs font-bold uppercase tracking-wider text-gray-400"><?php echo esc_html( t( 'about_author' ) ); ?></p>
                                <a href="<?php echo esc_url( sbek_localize_url( get_author_posts_url( $sbek_author_id ) ) ); ?>" class="font-display text-lg font-bold text-ink transition-colors hover:text-sbek-red"><?php echo esc_html( get_the_author_meta( 'display_name', $sbek_author_id ) ); ?></a>
                                <p class="mt-1 text-sm leading-relaxed text-gray-600"><?php echo esc_html( $sbek_author_bio ); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php
                    $sbek_related_cat = ! empty( $sbek_cats ) ? $sbek_cats[0]->term_id : 0;
                    if ( $sbek_related_cat ) :
                        $sbek_related = sbek_get_cached_query( array(
                            'posts_per_page' => 3,
                            'cat'            => $sbek_related_cat,
                            'post__not_in'   => array( get_the_ID() ),
                        ), 'sbek_related_' . get_the_ID() . '_' . sbek_lang(), 3600 );

                        if ( $sbek_related->have_posts() ) :
                    ?>
                        <div class="mt-12 border-t-2 border-ink pt-8">
                            <h2 class="mb-6 flex items-center gap-2.5 font-display text-xl font-extrabold uppercase tracking-tight text-ink">
                                <span class="inline-block h-5 w-1.5 shrink-0 bg-sbek-red"></span><?php echo esc_html( t( 'related_posts' ) ); ?>
                            </h2>
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                                <?php while ( $sbek_related->have_posts() ) : $sbek_related->the_post(); get_template_part( 'template-parts/content-grid' ); endwhile; wp_reset_postdata(); ?>
                            </div>
                        </div>
                    <?php endif; endif; ?>

                    <div class="mt-12">
                        <?php comments_template(); ?>
                    </div>
                <?php endwhile; ?>
            </article>

            <!-- Sidebar -->
            <aside class="col-span-12 lg:col-span-4">
                <div class="space-y-6 lg:sticky lg:top-28">
                    <div class="rounded-sm border border-gray-200 bg-white">
                        <h2 class="flex items-center gap-2 border-b border-gray-200 px-4 py-3 font-display text-lg font-extrabold uppercase tracking-tight text-ink">
                            <span class="inline-block h-4 w-1.5 shrink-0 bg-sbek-red"></span><?php echo esc_html( t( 'trending' ) ); ?>
                        </h2>
                        <ol class="divide-y divide-gray-200">
                            <?php
                            $sbek_trending = sbek_get_trending_posts( 5 );
                            $sbek_rank     = 0;
                            if ( $sbek_trending->have_posts() ) : while ( $sbek_trending->have_posts() ) : $sbek_trending->the_post();
                                $sbek_rank++;
                            ?>
                                <li>
                                    <a href="<?php echo esc_url( sbek_localize_url( get_permalink() ) ); ?>" class="group flex gap-3 px-4 py-3">
                                        <span class="w-6 shrink-0 font-display text-2xl font-extrabold leading-none text-gray-300 transition-colors group-hover:text-sbek-red"><?php echo esc_html( $sbek_rank ); ?></span>
                                        <span class="font-display text-[15px] font-bold leading-snug text-ink transition-colors group-hover:text-sbek-red"><?php the_title(); ?></span>
                                    </a>
                                </li>
                            <?php endwhile; wp_reset_postdata(); endif; ?>
                        </ol>
                    </div>

                    <?php get_sidebar(); ?>

                    <?php echo sbek_ad_slot( 'rectangle' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
                </div>
            </aside>
        </div>
    </div>
</main>

<?php get_footer(); ?>
