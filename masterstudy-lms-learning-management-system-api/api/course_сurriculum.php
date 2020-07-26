<?php

add_action('rest_api_init', function ($server) {
    register_rest_route(STM_LMS_API, '/course_curriculum', array(
        'methods' => 'POST',
        'args' => array(
            'id' => array(
                'required' => true,
                'validate_callback' => function ($param) {
                    return is_numeric($param);
                },
                'sanitize_callback' => 'absint'
            ),
        ),
        'callback' => function ($request) {
            $id = $request->get_param('id');

            $token = $request->get_header('token');
            $user_token = stm_lms_api_token($token);
            if (is_wp_error($user_token)) return $user_token;

            $user_id = (empty($user_id)) ? $user_token : $user_id;

            if (!STM_LMS_User::has_course_access($id)) stm_lms_api_error('no_access', 'Course is not available from this account');

            $curriculum = get_post_meta($id, 'curriculum', true);

            if (empty($curriculum)) stm_lms_api_error('no_items', 'Course do not have curriculum', 404);

            $curriculum = explode(',', $curriculum);
            $sections_data = STM_LMS_Lesson::create_sections($curriculum);

            $sections = array();
            foreach ($sections_data as $sections_datum) {
                $sections[] = $sections_datum;
            }

            foreach ($sections as $index => &$section_info) {

                $curriculum = (!empty($section_info['items'])) ? $section_info['items'] : array();

                foreach ($curriculum as $curriculum_index => $curriculum_item) {

                    $item_id = intval($curriculum_item);

                    $title = get_the_title($curriculum_item);
                    $content_type = get_post_type($curriculum_item);
                    $quiz_info = array();

                    $previous_completed = (isset($completed)) ? $completed : 'first';
                    $has_preview = STM_LMS_Lesson::lesson_has_preview($curriculum_item);

                    $user = STM_LMS_User::get_current_user($user_id);
                    $user_id = $user['id'];

                    $duration = '';
                    $questions = '';

                    if ($content_type === 'stm-quizzes') {
                        $type = 'quiz';
                        $quiz_info = STM_LMS_Helpers::simplify_db_array(stm_lms_get_user_quizzes($user_id, $curriculum_item, array('progress')));
                        $completed = STM_LMS_Quiz::quiz_passed($curriculum_item, $user_id);

                        $q = get_post_meta($curriculum_item, 'questions', true);
                        if (!empty($q)):
                            $questions = sprintf(_n(
                                '%s question',
                                '%s questions',
                                count(explode(',', $q)),
                                'masterstudy-lms-learning-management-system'
                            ), count(explode(',', $q)));
                        endif;

                    } else if ($content_type === 'stm-assignments') {
                        $type = 'assignment';
                        $completed = class_exists('STM_LMS_Assignments') ? STM_LMS_Assignments::has_passed_assignment($id) : false;
                        $completed = (!empty($completed));
                    } else {
                        $completed = STM_LMS_Lesson::is_lesson_completed($user_id, $item_id, $curriculum_item);
                        $type = get_post_meta($curriculum_item, 'type', true);
                        $duration = get_post_meta($curriculum_item, 'duration', true);
                    }

                    if (empty($type)) $type = 'lesson';
                    if (empty($duration)) $duration = '';

                    $locked = str_replace(
                        'prev-status-',
                        '',
                        apply_filters("stm_lms_prev_status", "{$previous_completed}", $item_id, $curriculum_item, $user_id)
                    );

                    $locked = (empty($locked));

                    $item_data = compact('id', 'title', 'type', 'quiz_info', 'locked', 'completed', 'has_preview', 'duration', 'questions');

                    $item_data['dedede'] = str_replace(
                        'prev-status-',
                        '',
                        apply_filters("stm_lms_prev_status", "{$previous_completed}", $item_id, $curriculum_item, $user_id)
                    );

                    $section_info['section_items'][] = $item_data;

                    if (!isset($user_id)) $user_id = 0;

                }
            }

            $user_stats = STM_LMS_Helpers::simplify_db_array(stm_lms_get_user_course($user_id, $id, array('current_lesson_id', 'progress_percent')));
            if (empty($user_stats['current_lesson_id'])) $user_stats['current_lesson_id'] = STM_LMS_Lesson::get_first_lesson($id);

            $lesson_type = get_post_meta($user_stats['current_lesson_id'], 'type', true);
            if(empty($lesson_type)) $lesson_type = 'text';

            $user_stats['lesson_type'] = $lesson_type;

            $data = array_merge($user_stats, array('sections' => $sections));

            $data['schema'] = stm_lms_course_curriculum_model();

            $data = stm_lms_api_prepare_rest($data);
            stm_lms_api_empty_to_null($data);

            return $data;
        },
    ));
});