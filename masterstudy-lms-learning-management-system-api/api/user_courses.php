<?php
add_action('rest_api_init', function ($server) {
    register_rest_route(STM_LMS_API, '/user_courses', array(
        array(
            'methods' => 'POST',
            'callback' => function ($request) {
                $page = $request->get_param('page');

                $token = $request->get_header('token');
                $user_id = stm_lms_api_token($token);
                if (is_wp_error($user_id)) return $user_id;

                if (empty($page)) $page = 1;

                $data = STM_LMS_User::_get_user_courses($page - 1);

                $width = $request->get_param('image_width');
                $height = $request->get_param('image_height');

                $width = (!empty($width)) ? $width : '800';
                $height = (!empty($height)) ? $height : '700';

                foreach ($data['posts'] as &$post) {
                    $post['categories_object'] = stm_lms_api_get_post_terms($post['id']);
                    $instructor_id = get_post_field('post_author', $post['id']);
                    $post['author'] = STM_LMS_User::get_current_user($instructor_id, false, true);
                    $post['app_image'] = stm_lms_api_image($post['image_id'], $width, $height);

                    if (!empty($post['lesson_id'])) {
                        $post['lesson_type'] = apply_filters('stm_lms_lesson_type', $post['lesson_id']);
                    }

                }

                stm_lms_api_stringify_all($data);
                stm_lms_api_empty_to_null($data);

                $data['offset'] = intval($data['offset']);
                $data['pages'] = intval($data['pages']);
                $data['total_posts'] = intval($data['total_posts']);

                $data['schema'] = stm_lms_user_courses_model();

                return stm_lms_api_prepare_rest($data);

            }
        )
    ));
});


function stm_lms_api_get_post_terms($post_id) {
    $terms = array();
    $terms_all = wp_get_post_terms($post_id, 'stm_lms_course_taxonomy');
    if (is_wp_error($terms_all)) $terms_all = array();

    if (!empty($terms_all)) {
        foreach ($terms_all as $term) {
            $meta_value = get_term_meta($term->term_id, 'course_app_image', true);
            $color = get_term_meta($term->term_id, 'course_color', true);
            if (empty($color)) $color = null;

            $term_data = array(
                'id' => $term->term_id,
                'name' => $term->name,
                'count' => $term->count,
                'color' => $color,
            );

            $term_data['image'] = !empty($meta_value) ? stm_lms_api_image($meta_value, 100, 100) : null;

            $terms[] = $term_data;
        }
    }

    return $terms;
}