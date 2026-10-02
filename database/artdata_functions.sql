-- PostgreSQL Functions and Procedures for Art Catalogue Database

-- Setting up self-portraits of authors
-- Updates AUTHOR table with FIMAGE pointing to self-portrait artworks
UPDATE "AUTHOR" a
SET "FIMAGE" = (
    SELECT "ID" FROM "ART" 
    WHERE "AUTHOR_ID" = a."ID" 
    AND LOWER("TITLE") LIKE '%self-portrait%'
    LIMIT 1
)
WHERE EXISTS (
    SELECT 1 FROM "ART" 
    WHERE "AUTHOR_ID" = a."ID" 
    AND LOWER("TITLE") LIKE '%self-portrait%'
);

-- Update feature images for FORM category
-- Selects a random artwork for each form as the featured image
UPDATE "FORM" f
SET "FIMAGE" = (
    SELECT "ID" FROM "ART" 
    WHERE "FORM_ID" = f."ID" 
    ORDER BY RANDOM() 
    LIMIT 1
)
WHERE "FIMAGE" IS NULL OR "FIMAGE" = 0;

-- Update feature images for LOCATION category
UPDATE "LOCATION" l
SET "FIMAGE" = (
    SELECT "ID" FROM "ART" 
    WHERE "LOCATION_ID" = l."ID" 
    ORDER BY RANDOM() 
    LIMIT 1
)
WHERE "FIMAGE" IS NULL OR "FIMAGE" = 0;

-- Update feature images for SCHOOL category
UPDATE "SCHOOL" s
SET "FIMAGE" = (
    SELECT "ID" FROM "ART" 
    WHERE "SCHOOL_ID" = s."ID" 
    ORDER BY RANDOM() 
    LIMIT 1
)
WHERE "FIMAGE" IS NULL OR "FIMAGE" = 0;

-- Update feature images for TIMEFRAME category
UPDATE "TIMEFRAME" t
SET "FIMAGE" = (
    SELECT "ID" FROM "ART" 
    WHERE "TIMEFRAME_ID" = t."ID" 
    ORDER BY RANDOM() 
    LIMIT 1
)
WHERE "FIMAGE" IS NULL OR "FIMAGE" = 0;

-- Update feature images for TYPE category
UPDATE "TYPE" ty
SET "FIMAGE" = (
    SELECT "ID" FROM "ART" 
    WHERE "TYPE_ID" = ty."ID" 
    ORDER BY RANDOM() 
    LIMIT 1
)
WHERE "FIMAGE" IS NULL OR "FIMAGE" = 0;

-- Query to get grouped values of categories by author
-- Returns distinct metadata associated with each author
SELECT
    au."ID",
    au."AUTHOR",
    au."BORN_DIED",
    COUNT(a."ID") AS artwork_count,
    STRING_AGG(DISTINCT f."FORM", ', ') AS forms,
    STRING_AGG(DISTINCT s."SCHOOL", ', ') AS schools,
    STRING_AGG(DISTINCT l."LOCATION", ', ') AS locations,
    STRING_AGG(DISTINCT t."TIMEFRAME", ', ') AS timeframes,
    STRING_AGG(DISTINCT ty."TYPE", ', ') AS types
FROM "AUTHOR" au
LEFT JOIN "ART" a ON au."ID" = a."AUTHOR_ID"
LEFT JOIN "FORM" f ON a."FORM_ID" = f."ID"
LEFT JOIN "SCHOOL" s ON a."SCHOOL_ID" = s."ID"
LEFT JOIN "LOCATION" l ON a."LOCATION_ID" = l."ID"
LEFT JOIN "TIMEFRAME" t ON a."TIMEFRAME_ID" = t."ID"
LEFT JOIN "TYPE" ty ON a."TYPE_ID" = ty."ID"
GROUP BY au."ID", au."AUTHOR", au."BORN_DIED"
HAVING COUNT(a."ID") > 0
ORDER BY au."ID" ASC;

-- Stored function to get full-text search results
CREATE OR REPLACE FUNCTION search_artdata(
    search_query TEXT,
    page_num INT DEFAULT 1,
    limit_num INT DEFAULT 10
)
RETURNS TABLE(
    "ID" INTEGER,
    "TITLE" VARCHAR,
    "DATE" VARCHAR,
    "TECHNIQUE" VARCHAR,
    "URL" VARCHAR,
    "AUTHOR" VARCHAR,
    "FORM" VARCHAR,
    "LOCATION" VARCHAR,
    "SCHOOL" VARCHAR,
    "TYPE" VARCHAR,
    found_in VARCHAR
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        a."ID",
        a."TITLE",
        a."DATE",
        a."TECHNIQUE",
        a."URL",
        au."AUTHOR",
        f."FORM",
        l."LOCATION",
        s."SCHOOL",
        ty."TYPE",
        STRING_AGG(matched_field, ', ') AS found_in
    FROM (
        SELECT
            a."ID",
            a."TITLE",
            a."DATE",
            a."TECHNIQUE",
            a."URL",
            a."AUTHOR_ID",
            a."FORM_ID",
            a."LOCATION_ID",
            a."SCHOOL_ID",
            a."TYPE_ID",
            CASE WHEN to_tsvector('english', coalesce(a."TITLE", '')) @@ plainto_tsquery('english', search_query) THEN 'TITLE' END AS matched_field
        FROM "ART" a
        WHERE to_tsvector('english', coalesce(a."TITLE", '') || ' ' || coalesce(a."TECHNIQUE", '') || ' ' || coalesce(a."URL", '')) @@ plainto_tsquery('english', search_query)
        UNION ALL
        SELECT
            a."ID",
            a."TITLE",
            a."DATE",
            a."TECHNIQUE",
            a."URL",
            a."AUTHOR_ID",
            a."FORM_ID",
            a."LOCATION_ID",
            a."SCHOOL_ID",
            a."TYPE_ID",
            'TECHNIQUE' AS matched_field
        FROM "ART" a
        WHERE to_tsvector('english', coalesce(a."TECHNIQUE", '')) @@ plainto_tsquery('english', search_query)
        UNION ALL
        SELECT
            a."ID",
            a."TITLE",
            a."DATE",
            a."TECHNIQUE",
            a."URL",
            a."AUTHOR_ID",
            a."FORM_ID",
            a."LOCATION_ID",
            a."SCHOOL_ID",
            a."TYPE_ID",
            'AUTHOR' AS matched_field
        FROM "ART" a
        WHERE EXISTS (
            SELECT 1 FROM "AUTHOR" au
            WHERE au."ID" = a."AUTHOR_ID"
            AND to_tsvector('english', coalesce(au."AUTHOR", '')) @@ plainto_tsquery('english', search_query)
        )
    ) a
    LEFT JOIN "AUTHOR" au ON a."AUTHOR_ID" = au."ID"
    LEFT JOIN "FORM" f ON a."FORM_ID" = f."ID"
    LEFT JOIN "LOCATION" l ON a."LOCATION_ID" = l."ID"
    LEFT JOIN "SCHOOL" s ON a."SCHOOL_ID" = s."ID"
    LEFT JOIN "TYPE" ty ON a."TYPE_ID" = ty."ID"
    WHERE matched_field IS NOT NULL
    GROUP BY a."ID", a."TITLE", a."DATE", a."TECHNIQUE", a."URL", au."AUTHOR", f."FORM", l."LOCATION", s."SCHOOL", ty."TYPE"
    ORDER BY a."ID" ASC
    OFFSET ((page_num - 1) * limit_num) ROWS
    FETCH NEXT limit_num ROWS ONLY;
END;
$$ LANGUAGE plpgsql STABLE;

-- Stored function to get author statistics
CREATE OR REPLACE FUNCTION get_author_stats(author_id_param INT)
RETURNS TABLE(
    author_name VARCHAR,
    birth_death VARCHAR,
    total_works INT,
    forms_count INT,
    schools_count INT,
    locations_count INT,
    timeframes_count INT,
    types_count INT
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        au."AUTHOR",
        au."BORN_DIED",
        COUNT(DISTINCT a."ID")::INT,
        COUNT(DISTINCT a."FORM_ID")::INT,
        COUNT(DISTINCT a."SCHOOL_ID")::INT,
        COUNT(DISTINCT a."LOCATION_ID")::INT,
        COUNT(DISTINCT a."TIMEFRAME_ID")::INT,
        COUNT(DISTINCT a."TYPE_ID")::INT
    FROM "AUTHOR" au
    LEFT JOIN "ART" a ON au."ID" = a."AUTHOR_ID"
    WHERE au."ID" = author_id_param
    GROUP BY au."ID", au."AUTHOR", au."BORN_DIED";
END;
$$ LANGUAGE plpgsql STABLE;
