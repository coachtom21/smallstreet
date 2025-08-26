<?php
/**
 * Class: Module
 * Name: Global Cursor
 * Slug: premium-global-cursor
 */

namespace PremiumAddonsPro\Modules\PremiumGlobalCursor;

// Elementor Classes.
use Elementor\Controls_Manager;
use Elementor\Utils;
use Elementor\Control_Media;
use Elementor\Group_Control_Border;
use Elementor\Core\Kits\Documents\Tabs\Global_Colors;
use Elementor\Core\Kits\Documents\Tabs\Global_Typography;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Group_Control_Background;

// Premium Addons Classes.
use PremiumAddons\Admin\Includes\Admin_Helper;
use PremiumAddons\Includes\Helper_Functions;
use PremiumAddonsPro\Base\Module_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // If this file is called directly, abort.
}

/**
 * Class Module For Premium Global Cursor Addon.
 */
class Module extends Module_Base {

	/**
	 * Load Script
	 *
	 * @var $load_script
	 */
	private static $load_script = null;

	/**
	 * Class Constructor Funcion.
	 */
	public function __construct() {

		parent::__construct();

		$modules = Admin_Helper::get_enabled_elements();

		$global_cursor = $modules['premium-global-cursor'];

		if ( ! $global_cursor ) {
			return;
		}

		// Enqueue the required CSS/JS file.
		add_action( 'elementor/preview/enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'elementor/preview/enqueue_styles', array( $this, 'enqueue_styles' ) );

		// Creates Premium Global Cursor tab at the end of Advanced tab.
		add_action( 'elementor/element/section/section_advanced/after_section_end', array( $this, 'register_controls' ), 10 );
		add_action( 'elementor/element/column/section_advanced/after_section_end', array( $this, 'register_controls' ), 10 );
		add_action( 'elementor/element/common/_section_style/after_section_end', array( $this, 'register_controls' ), 10 );

		// Editor Hooks.
		add_action( 'elementor/section/print_template', array( $this, 'print_template' ), 10, 2 );
		add_action( 'elementor/column/print_template', array( $this, 'print_template' ), 10, 2 );
		add_action( 'elementor/widget/print_template', array( $this, 'print_template' ), 10, 2 );

		// Frontend Hooks.
		add_action( 'elementor/frontend/section/before_render', array( $this, 'before_render' ) );
		add_action( 'elementor/frontend/column/before_render', array( $this, 'before_render' ) );
		add_action( 'elementor/widget/before_render_content', array( $this, 'before_render' ), 10, 1 );

		add_action( 'elementor/frontend/before_render', array( $this, 'check_script_enqueue' ) );

		if ( version_compare( PREMIUM_ADDONS_VERSION, '4.8.5', '>' ) ) {
			if ( Helper_Functions::check_elementor_experiment( 'container' ) ) {
				add_action( 'elementor/element/container/section_layout/after_section_end', array( $this, 'register_controls' ), 10 );
				add_action( 'elementor/container/print_template', array( $this, 'print_template' ), 10, 2 );
				add_action( 'elementor/frontend/container/before_render', array( $this, 'before_render' ) );
			}
		}

	}

	/**
	 * Enqueue scripts.
	 *
	 * Registers required dependencies for the extension and enqueues them.
	 *
	 * @since 1.6.5
	 * @access public
	 */
	public static function enqueue_scripts() {

		if ( ! wp_script_is( 'elementor-waypoints', 'enqueued' ) ) {
			wp_enqueue_script( 'elementor-waypoints' );
		}

		if ( ! wp_script_is( 'lottie-js', 'enqueued' ) ) {
			wp_enqueue_script( 'lottie-js' );
		}

		if ( ! wp_script_is( 'pa-tweenmax', 'enqueued' ) ) {
			wp_enqueue_script( 'pa-tweenmax' );
		}

		if ( ! wp_script_is( 'pa-cursor', 'enqueued' ) ) {
			wp_enqueue_script( 'pa-cursor' );
		}
	}

	/**
	 * Enqueue styles.
	 *
	 * Registers required dependencies for the extension and enqueues them.
	 *
	 * @since 2.6.5
	 * @access public
	 */
	public static function enqueue_styles() {

		if ( ! wp_style_is( 'pa-global', 'enqueued' ) ) {
			wp_enqueue_style( 'pa-global' );
		}

	}

	/**
	 * Register Global Cursor controls.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param object $element for current element.
	 */
	public function register_controls( $element ) {

		$element->start_controls_section(
			'section_premium_cursor',
			array(
				'label' => sprintf( '<i class="pa-extension-icon pa-dash-icon"></i> %s', __( 'Custom Mouse Cursor', 'premium-addons-pro' ) ),
				'tab'   => Controls_Manager::TAB_ADVANCED,
			)
		);

		$element->add_control(
			'premium_global_cursor_switcher',
			array(
				'label'        => __( 'Enable Custom Mouse Cursor', 'premium-addons-pro' ),
				'type'         => Controls_Manager::SWITCHER,
				'prefix_class' => 'premium-gCursor-',
				'render_type'  => 'template',
			)
		);

		$doc_link = Helper_Functions::get_campaign_link( 'https://premiumaddons.com/docs/elementor-custom-mouse-cursor-addon-tutorial/', 'editor-page', 'wp-editor', 'get-support' );

		$element->add_control(
			'pa_custom_cursor_notice',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => '<a href="' . esc_url( $doc_link ) . '" target="_blank">' . __( 'How to use Premium Custom Mouse Cursor for Elementor »', 'premium-addons-pro' ) . '</a>',
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
				'condition'       => array(
					'premium_global_cursor_switcher' => 'yes',
				),
			)
		);

		$element->add_control(
			'global_cursor_notice',
			array(
				'raw'             => __( 'It\'s recommend to use Elementor Navigator to select elements when Global Cursor is enabled.', 'premium-addons-pro' ),
				'type'            => Controls_Manager::RAW_HTML,
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-warning',
				'condition'       => array(
					'premium_global_cursor_switcher' => 'yes',
				),
			)
		);

		$element->add_control(
			'pa_cursor_type',
			array(
				'label'        => __( 'Type', 'premium-addons-pro' ),
				'type'         => Controls_Manager::SELECT,
				'render_type'  => 'template',
				'prefix_class' => 'premium-cursor-',
				'options'      => array(
					'icon'   => __( 'Icon', 'premium-addons-pro' ),
					'image'  => __( 'Image', 'premium-addons-pro' ),
					'lottie' => __( 'Lottie', 'premium-addons-pro' ),
					'fimage' => __( 'Follow Image', 'premium-addons-pro' ),
					'ftext'  => __( 'Follow Text', 'premium-addons-pro' ),
				),
				'default'      => 'icon',
				'condition'    => array(
					'premium_global_cursor_switcher' => 'yes',
				),
			)
		);

		$element->add_control(
			'pa_cursor_pulse',
			array(
				'label'       => __( 'Pulse Effect', 'premium-addons-pro' ),
				'type'        => Controls_Manager::SWITCHER,
				'render_type' => 'template',
				'condition'   => array(
					'premium_global_cursor_switcher' => 'yes',
					'pa_cursor_type'                 => array( 'icon', 'image' ),
					'pa_cursor_buzz!'                => 'yes',
				),
			)
		);

		$element->add_control(
			'pa_cursor_buzz',
			array(
				'label'       => __( 'Buzz Effect', 'premium-addons-pro' ),
				'type'        => Controls_Manager::SWITCHER,
				'render_type' => 'template',
				'condition'   => array(
					'premium_global_cursor_switcher' => 'yes',
					'pa_cursor_type'                 => array( 'icon', 'image' ),
					'pa_cursor_pulse!'               => 'yes',
				),
			)
		);

		$element->add_control(
			'pa_cursor_icon',
			array(
				'label'     => __( 'Choose Icon', 'premium-addons-pro' ),
				'type'      => Controls_Manager::ICONS,
				'default'   => array(
					'value'   => 'fas fa-mouse-pointer',
					'library' => 'solid',
				),
				'condition' => array(
					'premium_global_cursor_switcher' => 'yes',
					'pa_cursor_type'                 => 'icon',
				),
			)
		);

		$element->add_control(
			'pa_cursor_img',
			array(
				'label'     => __( 'Choose Image', 'premium-addons-pro' ),
				'type'      => Controls_Manager::MEDIA,
				'default'   => array(
					'url' => Utils::get_placeholder_image_src(),
				),
				'condition' => array(
					'premium_global_cursor_switcher' => 'yes',
					'pa_cursor_type'                 => array( 'image', 'fimage' ),
				),
			)
		);

		$element->add_control(
			'pa_cursor_ftext',
			array(
				'label'       => __( 'Follow Text', 'premium-addons-pro' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Premium Follow Text', 'premium-addons-pro' ),
				'placeholder' => __( 'premium follow text', 'premium-addons-pro' ),
				'condition'   => array(
					'premium_global_cursor_switcher' => 'yes',
					'pa_cursor_type'                 => array( 'ftext' ),
				),
			)
		);

		$element->add_responsive_control(
			'pa_cursor_xpos',
			array(
				'label'       => __( 'X Position (%)', 'premium-addons-pro' ),
				'type'        => Controls_Manager::SLIDER,
				'render_type' => 'template',
				'range'       => array(
					'px' => array(
						'min' => -50,
						'max' => 50,
					),
				),
				'condition'   => array(
					'premium_global_cursor_switcher' => 'yes',
					'pa_cursor_type'                 => array( 'fimage', 'ftext' ),
				),
			)
		);

		$element->add_responsive_control(
			'pa_cursor_ypos',
			array(
				'label'       => __( 'Y Position (%)', 'premium-addons-pro' ),
				'type'        => Controls_Manager::SLIDER,
				'render_type' => 'template',
				'range'       => array(
					'px' => array(
						'min' => -50,
						'max' => 50,
					),
				),
				'condition'   => array(
					'premium_global_cursor_switcher' => 'yes',
					'pa_cursor_type'                 => array( 'fimage', 'ftext' ),
				),
			)
		);

		$element->add_control(
			'pa_cursor_trans',
			array(
				'label'       => __( 'Follow Delay (s)', 'premium-addons-pro' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px' ),
				'range'       => array(
					'px' => array(
						'min'  => 0.3,
						'max'  => 10,
						'step' => 0.1,
					),
				),
				'default'     => array(
					'unit' => 'px',
					'size' => 0.3,
				),
				'description' => __( 'Default is 0.3s', 'premium-addons-pro' ),
				'condition'   => array(
					'premium_global_cursor_switcher' => 'yes',
					'pa_cursor_type'                 => array( 'fimage', 'ftext' ),
				),
			)
		);

		$element->add_control(
			'pa_cursor_lottie_url',
			array(
				'label'       => __( 'Animation JSON URL', 'premium-addons-pro' ),
				'type'        => Controls_Manager::TEXT,
				'dynamic'     => array( 'active' => true ),
				'description' => 'Get JSON code URL from <a href="https://lottiefiles.com/" target="_blank">here</a>',
				'label_block' => true,
				'condition'   => array(
					'premium_global_cursor_switcher' => 'yes',
					'pa_cursor_type'                 => 'lottie',
				),
			)
		);

		$element->add_control(
			'pa_cursor_loop',
			array(
				'label'        => __( 'Loop', 'premium-addons-pro' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'true',
				'default'      => 'true',
				'condition'    => array(
					'premium_global_cursor_switcher' => 'yes',
					'pa_cursor_type'                 => 'lottie',
				),
			)
		);

		$element->add_control(
			'pa_cursor_reverse',
			array(
				'label'        => __( 'Reverse', 'premium-addons-pro' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'true',
				'condition'    => array(
					'premium_global_cursor_switcher' => 'yes',
					'pa_cursor_type'                 => 'lottie',
				),
			)
		);

		$element->add_control(
			'pa_cursor_div',
			array(
				'type'      => Controls_Manager::DIVIDER,
				'condition' => array(
					'premium_global_cursor_switcher' => 'yes',
				),
			)
		);

		$element->add_responsive_control(
			'pa_cursor_size',
			array(
				'label'      => __( 'Size', 'premium-addons-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em' ),
				'default'    => array(
					'size' => 20,
				),
				'range'      => array(
					'px' => array(
						'max' => 500,
						'min' => 0,
					),
				),
				'condition'  => array(
					'premium_global_cursor_switcher' => 'yes',
					'pa_cursor_type!'                => 'ftext',
				),
				'selectors'  => array(
					'{{WRAPPER}}.premium-cursor-icon .premium-global-cursor-{{ID}} i' => 'font-size: {{SIZE}}{{UNIT}};line-height: {{SIZE}}{{UNIT}};',

					'{{WRAPPER}}.premium-cursor-icon .premium-global-cursor-{{ID}} i,
					{{WRAPPER}}.premium-cursor-image .premium-global-cursor-{{ID}},
					{{WRAPPER}}.premium-cursor-fimage .premium-global-cursor-{{ID}},
					{{WRAPPER}}.premium-cursor-lottie .premium-global-cursor-{{ID}} .premium-cursor-lottie-icon,
					{{WRAPPER}}.premium-cursor-icon .premium-global-cursor-{{ID}} .premium-cursor-icon-svg' => 'height: {{SIZE}}{{UNIT}}; width: {{SIZE}}{{UNIT}};',

				),
			)
		);

		$element->add_control(
			'pa_cursor_color',
			array(
				'label'     => __( 'Color', 'premium-addons-pro' ),
				'type'      => Controls_Manager::COLOR,
				'global'    => array(
					'default' => Global_Colors::COLOR_PRIMARY,
				),
				'condition' => array(
					'premium_global_cursor_switcher' => 'yes',
					'pa_cursor_type'                 => array( 'icon', 'ftext' ),
				),
				'selectors' => array(
					'{{WRAPPER}} .premium-global-cursor-{{ID}}' => 'color: {{VALUE}}; fill: {{VALUE}};',
				),
			)
		);

		$element->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'      => 'pa_cursor_bgColor',
				'types'     => array( 'classic', 'gradient' ),
				'condition' => array(
					'premium_global_cursor_switcher' => 'yes',
				),
				'selector'  => '{{WRAPPER}} .premium-global-cursor-{{ID}}',
			)
		);

		$element->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			array(
				'name'      => 'pa_cursor_shadow',
				'condition' => array(
					'premium_global_cursor_switcher' => 'yes',
					'pa_cursor_type'                 => 'ftext',
				),
				'selector'  => '{{WRAPPER}} .premium-global-cursor-{{ID}}',
			)
		);

		$element->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'      => 'pa_cursor_typo',
				'global'    => array(
					'default' => Global_Typography::TYPOGRAPHY_PRIMARY,
				),
				'condition' => array(
					'premium_global_cursor_switcher' => 'yes',
					'pa_cursor_type'                 => 'ftext',
				),
				'selector'  => '{{WRAPPER}}.premium-cursor-ftext .premium-global-cursor-{{ID}} .premium-cursor-follow-text',
			)
		);

		$element->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'      => 'pa_cursor_border',
				'selector'  => '{{WRAPPER}} .premium-global-cursor-{{ID}}',
				'separator' => 'before',
				'condition' => array(
					'premium_global_cursor_switcher' => 'yes',
				),
			)
		);

		$element->add_control(
			'pa_cursor_border_rad',
			array(
				'label'      => __( 'Border Radius', 'premium-addons-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'em' ),
				'condition'  => array(
					'premium_global_cursor_switcher' => 'yes',
					'pa_cursor_adv_radius!'          => 'yes',
				),
				'selectors'  => array(
					'{{WRAPPER}} .premium-global-cursor-{{ID}}, {{WRAPPER}} .premium-global-cursor-{{ID}} img' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$element->add_control(
			'pa_cursor_adv_radius',
			array(
				'label'       => __( 'Advanced Border Radius', 'premium-addons-pro' ),
				'type'        => Controls_Manager::SWITCHER,
				'description' => __( 'Apply custom radius values. Get the radius value from ', 'premium-addons-pro' ) . '<a href="https://9elements.github.io/fancy-border-radius/" target="_blank">here</a>',
				'condition'   => array(
					'premium_global_cursor_switcher' => 'yes',
				),
			)
		);

		$element->add_control(
			'pa_cursor_adv_radius_value',
			array(
				'label'     => __( 'Border Radius', 'premium-addons-pro' ),
				'type'      => Controls_Manager::TEXT,
				'dynamic'   => array( 'active' => true ),
				'selectors' => array(
					'{{WRAPPER}} .premium-global-cursor-{{ID}}, {{WRAPPER}} .premium-global-cursor-{{ID}} img' => 'border-radius: {{VALUE}};',
				),
				'condition' => array(
					'pa_cursor_adv_radius' => 'yes',
				),
			)
		);

		$element->add_responsive_control(
			'pa_cursor_rotate',
			array(
				'label'      => __( 'Rotate (Degrees)', 'premium-addons-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'deg' ),
				'default'    => array(
					'unit' => 'deg',
					'size' => 0,
				),
				'separator'  => 'before',
				'selectors'  => array(
					'{{WRAPPER}} .premium-global-cursor-{{ID}}' => 'transform: rotate({{SIZE}}deg)',
				),
				'condition'  => array(
					'premium_global_cursor_switcher' => 'yes',
				),
			)
		);

		$element->add_responsive_control(
			'pa_cursor_padding',
			array(
				'label'      => __( 'Padding', 'premium-addons-pro' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'condition'  => array(
					'premium_global_cursor_switcher' => 'yes',
				),
				'selectors'  => array(
					'{{WRAPPER}} .premium-global-cursor-{{ID}}' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$element->end_controls_section();
	}

	/**
	 * Render Global Cursor output in the editor.
	 *
	 * Written as a Backbone JavaScript template and used to generate the live preview.
	 *
	 * @since 2.2.8
	 * @access public
	 *
	 * @param object $template for current template.
	 * @param object $element for current element.
	 */
	public function print_template( $template, $element ) {

		if ( ! $template && 'widget' === $element->get_type() ) {
			return;
		}

		$old_template = $template;
		ob_start();
		?>
		<#
			var isEnabled = 'yes' === settings.premium_global_cursor_switcher ? true : false;

			if ( isEnabled ) {

				var cursorType = settings.pa_cursor_type,
					pulse = ['icon', 'image'].includes(cursorType) && 'yes' === settings.pa_cursor_pulse ? ' premium-pulse-yes ' : '',
					buzz = ['icon', 'image'].includes(cursorType) && 'yes' === settings.pa_cursor_buzz ? ' premium-buzz-yes ' : '',
					delay = ['ftext', 'fimage'].includes(cursorType) && '' !== settings.pa_cursor_trans.size ? settings.pa_cursor_trans.size : 0.01,
					elementSettings = {},
					cursorSettings = {
						cursorType : cursorType,
						delay: delay,
						pulse: pulse,
						buzz: buzz
					};

				if ( 'icon' === cursorType ) {
					elementSettings = settings.pa_cursor_icon;

				} else if ( 'image' === cursorType || 'fimage' === cursorType ) {
					elementSettings.url = settings.pa_cursor_img.url;

					if ( 'fimage' === cursorType ) {
						elementSettings.xpos = settings.pa_cursor_xpos.size;
						elementSettings.ypos = settings.pa_cursor_ypos.size;
					}

				} else if ( 'ftext' === cursorType ) {
					elementSettings.text = settings.pa_cursor_ftext;
					elementSettings.xpos = settings.pa_cursor_xpos.size;
					elementSettings.ypos = settings.pa_cursor_ypos.size;

				} else if ( 'lottie' === cursorType ) {
					elementSettings.url     = settings.pa_cursor_lottie_url;
					elementSettings.loop    = settings.pa_cursor_loop;
					elementSettings.reverse = settings.pa_cursor_reverse;
				}

				cursorSettings.elementSettings = elementSettings;

				view.addRenderAttribute( 'cursor_data', {
					'id': 'premium-global-cursor-' + view.getID(),
					'class': 'premium-global-cursor-wrapper',
					'data-gcursor': JSON.stringify( cursorSettings )
				});
		#>
				<div {{{ view.getRenderAttributeString( 'cursor_data' ) }}}></div>
		<#
			}
		#>

		<?php

			$slider_content = ob_get_contents();
			ob_end_clean();
			$template = $slider_content . $old_template;
			return $template;
	}


	/**
	 * Render Global Cursor output on the frontend.
	 *
	 * Written in PHP and used to collect cursor settings and add it as an element attribute.
	 *
	 * @access public
	 * @param object $element for current element.
	 */
	public function before_render( $element ) {

		$type = $element->get_type();

		$id = $element->get_id();

		$settings = $element->get_settings_for_display();

		$cursor_switcher = $settings['premium_global_cursor_switcher'];

		if ( 'yes' === $cursor_switcher ) {

			$cursor_type = $settings['pa_cursor_type'];

			$pulse = 'yes' === $settings['pa_cursor_pulse'] ? ' premium-pulse-yes ' : '';
			$buzz  = 'yes' === $settings['pa_cursor_buzz'] ? ' premium-buzz-yes ' : '';

			$element_settings = array();

			$cursor_settings = array(
				'cursorType' => $cursor_type,
				'delay'      => isset( $settings['pa_cursor_trans']['size'] ) && in_array( $cursor_type, array( 'fimage', 'ftext' ), true ) ? $settings['pa_cursor_trans']['size'] : 0.01,
				'pulse'      => $pulse,
				'buzz'       => $buzz,
			);

			if ( 'icon' === $cursor_type ) {
				$element_settings = $settings['pa_cursor_icon'];

			} elseif ( 'image' === $cursor_type || 'fimage' === $cursor_type ) {
				$element_settings['url'] = $settings['pa_cursor_img']['url'];
				$element_settings['alt'] = Control_Media::get_image_alt( $settings['pa_cursor_img'] );

				if ( 'fimage' === $cursor_type ) {
					$element_settings['xpos'] = $settings['pa_cursor_xpos']['size'];
					$element_settings['ypos'] = $settings['pa_cursor_ypos']['size'];
				}
			} elseif ( 'ftext' === $cursor_type ) {
				$element_settings['text'] = $settings['pa_cursor_ftext'];
				$element_settings['xpos'] = $settings['pa_cursor_xpos']['size'];
				$element_settings['ypos'] = $settings['pa_cursor_ypos']['size'];

			} elseif ( 'lottie' === $cursor_type ) {
				$element_settings['url']     = esc_url( $settings['pa_cursor_lottie_url'] );
				$element_settings['loop']    = $settings['pa_cursor_loop'];
				$element_settings['reverse'] = $settings['pa_cursor_reverse'];

			}

			$cursor_settings['elementSettings'] = $element_settings;

			$element->add_render_attribute( '_wrapper', 'data-gcursor', wp_json_encode( $cursor_settings ) );

			if ( 'widget' === $type && \Elementor\Plugin::instance()->editor->is_edit_mode() ) {
				?>
				<div id='premium-global-cursor-temp-<?php echo esc_html( $id ); ?>' data-gcursor='<?php echo wp_json_encode( $cursor_settings ); ?>'></div>
				<?php
			}
		}
	}

	/**
	 * Check Script Enqueue
	 *
	 * Check if the script files should be loaded.
	 *
	 * @since 2.6.3
	 * @access public
	 *
	 * @param object $element for current element.
	 */
	public function check_script_enqueue( $element ) {

		if ( self::$load_script ) {
			return;
		}

		if ( 'yes' === $element->get_settings_for_display( 'premium_global_cursor_switcher' ) ) {

			$this->enqueue_styles();
			$this->enqueue_scripts();

			self::$load_script = true;

			remove_action( 'elementor/frontend/before_render', array( $this, 'check_script_enqueue' ) );
		}

	}
}
