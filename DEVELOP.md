# Build a working plugin zip

The usual way to get a working version of this plugin, is to headover to the [Stud.IP marketplace](https://develop.studip.de/studip/plugins.php/pluginmarket/presenting/details/dfd73b3d67c627be493536c1ae0e27c9). If you need your own installable version of the Stud.IP-Opencast-plugin, follow these steps:

1. Check out the plugin to your local machine:
`git clone https://github.com/elan-ev/studip-opencast-plugin.git`

2. Make sure you have the latest [npm](https://docs.npmjs.com/try-the-latest-stable-version-of-npm) installed then change to the folder `studip-opencast-plugin` and run:
`npm run zip`

3. The production ready zip while will be located in the same folder and look something like
`Opencast-VX.X.zip`

# How to translate text strings in Opencast

The plugin uses gettext to translate the text from german to other languages.

## Extract

Currently there is only the German and English translation present in this plugin. To generate the translation files (.po & .mo) one can simply run the `translate.sh` or use `npm run translate`. The latter is done automatically when creating a plugin zip via `npm run zip`.


## Translate

After extracting all present translation strings from the current code with the above commands, you can either directly edit `locale/en/LC_MESSAGES/opencast.po` or better use a tool like [Poedit](https://poedit.net/).

The workflow would then be:
```
npm run translate
```

Then, edit the po-file, afterwards another

```
npm run translate
```

This will create the translation files for the vue-frontend and the php-backend alike. Take note that a web server restart might be necessary for php to load the modified translations!

## Compile

After you are done entering translation strings, run one of the extract commands once more to regenerate the `opencast.mo` and the `translations.json`, which are the files used by the translation mechanic in Stud.IP and henceforth this plugin.

# Codeception Tests

To run the api tests, one needs to have Stud.IP installation at hand with a default test course called "Test Lehrveranstaltung" with the id of "a07535cf2f8a72df33c12ddfa4b53dde" and currently 3 users accounts with the following credentials:

| Username         | Password         |
| ---------------- | ---------------- |
| apitester        | apitester        |
| apitester_autor1 | apitester_autor1 |
| apitester_autor2 | apitester_autor2 |

NOTE: the default course for test systems is available via applying the default db sql query under studip root directory: 'db/studip_demo_data.sql'.

Furthermore the URL of the Stud.IP installation needs to be configured in de `codeception.yml`

Make sure that the composer and npm packages are up to date with the composer.json and package.json.

To run the tests call `npm run tests`.

Under `docs` is the db scheme and the REST-API-definition

# Changes from using Vue 2 to Vue 3

## Filters

Before:  
`{{ file.size | filesize }}`

Now:  
`{{ $filters.filesize(file.size) }}`

## Translations

Before:  
`<b v-translate>Größe:</b>`  
`<translate>Größe:</translate>`

Now:  
`<b>{{ $gettext('Größe:') }}</b>`  
`{{ $gettext('Größe:') }}`

## vue-portal / MountingPortal

The functionalities of `vue-portal` are integrated into Vue 3 and is called `Teleport`.

Before:  
`<MountingPortal mountTo="#actions" name="actions">[...]</MountingPortal>`

Now:  
`<Teleport to="#actions" name="actions">[...]</Teleport>`

## Injected global vars

```
window.OpencastPlugin = {
    API_URL    : ...
    CID        : ...
    ICON_URL   : ...
    ASSETS_URL : ...
    PLUGIN_ASSET_URL : ...
};
```

Before:  
`console.log(API_URL)`

Now:  
`console.log(window.OpencastPlugin.API_URL)`

# Changes in permission checking (16.10.2023)

New design paradigm for checking permissions in single routes: Based on the way permission checking is handled in the Stud.IP core (f.e.: https://gitlab.studip.de/studip/studip/-/blob/abb58e943a83665f8f9db36a6d4d1ecd64c5970f/lib/classes/JsonApi/Routes/Wiki/Authority.php ), there shall be a class called `Authority.php` in every directory under `lib/Routes`. In `lib/Routes/Playlist/` there is such an authority-file now which can be used as reference. Basically the class shall only consits of static methods of the style `canUserDoY` which return a boolean.

# Renaming of plugin

## Things to do as a developer

To get things up and running again after the renaming to `OpencastV3`, developers can follow this list (the order of the steps IS important!):
1. `git pull`
2. `npm run build`
3. Run `install/install.sql` on the DB
4. Run plugin migrations
5. Renamen plugin folder from `OpenCast` to `OpencastV3`
6. Change settings in Opencast config for the Stud.IP user provider and change `opencast` to `opencastv3`
