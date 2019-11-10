# Example app for EGroupware development

#### 1. Step: [minimal "Hello World" app](https://github.com/EGroupware/example/tree/step1)
#### 2. Step: [an edit dialog](https://github.com/EGroupware/example/tree/step2)
#### 3. Step: [create a database table for data persistence](https://github.com/EGroupware/example/tree/step3)
#### 4. Step: [add UI to list hosts](https://github.com/EGroupware/example/tree/step4)
#### 5. Step: Linking with other EGroupware entries and attaching files
EGroupware has a system allowing all apps to link their entries with entries from other apps. E. g. book a time sheet on an example entry or link it to a customer. The link system also allows to attach files to entries of participating apps.

![step5-links](https://user-images.githubusercontent.com/972180/68548212-98dbe580-03ea-11ea-876a-45def07e377d.png)

* To participate in the link system we have to register a "search_link" hook in our [setup/setup.inc.php](https://github.com/EGroupware/example/tree/step5/setup/setup.inc.php#L32) file
```
$setup_info['example']['hooks']['search_link'] = EGroupware\Example\Hooks::class.'::search_link';
```
* And implement it e.g. in a new class [src/Hooks.php](https://github.com/EGroupware/example/tree/step5/src/Hooks.php)
```
namespace EGroupware\Example;

class Hooks
{
	/**
	 * Hook called by link-class to include example app / host in the appregistry of the linkage
	 *
	 * @param array|string $location location and other parameters (not used)
	 * @return array with method-names
	 */
	static function search_link($location)
	{
		unset($location);	// not used, but required by function signature

		return array(
			'query' => Bo::APP.'.'.Bo::class.'.link_query',
			'title' => Bo::APP.'.'.Bo::class.'.link_title',
			'view'  => array(
				'menuaction' => Bo::APP.'.'.Ui::class.'.edit',
			),
			'view_id' => 'host_id',
			'view_popup'  => '630x480',
			'edit'  => array(
				'menuaction' => Bo::APP.'.'.Ui::class.'.edit',
			),
			'edit_id' => 'host_id',
			'edit_popup'  => '630x480',
			'edit_popup'  => '630x480',
			'list' => array(
				'menuaction' => Bo::APP.'.'.Ui::class.'.index',
				'ajax' => 'true'
			),
			'add' => array(
				'menuaction' => Bo::APP.'.'.Ui::class.'.edit',
			),
			'add_popup'  => '630x480',
		);
	}
}
```
The link system abstracts EGroupware entries, by addressing them via their app-name and -id. It contains eg. methods to search entries of an app (returning id => title pairs), get title of an app-name & -id pair or how to add, edit, view or list entries of an app. More information about the link system and the various keys of the "search_link" hook can be found in [Api\Link class](https://github.com/EGroupware/egroupware/blob/master/api/src/Link.php#L33).

* We need to add some code in our Bo class to [implement the hooks](https://github.com/EGroupware/example/tree/step5/src/Bo.php#L84) registered by "search_link":
```
/**
	 * get name for an example entry / host identified by $entry
	 *
	 * Is called as hook to participate in the linking
	 *
	 * @param int|array $entry int ts_id or array with timesheet entry
	 * @return string/boolean string with title, null if timesheet not found, false if no perms to view it
	 */
	function link_title( $entry )
	{
		if (!is_array($entry))
		{
			// need to preserve the $this->data
			$backup =& $this->data;
			unset($this->data);
			$entry = $this->read(['host_id' => $entry]);
			// restore the data again
			$this->data =& $backup;
		}
		if (!$entry)
		{
			return $entry;
		}
		return $entry['host_name'];
	}

	/**
	 * query example app for entries matching $pattern
	 *
	 * Is called as hook to participate in the linking
	 *
	 * @param string $pattern pattern to search
	 * @param array $options Array of options for the search
	 * @return array with ts_id - title pairs of the matching entries
	 */
	function link_query($pattern, Array &$options = array() )
	{
		$limit = false;
		$need_count = false;
		if($options['start'] || $options['num_rows'])
		{
			$limit = array($options['start'], $options['num_rows']);
			$need_count = true;
		}
		$result = [];
		foreach($this->search($pattern,false,'','','%',false,'OR', $limit, null, '', $need_count) as $row)
		{
			$result[$row['host_id']] = $this->link_title($row);
		}
		$options['total'] = $need_count ? $this->total : count($result);
		return $result;
	}
```

* To be able to link or attach files in our example app, we have to add some more widgets to our edit popup:
```
<overlay>
	<template id="example.edit.notes" template="" lang="" group="0" version="19.1.001">
		<textbox multiline="true" id="host_description" rows="14" cols="70" class="et2_fullWidth"/>
	</template>
	<template id="example.edit.links" template="" lang="" group="0" version="19.1.001">
		<grid width="100%" overflow="auto">
			<columns>
				<column width="99%"/>
			</columns>
			<rows>
				<row>
					<link-to id="link_to"/>
				</row>
				<row class="th">
					<description value="Existing links"/>
				</row>
				<row>
					<link-list id="link_to"/>
				</row>
			</rows>
		</grid>
	</template>
	<template id="example.edit" template="" lang="" group="0" version="19.1">
		<grid width="100%">
			<columns>
				<column width="100"/>
				<column/>
			</columns>
			<rows>
				<row>
					<description for="host_name" value="Hostname"/>
					<textbox id="host_name" tabindex="1" maxlength="64" class="et2_fullWidth" />
				</row>
				<row>
					<tabbox id="tabs" class="et2_nowrap" width="100%" span="all" tab_height="200">
						<tabs>
							<tab id="notes" label="Description"/>
							<tab id="links" label="Links"/>
						</tabs>
						<tabpanels>
							<template id="example.edit.notes"/>
							<template id="example.edit.links"/>
						</tabpanels>
					</tabbox>
				</row>
				<!-- the rest of the file is unchanged -->
```

* While above link-to widget takes fully care of adding links and files to existing entries (with know id), we need to [help in adding them to new entries](https://github.com/EGroupware/example/tree/step5/src/Bo.php#L31) registered by "search_link":
```
	/**
	 * saves the content of data to the db
	 *
	 * Reimplemented to set creator and modifier(ed) and save links for new entries.
	 *
	 * @param array $keys =null if given $keys are copied to data before saveing => allows a save as
	 * @param string|array $extra_where =null extra where clause, eg. to check an etag, returns true if no affected rows!
	 * @return int|boolean 0 on success, or errno != 0 on error, or true if $extra_where is given and no rows affected
	 */
	public function save($keys = null, $extra_where = null)
	{
		$this->data_merge($keys);

		if (empty($this->data['host_id']))
		{
			$this->data['host_creator'] = $GLOBALS['egw_info']['user']['account_id'];
			$this->data['host_created'] = $this->now;
		}
		$this->data['host_modifier'] = $GLOBALS['egw_info']['user']['account_id'];
		$this->data['host_modified'] = $this->now;

		if (($ret = parent::save(null, $extra_where)) == 0 &&
			// check if we have links to save (new entries only)
			is_array($keys['link_to']['to_id']) && count($keys['link_to']['to_id']))
		{
			Api\Link::link(self::APP, $this->data['host_id'], $keys['link_to']['to_id']);
		}
	}
```

* and [let the link-system know if an entry get's deleted](https://github.com/EGroupware/example/tree/step5/src/Bo.php#L60):
```
	/**
	 * Deletes an example entry identified by $keys or the loaded one
	 *
	 * Reimplemented to notify the link class (unlink)
	 *
	 * @param array $keys if given array with col => value pairs to characterise the rows to delete
	 * @return int affected rows, should be 1 if ok, 0 if an error
	 */
	function delete($keys=null)
	{
		if (!is_array($keys) && (int) $keys)
		{
			$keys = array('host_id' => (int) $keys);
		}
		$host_id = is_null($keys) ? $this->data['host_id'] : $keys['host_id'];

		if (($ret = parent::delete($keys)) && $host_id)
		{
			// delete all links to example entry $host_id
			Api\Link::unlink(0, self::APP, $host_id);
		}
		return $ret;
	}
```

--> [continue to step 6](https://github.com/EGroupware/example/tree/step6) by checking out branch ```step6``` in your workingcopy
