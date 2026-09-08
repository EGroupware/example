<?php
/**
 * EGroupware - Example - schema and version updates
 *
 * One function per version, named <app>_upgrade<version with dots as underscores>.
 * Setup runs them in order until the app reaches the version in setup.inc.php.
 *
 * @link https://www.egroupware.org
 * @package example
 * @subpackage setup
 * @license https://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 */

/**
 * Nothing to change in the database - the new version is what makes setup re-read
 * setup.inc.php, and with it the changed entry point.
 *
 * @return string
 */
function example_upgrade26_1()
{
	return $GLOBALS['setup_info']['example']['currentver'] = '26.1.001';
}

/**
 * Create the table hosts are stored in
 *
 * @return string
 */
function example_upgrade26_1_001()
{
	$GLOBALS['egw_setup']->oProc->CreateTable('egw_example', array(
		'fd' => array(
			'host_id' => array('type' => 'auto','nullable' => False),
			'host_name' => array('type' => 'varchar','precision' => '64','nullable' => False),
			'host_description' => array('type' => 'varchar','precision' => '16384'),
			'host_creator' => array('type' => 'int','meta' => 'account','precision' => '4','nullable' => False),
			'host_created' => array('type' => 'timestamp','nullable' => False),
			'host_modifier' => array('type' => 'int','meta' => 'account','precision' => '4'),
			'host_modified' => array('type' => 'timestamp','default' => 'current_timestamp')
		),
		'pk' => array('host_id'),
		'fk' => array(),
		'ix' => array(),
		'uc' => array()
	));

	return $GLOBALS['setup_info']['example']['currentver'] = '26.1.002';
}
