<?php
/**
 * Comments template — included via comments_template() on single.php.
 * Bails on password-protected posts, per WordPress convention.
 */

if ( post_password_required() ) {
    return;
}
?>

<div id="comments" class="comments-area mt-12 border-t-2 border-ink pt-8">

    <?php if ( have_comments() ) : ?>
        <h2 class="comments-title mb-6 flex items-center gap-2.5 font-display text-xl font-extrabold uppercase tracking-tight text-ink">
            <span class="inline-block h-5 w-1.5 shrink-0 bg-sbek-red"></span><?php echo esc_html( t( 'comments_heading', get_comments_number() ) ); ?>
        </h2>

        <ol class="comment-list divide-y divide-gray-200">
            <?php
            wp_list_comments( [
                'style'       => 'ol',
                'short_ping'  => true,
                'avatar_size' => 48,
                'callback'    => 'sbek_comment_list_item',
            ] );
            ?>
        </ol>

        <?php the_comments_pagination( [
            'prev_text' => '&larr;',
            'next_text' => '&rarr;',
        ] ); ?>
    <?php endif; ?>

    <?php if ( ! comments_open() && get_comments_number() ) : ?>
        <p class="no-comments text-gray-500 italic mt-4"><?php echo esc_html( t( 'comments_closed' ) ); ?></p>
    <?php endif; ?>

    <?php comment_form( sbek_comment_form_args() ); ?>

</div>
