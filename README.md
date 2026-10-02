# api-symfony-php

Symfony 7 REST API for the art catalogue project.

## Overview
This repository contains a clean Symfony 7 migration of the original Slim-based art catalogue API, updated for PostgreSQL compatibility.

## Features
- REST-style endpoints for metadata and artworks
- PostgreSQL-ready connection settings using PDO PGSQL
- Full-text search with PostgreSQL `tsvector` / `plainto_tsquery`
- Pagination support
- Environment-based configuration
- Service-oriented architecture

## Requirements
- PHP 8.2+
- PostgreSQL 12+
- PDO PostgreSQL extension enabled
- Composer

## Setup

1. Install PHP dependencies:
   ```bash
   composer install
   ```

2. Configure PostgreSQL credentials in `.env` or `.env.local`:
   ```env
   DATABASE_DSN=pgsql:host=127.0.0.1;port=5432;dbname=mgoart
   DATABASE_USER=postgres
   DATABASE_PASSWORD=postgres
   ```

3. Start the app:
   ```bash
   php -S 127.0.0.1:8000 -t public/
   ```

4. Test a route:
   ```bash
   curl "http://127.0.0.1:8000/api/info/author?page=1&limit=10"
   ```

## Notes
- MySQL-specific `MATCH ... AGAINST` queries were replaced with PostgreSQL full-text search logic.
- PostgreSQL uses `RANDOM()` instead of `RAND()`.
- Ensure the `pdo_pgsql` PHP extension is enabled.
