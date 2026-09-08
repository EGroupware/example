<?php
/**
 * EGroupware - Example - User interface
 *
 * @link https://www.egroupware.org
 * @author Ralf Becker <rb-AT-egroupware.org>
 * @author Nathan Gray <ng-AT-egroupware.org>
 * @package example
 * @subpackage ui
 * @copyright (c) 2019-26 by Ralf Becker <rb-AT-egroupware.org>
 * @license https://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 */

namespace EGroupware\Example;

use EGroupware\Api;

class Ui
{
	/**
	 * Methods callable via the menuaction GET parameter
	 *
	 * Anything not listed here cannot be reached from a URL.
	 *
	 * @var array
	 */
	public $public_functions = [
		'index' => true,
		'edit'  => true,
	];

	/**
	 * Instance of our business object
	 *
	 * @var Bo
	 */
	protected $bo;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->bo = new Bo();
	}

	/**
	 * Edit a host
	 *
	 * @param ?array $content =null values from the submitted form, null on first call
	 */
	public function edit(?array $content=null)
	{
		if (!is_array($content))
		{
			// first call: either an existing host, or an empty one to add
			if (!empty($_GET['host_id']))
			{
				// read() answers false for a host that is not there.  init() answers an
				// empty array, which is a perfectly good new host - so only treat a
				// failed read as "not found", not an empty one.
				if (($content = $this->bo->read(['host_id' => (int)$_GET['host_id']])) === false)
				{
					Api\Framework::message(lang('Entry not found!'), 'error');
					$content = $this->bo->init();
				}
			}
			else
			{
				$content = $this->bo->init();

				// Preselect the category this user chose.  What arrives here is already
				// the effective preference, so there is nothing to fall back to: the
				// preference system has resolved user, group, installation default and
				// anything an administrator forced, before we are asked.
				$content['cat_id'] = $GLOBALS['egw_info']['user']['preferences'][Bo::APP]['default_cat'] ?? null;
			}
		}
		else
		{
			// which button was pressed?  Guard the key: a submit can arrive without one.
			$button = key($content['button'] ?? []);
			unset($content['button']);

			switch($button)
			{
				case 'save':
				case 'apply':
					// An installation can insist on a description.  The browser could
					// check this too, and should, but the server is the only place the
					// rule can actually be enforced.
					$config = Api\Config::read(Bo::APP);
					if (!empty($config['require_description']) && empty($content['host_description']))
					{
						Api\Etemplate::set_validation_error('host_description',
							lang('Description is required'));
						break;	// no save, and the dialog comes back with the error on it
					}
					if (!$this->bo->save($content))
					{
						// tell the list that opened us to refresh this row
						Api\Framework::refresh_opener(lang('Entry saved.'), Bo::APP,
							$this->bo->data['host_id'], empty($content['host_id']) ? 'add' : 'edit');

						if ($button === 'save')
						{
							Api\Framework::window_close();	// does NOT return
						}
						Api\Framework::message(lang('Entry saved.'));
						$content = array_merge($content, $this->bo->data);
					}
					else
					{
						Api\Framework::message(lang('Error storing entry!'), 'error');
					}
					break;

				case 'delete':
					if ($this->bo->delete(['host_id' => $content['host_id']]))
					{
						Api\Framework::refresh_opener(lang('Entry deleted.'), Bo::APP,
							$content['host_id'], 'delete');
						Api\Framework::window_close();	// does NOT return
					}
					else
					{
						Api\Framework::message(lang('Error deleting entry!'), 'error');
					}
					break;
			}
		}
		// What the link widgets work on: the host itself once it exists, or - while it
		// does not - whatever links the dialog is holding for us until it does.  to_id
		// of 0 is what tells et2-link-to to hold them client-side rather than store
		// them against an id that is not there yet.
		$content['link_to'] = [
			'to_id'  => $content['host_id'] ?: ($content['link_to']['to_id'] ?? 0),
			'to_app' => Bo::APP,
		];
		$readonlys = [
			'button[delete]' => empty($content['host_id']) ||
				!$this->bo->check_perms(Api\Acl::DELETE, $content),
		];
		// read-only rights: show the host, but let nothing be changed or saved
		if (!empty($content['host_id']) && !$this->bo->check_perms(Api\Acl::EDIT, $content))
		{
			$readonlys['__ALL__'] = true;
		}
		// Preserved values come back on the next submit without a widget carrying them,
		// and so without the browser being able to change them on the way.  Name the
		// ones that need it rather than preserving $content wholesale: host_id is what
		// says which host we are editing, and everything else on this dialog either has
		// a widget of its own or is rebuilt above.
		$preserv = [
			'host_id' => $content['host_id'] ?? null,
		];

		$tmpl = new Api\Etemplate('example.edit');
		$tmpl->exec('example.'.self::class.'.edit', $content, [], $readonlys, $preserv);
	}

	/**
	 * Fetch the rows the list asks for
	 *
	 * Called by the nextmatch widget, not from a URL, so it is not in
	 * $public_functions - the widget reaches it through its own exec-id.
	 *
	 * @param array $query what to fetch: search, sort order, which page
	 * @param array& $rows =null the rows to show
	 * @param array& $readonlys =null
	 * @return int total number of rows matching, for the pager
	 */
	public function get_rows($query, ?array &$rows=null, ?array &$readonlys=null)
	{
		// The category filter arrives on its own, not in col_filter, so map it across
		// for the storage layer.  Doing it before the query is stored means an action
		// running on "select all" filters by category exactly like the list does.
		if ((string)($query['cat_id'] ?? '') !== '')
		{
			$query['col_filter']['cat_id'] = $query['cat_id'];
		}
		else
		{
			// nothing selected: drop it, or the last choice would filter forever
			unset($query['col_filter']['cat_id']);
		}

		// Remember the query, so the filters and sorting survive a reload and
		// action() can re-apply exactly this one instead of trusting the client.
		//
		// Not every call is a real listing though: a CSV export, and the single-row
		// fetch behind a push update, both arrive with $query['csv_export'] set and a
		// col_filter narrowed to just the rows they want.  Storing one of those would
		// leave the list showing only those rows - and it would survive a reload,
		// because this is exactly what a reload restores.
		if (empty($query['csv_export']))
		{
			Api\Cache::setSession(Bo::APP, 'index', $query);
		}

		$total = $this->bo->get_rows($query, $rows, $readonlys);

		// Tell each row what may be done to it.  The action framework reads these
		// classes per row, so a selection mixing hosts you may delete with hosts you
		// may not still offers Delete - and runs it only on the ones it applies to.
		foreach($rows as $n => &$row)
		{
			if (!is_int($n)) continue;	// skip the non-row keys get_rows() adds

			if (!$this->bo->check_perms(Api\Acl::EDIT, $row))
			{
				$row['class'] .= ' rowNoEdit';
			}
			if (!$this->bo->check_perms(Api\Acl::DELETE, $row))
			{
				$row['class'] .= ' rowNoDelete';
			}
		}

		return $total;
	}

	/**
	 * Actions offered on the host list, shown in its context menu
	 *
	 * @return array
	 */
	protected function get_actions()
	{
		return [
			'view' => [
				'caption' => 'View',
				'default' => true,
				'allowOnMultiple' => false,
				// not a URL: this one runs a method on our own app object
				'onExecute' => 'javaScript:app.example.view',
				'group' => $group=0,
			],
			'edit' => [
				'caption' => 'Edit',
				'allowOnMultiple' => false,
				'url' => 'menuaction=example.'.self::class.'.edit&host_id=$id',
				'popup' => '640x480',
				'disableClass' => 'rowNoEdit',
				'group' => $group,
			],
			'add' => [
				'caption' => 'Add',
				'url' => 'menuaction=example.'.self::class.'.edit',
				'popup' => '640x480',
				'group' => $group,
			],
			'delete' => [
				'caption' => 'Delete',
				'confirm' => 'Delete this host(s)',
				'disableClass' => 'rowNoDelete',
				'group' => $group=5,
			],
		];
	}

	/**
	 * Run an action on the selected hosts
	 *
	 * @param string $action name of the action, eg. "delete"
	 * @param array $selected host_id's of the selected rows
	 * @param boolean $select_all true if the user selected everything matching the filters
	 * @param array $query the remembered nextmatch state, used to resolve $select_all
	 * @return string message to show
	 * @throws Api\Exception\AssertionFailed for an unknown action
	 */
	protected function action($action, array $selected, $select_all, array $query=[])
	{
		// "select all" means every host matching the current filters, not just the rows
		// the browser had loaded.  $start=false returns all of them, unpaged.
		if ($select_all)
		{
			$selected = array_column($this->bo->search($query['search'] ?? '', ['host_id'],
				'', '', '%', false, 'OR', false, $query['col_filter'] ?? []), 'host_id');
		}
		switch($action)
		{
			case 'delete':
				$deleted = 0;
				foreach($selected as $host_id)
				{
					if ($this->bo->delete(['host_id' => (int)$host_id]))
					{
						$deleted++;
					}
				}
				return lang('%1 host(s) deleted.', $deleted);
		}
		throw new Api\Exception\AssertionFailed(lang('Unknown action %1!', $action));
	}

	/**
	 * Index: the list of hosts
	 *
	 * @param ?array $content =null
	 */
	public function index(?array $content=null)
	{
		if (!is_array($content) || empty($content['nm']))
		{
			// the user's filter state survives a reload
			$nm = Api\Cache::getSession(Bo::APP, 'index');
			if (!is_array($nm))
			{
				$nm = [
					'order' => 'host_modified',	// sort by this until the user picks another
					'sort'  => 'DESC',
				];
			}
			// Everything the app itself owns is applied fresh on top, so a session
			// stored by an older version of the app can never break the list.
			$content = ['nm' => array_merge($nm, [
				'get_rows'            => Bo::APP.'.'.self::class.'.get_rows',
				'no_filter'           => true,	// we offer no filter ...
				'no_filter2'          => true,	// ... and no second one
				'row_id'              => 'host_id',
				'row_modified'        => 'host_modified',
				'actions'             => $this->get_actions(),
				'placeholder_actions' => ['add'],
			])];
		}
		elseif(!empty($content['nm']['action']))
		{
			try {
				// the remembered query, NOT $content['nm'] - the client must not be able
				// to widen a "select all" by sending filters it never displayed
				Api\Framework::message($this->action($content['nm']['action'],
					$content['nm']['selected'], $content['nm']['select_all'],
					Api\Cache::getSession(Bo::APP, 'index') ?: []));
			}
			catch (\Exception $ex) {
				Api\Framework::message($ex->getMessage(), 'error');
			}
		}
		$tmpl = new Api\Etemplate('example.index');
		$tmpl->exec('example.'.self::class.'.index', $content, [], [], ['nm' => $content['nm']]);
	}
}
