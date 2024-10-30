<?php

require_once (__DIR__ . '/flutter-base.php');

class FlutterUserController extends FlutterBaseController
{

    public function __construct()
    {
        $this->namespace = '/api/flutter_user';
    }

    public function register_routes()
    {
        register_rest_route($this->namespace, '/register', array(
            array(
                'methods' => 'POST',
                'callback' => array(
                    $this,
                    'register'
                ) ,
                'permission_callback' => function ()
                {
                    return parent::checkApiPermission();
                }
            ) ,
        ));

        /// Added by Toan 30/11/2020
        register_rest_route( $this->namespace, '/reset-password', array(
            array(
                'methods'   => 'POST',
                'callback'  => array( $this, 'reset_password' ),
                'permission_callback' => function () {
                    return parent::checkApiPermission();
                }
            ),
        ));

        register_rest_route($this->namespace, '/generate_auth_cookie', array(
            array(
                'methods' => 'POST',
                'callback' => array(
                    $this,
                    'generate_auth_cookie'
                ) ,
                'permission_callback' => function ()
                {
                    return parent::checkApiPermission();
                }
            ) ,
        ));

        register_rest_route($this->namespace, '/fb_connect', array(
            array(
                'methods' => 'GET',
                'callback' => array(
                    $this,
                    'fb_connect'
                ) ,
                'permission_callback' => function ()
                {
                    return parent::checkApiPermission();
                }
            ) ,
        ));

        register_rest_route($this->namespace, '/firebase_sms_login', array(
            array(
                'methods' => 'GET',
                'callback' => array(
                    $this,
                    'firebase_sms_login'
                ) ,
                'permission_callback' => function ()
                {
                    return parent::checkApiPermission();
                }
            ) ,
        ));

        register_rest_route($this->namespace, '/apple_login', array(
            array(
                'methods' => 'POST',
                'callback' => array(
                    $this,
                    'apple_login'
                ) ,
                'permission_callback' => function ()
                {
                    return parent::checkApiPermission();
                }
            ) ,
        ));

        register_rest_route($this->namespace, '/google_login', array(
            array(
                'methods' => 'GET',
                'callback' => array(
                    $this,
                    'google_login'
                ) ,
                'permission_callback' => function ()
                {
                    return parent::checkApiPermission();
                }
            ) ,
        ));

        register_rest_route($this->namespace, '/get_currentuserinfo', array(
            array(
                'methods' => 'GET',
                'callback' => array(
                    $this,
                    'get_currentuserinfo'
                ) ,
                'permission_callback' => function ()
                {
                    return parent::checkApiPermission();
                }
            ) ,
        ));

        register_rest_route($this->namespace, '/update_user_profile', array(
            array(
                'methods' => 'POST',
                'callback' => array(
                    $this,
                    'update_user_profile'
                ) ,
                'permission_callback' => function ()
                {
                    return parent::checkApiPermission();
                }
            ) ,
        ));
    }



       /// Added by Toan 30/11/2020
       public function reset_password(){
        $json = file_get_contents('php://input');
        $params = json_decode($json, TRUE);
        $usernameReq = $params["user_login"];
        
        $errors = new WP_Error();
        if ( empty( $usernameReq) || ! is_string( $usernameReq) ) {
            return parent::sendError("empty_username", "Enter a username or email address.", 400);
        } elseif ( strpos( $usernameReq, '@' ) ) {
            $user_data = get_user_by( 'email', trim( wp_unslash( $usernameReq) ) );
            if ( empty( $user_data ) ) {
                return parent::sendError("invalid_email", "There is no account with that username or email address.", 404);
            }
        } else {
            $login     = trim( $usernameReq );
            $user_data = get_user_by( 'login', $login );
        }
        if ( ! $user_data ) {
            return parent::sendError("invalid_email", "There is no account with that username or email address.", 404);
        }
    
        $user_login = $user_data->user_login;
        $user_email = $user_data->user_email;
        $key        = get_password_reset_key( $user_data );
    
        if ( is_wp_error( $key ) ) {
            return $key;
        }
    
        if ( is_multisite() ) {
            $site_name = get_network()->site_name;
        } else {
            $site_name = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
        }
    
        $message = __( 'Someone has requested a password reset for the following account:' ) . "\r\n\r\n";
        $message .= sprintf( __( 'Site Name: %s' ), $site_name ) . "\r\n\r\n";
        $message .= sprintf( __( 'Username: %s' ), $user_login ) . "\r\n\r\n";
        $message .= __( 'If this was a mistake, just ignore this email and nothing will happen.' ) . "\r\n\r\n";
        $message .= __( 'To reset your password, visit the following address:' ) . "\r\n\r\n";
        $message .= '<' . network_site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user_login ), 'login' ) . ">\r\n";
        $title = sprintf( __( '[%s] Password Reset' ), $site_name );
        $title = apply_filters( 'retrieve_password_title', $title, $user_login, $user_data );
        $message = apply_filters( 'retrieve_password_message', $message, $key, $user_login, $user_data );
    
        if ( $message && ! wp_mail( $user_email, wp_specialchars_decode( $title ), $message ) ) {
            return parent::sendError("retrieve_password_email_failure", "The email could not be sent. Your site may not be correctly configured to send emails.", 401);
        }
    
        return new WP_REST_Response(array(
                    'status' => 'success',
                ) , 200);;
    }


    public function register()
    {
        $json = file_get_contents('php://input');
        $params = json_decode($json);
        $usernameReq = $params->username;
        $emailReq = $params->email;
        $secondsReq = $params->seconds;
        $nonceReq = $params->nonce;
        $roleReq = $params->role;
        if ($roleReq && $roleReq != "subscriber" && $roleReq != "wcfm_vendor" && $roleReq != "seller")
        {
            return parent::sendError("invalid_role", "Role is invalid.", 400);
        }
        $userPassReq = $params->user_pass;
        $userLoginReq = $params->user_login;
        $userEmailReq = $params->user_email;
        $notifyReq = $params->notify;

        $username = sanitize_user($usernameReq);

        $email = sanitize_email($emailReq);

        if ($secondsReq)
        {
            $seconds = (int)$secondsReq;
        }
        else
        {
            $seconds = 120960000;
        }
        if (!validate_username($username))
        {
            return parent::sendError("invalid_username", "Username is invalid.", 400);
        }
        elseif (username_exists($username))
        {
            return parent::sendError("existed_username", "Username already exists.", 400);
        }
        else
        {
            if (!is_email($email))
            {
                return parent::sendError("invalid_email", "E-mail address is invalid.", 400);
            }
            elseif (email_exists($email))
            {
                return parent::sendError("existed_email", "E-mail address is already in use.", 400);
            }
            else
            {
                if (!$userPassReq)
                {
                    $params->user_pass = wp_generate_password();
                }

                $allowed_params = array(
                    'user_login',
                    'user_email',
                    'user_pass',
                    'display_name',
                    'user_nicename',
                    'user_url',
                    'nickname',
                    'first_name',
                    'last_name',
                    'description',
                    'rich_editing',
                    'user_registered',
                    'role',
                    'jabber',
                    'aim',
                    'yim',
                    'comment_shortcuts',
                    'admin_color',
                    'use_ssl',
                    'show_admin_bar_front',
                );

                $dataRequest = $params;

                foreach ($dataRequest as $field => $value)
                {
                    if (in_array($field, $allowed_params))
                    {
                        $user[$field] = trim(sanitize_text_field($value));
                    }
                }

                $user['role'] = $roleReq ? sanitize_text_field($roleReq) : get_option('default_role');
                $user_id = wp_insert_user($user);

                if (is_wp_error($user_id))
                {
                    return parent::sendError($user_id->get_error_code() , $user_id->get_error_message() , 400);
                }

                // if ($userPassReq && $notifyReq && $notifyReq == 'no') {
                //     $notify = '';
                // } elseif ($notifyReq && $notifyReq != 'no') {
                //     $notify = $notifyReq;
                // }
                // if ($user_id) {
                //     wp_new_user_notification($user_id, '', $notify);
                // }
                
            }
        }

        $expiration = time() + apply_filters('auth_cookie_expiration', $seconds, $user_id, true);
        $cookie = wp_generate_auth_cookie($user_id, $expiration, 'logged_in');

        return array(
            "cookie" => $cookie,
            "user_id" => $user_id,
        );
    }

    public function generate_auth_cookie()
    {
        $json = file_get_contents('php://input');
        $params = json_decode($json);
        if (!isset($params->username) || !isset($params->username))
        {
            return parent::sendError("invalid_login", "Invalid params", 400);
        }
        $username = $params->username;
        $password = $params->password;

        if ($params->seconds)
        {
            $seconds = (int)$params->seconds;
        }
        else
        {
            $seconds = 1209600;
        }

        $user = wp_authenticate($username, $password);

        if (is_wp_error($user))
        {
            return parent::sendError($user->get_error_code() , "Invalid username/email and/or password.", 401);
        }


        $response = $this->getUserResponse($user->ID,$avatar);
        return $response;
    }

    public function fb_connect($request)
    {
        $fields = 'id,name,first_name,last_name,email';
        $enable_ssl = true;
        $access_token = $request["access_token"];
        if (!isset($access_token))
        {
            return parent::sendError("invalid_login", "You must include a 'access_token' variable. Get the valid access_token for this app from Facebook API.", 400);
        }
        $url = 'https://graph.facebook.com/me/?fields=' . $fields . '&access_token=' . $access_token;

        $result = wp_remote_retrieve_body(wp_remote_get($url));

        $result = json_decode($result, true);

        if (isset($result["email"]))
        {

            $user_email = $result["email"];
            $email_exists = email_exists($user_email);

            if ($email_exists)
            {
                $user = get_user_by('email', $user_email);
                $user_id = $user->ID;
                $user_name = $user->user_login;
            }

            if (!$user_id && $email_exists == false)
            {

                $user_name = strtolower($result['first_name'] . '.' . $result['last_name']);

                while (username_exists($user_name))
                {
                    $i++;
                    $user_name = strtolower($result['first_name'] . '.' . $result['last_name']) . '.' . $i;

                }

                $random_password = wp_generate_password($length = 12, $include_standard_special_chars = false);
                $userdata = array(
                    'user_login' => $user_name,
                    'user_email' => $user_email,
                    'user_pass' => $random_password,
                    'display_name' => $result["name"],
                    'first_name' => $result['first_name'],
                    'last_name' => $result['last_name'],
                    'user' => $result
                );

                $user = wp_insert_user($userdata);
                if ($user) $user_account = 'user registered.';
                $response = $this->getUserResponse($user,$avatar);
            }
            else
            {
                if ($user) $user_account = 'user logged in.';
                $response = $this->getUserResponse($user->ID,$avatar);
            }
        }
        else
        {
            return parent::sendError("invalid_login", "Your 'access_token' did not return email of the user. Without 'email' user can't be logged in or registered. Get user email extended permission while joining the Facebook app.", 400);
        }

        return $response;
    }

    public function firebase_sms_login($request)
    {
        $phone = $request["phone"];
        if (!isset($phone))
        {
            return parent::sendError("invalid_login", "You must include a 'phone' variable.", 400);
        }
        $domain = $_SERVER['SERVER_NAME'];
        if (count(explode(".", $domain)) == 1)
        {
            $domain = "flutter.io";
        }
        $user_name = $phone;
        $user_email = $phone . "@" . $domain;
        $email_exists = email_exists($user_email);
        $user_pass = wp_generate_password($length = 12, $include_standard_special_chars = false);
        if ($email_exists)
        {
            $user = get_user_by('email', $user_email);
            $user_id = $user->ID;
            $user_name = $user->user_login;
            wp_update_user(array(
                'ID' => $user_id,
                'user_pass' => $user_pass
            ));
        }

        if (!$user_id && $email_exists == false)
        {

            while (username_exists($user_name))
            {
                $i++;
                $user_name = strtolower($user_name) . '.' . $i;

            }

            $userdata = array(
                'user_login' => $user_name,
                'user_email' => $user_email,
                'user_pass' => $user_pass,
                'display_name' => $user_name,
                'first_name' => $user_name,
                'last_name' => ""
            );

            $user = wp_insert_user($userdata);
            if ($user) $user_account = 'user registered.';
            $response = $this->getUserResponse($user,$avatar);
        }
        else
        {
            if ($user) $user_account = 'user logged in.';
            $response = $this->getUserResponse($user->ID,$avatar);
        }

        return $response;

    }


    function jwtDecode($token){
        $splitToken = explode(".", $token);
        $payloadBase64 = $splitToken[1]; // Payload is always the index 1
        $decodedPayload = json_decode(urldecode(base64_decode($payloadBase64)), true);
        return $decodedPayload;
    }

    public function apple_login($request)
    {
        $json = file_get_contents('php://input');
        $params = json_decode($json, TRUE);
        $token = $params["token"];
        $decoded = $this->jwtDecode($token);
        $user_email = $decoded["email"];
        if (!isset($user_email)) {
            return parent::sendError("invalid_login","Can't get the email to create account.", 400);
        }
        $display_name = explode("@", $user_email)[0];
        $user_name = $display_name;
        $email_exists = email_exists($user_email);
        if($email_exists) {
            $user = get_user_by( 'email', $user_email );
            $user_id = $user->ID;
        }else{
            $i = 0;
            while (username_exists($user_name)) {
                $i++;
                $user_name = strtolower($user_name) . '.' . $i;
            }
            $random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
            $userdata = array(
                'user_login'    => $display_name,
                'user_email'    => $user_email,
                'user_pass'  => $random_password,
                'display_name'  => $display_name,
                'first_name'  => $display_name,
                'last_name'  => '');
            $user_id = wp_insert_user( $userdata ) ;
        }
        return $this->getUserResponse( $user_id,$avatar);
    }

    public function google_login($request)
    {
        $access_token = $request["access_token"];
        if (!isset($access_token))
        {
            return parent::sendError("invalid_login", "You must include a 'access_token' variable. Get the valid access_token for this app from Google API.", 400);
        }

        $url = 'https://www.googleapis.com/oauth2/v1/userinfo?alt=json&access_token=' . $access_token;

        $result = wp_remote_retrieve_body(wp_remote_get($url));

        $result = json_decode($result, true);
        if (isset($result["email"]))
        {
            $firstName = $result["given_name"];
            $lastName = $result["family_name"];
            $email = $result["email"];
            $avatar = $result["picture"];

            $display_name = $firstName . " " . $lastName;
            $user_name = $firstName . "." . $lastName;
            $user_email = $email;
            $email_exists = email_exists($user_email);

            if ($email_exists)
            {
                $user = get_user_by('email', $user_email);
                $user_id = $user->ID;
                $user_name = $user->user_login;
            }

            if (!$user_id && $email_exists == false)
            {
                while (username_exists($user_name))
                {
                    $i++;
                    $user_name = strtolower($user_name) . '.' . $i;
                }

                $random_password = wp_generate_password($length = 12, $include_standard_special_chars = false);
                $userdata = array(
                    'user_login' => $user_name,
                    'user_email' => $user_email,
                    'user_pass' => $random_password,
                    'display_name' => $display_name,
                    'first_name' => $display_name,
                    'last_name' => ""
                );

                $user = wp_insert_user($userdata);
                if ($user) $user_account = 'user registered.';
                $response = $this->getUserResponse($user,$avatar);
            }
            else
            {
                if ($user) $user_account = 'user logged in.';
                $response = $this->getUserResponse($user->ID,$avatar);
            }

            

     

            return $response;
        }
        else
        {
            return parent::sendError("invalid_login", "Your 'token' did not return email of the user. Without 'email' user can't be logged in or registered. Get user email extended permission while joining the Google app.", 400);
        }
    }

    public function get_currentuserinfo($request)
    {
        $cookie = $request["cookie"];
        if (!isset($cookie))
        {
            return parent::sendError("invalid_login", "You must include a 'cookie' var in your request. Use the `generate_auth_cookie` method.", 401);
        }

        $user_id = wp_validate_auth_cookie($cookie, 'logged_in');
        if (!$user_id)
        {
            return parent::sendError("invalid_token", "Invalid cookie", 401);
        }
        $user = get_userdata($user_id);
        $response = $this->getUserResponse($user->ID,$avatar);
        return $response;
    }

    function update_user_profile()
    {
        global $json_api;
        $json = file_get_contents('php://input');
        $params = json_decode($json);
        $cookie = $params->cookie;
        if (!isset($cookie)) {
            return parent::sendError("invalid_login","You must include a 'cookie' var in your request. Use the `generate_auth_cookie` method.", 401);
        }
		$user_id = wp_validate_auth_cookie($cookie, 'logged_in');
		if (!$user_id) {
			return parent::sendError("invalid_token","Invalid cookie` method.", 401);
		}

        $user_update = array( 'ID' => $user_id);
        if (isset($params->user_pass)) {
            $user_update['user_pass'] = $params->user_pass;
        }
        if (isset($params->user_nicename)) {
            $user_update['user_nicename'] = $params->user_nicename;
        }
        if (isset($params->user_email)) {
            $user_update['user_email'] = $params->user_email;
        }
        if (isset($params->user_url)) {
            $user_update['user_url'] = $params->user_url;
        }
        if (isset($params->display_name)) {
            $user_update['display_name'] = $params->display_name;
        }
        if (isset($params->display_name)) {
            $user_update['first_name'] = $params->first_name;
        }
        if (isset($params->display_name)) {
            $user_update['last_name'] = $params->last_name;
        }
        if (isset($params->shipping_company)) {
            update_user_meta( $user_id, 'shipping_company', $params->shipping_company,'' );
        }
        if (isset($params->shipping_state)) {
            update_user_meta( $user_id, 'shipping_state', $params->shipping_state,'' );
        }
        if (isset($params->shipping_address_1)) {
            update_user_meta( $user_id, 'shipping_address_1', $params->shipping_address_1,'' );
        }
        if (isset($params->shipping_address_2)) {
            update_user_meta( $user_id, 'shipping_address_2', $params->shipping_address_2,'' );
        }
        if (isset($params->shipping_city)) {
            update_user_meta( $user_id, 'shipping_city', $params->shipping_city,'' );
        }
        if (isset($params->shipping_country)) {
            update_user_meta( $user_id, 'shipping_country', $params->shipping_country,'' );
        }
        if (isset($params->shipping_postcode)) {
            update_user_meta( $user_id, 'shipping_postcode', $params->shipping_postcode,'' );
        }
        
        if (isset($params->avatar)) {
            $count = 1;

            require_once (ABSPATH . '/wp-load.php');
            require_once (ABSPATH . 'wp-admin' . '/includes/file.php');
            require_once (ABSPATH . 'wp-admin' . '/includes/image.php');
            $imgdata = $params->avatar;
            $imgdata = trim($imgdata);
            $imgdata = str_replace('data:image/png;base64,', '', $imgdata);
            $imgdata = str_replace('data:image/jpg;base64,', '', $imgdata);
            $imgdata = str_replace('data:image/jpeg;base64,', '', $imgdata);
            $imgdata = str_replace('data:image/gif;base64,', '', $imgdata);
            $imgdata = str_replace(' ', '+', $imgdata);
            $imgdata = base64_decode($imgdata);
            $f = finfo_open();
            $mime_type = finfo_buffer($f, $imgdata, FILEINFO_MIME_TYPE);
            $type_file = explode('/', $mime_type);
            $avatar = time() . '_' . $count . '.' . $type_file[1];
    
            $uploaddir = wp_upload_dir();
            $myDirPath = $uploaddir["path"];
            $myDirUrl = $uploaddir["url"];
    
            file_put_contents($uploaddir["path"] . '/' . $avatar, $imgdata);
    
            $filename = $myDirUrl . '/' . basename($avatar);
            $wp_filetype = wp_check_filetype(basename($filename) , null);
            $uploadfile = $uploaddir["path"] . '/' . basename($filename);
    
            $attachment = array(
                "post_mime_type" => $wp_filetype["type"],
                "post_title" => preg_replace("/\.[^.]+$/", "", basename($filename)) ,
                "post_content" => "",
                "post_author" => $user_id,
                "post_status" => "inherit",
                'guid' => $myDirUrl . '/' . basename($filename) ,
            );
    
            $attachment_id = wp_insert_attachment($attachment, $uploadfile);
            $attach_data=  apply_filters( 'wp_generate_attachment_metadata', $attachment, $attachment_id, 'create' );
            // $attach_data = wp_generate_attachment_metadata($attachment_id, $uploadfile);
            wp_update_attachment_metadata($attachment_id, $attach_data);
            $url = wp_get_attachment_image_src($attachment_id);
            update_user_meta( $user_id, 'user_avatar', $url,'' );
        }

        if (isset($params->deviceToken)) {
            update_option("mstore_device_token_".$user_id, $params->deviceToken);
        }
        $user_data = wp_update_user($user_update);
 
        if ( is_wp_error( $user_data ) ) {
          // There was an error; possibly this user doesn't exist.
            echo 'Error.';
        }
        return $this->getUserResponse($user_id,$avatar);
    }

    public function getUserResponse($user_id, $avatar){
        
        $user = get_userdata($user_id);
        $expiration = time() + apply_filters('auth_cookie_expiration', 120960000, $user->ID, true);
        $cookie = wp_generate_auth_cookie($user->ID, $expiration, 'logged_in');
        $avatar = get_user_meta( $user->ID, 'user_avatar', true);
        if(is_bool($avatar)){
            $avatar = get_avatar_url($user->ID);
        }
        else{
            $avatar = $avatar[0];
        }
        
        $response['id'] = $user->ID;
        $response['cookie'] = $cookie;
        $response['username'] = $user->user_login;
        $response['email'] = $user->user_email;
        $response['displayname'] = $user->display_name;
        $response['firstname'] = $user->user_firstname;
        $response['lastname'] = $user->last_name;
        $response['role'] = $user->roles;
        $response['avatar'] = $avatar;
        
        $billing['first_name'] = get_user_meta($user_id,'billing_first_name',true);
        $billing['last_name'] = get_user_meta($user_id,'billing_last_name',true);
        $billing['company'] = get_user_meta($user_id,'billing_company',true);
        $billing['address_1'] = get_user_meta($user_id,'billing_address_1',true);
        $billing['address_2'] = get_user_meta($user_id,'billing_address_2',true);
        $billing['city'] = get_user_meta($user_id,'billing_city',true);
        $billing['postcode'] = get_user_meta($user_id,'billing_postcode',true);
        $billing['country'] = get_user_meta($user_id,'billing_country',true);
        $billing['state'] = get_user_meta($user_id,'billing_state',true);
        $billing['email'] = get_user_meta($user_id,'billing_email',true);
        $billing['phone'] = get_user_meta($user_id,'billing_phone',true);


        $shipping['first_name'] = get_user_meta($user_id,'shipping_first_name',true);
        $shipping['last_name'] = get_user_meta($user_id,'shipping_last_name',true);
        $shipping['company'] = get_user_meta($user_id,'shipping_company',true);
        $shipping['address_1'] = get_user_meta($user_id,'shipping_address_1',true);
        $shipping['address_2'] = get_user_meta($user_id,'shipping_address_2',true);
        $shipping['city'] = get_user_meta($user_id,'shipping_city',true);
        $shipping['postcode'] = get_user_meta($user_id,'shipping_postcode',true);
        $shipping['country'] = get_user_meta($user_id,'shipping_country',true);
        $shipping['state'] = get_user_meta($user_id,'shipping_state',true);

        $response['billing'] = $billing;
        $response['shipping']= $shipping;
        
        return apply_filters("get_user_response", $response, $user_id, $avatar);
    }
}

