<?php
/**
 * EGroupware - Setup
 * https://www.egroupware.org
 * Created by eTemplates DB-Tools written by ralfbecker@outdoor-training.de
 *
 * @license https://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @package example
 * @subpackage setup
 */

function example_upgrade19_1()
{
	$GLOBALS['egw_setup']->oProc->CreateTable('egw_example_extra',array(
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

	return $GLOBALS['setup_info']['example']['currentver'] = '23.1';
}

