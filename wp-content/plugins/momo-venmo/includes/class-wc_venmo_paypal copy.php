<?php if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( !class_exists( 'WC_Venmo_PayPal' ) && class_exists( 'WC_Venmo_Pay_Gateway' ) ):
class WC_Venmo_PayPal extends WC_Venmo_Pay_Gateway {

  // function register() {
  //   return;
  //   add_action( 'admin_post_save_live_paypal_env', array( $this, 'wc_venmo_save_live_paypal_env' ) );
  //   add_action( 'admin_post_revoke_paypal_token', array( $this, 'wc_venmo_revoke_paypal_token' ) );
  //   add_action( 'admin_post_refresh_paypal_token', array( $this, 'wc_venmo_refresh_paypal_token' ) );
  //   add_action( 'wc_venmo_paypal_renewal_token_cron_hook', array( $this, 'wc_venmo_renew_paypal_token_cron' ) );
  //   add_action( 'admin_post_save_test_paypal_env', array( $this, 'wc_venmo_save_test_paypal_env' ) );

  //   if ( 'no' === $this->enabled && wp_next_scheduled( 'wc_venmo_paypal_renewal_token_cron_hook' ) ) {
  //     wp_clear_scheduled_hook( 'wc_venmo_paypal_renewal_token_cron_hook' );
  //   }
  //   if ( 'yes' === $this->enabled && wp_next_scheduled( 'wc_venmo_paypal_renewal_token_cron_hook' ) === false ) {
  //     wp_schedule_event( time(), 'weekly', 'wc_venmo_paypal_renewal_token_cron_hook' );
  //   }
  // }

  function wc_venmo_save_live_paypal_env() {
      $merchant_id = esc_html( $_POST['merchant_id'] );
      $location_id = esc_html( $_POST['location_id'] );
      $access_token = esc_html( $_POST['access_token'] );
      $refresh_token = esc_html( $_POST['refresh_token'] );

      $referer = urldecode( $_POST['_wp_http_referer'] );
      echo  '<div class="wrap"><div style="padding: 10rem">' ;

      if ( !wp_verify_nonce( $_POST['save_live_paypal_env_nonce'], 'save_live_paypal_env' ) ) {
          wp_die( '<p style="margin-top: 50px;">
  <a style="padding: 1rem; border: none; background-color: black; color: white; text-decoration: none;"
  href="' . $referer . '">Go Back</a></p><br><br>
  <h1>Invalid nonce</h1>' . var_export( $_POST, true ) );
      }
      if ( !$referer ) {
          wp_die( '<p style="margin-top: 50px;">
  <a style="padding: 1rem; border: none; background-color: black; color: white; text-decoration: none;"
  href="' . $referer . '">Go Back</a></p><br><br>
  <h1>Missing target</h1>' . var_export( $_POST, true ) );
      }

      $this->update_option( 'PPL_Merchant_Id', $merchant_id );
      $this->update_option( 'PPL_Location_Id', $location_id );
      $this->update_option( 'PPL_Access_Token', $access_token );
      $this->update_option( 'PPL_Refresh_Token', $refresh_token );

      if ( $access_token && $refresh_token ) {
        if ( wp_next_scheduled( 'wc_venmo_paypal_renewal_token_cron_hook' ) === false ) {
            wp_schedule_event( time(), 'weekly', 'wc_venmo_paypal_renewal_token_cron_hook' );
        }
      }
      echo '</div></div>';
      wp_safe_redirect( $referer );
      exit;
  }

  function wc_venmo_revoke_paypal_token() {
      $referer = urldecode( $_POST['_wp_http_referer'] );
      echo  '<div class="wrap"><div style="padding: 10rem">' ;

      if ( !wp_verify_nonce( $_POST['revoke_paypal_token_nonce'], 'revoke_paypal_token' ) ) {
          wp_die( '<p style="margin-top: 50px;">
  <a style="padding: 1rem; border: none; background-color: black; color: white; text-decoration: none;"
  href="' . $referer . '">Go Back</a></p><br><br>
  <h1>Invalid nonce</h1>' . var_export( $_POST, true ) );
      }
      if ( !$referer ) {
          wp_die( '<p style="margin-top: 50px;">
  <a style="padding: 1rem; border: none; background-color: black; color: white; text-decoration: none;"
  href="' . $referer . '">Go Back</a></p><br><br>
  <h1>Missing target</h1>' . var_export( $_POST, true ) );
      }

      $access_token = $this->get_option( 'PPL_Access_Token', false );
      if ( !$access_token ) {
          wp_die( '<p style="margin-top: 50px;">
  <a style="padding: 1rem; border: none; background-color: black; color: white; text-decoration: none;"
  href="' . $referer . '">Go Back</a></p><br><br>
  <h1>Missing access token</h1>' . var_export( $_POST, true ) );
      } else {
        $this->update_option( 'PPL_Access_Token', null );
        wp_clear_scheduled_hook( 'wc_venmo_paypal_renewal_token_cron_hook' );
      }

      $data = array(
          'access_token' => $access_token,
          'origin' => get_bloginfo('url'),
          'admin_email' => get_bloginfo('admin_email')
      );
			$url = 'https://paypal.theafricanboss.com/revoke.php';

			$revoke_token_response = wp_remote_post( $url, array(
          'method'      => 'POST',
          'timeout'     => 45,
          'redirection' => 5,
          'httpversion' => '1.0',
          'blocking'    => true,
          'headers'     => array(),
          'body'        => $data,
          'cookies'     => array()
          )
      );

      if ( !is_wp_error( $revoke_token_response ) && 200 == wp_remote_retrieve_response_code( $revoke_token_response ) ) {
        $revoke_token_response_body = json_decode( wp_remote_retrieve_body( $revoke_token_response ), true );
        // print_r( $revoke_token_response_body );
      } else if ( is_wp_error( $revoke_token_response ) ) {
      	echo 'Response:<pre>';
      	print_r( $revoke_token_response );
      	echo '</pre>';
        $error_message = $revoke_token_response->get_error_message();
        echo "<p>Something went wrong: $error_message</p>";
      } else {
        echo "<p>Failed to revoke access token</p>";
        // $this->wc_venmo_revoke_token_logs("Failed to revoke access token");
      }

      echo '</div></div>';
      wp_safe_redirect( $referer );
      echo '<p style="margin-top: 50px;">
    <a style="padding: 1rem; border: none; background-color: black; color: white; text-decoration: none;"
    href="' . $referer . '">Go Back</a></p>';
      exit;
  }

  function wc_venmo_refresh_paypal_token() {
      $referer = urldecode( $_POST['_wp_http_referer'] );
      echo  '<div class="wrap"><div style="padding: 10rem">' ;

      if ( !wp_verify_nonce( $_POST['refresh_paypal_token_nonce'], 'refresh_paypal_token' ) ) {
          wp_die( '<p style="margin-top: 50px;">
  <a style="padding: 1rem; border: none; background-color: black; color: white; text-decoration: none;"
  href="' . $referer . '">Go Back</a></p><br><br>
  <h1>Invalid nonce</h1>' . var_export( $_POST, true ) );
      }
      if ( !$referer ) {
          wp_die( '<p style="margin-top: 50px;">
  <a style="padding: 1rem; border: none; background-color: black; color: white; text-decoration: none;"
  href="' . $referer . '">Go Back</a></p><br><br>
  <h1>Missing target</h1>' . var_export( $_POST, true ) );
      }

      $refresh_token = $this->get_option( 'PPL_Refresh_Token', false );
      if ( !$refresh_token ) {
          wp_die( '<p style="margin-top: 50px;">
  <a style="padding: 1rem; border: none; background-color: black; color: white; text-decoration: none;"
  href="' . $referer . '">Go Back</a></p><br><br>
  <h1>Missing refresh token</h1>' . var_export( $_POST, true ) );
      }

      $data = array(
          'refresh_token' => $refresh_token,
          'origin' => get_bloginfo('url'),
          'admin_email' => get_bloginfo('admin_email')
      );
			$url = 'https://paypal.theafricanboss.com/refresh.php';

			$refresh_token_response = wp_remote_post( $url, array(
          'method'      => 'POST',
          'timeout'     => 45,
          'redirection' => 5,
          'httpversion' => '1.0',
          'blocking'    => true,
          'headers'     => array(),
          'body'        => $data,
          'cookies'     => array()
          )
      );

      if ( !is_wp_error( $refresh_token_response ) && 200 == wp_remote_retrieve_response_code( $refresh_token_response ) ) {
        $refresh_token_response_body = json_decode( wp_remote_retrieve_body( $refresh_token_response ), true );
        // print_r( $refresh_token_response_body );
        if ( isset( $refresh_token_response_body['access_token'] ) ) {
            $PPL_Access_Token = $this->update_option( 'PPL_Access_Token', $refresh_token_response_body['access_token'] );
            if ( $PPL_Access_Token ) {
              $msg = 'PayPal Access token refreshed and updated successfully to *******' . substr($refresh_token_response_body['access_token'], -8);
              echo "<h1>$msg</h1>";
              $to = get_bloginfo('admin_email');
              $headers = array('Content-Type: text/html; charset=UTF-8');

              wp_mail( $to, $msg, $msg, $headers );
              echo '<br>' . "Also an email has been sent to $to for the update. The new access token ends in ****". substr($refresh_token_response_body['access_token'], -10) . '<br>';

              if ( wp_next_scheduled( 'wc_venmo_paypal_renewal_token_cron_hook' ) === false ) {
                  wp_schedule_event( time(), 'weekly', 'wc_venmo_paypal_renewal_token_cron_hook' );
              }
            } else {
              echo( '<h1>Error refreshing access token</h1>' . var_export( $refresh_token_response_body, true ) );
            }
        }
      } else if ( is_wp_error( $refresh_token_response ) ) {
      	echo 'Response:<pre>';
      	print_r( $refresh_token_response );
      	echo '</pre>';
        $error_message = $refresh_token_response->get_error_message();
        echo "<p>Something went wrong: $error_message</p>";
      } else {
        echo "<p>Failed to update/refresh access token</p>";
        // $this->wc_venmo_refresh_token_logs("Failed to update/refresh access token");
      }
      echo '<p style="margin-top: 50px;">
    <a style="padding: 1rem; border: none; background-color: black; color: white; text-decoration: none;"
    href="' . $referer . '">Go Back</a></p>';

    echo '</div></div>';
      exit;
  }

	function wc_venmo_renew_paypal_token_cron() {
		$refresh_token = $this->get_option( 'PPL_Refresh_Token', false );
		if ( !$refresh_token ) {
			// $this->wc_venmo_refresh_token_logs( 'Missing refresh token' . var_export( $_POST, true ) );
      return;
		} else {
      $data = array( 'refresh_token' => $refresh_token, 'origin' => get_bloginfo('url'), 'admin_email' => get_bloginfo('admin_email') );
      $url = 'https://paypal.theafricanboss.com/refresh.php';

      $refresh_token_response = wp_remote_post( $url, array(
          'method'      => 'POST',
          'timeout'     => 45,
          'redirection' => 5,
          'httpversion' => '1.0',
          'blocking'    => true,
          'headers'     => array(),
          'body'        => $data,
          'cookies'     => array()
          )
      );

      if ( !is_wp_error( $refresh_token_response ) && 200 == wp_remote_retrieve_response_code( $refresh_token_response ) ) {
        $refresh_token_response_body = json_decode( wp_remote_retrieve_body( $refresh_token_response ), true );
        if ( isset( $refresh_token_response_body['access_token'] ) ) {
            $PPL_Access_Token = $this->update_option( 'PPL_Access_Token', $refresh_token_response_body['access_token'] );
            if ( $PPL_Access_Token ) {
              $msg = 'PayPal Access token refreshed and updated successfully to *******' . substr($refresh_token_response_body['access_token'], -8);
              // $this->wc_venmo_refresh_token_logs($msg);
              $to = get_bloginfo('admin_email');
              $headers = array('Content-Type: text/html; charset=UTF-8');

              wp_mail( $to, $msg, $msg, $headers );
              return;
            } else {
              // $this->wc_venmo_refresh_token_logs(var_export( $refresh_token_response_body, true ) );
              wp_mail( get_bloginfo('admin_email'), 'IMPORTANT: Error trying to renew your PayPal token', '<p>An error occured trying to renew your PayPal access token.</p><p>Please renew it manually in your admin dashboard to keep processing Pay with Venmo orders.</p><p>Proceed to your admin dashboard by following <a href="' . admin_url('admin.php?page=wc_venmo_paypal') . '">Dashboard > Pay with Venmo > PayPal Tokens</a></p>', array('Content-Type: text/html; charset=UTF-8') );
              return;
            }
        }
      } else if ( is_wp_error( $refresh_token_response ) ) {
          // $this->wc_venmo_refresh_token_logs($refresh_token_response);
          $error_message = $refresh_token_response->get_error_message();
          wp_mail( get_bloginfo('admin_email'), 'IMPORTANT: Error trying to renew your PayPal token', '<p>An error occured trying to renew your PayPal access token.</p><p>Please renew it manually in your admin dashboard to keep processing Pay with Venmo orders.</p><p>Proceed to your admin dashboard by following <a href="' . admin_url('admin.php?page=wc_venmo_paypal') . '">Dashboard > Pay with Venmo > PayPal Tokens</a></p><br><br>' . $error_message, array('Content-Type: text/html; charset=UTF-8') );
        //   $this->wc_venmo_refresh_token_logs($error_message);
        return;
      } else {
        //   $this->wc_venmo_refresh_token_logs("Failed to update/refresh access token");
        wp_mail( get_bloginfo('admin_email'), 'IMPORTANT: Error trying to renew your PayPal token', '<p>An error occured trying to renew your PayPal access token.</p><p>Please renew it manually in your admin dashboard to keep processing Pay with Venmo orders.</p><p>Proceed to your admin dashboard by following <a href="' . admin_url('admin.php?page=wc_venmo_paypal') . '">Dashboard > Pay with Venmo > PayPal Tokens</a></p>', array('Content-Type: text/html; charset=UTF-8') );
          return;
      }
		}
	}

  // function wc_venmo_save_test_paypal_env() {
  //     $merchant_id = esc_html( $_POST['merchant_id'] );
  //     $location_id = esc_html( $_POST['location_id'] );
  //     $access_token = esc_html( $_POST['access_token'] );
  //     $refresh_token = esc_html( $_POST['refresh_token'] );

  //     $referer = urldecode( $_POST['_wp_http_referer'] );
  //     echo  '<div class="wrap"><div style="padding: 10rem">' ;

  //     if ( !wp_verify_nonce( $_POST['save_test_paypal_env_nonce'], 'save_test_paypal_env' ) ) {
  //         wp_die( '<p style="margin-top: 50px;">
  // <a style="padding: 1rem; border: none; background-color: black; color: white; text-decoration: none;"
  // href="' . $referer . '">Go Back</a></p><br><br>
  // <h1>Invalid nonce</h1>' . var_export( $_POST, true ) );
  //     }
  //     if ( !$referer ) {
  //         wp_die( '<p style="margin-top: 50px;">
  // <a style="padding: 1rem; border: none; background-color: black; color: white; text-decoration: none;"
  // href="' . $referer . '">Go Back</a></p><br><br>
  // <h1>Missing target</h1>' . var_export( $_POST, true ) );
  //     }

  //     $this->update_option( 'PPL_Sandbox_Merchant_Id', $merchant_id );
  //     $this->update_option( 'PPL_Sandbox_Location_Id', $location_id );
  //     $this->update_option( 'PPL_Sandbox_Access_Token', $access_token );
  //     $this->update_option( 'PPL_Sandbox_Refresh_Token', $refresh_token );

  //     echo '</div></div>';
  //     wp_safe_redirect( $referer );
  //     exit;
  // }

}

$WC_Venmo_PayPal = new WC_Venmo_PayPal();
$WC_Venmo_PayPal->register();

endif;