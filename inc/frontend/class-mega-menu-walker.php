<?php
/**
 * Custom Walker for Mega Menu
 */
class Sbek_Mega_Menu_Walker extends Walker_Nav_Menu {
    function start_lvl( &$output, $depth = 0, $args = null ) {
        $indent = str_repeat( "	", $depth );
        // Add specific classes for the dropdown container
        $classes = 'absolute left-0 top-full w-full bg-white shadow-lg border-t-4 border-sbek-red hidden group-hover:block z-50 p-6';
        $output .= "\n$indent<div class=\"sub-menu-wrap $classes\"><ul class=\"container mx-auto grid grid-cols-4 gap-4\">
";
    }

    function end_lvl( &$output, $depth = 0, $args = null ) {
        $indent = str_repeat( "	", $depth );
        $output .= "$indent</ul></div>\n";
    }

    function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
        // Standard item handling...
        $indent = ( $depth ) ? str_repeat( "	", $depth ) : '';
        
        $classes = empty( $item->classes ) ? array() : (array) $item->classes;
        $classes[] = 'menu-item-' . $item->ID;
        
        // Parent items need 'group' class for hover effects
        if ( in_array( 'menu-item-has-children', $classes ) ) {
            $classes[] = 'group relative';
        }

        $class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args, $depth ) );
        $class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';

        $output .= $indent . '<li' . $class_names . '>';

        $atts = array();
        $atts['title']  = ! empty( $item->attr_title ) ? $item->attr_title : '';
        $atts['target'] = ! empty( $item->target )     ? $item->target     : '';
        $atts['rel']    = ! empty( $item->xfn )        ? $item->xfn        : '';
        $atts['href']   = ! empty( $item->url )        ? $item->url        : '';
        
        // Tailwind classes for links
        $atts['class'] = 'block py-4 text-base font-bold hover:text-sbek-red transition-colors';

        $attributes = '';
        foreach ( $atts as $attr => $value ) {
            if ( ! empty( $value ) ) {
                $value = ( 'href' === $attr ) ? esc_url( $value ) : esc_attr( $value );
                $attributes .= ' ' . $attr . '="' . $value . '"';
            }
        }

        $title = apply_filters( 'the_title', $item->title, $item->ID );
        $item_output = $args->before;
        $item_output .= '<a'. $attributes .'>';
        $item_output .= $args->link_before . $title . $args->link_after;
        $item_output .= '</a>';
        $item_output .= $args->after;

        $output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
    }
}
