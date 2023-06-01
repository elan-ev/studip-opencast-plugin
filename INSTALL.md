# Stud.IP Opencast Plugin

The Opencast plugin is now using LTI to present the videos and enforce permissions. You need to configure your Opencast system to make this plugin work.

## Configure Opencast

Refer to the Opencast documentation for instructions on how to configure your version of the Opencast system (f.e. https://docs.opencast.org/r/8.x/admin/#modules/ltimodule)

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

3. Edit `/etc/opencast/org.opencastproject.security.lti.LtiLaunchAuthenticationHandler.cfg` and enable:
```
lti.create_jpa_user_reference=true
lti.custom_role_name=Instructor
lti.custom_roles=ROLE_STUDIO,ROLE_ADMIN_UI,ROLE_UI_EVENTS_DETAILS_COMMENTS_CREATE,ROLE_UI_EVENTS_DETAILS_COMMENTS_DELETE,ROLE_UI_EVENTS_DETAILS_COMMENTS_EDIT,ROLE_UI_EVENTS_DETAILS_COMMENTS_REPLY,ROLE_UI_EVENTS_DETAILS_COMMENTS_RESOLVE,ROLE_UI_EVENTS_DETAILS_COMMENTS_VIEW,ROLE_UI_EVENTS_DETAILS_MEDIA_VIEW,ROLE_UI_EVENTS_DETAILS_METADATA_EDIT,ROLE_UI_EVENTS_DETAILS_METADATA_VIEW,ROLE_UI_EVENTS_DETAILS_VIEW,ROLE_UI_EVENTS_EDITOR_EDIT,ROLE_UI_EVENTS_EDITOR_VIEW,ROLE_CAPTURE_AGENT
```

4. Edit `/etc/opencast/org.opencastproject.plugin.impl.PluginManagerImpl.cfg` and enable: *(Plugin Version >= 3, Opencast >= 13)*

```
	...
    opencast-plugin-userdirectory-studip = on
	...
```

5. Edit `/etc/opencast/org.opencastproject.userdirectory.studip-default.cfg` *(Plugin Version >= 3, Opencast >= 13)*

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

Make sure to change the token and add that token to the Opencast config in Stud.IP.

6. Add role `STUDIP` in Opencast *(Plugin Version >= 3, Opencast >= 13)*

In the Opencast Admin UI, go to Organisation -> Groups and add a group named `STUDIP`

----

After all that, restart Opencast.


## Opencast - CORS

If your Stud.IP system resides on a different domain than your Opencast, you need to configure Opencasts Nginx to allow CORS requests. For an explanation why this is necessary and examples how to achieve this, take a look at:
* https://gist.github.com/iki/1247cd182acd1aa3ee4876acb7263def#file-nginx-cors-proxy-conf
* https://developer.mozilla.org/de/docs/Web/HTTP/CORS

Example (nginx):

`/etc/nginx/nginx.conf`

```
user www-data;
worker_processes auto;
pid /run/nginx.pid;
include /etc/nginx/modules-enabled/*.conf;

events {
        worker_connections 768;
        # multi_accept on;
}

http {

    # HTTP set-up
    server {
        listen 80;
        listen [::]:80;
        server_name _;

        # Enforce HTTPS by redirecting requests
        location / {
            return 301 https://opencast.me$request_uri;
        }
    }

    # HTTPS set-up
    server {
        listen      443 ssl http2;
        listen [::]:443 ssl http2;
        server_name opencast.me;

        # Path to the TLS certificate and private key. In almost all cases, you
        # need to provide intermediate certificates as well to ensure browsers
        # get the whole certificate chain.
        ssl_certificate_key  /etc/ssl/private/nginx-selfsigned.key;
        ssl_certificate      /etc/ssl/certs/nginx-selfsigned.crt;

        # Accept large ingests. There should be no limit since Opencast may get
        # really large ingests.
        client_max_body_size 0;

        # Proxy configuration for Opencast
        location / {

            # Make sure to pass the real addresses as well as the fact that
            # outwards we are using HTTPS to Opencast.
            proxy_set_header        Host $host;
            proxy_set_header        X-Real-IP $remote_addr;
            proxy_set_header        X-Forwarded-For $proxy_add_x_forwarded_for;
            proxy_set_header        X-Forwarded-Proto $scheme;

            # Pass requests to this location. This expects Opencast to be
            # running locally on port 8080 which should be the default set-up.
            proxy_pass              http://127.0.0.1:8080;

            # Make sure to redirect location headers to HTTPS. This is just a
            # precaution and shouldn't strictly be necessary but it did prevent
            # some issues in the past and it does not cost much performance.
            proxy_redirect          http://$host https://$host;

            # Make sure to serve cookies only via secure connections.
            # proxy_cookie_flags ~ secure httponly;
            # When using Nginx <1.19.3 replace the above 'proxy_cookie_flags' line
            # with the (uncommented) 'proxy_cookie_path' line below.
            #proxy_cookie_path / "/; HTTPOnly; Secure";

            # Depending on your integration, you may also want to allow cookies
            # to be used on other sites. In that case, use this instead:
            #proxy_cookie_path / "/; HTTPOnly; Secure; SameSite=None";

            # Do not buffer responses
            proxy_buffering         off;

            # Do not buffer requests
            proxy_request_buffering off;

            #
            # Wide-open CORS config for nginx
            #
            if ($request_method = 'OPTIONS') {
                add_header 'Access-Control-Allow-Credentials' true;
                add_header 'Access-Control-Allow-Origin' 'https://studip.me';
                add_header 'Access-Control-Allow-Methods' 'GET, POST, OPTIONS';
                #
                # Custom headers and headers various browsers *should* be OK with but aren't
                #
                add_header 'Access-Control-Allow-Headers' 'DNT,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type,Range';
                #
                # Tell client that this pre-flight info is valid for 20 days
                #
                add_header 'Access-Control-Max-Age' 1728000;
                add_header 'Content-Type' 'text/plain; charset=utf-8';
                add_header 'Content-Length' 0;
                return 204;
            }

            if ($request_method = 'POST') {
                add_header 'Access-Control-Allow-Credentials' true;
                add_header 'Access-Control-Allow-Origin' 'https://studip.me' always;
                add_header 'Access-Control-Allow-Methods' 'GET, POST, OPTIONS' always;
                add_header 'Access-Control-Allow-Headers' 'DNT,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type,Range' always;
                add_header 'Access-Control-Expose-Headers' 'Content-Length,Content-Range' always;
            }

            if ($request_method = 'GET') {
                add_header 'Access-Control-Allow-Credentials' true;
                add_header 'Access-Control-Allow-Origin' 'https://studip.me' always;
                add_header 'Access-Control-Allow-Methods' 'GET, POST, OPTIONS' always;
                add_header 'Access-Control-Allow-Headers' 'DNT,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type,Range' always;
                add_header 'Access-Control-Expose-Headers' 'Content-Length,Content-Range' always;
            }
        }
    }
}
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

### Stud.IP User Provider *(Plugin Version >= 3, Opencast >= 13)*

Make sure you followed all steps above. Furthermore make sure the Opencast-Plugin in Stud.IP is assigned to the nobody role for it to work.
