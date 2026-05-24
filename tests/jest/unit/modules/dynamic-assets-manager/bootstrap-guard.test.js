jest.mock( 'elementor/modules/dynamic-assets-manager/assets/js/deferred-loader', () => ( {
	createDeferredLoader: jest.fn( () => ( { load: jest.fn().mockResolvedValue( undefined ) } ) ),
} ) );

describe( 'Dynamic Assets Manager bootstrap guard', () => {
	afterEach( () => {
		delete window.elementorDynamicAssets;
		jest.resetModules();
	} );

	it( 'should not throw when window.elementorDynamicAssets is undefined', async () => {
		// Arrange
		delete window.elementorDynamicAssets;

		// Act & Assert — importing and calling init when payload is absent must not throw
		const { init } = await import( 'elementor/modules/dynamic-assets-manager/assets/js/event-wiring' );
		expect( () => init() ).not.toThrow();
	} );

	it( 'should not throw when window.elementorDynamicAssets is an empty object', async () => {
		// Arrange
		window.elementorDynamicAssets = {};

		// Act & Assert
		const { init } = await import( 'elementor/modules/dynamic-assets-manager/assets/js/event-wiring' );
		expect( () => init() ).not.toThrow();
	} );
} );
