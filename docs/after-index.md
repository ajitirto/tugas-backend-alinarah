# Hasil Pengujian Query — Sesudah Index

Dokumentasi ini mencatat hasil **EXPLAIN (ANALYZE)** untuk tiga query yang dioptimalkan pada Soal 3, diambil **sesudah** index database ditambahkan.

- Database: PostgreSQL 17.4
- Volume data: ±100.651 artikel di tabel `posts`
- Alat ukur: skrip `scripts/analysize-indexes.sh` (pgcli)
- Index ditambahkan oleh migration `2026_09_15_025131_add_indexes_to_posts_users_comments_tables.php`:

  | Tabel | Index |
  |---|---|
  | `posts` | `(tags, views)` B-tree |
  | `posts` | `(user_id)` |
  | `comments` | `(post_id)` |
  | `comments` | `(user_id)` |

## 1. Filter tag + sort views

```sql
EXPLAIN ANALYZE
SELECT * FROM posts
WHERE tags @> '["history"]'::jsonb
ORDER BY views DESC;
```

```text
Gather Merge  (cost=7447.97..9715.89 rows=19438 width=388) (actual time=17.506..26.356 rows=22456 loops=1)
  Workers Planned: 2
  Workers Launched: 2
  ->  Sort  (cost=6447.94..6472.24 rows=9719 width=388) (actual time=14.793..15.450 rows=7485 loops=3)
        Sort Key: views DESC
        Sort Method: quicksort  Memory: 3715kB
        Worker 0:  Sort Method: quicksort  Memory: 2834kB
        Worker 1:  Sort Method: quicksort  Memory: 2974kB
        ->  Parallel Seq Scan on posts  (cost=0.00..5804.22 rows=9719 width=388) (actual time=0.017..12.376 rows=7485 loops=3)
              Filter: (tags @> '["history"]'::jsonb)
              Rows Removed by Filter: 26065
Planning Time: 0.775 ms
Execution Time: 27.569 ms
```

**Analisis**: query dieksekusi **paralel** (2 worker) dengan `Sort` in-memory (`quicksort`) — tidak lagi membuang sort ke disk. Eksekusi turun ke **27.569 ms** (sebelumnya 57.612 ms). Operator masih `Seq Scan` paralel karena operator `@>` pada kolom `jsonb` tidak memanfaatkan B-tree `(tags, views)`; percepatan berasal dari paralelisme dan sort in-memory.

## 2. Artikel per user

```sql
EXPLAIN (ANALYZE, BUFFERS)
SELECT * FROM posts WHERE user_id = 1;
```

```text
Bitmap Heap Scan on posts  (cost=7.42..1254.49 rows=404 width=388) (actual time=0.072..2.068 rows=401 loops=1)
  Recheck Cond: (user_id = 1)
  Heap Blocks: exact=401
  Buffers: shared hit=406
  ->  Bitmap Index Scan on posts_user_id_index  (cost=0.00..7.32 rows=404 width=0) (actual time=0.041..0.042 rows=401 loops=1)
        Index Cond: (user_id = 1)
        Buffers: shared hit=5
Planning:
  Buffers: shared hit=156
Planning Time: 0.917 ms
Execution Time: 2.114 ms
```

**Analisis**: query kini memakai **`Bitmap Index Scan on posts_user_id_index`** — hanya 401 baris yang benar-benar dibaca, bukan full scan 100.250 baris. Eksekusi turun dari 15.279 ms menjadi **2.114 ms** (±7× lebih cepat).

## 3. Komentar per artikel

```sql
EXPLAIN (ANALYZE, BUFFERS)
SELECT * FROM comments WHERE post_id = 1;
```

```text
Seq Scan on comments  (cost=0.00..9.25 rows=3 width=83) (actual time=0.022..0.036 rows=3 loops=1)
  Filter: (post_id = 1)
  Rows Removed by Filter: 337
  Buffers: shared hit=5
Planning:
  Buffers: shared hit=137
Planning Time: 0.742 ms
Execution Time: 0.051 ms
```

**Analisis**: tabel `comments` masih kecil (340 baris), sehingga PostgreSQL memilih `Seq Scan` daripada index — keputusan yang benar. Eksekusi turun ke **0.051 ms**.

## Perbandingan sebelum vs sesudah index

| # | Query | Sebelum | Sesudah | Perbaikan |
|---|---|---|---|---|
| 1 | Filter tag + sort views | 57.612 ms | 27.569 ms | **↓ 52%** |
| 2 | Artikel per user | 15.279 ms | 2.114 ms | **↓ 86%** |
| 3 | Komentar per artikel | 0.143 ms | 0.051 ms | **↓ 64%** |

Data lengkap pengujian sebelum index: [before-index.md](before-index.md).
