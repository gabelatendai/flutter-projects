<?php
add_action('rest_api_init', function ($server) {
    register_rest_route(STM_LMS_API, '/assignment/add/file', array(
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

                $token = $request->get_header('token');
                $user_token = stm_lms_api_token($token);
                if (is_wp_error($user_token)) return $user_token;
                $user_id = (empty($user_id)) ? $user_token : $user_id;

                $assignment_student_id = intval(get_post_meta($user_assignment_id, 'student_id', true));

                if($assignment_student_id !== $user_id) return new WP_Error('wrong_assignment', 'Assignment not related to this user', array('status' => 404));

                $files = $request->get_file_params();
                $file = (!empty($files['file'])) ? $files['file'] : '';

                if (empty($file)) return new WP_Error('no_file', 'No file', array('status' => 404));

                $r = STM_LMS_Assignments::stm_lms_upload_assignment_file($user_assignment_id, $file, true);

                if($r['error']) return new WP_Error('error', $r['message'], array('status' => 404));

                return $r;
            }
        ),
    ));
});