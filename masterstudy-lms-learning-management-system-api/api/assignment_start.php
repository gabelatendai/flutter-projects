<?php
add_action('rest_api_init', function ($server) {
    register_rest_route(STM_LMS_API, '/assignment/start', array(
        'args' => array(
            'course_id' => array(
                'required' => true,
                'validate_callback' => function ($param) {
                    return is_numeric($param);
                },
                'sanitize_callback' => 'absint'
            ),
            'assignment_id' => array(
                'required' => true,
                'validate_callback' => function ($param) {
                    return is_numeric($param);
                },
                'sanitize_callback' => 'absint'
            ),
        ),
        array(
            'methods' => 'PUT',
            'callback' => function ($request) {

                $course_id = $request->get_param('course_id');
                $assignment_id = $request->get_param('assignment_id');


                $token = $request->get_header('token');
                $user_token = stm_lms_api_token($token);
                if (is_wp_error($user_token)) return $user_token;
                $user_id = (empty($user_id)) ? $user_token : $user_id;

                $user = STM_LMS_User::get_current_user($user_id);

                $assignment_name = get_the_title($assignment_id);

                $has_access = STM_LMS_User::has_course_access($course_id, $assignment_id);
                if (!$has_access) return new WP_Error('no_access', 'No access', array('status' => 400));

                $has_current_assignments = STM_LMS_Assignments::has_current_assignments($assignment_id, $user_id);

                if ($has_current_assignments) return new WP_Error('assignment_in_progress', 'User already have current Assignment', array('status' => 400));

                $assignment_try = STM_LMS_Assignments::number_of_assignments($assignment_id) + 1;

                $new_assignment = array(
                    'post_type' => 'stm-user-assignment',
                    'post_status' => 'draft',
                    'post_title' => "{$user['login']} on \"{$assignment_name}\""
                );

                $user_assignment_id = wp_insert_post($new_assignment);

                update_post_meta($user_assignment_id, 'try_num', $assignment_try);
                update_post_meta($user_assignment_id, 'start_time', time() * 1000);
                update_post_meta($user_assignment_id, 'status', '');
                update_post_meta($user_assignment_id, 'assignment_id', $assignment_id);
                update_post_meta($user_assignment_id, 'student_id', $user_id);

                return array(
                    'user_assignment_id' => $user_assignment_id
                );
            }
        ),
    ));
});