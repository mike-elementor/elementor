export function resolve( handles, assetMap ) {
	const expanded = expandTransitiveDeps( handles, assetMap );
	return topologicalSort( expanded, assetMap );
}

function expandTransitiveDeps( initialHandles, assetMap ) {
	const collected = new Set();
	const queue = [ ...initialHandles ];

	while ( queue.length ) {
		const handle = queue.shift();

		if ( collected.has( handle ) ) {
			continue;
		}

		collected.add( handle );

		const deps = assetMap[ handle ]?.deps ?? [];

		for ( const dep of deps ) {
			if ( assetMap[ dep ] && ! collected.has( dep ) ) {
				queue.push( dep );
			}
		}
	}

	return [ ...collected ];
}

function topologicalSort( handles, assetMap ) {
	const handleSet = new Set( handles );
	const inDegree = new Map();
	const dependents = new Map();

	for ( const handle of handles ) {
		if ( ! inDegree.has( handle ) ) {
			inDegree.set( handle, 0 );
		}

		if ( ! dependents.has( handle ) ) {
			dependents.set( handle, [] );
		}

		const deps = assetMap[ handle ]?.deps ?? [];

		for ( const dep of deps ) {
			if ( ! handleSet.has( dep ) ) {
				continue;
			}

			if ( ! dependents.has( dep ) ) {
				dependents.set( dep, [] );
			}

			dependents.get( dep ).push( handle );
			inDegree.set( handle, ( inDegree.get( handle ) ?? 0 ) + 1 );
		}
	}

	const queue = [];

	for ( const [ handle, degree ] of inDegree ) {
		if ( 0 === degree ) {
			queue.push( handle );
		}
	}

	const sorted = [];

	while ( queue.length ) {
		const handle = queue.shift();
		sorted.push( handle );

		for ( const dependent of ( dependents.get( handle ) ?? [] ) ) {
			const newDegree = inDegree.get( dependent ) - 1;
			inDegree.set( dependent, newDegree );

			if ( 0 === newDegree ) {
				queue.push( dependent );
			}
		}
	}

	if ( sorted.length < handles.length ) {
		const sortedSet = new Set( sorted );

		for ( const handle of handles ) {
			if ( ! sortedSet.has( handle ) ) {
				sorted.push( handle );
			}
		}
	}

	return sorted;
}
