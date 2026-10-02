-- PostgreSQL Migration Guide from MySQL to PostgreSQL
-- This file contains helpful information for migrating data

-- If you have MySQL backups, follow these steps:

-- 1. Export MySQL data to CSV or use pgloader
--    pgloader mysql://user:pass@localhost/mgoart postgresql://user:pass@localhost/mgoart

-- 2. After importing, update sequences to match existing data:
-- SELECT setval('art_id_seq', (SELECT MAX("ID") FROM "ART"));
-- SELECT setval('author_id_seq', (SELECT MAX("ID") FROM "AUTHOR"));
-- SELECT setval('form_id_seq', (SELECT MAX("ID") FROM "FORM"));
-- SELECT setval('location_id_seq', (SELECT MAX("ID") FROM "LOCATION"));
-- SELECT setval('school_id_seq', (SELECT MAX("ID") FROM "SCHOOL"));
-- SELECT setval('timeframe_id_seq', (SELECT MAX("ID") FROM "TIMEFRAME"));
-- SELECT setval('type_id_seq', (SELECT MAX("ID") FROM "TYPE"));
-- SELECT setval('log_table_id_seq', (SELECT MAX("ID") FROM "LOG_TABLE"));

-- 3. Test full-text search:
-- SELECT * FROM "ARTDATA" WHERE to_tsvector('english', coalesce("TITLE", '') || ' ' || coalesce("TECHNIQUE", '')) @@ plainto_tsquery('english', 'portrait');

-- 4. Verify views:
-- SELECT * FROM "ARTDATA" LIMIT 10;

