<?php
add_action('rest_api_init', function ($server) {
    register_rest_route(STM_LMS_API, '/login', array(
        'methods' => 'POST',
        'args' => array(
            'login' => array(
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'password' => array(
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field'
            ),
        ),
        'callback' => function ($request) {

            $login = $request->get_param('login');
            $password = $request->get_param('password');

            $login_type = (is_email($login)) ? 'email' : 'login';

            /*Check if user exists*/
            $user = get_user_by($login_type, $login);
            if (!$user) return new WP_Error('user_not_exist', 'User not exist', array('status' => 400));

            $user_id = $user->ID;
            /*Now we have user, lets check its password*/
            if (!wp_check_password($password, $user->data->user_pass, $user->ID)) {
                return new WP_Error('wrong_password', 'Wrong Password', array('status' => 400));
            }

            $token = stm_lms_api_get_user_token($user_id);

            return ([
                'message' => esc_html__('Login completed successfully.', 'stm-lms-api-domain'),
                'token' => $token,
            ]);

        },
    ));
});

function stm_lms_api_user_token_name($user_id)
{
    return "lms_user_token_{$user_id}";
}

function stm_lms_api_get_current_user_token($user_id)
{
    $token_name = stm_lms_api_user_token_name($user_id);
    return get_option($token_name, '');
}

function stm_lms_api_get_user_token($user_id)
{
    $token_name = stm_lms_api_user_token_name($user_id);
    $token = get_option($token_name, '');

    if (!$token) {
        $token = $user_id . '|' . bin2hex(openssl_random_pseudo_bytes(16));
        update_option($token_name, $token);
    }

    return $token;
}

function stm_lms_api_check_token($token)
{
    $token_parts = explode('|', $token);
    if (!is_array($token_parts) and count($token_parts) !== 2) {
        return false;
    }

    $user_id = $token_parts[0];
    $original_token = stm_lms_api_get_current_user_token($user_id);

    return ($original_token === $token) ? $user_id : false;
}

function stm_lms_api_token($token) {

    $user_id = stm_lms_api_check_token($token);

    if(!($user_id)) {
        return (new WP_Error('invalid_token', 'Invalid Token', array('status' => 401)));
    }

    wp_clear_auth_cookie();
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id, 1, is_ssl());

    return intval($user_id);
}