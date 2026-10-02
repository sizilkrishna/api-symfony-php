# api-symfony-php

Symfony 7 REST API for the art catalogue project.

## Overview
This repository contains a clean Symfony 7 migration of the original Slim-based art catalogue API.

## Features
- REST-style endpoints for artist, type, school, location, form, and timeframe metadata
- Artwork listing and filtering based on IDs
- Full-text search using MySQL MATCH AGAINST
- Pagination support
- CORS-ready middleware by design
- Environment-based configuration
- Service-oriented architecture

## Requirements
- PHP 8.2+
- MySQL
- Composer

## Setup

1. Install PHP dependencies:
   composer install

2. Configure database credentials in `.env` or `.env.local`:
   DATABASE_DSN=mysql:host=127.0.0.1;dbname=mgoart;charset=utf8mb4
   DATABASE_USER=mercurial
   DATABASE_PASSWORD=qwe

3. Start the app:
   php -S 127.0.0.1:8000 -t public/

4. Test a route:
   curl http://127.0.0.1:8000/api/info/author?page=1\&limit=10

## Main routes
- /api/info/author
- /api/info/author/{id}
- /api/info/type
- /api/info/school
- /api/info/location
- /api/info/form
- /api/info/timeframe
- /api/art/all
- /api/art/author/{id}
- /api/art/type/{id}
- /api/art/school/{id}
- /api/art/location/{id}
- /api/art/form/{id}
- /api/search?q=...
- /api/filter?au=...&fo=...&lo=...&sc=...&ti=...&ty=...
- /api/random
- /api/logs

## Notes
This project is structured for clean maintainability, env configuration, and future extension.
