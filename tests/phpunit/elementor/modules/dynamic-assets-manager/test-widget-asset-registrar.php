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
use Elementor\Modules\DynamicAssetsManager\Widget_Asset_Registrar;
use ElementorEditorTesting\Elementor_Test_Base;

/**
 * @group dynamic-assets-manager
 */
class Test_Widget_Asset_Registrar extends Elementor_Test_Base {
	private const SWIPER_JS_URI  = 'https://example.com/swiper.js';
	private const E_SWIPER_URI   = 'https://example.com/e-swiper.css';
	private const CAROUSEL_URI   = 'https://example.com/widget-image-carousel.css';

	public function setUp(): void {
		parent::setUp();

		wp_register_script( 'swiper', self::SWIPER_JS_URI, [], '8.4.5', true );
		wp_register_style( 'e-swiper', self::E_SWIPER_URI, [], '1.0' );
		wp_register_style( 'widget-image-carousel', self::CAROUSEL_URI, [], '1.0' );
	}

	public function tearDown(): void {
		wp_deregister_script( 'swiper' );
		wp_deregister_style( 'e-swiper' );
		wp_deregister_style( 'widget-image-carousel' );

		parent::tearDown();
	}

	public function test_swiper_script_has_enqueue_intent_when_image_carousel_is_participant() {
		// Arrange
		$registry   = new Registry();
		$registrar  = new Widget_Asset_Registrar();
		$registrar->register( $registry, Context::EDITOR_CANVAS );

		$assets_manager = new Assets_Manager( $registry, new Dependency_Resolver(), new Legacy_Depends_Adapter() );

		// Act
		$result = $assets_manager->build( [ 'image-carousel' ], Context::EDITOR_CANVAS );

		// Assert
		$this->assertContains( 'swiper', $result['enqueue_handles'] );
		$this->assertArrayNotHasKey( 'swiper', $result['client_payload'] );
	}

	public function test_css_handles_are_in_client_payload_when_image_carousel_is_participant() {
		// Arrange
		$registry   = new Registry();
		$registrar  = new Widget_Asset_Registrar();
		$registrar->register( $registry, Context::EDITOR_CANVAS );

		$assets_manager = new Assets_Manager( $registry, new Dependency_Resolver(), new Legacy_Depends_Adapter() );

		// Act
		$result = $assets_manager->build( [ 'image-carousel' ], Context::EDITOR_CANVAS );

		// Assert
		$this->assertArrayHasKey( 'e-swiper', $result['client_payload'] );
		$this->assertEquals( Defer_Trigger::WIDGET_INSERT, $result['client_payload']['e-swiper']['deferTrigger'] );
		$this->assertEquals( Asset_Intent::ENQUEUE_DEFER, $result['client_payload']['e-swiper']['intent'] );

		$this->assertArrayHasKey( 'widget-image-carousel', $result['client_payload'] );
		$this->assertEquals( Defer_Trigger::WIDGET_INSERT, $result['client_payload']['widget-image-carousel']['deferTrigger'] );
		$this->assertEquals( Asset_Intent::ENQUEUE_DEFER, $result['client_payload']['widget-image-carousel']['intent'] );
	}

	public function test_no_image_carousel_handles_resolved_when_widget_is_absent() {
		// Arrange
		$registry   = new Registry();
		$registrar  = new Widget_Asset_Registrar();
		$registrar->register( $registry, Context::EDITOR_CANVAS );

		$assets_manager = new Assets_Manager( $registry, new Dependency_Resolver(), new Legacy_Depends_Adapter() );

		// Act — empty participant list simulates a document without the widget
		$result = $assets_manager->build( [], Context::EDITOR_CANVAS );

		// Assert
		$this->assertNotContains( 'swiper', $result['enqueue_handles'] );
		$this->assertArrayNotHasKey( 'e-swiper', $result['client_payload'] );
		$this->assertArrayNotHasKey( 'widget-image-carousel', $result['client_payload'] );
	}

	public function test_registration_is_idempotent() {
		// Arrange
		$registry  = new Registry();
		$registrar = new Widget_Asset_Registrar();

		// Act — register twice with identical metadata
		$registrar->register( $registry, Context::EDITOR );
		$exception = null;

		try {
			$registrar->register( $registry, Context::EDITOR );
		} catch ( \InvalidArgumentException $e ) {
			$exception = $e;
		}

		// Assert — second call must not throw
		$this->assertNull( $exception );
	}
}
