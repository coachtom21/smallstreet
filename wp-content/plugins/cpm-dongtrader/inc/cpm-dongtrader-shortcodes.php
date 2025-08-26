<?php

/*Shortcode for selection form popup on getstarted button on landing page*/

function mega_bundled_product_selection($atts)
{

	ob_start();
	$settings = get_option('dongtraders_api_settings_fields');
	$patron_pro_val = $settings['dong_patron_mem'];
	$mega_pro_val = $settings['dong_mega_mem'];
	$sectors = get_terms(array(
		'taxonomy' => 'pa_sector',
		'hide_empty' => true,
	));

	?>
	<style>
		#getstarted-popupform {
			max-width: 500px;
			margin: 0 auto;
			background: rgba(255, 255, 255, 0.6);
			padding: 20px 30px;
		}

		#getstarted-popupform .form-group select {
			margin: 10px 0;
		}
	</style>
	<div class="membership_selection_form">
		<h3>Select The Membership</h3>
		<video controls width="100%"
			poster="https://legacytoliveby.org/wp-content/uploads/2025/05/Screenshot-2025-05-16-140528.png">
			<source
				src="https://legacytoliveby.org/wp-content/uploads/2025/05/invideo-ai-1080-Join the YAM Revolution_ Your Role Awaits!-2025-05-13T23_39_18.mp4"
				type="video/mp4">
			Your browser does not support the video tag.
		</video>
		<form method="get" action="" id="getstarted-popupform">
			<div class="form-group">
				<select name="membership_selection" id="form-field-name" class="elementor-field-textual elementor-size-sm"
					required="required" aria-required="true">
					<option value="">Select Membership</option>
					<option value="yamer">YAMer's/Free of Cost</option>
					<option value="megavoter">MEGAvoter/Supporter/$12 annual</option>
					<option value="patron">Patron/Stakeholder/$377 annual</option>
				</select>
				<?php if (!empty($sectors)): ?>
					<select name="membership_sector" id="form-field-type" class="elementor-field-textual elementor-size-sm"
						required="required" aria-required="true">
						<option value="">Branch (Select One)</option>
						<?php foreach ($sectors as $s) { ?>
							<option value="<?php echo $s->slug ?>"><?php echo $s->name; ?></option>
						<?php } ?>
					</select>

				<?php endif; ?>
			</div>
			<div class="form-group">
				<input id="mem-sel-submit" type="submit" value="Continue">
			</div>

		</form>
	</div>
	<script>
		$(document).ready(function () {
			if (window.location.href.includes("yam=yamer")) {
				// Hide the popup manually
				$('#cpp-popup-3').css('display', 'none');
				$('#cpp-popup').css('display', 'none');
				$('#elementor-popup-modal-1140').css('display', 'none');

				// Remove body class that prevents scrolling
				$('body').removeClass('elementor-popup-open').css('overflow', 'auto');

				// Set form field value
				$('#form-field-name').val('yamer');
			}

			// Handle click outside the container
			$(document).on('mousedown', function (event) {
				const container = $('#membership_selection_form_container');

				// If clicked outside the popup container
				if (!container.is(event.target) && container.has(event.target).length === 0) {
					// Remove 'yam' from URL
					const url = new URL(window.location.href);
					url.searchParams.delete('yam');
					window.history.replaceState({}, document.title, url.pathname + url.search);

					// Also hide popup and enable scroll just in case
					$('#cpp-popup-3').hide();
					$('#cpp-popup').hide();
					$('#elementor-popup-modal-1140').hide();
					$('body').removeClass('elementor-popup-open').css('overflow', 'auto');
				}
			});
		});


	</script>
	<?php
	return ob_get_clean();
}


add_shortcode('membership_selection_form', 'mega_bundled_product_selection');


add_action('init', 'mega_process_popup_params');

function mega_process_popup_params()
{

	if (isset($_GET['membership_selection'])) {

		$settings = get_option('dongtraders_api_settings_fields');

		$selected_sector_id = isset($_GET['membership_sector']) && !empty($_GET['membership_sector']) ? $_GET['membership_sector'] : false;

		if ($_GET['membership_selection'] == 'megavoter' && $selected_sector_id) {

			$mega_pro_val = $settings['dong_mega_mem'];

			$cart_items = mega_add_variation_to_cart($mega_pro_val, $selected_sector_id);

			$megavoter_items = implode(',', $cart_items);

			$new_build_url = site_url() . '?multiple-items=' . $megavoter_items;

		} elseif ($_GET['membership_selection'] == 'patron' && $selected_sector_id) {

			$patron_pro_val = $settings['dong_patron_mem'];

			$cart_items = mega_add_variation_to_cart($patron_pro_val, $selected_sector_id);

			$patron_items = implode(',', $cart_items);

			$new_build_url = site_url() . '?multiple-items=' . $patron_items;

		} elseif ($_GET['membership_selection'] == 'yamer' && $selected_sector_id) {

			$new_build_url = site_url() . '/user-registration';

		} else {
			$new_build_url = site_url();
		}

		wp_redirect($new_build_url);
		exit();
	}
}


function find_matching_product_variation_id($product_id, $attribute)
{
	$attributes = [
		'attribute_pa_sector' => sanitize_text_field($attribute),
	];

	return (new \WC_Product_Data_Store_CPT())->find_matching_product_variation(
		new \WC_Product($product_id),
		$attributes
	);
}



function mega_add_variation_to_cart($products, $sector_id)
{

	$product_to_add_to_cart = [];

	foreach ($products as $mpv) {

		$pro_obj = wc_get_product($mpv);

		if ($pro_obj->is_type('variable')) {

			$var_id = find_matching_product_variation_id((int) $mpv, $sector_id);

			if ($var_id == 0) {

				$var_id = $pro_obj->get_children()[0];

			}

			$product_to_add_to_cart[] = (int) $var_id;

		} else {

			$product_to_add_to_cart[] = (int) $mpv;

		}
	}


	return $product_to_add_to_cart;

}

