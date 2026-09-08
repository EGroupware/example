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
	 * Anything not listed here cannot be reached from a URL, however public the
	 * method is.
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
					if (!$this->bo->save($content))
					{
						Api\Framework::message(lang('Entry saved.'));
						// take back what storage made of it: the new id, and the
						// timestamps it set
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
						Api\Framework::message(lang('Entry deleted.'));
						$content = $this->bo->init();
					}
					else
					{
						Api\Framework::message(lang('Error deleting entry!'), 'error');
					}
					break;
			}
		}
		$readonlys = [
			'button[delete]' => empty($content['host_id']),
		];
		// Preserved values come back on the next submit without a widget carrying them,
		// and so without the browser being able to change them on the way.  Name the
		// ones that need it: host_id is what says which host we are editing.
		$preserv = [
			'host_id' => $content['host_id'] ?? null,
		];

		$tmpl = new Api\Etemplate('example.edit');
		$tmpl->exec('example.'.self::class.'.edit', $content, [], $readonlys, $preserv);
	}

	/**
	 * Index: what opening the application shows
	 *
	 * @param ?array $content =null
	 */
	public function index(?array $content=null)
	{
		unset($content);

		$this->edit();
	}
}
