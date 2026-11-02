/**
 * Sbek front-end behaviour. Vanilla JS, no dependencies, loaded with `defer`.
 * Each module activates only when its data-* hook is on the page, and every
 * page works without JS (links, pagination and the first carousel slide
 * remain usable).
 */
( function () {
	'use strict';

	const MOBILE = window.matchMedia( '(max-width: 767px)' );
	const REDUCED_MOTION = window.matchMedia( '(prefers-reduced-motion: reduce)' );

	/* ── Menu: sticky bar, mobile toggle, search panel ─────────────────── */

	function initMenu() {
		const nav = document.querySelector( '[data-sbek-menu]' );
		if ( ! nav ) {
			return;
		}

		const top = document.getElementById( 'top' );
		const toggle = nav.querySelector( '[data-sbek-menu-toggle]' );
		const searchToggle = nav.querySelector( '[data-sbek-search-toggle]' );
		const searchPanel = document.getElementById( 'menu-search' );

		// Stick once the black top bar (hidden on mobile → 0px) has scrolled away.
		let ticking = false;
		const update = () => {
			ticking = false;
			nav.classList.toggle( 'is-fixed', window.scrollY > ( top ? top.offsetHeight : 0 ) );
		};
		const onScroll = () => {
			if ( ! ticking ) {
				ticking = true;
				window.requestAnimationFrame( update );
			}
		};
		window.addEventListener( 'scroll', onScroll, { passive: true } );
		window.addEventListener( 'resize', onScroll, { passive: true } );
		update();

		const setMenu = ( open ) => {
			nav.classList.toggle( 'is-open', open );
			if ( toggle ) {
				toggle.setAttribute( 'aria-expanded', String( open ) );
			}
		};
		const setSearch = ( open ) => {
			if ( ! searchPanel || ! searchToggle ) {
				return;
			}
			searchPanel.hidden = ! open;
			searchToggle.setAttribute( 'aria-expanded', String( open ) );
			if ( open ) {
				const input = searchPanel.querySelector( 'input[type="search"]' );
				if ( input ) {
					input.focus();
				}
			}
		};

		if ( toggle ) {
			toggle.addEventListener( 'click', () => setMenu( ! nav.classList.contains( 'is-open' ) ) );
		}
		if ( searchToggle ) {
			searchToggle.addEventListener( 'click', () => setSearch( searchPanel.hidden ) );
		}

		document.addEventListener( 'keydown', ( event ) => {
			if ( 'Escape' !== event.key ) {
				return;
			}
			if ( searchPanel && ! searchPanel.hidden ) {
				setSearch( false );
				searchToggle.focus();
			} else if ( nav.classList.contains( 'is-open' ) ) {
				setMenu( false );
				toggle.focus();
			}
		} );

		MOBILE.addEventListener( 'change', () => setMenu( false ) );
	}

	/* ── Hero carousel (mobile only; the same tiles are a grid on desktop) ── */

	function initCarousel( root ) {
		const track = root.querySelector( '[data-carousel-track]' );
		const slides = track ? Array.from( track.children ) : [];
		if ( slides.length < 2 ) {
			return;
		}

		const INTERVAL = 5000;
		let index = 0;
		let timer = 0;
		let paused = false;

		const render = () => {
			const mobile = MOBILE.matches;
			track.style.transform = mobile ? `translateX(-${ index * 100 }%)` : '';
			slides.forEach( ( slide, i ) => {
				const hidden = mobile && i !== index;
				if ( hidden ) {
					slide.setAttribute( 'aria-hidden', 'true' );
					slide.tabIndex = -1;
				} else {
					slide.removeAttribute( 'aria-hidden' );
					slide.removeAttribute( 'tabindex' );
				}
			} );
		};

		const schedule = () => {
			window.clearTimeout( timer );
			if ( MOBILE.matches && ! paused && ! REDUCED_MOTION.matches && ! document.hidden ) {
				timer = window.setTimeout( () => go( 1 ), INTERVAL );
			}
		};

		const go = ( delta ) => {
			index = ( index + delta + slides.length ) % slides.length;
			render();
			schedule(); // A manual move restarts the full interval.
		};

		const prev = root.querySelector( '[data-carousel-prev]' );
		const next = root.querySelector( '[data-carousel-next]' );
		if ( prev ) {
			prev.addEventListener( 'click', () => go( -1 ) );
		}
		if ( next ) {
			next.addEventListener( 'click', () => go( 1 ) );
		}

		// Pause while the visitor is interacting.
		const pause = ( state ) => () => {
			paused = state;
			schedule();
		};
		root.addEventListener( 'mouseenter', pause( true ) );
		root.addEventListener( 'mouseleave', pause( false ) );
		root.addEventListener( 'focusin', pause( true ) );
		root.addEventListener( 'focusout', pause( false ) );
		document.addEventListener( 'visibilitychange', schedule );

		// Touch swipe.
		let startX = null;
		track.addEventListener( 'touchstart', ( event ) => {
			startX = event.touches[ 0 ].clientX;
		}, { passive: true } );
		track.addEventListener( 'touchend', ( event ) => {
			if ( null === startX ) {
				return;
			}
			const dx = event.changedTouches[ 0 ].clientX - startX;
			startX = null;
			if ( Math.abs( dx ) > 40 ) {
				go( dx < 0 ? 1 : -1 );
			}
		} );

		MOBILE.addEventListener( 'change', () => {
			index = 0;
			render();
			schedule();
		} );

		render();
		schedule();
	}

	/* ── Mobile swipe rows: native touch scroll + mouse drag ───────────── */

	function initSwipeRow( row ) {
		let drag = null;

		row.addEventListener( 'pointerdown', ( event ) => {
			if ( 'mouse' === event.pointerType ) {
				drag = { x: event.clientX, scroll: row.scrollLeft, moved: false };
			}
		} );
		row.addEventListener( 'pointermove', ( event ) => {
			if ( ! drag ) {
				return;
			}
			const dx = event.clientX - drag.x;
			if ( Math.abs( dx ) > 5 ) {
				drag.moved = true;
				row.classList.add( 'is-dragging' );
			}
			row.scrollLeft = drag.scroll - dx;
		} );
		const end = () => {
			row.classList.remove( 'is-dragging' );
			if ( drag && ! drag.moved ) {
				drag = null;
			}
		};
		row.addEventListener( 'pointerup', end );
		row.addEventListener( 'pointerleave', end );

		// Swallow the click that ends a drag so it doesn't open a story.
		row.addEventListener( 'click', ( event ) => {
			if ( drag && drag.moved ) {
				event.preventDefault();
				event.stopPropagation();
			}
			drag = null;
		}, true );
		row.addEventListener( 'dragstart', ( event ) => event.preventDefault() );
	}

	/* ── "Next" arrows that cycle through pre-rendered pages ───────────── */

	function initPager( pager ) {
		const view = pager.querySelector( '[data-pager-view]' );
		const next = pager.querySelector( '[data-pager-next]' );
		const templates = Array.from( pager.querySelectorAll( 'template[data-pager-page]' ) );
		if ( ! view || ! next || ! templates.length ) {
			return;
		}

		const pages = [ Array.from( view.childNodes ) ].concat(
			templates.map( ( template ) => Array.from( template.content.cloneNode( true ).childNodes ) )
		);
		let index = 0;

		next.addEventListener( 'click', () => {
			index = ( index + 1 ) % pages.length;
			view.replaceChildren( ...pages[ index ] );
		} );
	}

	/* ── Feeds: list/grid switch + infinite scroll over rel=next pages ──── */

	const VIEW_KEY = 'sbekFeedView';

	function storedView() {
		try {
			return window.localStorage.getItem( VIEW_KEY );
		} catch ( e ) {
			return null;
		}
	}

	function initFeed( feed ) {
		// View switch.
		const buttons = feed.querySelectorAll( '.view-switch__btn' );
		const applyView = ( view ) => {
			feed.classList.toggle( 'is-grid', 'grid' === view );
			buttons.forEach( ( button ) => {
				button.setAttribute( 'aria-pressed', String( button.dataset.view === view ) );
			} );
		};
		buttons.forEach( ( button ) => {
			button.addEventListener( 'click', () => {
				applyView( button.dataset.view );
				try {
					window.localStorage.setItem( VIEW_KEY, button.dataset.view );
				} catch ( e ) {
					// Storage disabled: the choice just isn't remembered.
				}
			} );
		} );
		applyView( 'grid' === storedView() ? 'grid' : 'list' );

		// Infinite scroll.
		const list = feed.querySelector( '[data-feed-list]' );
		const pagination = feed.querySelector( '[data-feed-pagination]' );
		const loader = feed.querySelector( '[data-feed-loader]' );
		if ( ! list || ! pagination || ! loader || ! ( 'IntersectionObserver' in window ) ) {
			return;
		}

		const nextUrlFrom = ( root ) => {
			const link = root.querySelector( '[data-feed-pagination] a.next' );
			if ( ! link ) {
				return null;
			}
			const url = new URL( link.href, window.location.href );
			return url.origin === window.location.origin ? url.href : null; // Same-origin only.
		};

		let nextUrl = nextUrlFrom( feed );
		if ( ! nextUrl ) {
			return;
		}

		let loading = false;
		feed.classList.add( 'is-infinite' );
		loader.hidden = false;

		const stop = ( failed ) => {
			observer.disconnect();
			loader.hidden = true;
			if ( failed ) {
				feed.classList.remove( 'is-infinite' ); // Fall back to plain pagination.
			}
		};

		const loadMore = async () => {
			if ( loading || ! nextUrl ) {
				return;
			}
			loading = true;
			try {
				const response = await fetch( nextUrl, { credentials: 'same-origin', headers: { Accept: 'text/html' } } );
				if ( ! response.ok ) {
					throw new Error( `HTTP ${ response.status }` );
				}
				const doc = new DOMParser().parseFromString( await response.text(), 'text/html' );
				const items = doc.querySelectorAll( '[data-feed-list] [data-feed-item]' );
				items.forEach( ( item ) => list.appendChild( document.importNode( item, true ) ) );

				// Keep the no-JS pagination in sync with what's loaded.
				const freshPagination = doc.querySelector( '[data-feed-pagination]' );
				if ( freshPagination ) {
					pagination.replaceChildren( ...Array.from( freshPagination.childNodes ).map( ( node ) => document.importNode( node, true ) ) );
				}

				nextUrl = items.length ? nextUrlFrom( doc ) : null;
				if ( ! nextUrl ) {
					stop( false );
				}
			} catch ( error ) {
				stop( true );
			} finally {
				loading = false;
			}
		};

		const observer = new IntersectionObserver( ( entries ) => {
			if ( entries.some( ( entry ) => entry.isIntersecting ) ) {
				loadMore();
			}
		}, { rootMargin: '400px 0px' } );
		observer.observe( loader );
	}

	/* ── Khmer word-break toggle (reveals U+200B zero-width spaces) ────── */

	function initWordBreakToggle( toggle ) {
		const body = document.querySelector( '[data-sbek-article-body]' );
		if ( ! body ) {
			return;
		}

		const ZWSP = /​/g;
		const originals = new Map();

		toggle.addEventListener( 'change', () => {
			if ( toggle.checked ) {
				const walker = document.createTreeWalker( body, NodeFilter.SHOW_TEXT );
				for ( let node = walker.nextNode(); node; node = walker.nextNode() ) {
					if ( node.nodeValue.includes( '​' ) ) {
						originals.set( node, node.nodeValue );
						node.nodeValue = node.nodeValue.replace( ZWSP, ' ' );
					}
				}
			} else {
				originals.forEach( ( value, node ) => {
					node.nodeValue = value;
				} );
				originals.clear();
			}
		} );
	}

	/* ── Boot ──────────────────────────────────────────────────────────── */

	initMenu();
	document.querySelectorAll( '[data-sbek-carousel]' ).forEach( initCarousel );
	document.querySelectorAll( '[data-sbek-swipe]' ).forEach( initSwipeRow );
	document.querySelectorAll( '[data-sbek-pager]' ).forEach( initPager );
	document.querySelectorAll( '[data-sbek-feed]' ).forEach( initFeed );
	document.querySelectorAll( '[data-sbek-zwsp-toggle]' ).forEach( initWordBreakToggle );
}() );
