<?php get_header(); ?>

<div id="sbek-reading-progress" class="fixed left-0 top-0 z-[70] h-1 w-full origin-left scale-x-0 bg-sbek-red" aria-hidden="true"></div>

<main id="primary" class="site-main">
    <div class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-12 gap-8 lg:gap-12">
            <!-- Article -->
            <article id="post-<?php the_ID(); ?>" class="col-span-12 lg:col-span-8">
                <?php while ( have_posts() ) : the_post(); ?>
                    <?php
                    $sbek_cats         = get_the_category();
                    $sbek_share_url    = sbek_localize_url( get_permalink() );
                    $sbek_share_title  = get_the_title();
                    $sbek_author_id    = (int) get_the_author_meta( 'ID' );
                    $sbek_is_sponsored = (bool) get_post_meta( get_the_ID(), '_sbek_is_sponsored', true );
                    $sbek_is_breaking  = (bool) get_post_meta( get_the_ID(), '_sbek_is_breaking', true );
                    $sbek_video_url    = get_post_meta( get_the_ID(), '_sbek_video_url', true );
                    $sbek_was_updated  = ( get_the_modified_time( 'U' ) - get_the_time( 'U' ) ) > HOUR_IN_SECONDS;
                    ?>

                    <header class="entry-header">
                        <nav class="animate-fade-up flex flex-wrap items-center gap-x-2 gap-y-1.5 text-sm" aria-label="<?php echo esc_attr( t( 'breadcrumb' ) ); ?>">
                            <a href="<?php echo esc_url( sbek_localize_url( home_url( '/' ) ) ); ?>" class="font-medium text-gray-500 transition-colors hover:text-sbek-red"><?php echo esc_html( t( 'home' ) ); ?></a>
                            <?php if ( ! empty( $sbek_cats ) ) : ?>
                                <span aria-hidden="true" class="text-gray-300">/</span>
                                <a href="<?php echo esc_url( sbek_localize_url( get_category_link( $sbek_cats[0]->term_id ) ) ); ?>" class="font-bold uppercase tracking-wider text-sbek-red hover:underline"><?php echo esc_html( sbek_term_name( $sbek_cats[0] ) ); ?></a>
                            <?php endif; ?>
                            <?php if ( $sbek_is_breaking ) : ?>
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-sbek-red px-2.5 py-1 text-[11px] font-bold uppercase leading-none tracking-wider text-white">
                                    <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-white" aria-hidden="true"></span><?php echo esc_html( t( 'breaking' ) ); ?>
                                </span>
                            <?php endif; ?>
                        </nav>

                        <?php if ( $sbek_is_sponsored ) : ?>
                            <div class="animate-fade-up mt-4 rounded-sm border-l-4 border-amber-500 bg-amber-50 px-4 py-3" style="animation-delay: 50ms">
                                <p class="text-xs font-bold uppercase tracking-wider text-amber-700"><?php echo esc_html( t( 'sponsored_content' ) ); ?></p>
                                <p class="mt-0.5 text-sm leading-relaxed text-amber-900/80"><?php echo esc_html( t( 'sponsored_disclosure' ) ); ?></p>
                            </div>
                        <?php endif; ?>

                        <h1 class="animate-fade-up mt-3 font-display text-3xl font-extrabold leading-display text-ink sm:text-4xl lg:text-5xl xl:text-6xl" style="animation-delay: 90ms"><?php the_title(); ?></h1>

                        <?php if ( has_excerpt() ) : ?>
                            <p class="animate-fade-up mt-4 text-lg leading-relaxed text-gray-600 sm:text-xl" style="animation-delay: 160ms"><?php echo esc_html( get_the_excerpt() ); ?></p>
                        <?php endif; ?>

                        <div class="animate-fade-up mt-6 flex flex-wrap items-center gap-x-4 gap-y-3 border-y border-gray-200 py-4" style="animation-delay: 230ms">
                            <?php echo get_avatar( $sbek_author_id, 44, '', '', array( 'class' => 'rounded-full shrink-0' ) ); ?>
                            <div class="mr-auto">
                                <a href="<?php echo esc_url( sbek_localize_url( get_author_posts_url( $sbek_author_id ) ) ); ?>" class="block font-bold text-ink hover:text-sbek-red transition-colors"><?php the_author(); ?></a>
                                <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5 text-xs text-gray-500">
                                    <span><?php echo esc_html( get_the_date() ); ?></span>
                                    <span aria-hidden="true">•</span>
                                    <span><?php echo esc_html( t( 'min_read', sbek_reading_time() ) ); ?></span>
                                    <?php if ( $sbek_was_updated ) : ?>
                                        <span aria-hidden="true">•</span>
                                        <span class="font-medium text-sbek-red"><?php echo esc_html( t( 'updated', get_the_modified_date() ) ); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php get_template_part( 'template-parts/share-buttons', null, array( 'url' => $sbek_share_url, 'title' => $sbek_share_title ) ); ?>
                        </div>
                    </header>

                    <?php if ( $sbek_video_url ) : ?>
                        <?php $sbek_video_embed = wp_oembed_get( $sbek_video_url ); ?>
                        <figure class="animate-fade-up mt-8" style="animation-delay: 320ms">
                            <?php if ( $sbek_video_embed ) : ?>
                                <div class="sbek-video-embed overflow-hidden rounded-sm bg-ink">
                                    <?php echo $sbek_video_embed; // phpcs:ignore WordPress.Security.EscapeOutput -- provider HTML from WP's oEmbed whitelist. ?>
                                </div>
                            <?php else : // Provider not oEmbeddable — thumbnail linking out to the video. ?>
                                <a href="<?php echo esc_url( $sbek_video_url ); ?>" target="_blank" rel="noopener noreferrer" class="group relative block aspect-video overflow-hidden rounded-sm bg-gray-100">
                                    <?php if ( has_post_thumbnail() ) : ?>
                                        <?php the_post_thumbnail( 'sbek-hero', [ 'class' => 'h-full w-full object-cover', 'fetchpriority' => 'high', 'sizes' => '(min-width: 1024px) 60vw, 100vw' ] ); ?>
                                    <?php else : ?>
                                        <?php echo sbek_thumb_placeholder(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
                                    <?php endif; ?>
                                    <div class="absolute inset-0 flex items-center justify-center bg-black/20 transition-colors group-hover:bg-black/30">
                                        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-black/50 pl-1 backdrop-blur-xs transition-colors group-hover:bg-sbek-red/80">
                                            <svg class="h-7 w-7 fill-current text-white" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
                                        </div>
                                        <span class="sr-only"><?php echo esc_html( t( 'watch_video' ) ); ?></span>
                                    </div>
                                </a>
                            <?php endif; ?>
                        </figure>
                    <?php elseif ( has_post_thumbnail() ) : ?>
                        <figure class="animate-fade-up mt-8" style="animation-delay: 320ms">
                            <div class="overflow-hidden rounded-sm bg-gray-100">
                                <?php the_post_thumbnail( 'sbek-hero', [ 'class' => 'h-auto w-full', 'fetchpriority' => 'high', 'sizes' => '(min-width: 1024px) 60vw, 100vw' ] ); ?>
                            </div>
                            <?php if ( get_the_post_thumbnail_caption() ) : ?>
                                <figcaption class="mt-2 text-center text-xs text-gray-500"><?php echo esc_html( get_the_post_thumbnail_caption() ); ?></figcaption>
                            <?php endif; ?>
                        </figure>
                    <?php endif; ?>

                    <div class="entry-content prose prose-lg mt-8 max-w-[68ch] [--tw-prose-body:#33302b] [--tw-prose-headings:var(--color-ink)] [--tw-prose-links:var(--color-sbek-red)] [--tw-prose-bold:var(--color-ink)] [--tw-prose-quotes:var(--color-ink)] [--tw-prose-quote-borders:var(--color-sbek-red)] prose-headings:font-display prose-headings:tracking-tight prose-blockquote:font-display prose-blockquote:font-semibold prose-blockquote:not-italic prose-img:rounded-sm<?php echo sbek_is_default_lang() ? ' sbek-dropcap' : ''; ?>">
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

                    <div class="mt-10 flex flex-wrap items-center justify-between gap-4 rounded-sm border border-gray-200 bg-gray-50 px-5 py-4">
                        <p class="font-display font-bold text-ink"><?php echo esc_html( t( 'share_article' ) ); ?></p>
                        <?php get_template_part( 'template-parts/share-buttons', null, array( 'url' => $sbek_share_url, 'title' => $sbek_share_title ) ); ?>
                    </div>

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
                    // Adjacent posts constrained to the current language term.
                    // These queries bypass the pre_get_posts language filter,
                    // so without in_same_term('language') they'd cross languages.
                    $sbek_prev_post = get_adjacent_post( true, '', true, 'language' );
                    $sbek_next_post = get_adjacent_post( true, '', false, 'language' );

                    if ( $sbek_prev_post || $sbek_next_post ) :
                    ?>
                        <nav class="mt-12 grid grid-cols-1 gap-4 sm:grid-cols-2" aria-label="<?php echo esc_attr( t( 'post_navigation' ) ); ?>">
                            <?php if ( $sbek_prev_post ) : ?>
                                <a href="<?php echo esc_url( sbek_localize_url( get_permalink( $sbek_prev_post ) ) ); ?>" class="group flex flex-col rounded-sm border border-gray-200 bg-white p-5 transition-shadow hover:shadow-md">
                                    <span class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-gray-400">
                                        <?php echo sbek_icon( 'arrow-left', [ 'class' => 'h-3.5 w-3.5 transition-transform group-hover:-translate-x-0.5' ] ); ?>
                                        <?php echo esc_html( t( 'previous_article' ) ); ?>
                                    </span>
                                    <span class="mt-2 line-clamp-2 font-display font-bold leading-snug text-ink transition-colors group-hover:text-sbek-red"><?php echo esc_html( get_the_title( $sbek_prev_post ) ); ?></span>
                                </a>
                            <?php else : ?>
                                <span aria-hidden="true" class="hidden sm:block"></span>
                            <?php endif; ?>

                            <?php if ( $sbek_next_post ) : ?>
                                <a href="<?php echo esc_url( sbek_localize_url( get_permalink( $sbek_next_post ) ) ); ?>" class="group flex flex-col items-end rounded-sm border border-gray-200 bg-white p-5 text-right transition-shadow hover:shadow-md">
                                    <span class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-gray-400">
                                        <?php echo esc_html( t( 'next_article' ) ); ?>
                                        <?php echo sbek_icon( 'arrow-right', [ 'class' => 'h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5' ] ); ?>
                                    </span>
                                    <span class="mt-2 line-clamp-2 font-display font-bold leading-snug text-ink transition-colors group-hover:text-sbek-red"><?php echo esc_html( get_the_title( $sbek_next_post ) ); ?></span>
                                </a>
                            <?php endif; ?>
                        </nav>
                    <?php endif; ?>

                    <?php $sbek_related = sbek_get_related_posts( get_the_ID(), 3 ); ?>
                    <?php if ( $sbek_related->have_posts() ) : ?>
                        <div class="mt-12 border-t-2 border-ink pt-8">
                            <h2 class="mb-6 flex items-center gap-2.5 font-display text-xl font-extrabold uppercase tracking-tight text-ink">
                                <span class="inline-block h-5 w-1.5 shrink-0 bg-sbek-red"></span><?php echo esc_html( t( 'related_posts' ) ); ?>
                            </h2>
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                                <?php while ( $sbek_related->have_posts() ) : $sbek_related->the_post(); get_template_part( 'template-parts/content-grid' ); endwhile; wp_reset_postdata(); ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="mt-12">
                        <?php comments_template(); ?>
                    </div>
                <?php endwhile; ?>
            </article>

            <!-- Sidebar -->
            <aside class="col-span-12 lg:col-span-4">
                <div class="space-y-6 lg:sticky lg:top-28">
                    <?php $sbek_toc = sbek_article_headings(); ?>
                    <?php if ( count( $sbek_toc ) >= 3 ) : ?>
                        <nav class="sbek-toc rounded-sm border border-gray-200 bg-white" aria-label="<?php echo esc_attr( t( 'in_this_article' ) ); ?>">
                            <h2 class="flex items-center gap-2 border-b border-gray-200 px-4 py-3 font-display text-lg font-extrabold uppercase tracking-tight text-ink">
                                <span class="inline-block h-4 w-1.5 shrink-0 bg-sbek-red"></span><?php echo esc_html( t( 'in_this_article' ) ); ?>
                            </h2>
                            <ol class="py-2">
                                <?php foreach ( $sbek_toc as $sbek_heading ) : ?>
                                    <li>
                                        <a href="#<?php echo esc_attr( $sbek_heading['id'] ); ?>" class="block border-l-2 border-transparent px-4 py-1.5 text-sm font-medium leading-snug text-gray-600 transition-colors hover:text-sbek-red"><?php echo esc_html( $sbek_heading['title'] ); ?></a>
                                    </li>
                                <?php endforeach; ?>
                            </ol>
                        </nav>
                    <?php endif; ?>

                    <?php get_template_part( 'template-parts/trending', null, array( 'limit' => 5 ) ); ?>

                    <?php get_sidebar(); ?>

                    <?php echo sbek_ad_slot( 'rectangle' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
                </div>
            </aside>
        </div>
    </div>
</main>

<?php get_footer(); ?>
