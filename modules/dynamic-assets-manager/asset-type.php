<?php
namespace Elementor\Modules\DynamicAssetsManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Asset_Type {
	const SCRIPT = 'script';
	const STYLE = 'style';

	public static function is_valid( $type ) {
		return in_array( $type, [ self::SCRIPT, self::STYLE ], true );
	}
}
