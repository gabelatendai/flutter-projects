<?php
add_action('rest_api_init', function ($server) {
    register_rest_route(STM_LMS_API, '/assignment', array(
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
            'methods' => 'POST',
            'callback' => function ($request) {


                $course_id = $request->get_param('course_id');
                $item_id = $request->get_param('assignment_id');

                $token = $request->get_header('token');
                $user_token = stm_lms_api_token($token);
                if (is_wp_error($user_token)) return $user_token;
                $user_id = (empty($user_id)) ? $user_token : $user_id;

                $number_of_assignments = STM_LMS_Assignments::number_of_assignments($item_id);
                $passed = STM_LMS_Assignments::has_passed_assignment($item_id);
                $unpassed = STM_LMS_Assignments::has_unpassed_assignment($item_id);
                $reviewing = STM_LMS_Assignments::has_reviewing_assignment($item_id);
                $draft = STM_LMS_Assignments::has_draft_assignment($item_id);

                /**
                 * 1 Assignment not started
                 * 2 Assignment in progress
                 * 3 Assignment on review
                 * 4 Assignment not passed
                 * 5 Assignment passed
                 */

                if ($passed) {
                    /*----5----*/
                    $data = stm_lms_api_assignment_status_passed($passed);
                } else if ($reviewing) {
                    /*----3----*/
                    $data = stm_lms_api_assignment_status_pending($reviewing);
                } else if ($draft) {
                    /*----2----*/
                    $data = stm_lms_api_assignment_status_draft($item_id);
                    $data['draft_id'] = $draft['id'];
                } else if ($unpassed) {
                    /*----4----*/
                    $data = stm_lms_api_assignment_status_unpassed($unpassed);
                } else {
                    /*----1----*/
                    $data = stm_lms_api_assignment_status_new($item_id);
                }

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

                $curriculum = STM_LMS_Helpers::only_array_numbers($curriculum_full);

                if (in_array($item_id, $curriculum)) {
                    $current_lesson_id = array_search($item_id, $curriculum);
					$data['prev_lesson_type'] = (!empty($curriculum[$current_lesson_id - 1])) ? apply_filters('stm_lms_lesson_type', $curriculum[$current_lesson_id - 1]) : "";
					$data['next_lesson_type'] = (!empty($curriculum[$current_lesson_id + 1])) ? apply_filters('stm_lms_lesson_type', $curriculum[$current_lesson_id + 1]) : "";

					$data['prev_lesson'] = (!empty($curriculum[$current_lesson_id - 1])) ? $curriculum[$current_lesson_id - 1] : '';
                    $data['next_lesson'] = (!empty($curriculum[$current_lesson_id + 1])) ? $curriculum[$current_lesson_id + 1] : '';
                }

                return $data;
            },
        ),
    ));
});

function stm_lms_api_assignment_status_new($item_id)
{
    $q = new WP_Query(array(
        'posts_per_page' => 1,
        'post_type' => 'stm-assignments',
        'post__in' => array($item_id)
    ));

    $assignment = array(
        'status' => 'new'
    );

    if ($q->have_posts()) {
        while ($q->have_posts()) {
            $q->the_post();

            $assignment_data = get_post(get_the_ID());
            if (class_exists('WPBMap')) {
                WPBMap::addAllMappedShortcodes();
            }

            $assignment['title'] = $assignment_data->post_title;
            $assignment['content'] = stm_lms_api_webview_wrapper(apply_filters('the_content', $assignment_data->post_content));
            $assignment['button'] = esc_html__('Start Assignment', 'masterstudy-lms-learning-management-system-api');
        }
    }

    return $assignment;
}

function stm_lms_api_assignment_status_draft($assignment_id)
{

    $assignment_data = get_post($assignment_id);
    if (class_exists('WPBMap')) {
        WPBMap::addAllMappedShortcodes();
    }

    $data = array(
        'status' => 'draft',
        'translations' => array(
            'title' => esc_html__('Title...', 'masterstudy-lms-learning-management-system-api'),
            'content' => esc_html__('Enter text...', 'masterstudy-lms-learning-management-system-api'),
            'files' => esc_html__('Attach Files', 'masterstudy-lms-learning-management-system-api'),
        ),
        'title' => get_the_title($assignment_id),
        'content' => apply_filters('the_content', $assignment_data->post_content)
    );

    $unpassed = STM_LMS_Assignments::has_unpassed_assignment($assignment_id);
    if (!empty($unpassed['meta']) and !empty($unpassed['meta']['editor_comment']) and !empty($unpassed['meta']['editor_comment'][0])) {
        $data['instructor_comment'] = $unpassed['meta']['editor_comment'][0];
        $data['instructor_data'] = STM_LMS_User::get_current_user($assignment_data->post_author);
    }

    return $data;
}

function stm_lms_api_assignment_status_pending($reviewing)
{
    $reviewing['status'] = 'pending';
    $reviewing['label'] = esc_html__('Your assignment pending review', 'masterstudy-lms-learning-management-system');
    if ($reviewing['meta']) unset($reviewing['meta']);
    $reviewing['files'] = STM_LMS_Assignments::uploaded_attachments($reviewing['id']);
    return $reviewing;
}

function stm_lms_api_assignment_status_unpassed($unpassed)
{

    $comment = "";
    if (!empty($unpassed['meta']) and !empty($unpassed['meta']['editor_comment']) and !empty($unpassed['meta']['editor_comment'][0])) {
        $comment = $unpassed['meta']['editor_comment'][0];
    }

    return array(
        'status' => 'unpassed',
        'title' => $unpassed['title'],
        'comment' => $comment,
        'id' => $unpassed['id'],
        'content' => $unpassed['content'],
        'label' => esc_html__('You failed assignment. Try Again.', 'masterstudy-lms-learning-management-system-api'),
        'files' => STM_LMS_Assignments::uploaded_attachments($unpassed['id'])
    );
}

function stm_lms_api_assignment_status_passed($passed)
{

    $comment = "";
    if (!empty($passed['meta']) and !empty($passed['meta']['editor_comment']) and !empty($passed['meta']['editor_comment'][0])) {
        $comment = $passed['meta']['editor_comment'][0];
    }

    return array(
        'status' => 'passed',
        'title' => $passed['title'],
        'comment' => $comment,
        'id' => $passed['id'],
        'content' => $passed['content'],
        'label' => esc_html__('You passed assignment.', 'masterstudy-lms-learning-management-system-api'),
        'files' => STM_LMS_Assignments::uploaded_attachments($passed['id'])
    );
}