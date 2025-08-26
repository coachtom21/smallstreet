<?php
if (!defined('ABSPATH')) exit;
$dong_qr_array = get_option('dong_user_qr_values');


$vals = get_option('dongtraders_api_settings_fields');

global $wpdb;


$products = $wpdb->get_results(
	"SELECT ID, post_title, post_content
	 FROM {$wpdb->posts}
	 WHERE post_type = 'product' AND post_status = 'publish'"
);


$group_data_table = $wpdb->prefix . 'mega_mlm_groups';

$release_fund = $wpdb->prefix . 'release_groups_profit';
    
//sql query to get list of groups and group leader where distribution is not done or just a update is required
$group_prepared_query = $wpdb->prepare("SELECT group_id , circle_name, circle_id FROM $group_data_table WHERE distribution_status= 1 OR distribution_status=2 ");

//get results from above sql query
$group_results = $wpdb->get_results($group_prepared_query, ARRAY_A);


$release_profits = $wpdb->prepare("SELECT * FROM $release_fund ORDER BY release_date DESC");

?>

<div class="cpm-plugin-wrap">
	<div class="header-wrap">
		<div class="row">
			<div class="logo-wrap col-md-3">
				<img src="<?php echo plugin_dir_url(__FILE__) . '../img/settings-logo.png'; ?>" alt="logo" class="logo">

			</div>

			<div class="addbanner-wrap col-md-6">
				<div class="title">
					<h1><?php _e('Detente 2.0 Settings', 'dongtraders'); ?></h1>
				</div>
			</div>

			<div class="btn-wrap col-md-3">
				<!-- <a href="#" class="cpm-btn dashicons-before dashicons-heart">support</a>
				<a href="#" class="cpm-btn btn2 dashicons-before dashicons-star-filled">Rate us</a> -->
			</div>
		</div>
	</div>

	<div class="body-wrap">
		<div id="tabs-wrap" class="tabs-wrap">

			<ul class="tab-menu" id="dongtrader-tabs">
				<li class="nav-tab" id="first"><a href="#qr-code-generator" class="dashicons-before dashicons-editor-alignleft"><?php _e('QR Code', 'dongtraders'); ?></a></li>
				<?php if (!empty($dong_qr_array)) : ?>
					<li class="nav-tab" id="second"><a href="#qr-lists" class="dashicons-before dashicons-admin-generic"><?php _e('QR Lists', 'dongtraders'); ?></a></li>
				<?php endif; ?>
				<li class="nav-tab" id="third"><a href="#api-integration" class="dashicons-before dashicons-admin-generic"><?php _e('Integration', 'dongtraders'); ?></a></li>

				<li class="nav-tab" id="fourth"><a href="#advanced" class="dashicons-before dashicons-admin-settings"><?php _e('Orders', 'dongtraders'); ?></a></li>
				<li class="nav-tab" id="fifth"><a href="#import-order" class="dashicons-before dashicons-admin-tools"><?php _e('Import Order', 'dongtraders'); ?></a></li>
				<li class="nav-tab" id="seven"><a href="#export-order-list" class="dashicons-before dashicons-admin-tools"><?php _e('Export Order List', 'dongtraders'); ?></a></li>
				<li class="nav-tab" id="eight"><a href="#release-funds" class="dashicons-before dashicons-admin-tools"><?php _e('Release Disaster relief funds', 'dongtraders'); ?></a></li>
			</ul>

			<div class="tab-content">
				<div id="qr-code-generator">
					<h3 class=" tab-title"><?php _e('Generate new QR code ', 'cpm-dongtrader') ?></h2>
						<p>Add new QR image by providing color ,size and URL of QR code.</p>
						<form action="" method="POST" class="qrtiger-form">
							<div class="dong-notify-msg">
							</div>
							<div class="form-group">
								<label for=""><?php _e('QR Size', 'cpm-dongtrader') ?></label>
								<div class="form-control-wrap">
									<input name="qrtiger-size" class="form-control qrtiger-size" type="number" placeholder="<?php _e('Actual size of QR Code', 'cpm-dongtrader') ?>" onfocus="this.placeholder=''" onblur="this.placeholder='<?php _e('Actual size of QR Code', 'cpm-dongtrader') ?>'" required value="500">
								</div>
							</div>
							<div class="form-group">
								<label for=""><?php _e('Select Color', 'cpm-dongtrader') ?></label>
								<div class="form-control-wrap">
									<select name="qrtiger-color" id="" class="form-control qrtiger-color" required>
										<option value=""><?php _e('Default', 'cpm-dongtrader') ?></option>
										<option value="rgb(153,0,153)"><?php _e('Planning(Purple)', 'cpm-dongtrader') ?></option>
										<option value="rgb(241,104,60)"><?php _e('Budget(Orange)', 'cpm-dongtrader') ?></option>
										<option value="rgb(204,0,0)"><?php _e('Media(Red)', 'cpm-dongtrader') ?></option>
										<option value="rgb(0,153,0)"><?php _e('Distribution(Green)', 'cpm-dongtrader') ?></option>
										<option value="rgb(0,0,204)"><?php _e('Membership(Blue)', 'cpm-dongtrader') ?></option>
									</select>
								</div>
							</div>
							<div class="form-group">
								<label for=""><?php _e('QR URL', 'cpm-dongtrader') ?></label>
								<div class="form-control-wrap">
									<input name="qrtiger-url" class="form-control qrtiger-url" type="url" placeholder="<?php _e('Url to redirect after scanning qr code', 'cpm-dongtrader') ?>" onfocus="this.placeholder=''" onblur="this.placeholder='<?php _e('Url to redirect after scanning qr code', 'cpm-dongtrader') ?>'" required>
								</div>
							</div>

							<div class="form-group">
								<button type="submit" class="cpm-btn submit qrtiger-form-submit real-button">
									Generate
								</button>
								<button style="display: none;" type="submit" class="cpm-btn submit qrtiger-form-submit anim-button">
									Generating <i class="fa fa-spinner fa-spin custom-load"></i>
								</button>
							</div>
						</form>
				</div>

				<div id="api-integration">
					<?php
					$dongtraders_api_setting_data = get_option('dongtraders_api_settings_fields');
					?>
					<form action="options.php" method="POST" enctype="multipart/form-data" id="save-settings">
						<?php
						settings_errors();

						settings_fields('dongtraders_api_setting_page');
						

						?>

			
						<h3>Change Welcome Email Text</h3>
						<div class="form-group">
							<label for="">Welcome Email </label>
							<div class="form-control-wrap">
								
								<?php 
									$content = $dongtraders_api_setting_data['dong_change_welcome_text'];
									$custom_editor_id = "dongchange_welcome_email";
									$custom_editor_name = "dongtraders_api_settings_fields[dong_change_welcome_text]";
									$args = array(
											'media_buttons' => true, 
											'textarea_name' => $custom_editor_name, // Set custom name.
											'textarea_rows' => get_option('default_post_edit_rows', 10), //Determine the number of rows.
											// 'quicktags' => true, // Remove view as HTML button.
											// 'wpautop' => true,
										);
									wp_editor( $content, $custom_editor_id, $args );
								?>
							</div>
						</div>

						<h3>Change Welcome Email Text for Legacytoliveby Redirect</h3>
						<div class="form-group">
							<label for="">Welcome Email </label>
							<div class="form-control-wrap">
								
								<?php 
									$content = $dongtraders_api_setting_data['dong_change_welcome_text_legacytoliveby_redirect'];
									$custom_editor_id = "dongchange_welcome_email_legacytoliveby";
									$custom_editor_name = "dongtraders_api_settings_fields[dong_change_welcome_text_legacytoliveby_redirect]";
									$args = array(
											'media_buttons' => true, 
											'textarea_name' => $custom_editor_name, // Set custom name.
											'textarea_rows' => get_option('default_post_edit_rows', 10), //Determine the number of rows.
											// 'quicktags' => true, // Remove view as HTML button.
											// 'wpautop' => true,
										);
									wp_editor( $content, $custom_editor_id, $args );
								?>
							</div>
						</div>
						<hr>
						<h3><?php _e('Currency Conversion','cpm-dongtrader') ?></h3>
                        <div class="form-group">
							
                            <label for=""><?php _e('Enable','cpm-dongtrader') ?></label>
                            <div class="form-control-wrap">
                                <div class="checkbox-wrap custom">
								  	<input type="checkbox" id="dong_currency_check" name="dongtraders_api_settings_fields[dong_enable_currency]" 
									value="<?php echo esc_attr('on'); ?>"
									<?php if (isset($dongtraders_api_setting_data['dong_enable_currency'])) { echo checked($dongtraders_api_setting_data['dong_enable_currency'], 'on', false);}?>>
                                    <span class="custom-checkbox"></span>
                                </div>
                            </div>
                        </div>
						
						<div class="currency-convert">
							<label for=""><?php _e('1 Dollar is equal to (x) V.N.D ','cpm-dongtrader') ?></label>
							<div class="form-group">
								<div class="form-control-wrap">
									<input class="form-control" name="dongtraders_api_settings_fields[vnd_rate]" type="text" placeholder="1 dollar equals to (x) vnd"
									 onfocus="this.placeholder=''" onblur="this.placeholder='1 dollar equals to (x) vnd'"
									  value="<?php echo esc_html($dongtraders_api_setting_data['vnd_rate']); ?>" >
								</div>
							</div>
						</div>
						<hr>
						<h3>QrTiger API Credentials</h3>
						<div class="form-group">
							<label for="">QrTiger API Key</label>
							<div class="form-control-wrap">
								<input class="form-control" name="dongtraders_api_settings_fields[qrtiger-api-key]" type="text" placeholder="Enter API Key" onfocus="this.placeholder=''" onblur="this.placeholder='Enter API Key'" value="<?php echo esc_html($dongtraders_api_setting_data['qrtiger-api-key']); ?>">
							</div>
						</div>
						<div class="form-group">
							<label for="">QrTiger API URL</label>
							<div class="form-control-wrap">
								<input class="form-control" name="dongtraders_api_settings_fields[qrtiger-api-url]" type="text" placeholder="Enter API URL" onfocus="this.placeholder=''" onblur="this.placeholder='Enter API URL'" value="<?php echo esc_html($dongtraders_api_setting_data['qrtiger-api-url']); ?>">
							</div>
						</div>
						<hr>
						<h3>Glassfrog API Credentials</h3>
						<div class="form-group">
							<label for="">Glassfrog API Key</label>
							<div class="form-control-wrap">
								<input class="form-control" name="dongtraders_api_settings_fields[glassfrog-api-key]" type="text" placeholder="Enter API Key" onfocus="this.placeholder=''" onblur="this.placeholder='Enter API Key'" value="<?php echo esc_html($dongtraders_api_setting_data['glassfrog-api-key']); ?>">
							</div>
						</div>
						<div class="form-group">
							<label for="">Glassfrog API URL</label>
							<div class="form-control-wrap">
								<input class="form-control" name="dongtraders_api_settings_fields[glassfrog-api-url]" type="text" placeholder="Enter API URL" onfocus="this.placeholder=''" onblur="this.placeholder='Enter API URL'" value="<?php echo esc_html($dongtraders_api_setting_data['glassfrog-api-url']); ?>">
							</div>
						</div>
						<hr>
						<h3>Crowdsignal API Credentials</h3>
						<div class="form-group">
							<label for="">Crowdsignal API Key</label>
							<div class="form-control-wrap">
								<input class="form-control" name="dongtraders_api_settings_fields[crowdsignal-api-key]" type="text" placeholder="Enter API Key" onfocus="this.placeholder=''" onblur="this.placeholder='Enter API Key'" value="<?php echo esc_html($dongtraders_api_setting_data['crowdsignal-api-key']); ?>">
							</div>
						</div>
						<div class="form-group">
							<label for="">crowdsignal API URL</label>
							<div class="form-control-wrap">
								<input class="form-control" name="dongtraders_api_settings_fields[crowdsignal-api-url]" type="text" placeholder="Enter API URL" onfocus="this.placeholder=''" onblur="this.placeholder='Enter API URL'" value="<?php echo esc_html($dongtraders_api_setting_data['crowdsignal-api-url']); ?>">
							</div>
						</div>
						<hr/>
						<h3><?php _e('Monthly Subscription Product', 'cpm-dongtrader') ?></h3>
						<div class="form-group">
							<select name="dongtraders_api_settings_fields[monthly_subscription_product]" id="page_selector" class="form-control">
							<?php
							    echo '<option value="">Select Product </option>';
							    foreach ($products as $p) {
									
									if($p->ID == $vals['monthly_subscription_product']){
							            echo '<option  selected value="' . esc_attr($p->ID) . '">' . esc_html($p->post_title) . '</option>';
									}else{
										echo '<option value="' . esc_attr($p->ID) . '">' . esc_html($p->post_title) . '</option>';
									}
							    }
							    ?>
							</select>
						</div>
						<hr/>
						<h3><?php _e('Annual Subscription Product', 'cpm-dongtrader') ?></h3>
						<div class="form-group">
						
						<select name="dongtraders_api_settings_fields[annual_subscription_product]" id="page_selector" class="form-control"">
							    <?php
							     echo '<option value="">Select Product </option>';
							    foreach ($products as $p) {
									if($p->ID == $vals['annual_subscription_product']){
							            echo '<option  selected value="' . esc_attr($p->ID) . '">' . esc_html($p->post_title) . '</option>';
									}else{
										echo '<option value="' . esc_attr($p->ID) . '">' . esc_html($p->post_title) . '</option>';
									}
							    }
							    ?>
						</select>
						</div>
						<hr/>
						<h3><?php _e('Chase Page', 'cpm-dongtrader') ?></h3>
						<div class="form-group">
							<select name="dongtraders_api_settings_fields[chase_page_selector]" id="page_selector" class="form-control"">
							    <?php
							    $pages = get_pages();
							    foreach ($pages as $page) {
									if($page->ID == $vals['chase_page_selector']){
							            echo '<option  selected value="' . esc_attr($page->ID) . '">' . esc_html($page->post_title) . '</option>';
									}else{
										echo '<option value="' . esc_attr($page->ID) . '">' . esc_html($page->post_title) . '</option>';
									}
							    }
							    ?>
							</select>
						</div>
						<hr/>
						<h3><?php _e('Patron Page', 'cpm-dongtrader') ?></h3>
						<div class="form-group">
							<select name="dongtraders_api_settings_fields[patron_page_selector]" id="page_selector" class="form-control"">
							    <?php
							    $pages = get_pages();
							    foreach ($pages as $page) {
							    if($page->ID == $vals['patron_page_selector']){
									echo '<option  selected value="' . esc_attr($page->ID) . '">' . esc_html($page->post_title) . '</option>';
							    }else{
									echo '<option value="' . esc_attr($page->ID) . '">' . esc_html($page->post_title) . '</option>';
							    } 
							 
							    }
							    ?>
							</select>
						</div>
						<h3><?php _e('Bundled Products For Patron', 'cpm-dongtrader') ?></h3>
						<div class="form-group">
						    <select class="cpm-multiselect form-control" name="dongtraders_api_settings_fields[dong_patron_mem][]" multiple="multiple">
						       
						        <?php
						        // Query WooCommerce products
						        $args = array(
						            'post_type' => 'product',
						            'posts_per_page' => -1, // Show all products
						        );
						        $products = new WP_Query( $args );

						        // Loop through products and populate the dropdown
						        if ( $products->have_posts() ) :
						            while ( $products->have_posts() ) : $products->the_post();
						                $product_id = get_the_ID();
						                $product_title = get_the_title();

						                $check_patronsaved_productid = is_array($vals['dong_patron_mem']) && in_array($product_id , $vals['dong_patron_mem']) ? 'selected' : '';
						        ?>
						        <option <?php echo $check_patronsaved_productid; ?> value="<?php echo esc_attr( $product_id ); ?>"><?php echo esc_html( $product_title.'--'.$product_id ); ?></option>
						        <?php endwhile; endif; wp_reset_postdata(); ?>
						    </select>
						</div>
						<h3><?php _e('Bundled Products For Megavoters', 'cpm-dongtrader') ?></h3>
						<div class="form-group">
						    <select class="cpm-multiselect form-control" name="dongtraders_api_settings_fields[dong_mega_mem][]" multiple="multiple">
						        
						        <?php
						        // Query WooCommerce products
						        $args = array(
						            'post_type' => 'product',
						            'posts_per_page' => -1, // Show all products
						        );
						        $products = new WP_Query( $args );

						        // Loop through products and populate the dropdown
						        if ( $products->have_posts() ) :
						            while ( $products->have_posts() ) : $products->the_post();
						                $product_id = get_the_ID();
						                $product_title = get_the_title();
						                $check_mega_saved_productid = is_array($vals['dong_mega_mem']) && in_array($product_id , $vals['dong_mega_mem']) ? 'selected' : '';
						        ?>
						        <option <?php echo  $check_mega_saved_productid; ?> value="<?php echo esc_attr( $product_id ); ?>"><?php echo esc_html($product_title.'--'.$product_id); ?></option>
						        <?php endwhile; endif; wp_reset_postdata(); ?>
						    </select>
						</div>
						<div class="form-group settings-submit">
							<input type="submit" class="cpm-btn submit save-settings-dash" name="submit" value="Save changes">
						</div>
					</form>
				</div>
				<?php if (!empty($dong_qr_array)) : ?>
					<div id="qr-lists">
						<?php if ($dong_qr_array) : ?>
							<h3 class="tab-title"><?php _e('Generated QR codes', 'cpm-dongtrader') ?></h2>
								<div class="cpm-table-wrap" style="overflow: hidden;">
									<table id="qr-all-list">
										<thead>
											<tr>
												<th>S.N</th>
												<th>QR ID</th>
												<th>QR Image</th>
												<th>QR URL</th>
												<th>Remove</th>

											</tr>
										</thead>
										<tbody>
											<?php $i = 1;
											foreach ($dong_qr_array as $key => $value) :

											?>
												<tr id="<?php echo 'tr-index-'.$key?>">
													<td><?= $i ?></td>
													<td><?php echo $value['qr_id'] ?></td>
													<td><img src="<?php echo $value['qr_image_url']; ?>" width="50" height="50"></td>
													<td><button class="cpm-btn url-copy" data-url="<?= $value['qr_image_url']; ?>">Copy</button></td>
													<td><button id="<?php echo 'delete-item-'.$key ?>"class="cpm-btn qr-delete" data-index="<?= $key ?>">Delete</button></td>
												</tr>
											<?php $i++;
											endforeach; ?>
										</tbody>
									</table>
								</div>
							<?php endif; ?>
					</div>
				<?php endif; ?>
				<div id="advanced">
					<h2 class="tab-title">Order</h2>

					<?php dongtraders_list_order_meta_table(); ?>



				</div>

				<!-- import order tab -->
				<div id="import-order">
					<h2 class="tab-title">Import Order From CSV file </h2>
					<form action="#" method="post" enctype="multipart/form-data" onSubmit="window.location.reload()">
						<div class=" form-group">
							<label for=""><?php _e('Select CSV file', 'cpm-dongtrader') ?></label>
							<div class="form-control-wrap">
								<input type="file" name="get_file" class="form-control" accept=".csv" required>

							</div>

						</div>
						<div class="form-group settings-submit">
							<input type="submit" class="cpm-btn submit save-settings-dash" name="import_csv" value="Import CSV">
						</div>

					</form>
					<?php
					dongtraders_csv_order_importer();
					?>

				</div>
				<!-- order export List -->

				<div id="export-order-list">
					<h2 class="tab-title">Export Order List</h2>
					<?php
					dongtraders_custom_order_created_list();
					?>
				</div>
				<div id="release-funds">
					<h2 class="tab-title">Release Disaster relief's funds</h2>
					<form action="" method="POST" class="rfund-form">
							<div class="rfund-notify-msg">
							</div>
							<div class="form-group">
								<label for=""><?php _e('Select Group', 'cpm-dongtrader') ?></label>
								<div class="form-control-wrap">
									<select name="rfund-group" id="" class="form-control rfund-group" required>
										<option value="">Select Group </option>
										<?php 
										if(!empty($group_results)) {
											foreach($group_results as $gr) {

												echo '<option value="'.$gr['group_id'].'">'.$gr['circle_name'].'</option>';
											}
										}
										?>
									</select>
								</div>
							</div>
							<div class="form-group">
								<label for=""><?php _e('Disaster Release Cause', 'cpm-dongtrader') ?></label>
								<div class="form-control-wrap">
									<input name="rfund-note" class="form-control rfund-note" type="text" placeholder="<?php _e('Enter release note', 'cpm-dongtrader') ?>" onfocus="this.placeholder=''" onblur="this.placeholder='<?php _e('Enter release note', 'cpm-dongtrader') ?>'" required >
								</div>
							</div>
							<div class="form-group">
								<label for=""><?php _e('Disaster Release Amount', 'cpm-dongtrader') ?></label>
								<div class="form-control-wrap">
									<input name="rfund-amount" class="form-control rfund-amount" type="number" placeholder="<?php _e('Enter release amount', 'cpm-dongtrader') ?>" onfocus="this.placeholder=''" onblur="this.placeholder='<?php _e('Enter release amount', 'cpm-dongtrader') ?>'" required >
								</div>
							</div>
				
							<div class="form-group">
								<button type="submit" class="cpm-btn submit  real-button" style="">
									Release Funds
								</button>
								<button style="display: none;" type="submit" class="cpm-btn submit  anim-button">
									Releasing <i class="fa fa-spinner fa-spin custom-load"></i>
								</button>
							</div>
							
					</form>
					<hr/>
					<?php dongtrader_release_funds_tablelist() ?>
				</div>
			</div>

			<div class="footer-wrap">
				<div class="row">
					<div class="creator col-md-3">
						<span>Proudly Created by</span>
						<a href="codepixelzmedia.com"><img src="<?php echo plugin_dir_url(__FILE__) . '../img/cpm-logo.png'; ?>" alt="cpm-logo" class="cpm-logo"></a>
					</div>

					<div class="col-md-6">
					</div>

					<div class="copyright col-md-3">
						<span>All rights reserved</span>
						&copy; <?php echo date("Y"); ?>
					</div>
				</div>
			</div>

		</div>
		<div class="clear"></div>