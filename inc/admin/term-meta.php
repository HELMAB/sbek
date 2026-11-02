<?php
/**
 * Category / tag fields:
 *   sbek_color       Accent color (badges, section headers, arrows).
 *   sbek_name_{lang} Translated name for each non-default language.
 *   sbek_home_order  Homepage block position (categories; empty/0 = hidden).
 *
 * @package sbek
 */

defined( 'ABSPATH' ) || exit;

/**
 * Translated-name meta keys, one per non-default language.
 *
 * @return array<string,string> meta key => language label
 */
function sbek_term_name_fields(): array {
	$fields = array();

	foreach ( sbek_languages() as $slug => $language ) {
		if ( SBEK_DEFAULT_LANGUAGE !== $slug ) {
			$fields[ 'sbek_name_' . $slug ] = $language['label'];
		}
	}

	return $fields;
}

function sbek_register_term_meta(): void {
	foreach ( array( 'category', 'post_tag' ) as $taxonomy ) {
		register_term_meta(
			$taxonomy,
			'sbek_color',
			array(
				'type'              => 'string',
				'single'            => true,
				'sanitize_callback' => static fn( $value ): string => (string) sanitize_hex_color( (string) $value ),
				'auth_callback'     => static fn( $allowed, $key, $term_id ): bool => current_user_can( 'edit_term', $term_id ),
				'show_in_rest'      => true,
			)
		);

		foreach ( array_keys( sbek_term_name_fields() ) as $key ) {
			register_term_meta(
				$taxonomy,
				str_replace( 'sbek_name_', 'sbek_description_', $key ),
				array(
					'type'              => 'string',
					'single'            => true,
					'sanitize_callback' => 'sanitize_textarea_field',
					'auth_callback'     => static fn( $allowed, $meta_key, $term_id ): bool => current_user_can( 'edit_term', $term_id ),
					'show_in_rest'      => true,
				)
			);
			register_term_meta(
				$taxonomy,
				$key,
				array(
					'type'              => 'string',
					'single'            => true,
					'sanitize_callback' => 'sanitize_text_field',
					'auth_callback'     => static fn( $allowed, $meta_key, $term_id ): bool => current_user_can( 'edit_term', $term_id ),
					'show_in_rest'      => true,
				)
			);
		}
	}

	register_term_meta(
		'category',
		'sbek_home_order',
		array(
			'type'              => 'integer',
			'single'            => true,
			'sanitize_callback' => 'absint',
			'auth_callback'     => static fn( $allowed, $key, $term_id ): bool => current_user_can( 'edit_term', $term_id ),
			'show_in_rest'      => true,
		)
	);
}
add_action( 'init', 'sbek_register_term_meta' );

/**
 * Field definitions for a taxonomy's forms.
 *
 * @return array<string,array{label:string,type:string,description:string}>
 */
function sbek_term_form_fields( string $taxonomy ): array {
	$fields = array(
		'sbek_color' => array(
			'label'       => __( 'Accent color', 'sbek' ),
			'type'        => 'color',
			'description' => __( 'Used for badges, section headers and arrows. While "Use the brand color" is ticked, the category follows the brand color, even after it changes.', 'sbek' ),
		),
	);

	foreach ( sbek_term_name_fields() as $key => $label ) {
		$fields[ $key ] = array(
			/* translators: %s: language name. */
			'label'       => sprintf( __( 'Name (%s)', 'sbek' ), $label ),
			'type'        => 'text',
			'description' => __( 'Shown on pages in that language. Leave empty to use the main name.', 'sbek' ),
		);
		$fields[ str_replace( 'sbek_name_', 'sbek_description_', $key ) ] = array(
			/* translators: %s: language name. */
			'label'       => sprintf( __( 'Description (%s)', 'sbek' ), $label ),
			'type'        => 'textarea',
			'description' => __( 'Shown under the archive title on pages in that language. Leave empty to show no description there.', 'sbek' ),
		);
	}

	if ( 'category' === $taxonomy ) {
		$fields['sbek_home_order'] = array(
			'label'       => __( 'Homepage position', 'sbek' ),
			'type'        => 'number',
			'description' => sprintf(
				/* translators: %d: maximum number of homepage blocks. */
				__( '1, 2, 3… shows this category as a homepage block in that order (up to %d; categories without posts are skipped). Leave empty to keep it off the homepage. If no category has a position, the first top-level categories are shown.', 'sbek' ),
				SBEK_HOME_BLOCK_LIMIT
			),
		);
	}

	return $fields;
}

/**
 * @param string     $taxonomy Taxonomy.
 * @param string     $key      Meta key.
 * @param array      $field    Field definition.
 * @param string|int $value    Current value.
 */
function sbek_term_field_input( string $taxonomy, string $key, array $field, $value ): void {
	$attrs = 'id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '"';

	switch ( $field['type'] ) {
		case 'color':
			// An <input type="color"> can't be empty, so "no custom color" is a
			// separate checkbox; picking a color unticks it.
			printf(
				'<input type="color" %1$s value="%2$s" oninput="this.form.%3$s_auto.checked=false"> <label style="display:inline-flex;align-items:center;gap:4px;margin-left:8px"><input type="checkbox" name="%3$s_auto" value="1"%4$s> %5$s</label>',
				$attrs, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
				esc_attr( (string) ( $value ?: sbek_brand_color() ) ),
				esc_attr( $key ),
				$value ? '' : ' checked',
				esc_html__( 'Use the brand color', 'sbek' )
			);
			break;
		case 'textarea':
			printf( '<textarea %s rows="3" cols="40">%s</textarea>', $attrs, esc_textarea( (string) $value ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $attrs escaped above.
			break;
		case 'number':
			printf( '<input type="number" min="0" step="1" %s value="%s" class="small-text">', $attrs, esc_attr( $value ? (string) $value : '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $attrs escaped above.
			break;
		default:
			printf( '<input type="text" %s value="%s">', $attrs, esc_attr( (string) $value ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $attrs escaped above.
	}
}

function sbek_render_term_add_fields( string $taxonomy ): void {
	wp_nonce_field( 'sbek_term_meta', 'sbek_term_meta_nonce' );

	foreach ( sbek_term_form_fields( $taxonomy ) as $key => $field ) {
		echo '<div class="form-field"><label for="' . esc_attr( $key ) . '">' . esc_html( $field['label'] ) . '</label>';
		sbek_term_field_input( $taxonomy, $key, $field, '' );
		echo '<p>' . esc_html( $field['description'] ) . '</p></div>';
	}
}

function sbek_render_term_edit_fields( WP_Term $term, string $taxonomy ): void {
	wp_nonce_field( 'sbek_term_meta', 'sbek_term_meta_nonce' );

	foreach ( sbek_term_form_fields( $taxonomy ) as $key => $field ) {
		echo '<tr class="form-field"><th scope="row"><label for="' . esc_attr( $key ) . '">' . esc_html( $field['label'] ) . '</label></th><td>';
		sbek_term_field_input( $taxonomy, $key, $field, get_term_meta( $term->term_id, $key, true ) );
		echo '<p class="description">' . esc_html( $field['description'] ) . '</p></td></tr>';
	}
}

function sbek_save_term_fields( int $term_id ): void {
	$nonce = isset( $_POST['sbek_term_meta_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['sbek_term_meta_nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'sbek_term_meta' ) || ! current_user_can( 'edit_term', $term_id ) ) {
		return;
	}

	$term = get_term( $term_id );
	if ( ! $term instanceof WP_Term ) {
		return;
	}

	foreach ( array_keys( sbek_term_form_fields( $term->taxonomy ) ) as $key ) {
		if ( ! isset( $_POST[ $key ] ) ) {
			continue;
		}

		$value = trim( (string) wp_unslash( $_POST[ $key ] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized by the registered meta callback.

		// "Use the brand color" ticked: store nothing so the brand color applies.
		if ( 'sbek_color' === $key && ! empty( $_POST['sbek_color_auto'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified above.
			$value = '';
		}

		if ( '' === $value || '0' === $value ) {
			delete_term_meta( $term_id, $key );
		} else {
			update_term_meta( $term_id, $key, $value );
		}
	}
}

foreach ( array( 'category', 'post_tag' ) as $sbek_taxonomy ) {
	add_action( "{$sbek_taxonomy}_add_form_fields", 'sbek_render_term_add_fields' );
	add_action( "{$sbek_taxonomy}_edit_form_fields", 'sbek_render_term_edit_fields', 10, 2 );
	add_action( "created_{$sbek_taxonomy}", 'sbek_save_term_fields' );
	add_action( "edited_{$sbek_taxonomy}", 'sbek_save_term_fields' );
}
unset( $sbek_taxonomy );

/**
 * Color swatch column in the category / tag lists.
 *
 * @param string[] $columns Columns.
 * @return string[]
 */
function sbek_term_color_column( array $columns ): array {
	return array_slice( $columns, 0, 1, true ) + array( 'sbek_color' => '<span class="screen-reader-text">' . esc_html__( 'Color', 'sbek' ) . '</span>' ) + array_slice( $columns, 1, null, true );
}

/**
 * @param string $content Column content.
 * @param string $column  Column name.
 * @param int    $term_id Term ID.
 */
function sbek_term_color_column_content( $content, $column, $term_id ): string {
	if ( 'sbek_color' !== $column ) {
		return (string) $content;
	}

	$color = sanitize_hex_color( (string) get_term_meta( (int) $term_id, 'sbek_color', true ) );

	return $color ? '<span style="display:inline-block;width:14px;height:14px;border-radius:3px;background:' . esc_attr( $color ) . '"></span>' : '';
}

foreach ( array( 'category', 'post_tag' ) as $sbek_taxonomy ) {
	add_filter( "manage_edit-{$sbek_taxonomy}_columns", 'sbek_term_color_column' );
	add_filter( "manage_{$sbek_taxonomy}_custom_column", 'sbek_term_color_column_content', 10, 3 );
}
unset( $sbek_taxonomy );
