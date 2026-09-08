<?php
/**
 * EGroupware - Example - business logic
 *
 * @link https://www.egroupware.org
 * @author Ralf Becker <rb-AT-egroupware.org>
 * @author Nathan Gray <ng-AT-egroupware.org>
 * @package example
 * @subpackage storage
 * @copyright (c) 2019-26 by Ralf Becker <rb-AT-egroupware.org>
 * @license https://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 */

namespace EGroupware\Example;

use EGroupware\Api;

/**
 * Storing and reading hosts
 *
 * Api\Storage\Base does the actual SQL: it reads the table definition from
 * setup/tables_current.inc.php and gives us read(), save(), delete() and search().
 */
class Bo extends Api\Storage\Base
{
	const APP = 'example';
	const TABLE = 'egw_example';

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct(self::APP, self::TABLE);

		// Work with Api\DateTime objects instead of raw timestamps: the storage layer
		// then converts our date columns between server- and user-time on every read
		// and write, which is what makes times right for a user in another timezone.
		// set_times() also makes $this->now an Api\DateTime, which save() stores.
		$this->set_times('object');
		$this->convert_all_timestamps();
	}

	/**
	 * Save a host
	 *
	 * Reimplemented to record who created and last changed it.
	 *
	 * @param array $keys =null if given, copied to data before saving
	 * @param string|array $extra_where =null extra where clause
	 * @return int 0 on success, errno otherwise
	 */
	public function save($keys = null, $extra_where = null)
	{
		$this->data_merge($keys);

		if (empty($this->data['host_id']))
		{
			$this->data['host_creator'] = $GLOBALS['egw_info']['user']['account_id'];
			$this->data['host_created'] = $this->now;
		}
		$this->data['host_modifier'] = $GLOBALS['egw_info']['user']['account_id'];
		$this->data['host_modified'] = $this->now;

		return parent::save(null, $extra_where);
	}
}
