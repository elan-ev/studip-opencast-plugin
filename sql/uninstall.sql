DROP TABLE IF EXISTS `oc_config`, `oc_seminar_series`, `oc_resources`, `oc_seminar_episodes`, `oc_scheduled_recordings`;
DELETE FROM  resources_objects_properties 
WHERE property_id IN(SELECT property_id FROM resources_properties WHERE name = 'Opencast Capture Agent' );
DELETE FROM resources_properties WHERE name = 'Opencast Capture Agent';
