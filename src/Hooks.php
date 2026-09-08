<?php
/**
 * EGroupware - Example - hooks
 *
 * @link https://www.egroupware.org
 * @author Ralf Becker <rb-AT-egroupware.org>
 * @author Nathan Gray <ng-AT-egroupware.org>
 * @package example
 * @subpackage setup
 * @copyright (c) 2019-26 by Ralf Becker <rb-AT-egroupware.org>
 * @license https://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 */

namespace EGroupware\Example;

use EGroupware\Api;

/**
 * Hooks the rest of EGroupware calls into
 *
 * Which hooks we implement is declared in setup/setup.inc.php, and the list is
 * read when the app is installed or hooks are re-registered.
 */
class Hooks
{
	/**
	 * Tell the link system how to find, name and open our entries
	 *
	 * Everything that links to another application works from what we return
	 * here: the link dialog's search, the title shown on an existing link, and
	 * egw.open() in both PHP and JavaScript.
	 *
	 * @param array|string $location hook location, not used
	 * @return array the entry for our app in the link registry
	 */
	public static function search_link($location)
	{
		unset($location);	// not used, but part of the hook signature

		return [
			// finding an entry, and what to call it once found
			'query'      => Bo::APP.'.'.Bo::class.'.link_query',
			'title'      => Bo::APP.'.'.Bo::class.'.link_title',
			// opening one - view is what a user without edit rights gets
			'view'       => ['menuaction' => Bo::APP.'.'.Ui::class.'.edit'],
			'view_id'    => 'host_id',
			'view_popup' => '640x480',
			'edit'       => ['menuaction' => Bo::APP.'.'.Ui::class.'.edit'],
			'edit_id'    => 'host_id',
			'edit_popup' => '640x480',
			// the whole list, and adding a new entry
			'list'       => ['menuaction' => Bo::APP.'.'.Ui::class.'.index', 'ajax' => 'true'],
			'add'        => ['menuaction' => Bo::APP.'.'.Ui::class.'.edit'],
			'add_popup'  => '640x480',
		];
	}

	/**
	 * What this application offers an administrator
	 *
	 * Registered for the "admin" hook, which the Admin application calls to collect
	 * every app's section.  The interface itself is the API's - we only say which
	 * application it should be editing.
	 *
	 * @param string|array $args hook location, not used
	 */
	public static function admin($args)
	{
		unset($args);	// not used, but part of the hook signature

		if (empty($GLOBALS['egw_info']['user']['apps']['admin']))
		{
			return;
		}
		display_section(Bo::APP, [
			'Custom fields' => Api\Egw::link('/index.php', [
				'menuaction' => 'admin.admin_customfields.index',
				'appname'    => Bo::APP,
				'ajax'       => 'true',
			]),
			'Global Categories' => Api\Egw::link('/index.php', [
				'menuaction'  => 'admin.admin_categories.index',
				'appname'     => Bo::APP,
				'global_cats' => true,
				'ajax'        => 'true',
			]),
		]);
	}

	/**
	 * We use categories
	 *
	 * Answering this hook at all is what puts the application in the category
	 * administration's list, so an administrator can maintain categories for it.
	 *
	 * @param string|array $location hook location, not used
	 * @return boolean
	 */
	public static function categories($location)
	{
		unset($location);	// not used, but part of the hook signature

		return true;
	}

	/**
	 * The rights an owner can grant another user on their hosts
	 *
	 * This is what the ACL administration offers as tick-boxes, and the values are
	 * what Bo::check_perms() tests against.
	 *
	 * @param array|string $params hook parameters, not used
	 * @return array right => label
	 */
	public static function acl_rights($params)
	{
		unset($params);	// not used, but part of the hook signature

		return [
			Api\Acl::READ   => 'read',
			Api\Acl::EDIT   => 'edit',
			Api\Acl::DELETE => 'delete',
		];
	}
}
