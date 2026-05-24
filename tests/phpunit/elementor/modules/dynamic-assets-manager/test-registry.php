<?php

namespace Elementor\Tests\Phpunit\Elementor\Modules\DynamicAssetsManager;

use Elementor\Modules\DynamicAssetsManager\Asset_Intent;
use Elementor\Modules\DynamicAssetsManager\Asset_Type;
use Elementor\Modules\DynamicAssetsManager\Context;
use Elementor\Modules\DynamicAssetsManager\Defer_Trigger;
use Elementor\Modules\DynamicAssetsManager\Registry;
use ElementorEditorTesting\Elementor_Test_Base;
use InvalidArgumentException;

/**
 * @group dynamic-assets-manager
 */
class Test_Registry extends Elementor_Test_Base {
	public function test_registers_asset_with_required_fields() {
		$registry = new Registry();

		$registry->register(
			'test-widget',
			[
				'handle' => 'e_lazy_test_script',
				'type' => Asset_Type::SCRIPT,
				'uri' => 'https://example.com/test.js',
				'deps' => [ 'elementor-frontend' ],
				'intent' => Asset_Intent::ENQUEUE_DEFER,
				'deferTrigger' => Defer_Trigger::DOCUMENT_READY,
				'context' => Context::EDITOR,
			]
		);

		$metadata = $registry->get_handle_metadata( 'e_lazy_test_script' );

		$this->assertEquals( 'test-widget', $metadata['owner'] );
		$this->assertEquals( Asset_Intent::ENQUEUE_DEFER, $metadata['intent'] );
		$this->assertEquals( [ 'e_lazy_test_script' ], $registry->get_owner_handles( 'test-widget', Context::EDITOR ) );
	}

	public function test_rejects_invalid_metadata() {
		$this->expectException( InvalidArgumentException::class );

		$registry = new Registry();

		$registry->register(
			'test-widget',
			[
				'handle' => 'e_lazy_invalid',
				'type' => Asset_Type::SCRIPT,
				'uri' => 'https://example.com/invalid.js',
				'deps' => [],
				'intent' => 'invalid_intent',
				'context' => Context::EDITOR,
			]
		);
	}

	public function test_deduplicates_identical_handle_registration() {
		$registry = new Registry();
		$metadata = [
			'handle' => 'e_lazy_test_style',
			'type' => Asset_Type::STYLE,
			'uri' => 'https://example.com/test.css',
			'deps' => [],
			'intent' => Asset_Intent::ENQUEUE,
			'context' => Context::EDITOR,
		];

		$registry->register( 'test-widget', $metadata );
		$registry->register( 'test-widget', $metadata );

		$this->assertEquals( [ 'e_lazy_test_style' ], $registry->get_owner_handles( 'test-widget', Context::EDITOR ) );
	}
}
