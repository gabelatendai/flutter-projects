<?php
add_action('rest_api_init', function ($server) {
    register_rest_route(STM_LMS_API, '/popular_searches', array(
        'methods' => 'GET',
        'callback' => function ($request) {

            $limit = $request->get_param('limit');
            if (empty($limit)) $limit = 15;

            $r = stm_lms_get_popular_user_searches($limit);

            if (!empty($r)) $r = wp_list_pluck($r, 'search');

            return array('searches' => $r);

        },
    ));
});