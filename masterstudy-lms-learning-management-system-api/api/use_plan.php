<?php
add_action('rest_api_init', function ($server) {
    register_rest_route(STM_LMS_API, '/use_plan', array(
        array(
            'methods' => 'PUT',
            'args' => array(
                'course_id' => array(
                    'required' => true,
                    'validate_callback' => function ($param) {
                        return is_numeric($param);
                    },
                    'sanitize_callback' => 'absint'
                ),
                'subscription_id' => array(
                    'required' => true,
                    'validate_callback' => function ($param) {
                        return is_numeric($param);
                    },
                    'sanitize_callback' => 'absint'
                ),
            ),
            'callback' => function ($request) {

                $course_id = $request->get_param('course_id');
                $subscription_id = $request->get_param('subscription_id');
                $token = $request->get_header('token');
                $user_id = stm_lms_api_token($token);
                if (is_wp_error($user_id)) return $user_id;

                $r = STM_LMS_Subscriptions::_use_membership($user_id, $course_id, $subscription_id);

                return $r;

            }
        )
    ));
});