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

After that, restart Opencast.

## Opencast - CORS

If your Stud.IP system resides on a different domain than your Opencast, you need to configure Opencasts Nginx to allow CORS requests. For an explanation why this is necessary and examples how to achieve this, take a look at:
* https://gist.github.com/iki/1247cd182acd1aa3ee4876acb7263def#file-nginx-cors-proxy-conf
* https://developer.mozilla.org/de/docs/Web/HTTP/CORS

## Opencast Workflows

This plugin assumes your republish workflow [has the ID `republish-metadata`](https://github.com/elan-ev/studip-opencast-plugin/issues/196).

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
