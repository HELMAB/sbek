/**
 * Sbek Theme page: tabs, repeatable rows (phones / socials), media pickers,
 * category colors, the Design live preview, inline confirmations, demo
 * import and unsaved-changes tracking. Vanilla JS; wp.media comes from
 * wp_enqueue_media().
 *
 * Markup contract:
 *   [data-sbek-tabs] [data-sbek-tab="slug"]   tab links (href="#slug")
 *   [data-sbek-panel="slug"]                  tab panels
 *   [data-sbek-current-tab]                   hidden input: tab to return to after saving
 *   [data-sbek-save]                          save controls (hidden on the Tools tab)
 *   [data-sbek-add="group"] / [data-sbek-rows="group"] / #sbek-tmpl-group
 *   [data-sbek-remove] / [data-sbek-move="-1|1"] inside [data-sbek-row]
 *   [data-sbek-platform] + [data-sbek-social-icon] / #sbek-social-icons
 *   [data-sbek-media-field] with [data-sbek-media-input|preview|select|remove|warning]
 *   [data-sbek-term-color] with [data-sbek-term-color-auto|input|reset]
 */
( function () {
	'use strict';

	const form = document.querySelector( '[data-sbek-options-form]' );
	if ( ! form ) {
		return;
	}

	const l10n = window.sbekThemeOptions || {};

	/* ── Unsaved changes ──────────────────────────────────────────────── */

	// Compare the submittable form data with a snapshot taken on load, so the
	// hint disappears again when a change is undone. Demo-content controls
	// are actions, not settings.
	const serialize = () => {
		const parts = [];
		new FormData( form ).forEach( ( value, key ) => {
			if ( ! key.startsWith( 'sbek_demo' ) && 'sbek_tab' !== key ) {
				parts.push( key + '=' + value );
			}
		} );
		return parts.join( '&' );
	};

	const dirtyHint = form.querySelector( '[data-sbek-dirty-hint]' );
	let snapshot = null;
	let dirty = false;
	let submitting = false;

	const refreshDirty = () => {
		if ( null === snapshot ) {
			return;
		}
		dirty = serialize() !== snapshot;
		if ( dirtyHint ) {
			dirtyHint.hidden = ! dirty;
		}
	};

	form.addEventListener( 'input', refreshDirty );
	form.addEventListener( 'change', refreshDirty );

	// Any real submit (this form, or the separate Reset design form) leaves on purpose.
	document.addEventListener( 'submit', () => {
		submitting = true;
	} );

	window.addEventListener( 'beforeunload', ( event ) => {
		if ( dirty && ! submitting ) {
			event.preventDefault();
			event.returnValue = '';
		}
	} );

	/* ── Tabs ─────────────────────────────────────────────────────────── */

	const tabs = Array.from( form.querySelectorAll( '[data-sbek-tab]' ) );
	const panels = Array.from( form.querySelectorAll( '[data-sbek-panel]' ) );
	const saveControls = Array.from( form.querySelectorAll( '[data-sbek-save]' ) );
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

		// The Tools tab only has actions; nothing there is saved by the form.
		saveControls.forEach( ( control ) => {
			control.hidden = 'tools' === slug;
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

	// Drop one-shot notice flags (?updated=1, ?design_reset=1) so a reload
	// doesn't show "saved" again.
	const url = new URL( window.location.href );
	if ( url.searchParams.has( 'updated' ) || url.searchParams.has( 'design_reset' ) ) {
		url.searchParams.delete( 'updated' );
		url.searchParams.delete( 'design_reset' );
		history.replaceState( null, '', url.pathname + url.search + url.hash );
	}

	activate( window.location.hash.slice( 1 ), { updateHash: false } );

	// The inline boot rule kept other tabs hidden until now.
	const bootStyle = document.getElementById( 'sbek-tab-boot' );
	if ( bootStyle ) {
		bootStyle.remove();
	}

	// A field that fails browser validation inside a hidden tab can't be
	// focused, so the browser would block the submit silently — reveal it.
	form.addEventListener( 'invalid', ( event ) => {
		const panel = event.target.closest( '[data-sbek-panel]' );
		if ( panel && panel.hidden ) {
			activate( panel.dataset.sbekPanel );
		}
	}, true );

	/* ── Repeatable rows ──────────────────────────────────────────────── */

	// Keep indexes 0..n-1 in DOM order, so the saved order is what you see.
	function reindex( rows ) {
		const group = rows.dataset.sbekRows;
		const pattern = new RegExp( '\\[' + group + '\\]\\[[^\\]]+\\]' );
		rows.querySelectorAll( '[data-sbek-row]' ).forEach( ( row, index ) => {
			row.querySelectorAll( '[name]' ).forEach( ( input ) => {
				input.name = input.name.replace( pattern, '[' + group + '][' + index + ']' );
			} );
		} );
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
			const index = rows.querySelectorAll( '[data-sbek-row]' ).length;
			row.querySelectorAll( '[data-name]' ).forEach( ( input ) => {
				input.name = input.dataset.name.replace( '__i__', String( index ) );
				input.removeAttribute( 'data-name' );
			} );

			rows.appendChild( row );
			refreshDirty();
			const first = row.querySelector( 'input' );
			if ( first ) {
				first.focus();
			}
		} );
	} );

	form.addEventListener( 'click', ( event ) => {
		const remove = event.target.closest( '[data-sbek-remove]' );
		const move = event.target.closest( '[data-sbek-move]' );
		const row = ( remove || move ) && ( remove || move ).closest( '[data-sbek-row]' );
		if ( ! row ) {
			return;
		}
		const rows = row.parentElement;

		if ( remove ) {
			row.remove();
		} else {
			const up = '-1' === move.dataset.sbekMove;
			const sibling = up ? row.previousElementSibling : row.nextElementSibling;
			if ( ! sibling ) {
				return;
			}
			rows.insertBefore( up ? row : sibling, up ? sibling : row );
			move.focus(); // Moving the node drops focus in some browsers.
		}

		reindex( rows );
		refreshDirty();
	} );

	// Social rows: the brand icon follows the platform name as it is typed.
	const iconTemplate = document.getElementById( 'sbek-social-icons' );
	const aliases = l10n.socialIcons || {};

	form.addEventListener( 'input', ( event ) => {
		const platform = event.target.closest( '[data-sbek-platform]' );
		const row = platform && platform.closest( '[data-sbek-row]' );
		const slot = row && row.querySelector( '[data-sbek-social-icon]' );
		if ( ! slot || ! iconTemplate ) {
			return;
		}
		const name = aliases[ platform.value.trim().toLowerCase() ] || 'link';
		const icon = iconTemplate.content.querySelector( `[data-icon="${ name }"]` );
		if ( icon ) {
			slot.innerHTML = icon.innerHTML;
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

	// Categories without their own color follow the brand color.
	const termColors = Array.from( form.querySelectorAll( '[data-sbek-term-color]' ) );

	function applyColor( field, value ) {
		const variable = field.dataset.previewVar;
		setVar( variable, value );
		if ( '--p-brand' === variable ) {
			const isDefault = value.toLowerCase() === ( preview.dataset.defaultBrand || '' ).toLowerCase();
			setVar( '--p-brand-dark', isDefault ? preview.dataset.defaultBrandDark : darken( value, 0.15 ) );
			setVar( '--p-on-brand', readableOn( value ) );
			termColors.forEach( ( box ) => {
				box.dataset.brand = value;
				if ( 'auto' === box.dataset.state ) {
					box.querySelector( '[data-sbek-term-color-input]' ).value = value;
				}
			} );
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
			refreshDirty();
		} );
	} );

	termColors.forEach( ( box ) => {
		const auto = box.querySelector( '[data-sbek-term-color-auto]' );
		const input = box.querySelector( '[data-sbek-term-color-input]' );
		const reset = box.querySelector( '[data-sbek-term-color-reset]' );

		input.addEventListener( 'input', () => {
			auto.value = '0';
			box.dataset.state = 'custom';
		} );
		reset.addEventListener( 'click', () => {
			auto.value = '1';
			input.value = box.dataset.brand;
			box.dataset.state = 'auto';
			refreshDirty();
			input.focus();
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

	function setPreviewLogo( src ) {
		const box = form.querySelector( '[data-sbek-preview-logo]' );
		if ( ! box ) {
			return;
		}
		const img = box.querySelector( '[data-sbek-preview-logo-img]' );
		const text = box.querySelector( '[data-sbek-preview-logo-text]' );
		if ( src ) {
			img.src = src;
		} else {
			img.removeAttribute( 'src' );
		}
		img.hidden = ! src;
		text.hidden = Boolean( src );
		box.classList.toggle( 'has-image', Boolean( src ) );
	}

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
		const note = box.querySelector( '[data-sbek-confirm-dirty]' );
		const opening = Boolean( open );
		if ( note ) {
			note.hidden = ! dirty;
		}
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
		const image = field.querySelector( '[data-sbek-media-preview]' );
		const remove = field.querySelector( '[data-sbek-media-remove]' );
		const warning = field.querySelector( '[data-sbek-media-warning]' );
		const role = field.dataset.sbekMediaRole || '';
		let frame = null;

		const warn = ( text ) => {
			if ( warning ) {
				warning.textContent = text;
				warning.hidden = ! text;
			}
		};

		// Same rule as sbek_site_icon_problem(): square (±2px), at least 512px.
		const iconProblem = ( attachment ) => {
			const w = attachment.width || 0;
			const h = attachment.height || 0;
			if ( 'site_icon' !== role || ! w || ! h || ( Math.abs( w - h ) <= 2 && Math.min( w, h ) >= 512 ) ) {
				return '';
			}
			return ( l10n.iconSize || '' ).replace( '%1$d', w ).replace( '%2$d', h );
		};

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
					const problem = iconProblem( attachment );
					warn( problem );
					if ( problem ) {
						return; // Keep the current icon; the server would refuse it anyway.
					}

					const sizes = attachment.sizes || {};
					const thumb = sizes.medium || sizes.large || sizes.full || attachment;
					input.value = attachment.id;
					image.src = thumb.url || attachment.url;
					field.dataset.state = 'filled';
					if ( 'logo' === role ) {
						setPreviewLogo( image.src );
					}
					refreshDirty();
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
				image.removeAttribute( 'src' );
				field.dataset.state = 'empty';
				warn( '' );
				if ( 'logo' === role ) {
					setPreviewLogo( '' );
				}
				refreshDirty();
			} );
		}
	} );

	// Everything is wired: this is the "saved" state to compare against.
	snapshot = serialize();
}() );
