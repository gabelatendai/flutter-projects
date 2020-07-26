<?php

add_action('rest_api_init', function ($server) {
    register_rest_route(STM_LMS_API, '/course', array(
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

                $token = $request->get_header('token');

                $user_id = 0;

                if (isset($token)) {
                    $user_id = stm_lms_api_token($token);

                    if (is_wp_error($user_id)) return $user_id;
                }

                $course = stm_lms_api_get_single_course_info($id, $user_id);

                $instructor_id = get_post_field('post_author', $id);
                $course['author'] = STM_LMS_User::get_current_user($instructor_id, false, true);
                if (!empty($course['author']['email'])) unset($course['author']['email']);
                if (!empty($course['author']['avatar'])) unset($course['author']['avatar']);
                $course['author']['rating'] = stm_lms_api_instructor_rating($course['author']);
                $course['author']['total_courses'] = intval(count_user_posts($user_id, 'stm-courses', true));

                $course['url'] = get_permalink($id);
                $course_item = get_post($id);
                if (class_exists('WPBMap')) {
                    WPBMap::addAllMappedShortcodes();
                }
                $course['description'] = stm_lms_api_webview_wrapper(apply_filters('the_content', $course_item->post_content));
                $course['meta'] = stm_lms_api_course_meta_fields($id);
                $course['announcement'] = stm_lms_api_webview_wrapper(get_post_meta($id, 'announcement', true));

                $course['rating']['details'] = stm_lms_api_detailed_rating($id);
                $course['purchase_label'] = stm_lms_api_buy_label();
                $course['curriculum'] = stm_lms_api_course_curriculum($id);
                $course['faq'] = stm_lms_api_course_faq($id);

                $course['trial'] = false;
                $shareware = get_post_meta($id, 'shareware', true);
                if (!empty($shareware) && $shareware === 'on') $course['trial'] = true;

                $course['first_lesson'] = null;
                $course['first_lesson_type'] = "";

                $first_lesson = STM_LMS_Course::get_first_lesson($id);
                if ($first_lesson) {
                    $course['first_lesson'] = (int)STM_LMS_Course::get_first_lesson($id);
                    $course['first_lesson_type'] = apply_filters('stm_lms_lesson_type', $course['first_lesson']);
                }


                $course['has_access'] = false;
                $has_access = STM_LMS_User::has_course_access($id);
                $course['has_access'] = (bool)$has_access;

                $course['schema'] = stm_lms_course_model();

                return stm_lms_api_prepare_rest($course);

            }
        ),
    ));
});

function stm_lms_api_get_single_course_info($id, $user_id = 0)
{
    $post_thumbnail_id = get_post_thumbnail_id($id);
    $featured = get_post_meta($id, 'featured', true);
    $regular_price = get_post_meta($id, 'price', true);
    $sale_price = STM_LMS_Course::get_sale_price($id);
    $price = $regular_price;

    if (empty($regular_price) and !empty($sale_price)) {
        $regular_price = $sale_price;
        $sale_price = '';
    }

    if (!empty($sale_price)) $price = $sale_price;

    $display_price = (!empty($regular_price)) ? STM_LMS_Helpers::display_price($regular_price) : '';
    $price = (!empty($price)) ? STM_LMS_Helpers::display_price($price) : '';
    $if_sale_price_exists = (!empty($sale_price)) ? $display_price : null;

    $rating = get_post_meta($id, 'course_marks', true);
    $rates = STM_LMS_Course::course_average_rate($rating);
    $average = $rates['average'];
    $percent = $rates['percent'];

    $not_saleable = get_post_meta($id, 'not_single_sale', true);
    $not_saleable = (!empty($not_saleable));

    $course = array(
        'id' => $id,
        'title' => get_the_title($id),
        'images' => array(
            'full' => stm_lms_api_image_url($post_thumbnail_id),
            'small' => stm_lms_api_image($post_thumbnail_id, 545, 320),
        ),
        'not_saleable' => $not_saleable,
        'categories' => stm_lms_get_terms_array($id, 'stm_lms_course_taxonomy', 'name'),
        'price' => array(
            'free' => empty($price),
            'price' => $price,
            'old_price' => $if_sale_price_exists,
        ),
        'rating' => array(
            'average' => floatval($average),
            'total' => (!empty($rating)) ? count($rating) : 0,
            'percent' => round($percent)
        ),
        'featured' => $featured,
        'status' => STM_LMS_Course::get_post_status($id)
    );

    if (!empty($_GET) and !empty($_GET['image_width']) and !empty($_GET['image_height'])) {
        $height = intval($_GET['image_height']);
        $width = intval($_GET['image_width']);

        if (!empty($width) and !empty($height)) {
            $course['images']['custom'] = stm_lms_api_image($post_thumbnail_id, $width, $height);
        }
    }

    $course['is_favorite'] = STM_LMS_User::is_wishlisted($id, $user_id);

    $course['categories_object'] = stm_lms_api_get_post_terms($id);

    stm_lms_api_stringify_all($course);
    stm_lms_api_empty_to_null($course);

    return $course;
}

function stm_lms_api_course_meta_fields($course_id)
{
    $meta = STM_LMS_Helpers::parse_meta_field($course_id);
    $meta_fields = array();

    if (!empty($meta['current_students'])) {
        $meta_fields[] = array(
            'type' => 'current_students',
            'label' => esc_html__('Enrolled', 'masterstudy-lms-learning-management-system'),
            'text' => sprintf(_n('%s student', '%s students', $meta['current_students'], 'masterstudy-lms-learning-management-system'), $meta['current_students']),
        );
    }

    if (!empty($meta['duration_info'])) {
        $meta_fields[] = array(
            'type' => 'duration',
            'label' => esc_html__('Duration', 'masterstudy-lms-learning-management-system'),
            'text' => strval($meta['duration_info']),
        );
    }

    if (!empty($meta['curriculum'])) {
        $curriculum_info = STM_LMS_Course::curriculum_info($meta['curriculum']);
        $meta_fields[] = array(
            'type' => 'curriculum',
            'label' => esc_html__('Lectures', 'masterstudy-lms-learning-management-system'),
            'text' => strval($curriculum_info['lessons']),
        );
    }

    if (!empty($meta['video_duration'])) {
        $meta_fields[] = array(
            'type' => 'video_duration',
            'label' => esc_html__('Video', 'masterstudy-lms-learning-management-system'),
            'text' => strval($meta['video_duration']),
        );
    }

    if (!empty($meta['level'])) {
        $levels = array(
            'beginner' => esc_html__('Beginner', 'masterstudy-lms-learning-management-system'),
            'intermediate' => esc_html__('Intermediate', 'masterstudy-lms-learning-management-system'),
            'advanced' => esc_html__('Advanced', 'masterstudy-lms-learning-management-system'),
        );
        $meta_fields[] = array(
            'type' => 'level',
            'label' => esc_html__('Level', 'masterstudy-lms-learning-management-system'),
            'text' => strval($levels[$meta['level']]),
        );
    }

    return $meta_fields;
}

function stm_lms_api_detailed_rating($course_id)
{

    $marks = array(
        '5' => 0,
        '4' => 0,
        '3' => 0,
        '2' => 0,
        '1' => 0,
    );

    $reviews = get_post_meta($course_id, 'course_marks', true);

    if (empty($reviews)) return $marks;

    $marks = array(
        '5' => 0,
        '4' => 0,
        '3' => 0,
        '2' => 0,
        '1' => 0,
    );
    foreach ($reviews as $review) {
        $marks[$review]++;
    }

    return $marks;
}

function stm_lms_api_course_curriculum($course_id)
{
    $curriculum = get_post_meta($course_id, 'curriculum', true);

    $curriculum_data = array();

    if (!empty($curriculum)) {
        $curriculum = explode(',', $curriculum);
        if (is_array($curriculum)) {
            $section = 1;
            foreach ($curriculum as $curriculum_item) {
                if (!is_numeric($curriculum_item)) {
                    $curriculum_data[] = array(
                        'type' => 'section',
                        'view' => sprintf(esc_html__("Section %s", 'stm_lms_api_domain'), $section),
                        'label' => $curriculum_item,
                    );
                    $section++;

                    continue;
                }

                $post_type = get_post_type($curriculum_item);
                $lesson_excerpt = get_post_meta($curriculum_item, 'lesson_excerpt', true);
                $meta = '';

                if ($post_type === 'stm-quizzes') {
                    $q = get_post_meta($curriculum_item, 'questions', true);
                    if (!empty($q)) {
                        $meta = sprintf(_n(
                            '%s question',
                            '%s questions',
                            count(explode(',', $q)),
                            'masterstudy-lms-learning-management-system'
                        ), count(explode(',', $q)));
                    }
                    $curriculum_data[] = array(
                        'type' => 'quiz',
                        'label' => get_the_title($curriculum_item),
                        'excerpt' => $lesson_excerpt,
                        'meta' => $meta,
                        'has_preview' => STM_LMS_Lesson::lesson_has_preview($curriculum_item)
                    );

                    continue;
                }

                $preview = get_post_meta($curriculum_item, 'preview', true);
                $meta = get_post_meta($curriculum_item, 'duration', true);
                $type = get_post_meta($curriculum_item, 'type', true);

                $preview_url = (!empty($preview)) ? STM_LMS_Course::item_url($course_id, $curriculum_item) : '';

                $curriculum_data[] = array(
                    'type' => 'lesson',
                    'view' => $type,
                    'label' => get_the_title($curriculum_item),
                    'duration' => $meta,
                    'excerpt' => $lesson_excerpt,
                    'lesson_id' => $curriculum_item,
                    'preview_url' => $preview_url,
                    'has_preview' => STM_LMS_Lesson::lesson_has_preview($curriculum_item)
                );

            }
        }
    }

    return $curriculum_data;
}

function stm_lms_api_course_faq($course_id)
{
    $faq = get_post_meta($course_id, 'faq', true);

    if (!empty($faq)) $faq = json_decode($faq, true);

    return $faq;
}