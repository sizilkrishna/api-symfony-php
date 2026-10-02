-- PostgreSQL Functions and Procedures for Art Catalogue Database

-- Update feature images for artists and each taxonomy entry.
CREATE OR REPLACE FUNCTION update_feature_images()
RETURNS VOID
LANGUAGE plpgsql
AS $$
BEGIN
    -- Set author feature image to a self-portrait if found.
    UPDATE "AUTHOR" a
    SET "FIMAGE" = (
        SELECT art."ID"
        FROM "ART" art
        WHERE art."AUTHOR_ID" = a."ID"
          AND LOWER(art."TITLE") LIKE '%self-portrait%'
        ORDER BY art."ID"
        LIMIT 1
    )
    WHERE EXISTS (
        SELECT 1
        FROM "ART" art
        WHERE art."AUTHOR_ID" = a."ID"
          AND LOWER(art."TITLE") LIKE '%self-portrait%'
    );

    -- Update feature images for category tables.
    UPDATE "FORM" f
    SET "FIMAGE" = (
        SELECT art."ID"
        FROM "ART" art
        WHERE art."FORM_ID" = f."ID"
        ORDER BY RANDOM()
        LIMIT 1
    )
    WHERE "FIMAGE" IS NULL OR "FIMAGE" = 0;

    UPDATE "LOCATION" l
    SET "FIMAGE" = (
        SELECT art."ID"
        FROM "ART" art
        WHERE art."LOCATION_ID" = l."ID"
        ORDER BY RANDOM()
        LIMIT 1
    )
    WHERE "FIMAGE" IS NULL OR "FIMAGE" = 0;

    UPDATE "SCHOOL" s
    SET "FIMAGE" = (
        SELECT art."ID"
        FROM "ART" art
        WHERE art."SCHOOL_ID" = s."ID"
        ORDER BY RANDOM()
        LIMIT 1
    )
    WHERE "FIMAGE" IS NULL OR "FIMAGE" = 0;

    UPDATE "TIMEFRAME" t
    SET "FIMAGE" = (
        SELECT art."ID"
        FROM "ART" art
        WHERE art."TIMEFRAME_ID" = t."ID"
        ORDER BY RANDOM()
        LIMIT 1
    )
    WHERE "FIMAGE" IS NULL OR "FIMAGE" = 0;

    UPDATE "TYPE" ty
    SET "FIMAGE" = (
        SELECT art."ID"
        FROM "ART" art
        WHERE art."TYPE_ID" = ty."ID"
        ORDER BY RANDOM()
        LIMIT 1
    )
    WHERE "FIMAGE" IS NULL OR "FIMAGE" = 0;
END;
$$;

-- Query to get grouped metadata values by author.
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

-- Full-text search function for artworks.
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
)
LANGUAGE plpgsql
STABLE
AS $$
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
            CASE
                WHEN to_tsvector('english', coalesce(a."TITLE", '')) @@ plainto_tsquery('english', search_query)
                    THEN 'TITLE'
                WHEN to_tsvector('english', coalesce(a."TECHNIQUE", '')) @@ plainto_tsquery('english', search_query)
                    THEN 'TECHNIQUE'
                WHEN EXISTS (
                    SELECT 1
                    FROM "AUTHOR" au
                    WHERE au."ID" = a."AUTHOR_ID"
                      AND to_tsvector('english', coalesce(au."AUTHOR", '')) @@ plainto_tsquery('english', search_query)
                )
                    THEN 'AUTHOR'
                ELSE NULL
            END AS matched_field
        FROM "ART" a
        WHERE to_tsvector('english', coalesce(a."TITLE", '') || ' ' || coalesce(a."TECHNIQUE", '') || ' ' || coalesce(a."URL", '')) @@ plainto_tsquery('english', search_query)
           OR EXISTS (
                SELECT 1
                FROM "AUTHOR" au
                WHERE au."ID" = a."AUTHOR_ID"
                  AND to_tsvector('english', coalesce(au."AUTHOR", '')) @@ plainto_tsquery('english', search_query)
            )
    ) q
    LEFT JOIN "AUTHOR" au ON q."AUTHOR_ID" = au."ID"
    LEFT JOIN "FORM" f ON q."FORM_ID" = f."ID"
    LEFT JOIN "LOCATION" l ON q."LOCATION_ID" = l."ID"
    LEFT JOIN "SCHOOL" s ON q."SCHOOL_ID" = s."ID"
    LEFT JOIN "TYPE" ty ON q."TYPE_ID" = ty."ID"
    WHERE q.matched_field IS NOT NULL
    GROUP BY q."ID", q."TITLE", q."DATE", q."TECHNIQUE", q."URL", au."AUTHOR", f."FORM", l."LOCATION", s."SCHOOL", ty."TYPE"
    ORDER BY q."ID" ASC
    OFFSET ((page_num - 1) * limit_num)
    LIMIT limit_num;
END;
$$;

-- Stored function to get author statistics.
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
)
LANGUAGE plpgsql
STABLE
AS $$
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
$$;
