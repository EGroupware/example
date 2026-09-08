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
10. Step: [telling everyone else](https://github.com/EGroupware/example/tree/step10)
11. Step: [settings for the whole installation](https://github.com/EGroupware/example/tree/step11)

## 12. Step: settings each user chooses

The last step was a rule for everybody. This one is a choice each user makes for themselves:
which category a host they add starts in.

![step12-01 The app's preferences](https://raw.githubusercontent.com/wiki/EGroupware/example/images/step12-01.png)

### src/Hooks.php

Preferences are declared rather than drawn. The `settings` hook returns what the application
offers, and the preferences application builds the page and stores what is chosen:

```php
public static function settings()
{
	return [
		'default_cat' => [
			'type'   => 'select-cat',
			'label'  => 'Default category for new hosts',
			'name'   => 'default_cat',
			'values' => [],
			'help'   => 'Preselected when you add a host.',
			'xmlrpc' => true,
			'admin'  => false,
		],
	];
}
```

`type` picks the widget - `select-cat` gives the category selector, and `values` stays empty
because that widget fills itself. `help` is the text under the setting, and it is worth
writing: a preference nobody understands is a preference nobody changes.

`admin => false` means an ordinary user may set it; `true` would restrict it to an
administrator editing somebody's preferences for them.

Declared like every other hook:

```php
$setup_info['example']['hooks']['settings'] = 'EGroupware\\Example\\Hooks::settings';
```

### src/Ui.php

Reading it back is one line, and the reason it is only one line is the interesting part:

```php
// Preselect the category this user chose.  What arrives here is already
// the effective preference, so there is nothing to fall back to: the
// preference system has resolved user, group, installation default and
// anything an administrator forced, before we are asked.
$content['cat_id'] = $GLOBALS['egw_info']['user']['preferences'][Bo::APP]['default_cat'] ?? null;
```

There is no fallback to write, and writing one would be a mistake. A preference is resolved
before an application sees it, in this order:

| | |
|---|---|
| **forced** | an administrator has decided nobody may choose - wins outright |
| **session** | set for this login only, eg. a language chosen at sign-in |
| **user** | what this user chose on the preferences page |
| **group** | a default for a group they belong to |
| **default** | the installation-wide default |

So an administrator who wants every user to start in *Production* does not need anything from
us: they set that as a **default preference**, under *Preferences > Default preferences for
Example*, and users who have not chosen otherwise get it. If they want it settled and not
negotiable, they force it instead, and the preferences page stops offering the choice.

That is the distinction worth carrying away from the last two steps. A rule about the
installation is configuration, which the application reads and enforces. A default a user may
override is a preference, and the preference system already knows how to layer those - so an
application that reaches for `Api\Config` to default a user's choice is doing a worse job of
something the framework does properly.

## Try it

You need two categories from step 8.

1. Open **Preferences > Example**. There is *Default category for new hosts*, set to
   *Use default*.
2. Set it to `Staging` and save.
3. In Example, click **Add**. The category is already `Staging`.
4. Set the preference back to *Use default*, and add another host: no category.
5. Now as an administrator, go to *Preferences > Default preferences for Example* and set
   `Production` there. Add a host as a user who has made no choice of their own - it starts
   in `Production`, with no code in this application involved.

### Troubleshooting

**Example has no preferences page** - the `settings` hook is declared but not registered.
*Admin > Clear cache/register hooks*. If it survives that, the server is still holding the
old hook list: restart php-fpm.

**The setting appears but changing it has no effect** - the name in the returned array, its
`name` key, and the key read from `$GLOBALS['egw_info']['user']['preferences']` all have to
be the same string.

**The category selector is empty** - there are no categories for this application yet;
step 8 covers making them.

**A stored preference points at a category that no longer exists** - the selector shows
nothing selected and the value simply does not apply. Deleting a category does not go and
tidy up everybody's preferences.

That is the last step. The finished application is on
[master](https://github.com/EGroupware/example), which is where to start reading if you want
the whole thing at once rather than a step at a time.
