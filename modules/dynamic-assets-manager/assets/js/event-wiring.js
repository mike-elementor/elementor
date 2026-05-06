import { createDeferredLoader } from './deferred-loader';

export function init( loader = createDeferredLoader() ) {
	const payload = window.elementorDynamicAssets;

	if ( ! payload ) {
		return;
	}

	const docReadyHandles = getHandlesByTrigger( payload, 'document_ready' );
	const widgetInsertHandles = getHandlesByTrigger( payload, 'widget_insert' );

	if ( docReadyHandles.length ) {
		window.addEventListener( 'elementor/initialized', function onEditorInit() {
			loader.load( docReadyHandles, payload );
		}, { once: true } );
	}

	if ( widgetInsertHandles.length ) {
		window.addEventListener( 'elementor/initialized', function onEditorInitForWidgets() {
			if ( ! window.$e?.hooks ) {
				return;
			}

			// eslint-disable-next-line no-undef
			class DynamicAssetsWidgetInsertHook extends $e.modules.hookData.After {
				getCommand() {
					return 'document/elements/create';
				}

				getId() {
					return 'e-dynamic-assets-widget-insert';
				}

				apply() {
					loader.load( widgetInsertHandles, payload );
				}
			}

			new DynamicAssetsWidgetInsertHook().register();
		}, { once: true } );
	}
}

function getHandlesByTrigger( payload, trigger ) {
	return Object.keys( payload ).filter( ( handle ) => payload[ handle ].deferTrigger === trigger );
}
