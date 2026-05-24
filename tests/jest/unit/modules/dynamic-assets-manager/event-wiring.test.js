jest.mock( 'elementor/modules/dynamic-assets-manager/assets/js/deferred-loader', () => ( {
	createDeferredLoader: jest.fn(),
} ) );

const makePayload = ( overrides = {} ) => ( {
	// Shared runtime deps — loaded at document_ready so widgets can use them immediately
	react: {
		uri: 'https://cdn.example.com/react.min.js',
		type: 'script',
		deps: [],
		intent: 'enqueue/defer',
		deferTrigger: 'document_ready',
	},
	'react-dom': {
		uri: 'https://cdn.example.com/react-dom.min.js',
		type: 'script',
		deps: [ 'react' ],
		intent: 'enqueue/defer',
		deferTrigger: 'document_ready',
	},
	// Always-present widget loaded at document_ready (e.g. popover used site-wide)
	e_lazy_fancy_popover: {
		uri: 'https://cdn.example.com/fancy-popover.js',
		type: 'script',
		deps: [ 'react-dom' ],
		intent: 'enqueue/defer',
		deferTrigger: 'document_ready',
	},
	e_lazy_fancy_popover_css: {
		uri: 'https://cdn.example.com/fancy-popover.css',
		type: 'style',
		deps: [],
		intent: 'enqueue/defer',
		deferTrigger: 'document_ready',
	},
	// On-demand widget — loaded only when the user drops it into the canvas
	e_lazy_image_slider: {
		uri: 'https://cdn.example.com/image-slider.js',
		type: 'script',
		deps: [ 'react-dom' ],
		intent: 'enqueue/defer',
		deferTrigger: 'widget_insert',
	},
	e_lazy_image_slider_css: {
		uri: 'https://cdn.example.com/image-slider.css',
		type: 'style',
		deps: [],
		intent: 'enqueue/defer',
		deferTrigger: 'widget_insert',
	},
	...overrides,
} );

const DOCUMENT_READY_HANDLES = [ 'react', 'react-dom', 'e_lazy_fancy_popover', 'e_lazy_fancy_popover_css' ];
const WIDGET_INSERT_HANDLES = [ 'e_lazy_image_slider', 'e_lazy_image_slider_css' ];

describe( 'event-wiring', () => {
	let mockLoader;

	beforeEach( () => {
		mockLoader = { load: jest.fn().mockResolvedValue( undefined ) };

		global.$e = {
			modules: {
				hookData: {
					After: class {
						register() {}
					},
				},
			},
			hooks: {
				registerDataAfter: jest.fn(),
			},
		};
	} );

	afterEach( () => {
		delete window.elementorDynamicAssets;
		delete global.$e;
		jest.resetModules();
		jest.clearAllMocks();
	} );

	describe( 'init()', () => {
		it( 'should do nothing when window.elementorDynamicAssets is absent', async () => {
			// Arrange — payload not injected by PHP (e.g. experiment disabled mid-flight)
			delete window.elementorDynamicAssets;

			// Act
			const { init } = await import( 'elementor/modules/dynamic-assets-manager/assets/js/event-wiring' );
			init( mockLoader );

			window.dispatchEvent( new CustomEvent( 'elementor/initialized' ) );

			// Assert
			expect( mockLoader.load ).not.toHaveBeenCalled();
		} );

		it( 'should load all document_ready handles when elementor/initialized fires', async () => {
			// Arrange — react, react-dom, fancy-popover (JS + CSS) are all document_ready
			window.elementorDynamicAssets = makePayload();

			const { init } = await import( 'elementor/modules/dynamic-assets-manager/assets/js/event-wiring' );
			init( mockLoader );

			// Act
			window.dispatchEvent( new CustomEvent( 'elementor/initialized' ) );

			// Assert
			expect( mockLoader.load ).toHaveBeenCalledWith(
				expect.arrayContaining( DOCUMENT_READY_HANDLES ),
				window.elementorDynamicAssets,
			);
			expect( mockLoader.load.mock.calls[ 0 ][ 0 ] ).toHaveLength( DOCUMENT_READY_HANDLES.length );
		} );

		it( 'should load document_ready handles only once even if event fires multiple times', async () => {
			// Arrange — editor can emit elementor/initialized more than once in some flows
			window.elementorDynamicAssets = makePayload();

			const { init } = await import( 'elementor/modules/dynamic-assets-manager/assets/js/event-wiring' );
			init( mockLoader );

			// Act
			window.dispatchEvent( new CustomEvent( 'elementor/initialized' ) );
			window.dispatchEvent( new CustomEvent( 'elementor/initialized' ) );

			// Assert — react/react-dom/fancy-popover loaded exactly once
			const docReadyCalls = mockLoader.load.mock.calls.filter(
				( [ handles ] ) => handles.includes( 'react' ),
			);
			expect( docReadyCalls ).toHaveLength( 1 );
		} );

		it( 'should register widget_insert hook via $e.hooks when elementor/initialized fires', async () => {
			// Arrange
			window.elementorDynamicAssets = makePayload();
			const registerSpy = jest.spyOn( global.$e.hooks, 'registerDataAfter' );

			global.$e.modules.hookData.After = class {
				register() {
					$e.hooks.registerDataAfter( this ); // eslint-disable-line no-undef
				}
			};

			const { init } = await import( 'elementor/modules/dynamic-assets-manager/assets/js/event-wiring' );
			init( mockLoader );

			// Act
			window.dispatchEvent( new CustomEvent( 'elementor/initialized' ) );

			// Assert — a single hook registered for document/elements/create
			expect( registerSpy ).toHaveBeenCalledTimes( 1 );
			const hookInstance = registerSpy.mock.calls[ 0 ][ 0 ];
			expect( hookInstance.getCommand() ).toBe( 'document/elements/create' );
			expect( hookInstance.getId() ).toBe( 'e-dynamic-assets-widget-insert' );
		} );

		it( 'should call loader.load with widget_insert handles when user drops an image-slider', async () => {
			// Arrange — image-slider and its CSS are widget_insert handles
			window.elementorDynamicAssets = makePayload();
			let capturedHook = null;

			global.$e.modules.hookData.After = class {
				register() {
					capturedHook = this;
				}
			};

			const { init } = await import( 'elementor/modules/dynamic-assets-manager/assets/js/event-wiring' );
			init( mockLoader );

			window.dispatchEvent( new CustomEvent( 'elementor/initialized' ) );

			// Act — simulate dropping an image-slider widget into the canvas
			capturedHook.apply( { model: { widgetType: 'image-slider' } } );

			// Assert
			expect( mockLoader.load ).toHaveBeenCalledWith(
				expect.arrayContaining( WIDGET_INSERT_HANDLES ),
				window.elementorDynamicAssets,
			);
			expect( mockLoader.load.mock.calls.find(
				( [ handles ] ) => handles.includes( 'e_lazy_image_slider' ),
			)[ 0 ] ).toHaveLength( WIDGET_INSERT_HANDLES.length );
		} );

		it( 'should not register widget hook when $e is unavailable', async () => {
			// Arrange — canvas context where $e has not yet initialised
			window.elementorDynamicAssets = makePayload();
			delete global.$e;

			const { init } = await import( 'elementor/modules/dynamic-assets-manager/assets/js/event-wiring' );
			init( mockLoader );

			// Act — should not throw even without $e
			expect( () => {
				window.dispatchEvent( new CustomEvent( 'elementor/initialized' ) );
			} ).not.toThrow();
		} );

		it( 'should skip document_ready wiring when the payload contains only widget_insert handles', async () => {
			// Arrange — page has only an image-slider, no always-on widgets
			window.elementorDynamicAssets = {
				e_lazy_image_slider: {
					uri: 'https://cdn.example.com/image-slider.js',
					type: 'script',
					deps: [],
					intent: 'enqueue/defer',
					deferTrigger: 'widget_insert',
				},
				e_lazy_image_slider_css: {
					uri: 'https://cdn.example.com/image-slider.css',
					type: 'style',
					deps: [],
					intent: 'enqueue/defer',
					deferTrigger: 'widget_insert',
				},
			};

			const { init } = await import( 'elementor/modules/dynamic-assets-manager/assets/js/event-wiring' );
			init( mockLoader );

			// Act
			window.dispatchEvent( new CustomEvent( 'elementor/initialized' ) );

			// Assert — load not called for document_ready bucket (nothing to load upfront)
			const docReadyCalls = mockLoader.load.mock.calls.filter(
				( [ handles ] ) => handles.some( ( h ) => 'document_ready' === window.elementorDynamicAssets[ h ]?.deferTrigger ),
			);
			expect( docReadyCalls ).toHaveLength( 0 );
		} );
	} );
} );
