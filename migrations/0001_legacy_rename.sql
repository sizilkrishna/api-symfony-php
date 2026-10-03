-- Upgrades databases created with the previous (quoted, UPPER-CASE) schema to
-- lower-case snake_case identifiers. It is a no-op on a fresh database.
DO $$
DECLARE
    r record;
BEGIN
    IF to_regclass('public."ART"') IS NULL THEN
        RETURN;
    END IF;

    DROP VIEW IF EXISTS "ARTDATA";

    FOR r IN
        SELECT table_name FROM information_schema.tables
        WHERE table_schema = 'public' AND table_type = 'BASE TABLE' AND table_name <> lower(table_name)
    LOOP
        EXECUTE format('ALTER TABLE public.%I RENAME TO %I', r.table_name, lower(r.table_name));
    END LOOP;

    FOR r IN
        SELECT table_name, column_name FROM information_schema.columns
        WHERE table_schema = 'public' AND column_name <> lower(column_name)
    LOOP
        EXECUTE format('ALTER TABLE public.%I RENAME COLUMN %I TO %I', r.table_name, r.column_name, lower(r.column_name));
    END LOOP;

    IF EXISTS (SELECT 1 FROM information_schema.columns
               WHERE table_schema = 'public' AND table_name = 'log_table' AND column_name = 'timestamp') THEN
        ALTER TABLE log_table RENAME COLUMN "timestamp" TO created_at;
    END IF;
END
$$;
