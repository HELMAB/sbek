<?php
/**
 * Block styles and block patterns for articles.
 *
 * Presentation only: the styles are CSS classes (`is-style-sbek-*`, styled in
 * assets/css/src/input.css and editor.css) and the patterns are starting
 * layouts for the editor. Nothing here stores content.
 *
 * @package sbek
 */

defined( 'ABSPATH' ) || exit;

/**
 * Extra styles for core blocks, picked in the block sidebar under "Styles".
 */
function sbek_register_block_styles(): void {
	$styles = array(
		'core/group'     => array(
			'sbek-fact-box' => __( 'Fact box', 'sbek' ),
		),
		'core/heading'   => array(
			'sbek-rule' => __( 'Underlined', 'sbek' ),
		),
		'core/quote'     => array(
			'sbek-large' => __( 'Large', 'sbek' ),
		),
		'core/list'      => array(
			'sbek-key-points' => __( 'Key points', 'sbek' ),
		),
		'core/separator' => array(
			'sbek-brand' => __( 'Brand line', 'sbek' ),
		),
		'core/image'     => array(
			'sbek-framed' => __( 'Framed', 'sbek' ),
		),
	);

	foreach ( $styles as $block => $block_styles ) {
		foreach ( $block_styles as $name => $label ) {
			register_block_style(
				$block,
				array(
					'name'  => $name,
					'label' => $label,
				)
			);
		}
	}
}
add_action( 'init', 'sbek_register_block_styles' );

/**
 * News-article patterns, under their own "Sbek" category in the inserter.
 */
function sbek_register_block_patterns(): void {
	register_block_pattern_category(
		'sbek',
		array( 'label' => _x( 'Sbek news', 'block pattern category', 'sbek' ) )
	);

	$patterns = array(
		'key-points'  => array(
			'title'       => __( 'Key points', 'sbek' ),
			'description' => __( 'A short summary list at the top of an article.', 'sbek' ),
			'keywords'    => array( __( 'summary', 'sbek' ), __( 'highlights', 'sbek' ) ),
			'content'     => sbek_pattern_box(
				__( 'Key points', 'sbek' ),
				'<!-- wp:list {"className":"is-style-sbek-key-points"} -->
<ul class="wp-block-list is-style-sbek-key-points"><!-- wp:list-item -->
<li>' . esc_html__( 'The most important fact of the story.', 'sbek' ) . '</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>' . esc_html__( 'A second point readers should know.', 'sbek' ) . '</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>' . esc_html__( 'What happens next.', 'sbek' ) . '</li>
<!-- /wp:list-item --></ul>
<!-- /wp:list -->'
			),
		),
		'fact-box'    => array(
			'title'       => __( 'Fact box', 'sbek' ),
			'description' => __( 'Background information set apart from the story.', 'sbek' ),
			'keywords'    => array( __( 'background', 'sbek' ), __( 'info box', 'sbek' ) ),
			'content'     => sbek_pattern_box(
				__( 'Background', 'sbek' ),
				'<!-- wp:paragraph -->
<p>' . esc_html__( 'Add context, figures or a short timeline that helps readers follow the story.', 'sbek' ) . '</p>
<!-- /wp:paragraph -->'
			),
		),
		'read-also'   => array(
			'title'       => __( 'Read also', 'sbek' ),
			'description' => __( 'Links to related stories inside the article.', 'sbek' ),
			'keywords'    => array( __( 'related', 'sbek' ), __( 'links', 'sbek' ) ),
			'content'     => sbek_pattern_box(
				__( 'Read also', 'sbek' ),
				'<!-- wp:list -->
<ul class="wp-block-list"><!-- wp:list-item -->
<li>' . esc_html__( 'Select this text and add a link to a related story', 'sbek' ) . '</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>' . esc_html__( 'Select this text and add a link to a related story', 'sbek' ) . '</li>
<!-- /wp:list-item --></ul>
<!-- /wp:list -->'
			),
		),
		'quote'       => array(
			'title'       => __( 'Quote with source', 'sbek' ),
			'description' => __( 'A large quotation with the speaker’s name and role.', 'sbek' ),
			'keywords'    => array( __( 'quotation', 'sbek' ), __( 'interview', 'sbek' ) ),
			'content'     => '<!-- wp:quote {"className":"is-style-sbek-large"} -->
<blockquote class="wp-block-quote is-style-sbek-large"><!-- wp:paragraph -->
<p>' . esc_html__( 'Put the strongest line from the interview here.', 'sbek' ) . '</p>
<!-- /wp:paragraph --><cite>' . esc_html__( 'Name, role', 'sbek' ) . '</cite></blockquote>
<!-- /wp:quote -->',
		),
		'editor-note' => array(
			'title'       => __( 'Editor’s note', 'sbek' ),
			'description' => __( 'A correction or update note, separated from the story by a line.', 'sbek' ),
			'keywords'    => array( __( 'correction', 'sbek' ), __( 'update', 'sbek' ) ),
			'content'     => '<!-- wp:separator {"className":"is-style-sbek-brand"} -->
<hr class="wp-block-separator has-alpha-channel-opacity is-style-sbek-brand"/>
<!-- /wp:separator -->

<!-- wp:paragraph {"className":"sbek-editor-note"} -->
<p class="sbek-editor-note"><strong>' . esc_html__( 'Editor’s note:', 'sbek' ) . '</strong> ' . esc_html__( 'Describe what was corrected or updated, and when.', 'sbek' ) . '</p>
<!-- /wp:paragraph -->',
		),
	);

	foreach ( $patterns as $slug => $pattern ) {
		register_block_pattern(
			'sbek/' . $slug,
			$pattern + array( 'categories' => array( 'sbek' ) )
		);
	}
}
add_action( 'init', 'sbek_register_block_patterns' );

/**
 * Markup for a Fact box group: an underlined heading followed by `$inner`.
 *
 * @param string $title Heading text (unescaped).
 * @param string $inner Block markup to put under the heading.
 */
function sbek_pattern_box( string $title, string $inner ): string {
	return '<!-- wp:group {"className":"is-style-sbek-fact-box","layout":{"type":"constrained"}} -->
<div class="wp-block-group is-style-sbek-fact-box"><!-- wp:heading {"level":3,"className":"is-style-sbek-rule"} -->
<h3 class="wp-block-heading is-style-sbek-rule">' . esc_html( $title ) . '</h3>
<!-- /wp:heading -->

' . $inner . '</div>
<!-- /wp:group -->';
}
