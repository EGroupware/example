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

/**
 * Create the table custom-field values are stored in
 *
 * The meta types cfname and cfvalue are what mark the two columns as holding a
 * custom field's name and its value; Api\Storage finds them by those.
 *
 * @return string
 */
function example_upgrade26_1_002()
{
	$GLOBALS['egw_setup']->oProc->CreateTable('egw_example_extra', array(
		'fd' => array(
			'host_id' => array('type' => 'int','precision' => '4','nullable' => False),
			'host_extra_name' => array('type' => 'varchar','meta' => 'cfname','precision' => '64','nullable' => False),
			'host_extra_value' => array('type' => 'text','meta' => 'cfvalue','nullable' => False)
		),
		'pk' => array(),
		'fk' => array(),
		'ix' => array(),
		'uc' => array(array('host_id','host_extra_name'))
	));

	return $GLOBALS['setup_info']['example']['currentver'] = '26.1.003';
}

/**
 * Add the category column
 *
 * The meta type "category" is what marks it as holding a category id, rather than
 * just any integer.
 *
 * @return string
 */
function example_upgrade26_1_003()
{
	$GLOBALS['egw_setup']->oProc->AddColumn('egw_example', 'cat_id', array(
		'type' => 'int',
		'meta' => 'category',
		'precision' => '4',
		'default' => '0',
		'comment' => 'category'
	));

	return $GLOBALS['setup_info']['example']['currentver'] = '26.1.004';
}
