<?php

new STM_LMS_API_Sessions();

class STM_LMS_API_Sessions
{

    public function __construct()
    {

        add_action('template_redirect', array($this, 'store_session'));

        add_action('wp_enqueue_scripts', array($this, 'session_styles'));

        add_filter('stm_lms_purchase_done', array($this, 'purchase_done'));

        add_filter('stm_lms_add_to_cart_r', array($this, 'add_to_cart'), 10, 2);

        add_filter('pmpro_confirmation_message', array($this, 'confirmation'), 10, 1);

    }

    static function isFromAppToken() {
        $token = stm_lms_api_check_headers_token();

        return (!empty($token));
    }

    static function isFromApp()
    {
        return (!empty($_SESSION['stm_lms_app_user_is_logged_in']));
    }

    function session_styles()
    {
        $is_app = self::isFromApp();

        if ($is_app) {
            stm_lms_register_style('app/web_view_styles');
        }

    }

    static function store_session()
    {
        $token = stm_lms_api_check_headers_token();
        if (empty($token)) return;

        $user_id = stm_lms_api_check_token($token);
        $user = get_user_by('id', $user_id);

        wp_clear_auth_cookie();
        wp_set_current_user(0);

        if ($user) {
            if (!is_user_logged_in()) {

                if (!session_id()) {
                    session_start();
                }

                $_SESSION['stm_lms_app_user_is_logged_in'] = 'logged_in_from_app';

                wp_set_current_user($user_id, $user->user_login);
                wp_set_auth_cookie($user_id, 1, is_ssl());
                do_action('wp_login', $user->user_login, $user);
            }
        }

        /*Redirect to checkout*/
        if (!empty($_GET['stm_lms_app_buy'])) {
            wp_redirect(esc_url($_GET['stm_lms_app_buy']));
        }
    }

    function purchase_done($r)
    {
        if (self::isFromApp()) {
            if ($r['status'] === 'success') $r['is_app'] = true;
        }

        return $r;
    }

    function add_to_cart($r, $item_id)
    {

        if (self::isFromAppToken()) {
            $r['cart_url'] = add_query_arg('stm_lms_app_buy', $r['cart_url'], get_home_url());
            $r['lesson_id'] = null;

            /*If we have zero price and user - just add it without next steps*/
            $user_id = get_current_user_id();
            if(!STM_LMS_Course::get_course_price($item_id) && $user_id ) {
                STM_LMS_Course::add_student($item_id);
                STM_LMS_Course::add_user_course($item_id, $user_id, 0, 0);
                $r['lesson_id'] = (int) STM_LMS_Course::get_first_lesson($item_id);
            }

        }

        return $r;
    }

    function confirmation($message) {
        $message .= "<script>
            stm_lms_print_message({'event_type' : 'plan_order_created'});
        </script>";
        return $message;
    }

}

function stm_lms_api_check_headers_token() {
    $headers = getallheaders();

    $token = (!empty($headers['token'])) ? sanitize_text_field($headers['token']) : '';
    if(!empty($headers['Token'])) $token = sanitize_text_field($headers['Token']);

    if(!empty($_GET['test'])) $token = sanitize_text_field($_GET['test']);

    return $token;
}