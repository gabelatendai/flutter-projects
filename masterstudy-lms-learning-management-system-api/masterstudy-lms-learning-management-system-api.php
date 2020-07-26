<?php
/*
Plugin Name: MasterStudy LMS Learning Management System API
Plugin URI: http://masterstudy.stylemixthemes.com/
Description: API plugin for MasterStudy Learning Management System
Author: StylemixThemes
Author URI: https://stylemixthemes.com/
Text Domain: masterstudy-lms-learning-management-system-api
Version: 1.0
*/

if (!defined('ABSPATH')) exit; //Exit if accessed directly

define('STM_LMS_API', 'ms_lms/v1');
define('STM_LMS_API_FILE', __FILE__);
define('STM_LMS_API_PATH', dirname(STM_LMS_API_FILE));
define('STM_LMS_API_URL', plugin_dir_url(STM_LMS_API_FILE));

require_once STM_LMS_API_PATH . "/helpers/BFI_Thumb.php";
require_once STM_LMS_API_PATH . "/helpers/helpers.php";
require_once STM_LMS_API_PATH . "/helpers/prepare_rest.php";

/*API Endpoints*/
require_once STM_LMS_API_PATH . "/api/login.php";
require_once STM_LMS_API_PATH . "/api/registration.php";


require_once STM_LMS_API_PATH . "/api/course_lesson.php";
require_once STM_LMS_API_PATH . "/api/course_quiz.php";
require_once STM_LMS_API_PATH . "/api/course_lesson_complete.php";
require_once STM_LMS_API_PATH . "/api/course_questions.php";
require_once STM_LMS_API_PATH . "/api/course_reviews.php";

require_once STM_LMS_API_PATH . "/api/categories.php";

require_once STM_LMS_API_PATH . "/api/add_to_cart.php";

require_once STM_LMS_API_PATH . "/api/assignment.php";
require_once STM_LMS_API_PATH . "/api/assignment_start.php";
require_once STM_LMS_API_PATH . "/api/assignment_add.php";
require_once STM_LMS_API_PATH . "/api/assignment_add_file.php";

require_once STM_LMS_API_PATH . "/api/instructors.php";
require_once STM_LMS_API_PATH . "/api/account.php";
require_once STM_LMS_API_PATH . "/api/favorites.php";
require_once STM_LMS_API_PATH . "/api/edit_profile.php";

require_once STM_LMS_API_PATH . "/api/user_orders.php";
require_once STM_LMS_API_PATH . "/api/user_plans.php";
require_once STM_LMS_API_PATH . "/api/popular_searches.php";
require_once STM_LMS_API_PATH . "/api/use_plan.php";
require_once STM_LMS_API_PATH . "/api/translations.php";

require_once STM_LMS_API_PATH . "/helpers/quiz.web.view.php";

require_once STM_LMS_API_PATH . "/api/app_settings.php";


require_once STM_LMS_API_PATH . "/helpers/token.login.php";
require_once STM_LMS_API_PATH . "/helpers/session.php";

/*User Courses*/
require_once STM_LMS_API_PATH . "/model/user_courses.php";
require_once STM_LMS_API_PATH . "/api/user_courses.php";

/*Courses*/
require_once STM_LMS_API_PATH . "/api/courses.php";
require_once STM_LMS_API_PATH . "/model/courses.php";

/*Course*/
require_once STM_LMS_API_PATH . "/api/course.php";
require_once STM_LMS_API_PATH . "/model/course.php";

/*Plans*/
require_once STM_LMS_API_PATH . "/api/plans.php";
require_once STM_LMS_API_PATH . "/model/plans.php";

/*Course Curriculum*/
require_once STM_LMS_API_PATH . "/api/course_сurriculum.php";
require_once STM_LMS_API_PATH . "/model/course_curriculum.php";

/*Course Results*/
require_once STM_LMS_API_PATH . "/api/course_results.php";
require_once STM_LMS_API_PATH . "/model/course_results.php";

if (is_admin()) {
    require_once STM_LMS_API_PATH . "/admin/settings/blocks.php";
}