<?php

namespace Elementor\Tests\Phpunit\Elementor\Modules\DynamicAssetsManager;

use Elementor\Core\Experiments\Manager as Experiments_Manager;
use Elementor\Modules\DynamicAssetsManager\Module;
use Elementor\Plugin;
use ElementorEditorTesting\Elementor_Test_Base;

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
}
