<?php
add_action('rest_api_init', function ($server) {
    register_rest_route(STM_LMS_API, '/registration', array(
        'methods' => 'POST',
        'args' => array(
            'login' => array(
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'email' => array(
                'required' => true,
                'sanitize_callback' => 'sanitize_email',
                'validate_callback' => function ($param) {
                    return is_email($param);
                },
            ),
            'password' => array(
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field'
            ),
//            'password_duplicate' => array(
//                'required' => true,
//                'sanitize_callback' => 'sanitize_text_field'
//            ),
        ),
        'callback' => function ($request) {

            $login = $request->get_param('login');
            $email = $request->get_param('email');
            $password = $request->get_param('password');
            //$password_duplicate = $request->get_param('password_duplicate');

            /*Check if passwords equal*/
//            if($password !== $password_duplicate) {
//                return new WP_Error('password_match', 'Passwords do not match', array('status' => 400));
//            }

            $user_id = wp_create_user($login, $password, $email);

            if (is_wp_error($user_id)) {
                return new WP_Error('user_exist', $user_id->get_error_message(), array('status' => 400));
            }

            return [
                'message' => esc_html__( 'Registration completed successfully.', 'stm-lms-api-domain'),
                'token' => stm_lms_api_get_user_token($user_id)
            ];

        },
    ));
});