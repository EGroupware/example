<?php
/**
 * EGroupware - Example - setup definitions
 *
 * @link https://www.egroupware.org
 * @author Ralf Becker <rb-AT-egroupware.org>
 * @author Nathan Gray <ng-AT-egroupware.org>
 * @package example
 * @subpackage setup
 * @copyright (c) 2019-26 by Ralf Becker <rb-AT-egroupware.org>
 * @license https://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 */

$setup_info['example']['name']      = 'example';
$setup_info['example']['version']   = '26.1.003';
$setup_info['example']['app_order'] = 5;
$setup_info['example']['enable']    = 1;
$setup_info['example']['tables']    = array('egw_example', 'egw_example_extra');
$setup_info['example']['index']     = 'example.EGroupware\\Example\\Ui.index&ajax=true';

$setup_info['example']['author'] =
$setup_info['example']['maintainer'] = array(
	'name'  => 'Ralf Becker',
	'email' => 'rb@egroupware.org',
);
$setup_info['example']['license']  = 'GPL';
$setup_info['example']['description'] =
'Just an example app to start developing with.';

// Hooks we implement, see src/Hooks.php.  Registered when the app is installed,
// and by Admin > Clear cache/register hooks.
$setup_info['example']['hooks']['search_link'] = 'EGroupware\\Example\\Hooks::search_link';
$setup_info['example']['hooks']['admin'] = 'EGroupware\\Example\\Hooks::admin';

/* Dependencies for this app to work */
$setup_info['example']['depends'][] = array(
	'appname'  => 'api',
	'versions' => array('26.1')
);
