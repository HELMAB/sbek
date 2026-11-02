/**
 * Theme Option page: tabs, repeatable rows (phones / socials), media pickers
 * and an unsaved-changes hint. Vanilla JS; wp.media comes from wp_enqueue_media().
 *
 * Markup contract:
 *   [data-sbek-tabs] [data-sbek-tab="slug"]   tab links (href="#slug")
 *   [data-sbek-panel="slug"]                  tab panels
 *   [data-sbek-current-tab]                   hidden input: tab to return to after saving
 *   [data-sbek-add="group"] / [data-sbek-rows="group"] / #sbek-tmpl-group
 *   [data-sbek-remove] inside [data-sbek-row]
 *   [data-sbek-media-field] with [data-sbek-media-input|preview|select|remove]
 */
( function () {
	'use strict';

	const form = document.querySelector( '[data-sbek-options-form]' );
	if ( ! form ) {
		return;
	}

	const l10n = window.sbekThemeOptions || {};

	/* ── Tabs ─────────────────────────────────────────────────────────── */

	const tabs = Array.from( form.querySelectorAll( '[data-sbek-tab]' ) );
	const panels = Array.from( form.querySelectorAll( '[data-sbek-panel]' ) );
	const currentTab = form.querySelector( '[data-sbek-current-tab]' );
	const slugs = tabs.map( ( tab ) => tab.dataset.sbekTab );

	function activate( slug, { focus = false, updateHash = true } = {} ) {
		if ( ! slugs.includes( slug ) ) {
			slug = slugs[ 0 ];
		}

		tabs.forEach( ( tab ) => {
			const selected = tab.dataset.sbekTab === slug;
			tab.setAttribute( 'aria-selected', String( selected ) );
			tab.tabIndex = selected ? 0 : -1;
			tab.classList.toggle( 'is-active', selected );
			if ( selected && focus ) {
				tab.focus();
			}
		} );
		panels.forEach( ( panel ) => {
			panel.hidden = panel.dataset.sbekPanel !== slug;
		} );

		currentTab.value = slug;
		if ( updateHash ) {
			history.replaceState( null, '', '#' + slug );
		}
	}

	tabs.forEach( ( tab, index ) => {
		tab.addEventListener( 'click', ( event ) => {
			event.preventDefault();
			activate( tab.dataset.sbekTab );
		} );

		tab.addEventListener( 'keydown', ( event ) => {
			const keys = { ArrowDown: 1, ArrowRight: 1, ArrowUp: -1, ArrowLeft: -1 };
			let next = null;
			if ( event.key in keys ) {
				next = ( index + keys[ event.key ] + tabs.length ) % tabs.length;
			} else if ( 'Home' === event.key ) {
				next = 0;
			} else if ( 'End' === event.key ) {
				next = tabs.length - 1;
			}
			if ( null !== next ) {
				event.preventDefault();
				activate( tabs[ next ].dataset.sbekTab, { focus: true } );
			}
		} );
	} );

	activate( window.location.hash.slice( 1 ), { updateHash: false } );

	// A field that fails browser validation inside a hidden tab can't be
	// focused, so the browser would block the submit silently — reveal it.
	form.addEventListener( 'invalid', ( event ) => {
		const panel = event.target.closest( '[data-sbek-panel]' );
		if ( panel && panel.hidden ) {
			activate( panel.dataset.sbekPanel );
		}
	}, true );

	/* ── Unsaved-changes hint ─────────────────────────────────────────── */

	const dirtyHint = form.querySelector( '[data-sbek-dirty-hint]' );
	const markDirty = () => {
		if ( dirtyHint ) {
			dirtyHint.hidden = false;
		}
	};
	// Tools (demo content) controls are actions, not settings.
	const markDirtyFrom = ( event ) => {
		if ( ! event.target.closest( '[data-sbek-demo]' ) ) {
			markDirty();
		}
	};
	form.addEventListener( 'input', markDirtyFrom );
	form.addEventListener( 'change', markDirtyFrom );

	/* ── Repeatable rows ──────────────────────────────────────────────── */

	const counters = {};

	function nextIndex( group ) {
		if ( undefined === counters[ group ] ) {
			const rows = form.querySelector( `[data-sbek-rows="${ group }"]` );
			counters[ group ] = rows ? rows.querySelectorAll( '[data-sbek-row]' ).length : 0;
		}
		return counters[ group ]++;
	}

	form.querySelectorAll( '[data-sbek-add]' ).forEach( ( button ) => {
		button.addEventListener( 'click', () => {
			const group = button.dataset.sbekAdd;
			const template = document.getElementById( 'sbek-tmpl-' + group );
			const rows = form.querySelector( `[data-sbek-rows="${ group }"]` );
			if ( ! template || ! rows ) {
				return;
			}

			const row = template.content.firstElementChild.cloneNode( true );
			const index = nextIndex( group );
			row.querySelectorAll( '[data-name]' ).forEach( ( input ) => {
				input.name = input.dataset.name.replace( '__i__', String( index ) );
				input.removeAttribute( 'data-name' );
			} );

			rows.appendChild( row );
			markDirty();
			const first = row.querySelector( 'input' );
			if ( first ) {
				first.focus();
			}
		} );
	} );

	form.addEventListener( 'click', ( event ) => {
		const remove = event.target.closest( '[data-sbek-remove]' );
		const row = remove && remove.closest( '[data-sbek-row]' );
		if ( row ) {
			row.remove();
			markDirty();
		}
	} );

	/* ── Design: colors, fonts, live preview ───────────────────────────── */

	const preview = form.querySelector( '[data-sbek-preview]' );
	const HEX = /^#[0-9a-f]{6}$/i;

	const toRgb = ( hex ) => [ 1, 3, 5 ].map( ( i ) => parseInt( hex.slice( i, i + 2 ), 16 ) );
	const luminance = ( hex ) => {
		const [ r, g, b ] = toRgb( hex ).map( ( c ) => {
			c /= 255;
			return c <= 0.03928 ? c / 12.92 : Math.pow( ( c + 0.055 ) / 1.055, 2.4 );
		} );
		return 0.2126 * r + 0.7152 * g + 0.0722 * b;
	};
	// Same rules as sbek_readable_on() / sbek_mix_hex() in inc/design.php.
	const readableOn = ( hex ) => ( 1.05 / ( luminance( hex ) + 0.05 ) >= 3 ? '#ffffff' : '#111111' );
	const darken = ( hex, amount ) => '#' + toRgb( hex ).map( ( c ) => Math.round( c * ( 1 - amount ) ).toString( 16 ).padStart( 2, '0' ) ).join( '' );

	const setVar = ( name, value ) => preview && preview.style.setProperty( name, value );

	function applyColor( field, value ) {
		const variable = field.dataset.previewVar;
		setVar( variable, value );
		if ( '--p-brand' === variable ) {
			const isDefault = value.toLowerCase() === ( preview.dataset.defaultBrand || '' ).toLowerCase();
			setVar( '--p-brand-dark', isDefault ? preview.dataset.defaultBrandDark : darken( value, 0.15 ) );
			setVar( '--p-on-brand', readableOn( value ) );
		} else if ( '--p-top' === variable ) {
			setVar( '--p-on-top', readableOn( value ) );
		} else if ( '--p-footer' === variable ) {
			setVar( '--p-on-footer', '#ffffff' === readableOn( value ) ? '#abaaab' : '#555555' );
		}
	}

	form.querySelectorAll( '[data-sbek-color-field]' ).forEach( ( field ) => {
		const picker = field.querySelector( '[data-sbek-color]' );
		const hex = field.querySelector( '[data-sbek-color-hex]' );
		const reset = field.querySelector( '[data-sbek-color-reset]' );

		const set = ( value, from ) => {
			if ( ! HEX.test( value ) ) {
				return;
			}
			value = value.toLowerCase();
			if ( from !== picker ) {
				picker.value = value;
			}
			if ( from !== hex ) {
				hex.value = value;
			}
			applyColor( field, value );
		};

		picker.addEventListener( 'input', () => set( picker.value, picker ) );
		hex.addEventListener( 'input', () => set( hex.value.trim(), hex ) );
		hex.addEventListener( 'blur', () => {
			hex.value = picker.value; // Discard a half-typed value.
		} );
		reset.addEventListener( 'click', () => {
			set( picker.dataset.default, null );
			markDirty();
		} );
	} );

	const latinStack = () => {
		const select = form.querySelector( '[data-sbek-font="latin"]' );
		return select ? select.selectedOptions[ 0 ].dataset.css : 'Arial, sans-serif';
	};

	function applyFonts() {
		const body = form.querySelector( '[data-sbek-font="body"]' );
		const heading = form.querySelector( '[data-sbek-font="heading"]' );
		const latin = latinStack();
		if ( body ) {
			setVar( '--p-body', body.selectedOptions[ 0 ].dataset.css + ', ' + latin );
		}
		if ( heading ) {
			setVar( '--p-heading', heading.selectedOptions[ 0 ].dataset.css + ', ' + latin );
			setVar( '--p-heading-weight', heading.selectedOptions[ 0 ].dataset.weight || '400' );
		}
	}

	form.querySelectorAll( '[data-sbek-font]' ).forEach( ( select ) => select.addEventListener( 'change', applyFonts ) );

	/* ── Inline confirmations (no blocking browser dialogs) ───────────── */

	form.addEventListener( 'click', ( event ) => {
		const open = event.target.closest( '[data-sbek-confirm-open]' );
		const cancel = event.target.closest( '[data-sbek-confirm-cancel]' );
		const box = ( open || cancel ) && ( open || cancel ).closest( '[data-sbek-confirm]' );
		if ( ! box ) {
			return;
		}
		const trigger = box.querySelector( '[data-sbek-confirm-open]' );
		const prompt = box.querySelector( '[data-sbek-confirm-prompt]' );
		const opening = Boolean( open );
		trigger.hidden = opening;
		prompt.hidden = ! opening;
		( opening ? prompt.querySelector( 'button' ) : trigger ).focus();
	} );

	/* ── Demo content: batched import / removal ───────────────────────── */

	const demo = form.querySelector( '[data-sbek-demo]' );

	if ( demo ) {
		const progress = demo.querySelector( '[data-sbek-demo-progress]' );
		const fill = demo.querySelector( '[data-sbek-demo-fill]' );
		const message = demo.querySelector( '[data-sbek-demo-message]' );
		const siteSetup = demo.querySelector( 'input[type="checkbox"][name="sbek_demo_site_setup"]' );

		const show = ( percent, text ) => {
			progress.hidden = false;
			if ( null !== percent ) {
				fill.style.width = percent + '%';
			}
			message.textContent = text;
		};

		const setBusy = ( busy ) => {
			demo.classList.toggle( 'is-busy', busy );
			demo.querySelectorAll( 'button, input' ).forEach( ( el ) => {
				el.disabled = busy;
			} );
		};

		const call = async ( data ) => {
			const body = new FormData();
			body.append( 'action', 'sbek_demo' );
			body.append( '_ajax_nonce', l10n.demoNonce );
			Object.keys( data ).forEach( ( key ) => body.append( key, data[ key ] ) );

			const response = await fetch( l10n.ajaxUrl, { method: 'POST', credentials: 'same-origin', body } );
			const json = await response.json().catch( () => null );
			if ( ! json || ! json.success ) {
				throw new Error( ( json && json.data && json.data.message ) || l10n.demoFailed );
			}
			return json.data;
		};

		const run = async ( kind ) => {
			setBusy( true );
			demo.classList.remove( 'is-error' );

			try {
				let step = 'continue' === kind
					? { done: false, progress: 0, message: '…' }
					: await call( { task: 'start', type: kind, site_setup: siteSetup && siteSetup.checked ? '1' : '' } );
				show( step.progress, step.message );

				while ( ! step.done ) {
					step = await call( { task: 'next' } );
					show( step.progress, step.message );
				}

				show( 100, step.message + ' ' + ( l10n.demoDone || '' ) );
				window.setTimeout( () => window.location.reload(), 1200 );
			} catch ( error ) {
				show( null, error.message );
				demo.classList.add( 'is-error' );
				setBusy( false );

				// The job is kept server-side: offer to continue where it stopped.
				const primary = demo.querySelector( '[data-sbek-demo-run="import"], [data-sbek-demo-run="continue"]' );
				if ( primary && 'reset' !== kind ) {
					primary.dataset.sbekDemoRun = 'continue';
					primary.textContent = primary.dataset.continueLabel || primary.textContent;
				}
			}
		};

		demo.addEventListener( 'click', ( event ) => {
			const button = event.target.closest( '[data-sbek-demo-run]' );
			if ( button ) {
				run( button.dataset.sbekDemoRun );
			}
		} );
	}

	/* ── Media pickers ────────────────────────────────────────────────── */

	form.querySelectorAll( '[data-sbek-media-field]' ).forEach( ( field ) => {
		const input = field.querySelector( '[data-sbek-media-input]' );
		const preview = field.querySelector( '[data-sbek-media-preview]' );
		const remove = field.querySelector( '[data-sbek-media-remove]' );
		let frame = null;

		const open = () => {
			if ( ! window.wp || ! window.wp.media ) {
				return;
			}
			if ( ! frame ) {
				frame = window.wp.media( {
					title: l10n.mediaTitle || 'Select image',
					button: { text: l10n.mediaButton || 'Use this image' },
					library: { type: 'image' },
					multiple: false,
				} );
				frame.on( 'select', () => {
					const attachment = frame.state().get( 'selection' ).first().toJSON();
					const sizes = attachment.sizes || {};
					const thumb = sizes.medium || sizes.large || sizes.full || attachment;
					input.value = attachment.id;
					preview.src = thumb.url || attachment.url;
					field.dataset.state = 'filled';
					markDirty();
				} );
				frame.on( 'open', () => {
					if ( input.value ) {
						const existing = window.wp.media.attachment( input.value );
						existing.fetch();
						frame.state().get( 'selection' ).reset( [ existing ] );
					}
				} );
			}
			frame.open();
		};

		field.querySelectorAll( '[data-sbek-media-select]' ).forEach( ( trigger ) => trigger.addEventListener( 'click', open ) );

		if ( remove ) {
			remove.addEventListener( 'click', () => {
				input.value = '';
				preview.removeAttribute( 'src' );
				field.dataset.state = 'empty';
				markDirty();
			} );
		}
	} );
}() );
