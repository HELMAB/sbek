<?php
/**
 * Site header: black top bar (logo + ad), red primary menu that sticks to the
 * top after scrolling past the top bar, search panel, breaking-news ticker.
 *
 * @package sbek
 */

defined( 'ABSPATH' ) || exit;

$sbek_menu_location = sbek_is_default_lang() ? 'primary' : 'primary-' . sbek_lang();
$sbek_breaking      = sbek_section_enabled( 'ticker' ) ? sbek_get_breaking_posts() : array();
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main"><?php esc_html_e( 'Skip to content', 'sbek' ); ?></a>

<header class="site-header">
	<section id="top" class="top-bar">
		<div class="container top-bar__inner">
			<a href="<?php echo esc_url( sbek_home_url() ); ?>" class="top-bar__logo" rel="home">
				<?php echo sbek_get_logo( '', 82 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
			</a>
			<?php sbek_ad_area( 'sbek-ad-header', 'top-bar__ad' ); ?>
		</div>
	</section>

	<div class="menu-shell">
		<nav id="menu" class="menu-bar" aria-label="<?php esc_attr_e( 'Primary', 'sbek' ); ?>" data-sbek-menu>
			<div class="container menu-bar__inner">
				<div class="menu-bar__mobile">
					<a href="<?php echo esc_url( sbek_home_url() ); ?>" class="menu-bar__brand" rel="home">
						<?php echo sbek_get_logo( '', 40 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
					</a>
					<button type="button" class="menu-toggle" aria-controls="primary-menu" aria-expanded="false" data-sbek-menu-toggle>
						<span class="screen-reader-text"><?php esc_html_e( 'Toggle navigation', 'sbek' ); ?></span>
						<span class="menu-toggle__bar"></span><span class="menu-toggle__bar"></span><span class="menu-toggle__bar"></span>
					</button>
				</div>

				<div id="primary-menu" class="menu-bar__panel">
					<?php
					wp_nav_menu(
						array(
							'theme_location' => $sbek_menu_location,
							'container'      => false,
							'items_wrap'     => '<ul class="menu-list">%3$s</ul>',
							'depth'          => 1,
							'walker'         => new Sbek_Menu_Walker(),
							'fallback_cb'    => 'sbek_menu_fallback',
						)
					);
					?>
					<div class="menu-tools">
						<button type="button" class="menu-tools__search" aria-controls="menu-search" aria-expanded="false" data-sbek-search-toggle>
							<?php echo sbek_icon( 'search', array( 'width' => 18, 'height' => 18 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
							<span class="screen-reader-text"><?php esc_html_e( 'Search', 'sbek' ); ?></span>
						</button>
						<ul class="lang-switch" aria-label="<?php esc_attr_e( 'Language', 'sbek' ); ?>">
							<?php foreach ( sbek_language_switcher_items() as $sbek_lang_slug => $sbek_lang_item ) : ?>
								<li>
									<a href="<?php echo esc_url( $sbek_lang_item['url'] ); ?>"
										lang="<?php echo esc_attr( sbek_languages()[ $sbek_lang_slug ]['hreflang'] ); ?>"
										hreflang="<?php echo esc_attr( sbek_languages()[ $sbek_lang_slug ]['hreflang'] ); ?>"
										<?php echo $sbek_lang_item['active'] ? 'aria-current="true" class="is-active"' : ''; ?>><?php echo esc_html( $sbek_lang_item['label'] ); ?></a>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				</div>
			</div>

			<div id="menu-search" class="menu-search" hidden>
				<div class="container">
					<?php get_search_form(); ?>
				</div>
			</div>
		</nav>
	</div>

	<?php if ( $sbek_breaking ) : ?>
		<div class="ticker">
			<div class="container ticker__inner">
				<span class="ticker__label"><?php echo sbek_icon( 'zap', array( 'width' => 14, 'height' => 14 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?><?php esc_html_e( 'Breaking', 'sbek' ); ?></span>
				<div class="ticker__viewport">
					<?php
					// Rendered twice so the -50% marquee loops seamlessly; the copy is hidden from AT.
					foreach ( array( false, true ) as $sbek_ticker_copy ) :
						?>
						<ul class="ticker__track"<?php echo $sbek_ticker_copy ? ' aria-hidden="true"' : ''; ?>>
							<?php foreach ( $sbek_breaking as $sbek_ticker_post ) : ?>
								<li><a href="<?php echo esc_url( get_permalink( $sbek_ticker_post ) ); ?>"<?php echo $sbek_ticker_copy ? ' tabindex="-1"' : ''; ?>><?php echo esc_html( get_the_title( $sbek_ticker_post ) ); ?></a></li>
							<?php endforeach; ?>
						</ul>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	<?php endif; ?>
</header>
