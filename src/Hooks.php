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
}
