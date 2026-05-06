<?php
namespace Elementor\Modules\DynamicAssetsManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Document_Parser {
	const PARSED_KEYS_FILTER = 'elementor/dynamic_assets_manager/parsed_participant_keys';

	const PARSE_DATA_FILTER = 'elementor/dynamic_assets_manager/parse_document_data';

	public function parse( $post_id, $context ) {
		$post_id = (int) $post_id;

		if ( $post_id <= 0 ) {
			return Parse_Result::empty_document();
		}

		$raw_meta = get_post_meta( $post_id, '_elementor_data', true );

		$raw_meta = apply_filters( self::PARSE_DATA_FILTER, $raw_meta, $post_id, $context );

		if ( ! is_string( $raw_meta ) || '' === $raw_meta ) {
			return Parse_Result::empty_document();
		}

		$elements = json_decode( $raw_meta, true );

		if ( JSON_ERROR_NONE !== json_last_error() ) {
			return Parse_Result::malformed_document();
		}

		if ( ! is_array( $elements ) || empty( $elements ) ) {
			return Parse_Result::empty_document();
		}

		$keys = $this->collect_keys( $elements );
		$keys = array_values( array_unique( array_filter( $keys ) ) );

		$keys = (array) apply_filters( self::PARSED_KEYS_FILTER, $keys, $post_id, $context );

		return new Parse_Result( $keys );
	}

	private function collect_keys( array $elements ) {
		$keys = [];

		foreach ( $elements as $element ) {
			if ( ! is_array( $element ) || empty( $element['elType'] ) ) {
				continue;
			}

			if ( 'widget' === $element['elType'] ) {
				if ( ! empty( $element['widgetType'] ) && is_string( $element['widgetType'] ) ) {
					$keys[] = trim( $element['widgetType'] );
				}
			}

			if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
				$keys = array_merge( $keys, $this->collect_keys( $element['elements'] ) );
			}
		}

		return $keys;
	}
}
