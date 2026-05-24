<?php
namespace Elementor\Modules\DynamicAssetsManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Client_Payload {
	public static function from_metadata( array $metadata ) {
		return [
			'uri' => $metadata['uri'] ?? '',
			'type' => $metadata['type'] ?? '',
			'deps' => $metadata['deps'] ?? [],
			'intent' => $metadata['intent'] ?? '',
			'deferTrigger' => $metadata['deferTrigger'] ?? '',
			'owner' => $metadata['owner'] ?? '',
			'context' => $metadata['context'] ?? '',
		];
	}
}
