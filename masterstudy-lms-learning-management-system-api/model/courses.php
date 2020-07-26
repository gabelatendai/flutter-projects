<?php
function stm_lms_courses_model()
{
    $schema = array(
        '$schema' => 'http://json-schema.org/draft-04/schema#',
        'title' => 'courses',
        'properties' => array(
            'courses|{x}|categories_object|{x}|id' => 'integer',
            'courses|{x}|categories_object|{x}|count' => 'integer',
            'courses|{x}|rating|average' => 'integer',
            'courses|{x}|rating|total' => 'integer',
            'courses|{x}|rating|percent' => 'integer',
            'courses|{x}|not_saleable' => 'boolean',
            'courses|{x}|is_favorite' => 'boolean',
            'courses|{x}|price|free' => 'boolean',
            'page' => 'integer',
            'total_pages' => 'integer',
			'courses|{x}|id' => 'integer',
        )
    );

    return $schema;
}