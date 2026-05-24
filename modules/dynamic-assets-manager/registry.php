<?php
namespace Elementor\Modules\DynamicAssetsManager;

use InvalidArgumentException;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Registry implements Registry_Interface {
	private $assets_by_handle = [];

	private $owner_handles = [];

	public function register( $owner_key, array $metadata ) {
		$owner_key = (string) $owner_key;
		if ( '' === $owner_key ) {
			throw new InvalidArgumentException( 'Owner key is required.' );
		}

		$normalized = $this->normalize_metadata( $owner_key, $metadata );
		$handle = $normalized['handle'];

		if ( isset( $this->assets_by_handle[ $handle ] ) ) {
			if ( $this->assets_by_handle[ $handle ] !== $normalized ) {
				throw new InvalidArgumentException( 'Handle is already registered with different metadata.' );
			}
		} else {
			$this->assets_by_handle[ $handle ] = $normalized;
		}

		$context = $normalized['context'];
		if ( ! isset( $this->owner_handles[ $owner_key ] ) ) {
			$this->owner_handles[ $owner_key ] = [];
		}

		if ( ! isset( $this->owner_handles[ $owner_key ][ $context ] ) ) {
			$this->owner_handles[ $owner_key ][ $context ] = [];
		}

		if ( ! in_array( $handle, $this->owner_handles[ $owner_key ][ $context ], true ) ) {
			$this->owner_handles[ $owner_key ][ $context ][] = $handle;
		}
	}

	public function get_handle_metadata( $handle ) {
		$handle = (string) $handle;

		return $this->assets_by_handle[ $handle ] ?? null;
	}

	public function get_owner_handles( $owner_key, $context ) {
		$owner_key = (string) $owner_key;
		$context = (string) $context;

		if ( ! isset( $this->owner_handles[ $owner_key ] ) ) {
			return [];
		}

		$handles = $this->owner_handles[ $owner_key ][ $context ] ?? [];

		if ( isset( $this->owner_handles[ $owner_key ]['*'] ) ) {
			$handles = array_merge( $handles, $this->owner_handles[ $owner_key ]['*'] );
		}

		return array_values( array_unique( $handles ) );
	}

	private function normalize_metadata( $owner_key, array $metadata ) {
		$handle = isset( $metadata['handle'] ) ? (string) $metadata['handle'] : '';
		$type = isset( $metadata['type'] ) ? (string) $metadata['type'] : '';
		$uri = isset( $metadata['uri'] ) ? (string) $metadata['uri'] : '';
		$intent = isset( $metadata['intent'] ) ? (string) $metadata['intent'] : '';
		$context = isset( $metadata['context'] ) ? (string) $metadata['context'] : '';
		$defer_trigger = isset( $metadata['deferTrigger'] ) ? (string) $metadata['deferTrigger'] : '';

		if ( '' === $handle ) {
			throw new InvalidArgumentException( 'Handle is required.' );
		}

		if ( '' === $uri ) {
			throw new InvalidArgumentException( 'URI is required.' );
		}

		if ( '' === $context ) {
			throw new InvalidArgumentException( 'Context is required.' );
		}

		if ( ! Asset_Type::is_valid( $type ) ) {
			throw new InvalidArgumentException( 'Asset type is invalid.' );
		}

		if ( ! Asset_Intent::is_valid( $intent ) ) {
			throw new InvalidArgumentException( 'Asset intent is invalid.' );
		}

		if ( Asset_Intent::ENQUEUE_DEFER === $intent ) {
			if ( '' === $defer_trigger ) {
				$defer_trigger = Defer_Trigger::DOCUMENT_READY;
			}

			if ( ! Defer_Trigger::is_valid( $defer_trigger ) ) {
				throw new InvalidArgumentException( 'Defer trigger is invalid.' );
			}
		} else {
			$defer_trigger = '';
		}

		$deps = isset( $metadata['deps'] ) && is_array( $metadata['deps'] ) ? $metadata['deps'] : [];
		$deps = array_values(
			array_unique(
				array_filter(
					array_map(
						static function( $dependency_handle ) {
							return is_string( $dependency_handle ) ? trim( $dependency_handle ) : '';
						},
						$deps
					)
				)
			)
		);

		return [
			'handle' => $handle,
			'type' => $type,
			'uri' => $uri,
			'deps' => $deps,
			'intent' => $intent,
			'deferTrigger' => $defer_trigger,
			'context' => $context,
			'owner' => $owner_key,
		];
	}
}
