<?php

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/local/iomad/lib/company.php');
require_once($CFG->dirroot . '/local/iomad/lib/user.php');

/* this method is called from pluginfile.php when using a url like
 * pluginfile.php/1/theme_iomad/logo/2/logo.gif
 * theme_iomad files are uploaded and stored using the company_edit_form.
 */
function theme_iomad_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload) {
    global $USER, $CFG;

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/theme_iomad/$filearea/$relativepath";

    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        send_file_not_found();
    }

    send_stored_file($file, 0, 0, $forcedownload);
}

function company_css() {
    return '<link rel="Stylesheet" type="text/css" href="' . new moodle_url("/theme/iomad/company.php") . '" />';
}

/* iomad_process_css  - Processes iomad specific tags in CSS files
 *
 * [[logo]] gets replaced with the full url to the company logo
 * [[company:$property]] gets replaced with the property of the $USER->company object
 *     available properties are: id, shortname, name, logo_filename + the fields in company->cssfields, currently  bgcolor_header and bgcolor_content
 *
 */
function iomad_process_company_css($css, $theme) {
    global $USER;

    company_user::load_company();

    if (isset($USER->company)) {
        // prepare logo fullpath
        $context = get_context_instance(CONTEXT_SYSTEM);
        $logo = file_rewrite_pluginfile_urls('@@PLUGINFILE@@/[[company:logo_filename]]', 'pluginfile.php', $context->id, 'theme_iomad', 'logo', $USER->company->id);
        $css = preg_replace("/\[\[logo\]\]/", $logo, $css);

        // replace company properties
        foreach($USER->company as $key => $value) {
            if (isset($value)) {
                $css = preg_replace("/\[\[company:$key\]\]/", $value, $css);
            }
        }

        return $css;
    } else {
        return "";
    }

}

// prepend the additionalhtmlhead with the company css sheet
if (!empty($CFG->additionalhtmlhead)) {
    $CFG->additionalhtmlhead = company_css() . "\n".$CFG->additionalhtmlhead;
}
