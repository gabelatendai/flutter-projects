<?php

add_action('rest_api_init', function ($server) {
    register_rest_route(STM_LMS_API, '/course/lesson', array(
        'methods' => 'POST',
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

            $data = array();

            $course_id = $request->get_param('course_id');
            $item_id = $request->get_param('item_id');

            $token = $request->get_header('token');
            $user_token = stm_lms_api_token($token);
            if (is_wp_error($user_token)) return $user_token;
            $user_id = (empty($user_id)) ? $user_token : $user_id;


            $has_preview = STM_LMS_Lesson::lesson_has_preview($item_id);

            if (!STM_LMS_User::has_course_access($course_id, $item_id) && !$has_preview) stm_lms_api_error('no_access', 'Course is not available from this account');

            $curriculum = get_post_meta($course_id, 'curriculum', true);

            if (empty($curriculum)) stm_lms_api_error('no_item', 'Course do not have this lesson', 404);

            $curriculum_full = $curriculum = explode(',', $curriculum);

            if (!in_array($item_id, $curriculum)) stm_lms_api_error('no_item', 'Course do not have this lesson', 404);

            $sections = STM_LMS_Lesson::create_sections($curriculum);

            foreach ($sections as $section) {
                if (in_array($item_id, $section['items'])) {
                    $data['section'] = array(
                        'label' => $section['title'],
                        'number' => $section['number'],
                        'index' => array_search($item_id, $section['items']) + 1
                    );
                }
            }

            do_action('stm_lms_lesson_started', $course_id, $item_id, $user_id);

            if (class_exists('WPBMap')) {
                WPBMap::addAllMappedShortcodes();
            }

            $item = get_post($item_id);

            $type = get_post_meta($item_id, 'type', true);
            $type = (!empty($type)) ? $type : 'text';

            $data['title'] = get_the_title($item_id);
            $data['type'] = (!empty($type)) ? $type : 'text';

            $styles = '';
            if($data['type'] === 'video') {
                $styles = "<style>body {background-color: #151A25;color: #fff;} body * {color: #fff;}}</style>";
            }

            $data['content'] = stm_lms_api_webview_wrapper(apply_filters('the_content', $item->post_content), $styles, 'lesson_wrapper');
            $data['video'] = get_post_meta($item_id, 'lesson_video_url', true);
            $data['video_poster'] = stm_lms_api_image_url(get_post_meta($item_id, 'lesson_video_poster', true));

            $curriculum = STM_LMS_Helpers::only_array_numbers($curriculum_full);

            if (in_array($item_id, $curriculum)) {

                $current_lesson_id = array_search($item_id, $curriculum);

                $data['prev_lesson_type'] = (!empty($curriculum[$current_lesson_id - 1])) ? apply_filters('stm_lms_lesson_type', $curriculum[$current_lesson_id - 1]) : "";
                $data['next_lesson_type'] = (!empty($curriculum[$current_lesson_id + 1])) ? apply_filters('stm_lms_lesson_type', $curriculum[$current_lesson_id + 1]) : "";
                $data['prev_lesson'] = (!empty($curriculum[$current_lesson_id - 1])) ? $curriculum[$current_lesson_id - 1] : '';
                $data['next_lesson'] = (!empty($curriculum[$current_lesson_id + 1])) ? $curriculum[$current_lesson_id + 1] : '';
                $data['next_lesson_available'] = false;

                if(!empty($data['next_lesson'])) {
                    $is_next_available = STM_LMS_User::has_course_access($course_id, $data['next_lesson']);
                    $data['next_lesson_available'] = (bool) $is_next_available;
                }

            }

            $data['completed'] = STM_LMS_Lesson::is_lesson_completed($user_id, $course_id, $item_id);

            return $data;

        },
    ));
});