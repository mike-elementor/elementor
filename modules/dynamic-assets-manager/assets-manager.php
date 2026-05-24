<?php
namespace Elementor\Modules\DynamicAssetsManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Assets_Manager {
	private $registry;

	private $dependency_resolver;

	private $legacy_depends_adapter;

	public function __construct(
		Registry_Interface $registry,
		Dependency_Resolver $dependency_resolver,
		Legacy_Depends_Adapter $legacy_depends_adapter
	) {
		$this->registry = $registry;
		$this->dependency_resolver = $dependency_resolver;
		$this->legacy_depends_adapter = $legacy_depends_adapter;
	}

	public function build( array $participant_keys, $context ) {
		$participant_keys = $this->normalize_participant_keys( $participant_keys );
		$participant_keys = apply_filters(
			'elementor/dynamic_assets_manager/parsed_input',
			$participant_keys,
			$context
		);

		$handles = [];
		foreach ( $participant_keys as $owner_key ) {
			$this->register_legacy_fallback( $owner_key, $context );
			$handles = array_merge( $handles, $this->registry->get_owner_handles( $owner_key, $context ) );
		}

		$handles = array_values( array_unique( $handles ) );
		$ordered_handles = $this->dependency_resolver->resolve( $handles, $this->registry );

		$enqueue_handles = [];
		$deferred_by_trigger = [
			Defer_Trigger::DOCUMENT_READY => [],
			Defer_Trigger::WIDGET_INSERT => [],
		];
		$deferred_by_type = [
			Asset_Type::SCRIPT => [],
			Asset_Type::STYLE => [],
		];
		$client_payload = [];

		foreach ( $ordered_handles as $handle ) {
			$metadata = $this->registry->get_handle_metadata( $handle );

			if ( null === $metadata ) {
				$enqueue_handles[] = $handle;
				continue;
			}

			if ( Asset_Intent::ENQUEUE_DEFER === $metadata['intent'] ) {
				$trigger = $metadata['deferTrigger'] ?: Defer_Trigger::DOCUMENT_READY;
				if ( ! isset( $deferred_by_trigger[ $trigger ] ) ) {
					$deferred_by_trigger[ $trigger ] = [];
				}

				$deferred_by_trigger[ $trigger ][] = $handle;
				$deferred_by_type[ $metadata['type'] ][] = $handle;
				$client_payload[ $handle ] = Client_Payload::from_metadata( $metadata );
				continue;
			}

			$enqueue_handles[] = $handle;
		}

		$enqueue_handles = apply_filters(
			'elementor/dynamic_assets_manager/enqueue_list',
			array_values( array_unique( $enqueue_handles ) ),
			$context,
			$participant_keys
		);

		$deferred_by_trigger = apply_filters(
			'elementor/dynamic_assets_manager/deferred_buckets',
			$deferred_by_trigger,
			$context,
			$participant_keys
		);

		$client_payload = apply_filters(
			'elementor/dynamic_assets_manager/client_payload',
			$client_payload,
			$context,
			$participant_keys
		);

		$deferred_by_type = [
			Asset_Type::SCRIPT => array_values( array_unique( $deferred_by_type[ Asset_Type::SCRIPT ] ) ),
			Asset_Type::STYLE => array_values( array_unique( $deferred_by_type[ Asset_Type::STYLE ] ) ),
		];

		return [
			'enqueue_handles' => $enqueue_handles,
			'deferred_by_trigger' => $deferred_by_trigger,
			'deferred_by_type' => $deferred_by_type,
			'client_payload' => $client_payload,
		];
	}

	private function normalize_participant_keys( array $participant_keys ) {
		return array_values(
			array_unique(
				array_filter(
					array_map(
						static function( $owner_key ) {
							return is_string( $owner_key ) ? trim( $owner_key ) : '';
						},
						$participant_keys
					)
				)
			)
		);
	}

	private function register_legacy_fallback( $owner_key, $context ) {
		if ( ! empty( $this->registry->get_owner_handles( $owner_key, $context ) ) ) {
			return;
		}

		$legacy_assets = $this->legacy_depends_adapter->get_assets( $owner_key, $context );

		foreach ( $legacy_assets as $legacy_asset ) {
			try {
				$this->registry->register( $owner_key, $legacy_asset );
			} catch ( \Exception $exception ) {
				continue;
			}
		}
	}
}
