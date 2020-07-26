<?php

add_action('rest_api_init', function ($server) {

    register_rest_route(STM_LMS_API, '/add_to_cart', array(
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
                $cart = STM_LMS_Cart::_add_to_cart($id, $user_id);

                return $cart;
            }
        ),
    ));
});