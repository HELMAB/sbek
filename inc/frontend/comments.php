<?php
/**
 * Comment rendering: Tailwind-styled comment list callback and localized
 * comment_form() args. The markup lives in comments.php; this file is the
 * logic that feeds it.
 */

/**
 * wp_list_comments() callback — renders a single <li> per comment.
 */
function sbek_comment_list_item( $comment, $args, $depth ) {
    $tag = ( 'div' === $args['style'] ) ? 'div' : 'li';
    ?>
    <<?php echo $tag; ?> <?php comment_class( 'flex gap-3 py-4' . ( $depth > 1 ? ' ml-8 md:ml-12' : '' ), $comment ); ?> id="comment-<?php comment_ID(); ?>">
        <div class="shrink-0">
            <?php echo get_avatar( $comment, 48, '', '', [ 'class' => 'rounded-full' ] ); ?>
        </div>
        <div class="grow min-w-0">
            <div class="flex items-center gap-2 text-sm">
                <span class="font-bold text-gray-900"><?php comment_author(); ?></span>
                <span class="text-gray-400">•</span>
                <a href="<?php echo esc_url( get_comment_link( $comment ) ); ?>" class="text-gray-400 hover:text-sbek-red">
                    <?php echo esc_html( get_comment_date( '', $comment ) ); ?>
                </a>
            </div>

            <?php if ( '0' === $comment->comment_approved ) : ?>
                <p class="text-xs text-amber-600 italic mt-1"><?php echo esc_html( t( 'comment_pending' ) ); ?></p>
            <?php endif; ?>

            <div class="comment-text text-gray-700 mt-1 leading-relaxed">
                <?php comment_text(); ?>
            </div>

            <?php
            comment_reply_link( array_merge( $args, [
                'reply_text' => esc_html( t( 'reply' ) ),
                'depth'      => $depth,
                'max_depth'  => $args['max_depth'],
                'before'     => '<div class="mt-1 text-sm">',
                'after'      => '</div>',
            ] ) );
            ?>
        </div>
    </<?php echo $tag; ?>>
    <?php
}

/**
 * Localize and restyle the default comment_form() output to match the
 * theme's Tailwind look. Passed directly to comment_form() in comments.php.
 */
function sbek_comment_form_args(): array {
    $commenter    = wp_get_current_commenter();
    $req          = get_option( 'require_name_email' );
    $html_req     = ( $req ? " required" : '' );
    $input_class  = 'w-full border border-gray-200 rounded-sm px-3 py-2 text-sm focus:outline-hidden focus:border-sbek-red';

    $required_notice = $req
        ? ' <span class="required-field-message">' . esc_html( t( 'comment_required_notice' ) ) . ' <span class="required">*</span></span>'
        : '';

    $fields = [
        'author' => '<div class="comment-form-author mb-4"><label for="author" class="block text-sm font-bold mb-1">' . esc_html( t( 'name' ) ) . '</label><input id="author" name="author" type="text" value="' . esc_attr( $commenter['comment_author'] ) . '" class="' . esc_attr( $input_class ) . '"' . $html_req . '></div>',
        'email'  => '<div class="comment-form-email mb-4"><label for="email" class="block text-sm font-bold mb-1">' . esc_html( t( 'email' ) ) . '</label><input id="email" name="email" type="email" value="' . esc_attr( $commenter['comment_author_email'] ) . '" class="' . esc_attr( $input_class ) . '"' . $html_req . '></div>',
        'url'    => '<div class="comment-form-url mb-4"><label for="url" class="block text-sm font-bold mb-1">' . esc_html( t( 'website' ) ) . '</label><input id="url" name="url" type="url" value="' . esc_attr( $commenter['comment_author_url'] ) . '" class="' . esc_attr( $input_class ) . '"></div>',
    ];

    // WordPress force-injects its own English cookie-consent field onto
    // $args['fields']['cookies'] unless that key already exists — so we
    // supply our own localized version whenever the site has consent enabled.
    if ( has_action( 'set_comment_cookies', 'wp_set_comment_cookies' ) && get_option( 'show_comments_cookies_opt_in' ) ) {
        $checked = empty( $commenter['comment_author_email'] ) ? '' : ' checked';
        $fields['cookies'] = '<p class="comment-form-cookies-consent flex items-center gap-2 text-sm text-gray-500"><input id="wp-comment-cookies-consent" name="wp-comment-cookies-consent" type="checkbox" value="yes"' . $checked . '> <label for="wp-comment-cookies-consent">' . esc_html( t( 'comment_save_info' ) ) . '</label></p>';
    }

    return [
        'title_reply'          => esc_html( t( 'leave_a_comment' ) ),
        'title_reply_before'   => '<h3 id="reply-title" class="comment-reply-title text-lg font-bold border-b-2 border-sbek-red pb-2 mb-4 uppercase">',
        'title_reply_after'    => '</h3>',
        'class_form'           => 'comment-form space-y-4',
        'class_submit'         => 'submit',
        'comment_notes_before' => '<p class="comment-notes text-sm text-gray-500 mb-4"><span id="email-notes">' . esc_html( t( 'comment_email_notice' ) ) . '</span>' . $required_notice . '</p>',
        'comment_notes_after'  => '',
        'comment_field'        => '<div class="comment-form-comment mb-4"><label for="comment" class="block text-sm font-bold mb-1">' . esc_html( t( 'comment_field' ) ) . '</label><textarea id="comment" name="comment" rows="5" class="' . esc_attr( $input_class ) . '" required></textarea></div>',
        'fields'               => $fields,
        'label_submit'         => esc_html( t( 'post_comment' ) ),
        'submit_button'        => '<button name="%1$s" type="submit" id="%2$s" class="%3$s bg-sbek-red text-white font-bold py-2 px-6 rounded-full hover:bg-gray-900 transition-colors duration-300">%4$s</button>',
        'submit_field'         => '<div class="form-submit">%1$s %2$s</div>',
    ];
}
