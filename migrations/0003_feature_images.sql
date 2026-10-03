-- Picks a representative artwork ("feature image") for every taxonomy row.
-- Run via: php bin/console app:db:feature-images
CREATE OR REPLACE FUNCTION update_feature_images() RETURNS void
LANGUAGE plpgsql AS $$
DECLARE
    d text;
BEGIN
    -- Authors: prefer a self-portrait, otherwise the earliest artwork.
    UPDATE author au SET fimage = (
        SELECT a.id FROM art a
        WHERE a.author_id = au.id AND a.title ILIKE '%self-portrait%'
        ORDER BY a.id LIMIT 1
    )
    WHERE EXISTS (SELECT 1 FROM art a WHERE a.author_id = au.id AND a.title ILIKE '%self-portrait%');

    UPDATE author au SET fimage = (SELECT a.id FROM art a WHERE a.author_id = au.id ORDER BY a.id LIMIT 1)
    WHERE au.fimage IS NULL;

    -- Other taxonomies: a random artwork, only where none is set yet.
    FOREACH d IN ARRAY ARRAY['form', 'location', 'school', 'timeframe', 'type'] LOOP
        EXECUTE format(
            'UPDATE %1$I t SET fimage = (SELECT a.id FROM art a WHERE a.%2$I = t.id ORDER BY random() LIMIT 1) WHERE t.fimage IS NULL',
            d, d || '_id'
        );
    END LOOP;
END
$$;
