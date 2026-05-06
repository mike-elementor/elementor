<?php
namespace Elementor\Modules\DynamicAssetsManager;

use Elementor\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Participant_Keys_Provider {
	private $parser;

	public function __construct( Document_Parser $parser ) {
		$this->parser = $parser;
	}

	public function provide( array $keys, $context ) {
		if ( ! empty( $keys ) ) {
			return $keys;
		}

		$post_id = $this->resolve_post_id( $context );

		if ( $post_id <= 0 ) {
			return [];
		}

		$result = $this->parser->parse( $post_id, $context );

		return $result->get_participant_keys();
	}

	private function resolve_post_id( $context ) {
		if ( Context::EDITOR === $context && isset( Plugin::$instance->editor ) ) {
			$post_id = Plugin::$instance->editor->get_post_id();

			if ( $post_id ) {
				return (int) $post_id;
			}
		}

		if ( Context::EDITOR_CANVAS === $context && isset( Plugin::$instance->preview ) ) {
			$post_id = Plugin::$instance->preview->get_post_id();

			if ( $post_id ) {
				return (int) $post_id;
			}
		}

		return 0;
	}
}
