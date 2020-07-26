<?php

add_action('stm_lms_before_item_template_start', function ($post_id, $item_id) {

    if (get_post_type($item_id) !== 'stm-quizzes') return;

    $token = stm_lms_api_check_headers_token();

    if (empty($token)) return;

    $user_id = stm_lms_api_token($token);

    wp_set_current_user($user_id);

    $custom_css = get_post_meta($item_id, '_wpb_shortcodes_custom_css', true);

    stm_lms_register_style('lesson', array(), $custom_css);
    do_action('stm_lms_template_main');

    $has_access = STM_LMS_User::has_course_access($post_id, $item_id);
    $has_preview = STM_LMS_Lesson::lesson_has_preview($item_id);
    $is_previewed = STM_LMS_Lesson::is_previewed($post_id, $item_id); ?>


    <?php if ($has_access or $has_preview) {

        if (!$is_previewed) do_action('stm_lms_lesson_started', $post_id, $item_id, '');

        stm_lms_update_user_current_lesson($post_id, $item_id);

        $item_content = apply_filters('stm_lms_show_item_content', true, $post_id, $item_id);

        if (!$item_content) return; ?>

        <?php
        STM_LMS_Templates::show_lms_template(
            'lesson/header',
            compact('post_id', 'item_id', 'is_previewed', 'content_type', 'lesson_type')
        );

        stm_lms_register_script('quiz_app');
        wp_add_inline_script('stm-lms-quiz_app',
            'var stm_lms_lesson_id = ' . $item_id);

        ?>

        <div class="stm-lms-wrapper stm-quizzes">

            <?php echo apply_filters('stm_lms_lesson_content', STM_LMS_Templates::load_lms_template(
                'course/parts/quiz',
                compact('post_id', 'item_id', 'is_previewed')
            ), $post_id, $item_id);

            stm_lms_register_style('quiz_app');
            ?>

        </div>


        <?php STM_LMS_Templates::show_lms_template(
            'lesson/footer',
            compact('post_id', 'item_id', 'is_previewed')
        ); ?>

        <?php die;

    }


}, 10, 2);

//add_filter('stm_lms_before_item_template_start_filter', function ($r, $post_id, $item_id) {
//
//    if (get_post_type($item_id) !== 'stm-quizzes') return;
//
//    if (empty($_GET['token'])) return;
//
//    $user_id = stm_lms_api_token($_GET['token']);
//
//    return $r;
//
//}, 10, 3);