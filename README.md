# Example app for EGroupware development

A small, complete EGroupware application. It keeps a list of hosts - a name, a description,
a category, whatever custom fields an administrator adds - and it does that the way a real
application does: stored in its own table, listed in a nextmatch, linkable from other
applications, access-controlled, and configurable.

Two ways to use it:

- **Read it a step at a time.** Twelve branches build it from nothing, each with a README
  explaining what was added and why. Start at
  [step 1](https://github.com/EGroupware/example/tree/step1).
- **Copy it and start working.** This branch is the finished application. Rename it, rename
  the table, delete what you do not need.

![master-01 The finished application](https://raw.githubusercontent.com/wiki/EGroupware/example/images/master-01.png)

## What is in it

```
example/
├── setup/
│   ├── setup.inc.php            the file that makes a directory an application
│   ├── tables_current.inc.php   the schema a fresh install gets
│   ├── tables_update.inc.php    one function per version, to bring older installs forward
│   └── default_records.inc.php  records created at install time
├── src/
│   ├── Ui.php                   what the browser reaches: the list, the dialog, actions
│   ├── Bo.php                   storage and the rules over it, extending Api\Storage
│   └── Hooks.php                what this app answers when the rest of EGroupware asks
├── templates/default/
│   ├── index.xet                the list
│   ├── edit.xet                 the dialog
│   ├── config.xet               the administrator's settings page
│   ├── rows.css                 styling for the list's rows
│   └── images/navbar.svg        the navbar icon
├── js/app.ts                    the app's client-side class, built to js/app.min.js
└── lang/egw_en.lang             every phrase the user reads
```

`js/app.min.js` is a build artifact and is not committed. Run `npm run build` in the
EGroupware root after checking out, or the client-side code does nothing.

## Installing it

The application is a directory beside `api/` in an EGroupware installation.

1. Clone it into place as `example/`.
2. In `<your-egroupware>/setup/`, log in with the configuration user and install *example*
   from **Manage applications**.
3. In *Admin > User groups*, tick **Example** on a group's *Applications* tab so its members
   may use it.
4. Run `npm run build` in the EGroupware root.

[Step 1](https://github.com/EGroupware/example/tree/step1) covers all of this in more detail,
and what each file is for.

## Using it as a starting point

The quickest way to a new application is to copy this one and work through it:

- rename the directory, and `$setup_info['<app>']` in `setup/setup.inc.php`
- keep `&ajax=true` at the end of `index` in `setup/setup.inc.php`: without it the app is
  loaded in an iframe, and the header, the sidebox, the browser history and printing all
  misbehave. The entry point is stored at install time, so on an installation that already
  exists raise the version and add an update function, as step 2 does
- rename the namespace in `src/`, and the class names EGroupware is told about
- rename `egw_example` and its `host_` columns in `tables_current.inc.php`, and start the
  version chain again from scratch in `tables_update.inc.php`
- keep only the hooks you answer: this app declares `search_link`, `admin`, `categories`,
  `acl_rights` and `settings`
- replace the phrases in `lang/egw_en.lang`

The [developer](https://github.com/EGroupware/developer) app's **DB-Tools** writes the two
schema files for you, and its **TranslationTools** manages the language files.
