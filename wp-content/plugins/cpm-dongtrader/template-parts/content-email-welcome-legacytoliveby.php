<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>🐳 The Whale Never Saw You Coming – Welcome, Organized Krill.</title>
</head>

<body>
    <?php
    $dongtraders_api_setting_data = get_option('dongtraders_api_settings_fields');
    ?>
    <p>Welcome to the swarm.</p>
    <?php
    echo wpautop($dongtraders_api_setting_data['dong_change_welcome_text_legacytoliveby_redirect']);

    ?>


</body>

</html>