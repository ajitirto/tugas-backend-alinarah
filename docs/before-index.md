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
+-----------------------------------------------------------------------------------------------------------------+
| QUERY PLAN                                                                                                      |
|-----------------------------------------------------------------------------------------------------------------|
| Sort  (cost=6101.51..6103.09 rows=634 width=628) (actual time=51.846..54.734 rows=22456 loops=1)                |
|   Sort Key: views DESC                                                                                          |
|   Sort Method: external merge  Disk: 8808kB                                                                     |
|   ->  Seq Scan on posts  (cost=0.00..6072.00 rows=634 width=628) (actual time=0.017..32.881 rows=22456 loops=1) |
|         Filter: (tags @> '["history"]'::jsonb)                                                                  |
|         Rows Removed by Filter: 78195                                                                           |
| Planning Time: 0.383 ms                                                                                         |
| Execution Time: 57.612 ms                                                                                       |
+-----------------------------------------------------------------------------------------------------------------+
EXPLAIN 8
Time: 0.067s
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
+---------------------------------------------------------------------------------------------------------+
| QUERY PLAN                                                                                              |
|---------------------------------------------------------------------------------------------------------|
| Seq Scan on posts  (cost=0.00..6072.00 rows=317 width=628) (actual time=0.032..15.215 rows=401 loops=1) |
|   Filter: (user_id = 1)                                                                                 |
|   Rows Removed by Filter: 100250                                                                        |
|   Buffers: shared hit=5280                                                                              |
| Planning:                                                                                               |
|   Buffers: shared hit=84                                                                                |
| Planning Time: 0.575 ms                                                                                 |
| Execution Time: 15.279 ms                                                                               |
+---------------------------------------------------------------------------------------------------------+
EXPLAIN 8
Time: 0.033s
laravel@laravel@127.0.0.1:laravel>
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
+----------------------------------------------------------------------------------------------------+
| QUERY PLAN                                                                                         |
|----------------------------------------------------------------------------------------------------|
| Seq Scan on comments  (cost=0.00..19.38 rows=4 width=80) (actual time=0.052..0.111 rows=3 loops=1) |
|   Filter: (post_id = 1)                                                                            |
|   Rows Removed by Filter: 337                                                                      |
|   Buffers: shared hit=5                                                                            |
| Planning:                                                                                          |
|   Buffers: shared hit=74                                                                           |
| Planning Time: 0.480 ms                                                                            |
| Execution Time: 0.143 ms                                                                           |
+----------------------------------------------------------------------------------------------------+
EXPLAIN 8
Time: 0.018s
laravel@127.0.0.1:laravel>
Goodbye!

