<?php
function stm_lms_course_results_model()
{
    $schema = array(
        '$schema' => 'http://json-schema.org/draft-04/schema#',
        'title' => 'courses',
        'properties' => array(
			'course|user_course_id' => 'integer',
			'course|user_id' => 'integer',
			'course|course_id' => 'integer',
			'course|current_lesson_id' => 'integer',
			'course|progress_percent' => 'integer',
			'course|subscription_id' => 'integer',
			'course|enterprise_id' => 'integer',
			'course|bundle_id' => 'integer',
        )
    );

    return $schema;
}