<?php
/**
 * EGroupware - Example - User interface
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

class Ui
{
	/**
	 * Methods callable via menuaction GET parameter
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
	 * @param array $content =null
	 */
	public function edit(array $content=null)
	{
		if (!is_array($content))
		{
			if (!empty($_GET['host_id']))
			{
				if (!($content = $this->bo->read(['host_id' => $_GET['host_id']])))
				{
					Api\Framework::window_close(lang('Entry not found!'));
				}
			}
			else
			{
				$content = $this->bo->init();
			}
		}
		else
		{
			$button = key($content['button']);
			unset($content['button']);
			switch($button)
			{
				case 'save':
				case 'apply':
					if (!$this->bo->save($content))
					{
						Api\Framework::refresh_opener(lang('Entry saved.'),
							Bo::APP, $this->bo->data['host_id'],
							empty($content['host_id']) ? 'add' : 'edit');

						$content = $this->bo->data;
					}
					else
					{
						Api\Framework::message(lang('Error storing entry!'));
						unset($button);
					}
					if ($button === 'save')
					{
						Api\Framework::window_close();	// does NOT return
					}
					Api\Framework::message(lang('Entry saved.'));
					break;

				case 'delete':
					if (!$this->bo->delete(['host_id' => $content['host_id']]))
					{
						Api\Framework::message(lang('Error deleting entry!'));
					}
					else
					{
						Api\Framework::refresh_opener(lang('Entry deleted.'),
							Bo::APP, $content['host_id'], 'delete');

						Api\Framework::window_close();	// does NOT return
					}
			}
		}
		$content['link_to'] = [
			'to_id' => $this->data['host_id'] ? $this->data['host_id'] :
				($this->data['link_to']['host_id'] ? $this->data['link_to']['host_id'] : $content['link_to']['host_id']),
			'to_app' => Bo::APP,
		];
		$readonlys = [
			'button[delete]' => !$content['host_id'],
		];
		$tmpl = new Api\Etemplate('example.edit');
		$tmpl->exec('example.'.self::class.'.edit', $content, [], $readonlys, $content, 2);
	}

	/**
	 * Fetch rows to display
	 *
	 * @param array $query
	 * @param array& $rows =null
	 * @param array& $readonlys =null
	 */
	public function get_rows($query, array &$rows=null, array &$readonlys=null)
	{
		return $this->bo->get_rows($query, $rows, $readonlys);
	}

	/**
	 * Index
	 *
	 * @param array $content =null
	 */
	public function index(array $content=null)
	{
		if (!is_array($content) || empty($content['nm']))
		{
			$content = [
				'nm' => [
					'get_rows'       =>	Bo::APP.'.'.self::class.'.get_rows',
					'no_filter'      => true,	// disable the diverse filters we not (yet) use
					'no_filter2'     => true,
					'no_cat'         => true,
					'order'          =>	'host_modified',// IO name of the column to sort after (optional for the sortheaders)
					'sort'           =>	'DESC',// IO direction of the sort: 'ASC' or 'DESC'
					'row_id'         => 'host_id',
					'row_modified'   => 'host_modified',
					'actions'        => $this->get_actions(),
					'placeholder_actions' => array('add')
				]
			];
		}
		elseif(!empty($content['nm']['action']))
		{
			try {
				Api\Framework::message($this->action($content['nm']['action'],
					$content['nm']['selected'], $content['nm']['select_all']));
			}
			catch (\Exception $ex) {
				Api\Framework::message($ex->getMessage(), 'error');
			}
		}
		$tmpl = new Api\Etemplate('example.index');
		$tmpl->exec('example.'.self::class.'.index', $content, [], [], ['nm' => $content['nm']]);
	}

	/**
	 * Return actions for cup list
	 *
	 * @param array $cont values for keys license_(nation|year|cat)
	 * @return array
	 */
	protected function get_actions()
	{
		return [
			'edit' => [
				'caption' => 'Edit',
				'default' => true,
				'allowOnMultiple' => false,
				'url' => 'menuaction=example.'.self::class.'.edit&host_id=$id',
				'popup' => '640x480',
				'group' => $group=0,
			],
			'view' => [
				'caption' => 'View',
				'default' => true,
				'allowOnMultiple' => false,
				'onExecute' => 'javaScript:app.example.view',
				'group' => $group,
			],
			'add' => [
				'caption' => 'Add',
				'url' => 'menuaction=example.'.self::class.'.edit',
				'popup' => '640x320',
				'group' => $group,
			],
			'delete' => [
				'caption' => 'Delete',
				'confirm' => 'Delete this host(s)',
				'group' => $group=5,
			],
		];
	}

	/**
	 * Execute ation on list
	 *
	 * @param string $action
	 * @param array|int $selected
	 * @param boolean $select_all
	 * @returns tring with success message
	 * @throws Api\Exception\AssertionFailed
	 */
	protected function action($action, $selected, $select_all)
	{
		unset($action, $selected, $select_all);

		throw new Api\Exception\AssertionFailed('To be implemented ;)');
	}
}