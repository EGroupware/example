<?php
/**
 * EGroupware - Example - Business logic
 *
 * @link http://www.egroupware.org
 * @author Ralf Becker <rb-AT-egroupware.org>
 * @package example
 * @subpackage setup
 * @copyright (c) 2019 by Ralf Becker <rb-AT-egroupware.org>
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 */

namespace EGroupware\Example;

class Hooks
{
	/**
	 * Hook called by link-class to include example app / host in the appregistry of the linkage
	 *
	 * @param array|string $location location and other parameters (not used)
	 * @return array with method-names
	 */
	static function search_link($location)
	{
		unset($location);	// not used, but required by function signature

		return array(
			'query' => Bo::APP.'.'.Bo::class.'.link_query',
			'title' => Bo::APP.'.'.Bo::class.'.link_title',
			'view'  => array(
				'menuaction' => Bo::APP.'.'.Ui::class.'.edit',
			),
			'view_id' => 'host_id',
			'view_popup'  => '630x480',
			'edit'  => array(
				'menuaction' => Bo::APP.'.'.Ui::class.'.edit',
			),
			'edit_id' => 'host_id',
			'edit_popup'  => '630x480',
			'list' => array(
				'menuaction' => Bo::APP.'.'.Ui::class.'.index',
				'ajax' => 'true'
			),
			'add' => array(
				'menuaction' => Bo::APP.'.'.Ui::class.'.edit',
			),
			'add_popup'  => '630x480',
		);
	}
}