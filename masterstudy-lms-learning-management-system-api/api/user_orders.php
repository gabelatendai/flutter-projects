<?php
add_action('rest_api_init', function ($server) {
    register_rest_route(STM_LMS_API, '/user_orders', array(
        array(
            'methods' => 'POST',
            'callback' => function ($request) {
                $page = $request->get_param('page');

                $token = $request->get_header('token');
                $user_id = stm_lms_api_token($token);
                if (is_wp_error($user_id)) return $user_id;

                if (empty($page)) $page = 1;

                $r = STM_LMS_Order::_user_orders($user_id, $page - 1);

                if (!empty($r['posts'])) {
                    foreach ($r['posts'] as &$post) {

                        $cart_items = array();

                        if (empty($post['cart_items'])) continue;

                        foreach ($post['cart_items'] as $cart_item_id => $cart_item) {
                            $cart_item['image_url'] = (!empty($cart_item['thumbnail_id'])) ? stm_lms_api_image($cart_item['thumbnail_id'], 400, 400) : '';
                            $cart_items[] = array_merge(compact('cart_item_id'), $cart_item);
                        }

                        if (!empty($cart_items)) {
                            $post['cart_items'] = $cart_items;
                        }
                    }
                }

                return $r;

            }
        )
    ));
});