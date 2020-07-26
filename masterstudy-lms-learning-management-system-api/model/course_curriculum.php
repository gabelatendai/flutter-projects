<?php
function stm_lms_course_curriculum_model()
{
    $schema = array(
        '$schema' => 'http://json-schema.org/draft-04/schema#',
        'title' => 'courses',
        'properties' => array(
            'current_lesson_id' => 'string',
            'progress_percent' => 'string',
            'lesson_type' => 'string',
        )
    );

    return $schema;
}