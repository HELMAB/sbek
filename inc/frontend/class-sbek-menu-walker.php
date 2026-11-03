<?php
/**
 * Walker for the red primary menu bar (two levels).
 *
 * - The item linking to the front page (core adds `menu-item-home`) renders
 *   as the house icon.
 * - Active state comes from WordPress' own context classes, so a category
 *   item is highlighted on that category's archive *and* on its posts.
 * - Labels without Khmer script (e.g. "AUTO TALK", or the English menu) get
 *   the Latin face instead of Koulen.
 * - Items with children get a toggle button and a `.sub-menu` dropdown
 *   (hover/focus on desktop, tap on mobile; theme.js handles the toggles).
 * - The menu ends with a hidden "More" item; theme.js moves the items that
 *   don't fit on one desktop row into it (see sbek_menu_more_item()).
 *
 * @package sbek
 */

defined( 'ABSPATH' ) || exit;

class Sbek_Menu_Walker extends Walker_Nav_Menu {

	private const ACTIVE_CLASSES = array(
		'current-menu-item',
		'current-menu-parent',
		'current-menu-ancestor',
		'current-post-ancestor',
		'current-category-ancestor',
	);

	/**
	 * @param string   $output Passed by reference.
	 * @param int      $depth  Depth.
	 * @param stdClass $args   Menu args.
	 */
	public function start_lvl( &$output, $depth = 0, $args = null ) {
		$output .= '<ul class="sub-menu">';
	}

	/**
	 * @param string   $output Passed by reference.
	 * @param int      $depth  Depth.
	 * @param stdClass $args   Menu args.
	 */
	public function end_lvl( &$output, $depth = 0, $args = null ) {
		$output .= '</ul>';
	}

	/**
	 * @param string   $output            Passed by reference.
	 * @param WP_Post  $data_object       Menu item.
	 * @param int      $depth             Depth.
	 * @param stdClass $args              Menu args.
	 * @param int      $current_object_id Current object ID.
	 */
	public function start_el( &$output, $data_object, $depth = 0, $args = null, $current_object_id = 0 ) {
		$item    = $data_object;
		$classes = array_map( 'strval', (array) $item->classes );
		$is_home = in_array( 'menu-item-home', $classes, true );
		$active  = (bool) array_intersect( self::ACTIVE_CLASSES, $classes );

		// Home only counts as active on the front page itself.
		if ( $is_home ) {
			$active = is_front_page();
		}

		$title = (string) apply_filters( 'nav_menu_item_title', $item->title, $item, $args, $depth ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core hook.
		$url   = (string) $item->url;

		// Custom links into this site follow the visitor's language.
		if ( 'custom' === $item->type ) {
			$url = sbek_localize_url( $url );
		}

		$li_classes = array( 'menu-item' );
		$a_classes  = array( 'menu-link' );
		$parent     = $this->has_children && 0 === (int) $depth;

		if ( $depth > 0 ) {
			$li_classes = array( 'sub-menu__item' );
			$a_classes  = array( 'sub-menu__link' );
		}
		if ( $parent ) {
			$li_classes[] = 'menu-item--parent';
		}
		if ( $is_home ) {
			$li_classes[] = 'menu-item--home';
		}
		if ( $active ) {
			$li_classes[] = 'is-active';
		}
		if ( ! preg_match( '/\p{Khmer}/u', $title ) ) {
			$a_classes[] = $depth > 0 ? 'sub-menu__link--latin' : 'menu-link--latin';
		}

		$attributes = array(
			'href'         => $url,
			'class'        => implode( ' ', $a_classes ),
			'aria-current' => $active ? 'page' : '',
			'target'       => $item->target,
			'rel'          => $item->xfn,
			'aria-label'   => $is_home ? ( $title ?: __( 'Home', 'sbek' ) ) : '',
		);

		$output .= '<li class="' . esc_attr( implode( ' ', $li_classes ) ) . '"><a';
		foreach ( $attributes as $name => $value ) {
			if ( '' !== (string) $value ) {
				$output .= ' ' . $name . '="' . ( 'href' === $name ? esc_url( $value ) : esc_attr( $value ) ) . '"';
			}
		}
		$output .= '>';
		$output .= $is_home
			? sbek_icon( 'home', array( 'class' => 'menu-link__icon', 'width' => 20, 'height' => 20 ) )
			: esc_html( wp_strip_all_tags( $title ) );
		$output .= '</a>';

		if ( $parent ) {
			$output .= sbek_submenu_toggle( wp_strip_all_tags( $title ) );
		}
	}

	/**
	 * @param string   $output      Passed by reference.
	 * @param WP_Post  $data_object Menu item.
	 * @param int      $depth       Depth.
	 * @param stdClass $args        Menu args.
	 */
	public function end_el( &$output, $data_object, $depth = 0, $args = null ) {
		$output .= '</li>';
	}
}

/**
 * Button that opens a dropdown.
 *
 * @param string $label Parent item label (for the accessible name).
 */
function sbek_submenu_toggle( string $label ): string {
	return sprintf(
		'<button type="button" class="submenu-toggle" aria-expanded="false" data-sbek-submenu-toggle><span class="screen-reader-text">%s</span>%s</button>',
		/* translators: %s: menu item label. */
		esc_html( sprintf( __( 'Show submenu for %s', 'sbek' ), $label ) ),
		sbek_icon( 'chevron-down', array( 'width' => 14, 'height' => 14 ) )
	);
}

/**
 * Hidden "More" dropdown at the end of the menu; theme.js fills it with the
 * items that don't fit on one desktop row (without JS the row wraps).
 */
function sbek_menu_more_item(): string {
	return sprintf(
		'<li class="menu-item menu-item--parent menu-item--more" hidden data-sbek-menu-more><button type="button" class="menu-link menu-more__btn" aria-expanded="false" data-sbek-submenu-toggle>%s%s</button><ul class="sub-menu"></ul></li>',
		esc_html__( 'More', 'sbek' ),
		sbek_icon( 'chevron-down', array( 'width' => 14, 'height' => 14 ) )
	);
}

/**
 * Menu shown until an editor assigns one: Home + top-level categories.
 */
function sbek_menu_fallback(): void {
	$categories = get_terms(
		array(
			'taxonomy'   => 'category',
			'parent'     => 0,
			'hide_empty' => true,
			'number'     => 8,
			'exclude'    => array( (int) get_option( 'default_category' ) ),
		)
	);

	echo '<ul class="menu-list" data-sbek-menu-list>';
	printf(
		'<li class="menu-item menu-item--home%1$s"><a class="menu-link" href="%2$s" aria-label="%3$s">%4$s</a></li>',
		is_front_page() ? ' is-active' : '',
		esc_url( sbek_home_url() ),
		esc_attr__( 'Home', 'sbek' ),
		sbek_icon( 'home', array( 'class' => 'menu-link__icon' ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
	);

	if ( ! is_wp_error( $categories ) ) {
		$current = is_singular( 'post' ) ? sbek_primary_category( get_queried_object_id() ) : null;

		foreach ( $categories as $category ) {
			$active = is_category( $category->term_id ) || ( $current && $current->term_id === $category->term_id );
			printf(
				'<li class="menu-item%1$s"><a class="menu-link%2$s" href="%3$s"%4$s>%5$s</a></li>',
				$active ? ' is-active' : '',
				preg_match( '/\p{Khmer}/u', $category->name ) ? '' : ' menu-link--latin',
				esc_url( get_term_link( $category ) ),
				$active ? ' aria-current="page"' : '',
				esc_html( $category->name )
			);
		}
	}

	echo sbek_menu_more_item(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper.
	echo '</ul>';
}

