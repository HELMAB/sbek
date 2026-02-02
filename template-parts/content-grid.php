<?php
$is_sponsored = get_post_meta( get_the_ID(), '_sabay_is_sponsored', true );
$video_url = get_post_meta( get_the_ID(), '_sabay_video_url', true );
?>
<article id="post-<?php the_ID(); ?>" <?php post_class('bg-white rounded shadow-sm overflow-hidden group hover:shadow-md transition-shadow ' . ($is_sponsored ? 'border-2 border-yellow-400' : '')); ?>>
    <div class="relative h-48 overflow-hidden">
        <?php if ( has_post_thumbnail() ) : ?>
            <?php the_post_thumbnail('medium', ['class' => 'w-full h-full object-cover group-hover:scale-105 transition-transform duration-500']); ?>
        <?php else : ?>
            <div class="w-full h-full bg-gray-200"></div>
        <?php endif; ?>
        
        <?php if ( $video_url ) : ?>
             <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                <div class="w-12 h-12 bg-black/50 rounded-full flex items-center justify-center pl-1 backdrop-blur-sm group-hover:bg-sabay-red/80 transition-colors">
                    <svg class="w-6 h-6 text-white fill-current" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                </div>
            </div>
        <?php endif; ?>

        <?php
        $categories = get_the_category();
        if ( ! empty( $categories ) ) {
            echo '<a href="' . esc_url( get_category_link( $categories[0]->term_id ) ) . '" class="absolute top-2 left-2 bg-sabay-blue text-white text-[10px] font-bold px-2 py-0.5 rounded uppercase">' . esc_html( $categories[0]->name ) . '</a>';
        }
        ?>
    </div>
    
    <div class="p-4">
        <?php if ( $is_sponsored ) : ?>
            <span class="block text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-1">Sponsored</span>
        <?php endif; ?>
        <h3 class="text-base font-bold line-clamp-2 mb-2 leading-snug">
            <a href="<?php the_permalink(); ?>" class="hover:text-sabay-red transition-colors"><?php the_title(); ?></a>
        </h3>
        <div class="flex items-center text-gray-500 text-xs space-x-2">
            <span><?php echo get_the_date(); ?></span>
            <span>•</span>
            <span><?php echo human_time_diff( get_the_time('U'), current_time('timestamp') ) . ' ago'; ?></span>
        </div>
    </div>
</article>
