#!/usr/bin/env bash

set -euo pipefail

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-5432}"
DB_NAME="${DB_NAME:-laravel}"
DB_USER="${DB_USER:-laravel}"

run_query() {
    local title="$1"
    local query="$2"

    echo
    echo "=========================================="
    echo "$title"
    echo "=========================================="

    printf '%s\n' "$query" | pgcli \
        -h "$DB_HOST" \
        -p "$DB_PORT" \
        -U "$DB_USER" \
        -d "$DB_NAME"
}

run_query \
    "1. Filter tag + sort views" \
    "EXPLAIN ANALYZE SELECT * FROM posts WHERE tags @> '[\"history\"]'::jsonb  ORDER BY views DESC;"

run_query \
    "2. Artikel per user" \
    "EXPLAIN (ANALYZE, BUFFERS) SELECT * FROM posts WHERE user_id = 1;"

run_query \
    "3. Komentar per artikel" \
    "EXPLAIN (ANALYZE, BUFFERS) SELECT * FROM comments WHERE post_id = 1;"
