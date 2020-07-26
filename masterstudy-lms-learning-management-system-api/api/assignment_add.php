<?php
add_action('rest_api_init', function ($server) {
    register_rest_route(STM_LMS_API, '/assignment/add', array(
        'args' => array(
            'course_id' => array(
                'required' => true,
                'validate_callback' => function ($param) {
                    return is_numeric($param);
                },
                'sanitize_callback' => 'absint'
            ),
            'user_assignment_id' => array(
                'required' => true,
                'validate_callback' => function ($param) {
                    return is_numeric($param);
                },
                'sanitize_callback' => 'absint'
            ),
        ),

        array(
            'methods' => 'POST',
            'callback' => function ($request) {

                $course_id = $request->get_param('course_id');
                $user_assignment_id = $request->get_param('user_assignment_id');
                $content = $request->get_param('content');

                $token = $request->get_header('token');
                $user_token = stm_lms_api_token($token);
                if (is_wp_error($user_token)) return $user_token;
                $user_id = (empty($user_id)) ? $user_token : $user_id;

                $content = (!empty($content)) ? wp_kses_post($content) : '';

                $assignment_student_id = intval(get_post_meta($user_assignment_id, 'student_id', true));
                if ($assignment_student_id !== $user_id) return new WP_Error('wrong_assignment', 'Assignment not related to this user', array('status' => 404));

                STM_LMS_Assignments::_stm_lms_accept_draft_assignment($user_assignment_id, $course_id, $content);

                return array(
                    'status' => 'OK'
                );
            }
        ),
    ));
});