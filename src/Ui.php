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
	 * Edit a host
	 *
	 * Called twice for one round trip: once with null to draw the form, and again
	 * with what the browser submitted.
	 *
	 * @param ?array $content =null values from the submitted form, null on first call
	 */
	public function edit(?array $content=null)
	{
		if (!is_array($content))
		{
			$content = [];
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
					// nowhere to put it yet - the next step gives the app a table
					Api\Framework::message(lang('Entry saved.'));
					break;
			}
		}
		$tmpl = new Api\Etemplate('example.edit');
		$tmpl->exec('example.'.self::class.'.edit', $content);
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
