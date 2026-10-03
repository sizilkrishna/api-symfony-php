<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * The six taxonomies an artwork belongs to. Backing values double as the table
 * name and the label column of each taxonomy table, so they are the only values
 * ever interpolated into SQL (they come from this enum, never from user input).
 */
enum Dimension: string
{
    case Author = 'author';
    case Form = 'form';
    case Location = 'location';
    case School = 'school';
    case Timeframe = 'timeframe';
    case Type = 'type';

    public function table(): string
    {
        return $this->value;
    }

    public function label(): string
    {
        return $this->value;
    }

    /** Foreign key column on the "art" table. */
    public function foreignKey(): string
    {
        return $this->value . '_id';
    }

    /** Short query-string name used by /api/filter and /api/search. */
    public function queryParam(): string
    {
        return match ($this) {
            self::Author => 'au',
            self::Form => 'fo',
            self::Location => 'lo',
            self::School => 'sc',
            self::Timeframe => 'ti',
            self::Type => 'ty',
        };
    }
}
