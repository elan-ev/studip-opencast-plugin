# Table of Contents

1. [Installation of Stud.IP Opencast Plugin](#installation-of-studip-opencast-plugin)
   1. [Requirements](#requirements)
   2. [Configure Opencast](#configure-opencast)
   3. [Opencast - CORS](#opencast---cors)
   4. [Opencast Workflows](#opencast-workflows)
   5. [Credentials for Opencast](#credentials-for-opencast)
   6. [Configure Stud.IP](#configure-studip)
   7. [Caveats](#caveats)

2. [Migrating from older versions of the plugin](#migrating-from-older-versions-of-the-plugin)
   1. [Steps to follow for an upgrade](#steps-to-follow-for-an-upgrade)

3. [Feedback and Help](#feedback-and-help)

# Installation of Stud.IP Opencast Plugin

The Opencast plugin is using LTI and the Stud.IP User Provider to present the videos and enforce permissions. You need to configure your Opencast system to make this plugin work.

Make sure you configure the admin nodes as well as all presentation nodes, or the plugin will not work correctly!

## Requirements

For this plugin to work, you need:
- Stud.IP >= Version 5.1
- Opencast >= Version 13.1

## Configure Opencast

Refer to the [Opencast documentation](https://docs.opencast.org) for instructions on how to configure your version of the Opencast system.

Normally this boils down to the following two changes / additions in the config files:

1. Edit `/etc/opencast/security/mh_default_org.xml` and make sure the following setting is enabled:
```
<ref bean="oauthProtectedResourceFilter" />
```

2. Edit / Create `/etc/opencast/org.opencastproject.kernel.security.OAuthConsumerDetailsService.cfg` and configure / add the following settings:
```
oauth.consumer.name.1=CONSUMERNAME
oauth.consumer.key.1=CONSUMERKEY
oauth.consumer.secret.1=CONSUMERSECRET
```

3. Edit `/etc/opencast/org.opencastproject.security.lti.LtiLaunchAuthenticationHandler.cfg` and set:
```
lti.create_jpa_user_reference=false

lti.custom_role_name=Instructor
lti.custom_roles=ROLE_STUDIO
```

The reference entry in Opencast is neither needed nor wanted, since we are using the Stud.IP user provider. If some actions like ingesting seem to file, when this options is disabled, make sure that the user provider is working correctly. One way to check this, is to call https://opencast.me/info/me.json after opening the videos page in Stud.IP and look for `provider` -> `studip` has to be listed there.

4. Edit `/etc/opencast/org.opencastproject.plugin.impl.PluginManagerImpl.cfg` and enable:

```
	...
    opencast-plugin-userdirectory-studip = on
	...
```

5. Edit `/etc/opencast/org.opencastproject.userdirectory.studip-default.cfg`

```
# Studip UserDirectoryProvider configuration

# This is an an optional service which is not enabled by default. To enable it,
# edit etc/org.apache.karaf.features.cfg and add opencast-studip to the featuresBoot option.

# The organization for this provider
org.opencastproject.userdirectory.studip.org=mh_default_org

# The URL and token for the Studip REST webservice
org.opencastproject.userdirectory.studip.url=http://studip.me/studip/4.6/plugins.php/opencastv3/api/
org.opencastproject.userdirectory.studip.token=mytoken1234abcdef

# The maximum number of users to cache
# Default: 1000
#org.opencastproject.userdirectory.studip.cache.size=1000

# The maximum number of minutes to cache a user
# Default: 60
org.opencastproject.userdirectory.studip.cache.expiration=1
```

Make sure to change the token and add that token to the Opencast config in Stud.IP. Furthermore configure the Opencast-Plugin in Stud.IP to have the `nobody` role for it to work.

6. Recommended for Opencast Version >= 17, edit `/etc/opencast/custom.properties` and enable:

```
# Allow episode ID based access control via roles.
# If activated, users with a role like ROLE_EPISODE_<ID>_<ACTION> will have access to the episode with the given
# identifier, without this having to be explicitly stated in the ACL attached to the episode.
#
# For example, ROLE_EPISODE_872dc4ec-ca8a-4e12-8dac-ce99784d6d29_READ will allow the user to get read access to episode
# 872dc4ec-ca8a-4e12-8dac-ce99784d6d29.
#
# To make this work for the Admin UI and External API, the Elasticsearch index needs to be updated with modified
# ACLs. You can achieve this by calling the /index/rebuild/AssetManager/ACL endpoint AFTER activating this feature.
# The endpoint will reindex only event ACLs.
#
# Default: false
org.opencastproject.episode.id.role.access = true
```

This feature can then be activated in the plugin's Opencast settings (see below).

7. Add role `STUDIP` in Opencast

In the Opencast Admin UI, go to Organisation -> Groups and add a group named `STUDIP`.

> :warning: **If you do not add this group, media uploads WILL fail!**

----

After all that, restart Opencast.


## Opencast - CORS

If your Stud.IP system resides on a different (sub-)domain than your Opencast, you need to configure Opencasts Nginx to allow CORS requests. For an explanation why this is necessary and examples how to achieve this, take a look at:
* https://developer.mozilla.org/de/docs/Web/HTTP/CORS

For a good example for an nginx.conf, look at:
https://github.com/elan-ev/opencast_nginx/blob/main/templates/nginx.conf

## Opencast Workflows

You can configure which workflow is used for different actions. They can be edited on the plugins admin configuration page. The following table gives an overview of the workflows and their usage / meaning.

| Type of workflow | Details      | Allowed Workflow-Tags |
| ---------------- | ------------ | --------------------- |
| schedule         | Used for videos which are planned by the scheduling feature  | schedule			  |
| upload           | Workflow run after uploading a video                         | upload                |
| studio           | Workflow run after creating a video OC Studio                | upload                |
| delete           | Workflow run when a video shall be deleted permanently       | delete                |

## Credentials for Opencast

This plugin requires an user account to connect to Opencast; create one or use an existing one.
`matterhorn_system_account` and `opencast_system_account` ceased working!

The user needs to following role:
`ROLE_ADMIN`

## Configure Stud.IP

Install the most recent version of this plugin, make sure that all migrations worked properly.

After that go to "Admin" -> "System" -> "Opencast settings" and add a new server. Enter the URL and all the credentials for the Opencast system. Also make sure to assign to `nobody`-role to the plugin as well as entering the API-token for the user provider (see above).

If you have Opencast Version 17 and the recommended feature enabled, you can enable the role based access via event id. This function removes the need for the plugin to set video ID roles on the videos, which reduces the number of roles on the videos and simplifies the migration to version 3. Without this function, the ACLs of all OC videos must be updated during migration.

If everything worked you can now start using the plugin!

Further help can be found under:
https://hilfe.studip.de/help/4.0/de/Basis/OpencastV3Administration

## Caveats

Stud.IP root users currently get `ROLE_ADMIN` and are therefore factual Opencast admins!

# Migrating from older versions of the plugin

The plugin has been renamed to `OpencastV3`. When updating from V2 or and older V3, the plugin takes steps to rename things to make it work for the new plugin versions. After these steps, the old version of the plugin will remain in your Stud.IP installation in a deactivated state with a DB version of `0` and can be safely deinstalled in that case. Make sure the plugin is really showing a `0` before uninstalling it, otherwise bad things WILL happen!

Another thing to note is, that the URL in the user provider is changed! See the section about the user provider for correct configuration!

If you are migrating from version 2.x of this plugin, you have two options:

1. If you are using Opencast 17 or newer, activate role based event access in Opencast and afterwards in the server settings of the plugin. See https://docs.opencast.org/r/17.x/admin/#configuration/episode-id-roles/#episode-id-roles for how to activate that in Opencast

2. Alternatively you can use `tools/pre_migrate_acls.php` in the weeks before installing the new version to shift workload on opencast before the installation. The Cronjobs should take care of anything missing nonetheless, so this step is useful but not absolutely necessary. Just keep in mind, that ALL videos in your Opencast system will have to run through a `republish-metadata`-workflow in order for all plugin functions to work correctly!

## Steps to follow for an upgrade:
- Deactivate the plugin version 2
- Install or register existing plugin V3 -> Migrations are run
- Deinstall the old plugin if has schema version 0
- Check the "Opencast settings" page, add the token from the Stud.IP User provider
- If you are using Opencast 16 or above, migrate the playlists to Opencast by clicking the link in the messagebox on the settings page
- In the Opencast server settings configure the correct workflows
- Enable the cronjobs and at least run every cronjob once, starting with the "Discover new videos" one

# Feedback and Help

You can join our Matrix channel if you have feedback or need help:
https://matrix.to/#/#studip-opencast-plugin:uni-osnabrueck.de
