<?php
namespace Elementor\Modules\DynamicAssetsManager;

use Elementor\Core\Base\Module as BaseModule;
use Elementor\Core\Experiments\Manager as Experiments_Manager;
use Elementor\Plugin;
use Elementor\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Module extends BaseModule {
	const EXPERIMENT_NAME = 'e_dynamic_assets_manager';
	const REGISTER_ASSETS_HOOK = 'elementor/dynamic_assets_manager/register_assets';
	const PARTICIPANT_KEYS_FILTER = 'elementor/dynamic_assets_manager/participant_keys';
	const PAYLOAD_READY_ACTION = 'elementor/dynamic_assets_manager/client_payload_ready';
	const LOADER_SCRIPT_HANDLE = 'e-dynamic-assets-loader';

	private const PRIORITY_FIRST = 0;

	private const PRIORITY_LAST = 999;

	private $resolved_context_data = [];

	private $participant_keys_provider;

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
		$this->participant_keys_provider = new Participant_Keys_Provider( new Document_Parser() );

		add_action( self::REGISTER_ASSETS_HOOK, [ new Widget_Asset_Registrar(), 'register' ], 10, 2 );

		add_filter( self::PARTICIPANT_KEYS_FILTER, [ $this, 'provide_participant_keys_from_document' ], 10, 2 );

		add_action( 'elementor/editor/before_enqueue_scripts', [ $this, 'before_enqueue_scripts_editor' ], self::PRIORITY_FIRST );
		add_action( 'elementor/editor/after_enqueue_scripts', [ $this, 'after_enqueue_scripts_editor' ], self::PRIORITY_LAST );
		add_action( 'elementor/editor/before_enqueue_styles', [ $this, 'before_enqueue_styles_editor' ], self::PRIORITY_FIRST );
		add_action( 'elementor/editor/after_enqueue_styles', [ $this, 'after_enqueue_styles_editor' ], self::PRIORITY_LAST );
		add_action( 'elementor/preview/enqueue_styles', [ $this, 'before_enqueue_scripts_editor_canvas' ], self::PRIORITY_FIRST );
		add_action( 'elementor/preview/enqueue_styles', [ $this, 'after_enqueue_scripts_editor_canvas' ], self::PRIORITY_LAST );
		add_action( 'elementor/preview/enqueue_scripts', [ $this, 'before_enqueue_scripts_editor_canvas' ], self::PRIORITY_FIRST );
		add_action( 'elementor/preview/enqueue_scripts', [ $this, 'after_enqueue_scripts_editor_canvas' ], self::PRIORITY_LAST );
	}

	public function before_enqueue_scripts_editor() {
		$this->before_enqueue_scripts( Context::EDITOR );
	}

	public function after_enqueue_scripts_editor() {
		$this->after_enqueue_scripts( Context::EDITOR );
	}

	public function before_enqueue_styles_editor() {
		$this->before_enqueue_scripts( Context::EDITOR );
	}

	public function after_enqueue_styles_editor() {
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

		if ( isset( $this->resolved_context_data[ $context ] ) ) {
			return;
		}

		$registry = new Registry();

		do_action( self::REGISTER_ASSETS_HOOK, $registry, $context );

		$participant_keys = apply_filters( self::PARTICIPANT_KEYS_FILTER, [], $context, $registry );
		$participant_keys = is_array( $participant_keys ) ? $participant_keys : [];

		$assets_manager = new Assets_Manager(
			$registry,
			new Dependency_Resolver(),
			new Legacy_Depends_Adapter()
		);

		$this->resolved_context_data[ $context ] = $assets_manager->build( $participant_keys, $context );

		do_action(
			self::PAYLOAD_READY_ACTION,
			$this->resolved_context_data[ $context ]['client_payload'],
			$context,
			$registry
		);
	}

	public function after_enqueue_scripts( $context ) {
		if ( ! in_array( $context, [ Context::EDITOR, Context::EDITOR_CANVAS ], true ) ) {
			return;
		}

		if ( ! isset( $this->resolved_context_data[ $context ] ) ) {
			$this->before_enqueue_scripts( $context );
		}

		if ( ! isset( $this->resolved_context_data[ $context ] ) ) {
			return;
		}

		$this->prune_deferred_handles( Asset_Type::SCRIPT, $this->resolved_context_data[ $context ]['deferred_by_type'] );
		$this->prune_deferred_handles( Asset_Type::STYLE, $this->resolved_context_data[ $context ]['deferred_by_type'] );

		if ( Context::EDITOR === $context ) {
			$this->enqueue_client_script( $this->resolved_context_data[ $context ]['client_payload'] );
		}
	}

	private function enqueue_client_script( array $client_payload ) {
		$min_suffix = Utils::is_script_debug() ? '' : '.min';

		wp_register_script(
			self::LOADER_SCRIPT_HANDLE,
			ELEMENTOR_ASSETS_URL . "js/dynamic-assets-loader{$min_suffix}.js",
			[ 'elementor-editor' ],
			ELEMENTOR_VERSION,
			true
		);

		wp_enqueue_script( self::LOADER_SCRIPT_HANDLE );

		Utils::print_js_config( self::LOADER_SCRIPT_HANDLE, 'elementorDynamicAssets', $client_payload );
	}

	private function prune_deferred_handles( $asset_type, array $deferred_by_type ) {
		if ( ! isset( $deferred_by_type[ $asset_type ] ) || empty( $deferred_by_type[ $asset_type ] ) ) {
			return;
		}

		foreach ( $deferred_by_type[ $asset_type ] as $handle ) {
			if ( Asset_Type::SCRIPT === $asset_type ) {
				wp_dequeue_script( $handle );
				$this->remove_handle_from_dependencies_queue( wp_scripts(), $handle );
			}

			if ( Asset_Type::STYLE === $asset_type ) {
				wp_dequeue_style( $handle );
				$this->remove_handle_from_dependencies_queue( wp_styles(), $handle );
			}
		}
	}

	public function provide_participant_keys_from_document( array $keys, $context ) {
		return $this->participant_keys_provider->provide( $keys, $context );
	}

	private function remove_handle_from_dependencies_queue( \WP_Dependencies $dependencies, $handle ) {
		foreach ( [ 'queue', 'to_do', 'done' ] as $key ) {
			if ( ! isset( $dependencies->$key ) || ! is_array( $dependencies->$key ) ) {
				continue;
			}

			$dependencies->$key = array_values(
				array_filter(
					$dependencies->$key,
					static function( $queued_handle ) use ( $handle ) {
						return $queued_handle !== $handle;
					}
				)
			);
		}
	}
}
