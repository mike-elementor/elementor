<?php
namespace Elementor\Modules\DynamicAssetsManager;

use Elementor\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Legacy_Depends_Adapter {
	public function get_assets( $owner_key, $context ) {
		$owner_key = (string) $owner_key;
		$context = (string) $context;

		$assets = apply_filters(
			'elementor/dynamic_assets_manager/legacy_owner_assets',
			[],
			$owner_key,
			$context
		);

		if ( ! empty( $assets ) ) {
			return $this->normalize_assets( $assets, $owner_key, $context );
		}

		return $this->get_widget_assets( $owner_key, $context );
	}

	private function get_widget_assets( $owner_key, $context ) {
		$widget_types = Plugin::$instance->widgets_manager->get_widget_types();

		if ( ! isset( $widget_types[ $owner_key ] ) ) {
			return [];
		}

		$widget = $widget_types[ $owner_key ];
		$assets = [];

		if ( method_exists( $widget, 'get_script_depends' ) ) {
			foreach ( (array) $widget->get_script_depends() as $handle ) {
				$assets[] = $this->build_asset_metadata( (string) $handle, Asset_Type::SCRIPT, $owner_key, $context );
			}
		}

		if ( method_exists( $widget, 'get_style_depends' ) ) {
			foreach ( (array) $widget->get_style_depends() as $handle ) {
				$assets[] = $this->build_asset_metadata( (string) $handle, Asset_Type::STYLE, $owner_key, $context );
			}
		}

		return array_values( array_filter( $assets ) );
	}

	private function normalize_assets( array $assets, $owner_key, $context ) {
		$normalized = [];

		foreach ( $assets as $asset ) {
			if ( ! is_array( $asset ) || empty( $asset['handle'] ) || empty( $asset['type'] ) ) {
				continue;
			}

			$handle = (string) $asset['handle'];
			$type = (string) $asset['type'];
			$defaults = $this->build_asset_metadata( $handle, $type, $owner_key, $context );
			if ( null === $defaults ) {
				continue;
			}

			$normalized[] = array_merge( $defaults, $asset, [
				'owner' => $owner_key,
				'context' => $context,
			] );
		}

		return $normalized;
	}

	private function build_asset_metadata( $handle, $type, $owner_key, $context ) {
		global $wp_scripts, $wp_styles;

		if ( '' === $handle || ! Asset_Type::is_valid( $type ) ) {
			return null;
		}

		$uri = '';
		$deps = [];

		if ( Asset_Type::SCRIPT === $type && isset( $wp_scripts->registered[ $handle ] ) ) {
			$uri = (string) $wp_scripts->registered[ $handle ]->src;
			$deps = (array) $wp_scripts->registered[ $handle ]->deps;
		}

		if ( Asset_Type::STYLE === $type && isset( $wp_styles->registered[ $handle ] ) ) {
			$uri = (string) $wp_styles->registered[ $handle ]->src;
			$deps = (array) $wp_styles->registered[ $handle ]->deps;
		}

		return [
			'handle' => $handle,
			'type' => $type,
			'uri' => $uri,
			'deps' => $deps,
			'intent' => Asset_Intent::ENQUEUE,
			'deferTrigger' => '',
			'owner' => $owner_key,
			'context' => $context,
		];
	}
}
