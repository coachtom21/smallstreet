<?php

class Dongtrader_qr_metas
{

	public $generators;

	public $type;


	public function __construct()
	{
		if (is_admin()) {
			add_action('load-post.php',     array($this, 'init_metabox'));
			add_action('load-post-new.php', array($this, 'init_metabox'));
			add_action( 'woocommerce_product_after_variable_attributes', array($this, 'dong_add_qr_field_to_variations'), 10, 3 );
			add_action( 'woocommerce_save_product_variation', array($this,'dong_save_qrfield_variations'), 10, 2 );
			add_filter( 'woocommerce_available_variation',array($this, 'dong_add_qr_field_variation_data'));
			$this->generators = array(
				array(
					'slug' => '_product_qr_codes',
					'title' => __('Products QR Code', 'cpm-dongtrader'),
					'buttonClass' => 'generate-product-qr',
					'callback' => 'render_metabox_product_qr_code',

				),
				array(
					'slug' => '_product-qr-direct-checkouts',
					'title' => __('Products Direct Checkout QR Code', 'cpm-dongtrader'),
					'buttonClass' => 'generate-product-qr-direct-checkout',
					'callback' => 'render_metabox_direct_checkout'
				),
				array(
					'slug' => '_glassfrog_checkbox',
					'title' => __('Save Customer Details To Glassfrog API ', 'cpm-dongtrader'),
					'buttonClass' => false,
					'callback' => 'render_metabox_glassfrog_checkbox'
				)
		
			);
		}
	}



	public function init_metabox()
	{
		add_action('add_meta_boxes', array($this, 'add_metabox'));
		add_action('save_post',      array($this, 'save_metabox'), 10, 2);
		
	}
	public function dong_add_qr_field_variation_data($variations ){
		$variations['variable_product'] = '<div class="woocommerce_custom_field">Custom Field: <span>' . get_post_meta( $variations[ 'variation_id' ], 'variable_product_qr_data', true ) . '</span></div>';
   		return $variations;
	}

	public function dong_save_qrfield_variations($variation_id, $i){

		
		if (isset($_POST['variable_product_qr_data'][$variation_id])) {
			update_post_meta($variation_id, 'variable_product_qr_data', sanitize_text_field($_POST['variable_product_qr_data'][$variation_id]));
		 }
	}

	// Custom fields html for variable products

	public function dong_add_qr_field_to_variations($loop, $variation_data, $variation){
		$product_id 	= wp_get_post_parent_id($variation->ID);
		$qr_datas 	= get_post_meta($variation->ID, 'variable_product_qr_data', true);
		$html_decode 	= htmlspecialchars_decode($qr_datas);
		$decoded_json 	= json_decode($html_decode,true);
		echo '<div id="dong-qr-components'.$loop.'" class="dong-qr-components dong-qr-components-var">';
		if(!empty($qr_datas)){
				echo '<div class="qr-img-container-var">';
				//qr image
				echo '<img src="' . $decoded_json['qr_image_url'] . '' . '" alt="" width="100" height="100">';
				echo '</div>';
				//url copy
				echo '<div class="qr-urlbtn-container-var">';
				echo '<button data-url="'.$decoded_json['qr_image_url'] . '" class="button-primary button-large url-copy" >Copy QR URL</button>';
				//remover
				echo '<button data-index="'.$loop.'" id="variable_product_qr_data'.$loop.'" data-meta="variable_product_qr_data" data-remove="'.$variation->ID . '" class="button-primary button-large qr-remover"  style="margin-left:10px" >Remove</button>';
				echo '</div>';
				//hidden field
				echo '<input data-id="' . esc_attr($product_id) . '" type="hidden" name ="variable_product_qr_data" value="' . esc_attr($qr_datas) . '">';
		}else{

			//qr generator
			echo '<button data-index ="'.$loop.'"data-variable= "true" data-initiator= "_product-qr-variabled" data-id="' . esc_attr($variation->ID) . '" class=" button button-primary button-large generate-variable-qr">Generate Product QR</button>';
			//hidden field
			echo '<input data-id="' . esc_attr($variation->ID) . '" type="hidden" name ="variable_product_qr_data" id="variable_product_qr_data['.$loop.']" value="">';
		}
		echo '</div>';
	}

	public function add_metabox()
	{

		$req_fields = $this->filterArrayByKeys($this->generators, ['slug', 'title', 'callback']);
		foreach ($req_fields as $k => $g) {
			$p_check = get_post_type(get_the_ID());
			if($p_check == 'product'){
				if($g['slug'] == '_glassfrog_checkbox'){
					add_meta_box(
						$g['slug'],
						$g['title'],
						array($this, $g['callback']),
						'product',
						'side',
						'low'

					);
				}
				$product_check = wc_get_product(get_the_ID());
				if (!$product_check->is_type('variable')) {
					add_meta_box(
						$g['slug'],
						$g['title'],
						array($this, $g['callback']),
						'product',
						'side',
						'low'

					);
				}
			}
		}
	}

	//Get Meta values if data already exists for product meta

	public function render_generator_button_with_image($datas, $productNum)
	{
		$qr_datas 	= get_post_meta($productNum, $datas['slug'], true);
		$html_decode 	= htmlspecialchars_decode($qr_datas);
		$decoded_json 	= json_decode($html_decode,true);
		$product 		= wc_get_product($productNum);
		echo '<div class="dong-qr-components dong-qr-components-single">';
			if(!empty($decoded_json)){

				echo '<div class="qr-img-container-single">';
				echo '<img src="' . $decoded_json['qr_image_url'] . '' . '" alt="" width="200" height="200">';
				echo '</div>';

				echo '<div class="qr-urlbtn-container-single">';
				echo '<button data-url="'.$decoded_json['qr_image_url'] . '" class="button-primary button-large url-copy" >Copy QR URL</button>';
				echo '<button data-meta="'.$datas['slug'].'" data-remove="'.$productNum . '" class="button-primary button-large qr-remover" style="margin-left:10px" >Remove</button>';
				echo '</div>';

				echo '<input data-id="' . esc_attr($productNum) . '" type="hidden" name ="' . esc_attr($datas['slug']) . '" value="' . esc_attr($qr_datas) . '">';
			}else{
				echo '<button data-variable= "false" data-initiator= "' . esc_attr($datas['slug']) . '" data-id="' . esc_attr($productNum) . '" class="' . esc_attr($datas['buttonClass']) . ' button button-primary button-large">Generate Product QR</button>';
				echo '<input data-id="' . esc_attr($productNum) . '" type="hidden" name ="' . esc_attr($datas['slug']) . '" value="">';
			}			
		echo '</div>';
	
	}

	public function render_metabox_product_qr_code($post)
	{
		$this->render_generator_button_with_image($this->generators[0], $post->ID);
	}

	public function render_metabox_direct_checkout($post)
	{
		$this->render_generator_button_with_image($this->generators[1], $post->ID);
	}

	public function render_metabox_glassfrog_checkbox($post){
		$value = get_post_meta( $post->ID, '_glassfrog_checkbox', true );
		?>
		<label for="_glassfrog_checkbox">
		  <input type="checkbox" name="_glassfrog_checkbox" id="_glassfrog_checkbox" <?php checked( $value, 'on' ); ?>>
		 Enable
		</label>
		<?php
	}

	public function filterArrayByKeys(array $input, array $column_keys)
	{
		$result      = array();
		$column_keys = array_flip($column_keys);
		foreach ($input as $key => $val) {
			$result[$key] = array_intersect_key($val, $column_keys);
		}
		return $result;
	}



	public function save_metabox($post_id, $post)
	{
		
		// // Sanitize user input.
		$product_qr_code = isset($_POST['_product_qr_codes']) ? esc_attr($_POST['_product_qr_codes']) : '';
		update_post_meta($post_id, '_product_qr_codes', $product_qr_code);
		$direct_checkout_code = isset($_POST['_product-qr-direct-checkouts']) ? esc_attr($_POST['_product-qr-direct-checkouts']) : '';
		update_post_meta($post_id, '_product-qr-direct-checkouts', $direct_checkout_code);
		$glassfrog_check = isset($_POST['_glassfrog_checkbox']) ? esc_attr($_POST['_glassfrog_checkbox']) : 'off';
		update_post_meta($post_id, '_glassfrog_checkbox', $glassfrog_check);
		
	}
}
new Dongtrader_qr_metas;
