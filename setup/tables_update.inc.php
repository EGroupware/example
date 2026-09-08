<?php
/**
 * EGroupware - Example - schema and version updates
 *
 * One function per version, named <app>_upgrade<version with dots as underscores>.
 * Setup runs them in order until the app reaches the version in setup.inc.php.
 *
 * @link https://www.egroupware.org
 * @package example
 * @subpackage setup
 * @license https://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 */

/**
 * Nothing to change in the database - the new version is what makes setup re-read
 * setup.inc.php, and with it the changed entry point.
 *
 * @return string
 */
function example_upgrade26_1()
{
	return $GLOBALS['setup_info']['example']['currentver'] = '26.1.001';
}
