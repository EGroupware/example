# Example app for EGroupware development

## 1. Step: a minimal app

Already know EGroupware and just want a working app to copy?
[master](https://github.com/EGroupware/example) is the finished thing.

An EGroupware application is a directory beside `api/`. Two files are what make it an
application - one telling setup it exists, one holding the code - and an icon and a
translation belong there from the start. This step builds the smallest one that works: it
installs, appears in the navbar, and says hello.

![step1-01 The app open, reading Hello World](https://raw.githubusercontent.com/wiki/EGroupware/example/images/step1-01.png)

### setup/setup.inc.php

This file is what makes a directory an application. Setup reads it to find out the app
exists, what it is called, and what to open when someone clicks it:

```php
$setup_info['example']['name']      = 'example';
$setup_info['example']['version']   = '26.1';
$setup_info['example']['app_order'] = 5;
$setup_info['example']['enable']    = 1;
$setup_info['example']['index']     = 'example.EGroupware\\Example\\Ui.index';
```

`name` has to match the directory. `version` is the app's own, and matters more than it
looks: setup compares it with what is installed to decide whether anything needs doing, so
every later step that changes the database changes this too.

`index` is the entry point, written as `<app>.<class>.<method>`. The class name is
fully qualified, which is why the backslashes are doubled inside a double-quoted string.

`app_order` is where it sits in the navbar, and `enable` = 1 means an ordinary application
rather than a hidden or admin-only one.

The rest of the file is who wrote it and what it needs:

```php
/* Dependencies for this app to work */
$setup_info['example']['depends'][] = array(
	'appname'  => 'api',
	'versions' => array('26.1')
);
```

### src/Ui.php

Classes live under `src/`, in the namespace `EGroupware\<App>`, and are found by that
name - no registration, no include:

```php
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

	public function index(?array $content=null)
	{
		echo "Hello World :)";
	}
}
```

`$public_functions` is the one piece of security to understand before writing anything
else. EGroupware will only call a method from a URL if it is listed there. A method that
is `public` in the PHP sense but absent from that list cannot be reached by editing the
address bar - which is exactly what you want for anything a template calls internally.

`echo` is not how a real page is built - the next step replaces it with a template - but it
proves the whole chain works: setup found the app, the framework found the class, and the
method ran.

### templates/default/images/navbar.svg

The navbar icon. An application without one gets a placeholder, which is fine while
developing and looks unfinished the moment anyone else sees it.

### lang/egw_en.lang

One line, and it is what gives the application the name people see:

```
example	common	en	Example
```

Tab-separated: the phrase, the application it belongs to, the language, and the translation.
`common` means any application may use it, which is how the navbar labels ours. `egw_de.lang`
gives `Beispiel` for the same phrase. Step 2 covers the format properly, once there are
phrases of our own to add.

## Installing it

An application has to be installed before it can be used, and then granted to somebody.

1. Open `<your-egroupware>/setup/`. Two logins are offered: use the upper one,
   **Setup / config admin login**, with the configuration user and password. The
   *Header admin login* below it is for editing the configuration file itself and has
   its own separate password.

   ![step1-02 The Setup login](https://raw.githubusercontent.com/wiki/EGroupware/example/images/step1-02.png)

2. Go to **Manage applications** and find *example* in the list. Tick its box in the
   *Install* column and submit. Afterwards the row reads `example-OK`, with the same
   current and available version - which is how you can tell later whether an app needs
   an update.

   ![step1-03 Install the app in Setup](https://raw.githubusercontent.com/wiki/EGroupware/example/images/step1-03.png)

3. Back in EGroupware, go to *Admin > User groups*, open a group - *Default* is fine - and
   tick **Example** on its *Applications* tab. That is the list of applications the group's
   members may use.  (Setup lists applications by name, `example`; everywhere a user sees
   one it is the title, *Example* - the translation above.)

   ![step1-04 Admin's User groups list](https://raw.githubusercontent.com/wiki/EGroupware/example/images/step1-04.png)

   ![step1-05 Granting run rights to a group](https://raw.githubusercontent.com/wiki/EGroupware/example/images/step1-05.png)

Installing and granting are separate on purpose: an installed application that nobody has
rights to is invisible, which is how an administrator stages something before letting
people at it.

## Try it

1. Log in and look at the navbar: **Example** is there.
2. Click it. The page says `Hello World :)`.

### Troubleshooting

**Example is missing from the navbar,** even after logging back in - use *Admin > Clear
cache/register hooks*. The list of installed applications is cached server-side, and that
cache outlives your session, so a new login alone will not pick the app up.

**Example is in the navbar but clicking it gives an error** - the `index` entry point does
not resolve. Check the class name in `setup.inc.php` matches the namespace and class in
`src/Ui.php`, and that the method is in `$public_functions`.

**Setup does not offer the app at all** - the directory name and `$setup_info` key have to
match, and `setup/setup.inc.php` has to be readable by the webserver.

--> continue to [step 2](https://github.com/EGroupware/example/tree/step2), which replaces
the echo with a real dialog
