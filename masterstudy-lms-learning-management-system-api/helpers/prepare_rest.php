<?php

function stm_lms_api_prepare_rest($data)
{

    /*If we have schema obj*/
    $has_schema = (!empty($data['schema'])) ? $data['schema'] : array();
    if (empty($has_schema)) return $data;

    /*If we have properties in schema*/
    $schema = (!empty($has_schema['properties']) ? $has_schema['properties'] : array());
    if (empty($schema)) return $data;

    unset($data['schema']);

    stm_lms_data($data, array(), $schema, 0);

    stm_lms_api_empty_to_null($data);

    return $data;
}

function stm_lms_data(&$data, $keys, $schema, $level)
{
    if(is_array($data)) {
        foreach ($data as $key => &$value) {
            $keys[] = $key;
            stm_lms_api_change_field_type($keys, $value, $schema);
            if (is_array($value)) {
                $level++;
                stm_lms_data($value, $keys, $schema, $level);
                array_pop($keys);
            } else {
                array_pop($keys);
            }
            if($level === 0) $keys = array();
        }
    }
}

function stm_lms_api_change_field_type($keys, &$data, $schema) {

    $key = implode('|', array_map(function($item) {
        return is_int($item) ? "{x}" : $item;
    }, $keys));

    if(!empty($schema[$key]) && !is_array($data)) {
        $data = call_user_func("stm_lms_api_mutate_{$schema[$key]}", $data);
    }

}

function stm_lms_api_mutate_string($data) {
    return strval($data);
}

function stm_lms_api_mutate_integer($data) {
    return intval($data);
}

function stm_lms_api_mutate_float($data) {
    return floatval($data);
}

function stm_lms_api_mutate_double($data) {
    return doubleval($data);
}

function stm_lms_api_mutate_boolean($data) {
    return boolval($data);
}

if (!function_exists('stm_pa')) {
    function stm_pa($data)
    {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
    }
}