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

require_once(dirname(__FILE__) . '/../../config.php'); // Creates $PAGE.
require_once($CFG->dirroot.'/blocks/iomad_commerce/lib.php');

/**
 *
 */

class block_iomad_commerce extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_iomad_commerce');
    }

    public function hide_header() {
        return true;
    }

    public function get_content() {
        global $CFG;

        if ($this->content !== null) {
            return $this->content;
        }

        if (!isset($CFG->iomad_commerce_enabled) || $CFG->iomad_commerce_enabled) {
            $this->content = new stdClass;
            $this->content->text = '<a href="' . new moodle_url('/blocks/iomad_commerce/shop.php') .
                                   '">' . get_string('shop_title', 'block_iomad_commerce') . '</a>';

            $this->content->text .= get_basket_info();
            $this->content->footer = '';
        }

        return $this->content;
    }

}
