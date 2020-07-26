<?php
function stm_lms_user_courses_model()
{
    $schema = array(
        '$schema' => 'http://json-schema.org/draft-04/schema#',
        'title' => 'user_courses',
        'properties' => array(
            'posts|{x}|id' => 'integer',
            'posts|{x}|categories_object|{x}|id' => 'integer',
            'posts|{x}|categories_object|{x}|count' => 'integer',
        )
    );

    return $schema;

}