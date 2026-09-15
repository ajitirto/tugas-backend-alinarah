➜  tugas-backend-alinarah git:(3-data-index) ✗ ./scripts/analysize-indexes.sh

==========================================
1. Filter tag + sort views
==========================================
Using local time zone Asia/Jakarta (server uses Etc/UTC)
Use `set time zone <TZ>` to override, or set `use_local_timezone = False` in the config
Warning: Input is not a terminal (fd=0).
Server: PostgreSQL 17.4 (Debian 17.4-1.pgdg120+2)
Version: 4.3.0
Home: http://pgcli.com
laravel@127.0.0.1:laravel> EXPLAIN ANALYZE SELECT * FROM posts WHERE tags @> '["history"]'::jsonb  ORDER BY views DESC;
+--------------------------------------------------------------------------------------------------------------------------------+
| QUERY PLAN                                                                                                                     |
|--------------------------------------------------------------------------------------------------------------------------------|
| Gather Merge  (cost=7447.97..9715.89 rows=19438 width=388) (actual time=17.506..26.356 rows=22456 loops=1)                     |
|   Workers Planned: 2                                                                                                           |
|   Workers Launched: 2                                                                                                          |
|   ->  Sort  (cost=6447.94..6472.24 rows=9719 width=388) (actual time=14.793..15.450 rows=7485 loops=3)                         |
|         Sort Key: views DESC                                                                                                   |
|         Sort Method: quicksort  Memory: 3715kB                                                                                 |
|         Worker 0:  Sort Method: quicksort  Memory: 2834kB                                                                      |
|         Worker 1:  Sort Method: quicksort  Memory: 2974kB                                                                      |
|         ->  Parallel Seq Scan on posts  (cost=0.00..5804.22 rows=9719 width=388) (actual time=0.017..12.376 rows=7485 loops=3) |
|               Filter: (tags @> '["history"]'::jsonb)                                                                           |
|               Rows Removed by Filter: 26065                                                                                    |
| Planning Time: 0.775 ms                                                                                                        |
| Execution Time: 27.569 ms                                                                                                      |
+--------------------------------------------------------------------------------------------------------------------------------+
EXPLAIN 13
Time: 0.039s
laravel@127.0.0.1:laravel>
Goodbye!

==========================================
2. Artikel per user
==========================================
Using local time zone Asia/Jakarta (server uses Etc/UTC)
Use `set time zone <TZ>` to override, or set `use_local_timezone = False` in the config
Warning: Input is not a terminal (fd=0).
Server: PostgreSQL 17.4 (Debian 17.4-1.pgdg120+2)
Version: 4.3.0
Home: http://pgcli.com
laravel@127.0.0.1:laravel> EXPLAIN (ANALYZE, BUFFERS) SELECT * FROM posts WHERE user_id = 1;
+--------------------------------------------------------------------------------------------------------------------------------+
| QUERY PLAN                                                                                                                     |
|--------------------------------------------------------------------------------------------------------------------------------|
| Bitmap Heap Scan on posts  (cost=7.42..1254.49 rows=404 width=388) (actual time=0.072..2.068 rows=401 loops=1)                 |
|   Recheck Cond: (user_id = 1)                                                                                                  |
|   Heap Blocks: exact=401                                                                                                       |
|   Buffers: shared hit=406                                                                                                      |
|   ->  Bitmap Index Scan on posts_user_id_index  (cost=0.00..7.32 rows=404 width=0) (actual time=0.041..0.042 rows=401 loops=1) |
|         Index Cond: (user_id = 1)                                                                                              |
|         Buffers: shared hit=5                                                                                                  |
| Planning:                                                                                                                      |
|   Buffers: shared hit=156                                                                                                      |
| Planning Time: 0.917 ms                                                                                                        |
| Execution Time: 2.114 ms                                                                                                       |
+--------------------------------------------------------------------------------------------------------------------------------+
EXPLAIN 11
Time: 0.020s
laravel@127.0.0.1:laravel>
Goodbye!

==========================================
3. Komentar per artikel
==========================================
Using local time zone Asia/Jakarta (server uses Etc/UTC)
Use `set time zone <TZ>` to override, or set `use_local_timezone = False` in the config
Warning: Input is not a terminal (fd=0).
Server: PostgreSQL 17.4 (Debian 17.4-1.pgdg120+2)
Version: 4.3.0
Home: http://pgcli.com
laravel@127.0.0.1:laravel> EXPLAIN (ANALYZE, BUFFERS) SELECT * FROM comments WHERE post_id = 1;
+---------------------------------------------------------------------------------------------------+
| QUERY PLAN                                                                                        |
|---------------------------------------------------------------------------------------------------|
| Seq Scan on comments  (cost=0.00..9.25 rows=3 width=83) (actual time=0.022..0.036 rows=3 loops=1) |
|   Filter: (post_id = 1)                                                                           |
|   Rows Removed by Filter: 337                                                                     |
|   Buffers: shared hit=5                                                                           |
| Planning:                                                                                         |
|   Buffers: shared hit=137                                                                         |
| Planning Time: 0.742 ms                                                                           |
| Execution Time: 0.051 ms                                                                          |
+---------------------------------------------------------------------------------------------------+
EXPLAIN 8
Time: 0.018s
laravel@127.0.0.1:laravel>
Goodbye!
