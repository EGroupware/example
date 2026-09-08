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
 * Api\Storage does the actual SQL: it reads the table definition from
 * setup/tables_current.inc.php and gives us read(), save(), delete() and search().
 * It also stores custom fields, in a second table, keyed by the entry they belong
 * to - so read() and save() carry them along with everything else, and the rest of
 * this class needs to know nothing about them.
 */
class Bo extends Api\Storage
{
	const APP = 'example';
	const TABLE = 'egw_example';
	const EXTRA_TABLE = 'egw_example_extra';

	/**
	 * What the current user may do, and to whose hosts
	 *
	 * account_id => rights.  Every user implicitly has all rights on their own,
	 * plus whatever other users have granted them.
	 *
	 * @var array
	 */
	protected $grants;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		// the last two name the columns holding a custom field's name and its value;
		// a leading underscore is prefixed with the column prefix, here "host"
		parent::__construct(self::APP, self::TABLE, self::EXTRA_TABLE, '', '_extra_name', '_extra_value');

		// Work with Api\DateTime objects instead of raw timestamps: the storage layer
		// then converts our date columns between server- and user-time on every read
		// and write, which is what makes times right for a user in another timezone.
		// set_times() also makes $this->now an Api\DateTime, which save() stores.
		$this->set_times('object');
		$this->convert_all_timestamps();

		$this->grants = $GLOBALS['egw']->acl->get_grants(self::APP);
	}

	/**
	 * Save a host
	 *
	 * Reimplemented to record who created and last changed it, to store the links the
	 * dialog collected while the host had no id yet, and to refuse a change the user
	 * is not allowed to make.
	 *
	 * @param array $keys =null if given, copied to data before saving
	 * @param string|array $extra_where =null extra where clause
	 * @return int 0 on success, errno otherwise
	 */
	public function save($keys = null, $extra_where = null)
	{
		$this->data_merge($keys);

		// Changing an existing host needs edit rights on it.  The user interface does
		// not offer the attempt, so reaching here means something went around it.
		// Check against the stored host, not the submitted one: the dialog has no
		// owner field, and a client must not be able to decide who owns an entry.
		if (!empty($this->data['host_id']) && !$this->check_perms(Api\Acl::EDIT, $this->stored()))
		{
			throw new Api\Exception\NoPermission();
		}
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
	 * Reimplemented to remove its links as well, so the entries on the other side do
	 * not keep pointing at a host that is gone, and to delete only what the user is
	 * allowed to - deleting several at once must not stop at the first one it may not
	 * touch.
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

		// read() already refuses a host we may not even see
		if (!($host = $this->read(['host_id' => $host_id])) ||
			!$this->check_perms(Api\Acl::DELETE, $host))
		{
			return 0;
		}
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

	/**
	 * The host as it is stored, regardless of what has been merged into data
	 *
	 * read() replaces $this->data, which we are in the middle of using, so it is put
	 * back afterwards.
	 *
	 * @return array the stored host, or an empty array if it is not there
	 */
	protected function stored()
	{
		$backup =& $this->data;
		unset($this->data);
		$stored = parent::read(['host_id' => $backup['host_id']]);
		$this->data =& $backup;

		return $stored ?: [];
	}

	/**
	 * May the current user do $required to this host?
	 *
	 * @param int $required Api\Acl::READ, EDIT or DELETE
	 * @param array $host the host to check, as read() returns it
	 * @return boolean
	 */
	public function check_perms($required, array $host)
	{
		return (bool)(($this->grants[$host['host_creator']] ?? 0) & $required);
	}

	/**
	 * Search hosts
	 *
	 * Reimplemented to only ever return hosts the user is allowed to see.  Every read
	 * path ends up here - the list, the link dialog, an action re-applying a stored
	 * filter - so this is the one place the permission filter cannot be gone around.
	 *
	 * @param array|string $criteria
	 * @param boolean|string|array $only_keys
	 * @param string $order_by
	 * @param string|array $extra_cols
	 * @param string $wildcard
	 * @param boolean $empty
	 * @param string $op
	 * @param boolean|int $start
	 * @param array $filter
	 * @param string $join
	 * @param boolean $need_full_no_count
	 * @return array|null
	 */
	public function &search($criteria, $only_keys=true, $order_by='', $extra_cols='', $wildcard='', $empty=false,
		$op='AND', $start=false, $filter=null, $join='', $need_full_no_count=false)
	{
		$filter = (array)$filter;
		$permitted = array_keys($this->grants);

		// The list offers a Creator filter of its own, so do not simply overwrite what
		// the caller asked for: narrow it instead.  Replacing it would quietly widen
		// their filter back out to everything they may see.
		$filter['host_creator'] = isset($filter['host_creator']) && $filter['host_creator'] !== '' ?
			array_intersect((array)$filter['host_creator'], $permitted) : $permitted;

		return parent::search($criteria, $only_keys, $order_by, $extra_cols, $wildcard, $empty,
			$op, $start, $filter, $join, $need_full_no_count);
	}

	/**
	 * Read a host
	 *
	 * Reimplemented to answer false for a host the user may not read.  Deliberately
	 * the same answer as for one that does not exist, so nobody can discover which
	 * hosts are there by asking for them one id at a time.
	 *
	 * @param array|int $keys
	 * @param string|array $extra_cols
	 * @param string $join
	 * @return array|boolean the host, or false
	 */
	public function read($keys, $extra_cols='', $join='')
	{
		$host = parent::read($keys, $extra_cols, $join);

		return $host && $this->check_perms(Api\Acl::READ, $host) ? $host : false;
	}
}
