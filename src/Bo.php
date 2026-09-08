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
	 * Reimplemented to record who created and last changed it, and to store the
	 * links the dialog collected while the host had no id yet.
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

		if (($error = parent::save(null, $extra_where)) == 0 &&
			// A new host cannot be linked before it exists, so the dialog holds its
			// links until now and hands them over as link_to[to_id].
			!empty($keys['link_to']['to_id']) && is_array($keys['link_to']['to_id']))
		{
			Api\Link::link(self::APP, $this->data['host_id'], $keys['link_to']['to_id']);
		}
		return $error;
	}

	/**
	 * Delete a host
	 *
	 * Reimplemented to remove its links as well, so the entries on the other side
	 * do not keep pointing at a host that is gone.
	 *
	 * @param array|int $keys =null host_id, or col => value pairs, or null for the loaded host
	 * @param boolean $only_return_query =false not supported, part of the parent signature
	 * @return int number of rows deleted
	 */
	public function delete($keys = null, $only_return_query = false)
	{
		if (!is_array($keys) && (int)$keys)
		{
			$keys = ['host_id' => (int)$keys];
		}
		$host_id = $keys === null ? $this->data['host_id'] : $keys['host_id'];

		if (($deleted = parent::delete($keys)) && $host_id)
		{
			Api\Link::unlink(0, self::APP, $host_id);
		}
		return $deleted;
	}

	/**
	 * Name of a host, as other applications show it on a link
	 *
	 * Registered as "title" in the link registry.
	 *
	 * @param int|array $entry host_id, or the host itself if the caller already read it
	 * @return string|null|false name, null if there is no such host
	 */
	public function link_title($entry)
	{
		if (!is_array($entry))
		{
			// read() replaces $this->data, which whoever called us may still be using
			$backup =& $this->data;
			unset($this->data);
			$entry = $this->read(['host_id' => $entry]);
			$this->data =& $backup;
		}
		return $entry ? $entry['host_name'] : $entry;
	}

	/**
	 * Hosts matching what the user typed into the link dialog
	 *
	 * Registered as "query" in the link registry.
	 *
	 * @param string $pattern what to search for
	 * @param array& $options in: start & num_rows for paging, out: total for the pager
	 * @return array host_id => name
	 */
	public function link_query($pattern, array &$options = [])
	{
		$limit = false;
		$need_count = false;
		if (!empty($options['start']) || !empty($options['num_rows']))
		{
			$limit = [$options['start'], $options['num_rows']];
			$need_count = true;
		}
		$result = [];
		foreach((array)$this->search($pattern, false, 'host_name', '', '%', false, 'OR',
			$limit, null, '', $need_count) as $host)
		{
			if ($host) $result[$host['host_id']] = $this->link_title($host);
		}
		$options['total'] = $need_count ? $this->total : count($result);

		return $result;
	}
}
