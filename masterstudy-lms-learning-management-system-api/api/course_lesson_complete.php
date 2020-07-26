<?php

add_action('rest_api_init', function ($server) {
    register_rest_route(STM_LMS_API, '/course/lesson/complete', array(
        'methods' => 'PUT',
        'args' => array(
            'course_id' => array(
                'required' => true,
                'validate_callback' => function ($param) {
                    return is_numeric($param);
                },
                'sanitize_callback' => 'absint'
            ),
            'item_id' => array(
                'required' => true,
                'validate_callback' => function ($param) {
                    return is_numeric($param);
                },
                'sanitize_callback' => 'absint'
            ),
        ),
        'callback' => function ($request) {

            $data = [
                'label' => esc_html__('Lesson Completed', 'stm-lms-api-domain')
            ];

            $course_id = $request->get_param('course_id');
            $lesson_id = $request->get_param('item_id');

            $token = $request->get_header('token');
            $user_token = stm_lms_api_token($token);
            if (is_wp_error($user_token)) return $user_token;
            $user_id = (empty($user_id)) ? $user_token : $user_id;

            if (!STM_LMS_User::has_course_access($course_id, $user_id)) stm_lms_api_error('no_access', 'Course is not available from this account');

            $curriculum = get_post_meta($course_id, 'curriculum', true);

            if (empty($curriculum)) stm_lms_api_error('no_item', 'Course do not have this lesson', 404);

            $curriculum = explode(',', $curriculum);

            if (!in_array($lesson_id, $curriculum)) stm_lms_api_error('no_item', 'Course do not have this lesson', 404);

            if (get_post_type($lesson_id) !== 'stm-lessons') stm_lms_api_error('not_lesson', 'Cheatin, hm?', 404);

            if (STM_LMS_Lesson::is_lesson_completed($user_id, $course_id, $lesson_id)) {
                wp_send_json($data);
            };

            $end_time = time();
            $start_time = get_user_meta($user_id, "stm_lms_course_started_{$course_id}_{$lesson_id}", true);

            stm_lms_add_user_lesson(compact('user_id', 'course_id', 'lesson_id', 'start_time', 'end_time'));
            STM_LMS_Course::update_course_progress($user_id, $course_id);

            do_action('stm_lms_lesson_passed', $user_id, $lesson_id);

            delete_user_meta($user_id, "stm_lms_course_started_{$course_id}_{$lesson_id}");

            return $data;

        },
    ));
});