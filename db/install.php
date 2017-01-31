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

/**
 * Provides code to be executed during the module installation
 *
 * This file replaces the legacy STATEMENTS section in db/install.xml,
 * lib.php/modulename_install() post installation hook and partially defaults.php.
 *
 * @package    mod_branchedquiz
 * @copyright  2016 Your Name <your@email.address>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Post installation procedure
 *
 * @see upgrade_plugins_modules()
 */
function xmldb_branchedquiz_install() {
	global $DB;
	$pfx = $DB->get_prefix();

	$DB->execute('INSERT IGNORE INTO mdl_quiz_node VALUES (\'1\',\'1\')');
	$DB->execute('INSERT IGNORE INTO mdl_quiz_node VALUES (\'2\',\'2\')');
	$DB->execute('INSERT IGNORE INTO mdl_quiz_node VALUES (\'3\',\'3\')');
	$DB->execute('INSERT IGNORE INTO mdl_quiz_node VALUES (\'4\',\'4\')');
	$DB->execute('INSERT IGNORE INTO mdl_quiz_edge VALUES (\'1\',\'2\',\'0\',\'question 1 was false\',\'1\')');
    $DB->execute('INSERT IGNORE INTO mdl_quiz_edge VALUES (\'2\',\'3\',\'1\',\'question 1 was true\',\'1\')');
    $DB->execute('INSERT IGNORE INTO mdl_quiz_edge VALUES (\'3\',\'4\',\'-1\',\'q1 = f, q2 = x\',\'2\')');
    $DB->execute('INSERT IGNORE INTO mdl_quiz_edge VALUES (\'4\',\'4\',\'-1\',\'q1 = t, q3 = x\',\'3\')');

	$DB->execute('CREATE OR REPLACE VIEW '.$pfx.'branchedquiz AS SELECT * FROM '.$pfx.'quiz');
}

/**
 * Post installation recovery procedure
 *
 * @see upgrade_plugins_modules()
 */
function xmldb_branchedquiz_install_recovery() {
}
