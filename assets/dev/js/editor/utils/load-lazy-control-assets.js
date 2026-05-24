const loadedKeys = new Set();
const inflight = new Map();

function isBundleAlreadySatisfied( bundleId ) {
	switch ( bundleId ) {
		case 'flatpickr':
			return 'function' === typeof jQuery?.fn?.flatpickr;
		case 'nouislider':
			return 'undefined' !== typeof window.noUiSlider;
		case 'pickr':
			return 'undefined' !== typeof window.Pickr;
		case 'ace':
			return 'undefined' !== typeof window.ace;
		default:
			return false;
	}
}

function loadStyle( href ) {
	const key = 'style:' + href;

	if ( loadedKeys.has( key ) ) {
		return Promise.resolve();
	}

	return new Promise( ( resolve, reject ) => {
		const id = 'elementor-lazy-style-' + href.replace( /[^a-z0-9]+/gi, '-' ).slice( 0, 120 );

		if ( document.getElementById( id ) ) {
			loadedKeys.add( key );
			resolve();
			return;
		}

		const link = document.createElement( 'link' );

		link.id = id;
		link.rel = 'stylesheet';
		link.href = href;
		link.onload = () => {
			loadedKeys.add( key );
			resolve();
		};
		link.onerror = () => reject( new Error( 'Failed to load stylesheet: ' + href ) );

		document.head.appendChild( link );
	} );
}

function loadScript( src ) {
	const key = 'script:' + src;

	if ( loadedKeys.has( key ) ) {
		return Promise.resolve();
	}

	return new Promise( ( resolve, reject ) => {
		const el = document.createElement( 'script' );

		el.src = src;
		el.async = false;
		el.onload = () => {
			loadedKeys.add( key );
			resolve();
		};
		el.onerror = () => reject( new Error( 'Failed to load script: ' + src ) );

		document.head.appendChild( el );
	} );
}

export function ensureLazyControlAssets( bundleId ) {
	if ( isBundleAlreadySatisfied( bundleId ) ) {
		return Promise.resolve();
	}

	if ( inflight.has( bundleId ) ) {
		return inflight.get( bundleId );
	}

	const bundle = window.elementor?.config?.lazy_control_assets?.[ bundleId ];

	const promise = ( async () => {
		if ( ! bundle ) {
			throw new Error( 'Missing elementor.config.lazy_control_assets entry: ' + bundleId );
		}

		const scripts = bundle.scripts || [];
		const styles = bundle.styles || [];

		if ( ! scripts.length && ! styles.length ) {
			throw new Error( 'Lazy control asset URLs are empty for: ' + bundleId );
		}

		await Promise.all( styles.map( ( href ) => loadStyle( href ) ) );

		for ( const src of scripts ) {
			await loadScript( src );
		}
	} )().catch( ( error ) => {
		inflight.delete( bundleId );
		throw error;
	} );

	inflight.set( bundleId, promise );

	return promise;
}

export const ensureAceLoaded = () => ensureLazyControlAssets( 'ace' );
export const ensureFlatpickrLoaded = () => ensureLazyControlAssets( 'flatpickr' );
export const ensureNouisliderLoaded = () => ensureLazyControlAssets( 'nouislider' );
export const ensurePickrLoaded = () => ensureLazyControlAssets( 'pickr' );
