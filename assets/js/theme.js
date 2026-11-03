/**
 * Sbek front-end behaviour. Vanilla JS, no dependencies, loaded with `defer`.
 * Each module activates only when its data-* hook is on the page, and every
 * page works without JS (links, pagination and the first carousel slide
 * remain usable).
 *
 * An inline <head> script (inc/frontend/assets.php) adds `sbek-js` to <html>
 * and restores the saved feed view (`sbek-grid`) before first paint.
 */
( function () {
	'use strict';

	const ROOT = document.documentElement;
	const MOBILE = window.matchMedia( '(max-width: 767px)' );
	const REDUCED_MOTION = window.matchMedia( '(prefers-reduced-motion: reduce)' );

	/** "Page %1$s of %2$s" style templates from PHP. */
	const format = ( template, ...values ) =>
		values.reduce(
			( text, value, i ) => text.replace( `%${ i + 1 }$s`, value ).replace( `%${ i + 1 }$d`, value ),
			template
		).replace( '%d', values[ 0 ] ).replace( '%s', values[ 0 ] );

	/* ── Menu: sticky bar, mobile toggle, search panel, dropdowns ──────── */

	function initMenu() {
		const nav = document.querySelector( '[data-sbek-menu]' );
		if ( ! nav ) {
			return;
		}

		const top = document.getElementById( 'top' );
		const toggle = nav.querySelector( '[data-sbek-menu-toggle]' );
		const searchToggles = Array.from( nav.querySelectorAll( '[data-sbek-search-toggle]' ) );
		const searchPanel = document.getElementById( 'menu-search' );
		let searchOpener = null;

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

		const closeSubmenus = ( except ) => {
			nav.querySelectorAll( 'li.is-open' ).forEach( ( li ) => {
				if ( li !== except && ! li.contains( except ) ) {
					li.classList.remove( 'is-open' );
					const button = li.querySelector( ':scope > [data-sbek-submenu-toggle]' );
					if ( button ) {
						button.setAttribute( 'aria-expanded', 'false' );
					}
				}
			} );
		};

		const isMenuOpen = () => nav.classList.contains( 'is-open' );
		const setMenu = ( open ) => {
			nav.classList.toggle( 'is-open', open );
			// No page scrolling behind the full-height mobile menu.
			ROOT.classList.toggle( 'sbek-menu-open', open && MOBILE.matches );
			if ( toggle ) {
				toggle.setAttribute( 'aria-expanded', String( open ) );
			}
			if ( ! open ) {
				closeSubmenus( null );
			}
		};

		const isSearchOpen = () => searchPanel && ! searchPanel.hidden;
		const setSearch = ( open, opener ) => {
			if ( ! searchPanel ) {
				return;
			}
			searchPanel.hidden = ! open;
			searchToggles.forEach( ( button ) => button.setAttribute( 'aria-expanded', String( open ) ) );
			if ( open ) {
				searchOpener = opener || searchOpener;
				if ( MOBILE.matches && isMenuOpen() ) {
					setMenu( false );
				}
				const input = searchPanel.querySelector( 'input[type="search"]' );
				if ( input ) {
					input.focus();
				}
			}
		};

		if ( toggle ) {
			toggle.addEventListener( 'click', () => {
				setMenu( ! isMenuOpen() );
				if ( isMenuOpen() ) {
					setSearch( false );
				}
			} );
		}
		searchToggles.forEach( ( button ) => {
			button.addEventListener( 'click', () => setSearch( ! isSearchOpen(), button ) );
		} );

		// Dropdowns: hover/focus opens them on desktop (CSS); the chevron
		// buttons open them on touch and for keyboard users.
		nav.addEventListener( 'click', ( event ) => {
			const button = event.target.closest( '[data-sbek-submenu-toggle]' );
			if ( ! button ) {
				return;
			}
			const li = button.closest( 'li' );
			const open = ! li.classList.contains( 'is-open' );
			closeSubmenus( li );
			li.classList.toggle( 'is-open', open );
			button.setAttribute( 'aria-expanded', String( open ) );
		} );
		nav.addEventListener( 'focusout', ( event ) => {
			if ( MOBILE.matches ) {
				return;
			}
			nav.querySelectorAll( 'li.is-open' ).forEach( ( li ) => {
				if ( ! li.contains( event.relatedTarget ) ) {
					closeSubmenus( event.relatedTarget );
				}
			} );
		} );

		// Clicks outside the bar close whatever it has open.
		document.addEventListener( 'click', ( event ) => {
			if ( nav.contains( event.target ) ) {
				return;
			}
			closeSubmenus( null );
			if ( isMenuOpen() ) {
				setMenu( false );
			}
			if ( isSearchOpen() ) {
				setSearch( false );
			}
		} );

		document.addEventListener( 'keydown', ( event ) => {
			if ( 'Escape' !== event.key ) {
				return;
			}
			const openSub = nav.querySelector( 'li.is-open' );
			if ( openSub ) {
				const button = openSub.querySelector( ':scope > [data-sbek-submenu-toggle]' );
				closeSubmenus( null );
				if ( button ) {
					button.focus();
				}
			} else if ( isSearchOpen() ) {
				setSearch( false );
				const visible = searchToggles.find( ( button ) => button.offsetParent );
				( searchOpener && searchOpener.offsetParent ? searchOpener : visible )?.focus();
			} else if ( isMenuOpen() ) {
				setMenu( false );
				toggle.focus();
			}
		} );

		MOBILE.addEventListener( 'change', () => setMenu( false ) );

		const list = nav.querySelector( '[data-sbek-menu-list]' );
		if ( list ) {
			initMenuOverflow( list );
		}
	}

	/**
	 * "Priority+" menu: on desktop, items that would wrap onto a second row
	 * move into the trailing "More" dropdown (without JS the row wraps).
	 */
	function initMenuOverflow( list ) {
		const more = list.querySelector( '[data-sbek-menu-more]' );
		const moreList = more ? more.querySelector( '.sub-menu' ) : null;
		if ( ! moreList ) {
			return;
		}

		const restore = () => {
			Array.from( moreList.children ).forEach( ( li ) => {
				li.classList.remove( 'is-overflow' );
				list.insertBefore( li, more );
			} );
			more.hidden = true;
			more.classList.remove( 'is-active', 'is-open' );
		};

		const wraps = () => {
			const items = Array.from( list.children ).filter( ( li ) => ! li.hidden );
			return items.length > 1 && items.some( ( li ) => li.offsetTop > items[ 0 ].offsetTop );
		};

		const fit = () => {
			restore();
			if ( MOBILE.matches || ! wraps() ) {
				return;
			}
			more.hidden = false;
			for ( let guard = 0; guard < 50 && wraps(); guard++ ) {
				const items = Array.from( list.children ).filter( ( li ) => li !== more );
				if ( items.length < 2 ) {
					break;
				}
				const last = items[ items.length - 1 ];
				last.classList.add( 'is-overflow' );
				last.classList.remove( 'is-open' );
				moreList.insertBefore( last, moreList.firstChild );
			}
			more.classList.toggle( 'is-active', !! moreList.querySelector( '.is-active' ) );
		};

		let frame = 0;
		const schedule = () => {
			window.cancelAnimationFrame( frame );
			frame = window.requestAnimationFrame( fit );
		};
		window.addEventListener( 'resize', schedule, { passive: true } );
		MOBILE.addEventListener( 'change', schedule );
		if ( document.fonts && document.fonts.ready ) {
			document.fonts.ready.then( schedule ); // Khmer webfonts change label widths.
		}
		fit();
	}

	/* ── Breaking-news ticker: pause button ────────────────────────────── */

	function initTicker( button ) {
		const ticker = button.closest( '.ticker' );
		button.addEventListener( 'click', () => {
			const paused = 'true' !== button.getAttribute( 'aria-pressed' );
			button.setAttribute( 'aria-pressed', String( paused ) );
			ticker.classList.toggle( 'is-paused', paused );
		} );
	}

	/* ── Hero carousel (mobile only; the same tiles are a grid on desktop) ── */

	function initCarousel( root ) {
		const track = root.querySelector( '[data-carousel-track]' );
		const slides = track ? Array.from( track.children ) : [];
		if ( slides.length < 2 ) {
			return;
		}

		const INTERVAL = 5000;
		const dots = Array.from( root.querySelectorAll( '[data-carousel-dot]' ) );
		const pauseButton = root.querySelector( '[data-carousel-pause]' );
		const pauseLabel = root.querySelector( '[data-carousel-pause-label]' );
		let index = 0;
		let timer = 0;
		let hovering = false;
		let stopped = false; // Pressed the pause button.

		const rotating = () => MOBILE.matches && ! stopped && ! REDUCED_MOTION.matches;

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
			dots.forEach( ( dot, i ) => {
				if ( i === index ) {
					dot.setAttribute( 'aria-current', 'true' );
				} else {
					dot.removeAttribute( 'aria-current' );
				}
			} );
			// Announce slide changes only while nothing rotates by itself.
			track.setAttribute( 'aria-live', rotating() ? 'off' : 'polite' );
			if ( pauseButton ) {
				pauseButton.hidden = ! mobile || REDUCED_MOTION.matches;
				pauseButton.classList.toggle( 'is-stopped', stopped );
				const label = stopped ? pauseButton.dataset.labelPlay : pauseButton.dataset.labelPause;
				if ( pauseLabel && label ) {
					pauseLabel.textContent = label;
				}
			}
		};

		const schedule = () => {
			window.clearTimeout( timer );
			if ( rotating() && ! hovering && ! document.hidden ) {
				timer = window.setTimeout( () => go( 1 ), INTERVAL );
			}
		};

		const show = ( i ) => {
			index = ( i + slides.length ) % slides.length;
			render();
			schedule(); // A manual move restarts the full interval.
		};
		const go = ( delta ) => show( index + delta );

		const prev = root.querySelector( '[data-carousel-prev]' );
		const next = root.querySelector( '[data-carousel-next]' );
		if ( prev ) {
			prev.addEventListener( 'click', () => go( -1 ) );
		}
		if ( next ) {
			next.addEventListener( 'click', () => go( 1 ) );
		}
		dots.forEach( ( dot ) => {
			dot.addEventListener( 'click', () => show( Number( dot.dataset.carouselDot ) ) );
		} );
		if ( pauseButton ) {
			pauseButton.addEventListener( 'click', () => {
				stopped = ! stopped;
				render();
				schedule();
			} );
		}

		// Pause while the visitor is interacting.
		const hover = ( state ) => () => {
			hovering = state;
			schedule();
		};
		root.addEventListener( 'mouseenter', hover( true ) );
		root.addEventListener( 'mouseleave', hover( false ) );
		root.addEventListener( 'focusin', hover( true ) );
		root.addEventListener( 'focusout', hover( false ) );
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

	/* ── Prev/next arrows that cycle through pre-rendered pages ────────── */

	function initPager( pager ) {
		const view = pager.querySelector( '[data-pager-view]' );
		const prev = pager.querySelector( '[data-pager-prev]' );
		const next = pager.querySelector( '[data-pager-next]' );
		const count = pager.querySelector( '[data-pager-count]' );
		const status = pager.querySelector( '[data-pager-status]' );
		const templates = Array.from( pager.querySelectorAll( 'template[data-pager-page]' ) );
		if ( ! view || ! next || ! templates.length ) {
			return;
		}

		const pages = [ Array.from( view.childNodes ) ].concat(
			templates.map( ( template ) => Array.from( template.content.cloneNode( true ).childNodes ) )
		);
		let index = 0;

		const go = ( delta ) => {
			index = ( index + delta + pages.length ) % pages.length;
			view.replaceChildren( ...pages[ index ] );
			if ( count ) {
				count.textContent = `${ index + 1 } / ${ pages.length }`;
			}
			if ( status && pager.dataset.pageLabel ) {
				status.textContent = format( pager.dataset.pageLabel, index + 1, pages.length );
			}
		};

		next.addEventListener( 'click', () => go( 1 ) );
		if ( prev ) {
			prev.addEventListener( 'click', () => go( -1 ) );
		}
	}

	/* ── Feeds: list/grid switch + infinite scroll over rel=next pages ──── */

	const VIEW_KEY = 'sbekFeedView';
	const AUTO_PAGES = 2; // Then a "Load more" button, so the footer stays reachable.

	function storedView() {
		try {
			return window.localStorage.getItem( VIEW_KEY );
		} catch ( e ) {
			return null;
		}
	}

	function initFeed( feed ) {
		// View switch. The <html> class is already set by the inline head script.
		const buttons = feed.querySelectorAll( '.view-switch__btn' );
		const applyView = ( view ) => {
			ROOT.classList.toggle( 'sbek-grid', 'grid' === view );
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
		const moreButton = feed.querySelector( '[data-feed-more]' );
		const end = feed.querySelector( '[data-feed-end]' );
		const status = feed.querySelector( '[data-feed-status]' );
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
		let loaded = 0;
		feed.classList.add( 'is-infinite' );
		loader.hidden = false;

		const finish = ( failed ) => {
			observer.disconnect();
			loader.hidden = true;
			if ( moreButton ) {
				moreButton.hidden = true;
			}
			if ( failed ) {
				feed.classList.remove( 'is-infinite' ); // Fall back to plain pagination.
			} else if ( end && loaded ) {
				end.hidden = false;
			}
		};

		const loadMore = async () => {
			if ( loading || ! nextUrl ) {
				return;
			}
			loading = true;
			loader.hidden = false;
			if ( moreButton ) {
				moreButton.disabled = true;
			}
			try {
				const url = nextUrl;
				const response = await fetch( url, { credentials: 'same-origin', headers: { Accept: 'text/html' } } );
				if ( ! response.ok ) {
					throw new Error( `HTTP ${ response.status }` );
				}
				const doc = new DOMParser().parseFromString( await response.text(), 'text/html' );
				const items = doc.querySelectorAll( '[data-feed-list] [data-feed-item]' );
				items.forEach( ( item ) => list.appendChild( document.importNode( item, true ) ) );
				loaded++;

				// Keep the no-JS pagination in sync with what's loaded.
				const freshPagination = doc.querySelector( '[data-feed-pagination]' );
				if ( freshPagination ) {
					pagination.replaceChildren( ...Array.from( freshPagination.childNodes ).map( ( node ) => document.importNode( node, true ) ) );
				}

				// The address follows the reading position, so a reload or a
				// shared link lands on the page the visitor had reached.
				if ( window.history.replaceState ) {
					window.history.replaceState( window.history.state, '', url );
				}
				if ( status && status.dataset.loaded ) {
					status.textContent = format( status.dataset.loaded, items.length );
				}

				nextUrl = items.length ? nextUrlFrom( doc ) : null;
				if ( ! nextUrl ) {
					finish( false );
				} else if ( loaded >= AUTO_PAGES && moreButton ) {
					observer.disconnect();
					loader.hidden = true;
					moreButton.hidden = false;
				}
			} catch ( error ) {
				finish( true );
			} finally {
				loading = false;
				if ( moreButton ) {
					moreButton.disabled = false;
				}
			}
		};

		const observer = new IntersectionObserver( ( entries ) => {
			if ( entries.some( ( entry ) => entry.isIntersecting ) ) {
				loadMore();
			}
		}, { rootMargin: '400px 0px' } );
		observer.observe( loader );

		if ( moreButton ) {
			moreButton.addEventListener( 'click', loadMore );
		}
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
	document.querySelectorAll( '[data-sbek-ticker-pause]' ).forEach( initTicker );
	document.querySelectorAll( '[data-sbek-carousel]' ).forEach( initCarousel );
	document.querySelectorAll( '[data-sbek-swipe]' ).forEach( initSwipeRow );
	document.querySelectorAll( '[data-sbek-pager]' ).forEach( initPager );
	document.querySelectorAll( '[data-sbek-feed]' ).forEach( initFeed );
	document.querySelectorAll( '[data-sbek-zwsp-toggle]' ).forEach( initWordBreakToggle );
}() );
