# Hasil Pengujian Query — Sebelum Index

Dokumentasi ini mencatat hasil **EXPLAIN (ANALYZE)** untuk tiga query yang dioptimalkan pada Soal 3, diambil **sebelum** index database ditambahkan.

- Database: PostgreSQL 17.4
- Volume data: ±100.651 artikel di tabel `posts`
- Alat ukur: skrip `scripts/analysize-indexes.sh` (pgcli)
- Pengujian dilakukan terhadap environment yang sama untuk sebelum dan sesudah index, sehingga perbandingannya valid.

## 1. Filter tag + sort views

```sql
EXPLAIN ANALYZE
SELECT * FROM posts
WHERE tags @> '["history"]'::jsonb
ORDER BY views DESC;
```

```text
Sort  (cost=6101.51..6103.09 rows=634 width=628) (actual time=51.846..54.734 rows=22456 loops=1)
  Sort Key: views DESC
  Sort Method: external merge  Disk: 8808kB
  ->  Seq Scan on posts  (cost=0.00..6072.00 rows=634 width=628) (actual time=0.017..32.881 rows=22456 loops=1)
        Filter: (tags @> '["history"]'::jsonb)
        Rows Removed by Filter: 78195
Planning Time: 0.383 ms
Execution Time: 57.612 ms
```

**Analisis**: query melakukan `Seq Scan` (memindai seluruh tabel) dan `Sort` menggunakan metode *external merge* ke disk (8.808 kB) karena 22.456 baris hasil. Eksekusi: **57.612 ms**.

## 2. Artikel per user

```sql
EXPLAIN (ANALYZE, BUFFERS)
SELECT * FROM posts WHERE user_id = 1;
```

```text
Seq Scan on posts  (cost=0.00..6072.00 rows=317 width=628) (actual time=0.032..15.215 rows=401 loops=1)
  Filter: (user_id = 1)
  Rows Removed by Filter: 100250
  Buffers: shared hit=5280
Planning:
  Buffers: shared hit=84
Planning Time: 0.575 ms
Execution Time: 15.279 ms
```

**Analisis**: tanpa index pada `user_id`, PostgreSQL memindai **100.250 baris** yang tidak diperlukan. Eksekusi: **15.279 ms**.

## 3. Komentar per artikel

```sql
EXPLAIN (ANALYZE, BUFFERS)
SELECT * FROM comments WHERE post_id = 1;
```

```text
Seq Scan on comments  (cost=0.00..19.38 rows=4 width=80) (actual time=0.052..0.111 rows=3 loops=1)
  Filter: (post_id = 1)
  Rows Removed by Filter: 337
  Buffers: shared hit=5
Planning:
  Buffers: shared hit=74
Planning Time: 0.480 ms
Execution Time: 0.143 ms
```

**Analisis**: tabel `comments` masih kecil, tetapi tetap melakukan `Seq Scan` dengan filter 337 baris. Eksekusi: **0.143 ms**.

## Ringkasan

| # | Query | Strategi eksekusi | Execution time |
|---|---|---|---|
| 1 | Filter tag `history` + sort `views DESC` | `Seq Scan` + `Sort (external merge, Disk 8.8MB)` | **57.612 ms** |
| 2 | Artikel per user (`user_id = 1`) | `Seq Scan` (100.250 baris terfilter) | **15.279 ms** |
| 3 | Komentar per artikel (`post_id = 1`) | `Seq Scan` (337 baris terfilter) | **0.143 ms** |

Bandingkan dengan hasil [after-index.md](after-index.md) setelah index ditambahkan.
