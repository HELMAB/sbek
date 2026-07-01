<?php
/**
 * Custom Walker for the primary navigation's mega-menu dropdowns.
 *
 * Top-level items render as a plain nav link. Items with children get a
 * full-width dropdown: the child terms as a numbered index list, plus a
 * "Latest in {Section}" live headline pulled from that category.
 */
class Sbek_Mega_Menu_Walker extends Walker_Nav_Menu {

	/**
	 * Top-level item currently opening a dropdown. Walker_Nav_Menu never
	 * passes the parent item into start_lvl()/end_lvl(), so start_el()
	 * stashes it here right before its children are walked.
	 *
	 * @var WP_Post|null
	 */
	private $parent_item;

	/**
	 * 1-based position of the child item currently being rendered within
	 * its parent's dropdown, used for the "01 / 02 / 03" index labels.
	 *
	 * @var int
	 */
	private $child_index = 0;

	function start_lvl( &$output, $depth = 0, $args = null ) {
		$indent = str_repeat( "\t", $depth );

		$this->child_index = 0;

		$output .= "\n$indent<div class=\"absolute left-0 top-full z-40 w-full -translate-y-1 opacity-0 invisible pointer-events-none transition-all duration-200 ease-out group-hover:translate-y-0 group-hover:opacity-100 group-hover:visible group-hover:pointer-events-auto group-focus-within:translate-y-0 group-focus-within:opacity-100 group-focus-within:visible group-focus-within:pointer-events-auto\">";
		$output .= '<div class="border-t-[3px] border-sbek-red bg-white shadow-2xl">';
		$output .= '<div class="mx-auto grid max-w-7xl grid-cols-1 gap-x-12 px-8 py-7 md:grid-cols-[1fr_300px]">';
		$output .= '<ul class="grid gap-x-8 gap-y-1 sm:grid-cols-3">' . "\n";
	}

	function end_lvl( &$output, $depth = 0, $args = null ) {
		$indent = str_repeat( "\t", $depth );
		$output .= "$indent</ul>\n";
		$output .= $this->render_latest_in_section( $this->parent_item );
		$output .= "$indent</div></div></div>\n";
	}

	/**
	 * Renders the "Latest in {Section}" column shown next to a dropdown's
	 * item list. Only pulls real posts already in that category — no
	 * fabricated content — and falls back to a plain empty state.
	 */
	private function render_latest_in_section( $parent ): string {
		$title = apply_filters( 'the_title', $parent->title, $parent->ID );

		$query = ( 'taxonomy' === $parent->type && 'category' === $parent->object )
			? sbek_get_megamenu_latest_post( (int) $parent->object_id )
			: null;

		$html  = '<div class="mt-6 border-t border-gray-200 pt-6 md:mt-0 md:border-l md:border-t-0 md:pl-10 md:pt-0">';
		$html .= '<p class="mb-4 text-xs font-bold uppercase tracking-wider text-gray-400">' . esc_html( t( 'latest_in', $title ) ) . '</p>';

		if ( $query && $query->have_posts() ) {
			$query->the_post();

			$html .= '<a href="' . esc_url( get_permalink() ) . '" class="group/latest block">';
			if ( has_post_thumbnail() ) {
				$html .= '<div class="mb-3 aspect-video overflow-hidden rounded-sm bg-gray-100">';
				$html .= get_the_post_thumbnail( get_the_ID(), 'medium', [ 'class' => 'h-full w-full object-cover transition-transform duration-300 group-hover/latest:scale-105' ] );
				$html .= '</div>';
			}
			$html .= '<h4 class="text-base font-bold leading-snug text-gray-900 transition-colors duration-150 group-hover/latest:text-sbek-red">' . esc_html( get_the_title() ) . '</h4>';
			$html .= '<span class="mt-1 block text-xs text-gray-500">' . esc_html( t( 'time_ago', human_time_diff( get_the_time( 'U' ), current_time( 'timestamp' ) ) ) ) . '</span>';
			$html .= '</a>';

			wp_reset_postdata();
		} else {
			$html .= '<p class="text-sm text-gray-400">' . esc_html( t( 'no_posts_found' ) ) . '</p>';
		}

		$html .= '</div>';

		return $html;
	}

	function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
		$indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';

		$classes   = empty( $item->classes ) ? array() : (array) $item->classes;
		$classes[] = 'menu-item-' . $item->ID;

		$has_children = in_array( 'menu-item-has-children', $classes, true );
		$is_current   = in_array( 'current-menu-item', $classes, true )
			|| in_array( 'current_page_item', $classes, true )
			|| in_array( 'current-menu-ancestor', $classes, true );

		// Parent items need 'group' class for hover effects. Positioning is
		// anchored to the header row (see header.php), not the <li> itself,
		// so the mega-menu panel spans the full header width instead of
		// shrinking to the width of the menu item's own text.
		if ( $has_children ) {
			$classes[]         = 'group';
			$this->parent_item = $item;
		}

		$class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args, $depth ) );
		$class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';

		$output .= $indent . '<li' . $class_names . '>';

		$atts = array();
		$atts['title']  = ! empty( $item->attr_title ) ? $item->attr_title : '';
		$atts['target'] = ! empty( $item->target )     ? $item->target     : '';
		$atts['rel']    = ! empty( $item->xfn )        ? $item->xfn        : '';
		$atts['href']   = ! empty( $item->url )        ? $item->url        : '';

		if ( 0 === $depth ) {
			// Top-level item: bold label, red rule grows in on hover (and
			// stays lit for the active section).
			$atts['class'] = trim( implode( ' ', array_filter( [
				'relative flex h-full items-center gap-1.5 text-[15px] font-bold transition-colors duration-150',
				$is_current ? 'text-sbek-red' : 'text-gray-900 hover:text-sbek-red',
				"after:content-[''] after:absolute after:inset-x-0 after:-bottom-0.5 after:h-[3px] after:origin-center after:bg-sbek-red after:transition-transform after:duration-200 after:ease-out",
				$is_current ? 'after:scale-x-100' : 'after:scale-x-0 group-hover:after:scale-x-100',
			] ) ) );
		} else {
			// Dropdown row: a numbered index entry (01/02/03).
			$this->child_index++;
			$atts['class'] = 'group/item flex items-baseline gap-3 rounded-sm py-3 text-[15px] font-semibold text-gray-800 transition-colors duration-150 hover:text-sbek-red';
		}

		$attributes = '';
		foreach ( $atts as $attr => $value ) {
			if ( ! empty( $value ) ) {
				$value = ( 'href' === $attr ) ? esc_url( $value ) : esc_attr( $value );
				$attributes .= ' ' . $attr . '="' . $value . '"';
			}
		}

		$title = apply_filters( 'the_title', $item->title, $item->ID );

		$item_output  = $args->before;
		$item_output .= '<a' . $attributes . '>';

		if ( 0 === $depth ) {
			$item_output .= $args->link_before . $title . $args->link_after;
			if ( $has_children ) {
				$item_output .= sbek_icon( 'chevron-down', [ 'class' => 'h-3.5 w-3.5 text-gray-400 transition-transform duration-200 group-hover:rotate-180 group-hover:text-sbek-red' ] );
			}
		} else {
			$item_output .= '<span class="font-mono text-xs font-bold text-gray-300 transition-colors duration-150 group-hover/item:text-sbek-red">' . esc_html( str_pad( (string) $this->child_index, 2, '0', STR_PAD_LEFT ) ) . '</span>';
			$item_output .= '<span class="border-b border-transparent transition-colors duration-150 group-hover/item:border-sbek-red">' . $args->link_before . $title . $args->link_after . '</span>';
		}

		$item_output .= '</a>';
		$item_output .= $args->after;

		$output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
	}
}
