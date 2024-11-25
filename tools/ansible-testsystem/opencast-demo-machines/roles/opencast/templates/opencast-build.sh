#!/bin/sh
set -uex

cd /opt/opencast-build/

# Clean up first of we are out of space (<300MB free)
free_space="$(df --output=avail . | tail -n1)"
if [ "${free_space}" -lt 300000 ]; then
  rm -rf /srv/opencast/opencast-dist-allinone/data/opencast/
fi

# Get latest opencast
curl -s -O https://radosgw.public.os.wwu.de/opencast-daily/opencast-dist-allinone-{{ version }}.tar.gz
tar xf opencast-dist-allinone-*.tar.gz
rm opencast-dist-allinone-*.tar.gz

# Stop and remove old Opencast
sudo systemctl stop opencast.service || :
rm -rf /srv/opencast/opencast-dist-allinone

# Set-up new Opencast
mv opencast-dist-allinone /srv/opencast/
sed -i 's#^org.opencastproject.server.url=.*$#org.opencastproject.server.url=https://{{ inventory_hostname }}#' /srv/opencast/opencast-dist-allinone/etc/custom.properties

# Enable capture agent user
sed -i 's/^#capture_agent.user.mh_default_org.opencast_capture_agent/capture_agent.user.mh_default_org.opencast_capture_agent/' \
	/srv/opencast/opencast-dist-allinone/etc/org.opencastproject.userdirectory.InMemoryUserAndRoleProvider.cfg

# Configure LTI
sed -i 's_<!-- \(<ref.*oauthProtectedResourceFilter.*/>\) -->_\1_' /srv/opencast/opencast-dist-allinone/etc/security/mh_default_org.xml
sed -i 's_#oauth_oauth_' /srv/opencast/opencast-dist-allinone/etc/org.opencastproject.kernel.security.OAuthConsumerDetailsService.cfg

echo 'lti.create_jpa_user_reference = false' >> /srv/opencast/opencast-dist-allinone/etc/org.opencastproject.security.lti.LtiLaunchAuthenticationHandler.cfg
echo 'lti.custom_role_name=Instructor' >> /srv/opencast/opencast-dist-allinone/etc/org.opencastproject.security.lti.LtiLaunchAuthenticationHandler.cfg
echo 'lti.custom_roles=ROLE_STUDIO,ROLE_UI_EVENTS_DETAILS_COMMENTS_CREATE,ROLE_UI_EVENTS_DETAILS_COMMENTS_DELETE,ROLE_UI_EVENTS_DETAILS_COMMENTS_EDIT,ROLE_UI_EVENTS_DETAILS_COMMENTS_REPLY,ROLE_UI_EVENTS_DETAILS_COMMENTS_RESOLVE,ROLE_UI_EVENTS_DETAILS_COMMENTS_VIEW,ROLE_UI_EVENTS_DETAILS_MEDIA_VIEW,ROLE_UI_EVENTS_DETAILS_METADATA_EDIT,ROLE_UI_EVENTS_DETAILS_METADATA_VIEW,ROLE_UI_EVENTS_DETAILS_VIEW,ROLE_UI_EVENTS_EDITOR_EDIT,ROLE_UI_EVENTS_EDITOR_VIEW,ROLE_CAPTURE_AGENT,ROLE_API_EVENTS_TRACK_EDIT,ROLE_API_WORKFLOW_INSTANCE_CREATE' >> /srv/opencast/opencast-dist-allinone/etc/org.opencastproject.security.lti.LtiLaunchAuthenticationHandler.cfg
echo 'lti.consumer_role_prefix.CONSUMERKEY0 = STUDIP_' >> /srv/opencast/opencast-dist-allinone/etc/org.opencastproject.security.lti.LtiLaunchAuthenticationHandler.cfg
echo 'lti.oauth.highly_trusted_consumer_key.1=CONSUMERKEY' >> /srv/opencast/opencast-dist-allinone/etc/org.opencastproject.security.lti.LtiLaunchAuthenticationHandler.cfg

# Configure Stud.IP user provider
cp /srv/opencast/opencast-dist-allinone/etc/org.opencastproject.userdirectory.studip-default.cfg.template /srv/opencast/opencast-dist-allinone/etc/org.opencastproject.userdirectory.studip-default.cfg
sed -i 's_https://my-studip.de/studip5/plugins.php/opencastv3/api/_{{ studip_uri }}/plugins.php/opencastv3/api/_' /srv/opencast/opencast-dist-allinone/etc/org.opencastproject.userdirectory.studip-default.cfg
echo org.opencastproject.userdirectory.studip.cache.expiration=1 >> /srv/opencast/opencast-dist-allinone/etc/org.opencastproject.userdirectory.studip-default.cfg

sed -i 's/opencast-plugin-userdirectory-studip.*= off/opencast-plugin-userdirectory-studip        = on/' /srv/opencast/opencast-dist-allinone/etc/org.opencastproject.plugin.impl.PluginManagerImpl.cfg

# Configure Opencast editor
sed -i 's/#show =.*/show = true/' /srv/opencast/opencast-dist-allinone/etc/ui-config/mh_default_org/editor/editor-settings.toml

# Ensure access to log files
mkdir -p /srv/opencast/opencast-dist-allinone/data/log
restorecon -r /srv/opencast/ || :
chcon -Rt httpd_sys_content_t /srv/opencast/opencast-dist-allinone/data/log || :
chcon -R system_u:object_r:bin_t:s0 /srv/opencast/opencast-dist-allinone/bin/ || :
chown opencast: /srv/opencast -R

# Clear Elasticsearch
sudo systemctl stop elasticsearch.service
sudo rm -rf /var/lib/elasticsearch/nodes
sudo systemctl restart elasticsearch.service

sleep 10

# Start Opencast
sudo systemctl start opencast.service

# Wait until Opencast is up before ingesting media
sleep 120
./ingest.py

# Avoid registration form
curl -i -u admin:opencast \
	'http://127.0.0.1:8080/admin-ng/adopter/registration' \
	--data-raw 'contactMe=false&allowsStatistics=false&allowsErrorReports=false&agreedToPolicy=false&organisationName=&departmentName=&country=&postalCode=&city=&firstName=&lastName=&street=&streetNo=&email=&registered='
