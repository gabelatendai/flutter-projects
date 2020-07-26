<?php
add_action('rest_api_init', function ($server) {
    register_rest_route(STM_LMS_API, '/app_settings', array(
        'methods' => 'GET',
        'callback' => function ($request) {

            $home_layout = get_option('stm_lms_api_app_blocks');

            if (!empty($home_layout)) {
                $data = array();
                foreach ($home_layout as $item) {
                    if (!is_object($item) && !$item->enabled) continue;
                    $data[] = $item;
                }
            } else {
                $data = array_values(stm_lms_api_app_default_blocks());
            }

            $app_data = array(
                'addons' => array_filter(get_option('stm_lms_addons', array()), function ($item) {
                    return $item === 'on';
                }),
                'home_layout' => $data,
                'options' => array(
                    'logo' => null,
                    'main_color' => null,
                    'main_color_hex' => null,
                    'secondary_color' => null,
                    'app_view' => false,
                    'posts_count' => 0,
                ),
            );

            $app_options = get_option('ms_lms_api_options', array());

            foreach ($app_data['options'] as $option_name => &$option) {
                if (empty($app_options[$option_name])) continue;
                if ($option_name === 'main_color') $app_data['options']['main_color_hex'] = $app_options['main_color'];
                if ($option_name === 'secondary_color') $app_data['options']['secondary_color_hex'] = $app_options['secondary_color'];
                if (in_array($option_name, array('main_color', 'secondary_color'))) {
                    stm_lms_api_color_to_object($app_options[$option_name]);
                }
                $option = $app_options[$option_name];
            }

            $app_data['options']['posts_count'] = intval(wp_count_posts('stm-courses')->publish);

            if (!empty($app_options['logo'])) {
                $app_logo = wp_get_attachment_image_src($app_options['logo']);
                if (!empty($app_logo[0])) {
                    $app_data['options']['logo'] = $app_logo[0];
                }
            }

            return $app_data;
        },
    ));
});

function stm_lms_api_app_default_blocks()
{
    return array(
        array(
            'id' => 1,
            'name' => 'Categories',
            'enabled' => true
        ),
        array(
            'id' => 2,
            'name' => 'New Courses',
            'enabled' => true
        ),
        array(
            'id' => 3,
            'name' => 'Trending Courses',
            'enabled' => true
        ),
        array(
            'id' => 4,
            'name' => 'Instructors',
            'enabled' => true
        ),
        array(
            'id' => 5,
            'name' => 'Free Courses',
            'enabled' => true
        ),
    );
}

function stm_lms_api_color_to_object(&$option)
{
    $option = str_replace(array('rgba(', ')'), '', $option);
    $option = explode(',', $option);
    if (!empty($option) && count($option) === 4) {
        $option = (object)[
            'r' => (int)$option[0],
            'g' => (int)$option[1],
            'b' => (int)$option[2],
            'a' => (int)$option[3],
        ];
    } else {
        $option = null;
    }
}