<?php
/**
 * EGroupware - Example - records created when the app is installed
 *
 * @link https://www.egroupware.org
 * @package example
 * @subpackage setup
 * @license https://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 */

use EGroupware\Api;

// give the Default group rights to run the app, so it is usable straight after installing
$defaultgroup = $GLOBALS['egw_setup']->add_account('Default', 'Default', 'Group', false, false);
$GLOBALS['egw_setup']->add_acl('example', 'run', $defaultgroup);

// The app is normally installed into an EGroupware that already has users, so there is
// someone to own the demo data.  Fall back to the Default group if there is not.
$owner = $GLOBALS['egw_setup']->db->select($GLOBALS['egw_setup']->accounts_table, 'account_id', array(
	'account_type' => 'u',
	"account_lid != 'anonymous'",
), __LINE__, __FILE__, false, 'ORDER BY account_id ASC', false, 1)->fetchColumn();
if (empty($owner))
{
	$owner = $defaultgroup;
}

// two hosts, so the app has something to show on a fresh install
foreach ([
	['host_name' => 'localhost',   'host_description' => 'This host'],
	['host_name' => 'example.org', 'host_description' => 'Some other host'],
] as $host)
{
	$GLOBALS['egw_setup']->oProc->insert('egw_example', $host + array(
		'host_creator'  => $owner,
		'host_created'  => Api\DateTime::to('now', Api\DateTime::DATABASE),
		'host_modifier' => $owner,
		'host_modified' => Api\DateTime::to('now', Api\DateTime::DATABASE),
	), array('host_name' => $host['host_name']), __LINE__, __FILE__, 'example');
}
