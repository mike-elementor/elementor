import { inject } from 'elementor/modules/dynamic-assets-manager/assets/js/dom-injector';

const MARKER_ATTR = 'data-e-lazy-handle';

const makeScriptEntry = ( uri = 'https://cdn.example.com/react.min.js' ) => ( {
	uri,
	type: 'script',
	deps: [],
} );

const makeStyleEntry = ( uri = 'https://cdn.example.com/image-slider.css' ) => ( {
	uri,
	type: 'style',
	deps: [],
} );

function simulateLoad( element ) {
	element.dispatchEvent( new Event( 'load' ) );
}

function simulateError( element ) {
	element.dispatchEvent( new Event( 'error' ) );
}

describe( 'dom-injector', () => {
	afterEach( () => {
		document.head.innerHTML = '';
	} );

	describe( 'script injection', () => {
		it( 'should create a script element with correct src and marker', async () => {
			// Arrange — image-slider JS bundle loaded on widget_insert
			const handle = 'e_lazy_image_slider';
			const entry = makeScriptEntry( 'https://cdn.example.com/image-slider.js' );

			// Act
			const promise = inject( handle, entry );
			const script = document.querySelector( `script[${ MARKER_ATTR }="${ handle }"]` );
			simulateLoad( script );
			await promise;

			// Assert
			expect( script ).not.toBeNull();
			expect( script.src ).toBe( 'https://cdn.example.com/image-slider.js' );
			expect( script.getAttribute( MARKER_ATTR ) ).toBe( handle );
		} );

		it( 'should append the script element to document.head', async () => {
			// Arrange — react-dom injected as transitive dep of fancy-popover
			const handle = 'react-dom';
			const entry = makeScriptEntry( 'https://cdn.example.com/react-dom.min.js' );

			// Act
			const promise = inject( handle, entry );
			simulateLoad( document.querySelector( `script[${ MARKER_ATTR }="${ handle }"]` ) );
			await promise;

			// Assert
			expect( document.head.contains( document.querySelector( `script[${ MARKER_ATTR }="${ handle }"]` ) ) ).toBe( true );
		} );

		it( 'should resolve the promise on script load', async () => {
			// Arrange — react base bundle
			const handle = 'react';
			const entry = makeScriptEntry( 'https://cdn.example.com/react.min.js' );

			// Act
			const promise = inject( handle, entry );
			simulateLoad( document.querySelector( `script[${ MARKER_ATTR }="${ handle }"]` ) );

			// Assert
			await expect( promise ).resolves.toBeUndefined();
		} );

		it( 'should reject the promise on script error', async () => {
			// Arrange — fancy-popover CDN is unreachable
			const handle = 'e_lazy_fancy_popover';
			const entry = makeScriptEntry( 'https://cdn.example.com/fancy-popover.js' );

			// Act
			const promise = inject( handle, entry );
			simulateError( document.querySelector( `script[${ MARKER_ATTR }="${ handle }"]` ) );

			// Assert
			await expect( promise ).rejects.toThrow( 'Failed to load script: e_lazy_fancy_popover' );
		} );
	} );

	describe( 'style injection', () => {
		it( 'should create a link element with correct href and marker', async () => {
			// Arrange — fancy-popover stylesheet
			const handle = 'e_lazy_fancy_popover_css';
			const entry = makeStyleEntry( 'https://cdn.example.com/fancy-popover.css' );

			// Act
			const promise = inject( handle, entry );
			const link = document.querySelector( `link[${ MARKER_ATTR }="${ handle }"]` );
			simulateLoad( link );
			await promise;

			// Assert
			expect( link ).not.toBeNull();
			expect( link.href ).toBe( 'https://cdn.example.com/fancy-popover.css' );
			expect( link.rel ).toBe( 'stylesheet' );
			expect( link.getAttribute( MARKER_ATTR ) ).toBe( handle );
		} );

		it( 'should reject the promise on style load error', async () => {
			// Arrange — image-slider CSS 404s on the CDN
			const handle = 'e_lazy_image_slider_css';
			const entry = makeStyleEntry( 'https://cdn.example.com/image-slider.css' );

			// Act
			const promise = inject( handle, entry );
			simulateError( document.querySelector( `link[${ MARKER_ATTR }="${ handle }"]` ) );

			// Assert
			await expect( promise ).rejects.toThrow( 'Failed to load style: e_lazy_image_slider_css' );
		} );
	} );

	describe( 'deduplication via DOM marker', () => {
		it( 'should resolve immediately without re-injecting if handle already in DOM', async () => {
			// Arrange — react was already injected by a previous document_ready trigger
			const handle = 'react';
			const existing = document.createElement( 'script' );
			existing.setAttribute( MARKER_ATTR, handle );
			document.head.appendChild( existing );

			const countBefore = document.querySelectorAll( `[${ MARKER_ATTR }="${ handle }"]` ).length;

			// Act — second widget requesting react should not add a second tag
			await inject( handle, makeScriptEntry( 'https://cdn.example.com/react.min.js' ) );

			// Assert
			const countAfter = document.querySelectorAll( `[${ MARKER_ATTR }="${ handle }"]` ).length;
			expect( countAfter ).toBe( countBefore );
		} );
	} );
} );
