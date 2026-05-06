import { resolve } from './dependency-resolver';
import { inject } from './dom-injector';

export function createDeferredLoader() {
	const loaded = new Set();
	const pending = new Map();

	function loadOne( handle, assetMap ) {
		if ( loaded.has( handle ) ) {
			return Promise.resolve();
		}

		if ( pending.has( handle ) ) {
			return pending.get( handle );
		}

		const entry = assetMap[ handle ];

		if ( ! entry ) {
			return Promise.reject( new Error( `Handle not found in asset map: ${ handle }` ) );
		}

		const promise = inject( handle, entry )
			.then( () => {
				loaded.add( handle );
				pending.delete( handle );
			} )
			.catch( ( err ) => {
				pending.delete( handle );
				return Promise.reject( err );
			} );

		pending.set( handle, promise );

		return promise;
	}

	async function load( handles, assetMap ) {
		const resolved = resolve( handles, assetMap );

		for ( const handle of resolved ) {
			await loadOne( handle, assetMap );
		}
	}

	return { load };
}
