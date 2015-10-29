BEGIN;

DROP INDEX ttrss_entries_guid_index;

UPDATE ttrss_version SET schema_version = 127;

COMMIT;
