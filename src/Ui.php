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
	];

	/**
	 * Index: what opening the application shows
	 *
	 * @param ?array $content =null
	 */
	public function index(?array $content=null)
	{
		unset($content);	// nothing submits to us yet

		echo "Hello World :)";
	}
}
