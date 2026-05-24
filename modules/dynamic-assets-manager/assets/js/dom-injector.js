const MARKER_ATTR = 'data-e-lazy-handle';

export function inject( handle, entry ) {
	if ( isAlreadyInDOM( handle ) ) {
		return Promise.resolve();
	}

	if ( 'style' === entry.type ) {
		return injectStyle( handle, entry );
	}

	return injectScript( handle, entry );
}

function isAlreadyInDOM( handle ) {
	return !! document.querySelector( `[${ MARKER_ATTR }="${ handle }"]` );
}

function injectScript( handle, entry ) {
	return new Promise( ( resolve, reject ) => {
		const script = document.createElement( 'script' );

		script.src = entry.uri;
		script.setAttribute( MARKER_ATTR, handle );

		script.addEventListener( 'load', () => resolve() );
		script.addEventListener( 'error', () => reject( new Error( `Failed to load script: ${ handle }` ) ) );

		document.head.appendChild( script );
	} );
}

function injectStyle( handle, entry ) {
	return new Promise( ( resolve, reject ) => {
		const link = document.createElement( 'link' );

		link.rel = 'stylesheet';
		link.href = entry.uri;
		link.setAttribute( MARKER_ATTR, handle );

		link.addEventListener( 'load', () => resolve() );
		link.addEventListener( 'error', () => reject( new Error( `Failed to load style: ${ handle }` ) ) );

		document.head.appendChild( link );
	} );
}
