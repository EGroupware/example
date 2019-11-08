# Example app for EGroupware development

#### 1. Step: [minimal "Hello World" app](https://github.com/EGroupware/example/tree/step1)
#### 2. Step: [an edit dialog](https://github.com/EGroupware/example/tree/step2)
#### 3. Step: [create a database table for data persistence](https://github.com/EGroupware/example/tree/step3)

#### 4. Step: Add UI to list hosts
![step4-list](https://raw.githubusercontent.com/wiki/EGroupware/example/images/master-01.png)

* We need to create an eTemplate [index.xet](https://github.com/EGroupware/example/tree/step4/templates/default/index.xet) to display the host list / index page
```
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE overlay PUBLIC "-//EGroupware GmbH//eTemplate 2//EN" "http://www.egroupware.org/etemplate2.dtd">
<overlay>
	<template id="example.index.rows" template="" lang="" group="0" version="19.1.001">
		<grid width="100%">
			<columns>
				<column width="15"/>
				<column width="15%"/>
				<column width="40%"/>
				<column width="120"/>
				<column width="120"/>
			</columns>
			<rows>
				<row class="th">
					<nextmatch-sortheader label="#" id="host_id"/>
					<nextmatch-header label="Name" id="host_name"/>
					<nextmatch-header label="Description" id="host_description"/>
					<vbox>
						<nextmatch-sortheader label="Created" id="host_created"/>
						<nextmatch-accountfilter id="host_creator"/>
					</vbox>
					<vbox>
						<nextmatch-sortheader label="Last modified" id="host_modified"/>
						<nextmatch-accountfilter id="host_modifier"/>
					</vbox>
				</row>
				<row class="$row_cont[cat_id] $row_cont[class]">
					<description id="${row}[host_id]"/>
					<description id="${row}[host_name]"/>
					<description id="${row}[host_description]"/>
					<vbox>
						<date-time id="${row}[host_created]" readonly="true"/>
						<select-account id="${row}[host_creator]" readonly="true"/>
					</vbox>
					<vbox>
						<date-time id="${row}[host_modified]" readonly="true"/>
						<select-account id="${row}[host_modifier]" readonly="true"/>
					</vbox>
				</row>
			</rows>
		</grid>
	</template>
	<template id="example.index.add" template="" lang="" group="0" version="19.1.001">
		<buttononly statustext="Add" id="add"
			onclick="egw(window).openPopup(egw::link('/index.php','menuaction=example.EGroupware\\Example\\Ui.edit'),'640','480','_blank','example',null,true); return false;"/>
	</template>
	<template id="example.index" template="" lang="" group="0" version="19.1.001">
		<nextmatch id="nm" options="example.index.rows" header_left="example.index.add"/>
	</template>
</overlay>
```

* some more code in our Ui class to [display the list](https://github.com/EGroupware/example/tree/step4/src/Ui.php#L128):
```
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
					'order'          => 'host_modified',// IO name of the column to sort after (optional for the sortheaders)
					'sort'           => 'DESC',// IO direction of the sort: 'ASC' or 'DESC'
					'row_id'         => 'host_id',
					'row_modified'   => 'host_modified',
					'actions'        => $this->get_actions(),
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
```
* some more code in our Ui class to [fetch the rows to display](https://github.com/EGroupware/example/tree/step4/src/Ui.php#L111):
```
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
```
* [show a context menu on the list to eg. edit a row / host](https://github.com/EGroupware/example/tree/step4/src/Ui.php#L160)
```
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
```

* [code to load and save host to edit them](https://github.com/EGroupware/example/tree/step4/src/Ui.php#L45)
```
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
		$tmpl = new Api\Etemplate('example.edit');
		$tmpl->exec('example.'.self::class.'.edit', $content, [], [], $content, 2);
	}
```

--> [continue to step 5](https://github.com/EGroupware/example/tree/step3) by checking out branch ```step5``` in your workingcopy
