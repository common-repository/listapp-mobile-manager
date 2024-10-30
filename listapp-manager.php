<?php
/**
 * Plugin Name: ListApp Mobile Manager
 * Plugin URI: https://github.com/inspireui/listapp-manager
 * Description: The ListApp Settings and APIs for supporting the Listing Directory mobile app by React Native framework
 * Version: 1.7.7
 * Author: InspireUI
 * Author URI: http://inspireui.com
 *
 * Text Domain: listapp
 */


if (!defined('ABSPATH')) {
    exit;
}

include_once plugin_dir_path(__FILE__)."controllers/flutter-user.php";

class ListAppSetting
{
    public $version = '1.7.7';
    protected $_textDomain = 'listapp-setting';
    protected $_pageTitle = 'ListApp Settings';
    protected $_menuTitle = 'ListApp Settings';
    protected $_slugPage = 'listapp-setting';
    protected $_routeApi = 'inspireui/v1';
    protected $_routeApiUrl = 'config';

    protected $_slugOS = 'push-notification';
    protected $_pageTitleOS = 'Push Notification';
    protected $_menuTitleOS = 'Push Notification';
    /*
    * ListAppSetting constructor
    */

    public function __construct()
    {
        define('LISTAPP_SETTING', $this->version);
        define('LISTAPP_PLUGIN_FILE', __FILE__);

        //extra for define constants
        define('LISTAPP_SETTING_VERSION', '1.0.0');
        define('LISTAPP_SETTING_PLUGIN_PATH', plugin_dir_path(__FILE__));
        define('LISTAPP_SETTING_PLUGIN_URL', plugin_dir_url(__FILE__));
        define('LISTAPP_SETTING_PLUGIN_URL_JS', plugin_dir_url(__FILE__) . 'assets/js/');

        add_action('template_redirect', 'prepare_checkout');

        add_action('admin_menu', function () {
            // add menu item to settings page
            add_menu_page(__($this->_pageTitle, $this->_textDomain),
                          __($this->_menuTitle, $this->_textDomain),
                          'manage_options', $this->_slugPage,
                          array($this, 'display_setting'), 
                          'dashicons-location');
            add_submenu_page($this->_slugPage,
                          $this->_pageTitleOS, 
                          $this->_menuTitleOS,
                          'manage_options',
                          $this->_slugOS,
                          array($this, 'output_pushNotification'));

        });

        // add custom url to rest_api if have
        add_action('rest_api_init', function () {
            register_rest_route($this->_routeApi, $this->_routeApiUrl, array(
                'methods' => 'GET',
                'callback' => array($this, 'get_config_layouts'),
            ));
        });

        //allow comments
        add_filter('rest_allow_anonymous_comments', '__return_true');

        //autoload templates 
        $this->load_layout();
        $this->set_config_default();
    }

    /**
     * Call the function to display view
     */
    public function display_setting()
    {
        require_once LISTAPP_SETTING_PLUGIN_PATH . '/templates/setting-template.php';
    }

    public function output_pushNotification(){
        require_once LISTAPP_SETTING_PLUGIN_PATH . '/templates/push-notification.php';
    }

    /**
    * Load Template When Active
    */

    public function load_layout(){
        include_once LISTAPP_SETTING_PLUGIN_PATH . '/controllers/mstore-checkout.php';
        include_once LISTAPP_SETTING_PLUGIN_PATH . '/rest-api/class.api.fields.php';
        include_once LISTAPP_SETTING_PLUGIN_PATH . '/controllers/rename-media.php';
    }
    /**
     * Set default config for the app
     */
    public function set_config_default()
    {
        //set option default when active
        $result = [
            'homeLayout' => 1,
            'verticalLayout' => 2,
            'horizontalLayout' => [
                ['component' => 'listing', "paging" => true, 'layout' => 1],
                ['component' => 'map'],
                ['component' => 'listing', 'name' => 'Eat & Drink', 'layout' => 5],
                ['component'=> 'listing', 'name'=> 'Visit', 'paging'=> true, 'row'=> 3, 'layout'=> 8],
                ['component'=> 'listing','name'=> 'Stay', 'layout'=> 4],
                ['component'=> 'listing', 'name'=> 'Shops', 'layout'=> 7, 'width'=> 120, 'height'=> 250],
                ['component'=> 'news', 'name'=> 'Videos', 'paging'=> true, 'layout'=> 1],
                ['component'=> 'news', 'name'=> 'Tips & Articles', 'paging'=> true, 'row'=> 3, 'layout'=> 9]
            ],
            'menu' => [
                (Object)[
                    'route' =>'home',
                    'name' => 'Explore',
                ],
                (Object)[
                    'route' =>'setting',
                    'name' => 'Settings',
                ],
                (Object)[
                    'route' =>'customPage',
                    'name' => 'Contact',
                    'params' => (Object)[
                        'title' => 'Contact', 
                        'url'=> 'https://inspireui.com/about'
                    ],

                ],
                (Object)[
                    'route' =>'customPage',
                    'name' => 'About Us',
                    'params' => (Object)[
                        'title' => 'Contact', 
                        'url'=> 'https://inspireui.com/about'
                    ],
                    'icon' => 'assignment',

                ],
                (Object)[
                    'route' =>'login',
                    'name' => 'Sign In',
                ],

            ],
            'color' => (Object)[
                  'mainColorTheme'=> '#000000',
                  'tabbar' => '#ffffff',
                  'tabbarTint' => '#3bc651',
                  'tabbarColor' => '#929292',
            ],
            'general' => (Object)[
                'Firebase' => (Object)[
                        'apiKey'=> 'AIzaSyAZhwel4Nd4T5dSmGB3fI_MUJj6BIz5Kk8',
                        'authDomain'=> 'beonews-ef22f.firebaseapp.com',
                        'databaseURL'=> 'https://beonews-ef22f.firebaseio.com',
                        'storageBucket'=> 'beonews-ef22f.appspot.com',
                        'messagingSenderId'=> '1008301626030',
                        'readlaterTable'=> 'list_readlater',
                ],
                "Facebook" => (Object)[
                        'visible'=> false,
                        'adPlacementID'=> '1809822172592320_1981610975413438',
                        'logInID'=> '1809822172592320',
                        'sizeAds'=> 'standard', // standard, large
                ],
                "AdMob" => (Object)[
                        'visible'=> false,
                        'deviceID'=> 'pub-2101182411274198',
                        'unitID'=> 'ca-app-pub-2101182411274198/8802887662',
                        'unitInterstitial'=> 'ca-app-pub-2101182411274198/7326078867',
                        'isShowInterstital'=> true,
                ]
            ],
        ];

        // var_dump(get_option('_listapp_config'));
        if(get_option('_listapp_config') == ''){
            update_option('_listapp_config', json_encode($result));
        }
    }

    /**
     * Call the function return to api json
     * @param $data
     * @return array|mixed|object
     */
    public function get_config_layouts()
    {
        $layouts = get_option('_listapp_config', array());
        $result = json_decode($layouts);
        if (empty($layouts)) {
            return [];
        }
        foreach($result->menu as $item):
            foreach($item as $key => $item2):
                if($key == 'params' && is_array($item->params) && count($item->params) == 0){
                    unset($item->params);
                }
            endforeach;
        endforeach;
        // foreach ($layouts as $k => $item):
        //     $layouts[$k] = json_decode(stripslashes($item));
        // endforeach;

        return $result;
    }
}

$listApp = new ListAppSetting();

add_filter('json_api_controllers', 'registerJsonApiController');
add_filter('json_api_mstore_user_controller_path', 'setMstoreUserControllerPath');
    
function registerJsonApiController($aControllers)
{
    $aControllers[] = 'Mstore_User';
    return $aControllers;
}
    
function setMstoreUserControllerPath()
{
    return plugin_dir_path(__FILE__) . '/controllers/mstore-user.php';
}

//custom rest api
function mstore_users_routes() {
    $controller = new FlutterUserController();
    $controller->register_routes();
} 
add_action( 'rest_api_init', 'mstore_users_routes' );


function prefix_plugin_update_message( $data, $response ) {
	printf(
		'<div class="update-message"><p><strong>%s</strong></p></div>',
		__( 'Version 1.7.4 is compatible with FluxListing >1.5.0. Please DO NOT update it if you are not using FluxListing ver >1.5.0', 'text-domain' )
	);
}
add_action( 'listapp-mobile-manager/listapp-manager.php', 'prefix_plugin_update_message', 10, 2 );

function validateCookieLogin($cookie){
        if(isset($cookie) && strlen($cookie) > 0){
            $userId = wp_validate_auth_cookie($cookie, 'logged_in');
            if($userId == false){
                return new WP_Error("invalid_login", "Your session has expired. Please logout and login again.", array('status' => 401));
            }else{
                return $userId;
            }
        }else{
            return new WP_Error("invalid_login", "Cookie is required", array('status' => 401));
        }
    }
    
    function prepare_checkout()
    {

        if(empty($_GET) && isset($_SERVER['HTTP_REFERER'])){
            $url_components = parse_url($_SERVER['HTTP_REFERER']);
            parse_str($url_components['query'], $params);
            if(!empty($params)){
                $_GET = $params;
            }
        }
        

        if (isset($_GET['cookie'])) {
            $cookie = urldecode(base64_decode(sanitize_text_field($_GET['cookie'])));
            $userId = validateCookieLogin($cookie);
			
            if (!is_wp_error($userId)) {
                $user = get_userdata($userId);
                if ($user !== false) {
                    wp_set_current_user($userId, $user->user_login);
                    wp_set_auth_cookie($userId);
                    if (isset($_GET['vendor_admin'])) {
                        global $wp;
                        $request = $wp->request;
                        wp_redirect(esc_url_raw(home_url("/" . $request)));
                        die;
                    }
                }
            }
        }
    }