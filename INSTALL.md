# Stud.IP Opencast Plugin

The Opencast plugin is now using LTI to present the videos and enforce permissions. You need to configure your Opencast system to make this plugin work.

## Configure Opencast

Refer to the Opencast documentation for instructions on how to configure your version of the Opencast system (f.e. https://docs.opencast.org/r/8.x/admin/#modules/ltimodule)

Normally this boils down to the following two changes / additions in the config files:

1. Edit `etc/security/mh_default_org.xml` and make sure the following setting is enabled:
```
<ref bean="oauthProtectedResourceFilter" />
```

2. Edit / Create `etc/org.opencastproject.kernel.security.OAuthConsumerDetailsService.cfg` and configure / add the following settings:
```
oauth.consumer.name.1=CONSUMERNAME
oauth.consumer.key.1=CONSUMERKEY
oauth.consumer.secret.1=CONSUMERSECRET
```

3. Edit `etc/org.opencastproject.security.lti.LtiLaunchAuthenticationHandler.cfg ``
enable:
```
lti.create_jpa_user_reference=true
lti.custom_role_name=Instructor
lti.custom_roles=ROLE_STUDIO,ROLE_ADMIN_UI,ROLE_UI_EVENTS_DETAILS_COMMENTS_CREATE,ROLE_UI_EVENTS_DETAILS_COMMENTS_DELETE,ROLE_UI_EVENTS_DETAILS_COMMENTS_EDIT,ROLE_UI_EVENTS_DETAILS_COMMENTS_REPLY,ROLE_UI_EVENTS_DETAILS_COMMENTS_RESOLVE,ROLE_UI_EVENTS_DETAILS_COMMENTS_VIEW,ROLE_UI_EVENTS_DETAILS_MEDIA_VIEW,ROLE_UI_EVENTS_DETAILS_METADATA_EDIT,ROLE_UI_EVENTS_DETAILS_METADATA_VIEW,ROLE_UI_EVENTS_DETAILS_VIEW,ROLE_UI_EVENTS_EDITOR_EDIT,ROLE_UI_EVENTS_EDITOR_VIEW,ROLE_CAPTURE_AGENT
```

After that, restart Opencast.



## Opencast - CORS

If your Stud.IP system resides on a different domain than your Opencast, you need to configure Opencasts Nginx to allow CORS requests. For an explanation why this is necessary and examples how to achieve this, take a look at:
* https://gist.github.com/iki/1247cd182acd1aa3ee4876acb7263def#file-nginx-cors-proxy-conf
* https://developer.mozilla.org/de/docs/Web/HTTP/CORS

Example (nginx):

http context:
```
# CORS preparations: Allow CORS requests from some hosts (1)
# for plugin integration.
map $http_origin $cors_ok {
    default                              0;
    https://dev.studip.example.com       1;
    https://studip.example.com           1;
    https://ilias.example.com            1;
}

map $cors_ok $cors_origin {
    default                              '';
    1                                    $http_origin;
}

map $cors_ok $cors_credentials {
    default                              '';
    1                                    true;
}
```

location context:
```
# CAUTION: There could be several add_header directives.
# These directives are inherited from the previous level
# if and only if there are no add_header directives defined
# on the current level.
# -------------------------
# Allow some CORS access
# https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
add_header Access-Control-Allow-Origin       '$cors_origin';
add_header Access-Control-Allow-Credentials  '$cors_credentials';
```

## Opencast Workflows

This plugin assumes your

* republish workflow's ID is [`republish-metadata`](https://github.com/elan-ev/studip-opencast-plugin/issues/196)
* retract workflow's ID is `retract`

## Credentials for Opencast

This plugin requires a front end user account to connect to Opencast; create one or use an existing one.
`matterhorn_system_account` and `opencast_system_account` ceased working!

The frontend user needs to following roles:
`ROLE_ADMIN`, `ROLE_ADMIN_UI`

## Configure Stud.IP

Install the most recent version of this plugin, make sure that all migrations worked properly.

After that go to "Admin" -> "System" -> "Opencast settings" and enter the URL and credentials for the Opencast system.
Make sure you enter the LTI credentials under "Additional settings".
If everything worked you can now start using the plugin in seminars.
