<?php

add_action('rest_api_init', function ($server) {
    register_rest_route(STM_LMS_API, '/courses', array(
        'methods' => 'GET',
        'callback' => function ($request) {

            $token = $request->get_header('token');

            $user_id = 0;

            if (!empty($token)) {
                $user_id = stm_lms_api_token($token);

                if (is_wp_error($user_id)) return $user_id;
            }

            $per_page = stm_lms_api_per_page();
            $data = array(
                'page' => 1,
                'args' => array(),
                'courses' => array(),
            );

            $args = array(
                'post_type' => 'stm-courses',
                'posts_per_page' => $per_page
            );

            stm_lms_api_courses_per_page($args);
            stm_lms_api_courses_search($args);
            stm_lms_api_courses_page($args, $data);
            stm_lms_api_courses_sort_trending($args);
            stm_lms_api_courses_category($args);
            stm_lms_api_courses_author($args);
            stm_lms_api_courses_wishlist($args, $token);

            $data['args'] = $args;

            $q = new WP_Query($args);

            $data['total_pages'] = ceil($q->found_posts / $per_page);

            if ($q->have_posts()) {
                while ($q->have_posts()) {
                    $q->the_post();

                    $id = get_the_ID();

                    $course = stm_lms_api_get_single_course_info($id, $user_id);

                    $data['courses'][] = $course;

                }
            }

            stm_lms_api_stringify_all($data);
            stm_lms_api_empty_to_null($data);

            $data['schema'] = stm_lms_courses_model();

            wp_send_json(stm_lms_api_prepare_rest($data));
        },
    ));
});

function stm_lms_api_courses_per_page(&$args)
{
    if (!empty($_GET['per_page'])) $per_page = intval($_GET['per_page']);

    if (!empty($per_page)) $args['posts_per_page'] = intval($_GET['per_page']);
}

function stm_lms_api_courses_search(&$args)
{
    if (!empty($_GET['s'])) $search = sanitize_text_field($_GET['s']);

    if (!empty($search)) $args['s'] = $search;
}

function stm_lms_api_courses_page(&$args, &$data)
{
    if (!empty($_GET['page'])) $page = intval($_GET['page']);

    if (!empty($page)) {
        $args['offset'] = $page * $args['posts_per_page'] - $args['posts_per_page'];
        $data['page'] = $page;
    }

}

function stm_lms_api_courses_sort_trending(&$args)
{
    if (!empty($_GET['sort'])) $sort = sanitize_text_field($_GET['sort']);

    if($sort === 'free') {
        $args['meta_query'] = array(
            'relation' => 'AND',
            array(
                'relation' => 'OR',
                array(
                    'key' => 'price',
                    'value' => '',
                    'compare' => '=',
                ),
                array(
                    'key' => 'price',
                    'compare' => 'NOT EXISTS',
                ),
            ),
            array(
                'relation' => 'OR',
                array(
                    'key' => 'not_single_sale',
                    'value' => 'on',
                    'compare' => '!='
                ),
                array(
                    'key' => 'not_single_sale',
                    'compare' => 'NOT EXISTS',
                ),
            )
        );
    }

    if (!empty($sort)) {
        $sort = STM_LMS_Helpers::sort_query($sort);
        $args = array_merge($args, $sort);
    }

}

function stm_lms_api_courses_category(&$args)
{
    if (!empty($_GET['category'])) $category = intval($_GET['category']);
    $include_childs = (!empty($_GET['include_category_childs'])) ? sanitize_text_field($_GET['include_category_childs']) : false;


    if (!empty($category)) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'stm_lms_course_taxonomy',
                'terms' => $category,
                'field' => 'id',
                'include_children' => $include_childs,
                'operator' => 'IN'
            )
        );
    }
}

function stm_lms_api_courses_author(&$args)
{
    $author_id = (!empty($_GET['author_id'])) ? intval($_GET['author_id']) : '';

    if (!empty($author_id)) {
        $args['author'] = $author_id;
    }
}

function stm_lms_api_courses_wishlist(&$args, $token)
{

    if (!empty($token)) {

        $user_id = stm_lms_api_token($token);

        $wishlist = stm_lms_api_get_wishlist($user_id);

        $args['post__in'] = $wishlist;

        if (empty($wishlist)) {
            $args['post__in'] = array(0);
        }

    }

}