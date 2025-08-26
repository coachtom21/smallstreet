<?php

/**
 * Plugin Name:       Zapier for WordPress
 * Description:       Zapier enables you to automatically share your posts to social media, create WordPress posts from Mailchimp newsletters, and much more. Visit https://zapier.com/apps/wordpress/integrations for more details.
 * Version:           1.4.0
 * Author:            Zapier
 * Author URI:        https://zapier.com
 * License:           Expat (MIT License)
 * License URI:       https://spdx.org/licenses/MIT.html
 */

require_once dirname(__FILE__) . '/vendor/autoload.php';
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;


class Zapier_Auth_Loader
{
    protected $actions;
    protected $filters;

    public function __construct()
    {
        $this->actions = array();
        $this->filters = array();
    }

    public function add_plugin_action($hook, $component, $callback, $priority = 10, $accepted_args = 1)
    {
        $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
    }

    public function add_plugin_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1)
    {
        $this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
    }

    private function add($hooks, $hook, $component, $callback, $priority, $accepted_args)
    {
        $hooks[] = array(
            'hook' => $hook,
            'component' => $component,
            'callback' => $callback,
            'priority' => $priority,
            'accepted_args' => $accepted_args,
        );

        return $hooks;
    }

    public function run()
    {
        foreach ($this->filters as $hook) {
            add_filter($hook['hook'], array($hook['component'], $hook['callback']), $hook['priority'], $hook['accepted_args']);
        }

        foreach ($this->actions as $hook) {
            add_action($hook['hook'], array($hook['component'], $hook['callback']), $hook['priority'], $hook['accepted_args']);
        }
    }
}


class Zapier_Auth
{
    private $error = null;
    protected $namespace;
    protected $loader;

    public function __construct()
    {
        $this->namespace = 'zapier/v1';
        $this->loader = new Zapier_Auth_Loader();
        $this->define_public_hooks();
    }

    private function define_public_hooks()
    {
        $this->loader->add_plugin_action('rest_api_init', $this, 'add_api_routes');
        $this->loader->add_plugin_filter('rest_pre_dispatch', $this, 'rest_pre_dispatch');
        $this->loader->add_plugin_filter('determine_current_user', $this, 'determine_current_user');
        $this->loader->add_plugin_action('wp_update_user', $this, 'updated_user');
    }

    public function run()
    {
        $this->loader->run();
    }

    public function add_api_routes()
    {
        register_rest_route($this->namespace, '/token', array(
            'methods' => "POST",
            'callback' => array($this, 'generate_token'),
            'permission_callback' => '__return_true'
        ));

        register_rest_route($this->namespace, '/(?P<type>[a-zA-Z0-9_-]+)/supports', array(
            'methods' => "GET",
            'callback' => array($this, 'get_custom_type_supports'),
            'permission_callback' => '__return_true'
        ));

        register_rest_route($this->namespace, '/roles', array(
            'methods' => "GET",
            'callback' => array($this, 'get_roles'),
            'permission_callback' => '__return_true'
        ));

        register_rest_route($this->namespace, '/webhook', array(
            'methods' => "POST",
            'callback' => array($this, 'add_webhook'),
            'permission_callback' => '__return_true'
        ));

        register_rest_route($this->namespace, '/webhook', array(
            'methods' => "DELETE",
            'callback' => array($this, 'remove_webhook'),
            'permission_callback' => '__return_true'
        ));
    }

    public function generate_token($request)
    {
        $secret_key = get_option('zapier_secret');
        $username = $request->get_param('username');
        $password = $request->get_param('password');
        $user = wp_authenticate($username, $password);

        if (is_wp_error($user)) {
            $error_code = $user->get_error_code();
            return new WP_Error(
                $error_code,
                $user->get_error_message($error_code),
                array(
                    'status' => 401,
                )
            );
        }

        $issuedAt = time();
        $token = array(
            'iss' => get_bloginfo('url'),
            'iat' => $issuedAt,
            'nbf' => $issuedAt,
            'exp' => $issuedAt + 300,
            'data' => array(
                'user_id' => $user->data->ID,
            ),
        );

        return array(
            'token' => JWT::encode($token, $secret_key, 'HS256'),
        );
    }

    public function get_custom_type_supports($request)
    {

        if(!is_user_logged_in()) {
            return new WP_Error(
                'not_logged_in',
                'You are not logged in',
                array(
                    'status' => 401,
                )
            );
        }

        $type = $request['type'];
        $types = get_post_types(array());

        if(!in_array($type, $types)) {
            return new WP_Error(
                'invalid_post_type',
                'Invalid post type',
                array(
                    'status' => 404,
                )
            );
        }
        
        return array('supports' => get_all_post_type_supports($type));
    }

    public function get_roles()
    {
        if(!is_user_logged_in()) {
            return new WP_Error(
                'not_logged_in',
                'You are not logged in',
                array(
                    'status' => 401,
                )
            );
        }
        
        $roles = array();
        foreach (wp_roles()->roles as $key => $role) {
            $roles[] = (array('id' => $key, 'name' => $role['name']));
        }

        return array('roles' => $roles);
    }

    public function add_webhook($request) {

        if(!is_user_logged_in()) {
            return new WP_Error(
                'not_logged_in',
                'You are not logged in',
                array(
                    'status' => 401,
                )
            );
        }

        $ALLOWED_ACTIONS = array('wp_update_user');

        $action = $request->get_param("action");
        $endpoint_url = $request->get_param("endpoint_url");

        if(!in_array($action, $ALLOWED_ACTIONS)) {
            return new WP_Error(
                'invalid_action',
                'Invalid action',
                array(
                    'status' => 400,
                )
            );
        }

        if(empty($endpoint_url)) {
            return new WP_Error(
                'invalid_endpoint_url',
                'Invalid endpoint url',
                array(
                    'status' => 400,
                )
            );
        }

        $option_key = "zapier_hooks_$action";

        $hooks = get_option($option_key, []);

        if(!in_array($endpoint_url, $hooks)) {
            $hooks[] = $endpoint_url;
            update_option($option_key, $hooks);
        }

        return array('success' => true);
    }

    public function remove_webhook($request) {

        if(!is_user_logged_in()) {
            return new WP_Error(
                'not_logged_in',
                'You are not logged in',
                array(
                    'status' => 401,
                )
            );
        }

        $action = $request->get_param("action");
        $endpoint_url = $request->get_param("endpoint_url");

        $option_key = "zapier_hooks_$action";
        $hooks = get_option($option_key, []);

        if(($key = array_search($endpoint_url, $hooks)) !== false) {
            unset($hooks[$key]);
            update_option($option_key, $hooks);
        }

        return array('success' => true);
    }

    public function updated_user($user_id) {
        $option_key = "zapier_hooks_wp_update_user";

        $hooks = get_option($option_key, []);

        foreach($hooks as $hook) {
            $response = wp_remote_post($hook, array(
                'body' => json_encode(array('user_id' => $user_id)),
                'headers' => array('Content-Type' => 'application/json'),
            ));
        }
    }

    public function get_user_from_token()
    {
        try {
            JWT::$leeway = 240; // $leeway in seconds
            $token = JWT::decode(
                $_SERVER['HTTP_X_ZAPIER_AUTH'],
                new Key(get_option('zapier_secret'), 'HS256')
            );

            if ($token->iss != get_bloginfo('url')) {
                $this->error = new WP_Error(
                    'bad_issuer',
                    'The issuer does not match with this server',
                    array(
                        'status' => 401,
                    )
                );
            } elseif (!isset($token->data->user_id)) {
                $this->error = new WP_Error(
                    'bad_request',
                    'Incomplete data',
                    array(
                        'status' => 401,
                    )
                );
            } else {
                return $token->data->user_id;
            }
        } catch (Exception $e) {
            $this->error = new WP_Error(
                'invalid_token',
                $e->getMessage(),
                array(
                    'status' => 403,
                )
            );
        }
    }

    public function determine_current_user($user)
    {
        $rest_api_slug = rest_get_url_prefix();
        $is_valid_rest_api_uri = strpos($_SERVER['REQUEST_URI'], $rest_api_slug);
        $is_valid_token_uri = strpos($_SERVER['REQUEST_URI'], $this->namespace . '/token');
        $is_zapier_request = isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT'] === 'Zapier' && isset($_SERVER['HTTP_X_ZAPIER_AUTH']);

        if ($is_zapier_request && $is_valid_rest_api_uri && !$is_valid_token_uri) {
            $user_id = $this->get_user_from_token();
            if ($user_id) {
                return $user_id;
            }
        }

        return $user;
    }

    public function rest_pre_dispatch($request)
    {
        if (is_wp_error($this->error)) {
            return $this->error;
        }
        return $request;
    }
}

register_activation_hook(__FILE__, 'zapier_add_secret_key');
register_deactivation_hook(__FILE__, 'zapier_delete_secret_key');

function zapier_add_secret_key() {
    // the resulting value for the zapier_secret is 256 in length
    add_option('zapier_secret', bin2hex(random_bytes(128)));
}

function zapier_delete_secret_key() {
    delete_option('zapier_secret');
}

$plugin = new Zapier_Auth();
$plugin->run();