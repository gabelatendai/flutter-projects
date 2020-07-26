<?php

add_action('rest_api_init', function ($server) {

    register_rest_route(STM_LMS_API, '/favorite', array(
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
            'methods' => 'PUT',
            'callback' => function ($request) {
                $id = $request->get_param('id');

                $token = $request->get_header('token');
                $user_id = stm_lms_api_token($token);
                if (is_wp_error($user_id)) return $user_id;

                /*Add to wishlist*/
                $wishlist = stm_lms_api_get_wishlist($user_id);

                $wishlist[] = $id;

                STM_LMS_User::update_wishlist($user_id, $wishlist);

                return array(
                    'label' => esc_html__('Added to wishlist', 'stm_lms_api_domain'),
                );
            },
        ),
        array(
            'methods' => 'DELETE',
            'callback' => function ($request) {
                $id = $request->get_param('id');

                $token = $request->get_header('token');
                $user_id = stm_lms_api_token($token);
                if (is_wp_error($user_id)) return $user_id;

                /*Add to wishlist*/
                $wishlist = stm_lms_api_get_wishlist($user_id);

                $index = array_search($id, $wishlist);
                $data['index'] = $index;
                if (isset($index) && isset($wishlist[$index])) unset($wishlist[$index]);
                $data['label'] = esc_html__('Removed from wishlist', 'stm_lms_api_domain');

                STM_LMS_User::update_wishlist($user_id, $wishlist);

                return $data;
            },
        )
    ));
});

function stm_lms_api_get_wishlist($user_id)
{
    $wishlist = get_user_meta($user_id, 'stm_lms_wishlist', true);

    if (empty($wishlist)) $wishlist = array();

    return $wishlist;
}