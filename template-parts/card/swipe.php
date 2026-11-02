<?php
/**
 * 150×180 card for the mobile horizontal swipe rows.
 *
 * Args:
 *   post  WP_Post (required)
 *
 * @package sbek
 */

defined( 'ABSPATH' ) || exit;

if ( ! ( $args['post'] ?? null ) instanceof WP_Post ) {
	return;
}
?>
<div class="swipe-card">
	<?php
	get_template_part(
		'template-parts/card/thumb',
		null,
		array(
			'post'        => $args['post'],
			'sizes'       => '150px',
			'title_class' => 'news-thumb__title--swipe',
		)
	);
	?>
</div>
