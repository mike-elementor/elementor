<?php
namespace Elementor\Modules\DynamicAssetsManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Defer_Trigger {
	const DOCUMENT_READY = 'document_ready';
	const WIDGET_INSERT = 'widget_insert';

	public static function is_valid( $trigger ) {
		return in_array( $trigger, [ self::DOCUMENT_READY, self::WIDGET_INSERT ], true );
	}
}
