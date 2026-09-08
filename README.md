# Example app for EGroupware development

1. Step: [a minimal app](https://github.com/EGroupware/example/tree/step1)
2. Step: [an edit dialog](https://github.com/EGroupware/example/tree/step2)
3. Step: [storing hosts in the database](https://github.com/EGroupware/example/tree/step3)
4. Step: [a list of hosts](https://github.com/EGroupware/example/tree/step4)
5. Step: [linking hosts to other entries](https://github.com/EGroupware/example/tree/step5)
6. Step: [code that runs in the browser](https://github.com/EGroupware/example/tree/step6)
7. Step: [custom fields](https://github.com/EGroupware/example/tree/step7)
8. Step: [categories](https://github.com/EGroupware/example/tree/step8)

## 9. Step: access control

Every host so far has belonged to whoever created it, and everybody has seen all of them.
This step makes that real: you see your own hosts, plus the hosts of anyone who has granted
you rights, and you can only change what you have been allowed to change.

![step9-01 A host belonging to another user, shown read-only](https://raw.githubusercontent.com/wiki/EGroupware/example/images/step9-01.png)

### src/Hooks.php

Rights are granted by one user to another, in the ACL administration. The `acl_rights` hook
is what that page offers as tick-boxes:

```php
public static function acl_rights($params)
{
	unset($params);	// not used, but part of the hook signature

	return [
		Api\Acl::READ   => 'read',
		Api\Acl::EDIT   => 'edit',
		Api\Acl::DELETE => 'delete',
	];
}
```

Offer only rights the application actually enforces. Each one here is checked below.

### src/Bo.php

`get_grants()` answers what the current user may do and to whose hosts, as
`account_id => rights`. A user always has every right on their own entries, so the list is
"me, plus anyone who has granted me something":

```php
$this->grants = $GLOBALS['egw']->acl->get_grants(self::APP);
```

```php
public function check_perms($required, array $host)
{
	return (bool)(($this->grants[$host['host_creator']] ?? 0) & $required);
}
```

**Where the filter goes matters more than what it says.** Put it in the user interface and
every new list, search or action has to remember it. Put it in `search()` and there is
nowhere left to forget it, because every read path ends up there - the list, the link
dialog, an action re-applying a stored filter:

```php
public function &search($criteria, $only_keys=true, $order_by='', $extra_cols='', $wildcard='', $empty=false,
	$op='AND', $start=false, $filter=null, $join='', $need_full_no_count=false)
{
	$filter = (array)$filter;
	$permitted = array_keys($this->grants);

	// The list offers a Creator filter of its own, so do not simply overwrite what
	// the caller asked for: narrow it instead.  Replacing it would quietly widen
	// their filter back out to everything they may see.
	$filter['host_creator'] = isset($filter['host_creator']) && $filter['host_creator'] !== '' ?
		array_intersect((array)$filter['host_creator'], $permitted) : $permitted;

	return parent::search($criteria, $only_keys, $order_by, $extra_cols, $wildcard, $empty,
		$op, $start, $filter, $join, $need_full_no_count);
}
```

`get_rows()` calls `search()`, so the list is covered without touching it. So is the link
dialog from step 5, which was written before any of this existed.

Narrowing rather than overwriting matters because the list already filters on this very
column - the Creator header from step 4. Assigning over it would leave someone who picked one
creator looking at every host they are allowed to see, with the filter apparently on. When
the two have nothing in common the result is an empty set, and an empty set here means no
rows, which is the right way for it to fail.

Reading one host by its id does not go through `search()`, so `read()` checks for itself:

```php
public function read($keys, $extra_cols='', $join='')
{
	$host = parent::read($keys, $extra_cols, $join);

	return $host && $this->check_perms(Api\Acl::READ, $host) ? $host : false;
}
```

It answers `false` for a host you may not read - deliberately the same answer as for one that
does not exist, so nobody can find out which hosts are there by asking for them an id at a
time.

Deleting checks per host, and carries on with the rest:

```php
// read() already refuses a host we may not even see
if (!($host = $this->read(['host_id' => $host_id])) ||
	!$this->check_perms(Api\Acl::DELETE, $host))
{
	return 0;
}
```

That is what makes deleting a mixed selection behave: the ones you may delete go, the ones
you may not stay, and the message counts what actually happened.

Saving refuses outright, because nothing in the interface should have offered it. It checks
the *stored* host rather than the submitted one - the dialog has no owner field, and a client
must not be able to decide who owns an entry:

```php
if (!empty($this->data['host_id']) && !$this->check_perms(Api\Acl::EDIT, $this->stored()))
{
	throw new Api\Exception\NoPermission();
}
```

### src/Ui.php

The interface's job is to not offer what will be refused. Each row is told what may be done
to it:

```php
if (!$this->bo->check_perms(Api\Acl::EDIT, $row))
{
	$row['class'] .= ' rowNoEdit';
}
if (!$this->bo->check_perms(Api\Acl::DELETE, $row))
{
	$row['class'] .= ' rowNoDelete';
}
```

and the actions say which class disables them:

```php
'disableClass' => 'rowNoEdit',
```

This is per row, not per selection, which is the point. Select ten hosts of which you may
delete seven, and *Delete* is still offered - it simply applies to the seven.

The dialog does the same thing for a single host:

```php
$readonlys = [
	'button[delete]' => empty($content['host_id']) ||
		!$this->bo->check_perms(Api\Acl::DELETE, $content),
];
// read-only rights: show the host, but let nothing be changed or saved
if (!empty($content['host_id']) && !$this->bo->check_perms(Api\Acl::EDIT, $content))
{
	$readonlys['__ALL__'] = true;
}
```

`__ALL__` makes every widget on the template read-only, and the `hideOnReadonly` from step 6
takes Save, Apply and Delete away with it.

### js/app.ts

The view dialog needs the same treatment. It already has the answer in the row it was given -
the class the list put there:

```ts
// the row carries what may be done to it, the same signal the context menu uses
const canEdit = !String(values.content.class || '').includes('rowNoEdit');
```

and offers *Edit* only when that is true. One decision, made on the server, used by the
context menu and the dialog alike.

## Try it

You need a second user, and something of theirs to look at. Create one in
*Admin > User accounts* if there is not one already, and give them run rights to Example.

1. Log in as the second user, add a host, log out.
2. As yourself, open **Example**. Their host is not in the list - you have no rights to it.
3. Try to open it anyway by putting its id in the URL:
   `…menuaction=example.EGroupware\Example\Ui.edit&host_id=<their id>`. You get an empty
   form and *Entry not found!* - the identical answer a host that really does not exist
   gives, which is the point: the two cannot be told apart.
4. In *Admin > User accounts*, use the second user's context menu to grant yourself **read**
   on their Example entries.
5. Reload the list. Their host is there now. Right-click it: *Edit* and *Delete* are greyed
   out, *View* is not.
6. Add a throwaway host of your own, then select it **and** theirs and press *Delete*. The
   action is offered because part of the selection allows it, only your own goes, and the
   message counts one.

### Troubleshooting

**The list is empty** - you have no grants at all, which should not happen: everyone has
rights to their own entries. Check `host_creator` is actually being set on save.

**"No permission to access application Example"** - that is run rights, not this step. They
live in the same ACL table but under the location `run`; *Admin > User groups* grants them.

**Actions are greyed out on rows you own** - `host_creator` does not match your account id,
or `check_perms()` is being given a row that has not got that column.

**A user can still see entries after a grant is removed** - grants are cached per request;
reload rather than trusting a stale page.

**Filtering by Creator shows more than it should** - the permission filter is assigning over
the column filter instead of intersecting with it.

--> continue to [step 10](https://github.com/EGroupware/example/tree/step10), which pushes
changes to other users' open lists
