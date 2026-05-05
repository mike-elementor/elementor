<?php

namespace Elementor\Tests\Phpunit\Elementor\Modules\DynamicAssetsManager;

use Elementor\Modules\DynamicAssetsManager\Asset_Intent;
use Elementor\Modules\DynamicAssetsManager\Asset_Type;
use Elementor\Modules\DynamicAssetsManager\Assets_Manager;
use Elementor\Modules\DynamicAssetsManager\Context;
use Elementor\Modules\DynamicAssetsManager\Defer_Trigger;
use Elementor\Modules\DynamicAssetsManager\Dependency_Resolver;
use Elementor\Modules\DynamicAssetsManager\Legacy_Depends_Adapter;
use Elementor\Modules\DynamicAssetsManager\Registry;
use ElementorEditorTesting\Elementor_Test_Base;

/**
 * @group dynamic-assets-manager
 */
class Test_Assets_Manager extends Elementor_Test_Base {
	public function test_resolves_enqueue_and_defer_partitions_with_dependency_order() {
		$registry = new Registry();

		$registry->register(
			'test-widget',
			[
				'handle' => 'e_lazy_dependency',
				'type' => Asset_Type::SCRIPT,
				'uri' => 'https://example.com/dependency.js',
				'deps' => [],
				'intent' => Asset_Intent::ENQUEUE,
				'context' => Context::EDITOR,
			]
		);

		$registry->register(
			'test-widget',
			[
				'handle' => 'e_lazy_enqueue',
				'type' => Asset_Type::SCRIPT,
				'uri' => 'https://example.com/enqueue.js',
				'deps' => [ 'e_lazy_dependency' ],
				'intent' => Asset_Intent::ENQUEUE,
				'context' => Context::EDITOR,
			]
		);

		$registry->register(
			'test-widget',
			[
				'handle' => 'e_lazy_defer_ready',
				'type' => Asset_Type::SCRIPT,
				'uri' => 'https://example.com/defer-ready.js',
				'deps' => [ 'e_lazy_enqueue' ],
				'intent' => Asset_Intent::ENQUEUE_DEFER,
				'deferTrigger' => Defer_Trigger::DOCUMENT_READY,
				'context' => Context::EDITOR,
			]
		);

		$registry->register(
			'test-widget',
			[
				'handle' => 'e_lazy_defer_insert',
				'type' => Asset_Type::STYLE,
				'uri' => 'https://example.com/defer-insert.css',
				'deps' => [],
				'intent' => Asset_Intent::ENQUEUE_DEFER,
				'deferTrigger' => Defer_Trigger::WIDGET_INSERT,
				'context' => Context::EDITOR,
			]
		);

		$assets_manager = new Assets_Manager(
			$registry,
			new Dependency_Resolver(),
			new Legacy_Depends_Adapter()
		);

		$result = $assets_manager->build( [ 'test-widget' ], Context::EDITOR );

		$this->assertEquals( [ 'e_lazy_dependency', 'e_lazy_enqueue' ], $result['enqueue_handles'] );
		$this->assertEquals( [ 'e_lazy_defer_ready' ], $result['deferred_by_trigger'][ Defer_Trigger::DOCUMENT_READY ] );
		$this->assertEquals( [ 'e_lazy_defer_insert' ], $result['deferred_by_trigger'][ Defer_Trigger::WIDGET_INSERT ] );
		$this->assertArrayHasKey( 'e_lazy_defer_ready', $result['client_payload'] );
		$this->assertEquals(
			Defer_Trigger::DOCUMENT_READY,
			$result['client_payload']['e_lazy_defer_ready']['deferTrigger']
		);
	}

	public function test_uses_legacy_adapter_when_owner_has_no_registry_assets() {
		$registry = new Registry();
		$legacy_filter = static function( $assets, $owner_key, $context ) {
			if ( 'legacy-widget' !== $owner_key || Context::EDITOR !== $context ) {
				return $assets;
			}

			return [
				[
					'handle' => 'legacy-widget-script',
					'type' => Asset_Type::SCRIPT,
					'uri' => 'https://example.com/legacy.js',
					'deps' => [],
					'intent' => Asset_Intent::ENQUEUE,
					'context' => Context::EDITOR,
				],
			];
		};

		add_filter( 'elementor/dynamic_assets_manager/legacy_owner_assets', $legacy_filter, 10, 3 );

		$assets_manager = new Assets_Manager(
			$registry,
			new Dependency_Resolver(),
			new Legacy_Depends_Adapter()
		);

		$result = $assets_manager->build( [ 'legacy-widget' ], Context::EDITOR );

		remove_filter( 'elementor/dynamic_assets_manager/legacy_owner_assets', $legacy_filter, 10 );

		$this->assertEquals( [ 'legacy-widget-script' ], $result['enqueue_handles'] );
		$this->assertEmpty( $result['client_payload'] );
	}
}
