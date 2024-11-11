-- Migrate from plugin version 2, if any
UPDATE plugins SET enabled = 'no' WHERE pluginclassname = 'OpenCast';
INSERT IGNORE INTO schema_version (domain, branch, version) SELECT 'OpencastV3' as domain, branch, version FROM schema_version WHERE domain = 'OpenCast';
UPDATE schema_version SET version = 0 WHERE domain = 'OpenCast';
