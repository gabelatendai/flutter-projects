<?php
add_action('rest_api_init', function ($server) {
    register_rest_route(STM_LMS_API, '/user_plans', array(
        array(
            'methods' => 'POST',
            'callback' => function ($request) {

                $token = $request->get_header('token');
                $user_id = stm_lms_api_token($token);
                if (is_wp_error($user_id)) return $user_id;

                return STM_LMS_Subscriptions::user_subscription_levels(false, $user_id);

            }
        )
    ));
});