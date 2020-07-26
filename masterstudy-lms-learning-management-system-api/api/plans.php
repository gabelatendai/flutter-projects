<?php
add_action('rest_api_init', function ($server) {
    register_rest_route(STM_LMS_API, '/plans', array(
        'methods' => 'GET',
        'callback' => function () {

            $plans = array();

            global $wpdb, $pmpro_msg, $pmpro_msgt, $current_user;
            $pmpro_levels = pmpro_getAllLevels(false, true);

            foreach ($pmpro_levels as $level_number => &$level) {
                $period = pmpro_translate_billing_period($level->cycle_period);
                if (empty($period)) $period = '';
                $level->price = (pmpro_isLevelFree($level)) ? esc_html__('Free', 'masterstudy') : pmpro_formatPrice($level->initial_payment);
                $level->period = $period;
                $current_level = isset($current_user->membership_level->ID) ? $current_user->membership_level->ID == $level->id : false;
                if (empty($current_user->membership_level->ID) || !$current_level) {
                    $text = esc_html__('Get now', 'masterstudy');
                    $url = pmpro_url('checkout', '?level=' . $level->id, 'https');
                } elseif ($current_level) {
                    if (pmpro_isLevelExpiringSoon($current_user->membership_level) && $current_user->membership_level->allow_signups) {
                        $text = esc_html__('Renew', 'masterstudy');
                        $url = pmpro_url('checkout', '?level=' . $level->id, 'https');
                    } else {
                        $text = esc_html__('Your Level', 'masterstudy');
                        $url = pmpro_url('account');
                    }
                }

                $url = add_query_arg('stm_lms_app_buy', $url, get_home_url());

                $level_description = stm_lms_apo_ul_to_array($level->description);
                $level->features = (!empty($level_description)) ? $level_description : '';
                $level->description = (empty($level_description)) ? stm_lms_api_webview_wrapper($level->description) : '';

                $level->button = compact('text', 'url');
            }

            $pmpro_levels = apply_filters("pmpro_levels_array", $pmpro_levels);

            //$pmpro_levels['schema'] = stm_lms_plans_model();

            $pmpro_levels = stm_lms_api_prepare_rest($pmpro_levels);

            return array_values($pmpro_levels);

        },
    ));
});

function stm_lms_apo_ul_to_array($ul)
{
   return $ul;
}