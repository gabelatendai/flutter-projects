<?php

new STM_LMS_API_OPTIONS_PAGE;

class STM_LMS_API_OPTIONS_PAGE
{
    function __construct()
    {
//        add_action('admin_menu', array($this, 'menu_page'));
        add_action('admin_enqueue_scripts', array($this, 'menu_scripts'));

        add_action('wp_ajax_stm_lms_api_get_blocks', array($this, 'get_blocks'));
        add_action('wp_ajax_nopriv_stm_lms_api_get_blocks', array($this, 'get_blocks'));

        add_action('wp_ajax_stm_lms_api_save_blocks', array($this, 'save_blocks'));

        add_filter('wpcfto_options_page_setup', array($this, 'options'));

        add_action('wpcfto_settings_screen_ms_lms_api_options_before', array($this, 'page_render'));

    }

    function options($setups)
    {


        $json = json_decode(file_get_contents(STM_LMS_API_PATH . '/translations.json'), true);
        $translations = array();

        foreach($json as $translation_key => $translation_value) {
            $translations[$translation_key] = array(
                'type' => 'text',
                'label' => $translation_value,
                'value' => $translation_value,
                'columns' => 50
            );
        }

        $setups[] = array(
            'option_name' => 'ms_lms_api_options',
            'page' => array(
                'page_title' => 'LMS API Settings',
                'menu_title' => 'API Settings',
                'menu_slug' => 'ms_lms_api_options',
                'icon' => 'dashicons-welcome-view-site',
                'position' => 8,
            ),
            'fields' => array(
                'section_1' => array(
                    'name' => esc_html__('General', 'masterstudy-lms-learning-management-system'),
                    'fields' => array(
                        'logo' => array(
                            'type' => 'image',
                            'label' => esc_html__('App logo', 'masterstudy-lms-learning-management-system'),
                        ),
                        'main_color' => array(
                            'type' => 'color',
                            'label' => esc_html__('Main color', 'masterstudy-lms-learning-management-system'),
                        ),
                        'secondary_color' => array(
                            'type' => 'color',
                            'label' => esc_html__('Secondary color', 'masterstudy-lms-learning-management-system'),
                        ),
                        'app_view' => array(
                            'type' => 'checkbox',
                            'label' => esc_html__('Show only courses on main app screen', 'masterstudy-lms-learning-management-system'),
                        ),
                    )
                ),
                'section_2' => array(
                    'name' => esc_html__('Translations', 'masterstudy-lms-learning-management-system'),
                    'fields' => $translations
                )
            )
        );

        return $setups;
    }

    public static function block_option_name()
    {
        return 'stm_lms_api_app_blocks';
    }

    function menu_scripts($hook)
    {
        if ($hook === 'toplevel_page_ms_lms_api_options') {

            wp_enqueue_style('stm_lms_api_options_page', STM_LMS_API_URL . '/admin/settings_app/dist/css/app.css');

            wp_enqueue_script('stm_lms_api_options_page', STM_LMS_API_URL . '/admin/settings_app/dist/js/app.js', null, time(), true);
            wp_enqueue_script('stm_lms_api_options_page_chunks', STM_LMS_API_URL . '/admin/settings_app/dist/js/chunk-vendors.js', array('stm_lms_api_options_page'), time(), true);

            wp_localize_script('stm_lms_api_options_page', 'stm_lms_api_vars', array(
                'save_blocks' => admin_url('admin-ajax.php') . '?action=stm_lms_api_save_blocks',
                'get_blocks' => admin_url('admin-ajax.php') . '?action=stm_lms_api_get_blocks',
            ));

            stm_lms_register_style('admin/lms_api');

        }
    }

    function page_render()
    {
        ?>
        <div id=app></div>
        <?php
    }

    static function _get_blocks()
    {
        return get_option(self::block_option_name(), stm_lms_api_app_default_blocks());
    }

    function get_blocks()
    {
        wp_send_json(self::_get_blocks());
    }

    function save_blocks()
    {

        if (!current_user_can('manage_options')) die;

        $data = json_decode(file_get_contents('php://input'));

        wp_send_json(update_option(self::block_option_name(), $data));

    }

}