<?php
add_action('rest_api_init', function ($server) {
    register_rest_route(STM_LMS_API, '/translations', array(
        'methods' => 'GET',
        'callback' => function ($request) {

            $app_data = array();
            $app_options = get_option('ms_lms_api_options', array());
            $json = json_decode(file_get_contents(STM_LMS_API_PATH . '/translations.json'), true);

            foreach($json as $translation_key => $translation_value) {
                if(!empty($app_options[$translation_key])) {
                    $translation_value = $app_options[$translation_key];
                }
                $app_data[$translation_key] = $translation_value;
            }

            return $app_data;
        },
    ));
});
