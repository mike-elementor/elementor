<?php

namespace Elementor\Tests\Phpunit\Elementor\Modules\DynamicAssetsManager;

use Elementor\Core\Experiments\Manager as Experiments_Manager;
use Elementor\Modules\DynamicAssetsManager\Asset_Intent;
use Elementor\Modules\DynamicAssetsManager\Asset_Type;
use Elementor\Modules\DynamicAssetsManager\Context;
use Elementor\Modules\DynamicAssetsManager\Document_Parser;
use Elementor\Modules\DynamicAssetsManager\Module;
use Elementor\Modules\DynamicAssetsManager\Participant_Keys_Provider;
use Elementor\Plugin;
use ElementorEditorTesting\Elementor_Test_Base;

/**
 * @group dynamic-assets-manager
 */
class Test_Participant_Keys_Provider extends Elementor_Test_Base {
	public function test_returns_already_provided_keys_without_parsing() {
		$provider = new Participant_Keys_Provider( new Document_Parser() );

		$result = $provider->provide( [ 'heading', 'button' ], Context::EDITOR );

		$this->assertEquals( [ 'heading', 'button' ], $result );
	}

	public function test_returns_empty_array_for_zero_post_id() {
		$provider = new Participant_Keys_Provider( new Document_Parser() );

		$result = $provider->provide( [], Context::EDITOR );

		$this->assertEmpty( $result );
	}

	public function test_experiment_disabled_does_not_wire_participant_keys_filter() {
		Plugin::$instance->experiments->set_feature_default_state(
			Module::EXPERIMENT_NAME,
			Experiments_Manager::STATE_INACTIVE
		);

		$module = new Module();

		$this->assertFalse( has_filter(
			Module::PARTICIPANT_KEYS_FILTER,
			[ $module, 'provide_participant_keys_from_document' ]
		) );
	}

	public function test_experiment_active_wires_participant_keys_filter() {
		Plugin::$instance->experiments->set_feature_default_state(
			Module::EXPERIMENT_NAME,
			Experiments_Manager::STATE_ACTIVE
		);

		$module = new Module();

		$this->assertNotFalse( has_filter(
			Module::PARTICIPANT_KEYS_FILTER,
			[ $module, 'provide_participant_keys_from_document' ]
		) );
	}

	public function test_parser_output_reaches_assets_manager_through_participant_keys_filter() {
		$post_id = $this->factory()->post->create();
		$elements = [
			[
				'elType' => 'widget',
				'widgetType' => 'heading',
				'elements' => [],
			],
		];
		update_post_meta( $post_id, '_elementor_data', wp_json_encode( $elements ) );

		$parser = new Document_Parser();
		$provider = new Participant_Keys_Provider( $parser );

		$data_filter = static function( $raw, $pid ) use ( $post_id ) {
			return $pid === $post_id ? $raw : $raw;
		};
		add_filter( Document_Parser::PARSE_DATA_FILTER, $data_filter, 10, 3 );

		$keys_from_filter = static function( array $keys, $context ) use ( $provider ) {
			return $provider->provide( $keys, $context );
		};

		add_filter( Module::PARTICIPANT_KEYS_FILTER, $keys_from_filter, 10, 2 );

		Plugin::$instance->editor->set_post_id( $post_id );

		$keys = apply_filters( Module::PARTICIPANT_KEYS_FILTER, [], Context::EDITOR, null );

		remove_filter( Module::PARTICIPANT_KEYS_FILTER, $keys_from_filter, 10 );
		remove_filter( Document_Parser::PARSE_DATA_FILTER, $data_filter, 10 );

		$this->assertContains( 'heading', $keys );
	}

	public function test_context_is_passed_correctly_for_editor_and_canvas_parse() {
		$post_id = $this->factory()->post->create();
		$elements = [
			[
				'elType' => 'widget',
				'widgetType' => 'image',
				'elements' => [],
			],
		];
		update_post_meta( $post_id, '_elementor_data', wp_json_encode( $elements ) );

		$captured_context = null;
		$context_capture = static function( $keys, $pid, $ctx ) use ( &$captured_context ) {
			$captured_context = $ctx;
			return $keys;
		};
		add_filter( Document_Parser::PARSED_KEYS_FILTER, $context_capture, 10, 3 );

		$parser = new Document_Parser();
		$parser->parse( $post_id, Context::EDITOR );

		remove_filter( Document_Parser::PARSED_KEYS_FILTER, $context_capture, 10 );

		$this->assertEquals( Context::EDITOR, $captured_context );
	}
}
