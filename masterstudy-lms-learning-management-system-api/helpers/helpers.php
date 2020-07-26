<?php

//TODO add settings
function stm_lms_api_per_page()
{
    return 10;
}

function stm_lms_api_buy_label()
{
    return esc_html__('Get now', 'stm_lms_api_domain');
}


function stm_lms_api_image($image_id, $width, $height)
{
    $params = compact('width', 'height');

    $image = stm_lms_api_image_url($image_id);

    return bfi_thumb($image, $params);
}

function stm_lms_api_image_url($image_id, $size = 'full')
{
    $image = wp_get_attachment_image_src($image_id, $size);

    $image = ($image) ? $image[0] : stm_lms_api_placeholder();

    return $image;
}

function stm_lms_api_placeholder()
{
    return STM_LMS_API_URL . 'placeholder.png';
}

function stm_lms_api_error($code, $message, $status = 400)
{
    wp_send_json(new WP_Error($code, $message, array('status' => $status)));
}

add_filter('stm_wpcfto_term_meta_fields', function ($fields) {
    $fields['stm_lms_course_taxonomy']['course_app_image'] = array(
        'label' => esc_html__('Category APP Image', 'masterstudy-lms-learning-management-system-api'),
        'type' => 'image',
    );

    return $fields;
});

function stm_lms_api_empty_to_null(&$datas)
{
    if (is_array($datas)) {
        foreach ($datas as $key => &$data) {
            if (is_array($data)) {
                stm_lms_api_empty_to_null($data);
                continue;
            }

            if ($data === '') {
                $data = null;
            }
        }
    }
}

function stm_lms_api_stringify_all(&$datas)
{
    if (is_array($datas)) {
        foreach ($datas as $key => &$data) {
            if (is_array($data)) {
                stm_lms_api_stringify_all($data);
                continue;
            }

            $data = strval($data);
        }
    }
}

function stm_lms_api_webview_wrapper($content, $styles='', $wrapper='') {
    $url = STM_LMS_API_URL . "web_view_styles/typebase.css";
    $font_url = STM_LMS_API_URL . "web_view_styles/roboto/roboto.css";

    $content = htmlentities($content,ENT_NOQUOTES,'UTF-8',false);
    $content = str_replace(array('&lt;','&gt;'),array('<','>'), $content);
    $content = str_replace(array('&amp;lt;','&amp;gt'),array('&lt;','&gt;'), $content);

    $html = "<!DOCTYPE html>
        <html>
            <head>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <link rel='stylesheet' href='{$font_url}?v=" . time() . "' type='text/css'>
                <link rel='stylesheet' href='{$url}?v=" . time() . "' type='text/css'>
                {$styles}
            </head>
            <body style='margin: 0; padding: 0;'>
                <div class='{$wrapper}'>{$content}</div>
            </body>
        </html>";

    return trim(str_replace(array("\n", "\r", "\t"), '', $html));
}

add_filter('stm_lms_lesson_type', function ($currId) {

	if(empty($currId)) return "";

	$type = get_post_meta($currId, 'type', true);
	$content_type = get_post_type($currId);

	if ($content_type === 'stm-quizzes') {
		$type = 'quiz';
	} else if($content_type === 'stm-assignments') {
		$type = 'assignment';
	}

	return $type;
});