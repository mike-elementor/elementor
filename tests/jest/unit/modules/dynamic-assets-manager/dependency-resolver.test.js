import { resolve } from 'elementor/modules/dynamic-assets-manager/assets/js/dependency-resolver';

const makeEntry = ( deps = [], type = 'script' ) => ( {
	uri: 'https://cdn.example.com/asset.js',
	type,
	deps,
} );

describe( 'dependency-resolver', () => {
	describe( 'resolve()', () => {
		it( 'should return handles in topological order (dep before dependent)', () => {
			// Arrange — image-slider requires react-dom, which requires react
			const assetMap = {
				react: makeEntry( [] ),
				'react-dom': makeEntry( [ 'react' ] ),
				e_lazy_slider: makeEntry( [ 'react-dom' ] ),
			};

			// Act
			const result = resolve( [ 'e_lazy_slider' ], assetMap );

			// Assert
			expect( result.indexOf( 'react' ) ).toBeLessThan( result.indexOf( 'react-dom' ) );
			expect( result.indexOf( 'react-dom' ) ).toBeLessThan( result.indexOf( 'e_lazy_slider' ) );
		} );

		it( 'should expand transitive deps not in the initial handle list', () => {
			// Arrange — requesting only the widget; react and react-dom must be pulled in automatically
			const assetMap = {
				react: makeEntry( [] ),
				'react-dom': makeEntry( [ 'react' ] ),
				e_lazy_video_player: makeEntry( [ 'react-dom' ] ),
			};

			// Act
			const result = resolve( [ 'e_lazy_video_player' ], assetMap );

			// Assert
			expect( result ).toContain( 'react' );
			expect( result ).toContain( 'react-dom' );
			expect( result ).toContain( 'e_lazy_video_player' );
		} );

		it( 'should not include deps that are absent from the asset map', () => {
			// Arrange — react-query is a server-side dep already loaded; not present in client payload
			const assetMap = {
				e_lazy_data_table: makeEntry( [ 'react-query' ] ),
			};

			// Act
			const result = resolve( [ 'e_lazy_data_table' ], assetMap );

			// Assert
			expect( result ).toContain( 'e_lazy_data_table' );
			expect( result ).not.toContain( 'react-query' );
		} );

		it( 'should handle multiple root handles with shared deps correctly', () => {
			// Arrange — two widgets both depend on react; react should appear once and load first
			const assetMap = {
				react: makeEntry( [] ),
				e_lazy_image_gallery: makeEntry( [ 'react' ] ),
				e_lazy_fancy_popover: makeEntry( [ 'react' ] ),
			};

			// Act
			const result = resolve( [ 'e_lazy_image_gallery', 'e_lazy_fancy_popover' ], assetMap );

			// Assert
			expect( result ).toContain( 'react' );
			expect( result ).toContain( 'e_lazy_image_gallery' );
			expect( result ).toContain( 'e_lazy_fancy_popover' );
			expect( result.indexOf( 'react' ) ).toBeLessThan( result.indexOf( 'e_lazy_image_gallery' ) );
			expect( result.indexOf( 'react' ) ).toBeLessThan( result.indexOf( 'e_lazy_fancy_popover' ) );
			expect( result.filter( ( h ) => 'react' === h ) ).toHaveLength( 1 );
		} );

		it( 'should return handles in safe order when a cycle is detected', () => {
			// Arrange — pathological circular dep between two third-party libs
			const assetMap = {
				'lib-a': makeEntry( [ 'lib-b' ] ),
				'lib-b': makeEntry( [ 'lib-a' ] ),
			};

			// Act
			const result = resolve( [ 'lib-a', 'lib-b' ], assetMap );

			// Assert — both are still returned, no infinite loop
			expect( result ).toContain( 'lib-a' );
			expect( result ).toContain( 'lib-b' );
			expect( result ).toHaveLength( 2 );
		} );

		it( 'should return empty array for empty handles', () => {
			// Arrange
			const assetMap = { react: makeEntry( [] ) };

			// Act
			const result = resolve( [], assetMap );

			// Assert
			expect( result ).toEqual( [] );
		} );

		it( 'should include initial handle even when not in asset map (dep expansion is skipped)', () => {
			// Arrange — jquery is enqueued server-side and absent from the deferred client payload
			const result = resolve( [ 'jquery' ], {} );

			// Assert — handle passes through; loader will handle the missing entry
			expect( result ).toEqual( [ 'jquery' ] );
		} );
	} );
} );
