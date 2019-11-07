# Example app for development

#### [1. Step](https://github.com/EGroupware/example/tree/step1): minimal "Hello World" app

### 2. Step: an edit dialog
![step2-edit-dialog](https://user-images.githubusercontent.com/972180/68398527-2e6e3f80-0175-11ea-8eb0-81132ffc30f1.png)

* Ui in EGroupware uses eTemplate2 templating system rending an xml template plus some content on client-side
* create the following template under [templates/default/edit.xet](https://github.com/EGroupware/example/tree/step2/templates/default)
```
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE overlay PUBLIC "-//EGroupware GmbH//eTemplate 2//EN" "http://www.egroupware.org/etemplate2.dtd">
<overlay>
	<template id="example.edit" template="" lang="" group="0" version="19.1">
		<grid width="100%">
			<columns>
				<column width="100"/>
				<column/>
			</columns>
			<rows>
				<row>
					<description for="hostname" value="Hostname"/>
					<textbox id="hostname" tabindex="1" maxlength="64" class="et2_fullWidth" />
				</row>
				<row>
					<description for="description" value="Description"/>
					<textbox id="description" tabindex="2" class="et2_fullWidth" rows="7"/>
				</row>
				<row>
					<hbox span="all" width="100%">
						<button accesskey="s" label="Save" id="button[save]"/>
						<button label="Apply" id="button[apply]"/>
						<button label="Cancel" id="button[cancel]" onclick="window.close(); return false;"/>
						<button align="right" label="Delete" id="button[delete]" onclick="et2_dialog.confirm(widget,'Do you really want to delete this host?','Delete')"/>
					</hbox>
				</row>
			</rows>
		</grid>
	</template>
</overlay>
```
* and some code in [src/Ui.php](https://github.com/EGroupware/example/tree/step2/src/Ui.php) to call it
```
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
	 * Edit a host
	 *
	 * @param array $content =null
	 */
	public function edit(array $content=null)
	{
		if (!is_array($content))
		{
			$content = [];
		}
		$tmpl = new Api\Etemplate('example.edit');
		$tmpl->exec('example.'.self::class.'.edit', $content);
	}

	/**
	 * Index
	 *
	 * @param array $content =null
	 */
	public function index(array $content=null)
	{
		$this->edit();
	}
}
```
* In step 1 our minimal app was echo-ing out "Hello World". To make that happen, it had to run in an iframe. Now as we use the eTemplate system, we no longer need an iframe (with all the problem it has).
* To let EGroupware framework know, that we don't need an iframe, we have to change the index entrypoint (and other links we genrate) by using a get parameter ```ajax=true``` in our [setup/setup.inc.php]():
```
$setup_info['example']['index']     = 'example.'.EGroupware\Example\Ui::class.'.index&ajax=true';
```
* after that change we either have to uninstall and re-install the app as described in step 1, or create an update script for it or most quickly run the following SQL on the EGroupware database AND then log out and in again:
```
UPDATE egw_applications SET app_index='example.EGroupware\\Example\\Ui::class.index&ajax=true';
```
