<?php

namespace Elementor\Tests\Phpunit\Elementor\Modules\DynamicAssetsManager;

use Elementor\Modules\DynamicAssetsManager\Context;
use Elementor\Modules\DynamicAssetsManager\Document_Parser;
use Elementor\Modules\DynamicAssetsManager\Parse_Result;
use ElementorEditorTesting\Elementor_Test_Base;

/**
 * @group dynamic-assets-manager
 */
class Test_Document_Parser extends Elementor_Test_Base {
	private $parser;

	public function setUp(): void {
		parent::setUp();
		$this->parser = new Document_Parser();
	}

	public function test_extracts_widget_types_from_flat_tree() {
		$post_id = $this->factory()->post->create();
		$elements = [
			[
				'elType' => 'container',
				'elements' => [
					[
						'elType' => 'widget',
						'widgetType' => 'heading',
						'elements' => [],
					],
					[
						'elType' => 'widget',
						'widgetType' => 'button',
						'elements' => [],
					],
				],
			],
		];

		update_post_meta( $post_id, '_elementor_data', wp_json_encode( $elements ) );

		$result = $this->parser->parse( $post_id, Context::EDITOR );

		$this->assertFalse( $result->is_empty() );
		$this->assertFalse( $result->is_malformed() );
		$this->assertContains( 'heading', $result->get_participant_keys() );
		$this->assertContains( 'button', $result->get_participant_keys() );
	}

	public function test_extracts_widget_types_from_nested_tree() {
		$post_id = $this->factory()->post->create();
		$elements = [
			[
				'elType' => 'section',
				'elements' => [
					[
						'elType' => 'column',
						'elements' => [
							[
								'elType' => 'widget',
								'widgetType' => 'image',
								'elements' => [],
							],
						],
					],
				],
			],
		];

		update_post_meta( $post_id, '_elementor_data', wp_json_encode( $elements ) );

		$result = $this->parser->parse( $post_id, Context::EDITOR );

		$this->assertContains( 'image', $result->get_participant_keys() );
	}

	public function test_deduplicates_repeated_widget_types() {
		$post_id = $this->factory()->post->create();
		$elements = [
			[
				'elType' => 'container',
				'elements' => [
					[
						'elType' => 'widget',
						'widgetType' => 'text-editor',
						'elements' => [],
					],
					[
						'elType' => 'widget',
						'widgetType' => 'text-editor',
						'elements' => [],
					],
				],
			],
		];

		update_post_meta( $post_id, '_elementor_data', wp_json_encode( $elements ) );

		$result = $this->parser->parse( $post_id, Context::EDITOR );
		$keys = $result->get_participant_keys();

		$this->assertCount( 1, array_keys( $keys, 'text-editor', true ) );
	}

	public function test_ignores_invalid_nodes_safely() {
		$post_id = $this->factory()->post->create();
		$elements = [
			null,
			'invalid-string',
			[],
			[ 'elType' => 'widget' ],
			[
				'elType' => 'widget',
				'widgetType' => 'counter',
				'elements' => [],
			],
		];

		update_post_meta( $post_id, '_elementor_data', wp_json_encode( $elements ) );

		$result = $this->parser->parse( $post_id, Context::EDITOR );

		$this->assertFalse( $result->is_malformed() );
		$this->assertContains( 'counter', $result->get_participant_keys() );
	}

	public function test_returns_empty_result_for_missing_meta() {
		$post_id = $this->factory()->post->create();

		$result = $this->parser->parse( $post_id, Context::EDITOR );

		$this->assertTrue( $result->is_empty() );
		$this->assertEmpty( $result->get_participant_keys() );
	}

	public function test_returns_empty_result_for_empty_meta_string() {
		$post_id = $this->factory()->post->create();
		update_post_meta( $post_id, '_elementor_data', '' );

		$result = $this->parser->parse( $post_id, Context::EDITOR );

		$this->assertTrue( $result->is_empty() );
		$this->assertEmpty( $result->get_participant_keys() );
	}

	public function test_returns_empty_result_for_empty_array_meta() {
		$post_id = $this->factory()->post->create();
		update_post_meta( $post_id, '_elementor_data', '[]' );

		$result = $this->parser->parse( $post_id, Context::EDITOR );

		$this->assertTrue( $result->is_empty() );
		$this->assertEmpty( $result->get_participant_keys() );
	}

	public function test_returns_malformed_result_for_invalid_json() {
		$post_id = $this->factory()->post->create();
		update_post_meta( $post_id, '_elementor_data', '{not valid json' );

		$result = $this->parser->parse( $post_id, Context::EDITOR );

		$this->assertTrue( $result->is_malformed() );
		$this->assertEmpty( $result->get_participant_keys() );
	}

	public function test_returns_empty_result_for_invalid_post_id() {
		$result = $this->parser->parse( 0, Context::EDITOR );

		$this->assertTrue( $result->is_empty() );
		$this->assertEmpty( $result->get_participant_keys() );
	}

	public function test_parsed_participant_keys_filter_can_modify_keys() {
		$post_id = $this->factory()->post->create();
		$elements = [
			[
				'elType' => 'widget',
				'widgetType' => 'heading',
				'elements' => [],
			],
		];
		update_post_meta( $post_id, '_elementor_data', wp_json_encode( $elements ) );

		$filter = static function( $keys ) {
			$keys[] = 'injected-widget';
			return $keys;
		};

		add_filter( Document_Parser::PARSED_KEYS_FILTER, $filter, 10, 3 );
		$result = $this->parser->parse( $post_id, Context::EDITOR );
		remove_filter( Document_Parser::PARSED_KEYS_FILTER, $filter, 10 );

		$this->assertContains( 'heading', $result->get_participant_keys() );
		$this->assertContains( 'injected-widget', $result->get_participant_keys() );
	}

	public function test_parse_document_data_filter_can_replace_raw_data() {
		$post_id = $this->factory()->post->create();
		update_post_meta( $post_id, '_elementor_data', '' );

		$filter = static function( $raw, $pid ) use ( $post_id ) {
			if ( $pid === $post_id ) {
				return wp_json_encode( [
					[
						'elType' => 'widget',
						'widgetType' => 'video',
						'elements' => [],
					],
				] );
			}
			return $raw;
		};

		add_filter( Document_Parser::PARSE_DATA_FILTER, $filter, 10, 3 );
		$result = $this->parser->parse( $post_id, Context::EDITOR );
		remove_filter( Document_Parser::PARSE_DATA_FILTER, $filter, 10 );

		$this->assertContains( 'video', $result->get_participant_keys() );
	}
}
