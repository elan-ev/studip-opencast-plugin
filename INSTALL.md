# Stud.IP Opencast Plugin

The Opencast plugin is now using LTI to present the videos and enforce permissions. You need to configure your opencast system to make this plugin work.

## Configure Opencast

Refer to the Opencast documentation for instructions on how to configure your version of the Opencast system (f.e. https://docs.opencast.org/r/8.x/admin/#modules/ltimodule)

Normally this boils down to the following two changes / additions in the config files

Edit `etc/security/mh_default_org.xml` and make sure the follwing setting is enabled:
```
<ref bean="oauthProtectedResourceFilter" />
```

Edit / Create `etc/org.opencastproject.kernel.security.OAuthConsumerDetailsService.cfg` and configure / add the following settings:
```
oauth.consumer.name.1=CONSUMERNAME
oauth.consumer.key.1=CONSUMERKEY
oauth.consumer.secret.1=CONSUMERSECRET
```

After that, restart Opencast.

## Opencast Workflows

This plugin assumes your republish workflow [has the ID `republish-metadata`](https://github.com/elan-ev/studip-opencast-plugin/issues/196).

## Credentials for Opencast

This plugin now needs a frontend user to connect to opencast, create one or us an existing one. 
This means, thath `matterhorn_system_account` or `opencast_system_account` no longer works!!

## Configure Stud.IP

Install the most recent version of this plugin, make sure that all migrations worked properly.

After that go to "Admin" -> "System" -> "Opencast settings" and enter the url and credentials for the opencast system. 
Make sure you enter the LTI credentials under "Additional settings".
If everything worked you can now start using the plugin in seminars.
