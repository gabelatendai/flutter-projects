<?php

if (!empty($_GET['token'])) {

    add_action('after_setup_theme', function () {

        $user = stm_lms_api_token(sanitize_text_field($_GET['token']));

        if (!is_user_logged_in() || $user !== get_current_user_id()) {
            wp_clear_auth_cookie();
            wp_set_current_user($user);
            wp_set_auth_cookie($user, 1, is_ssl());
        }

    });

    apply_filters('stm_lms_item_url_quiz_ended', function($url){


        return $url;
    }, 10, 1);

}