<!DOCTYPE html>
<html>

<head>
   <meta charset="utf-8">
   <title>Registration Confirmation</title>
</head>

<body>
   <?php
   $dongtraders_api_setting_data = get_option('dongtraders_api_settings_fields');

   ?>
   <p><?php printf(esc_html__('Welcome, %s,', 'dongtrader'), esc_html($username)); ?> to our <strong>Détente
         2.0</strong> world peace initiative.</p>
   <?php
   echo wpautop($dongtraders_api_setting_data['dong_change_welcome_text']);
   ?>


</body>

</html>