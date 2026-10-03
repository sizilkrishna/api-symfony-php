#!/bin/sh
set -e

# Opt-in: apply pending SQL migrations before the server starts.
if [ "${RUN_MIGRATIONS:-0}" = "1" ]; then
    runuser -u www-data -- php bin/console app:db:migrate --no-interaction
fi

exec "$@"
