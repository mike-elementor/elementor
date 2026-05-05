<?php
namespace Elementor\Modules\DynamicAssetsManager;

use Elementor\Core\Base\Module as BaseModule;
use Elementor\Core\Experiments\Manager as Experiments_Manager;
use Elementor\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Module extends BaseModule {
	const EXPERIMENT_NAME = 'e_dynamic_assets_manager';

	private const PRIORITY_FIRST = 0;

	private const PRIORITY_LAST = 999;

	public function get_name() {
		return 'dynamic-assets-manager';
	}

	public static function get_experimental_data() {
		return [
			'name' => self::EXPERIMENT_NAME,
			'title' => esc_html__( 'Dynamic Assets Manager', 'elementor' ),
			'description' => esc_html__( 'Enable dynamic assets manager bootstrap integration points.', 'elementor' ),
			'hidden' => true,
			'default' => Experiments_Manager::STATE_INACTIVE,
			'release_status' => Experiments_Manager::RELEASE_STATUS_ALPHA,
		];
	}

	public function __construct() {
		parent::__construct();

		if ( ! Plugin::$instance->experiments->is_feature_active( self::EXPERIMENT_NAME ) ) {
			return;
		}

		$this->register_hooks();
	}

	private function register_hooks() {
		add_action( 'elementor/editor/before_enqueue_scripts', [ $this, 'before_enqueue_scripts_editor' ], self::PRIORITY_FIRST );
		add_action( 'elementor/editor/after_enqueue_scripts', [ $this, 'after_enqueue_scripts_editor' ], self::PRIORITY_LAST );
		add_action( 'elementor/preview/enqueue_scripts', [ $this, 'before_enqueue_scripts_editor_canvas' ], self::PRIORITY_FIRST );
		add_action( 'elementor/preview/enqueue_scripts', [ $this, 'after_enqueue_scripts_editor_canvas' ], self::PRIORITY_LAST );
	}

	public function before_enqueue_scripts_editor() {
		$this->before_enqueue_scripts( Context::EDITOR );
	}

	public function after_enqueue_scripts_editor() {
		$this->after_enqueue_scripts( Context::EDITOR );
	}

	public function before_enqueue_scripts_editor_canvas() {
		$this->before_enqueue_scripts( Context::EDITOR_CANVAS );
	}

	public function after_enqueue_scripts_editor_canvas() {
		$this->after_enqueue_scripts( Context::EDITOR_CANVAS );
	}

	public function before_enqueue_scripts( $context ) {
		if ( ! in_array( $context, [ Context::EDITOR, Context::EDITOR_CANVAS ], true ) ) {
			return;
		}
	}

	public function after_enqueue_scripts( $context ) {
		if ( ! in_array( $context, [ Context::EDITOR, Context::EDITOR_CANVAS ], true ) ) {
			return;
		}
	}
}
