<?php
namespace Elementor\Modules\DynamicAssetsManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Asset_Intent {
	const ENQUEUE = 'enqueue';
	const ENQUEUE_DEFER = 'enqueue/defer';

	public static function is_valid( $intent ) {
		return in_array( $intent, [ self::ENQUEUE, self::ENQUEUE_DEFER ], true );
	}
}
