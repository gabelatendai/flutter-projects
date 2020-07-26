<?php

add_action('rest_api_init', function ($server) {

    register_rest_route(STM_LMS_API, '/account/edit_profile', array(
        array(
            'methods' => 'POST',
            'callback' => function ($request) {

                $token = $request->get_header('token');
                $user_token = stm_lms_api_token($token);
                if (is_wp_error($user_token)) return $user_token;
                $user_id = (empty($user_id)) ? $user_token : $user_id;

                $r = array(
                    'modified' => array(),
                    'values' => array()
                );

                /*Text Fields*/
                $fields = STM_LMS_User::extra_fields();
                $fields = array_merge($fields, STM_LMS_User::additional_fields());

                $input_data = array();
                foreach ($fields as $field_key => $field) {
                    $user_data = $request->get_param($field_key);
                    if (empty($user_data)) continue;
                    $r['values'][$field_key] = $input_data[$field_key] = sanitize_text_field($user_data);
                    $r['modified'][$field_key] = update_user_meta($user_id, $field_key, sanitize_text_field($user_data));
                }

                /*PASSWORD CHANGE*/
                if (!empty($fields['password'])) {
                    wp_set_password($fields['password'], $user_id);
                    $r['modified']['password'] = true;
                }

                $files = $request->get_file_params();
                $avatar = (!empty($files['file'])) ? $files['file'] : '';

                if (!empty($avatar)) {
                    $file = STM_LMS_User::stm_lms_change_avatar(array('id' => $user_id), $files, true);
                    if (empty($file['error'])) {
                        $r['modified']['file'] = true;
                        $r['values']['file'] = $file['file'];
                    } else {
                        return new WP_Error('demo_mode', $file['message'], array('status' => 400));
                    }
                }

                return $r;
            },
        )
    ));
});