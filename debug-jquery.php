<?php
/**
 * Debug script to check jQuery loading and identify JavaScript issues
 */
require_once __DIR__ . '/wp-load.php';

// Check if user is logged in and has admin privileges
if (!current_user_can('manage_options')) {
    die('You need to be logged in as an administrator to run this script.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>jQuery Debug</title>
    <script>
    // Debug script to check jQuery loading
    console.log('Debug script loaded');
    
    // Check if jQuery is loaded
    if (typeof jQuery !== 'undefined') {
        console.log('✅ jQuery is loaded, version:', jQuery.fn.jquery);
    } else {
        console.log('❌ jQuery is NOT loaded');
    }
    
    // Check if $ is available
    if (typeof $ !== 'undefined') {
        console.log('✅ $ is available');
    } else {
        console.log('❌ $ is NOT available');
    }
    
    // Check for print_country function
    if (typeof print_country !== 'undefined') {
        console.log('✅ print_country function exists');
    } else {
        console.log('❌ print_country function does NOT exist');
    }
    
    // Monitor for errors
    window.addEventListener('error', function(e) {
        console.log('🚨 JavaScript Error:', e.message);
        console.log('File:', e.filename);
        console.log('Line:', e.lineno);
        console.log('Column:', e.colno);
    });
    
    // Test jQuery functionality
    jQuery(document).ready(function($) {
        console.log('✅ jQuery ready function works');
        console.log('✅ $ is available in ready function');
        
        // Test basic jQuery functionality
        $('body').append('<div id="jquery-test">jQuery is working!</div>');
    });
    </script>
</head>
<body>
    <h1>jQuery Debug Page</h1>
    <p>Check the browser console for debug information.</p>
    
    <h2>WordPress Scripts Loaded:</h2>
    <ul>
        <li>jQuery: <?php echo wp_script_is('jquery', 'enqueued') ? '✅ Enqueued' : '❌ Not Enqueued'; ?></li>
        <li>jQuery Migrate: <?php echo wp_script_is('jquery-migrate', 'enqueued') ? '✅ Enqueued' : '❌ Not Enqueued'; ?></li>
        <li>Theme Script: <?php echo wp_script_is('cpm-dong-public-js', 'enqueued') ? '✅ Enqueued' : '❌ Not Enqueued'; ?></li>
    </ul>
    
    <h2>Current Page Scripts:</h2>
    <script>
    // List all loaded scripts
    var scripts = document.getElementsByTagName('script');
    console.log('📜 Loaded scripts:');
    for (var i = 0; i < scripts.length; i++) {
        if (scripts[i].src) {
            console.log('- ' + scripts[i].src);
        }
    }
    </script>
    
    <h2>Test jQuery Functionality:</h2>
    <button onclick="testJQuery()">Test jQuery</button>
    <div id="test-result"></div>
    
    <script>
    function testJQuery() {
        if (typeof $ !== 'undefined') {
            $('#test-result').html('<p style="color: green;">✅ jQuery is working!</p>');
        } else if (typeof jQuery !== 'undefined') {
            jQuery('#test-result').html('<p style="color: orange;">⚠️ jQuery works but $ is not available</p>');
        } else {
            document.getElementById('test-result').innerHTML = '<p style="color: red;">❌ jQuery is not available</p>';
        }
    }
    </script>
</body>
</html>

