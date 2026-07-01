<?php
/**
 * Admin UI for multilingual content management.
 *
 * - Translation link meta box: links a post to its counterpart in another language.
 * - Localized category/tag names: stored as term meta.
 */

// ─── Translation link meta box ────────────────────────────────────────────────

add_action( 'add_meta_boxes', function () {
    add_meta_box(
        'sbek_translation_link',
        'Translation',
        'sbek_render_translation_meta_box',
        [ 'post', 'page' ],
        'side',
        'default'
    );
} );

function sbek_render_translation_meta_box( WP_Post $post ): void {
    wp_nonce_field( 'sbek_translation_nonce', 'sbek_translation_nonce' );

    $linked_id   = (int) get_post_meta( $post->ID, '_translation_id', true );
    $linked_post = $linked_id ? get_post( $linked_id ) : null;

    $linked_lang = '–';
    if ( $linked_post ) {
        $terms       = wp_get_post_terms( $linked_id, 'language', [ 'fields' => 'slugs' ] );
        $linked_lang = ( ! is_wp_error( $terms ) && $terms ) ? strtoupper( $terms[0] ) : '–';
    }
    ?>
    <p class="description" style="margin-bottom:8px">
        Enter the post ID of the translated version.
    </p>
    <input
        type="number"
        name="sbek_translation_id"
        value="<?php echo esc_attr( $linked_id ?: '' ); ?>"
        style="width:100%;margin-bottom:6px"
        placeholder="Post ID"
    >
    <?php if ( $linked_post ) : ?>
        <p style="color:#666;font-size:11px;margin:0">
            Linked: <strong><?php echo esc_html( $linked_post->post_title ); ?></strong>
            (<?php echo esc_html( $linked_lang ); ?>)
        </p>
    <?php endif; ?>
    <?php
}

add_action( 'save_post', function ( int $post_id ): void {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if (
        ! isset( $_POST['sbek_translation_nonce'] )
        || ! wp_verify_nonce( $_POST['sbek_translation_nonce'], 'sbek_translation_nonce' )
    ) {
        return;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    $translation_id = isset( $_POST['sbek_translation_id'] )
        ? (int) $_POST['sbek_translation_id']
        : 0;

    if ( $translation_id ) {
        update_post_meta( $post_id, '_translation_id', $translation_id );
        update_post_meta( $translation_id, '_translation_id', $post_id ); // bidirectional
    } else {
        delete_post_meta( $post_id, '_translation_id' );
    }
} );

// ─── Localized category / tag names ──────────────────────────────────────────

foreach ( [ 'category', 'post_tag' ] as $_sbek_tax ) {
    add_action( "{$_sbek_tax}_add_form_fields",  'sbek_render_term_lang_add_fields' );
    add_action( "{$_sbek_tax}_edit_form_fields", 'sbek_render_term_lang_edit_fields' );
    add_action( "edited_{$_sbek_tax}",           'sbek_save_term_lang_fields' );
    add_action( "created_{$_sbek_tax}",          'sbek_save_term_lang_fields' );
}

function sbek_render_term_lang_add_fields(): void {
    foreach ( SBEK_LANGUAGES as $lang ) {
        if ( $lang === SBEK_DEFAULT_LANGUAGE ) {
            continue;
        }
        ?>
        <div class="form-field">
            <label for="term_name_<?php echo esc_attr( $lang ); ?>">
                <?php echo esc_html( 'Name (' . strtoupper( $lang ) . ')' ); ?>
            </label>
            <input
                type="text"
                id="term_name_<?php echo esc_attr( $lang ); ?>"
                name="term_name_<?php echo esc_attr( $lang ); ?>"
            >
        </div>
        <?php
    }
}

function sbek_render_term_lang_edit_fields( WP_Term $term ): void {
    foreach ( SBEK_LANGUAGES as $lang ) {
        if ( $lang === SBEK_DEFAULT_LANGUAGE ) {
            continue;
        }
        $current = get_term_meta( $term->term_id, "name_{$lang}", true );
        ?>
        <tr class="form-field">
            <th>
                <label for="term_name_<?php echo esc_attr( $lang ); ?>">
                    <?php echo esc_html( 'Name (' . strtoupper( $lang ) . ')' ); ?>
                </label>
            </th>
            <td>
                <input
                    type="text"
                    id="term_name_<?php echo esc_attr( $lang ); ?>"
                    name="term_name_<?php echo esc_attr( $lang ); ?>"
                    value="<?php echo esc_attr( $current ); ?>"
                >
            </td>
        </tr>
        <?php
    }
}

function sbek_save_term_lang_fields( int $term_id ): void {
    foreach ( SBEK_LANGUAGES as $lang ) {
        if ( $lang === SBEK_DEFAULT_LANGUAGE ) {
            continue;
        }

        $key = "term_name_{$lang}";

        if ( isset( $_POST[ $key ] ) ) {
            update_term_meta( $term_id, "name_{$lang}", sanitize_text_field( $_POST[ $key ] ) );
        }
    }
}
