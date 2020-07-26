<?php

add_action('rest_api_init', function ($server) {
    register_rest_route(STM_LMS_API, '/course/results', array(
        'methods' => 'POST',
        'args' => array(
            'course_id' => array(
                'required' => true,
                'validate_callback' => function ($param) {
                    return is_numeric($param);
                },
                'sanitize_callback' => 'absint'
            )
        ),
        'callback' => function ($request) {

            $course_id = $request->get_param('course_id');

            $token = $request->get_header('token');
            $user_token = stm_lms_api_token($token);
            if (is_wp_error($user_token)) return $user_token;
            $user_id = (empty($user_id)) ? $user_token : $user_id;

            $data = STM_LMS_Lesson::get_total_progress($user_id, $course_id);

            if(!empty($data['certificate_url'])) {
                $data['certificate_url'] = add_query_arg('stm_lms_app_buy', $data['certificate_url'], get_home_url());
            }

            $data['schema'] = stm_lms_course_results_model();

            return stm_lms_api_prepare_rest($data);

        },
    ));
});