<?php
/**
 * PAPRO Helper Functions.
 */

namespace PremiumAddonsPro\Includes;

use Elementor\Utils;
use Elementor\Group_Control_Image_Size;
use Elementor\Control_Media;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Helper_Functions.
 */
class PAPRO_Helper {

	/**
	 * A list of safe tage for `validate_html_tag` method.
	 */
	const ALLOWED_HTML_WRAPPER_TAGS = array(
		'article',
		'aside',
		'div',
		'footer',
		'h1',
		'h2',
		'h3',
		'h4',
		'h5',
		'h6',
		'header',
		'main',
		'nav',
		'p',
		'section',
		'span',
	);

	/**
	 * Valide HTML Tag
	 *
	 * Validates an HTML tag against a safe allowed list.
	 *
	 * @param string $tag HTML tag.
	 *
	 * @return string
	 */
	public static function validate_html_tag( $tag ) {
		return in_array( strtolower( $tag ), self::ALLOWED_HTML_WRAPPER_TAGS, true ) ? $tag : 'div';
	}

	/**
	 * Get attachment image HTML.
	 *
	 * Retrieve the attachment image HTML code. Used to add custom classes.
	 *
	 * Note that some widgets use the same key for the media control that allows
	 * the image selection and for the image size control that allows the user
	 * to select the image size, in this case the third parameter should be null
	 * or the same as the second parameter. But when the widget uses different
	 * keys for the media control and the image size control, when calling this
	 * method you should pass the keys.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 *
	 * @param array  $settings       Control settings.
	 * @param string $image_size_key Optional. Settings key for image size.
	 *                               Default is `image`.
	 * @param string $image_key      Optional. Settings key for image. Default
	 *                               is null. If not defined uses image size key
	 *                               as the image key.
	 * @param string $classes Optional. Classes list to be added to the image HTML markup.
	 *
	 * @return string Image HTML.
	 */
	public static function get_attachment_image_html( $settings, $image_size_key = 'image', $image_key = null, $classes = '' ) {

		if ( ! $image_key ) {
			$image_key = $image_size_key;
		}

		$image = $settings[ $image_key ];

		$is_repeater = false;
		if ( isset( $settings[ $image_size_key . '_size' ] ) ) {
			$size = $settings[ $image_size_key . '_size' ];
		} else {
			// Used with get_image_data() method which is used with repeaters.
			$size        = isset( $image['image_size'] ) ? $image['image_size'] : 'full';
			$is_repeater = true;
		}

		$html = '';

		// If is the new version - with image size.
		$image_sizes = get_intermediate_image_sizes();

		$image_sizes[] = 'full';

		if ( ! empty( $image['id'] ) && ! wp_attachment_is_image( $image['id'] ) ) {
			$image['id'] = '';
		}

		// On static mode don't use WP responsive images.
		if ( ! empty( $image['id'] ) && in_array( $size, $image_sizes ) ) {
			$image_class = " attachment-$size size-$size " . esc_attr( $classes );
			$image_attr  = array(
				'class' => trim( $image_class ),
			);

			$html .= wp_get_attachment_image( $image['id'], $size, false, $image_attr );
		} else {

			// If repeater, then we should pass the data for the current image, not the settings object.
			$size_key = $is_repeater ? 'image' : $image_size_key;
			$source   = $is_repeater ? $image : $settings;

			$image_src = Group_Control_Image_Size::get_attachment_image_src( $image['id'], $size_key, $source );

			if ( ! $image_src && isset( $image['url'] ) ) {
				$image_src = $image['url'];
			}

			if ( ! empty( $image_src ) ) {
				$image_class_html = ! empty( $classes ) ? ' class="' . $classes . '"' : '';

				$html .= sprintf( '<img src="%s" title="%s" alt="%s"%s />', esc_attr( $image_src ), Control_Media::get_image_title( $image ), Control_Media::get_image_alt( $image ), $image_class_html );
			}
		}

		return Utils::print_wp_kses_extended( $html, array( 'image' ) );

	}
}
