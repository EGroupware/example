<?php
/**
 * EGroupware - Example - setup definitions
 *
 * @link http://www.egroupware.org
 * @author Ralf Becker <rb-AT-egroupware.org>
 * @package example
 * @subpackage setup
 * @copyright (c) 2019 by Ralf Becker <rb-AT-egroupware.org>
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 */

$setup_info['example']['name']      = 'example';
$setup_info['example']['version']   = '19.1';
$setup_info['example']['app_order'] = 5;
$setup_info['example']['enable']    = 1;
$setup_info['example']['index']     = 'example.'.EGroupware\Example\Ui::class.'.index';

$setup_info['example']['author'] =
$setup_info['example']['maintainer'] = array(
	'name'  => 'Ralf Becker',
	'email' => 'rb@egroupware.org',
);
$setup_info['example']['license']  = 'GPL';
$setup_info['example']['description'] =
'Just an example app to start developing with.';

/* Dependencies for this app to work */
$setup_info['example']['depends'][] = array(
	 'appname' => 'api',
	 'versions' => Array('19.1')
);
