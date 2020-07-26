<?php

add_action('rest_api_init', function ($server) {
    register_rest_route(STM_LMS_API, '/lesson/questions', array(
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
            'methods' => 'POST',
            'args' => array(
                'page' => array(
                    'validate_callback' => function ($param) {
                        return is_numeric($param);
                    },
                    'sanitize_callback' => 'absint'
                ),
                'search' => array(
                    'sanitize_callback' => 'esc_attr'
                ),
                'author__in' => array(
                    'validate_callback' => function ($param) {
                        return is_numeric($param);
                    },
                    'sanitize_callback' => 'absint'
                ),
            ),
            'callback' => function ($request) {

                $id = $request->get_param('id');
                $page = $request->get_param('page');
                $search = $request->get_param('search');
                $author__in = $request->get_param('author__in');

                $token = $request->get_header('token');
                $user_token = stm_lms_api_token($token);
                if (is_wp_error($user_token)) return $user_token;
                $user_id = (empty($user_id)) ? $user_token : $user_id;

//                if (get_post_type($id) !== 'stm-lessons') {
//                    return new WP_Error('invalid_lesson_id', 'Invalid ID', array('status' => 404));
//                }

                $page = empty($page) ? $page - 1 : 0;
                if (empty($search)) $search = '';
                $author__in = $author__in ? $user_id : 0;

                $r = STM_LMS_Comments::_get_user_comments($user_id, $id, $page, $search, $author__in);

                return $r;
            }
        ),
        array(
            'methods' => 'PUT',
            'args' => array(
                'comment' => array(
                    'required' => true,
                    'sanitize_callback' => 'esc_attr'
                ),
                'parent' => array(
                    'validate_callback' => function ($param) {
                        return is_numeric($param);
                    },
                    'sanitize_callback' => 'absint'
                ),
            ),
            'callback' => function ($request) {

                $id = $request->get_param('id');
                $comment = $request->get_param('comment');
                $parent = $request->get_param('parent');

                $token = $request->get_header('token');
                $user_token = stm_lms_api_token($token);
                if (is_wp_error($user_token)) return $user_token;
                $user_id = (empty($user_id)) ? $user_token : $user_id;

//                if (get_post_type($id) !== 'stm-lessons') {
//                    return new WP_Error('invalid_lesson_id', 'Invalid ID', array('status' => 404));
//                }

                $comment = (!empty($comment)) ? $comment : '';
                $parent = (!empty($parent)) ? $parent : '';

                $r = STM_LMS_Comments::_add_comment(STM_LMS_User::get_current_user($user_id), $id, $comment, $parent);

                if (!empty($r['error']) and $r['error']) return new WP_Error('empty_comment', $r['message'], array('status' => 404));

                return $r;
            }
        )
    ));
});