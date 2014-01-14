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

// This script is run after the dashboard has been installed.

function xmldb_local_iomad_dashboard_install() {
    global $SITE;

    // Add some default blocks to the dashboard
    // yes, I know this isn't really what this is for!!
    $systemcontext = get_context_instance(CONTEXT_SYSTEM);
    $page = new moodle_page();
    $page->set_context( $systemcontext );
    $page->set_pagetype( 'local-iomad-dashboard-index' );
    $page->set_pagelayout( 'mydashboard' );
    $page->blocks->add_region('content');
    $defaultblocks = array(
        'side_pre' => array('course_list'),
        'content' => array('iomad_company_selector',
                           'iomad_company_admin',
                           'iomad_approve_access',
                           'iomad_reports'),
        'side_post' => array('news_items')
        );
    $page->blocks->add_blocks($defaultblocks);

    return true;
}
