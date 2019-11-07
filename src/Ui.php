<?php
/**
 * EGroupware - Example - setup definitions
 *
 * @link http://www.egroupware.org
 * @author Ralf Becker <rb-AT-egroupware.org>
 * @package example
 * @subpackage setup
 * @copyright (c) 2019 by Ralf Becker <rb-AT-egroupware.org>
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 */

namespace EGroupware\Example;

class Ui
{
	/**
	 * Methods callable via menuaction GET parameter
	 *
	 * @var array
	 */
	public $public_functions = [
		'index' => true,
	];

	/**
	 * Index
	 *
	 * @param array $content =null
	 */
	public function index(array $content=null)
	{
		echo "Hello World :)";
	}
}