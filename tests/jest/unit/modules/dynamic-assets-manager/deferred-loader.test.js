import { createDeferredLoader } from 'elementor/modules/dynamic-assets-manager/assets/js/deferred-loader';

jest.mock( 'elementor/modules/dynamic-assets-manager/assets/js/dom-injector', () => ( {
	inject: jest.fn(),
} ) );

jest.mock( 'elementor/modules/dynamic-assets-manager/assets/js/dependency-resolver', () => ( {
	resolve: jest.fn( ( handles ) => handles ),
} ) );

import { inject } from 'elementor/modules/dynamic-assets-manager/assets/js/dom-injector';
import { resolve } from 'elementor/modules/dynamic-assets-manager/assets/js/dependency-resolver';

const makeEntry = ( uri, type = 'script', deps = [] ) => ( {
	uri,
	type,
	deps,
	intent: 'enqueue/defer',
	deferTrigger: 'document_ready',
} );

const ASSET_MAP = {
	react: makeEntry( 'https://cdn.example.com/react.min.js' ),
	'react-dom': makeEntry( 'https://cdn.example.com/react-dom.min.js', 'script', [ 'react' ] ),
	e_lazy_video_player: makeEntry( 'https://cdn.example.com/video-player.js', 'script', [ 'react-dom' ] ),
	e_lazy_image_slider: makeEntry( 'https://cdn.example.com/image-slider.js', 'script', [ 'react-dom' ] ),
	e_lazy_image_slider_css: makeEntry( 'https://cdn.example.com/image-slider.css', 'style' ),
	e_lazy_fancy_popover: makeEntry( 'https://cdn.example.com/fancy-popover.js', 'script', [ 'react-dom' ] ),
};

describe( 'deferred-loader', () => {
	beforeEach( () => {
		inject.mockResolvedValue( undefined );
		resolve.mockImplementation( ( handles ) => handles );
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	describe( 'load()', () => {
		it( 'should resolve full dependency chain via resolver before injecting', async () => {
			// Arrange — requesting the video player; resolver expands to react → react-dom → video-player
			const loader = createDeferredLoader();
			resolve.mockReturnValue( [ 'react', 'react-dom', 'e_lazy_video_player' ] );

			// Act
			await loader.load( [ 'e_lazy_video_player' ], ASSET_MAP );

			// Assert
			expect( resolve ).toHaveBeenCalledWith( [ 'e_lazy_video_player' ], ASSET_MAP );
			expect( inject ).toHaveBeenCalledWith( 'react', ASSET_MAP.react );
			expect( inject ).toHaveBeenCalledWith( 'react-dom', ASSET_MAP[ 'react-dom' ] );
			expect( inject ).toHaveBeenCalledWith( 'e_lazy_video_player', ASSET_MAP.e_lazy_video_player );
		} );

		it( 'should inject handles in the order returned by resolver (deps before dependents)', async () => {
			// Arrange — resolver returns deps-first order for image-slider stack
			const loader = createDeferredLoader();
			const callOrder = [];

			resolve.mockReturnValue( [ 'react', 'react-dom', 'e_lazy_image_slider' ] );
			inject.mockImplementation( ( handle ) => {
				callOrder.push( handle );
				return Promise.resolve();
			} );

			// Act
			await loader.load( [ 'e_lazy_image_slider' ], ASSET_MAP );

			// Assert
			expect( callOrder ).toEqual( [ 'react', 'react-dom', 'e_lazy_image_slider' ] );
		} );

		it( 'should skip already-loaded handles on repeated calls', async () => {
			// Arrange — image-slider is added to the page twice (two widget instances)
			const loader = createDeferredLoader();
			const assetMap = { e_lazy_image_slider: ASSET_MAP.e_lazy_image_slider };

			// Act
			await loader.load( [ 'e_lazy_image_slider' ], assetMap );
			await loader.load( [ 'e_lazy_image_slider' ], assetMap );

			// Assert — inject called once regardless of how many widget instances exist
			expect( inject ).toHaveBeenCalledTimes( 1 );
		} );

		it( 'should reuse in-flight promise when same handle is requested concurrently', async () => {
			// Arrange — two widgets dropped at the same time both need the image-slider bundle
			const loader = createDeferredLoader();
			const assetMap = { e_lazy_image_slider: ASSET_MAP.e_lazy_image_slider };

			let resolveInject;
			inject.mockReturnValue( new Promise( ( res ) => {
				resolveInject = res;
			} ) );

			// Act
			const p1 = loader.load( [ 'e_lazy_image_slider' ], assetMap );
			const p2 = loader.load( [ 'e_lazy_image_slider' ], assetMap );

			resolveInject();
			await Promise.all( [ p1, p2 ] );

			// Assert — network request made only once even though two loads were triggered
			expect( inject ).toHaveBeenCalledTimes( 1 );
		} );

		it( 'should reject when a handle is missing from the asset map', async () => {
			// Arrange — fancy-popover was never registered in the dynamic assets manager
			const loader = createDeferredLoader();

			// Act & Assert
			await expect( loader.load( [ 'e_lazy_fancy_popover' ], {} ) ).rejects.toThrow( 'e_lazy_fancy_popover' );
		} );

		it( 'should propagate injection errors and allow retry on the next call', async () => {
			// Arrange — CDN returns 404 for image-slider on first attempt
			const loader = createDeferredLoader();
			const assetMap = { e_lazy_image_slider: ASSET_MAP.e_lazy_image_slider };
			inject.mockRejectedValue( new Error( 'Failed to load script: e_lazy_image_slider' ) );

			// Act & Assert — first call rejects
			await expect( loader.load( [ 'e_lazy_image_slider' ], assetMap ) ).rejects.toThrow( 'Failed to load script' );

			// Arrange — CDN recovers; verify retry is not stuck in pending
			inject.mockResolvedValue( undefined );
			await expect( loader.load( [ 'e_lazy_image_slider' ], assetMap ) ).resolves.toBeUndefined();
		} );
	} );
} );
