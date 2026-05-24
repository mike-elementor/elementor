<?php
namespace Elementor\Modules\DynamicAssetsManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Widget_Asset_Registrar {
	public function register( Registry_Interface $registry, string $context ): void {
		$this->register_image_carousel( $registry, $context );
	}

	private function register_image_carousel( Registry_Interface $registry, string $context ): void {
		$swiper_src   = wp_scripts()->registered['swiper']->src ?? '';
		$e_swiper_src = wp_styles()->registered['e-swiper']->src ?? '';
		$carousel_src = wp_styles()->registered['widget-image-carousel']->src ?? '';

		if ( $swiper_src ) {
			$registry->register( 'image-carousel', [
				'handle'  => 'swiper',
				'type'    => Asset_Type::SCRIPT,
				'uri'     => $swiper_src,
				'deps'    => [],
				'intent'  => Asset_Intent::ENQUEUE,
				'context' => '*',
			] );
		}

		if ( $e_swiper_src ) {
			$registry->register( 'image-carousel', [
				'handle'       => 'e-swiper',
				'type'         => Asset_Type::STYLE,
				'uri'          => $e_swiper_src,
				'deps'         => [],
				'intent'       => Asset_Intent::ENQUEUE_DEFER,
				'deferTrigger' => Defer_Trigger::WIDGET_INSERT,
				'context'      => '*',
			] );
		}

		if ( $carousel_src ) {
			$registry->register( 'image-carousel', [
				'handle'       => 'widget-image-carousel',
				'type'         => Asset_Type::STYLE,
				'uri'          => $carousel_src,
				'deps'         => [],
				'intent'       => Asset_Intent::ENQUEUE_DEFER,
				'deferTrigger' => Defer_Trigger::WIDGET_INSERT,
				'context'      => '*',
			] );
		}
	}
}
