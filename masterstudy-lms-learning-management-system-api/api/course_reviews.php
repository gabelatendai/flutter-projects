<?php

add_action('rest_api_init', function ($server) {
    register_rest_route(STM_LMS_API, '/course_reviews', array(
        'args' => array(
            'id' => array(
                'required' => true,
                'validate_callback' => function ($param) {
                    return is_numeric($param);
                },
                'sanitize_callback' => 'absint'
            ),
        ),
        array(
            'methods' => 'GET',
            'callback' => function ($request) {

                $id = $request->get_param('id');

                if (get_post_type($id) !== 'stm-courses') {
                    return new WP_Error('invalid_course_id', 'Invalid ID', array('status' => 404));
                }

                $r = STM_LMS_Reviews::_get_reviews($id, 0);

                return ($r);

            }
        ),
        array(
            'methods' => 'PUT',
            'args' => array(
                'mark' => array(
                    'required' => true,
                    'validate_callback' => function ($param) {
                        return is_numeric($param);
                    },
                    'sanitize_callback' => 'absint'
                ),
                'review' => array(
                    'required' => true,
                ),
            ),
            'callback' => function ($request) {
                $id = $request->get_param('id');
                $review = $request->get_param('review');
                $mark = $request->get_param('mark');

                $token = $request->get_header('token');
                $user_id = stm_lms_api_token($token);
                if (is_wp_error($user_id)) return $user_id;

                return STM_LMS_Reviews::_add_review($id, $mark, $review);

            },
        ),

    ));
});