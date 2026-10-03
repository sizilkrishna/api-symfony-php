TRUNCATE art, author, form, location, school, timeframe, type, log_table RESTART IDENTITY CASCADE;

INSERT INTO author (id, author, born_died) VALUES
    (1, 'REMBRANDT Harmenszoon van Rijn', '(b. 1606, Leiden, d. 1669, Amsterdam)'),
    (2, 'MONET, Claude', '(b. 1840, Paris, d. 1926, Giverny)'),
    (3, 'ZURBARAN, Francisco de', '(b. 1598, Fuente de Cantos, d. 1664, Madrid)');
INSERT INTO form      (id, form)      VALUES (1, 'painting'), (2, 'graphics');
INSERT INTO location  (id, location)  VALUES (1, 'Amsterdam'), (2, 'Paris');
INSERT INTO school    (id, school)    VALUES (1, 'Dutch'), (2, 'French');
INSERT INTO timeframe (id, timeframe) VALUES (1, '1601-1650'), (2, '1851-1900');
INSERT INTO type      (id, type)      VALUES (1, 'portrait'), (2, 'landscape');

INSERT INTO art (id, title, date, technique, url, author_id, form_id, location_id, school_id, timeframe_id, type_id) VALUES
    (1, 'Self-Portrait with Beret', '1659', 'Oil on canvas',   'https://example.org/a/rembrandt/1.jpg', 1, 1, 1, 1, 1, 1),
    (2, 'The Night Watch',          '1642', 'Oil on canvas',   'https://example.org/a/rembrandt/2.jpg', 1, 1, 1, 1, 1, 1),
    (3, 'Water Lilies',             '1906', 'Oil on canvas',   'https://example.org/a/monet/3.jpg',     2, 1, 2, 2, 2, 2),
    (4, 'Impression, Sunrise',      '1872', 'Oil on canvas',   'https://example.org/a/monet/4.jpg',     2, 1, 2, 2, 2, 2),
    (5, 'Etching of a Beggar',      '1630', 'Etching on paper','https://example.org/a/rembrandt/5.jpg', 1, 2, 1, 1, 1, 1);

UPDATE author    SET fimage = 1 WHERE id = 1;
UPDATE type      SET fimage = 1 WHERE id = 1;
UPDATE school    SET fimage = 3 WHERE id = 2;
