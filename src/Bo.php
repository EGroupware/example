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

use EGroupware\Api;

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
	}

	/**
	 * saves the content of data to the db
	 *
	 * @param array $keys =null if given $keys are copied to data before saveing => allows a save as
	 * @param string|array $extra_where =null extra where clause, eg. to check an etag, returns true if no affected rows!
	 * @return int|boolean 0 on success, or errno != 0 on error, or true if $extra_where is given and no rows affected
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