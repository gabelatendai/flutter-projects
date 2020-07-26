<?php
add_action('rest_api_init', function ($server) {
    register_rest_route(STM_LMS_API, '/instructors', array(
        'methods' => 'GET',
        'callback' => function () {

            $data = array(
                'page' => 1,
                'data' => array()
            );

            $user_args = array(
                'role' => STM_LMS_Instructor::role(),
            );

            stm_lms_api_instructors_per_page($user_args, $data);
            stm_lms_api_instructors_sort($user_args);

            $user_query = new WP_User_Query($user_args);
            $results = $user_query->get_results();
            $total = $user_query->get_total();
            $data['total_pages'] = ceil($total / $user_args['number']);

            if (!empty($results)) {

                foreach ($user_query->get_results() as $user) {
                    $user_data = STM_LMS_User::get_current_user($user->ID, false, true);
                    $user_data['rating'] = stm_lms_api_instructor_rating($user_data);
                    $user_data['profile_url'] = STM_LMS_User::user_public_page_url($user->ID);

                    $data['data'][] = $user_data;
                }

            }

            return $data;
        },
    ));
});


function stm_lms_api_instructor_rating($instructor)
{
    return STM_LMS_Instructor::my_rating_v2($instructor);

}

function stm_lms_api_instructors_sort(&$user_args)
{

    if (!empty($_GET['sort'])) $sort = sanitize_text_field($_GET['sort']);

    if (!empty($sort) and $sort == 'rating') {
        $sort_args = array(
            'meta_key' => 'average_rating',
            'orderby' => 'meta_value_num',
            'order' => 'DESC'
        );

        $user_args = array_merge($user_args, $sort_args);
    }
}

function stm_lms_api_instructors_per_page(&$user_args, &$data)
{
    $per_page = (!empty($_GET['per_page'])) ? intval($_GET['per_page']) : stm_lms_api_per_page();

    $user_args['number'] = $per_page;

    if (!empty($_GET['page'])) $page = intval($_GET['page']);

    if (!empty($page)) {
        $user_args['offset'] = $page * $per_page - $per_page;
        $data['page'] = $page;
    }
}