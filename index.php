<?php
// Main entry point
get_header();
?>

<main id="primary" class="site-main container mx-auto px-4">
    <?php
    if ( have_posts() ) :
        while ( have_posts() ) :
            the_post();
            get_template_part( 'template-parts/content', get_post_type() );
        endwhile;
    else :
        get_template_part( 'template-parts/content', 'none' );
    endif;
    ?>
</main>

<?php
get_footer();
