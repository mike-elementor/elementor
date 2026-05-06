<?php

namespace Elementor\Tests\Phpunit\Elementor\Modules\DynamicAssetsManager;

use Elementor\Core\Experiments\Manager as Experiments_Manager;
use Elementor\Modules\DynamicAssetsManager\Asset_Intent;
use Elementor\Modules\DynamicAssetsManager\Asset_Type;
use Elementor\Modules\DynamicAssetsManager\Context;
use Elementor\Modules\DynamicAssetsManager\Defer_Trigger;
use Elementor\Modules\DynamicAssetsManager\Module;
use Elementor\Plugin;
use ElementorEditorTesting\Elementor_Test_Base;

const REQUIRED_CLIENT_PAYLOAD_FIELDS = [ 'uri', 'type', 'deps', 'intent', 'deferTrigger' ];

/**
 * @group dynamic-assets-manager
 */
class Test_Module extends Elementor_Test_Base {
	public function test_experiment_metadata_uses_expected_slug() {
		$data = Module::get_experimental_data();

		$this->assertEquals( Module::EXPERIMENT_NAME, $data['name'] );
		$this->assertEquals( Experiments_Manager::STATE_INACTIVE, $data['default'] );
		$this->assertTrue( $data['hidden'] );
	}

	public function test_enqueue_integration_hooks_are_not_registered_when_experiment_is_inactive() {
		Plugin::$instance->experiments->set_feature_default_state(
			Module::EXPERIMENT_NAME,
			Experiments_Manager::STATE_INACTIVE
		);

		$module = new Module();

		$this->assertFalse( has_action( 'elementor/editor/before_enqueue_scripts', [ $module, 'before_enqueue_scripts_editor' ] ) );
		$this->assertFalse( has_action( 'elementor/editor/after_enqueue_scripts', [ $module, 'after_enqueue_scripts_editor' ] ) );
		$this->assertFalse( has_action( 'elementor/editor/before_enqueue_styles', [ $module, 'before_enqueue_styles_editor' ] ) );
		$this->assertFalse( has_action( 'elementor/editor/after_enqueue_styles', [ $module, 'after_enqueue_styles_editor' ] ) );
		$this->assertFalse( has_action( 'elementor/preview/enqueue_styles', [ $module, 'before_enqueue_scripts_editor_canvas' ] ) );
		$this->assertFalse( has_action( 'elementor/preview/enqueue_styles', [ $module, 'after_enqueue_scripts_editor_canvas' ] ) );
		$this->assertFalse( has_action( 'elementor/preview/enqueue_scripts', [ $module, 'before_enqueue_scripts_editor_canvas' ] ) );
		$this->assertFalse( has_action( 'elementor/preview/enqueue_scripts', [ $module, 'after_enqueue_scripts_editor_canvas' ] ) );
	}

	public function test_editor_enqueue_hooks_remain_safe_with_no_module_listeners() {
		$count_before = did_action( 'elementor/editor/before_enqueue_scripts' );

		do_action( 'elementor/editor/before_enqueue_scripts' );

		$this->assertEquals( $count_before + 1, did_action( 'elementor/editor/before_enqueue_scripts' ) );
	}

	public function test_disabled_mode_does_not_alter_enqueue_flow() {
		Plugin::$instance->experiments->set_feature_default_state(
			Module::EXPERIMENT_NAME,
			Experiments_Manager::STATE_INACTIVE
		);

		new Module();

		global $wp_scripts, $wp_styles;

		$scripts_queue_before = count( $wp_scripts->queue ?? [] );
		$styles_queue_before = count( $wp_styles->queue ?? [] );

		do_action( 'elementor/preview/enqueue_scripts' );

		$scripts_queue_after = count( $wp_scripts->queue ?? [] );
		$styles_queue_after = count( $wp_styles->queue ?? [] );

		$this->assertEquals( $scripts_queue_before, $scripts_queue_after );
		$this->assertEquals( $styles_queue_before, $styles_queue_after );
	}

	public function test_bootstrap_action_receives_registry_instance_when_feature_is_active() {
		Plugin::$instance->experiments->set_feature_default_state(
			Module::EXPERIMENT_NAME,
			Experiments_Manager::STATE_ACTIVE
		);

		new Module();

		$received_registry = null;
		$listener = function( $registry ) use ( &$received_registry ) {
			$received_registry = $registry;
		};

		add_action( Module::REGISTER_ASSETS_HOOK, $listener, 10, 2 );
		$participant_keys_filter = static function() {
			return [];
		};

		add_filter( Module::PARTICIPANT_KEYS_FILTER, $participant_keys_filter, 10, 3 );

		do_action( 'elementor/editor/before_enqueue_scripts' );

		remove_action( Module::REGISTER_ASSETS_HOOK, $listener, 10 );
		remove_filter( Module::PARTICIPANT_KEYS_FILTER, $participant_keys_filter, 10 );

		$this->assertInstanceOf( 'Elementor\Modules\DynamicAssetsManager\Registry', $received_registry );
	}

	public function test_deferred_handles_are_pruned_from_queue_when_feature_is_active() {
		Plugin::$instance->experiments->set_feature_default_state(
			Module::EXPERIMENT_NAME,
			Experiments_Manager::STATE_ACTIVE
		);

		new Module();

		wp_register_script( 'e_lazy_test_script', 'https://example.com/deferred.js', [], null, true );
		wp_register_style( 'e_lazy_test_style', 'https://example.com/deferred.css', [], null );

		wp_enqueue_script( 'e_lazy_test_script' );
		wp_enqueue_style( 'e_lazy_test_style' );

		$register_assets = static function( $registry, $context ) {
			if ( Context::EDITOR !== $context ) {
				return;
			}

			$registry->register(
				'test-widget',
				[
					'handle' => 'e_lazy_test_script',
					'type' => Asset_Type::SCRIPT,
					'uri' => 'https://example.com/deferred.js',
					'deps' => [],
					'intent' => Asset_Intent::ENQUEUE_DEFER,
					'deferTrigger' => Defer_Trigger::DOCUMENT_READY,
					'context' => Context::EDITOR,
				]
			);

			$registry->register(
				'test-widget',
				[
					'handle' => 'e_lazy_test_style',
					'type' => Asset_Type::STYLE,
					'uri' => 'https://example.com/deferred.css',
					'deps' => [],
					'intent' => Asset_Intent::ENQUEUE_DEFER,
					'deferTrigger' => Defer_Trigger::WIDGET_INSERT,
					'context' => Context::EDITOR,
				]
			);
		};

		add_action( Module::REGISTER_ASSETS_HOOK, $register_assets, 10, 2 );

		$participant_keys_filter = static function( $keys, $context ) {
			if ( Context::EDITOR !== $context ) {
				return [];
			}

			return [ 'test-widget' ];
		};

		add_filter( Module::PARTICIPANT_KEYS_FILTER, $participant_keys_filter, 10, 3 );

		do_action( 'elementor/editor/before_enqueue_scripts' );
		do_action( 'elementor/editor/after_enqueue_scripts' );
		do_action( 'elementor/editor/before_enqueue_styles' );
		do_action( 'elementor/editor/after_enqueue_styles' );

		remove_action( Module::REGISTER_ASSETS_HOOK, $register_assets, 10 );
		remove_filter( Module::PARTICIPANT_KEYS_FILTER, $participant_keys_filter, 10 );

		$this->assertFalse( in_array( 'e_lazy_test_script', wp_scripts()->queue, true ) );
		$this->assertFalse( in_array( 'e_lazy_test_style', wp_styles()->queue, true ) );
	}

	public function test_client_payload_entries_contain_all_required_fields_when_feature_is_active() {
		Plugin::$instance->experiments->set_feature_default_state(
			Module::EXPERIMENT_NAME,
			Experiments_Manager::STATE_ACTIVE
		);

		new Module();

		wp_register_script( 'e_lazy_field_check', 'https://example.com/field-check.js', [], null, true );

		$received_payload = null;
		add_action(
			Module::PAYLOAD_READY_ACTION,
			static function( $payload ) use ( &$received_payload ) {
				$received_payload = $payload;
			}
		);

		$register_assets = static function( $registry, $context ) {
			if ( Context::EDITOR !== $context ) {
				return;
			}

			$registry->register(
				'field-check-widget',
				[
					'handle'       => 'e_lazy_field_check',
					'type'         => Asset_Type::SCRIPT,
					'uri'          => 'https://example.com/field-check.js',
					'deps'         => [],
					'intent'       => Asset_Intent::ENQUEUE_DEFER,
					'deferTrigger' => Defer_Trigger::DOCUMENT_READY,
					'context'      => Context::EDITOR,
				]
			);
		};

		add_action( Module::REGISTER_ASSETS_HOOK, $register_assets, 10, 2 );

		$keys_filter = static function( $keys, $context ) {
			return Context::EDITOR === $context ? [ 'field-check-widget' ] : [];
		};

		add_filter( Module::PARTICIPANT_KEYS_FILTER, $keys_filter, 10, 3 );

		do_action( 'elementor/editor/before_enqueue_scripts' );

		remove_action( Module::REGISTER_ASSETS_HOOK, $register_assets, 10 );
		remove_filter( Module::PARTICIPANT_KEYS_FILTER, $keys_filter, 10 );

		$this->assertNotNull( $received_payload );
		$this->assertArrayHasKey( 'e_lazy_field_check', $received_payload );

		$entry = $received_payload['e_lazy_field_check'];

		foreach ( REQUIRED_CLIENT_PAYLOAD_FIELDS as $field ) {
			$this->assertArrayHasKey( $field, $entry, "Client payload entry is missing required field: $field" );
		}
	}

	public function test_loader_script_is_enqueued_with_payload_config_when_feature_is_active() {
		Plugin::$instance->experiments->set_feature_default_state(
			Module::EXPERIMENT_NAME,
			Experiments_Manager::STATE_ACTIVE
		);

		new Module();

		wp_register_script( 'e_lazy_loader_script', 'https://example.com/loader.js', [], null, true );

		$register_assets = static function( $registry, $context ) {
			if ( Context::EDITOR !== $context ) {
				return;
			}

			$registry->register(
				'loader-widget',
				[
					'handle'       => 'e_lazy_loader_script',
					'type'         => Asset_Type::SCRIPT,
					'uri'          => 'https://example.com/loader.js',
					'deps'         => [],
					'intent'       => Asset_Intent::ENQUEUE_DEFER,
					'deferTrigger' => Defer_Trigger::DOCUMENT_READY,
					'context'      => Context::EDITOR,
				]
			);
		};

		add_action( Module::REGISTER_ASSETS_HOOK, $register_assets, 10, 2 );

		$keys_filter = static function( $keys, $context ) {
			return Context::EDITOR === $context ? [ 'loader-widget' ] : [];
		};

		add_filter( Module::PARTICIPANT_KEYS_FILTER, $keys_filter, 10, 3 );

		do_action( 'elementor/editor/before_enqueue_scripts' );
		do_action( 'elementor/editor/after_enqueue_scripts' );

		remove_action( Module::REGISTER_ASSETS_HOOK, $register_assets, 10 );
		remove_filter( Module::PARTICIPANT_KEYS_FILTER, $keys_filter, 10 );

		$this->assertTrue( wp_script_is( Module::LOADER_SCRIPT_HANDLE, 'enqueued' ) );

		$inline_scripts = wp_scripts()->get_data( Module::LOADER_SCRIPT_HANDLE, 'data' );
		$this->assertNotEmpty( $inline_scripts );
		$this->assertStringContainsString( 'elementorDynamicAssets', $inline_scripts );
		$this->assertStringContainsString( 'e_lazy_loader_script', $inline_scripts );
	}

	public function test_loader_script_is_not_enqueued_when_feature_is_inactive() {
		Plugin::$instance->experiments->set_feature_default_state(
			Module::EXPERIMENT_NAME,
			Experiments_Manager::STATE_INACTIVE
		);

		new Module();

		do_action( 'elementor/editor/before_enqueue_scripts' );
		do_action( 'elementor/editor/after_enqueue_scripts' );

		$this->assertFalse( wp_script_is( Module::LOADER_SCRIPT_HANDLE, 'enqueued' ) );
	}
}
