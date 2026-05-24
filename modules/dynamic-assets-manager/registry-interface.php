<?php
namespace Elementor\Modules\DynamicAssetsManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface Registry_Interface {
	public function register( $owner_key, array $metadata );

	public function get_handle_metadata( $handle );

	public function get_owner_handles( $owner_key, $context );
}
