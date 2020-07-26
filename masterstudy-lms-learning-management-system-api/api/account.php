<?php
add_action('rest_api_init', function ($server) {
    register_rest_route(STM_LMS_API, '/account', array(
        'methods' => 'GET',
        'args' => array(
            'id' => array(
                'validate_callback' => function ($param) {
                    return is_numeric($param);
                },
                'sanitize_callback' => 'absint'
            ),
        ),
        'callback' => function ($request) {

            $user_id = $request->get_param('id');

            $token = $request->get_header('token');
            $user_token = stm_lms_api_token($token);
            if (is_wp_error($user_token)) return $user_token;
            $user_id = (empty($user_id)) ? $user_token : $user_id;

            $data = STM_LMS_User::get_current_user($user_id, false, true);

            if(empty($data['id'])) return new WP_Error('no_instructor_id', 'Invalid ID', array('status' => 404));

            $ratings = stm_lms_api_instructor_rating($data);

            $data['rating'] = $ratings;
            $data['total_courses'] = intval(count_user_posts($user_id, 'stm-courses', true));
            $data['profile_url'] = STM_LMS_User::user_public_page_url($user_id);

            return $data;
        },
    ));
});
