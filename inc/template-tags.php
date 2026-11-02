<?php
/**
 * Presentation helpers shared by templates (the PHP counterparts of the UI
 * components: Thumbnail, CategoryBadge, SectionHeader, …).
 *
 * Helpers named sbek_get_*() return escaped HTML strings; the others echo.
 *
 * @package sbek
 */

defined( 'ABSPATH' ) || exit;

const SBEK_BRAND_COLOR = '#fa1939';

// ─── Terms ─────────────────────────────────────────────────────────────────────

/**
 * The category a post is filed under for badges, colors and related posts:
 * the first category that isn't the default "Uncategorized".
 *
 * @param WP_Post|int|null $post Post.
 */
function sbek_primary_category( $post = null ): ?WP_Term {
	$post       = get_post( $post );
	$categories = $post ? get_the_category( $post->ID ) : array();

	if ( empty( $categories ) ) {
		return null;
	}

	$default = (int) get_option( 'default_category' );

	foreach ( $categories as $category ) {
		if ( $category->term_id !== $default ) {
			return $category;
		}
	}

	return $categories[0];
}

/**
 * A term's accent color (term meta `sbek_color`), or the brand red.
 */
function sbek_term_color( ?WP_Term $term ): string {
	if ( ! $term ) {
		return SBEK_BRAND_COLOR;
	}

	$color = sanitize_hex_color( (string) get_term_meta( $term->term_id, 'sbek_color', true ) );

	return $color ?: SBEK_BRAND_COLOR;
}

/**
 * Inline `--cat` custom property for a term color (used by CSS for badges,
 * section headers and arrows).
 */
function sbek_color_style( string $hex ): string {
	return '--cat:' . ( sanitize_hex_color( $hex ) ?: SBEK_BRAND_COLOR ) . ';';
}

// ─── Dates ─────────────────────────────────────────────────────────────────────

/**
 * Publication date in the site's news format, e.g. "ច័ន្ទ, 19 តុលា 2026 09:32".
 *
 * @param WP_Post|int|null $post Post.
 */
function sbek_post_date( $post = null ): string {
	/* translators: PHP date format for article dates on cards and article pages. */
	return (string) get_the_date( _x( 'l, j F Y H:i', 'post date format', 'sbek' ), $post );
}

// ─── Images ────────────────────────────────────────────────────────────────────

/**
 * Featured image markup, or a neutral placeholder when the post has none.
 *
 * @param WP_Post             $post  Post.
 * @param string              $size  Registered image size.
 * @param array<string,mixed> $attrs Attributes for wp_get_attachment_image()
 *                                   (`sizes`, `loading`, `fetchpriority`, `class`…).
 */
function sbek_get_post_image( WP_Post $post, string $size = 'sbek-card', array $attrs = array() ): string {
	$attrs = array_merge(
		array(
			'class'    => 'sbek-img',
			'alt'      => '', // Decorative: the linked headline next to it carries the meaning.
			'decoding' => 'async',
		),
		$attrs
	);

	$thumbnail_id = get_post_thumbnail_id( $post );

	if ( $thumbnail_id ) {
		$html = wp_get_attachment_image( $thumbnail_id, $size, false, $attrs );
		if ( $html ) {
			return $html;
		}
	}

	return '<span class="sbek-img sbek-img--placeholder" aria-hidden="true"></span>';
}

/**
 * The 16:9 zoom-on-hover thumbnail frame (`.thumb` in the stylesheet).
 *
 * @param WP_Post             $post    Post.
 * @param array<string,mixed> $options size, sizes, class, eager.
 */
function sbek_get_thumb( WP_Post $post, array $options = array() ): string {
	$options = wp_parse_args(
		$options,
		array(
			'size'  => 'sbek-card',
			'sizes' => '(max-width: 767px) 150px, 180px',
			'class' => '',
			'eager' => false,
		)
	);

	$attrs = array( 'sizes' => $options['sizes'] );
	if ( $options['eager'] ) {
		$attrs['loading'] = 'eager';
	}

	$play = has_post_format( 'video', $post )
		? '<span class="thumb-play" aria-hidden="true">' . sbek_icon( 'play', array( 'width' => 14, 'height' => 14 ) ) . '</span>'
		: '';

	return sprintf(
		'<div class="thumb %1$s"><div class="thumb-ele"><div class="thumb-img">%2$s</div>%3$s</div></div>',
		esc_attr( $options['class'] ),
		sbek_get_post_image( $post, $options['size'], $attrs ),
		$play
	);
}

// ─── Badges, headers, logo ─────────────────────────────────────────────────────

/**
 * Colored category label with a slanted corner (pinned over hero images).
 */
function sbek_get_category_badge( WP_Post $post ): string {
	$category = sbek_primary_category( $post );

	if ( ! $category ) {
		return '';
	}

	return sprintf(
		'<span class="cat-badge" style="%s">%s</span>',
		esc_attr( sbek_color_style( sbek_term_color( $category ) ) ),
		esc_html( $category->name )
	);
}

/**
 * Colored tab header with a slanted corner on a 3px rule (SectionHeader).
 *
 * @param string $label      Visible label.
 * @param string $color      Hex accent color.
 * @param string $url        Link target ('' for a plain label).
 * @param string $extra_html Trusted, already-escaped markup floated right
 *                           (e.g. the list/grid switch).
 * @param string $tag        Heading element for the label.
 */
function sbek_section_header( string $label, string $color, string $url = '', string $extra_html = '', string $tag = 'h2' ): void {
	$tag   = in_array( $tag, array( 'h1', 'h2', 'h3' ), true ) ? $tag : 'h2';
	$inner = esc_html( $label ) . ' ' . sbek_icon( 'menu-right', array( 'class' => 'section-tab__arrow' ) );
	?>
	<div class="section-head" style="<?php echo esc_attr( sbek_color_style( $color ) ); ?>">
		<<?php echo esc_html( $tag ); ?> class="section-tab">
			<?php if ( $url ) : ?>
				<a href="<?php echo esc_url( $url ); ?>" class="section-tab__link"><?php echo $inner; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?></a>
			<?php else : ?>
				<span class="section-tab__link"><?php echo $inner; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?></span>
			<?php endif; ?>
		</<?php echo esc_html( $tag ); ?>>
		<?php echo $extra_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- documented as trusted. ?>
	</div>
	<?php
}

/**
 * Site logo <img> (Options page logo, else the site name as text).
 *
 * @param string $class Extra classes on the <img>/<span>.
 * @param int    $size  Rendered height in px.
 */
function sbek_get_logo( string $class = '', int $size = 82 ): string {
	$logo_id = function_exists( 'sbek_site_setting' ) ? absint( sbek_site_setting( 'logo', 0 ) ) : 0;

	if ( $logo_id ) {
		$html = wp_get_attachment_image(
			$logo_id,
			'medium',
			false,
			array(
				'class'         => trim( 'site-logo ' . $class ),
				'alt'           => get_bloginfo( 'name' ),
				'style'         => 'height:' . $size . 'px;width:auto',
				'loading'       => 'eager',
				'fetchpriority' => 'low',
				'sizes'         => ( $size * 2 ) . 'px',
			)
		);
		if ( $html ) {
			return $html;
		}
	}

	return '<span class="site-logo site-logo--text ' . esc_attr( $class ) . '">' . esc_html( get_bloginfo( 'name' ) ) . '</span>';
}

/**
 * Render a widget area only when it has widgets.
 *
 * @param string $id    Sidebar ID.
 * @param string $class Wrapper classes.
 */
function sbek_ad_area( string $id, string $class = '' ): bool {
	if ( ! is_active_sidebar( $id ) ) {
		return false;
	}

	echo '<div class="ad-area ' . esc_attr( $class ) . '">';
	dynamic_sidebar( $id );
	echo '</div>';

	return true;
}
