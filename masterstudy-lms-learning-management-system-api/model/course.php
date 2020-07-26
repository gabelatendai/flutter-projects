<?php
function stm_lms_course_model()
{
    $schema = array(
        '$schema' => 'http://json-schema.org/draft-04/schema#',
        'title' => 'courses',
        'properties' => array(
            'id' => 'integer',
			'not_saleable' => 'boolean',
			'is_favorite' => 'boolean',
			'trial' => 'boolean',
			'has_access' => 'boolean',
			'rating|average' => 'integer',
			'rating|total' => 'integer',
			'rating|percent' => 'integer',
			'price|free' => 'boolean',
			'categories_object|{x}|id' => 'integer',
			'categories_object|{x}|count' => 'integer',
			'author|rating|average' => 'float',
        )
    );

    return $schema;
}