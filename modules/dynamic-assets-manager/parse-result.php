<?php
namespace Elementor\Modules\DynamicAssetsManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Parse_Result {
	private $participant_keys;

	private $is_empty;

	private $is_malformed;

	public function __construct( array $participant_keys, $is_empty = false, $is_malformed = false ) {
		$this->participant_keys = $participant_keys;
		$this->is_empty = (bool) $is_empty;
		$this->is_malformed = (bool) $is_malformed;
	}

	public static function empty_document() {
		return new self( [], true, false );
	}

	public static function malformed_document() {
		return new self( [], false, true );
	}

	public function get_participant_keys() {
		return $this->participant_keys;
	}

	public function is_empty() {
		return $this->is_empty;
	}

	public function is_malformed() {
		return $this->is_malformed;
	}
}
