<?php
namespace Elementor\Modules\DynamicAssetsManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Dependency_Resolver {
	private $registry;

	private $resolved = [];

	private $resolving = [];

	private $ordered_handles = [];

	public function resolve( array $handles, Registry_Interface $registry ) {
		$this->registry = $registry;
		$this->resolved = [];
		$this->resolving = [];
		$this->ordered_handles = [];

		foreach ( $handles as $handle ) {
			$this->resolve_handle( (string) $handle );
		}

		return $this->ordered_handles;
	}

	private function resolve_handle( $handle ) {
		if ( '' === $handle || isset( $this->resolved[ $handle ] ) ) {
			return;
		}

		if ( isset( $this->resolving[ $handle ] ) ) {
			return;
		}

		$this->resolving[ $handle ] = true;

		$metadata = $this->registry->get_handle_metadata( $handle );
		$deps = is_array( $metadata['deps'] ?? null ) ? $metadata['deps'] : [];

		$deps = apply_filters(
			'elementor/dynamic_assets_manager/dependency_graph',
			$deps,
			$handle,
			$metadata
		);

		foreach ( $deps as $dependency_handle ) {
			$this->resolve_handle( (string) $dependency_handle );
		}

		unset( $this->resolving[ $handle ] );
		$this->resolved[ $handle ] = true;
		$this->ordered_handles[] = $handle;
	}
}
