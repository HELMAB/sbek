<?php
/**
 * Walker for the mobile navigation drawer.
 *
 * Renders the same menu as the desktop mega-menu, but as a stacked,
 * touch-friendly accordion: each top-level section is a full-width row, and
 * sections with children get a separate expand button (so the label stays a
 * real link to the section while the chevron toggles its sub-list). The
 * open/close behavior lives in assets/js/mobile-nav.js.
 */
class Sbek_Mobile_Menu_Walker extends Walker_Nav_Menu {

	/**
	 * ID of the top-level item whose children are about to be walked, so
	 * start_lvl() can tie the collapsible <ul> to its toggle button via
	 * aria-controls / id.
	 *
	 * @var int
	 */
	private $parent_id = 0;

	function start_lvl( &$output, $depth = 0, $args = null ) {
		$panel_id = 'mobile-submenu-' . $this->parent_id;
		$output  .= '<ul id="' . esc_attr( $panel_id ) . '" class="hidden bg-gray-50" data-mobile-submenu>';
	}

	function end_lvl( &$output, $depth = 0, $args = null ) {
		$output .= '</ul>';
	}

	function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
		$classes      = empty( $item->classes ) ? array() : (array) $item->classes;
		$has_children = in_array( 'menu-item-has-children', $classes, true );
		$is_current   = in_array( 'current-menu-item', $classes, true )
			|| in_array( 'current-menu-ancestor', $classes, true );

		$url   = ! empty( $item->url ) ? $item->url : '#';
		$title = apply_filters( 'the_title', $item->title, $item->ID );

		if ( 0 === $depth ) {
			$this->parent_id = (int) $item->ID;

			$link_cls = 'flex-1 py-4 text-base font-bold ' . ( $is_current ? 'text-sbek-red' : 'text-ink' );

			$output .= '<li class="border-b border-gray-200">';
			$output .= '<div class="flex items-center px-5">';
			$output .= '<a href="' . esc_url( $url ) . '" class="' . esc_attr( $link_cls ) . '">' . esc_html( $title ) . '</a>';

			if ( $has_children ) {
				$panel_id = 'mobile-submenu-' . $item->ID;
				$output  .= '<button type="button" class="mobile-submenu-toggle -mr-2 flex h-11 w-11 shrink-0 items-center justify-center text-gray-500" aria-expanded="false" aria-controls="' . esc_attr( $panel_id ) . '">';
				/* translators: %s: section name */
				$output  .= '<span class="sr-only">' . esc_attr( sprintf( t( 'toggle_submenu' ), $title ) ) . '</span>';
				$output  .= sbek_icon( 'chevron-down', [ 'class' => 'h-5 w-5 transition-transform duration-200' ] );
				$output  .= '</button>';
			}

			$output .= '</div>';
		} else {
			$child_cls = 'block py-3 pl-9 pr-5 text-[15px] font-medium ' . ( $is_current ? 'text-sbek-red' : 'text-gray-600' );
			$output   .= '<li>';
			$output   .= '<a href="' . esc_url( $url ) . '" class="' . esc_attr( $child_cls ) . '">' . esc_html( $title ) . '</a>';
		}
	}
}
