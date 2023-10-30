# Stud.IP Opencast Plugin

The Opencast plugin is using LTI and the Stud.IP User Provider to present the videos and enforce permissions. You need to configure your Opencast system to make this plugin work.

## Requirements

For this plugin to work, you need:
- Stud.IP >= Version 4.6
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
lti.custom_roles=ROLE_STUDIO,ROLE_UI_EVENTS_DETAILS_COMMENTS_CREATE,ROLE_UI_EVENTS_DETAILS_COMMENTS_DELETE,ROLE_UI_EVENTS_DETAILS_COMMENTS_EDIT,ROLE_UI_EVENTS_DETAILS_COMMENTS_REPLY,ROLE_UI_EVENTS_DETAILS_COMMENTS_RESOLVE,ROLE_UI_EVENTS_DETAILS_COMMENTS_VIEW,ROLE_UI_EVENTS_DETAILS_MEDIA_VIEW,ROLE_UI_EVENTS_DETAILS_METADATA_EDIT,ROLE_UI_EVENTS_DETAILS_METADATA_VIEW,ROLE_UI_EVENTS_DETAILS_VIEW,ROLE_UI_EVENTS_EDITOR_EDIT,ROLE_UI_EVENTS_EDITOR_VIEW,ROLE_CAPTURE_AGENT,ROLE_API_EVENTS_TRACK_EDIT
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
org.opencastproject.userdirectory.studip.url=http://studip.me/studip/4.6/plugins.php/opencast/api/
org.opencastproject.userdirectory.studip.token=mytoken1234abcdef

# The maximum number of users to cache
# Default: 1000
#org.opencastproject.userdirectory.studip.cache.size=1000

# The maximum number of minutes to cache a user
# Default: 60
org.opencastproject.userdirectory.studip.cache.expiration=1
```

Make sure to change the token and add that token to the Opencast config in Stud.IP. Furthermore configure the Opencast-Plugin in Stud.IP to have the `nobody` role for it to work.

6. Add role `STUDIP` in Opencast

In the Opencast Admin UI, go to Organisation -> Groups and add a group named `STUDIP`

----

After all that, restart Opencast.


## Opencast - CORS

If your Stud.IP system resides on a different (sub-)domain than your Opencast, you need to configure Opencasts Nginx to allow CORS requests. For an explanation why this is necessary and examples how to achieve this, take a look at:
* https://developer.mozilla.org/de/docs/Web/HTTP/CORS

For a good example for an nginx.conf, look at:
https://github.com/elan-ev/opencast_nginx/blob/main/templates/nginx.conf

## Opencast Workflows

This plugin assumes your

* republish workflow's ID is [`republish-metadata`](https://github.com/elan-ev/studip-opencast-plugin/issues/196)
* retract workflow's ID is `retract`

## Credentials for Opencast

This plugin requires an user account to connect to Opencast; create one or use an existing one.
`matterhorn_system_account` and `opencast_system_account` ceased working!

The user needs to following role:
`ROLE_ADMIN`

## Configure Stud.IP

Install the most recent version of this plugin, make sure that all migrations worked properly.

After that go to "Admin" -> "System" -> "Opencast settings" and add a new server. Enter the URL and all the credentials for the Opencast system. Also make sure to assign to `nobody`-role to the plugin as well as entering the API-token for the user provider (see above).
If everything worked you can now start using the plugin!

Further help can be found under:
https://hilfe.studip.de/help/4.0/de/Basis/OpencastV3Administration