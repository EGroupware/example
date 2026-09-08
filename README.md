# Example app for EGroupware development

1. Step: [a minimal app](https://github.com/EGroupware/example/tree/step1)
2. Step: [an edit dialog](https://github.com/EGroupware/example/tree/step2)
3. Step: [storing hosts in the database](https://github.com/EGroupware/example/tree/step3)
4. Step: [a list of hosts](https://github.com/EGroupware/example/tree/step4)
5. Step: [linking hosts to other entries](https://github.com/EGroupware/example/tree/step5)
6. Step: [code that runs in the browser](https://github.com/EGroupware/example/tree/step6)
7. Step: [custom fields](https://github.com/EGroupware/example/tree/step7)
8. Step: [categories](https://github.com/EGroupware/example/tree/step8)
9. Step: [access control](https://github.com/EGroupware/example/tree/step9)

## 10. Step: telling everyone else

Saving a host updates your own list, because your browser asked for the change. Everyone
else's list goes stale until they reload. This step fixes that: a change is announced, and
open lists update themselves.

![step10-01 A host appearing in a second user's list without a reload](https://raw.githubusercontent.com/wiki/EGroupware/example/images/step10-01.png)

### src/Bo.php

One method, called after a host is written or removed:

```php
protected function push($type, array $host)
{
	(new Api\Json\Push(Api\Json\Push::ALL))->apply('egw.push', [[
		'app'        => self::APP,
		'id'         => $host['host_id'],
		'type'       => $type,
		'acl'        => ['host_creator' => $host['host_creator']],
		'account_id' => $GLOBALS['egw_info']['user']['account_id'],
	]]);
}
```

`Push::ALL` sends to every logged-in user. That sounds wasteful, and it is deliberate:
working out server-side who is allowed to see a host, for everyone currently online, would
cost more than sending a short message and letting each browser decide. Which is why the
message carries no host data - only what is needed to make that decision, and an id to
fetch with if the answer is yes.

It goes at the end of a successful write, next to the link handling:

```php
if (($error = parent::save(null, $extra_where)) == 0)
{
	// A new host cannot be linked before it exists, so the dialog holds its
	// links until now and hands them over as link_to[to_id].
	if (!empty($keys['link_to']['to_id']) && is_array($keys['link_to']['to_id']))
	{
		Api\Link::link(self::APP, $this->data['host_id'], $keys['link_to']['to_id']);
	}
	// $stored fills in what the dialog did not send - host_creator above all,
	// since that is what the receiving browsers decide on
	$this->push($is_new ? 'add' : 'edit', $this->data + $stored);
}
```

That `$this->data + $stored` is worth a moment. `$this->data` holds what was submitted, and
the dialog only submits what it shows - there is no owner field on it, so `host_creator` is
not in there. Sending the message from the submitted data alone produces one with an empty
`acl`, which every receiver would then reject. The stored host supplies what the form did
not.

Deleting announces itself the same way, from the host it just read to check permissions:

```php
$this->push('delete', $host);
```

### js/app.ts

The receiving end is one property:

```ts
/**
 * Row fields holding an account whose grants decide whether a pushed change
 * is any of our business
 *
 * @var string[]
 */
protected push_grant_fields = ['host_creator'];
```

`EgwApp` does the rest. It takes the account named in the message's `acl`, compares it with
the grants this user holds, and - when the change is one this user could care about - hands
it to the list, which refreshes that single row rather than reloading everything.

Nothing else is needed. An application only writes its own `push()` method when it wants
different behaviour from that.

### One row, not the whole list

Refreshing a single row is not an ordinary listing, and it does not reach `get_rows()`
looking like one: it arrives with `col_filter` narrowed to the row being refreshed, and
`$query['csv_export']` set to `refresh`. That is the flag
[step 4](https://github.com/EGroupware/example/tree/step4)'s `get_rows()` tests before it
stores anything:

```php
if (empty($query['csv_export']))
{
	Api\Cache::setSession(Bo::APP, 'index', $query);
}
```

Store that one and the narrowed filter becomes the list's saved state: every later load
answers with just that row, and reloading the page does not clear it, because the stored
query is precisely what a reload restores. A CSV export arrives the same way and must be
skipped for the same reason.

## Try it

You need two browser windows that are not the same window - a second browser, or a private
window. The same user in both is fine: a push goes to every logged-in client, not only to
other people.

1. Open **Example** in both, side by side.
2. In the first, add a host.
3. It appears in the second, without touching it.
4. Change its name in one window and watch the row update in the other.
5. Delete it, and watch the row go.

Push needs a push server to be configured for the installation. Without one, EGroupware
falls back to polling, and the same updates arrive - just not as promptly.

### Troubleshooting

**Nothing arrives** - check the installation actually has push configured before suspecting
this code; *Admin > Test Push* answers that in one click.

**The window you saved in updates, the other does not** - the window that submitted the form
updates for that reason alone, push or no push. It tells you nothing about whether the push
worked; only the other window does.

**Everything arrives, but nothing appears in the list** - the id in the message has to be the
one the list uses for a row, the `row_id` from step 4.

**The list drops to just the pushed row, and a reload does not fix it** - `get_rows()` stored
the refresh query as the list's state. That is what the `csv_export` test above prevents.

--> continue to [step 11](https://github.com/EGroupware/example/tree/step11), which adds
site-wide configuration
