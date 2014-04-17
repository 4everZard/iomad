<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

require_once('../../config.php');
require_once($CFG->dirroot.'/blocks/iomad_company_admin/lib.php');
require_once('lib.php');

// Check permissions.
require_login($SITE);
$context = get_context_instance(CONTEXT_SYSTEM);
require_capability('local/report_companies:view', $context);


// Url stuff.
$url = new moodle_url('/local/report_companies/index.php');

// Page stuff:.
$strcompletion = get_string('pluginname', 'local_report_companies');
$PAGE->set_url($url);
$PAGE->set_pagelayout('report');
$PAGE->set_title($strcompletion);
$PAGE->requires->css("/local/report_companies/styles.css");

// Set the url.
company_admin_fix_breadcrumb($PAGE, get_string('pluginname', 'local_report_companies'), $url);

// Navigation and header.
echo $OUTPUT->header();
echo $OUTPUT->heading( get_string('pluginname', 'local_report_companies') );

// Ajax odds and sods.
$PAGE->requires->js_init_call( 'M.local_report_companies.init');

// Get the company list.
$companies = companyrep::companylist( $USER );
companyrep::addmanagers( $companies );
companyrep::addusers( $companies );
companyrep::addcourses( $companies );

// Iterate over companies.
foreach ($companies as $company) {
    echo "<div class=\"iomad_company\" />\n";
    echo "<h2>{$company->name}</h2>";

    // Managers.
    echo "<div class=\"iomad_managers\" />\n";
    if (empty($company->managers)) {
        echo "<strong>".get_string('nomanagers', 'local_report_companies')."</strong>";
    } else {
        echo "<h4>".get_string('coursemanagers', 'local_report_companies')."</h4>\n";
        companyrep::listusers( $company->managers );
    }
    echo "</div>\n";

    // Users.
    echo "<div class=\"iomad_users\" />\n";
    if (empty($company->users)) {
        echo "<strong>".get_string('nousers', 'local_report_companies')."</strong>";
    } else {
        echo "<h4>".get_string('courseusers', 'local_report_companies')."</h4>\n";
        echo get_string('totalusercount', 'local_report_companies') . count($company->users) . ' <a href="' .
        new moodle_url('/local/report_users/index.php').'">'.
        get_string('completionreportlink', 'local_report_companies') . '</a>';
    }
    echo "</div>\n";

    // Courses.
    echo "<div class=\"iomad_courses\" />\n";
    if (empty($company->courses)) {
        echo "<strong>".get_string('nocourses', 'local_report_companies')."</strong>";
    } else {
        echo "<h4>".get_string('courses', 'local_report_companies')."</h4>\n";
        echo get_string('totalcoursecount', 'local_report_companies'). count($company->courses) . ' <a href="' .
        new moodle_url('/local/report_completion/index.php').'">'.
        get_string('completionreportlink', 'local_report_companies') . '</a>';
    }
    echo "</div>\n";

    // Theme.
    echo "<div class=\"iomad_Theme\" />\n";
    if (empty($company->theme)) {
        echo "<strong>".get_string('notheme', 'local_report_companies')."</strong>";
    } else {
        echo "<h4>".get_string('themeinfo', 'local_report_companies')."</h4>\n";
        echo get_string('themedetails', 'local_report_companies'). $company->theme;
        $screenshotpath = new moodle_url('/theme/image.php', array('theme' => $company->theme,
                                                                   'image' => 'screenshot',
                                                                   'component' => 'theme'));
        echo '<p>'.html_writer::empty_tag('img', array('src' => $screenshotpath, 'alt' => $company->theme)) .'</p>';
    }
    echo "</div>\n";

    echo "</div>\n";
}

echo $OUTPUT->footer();
