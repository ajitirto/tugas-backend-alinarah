## Tugas Backend Al-Inarah


### Testing REST API

Testing endpoint REST API dapat dilakukan menggunakan file:

```bash 
api-test.http

```

### Dokumentasi Database Index

Dokumentasi perbandingan performa query sebelum dan sesudah penambahan index tersedia di:

docs/before-index.md — hasil pengujian sebelum penambahan index.
docs/after-index.md — hasil pengujian setelah penambahan index.


Endpoint GET /api/posts?tag=history&sort=-views harus merespons di bawah 200 ms.


```bash
http GET :8000/api/posts tag==history sort==-views 

0,15s user 0,04s system 50% cpu 0,386 total
```


### Alasan Memilih Meilisearch

Saya menggunakan Meilisearch sebagai search engine karena kebutuhan utama soal adalah pencarian artikel berdasarkan `title` dan `body`, mendukung toleransi terhadap kesalahan pengetikan, serta memberikan response time di bawah 100 ms pada dataset sekitar 100.651 artikel.

Meilisearch dipilih karena:

1. **Mendukung typo tolerance secara built-in**, sehingga pencarian seperti `histroy` tetap dapat menemukan artikel yang mengandung kata `history`.
2. **Cepat untuk full-text search**, sehingga lebih sesuai untuk kebutuhan pencarian dibandingkan melakukan pencarian langsung menggunakan `LIKE` pada PostgreSQL.
3. **Mudah dijalankan menggunakan Docker Compose**, sehingga environment search engine dapat direproduksi dengan mudah.
4. **Integrasi dengan Laravel Scout** cukup sederhana dan tidak membutuhkan implementasi search engine secara manual.
5. **Mendukung asynchronous indexing**, sehingga proses update index dapat dilakukan melalui Laravel Queue tanpa memperlambat request pembuatan artikel.
6. Data yang di-index hanya field yang dibutuhkan untuk pencarian, yaitu `id`, `title`, dan `body`.

Arsitektur pencarian:

```text
Laravel API
    │
    ├── PostgreSQL
    │      └── Source of Truth
    │
    └── Laravel Queue
            │
            ▼
       Meilisearch
            │
            ▼
       GET /api/search?q=
```

Dengan pendekatan ini, PostgreSQL tetap menjadi sumber data utama, sedangkan Meilisearch digunakan sebagai search index untuk mempercepat proses pencarian dan menyediakan typo tolerance.


http GET :8000/api/search q==history  0,16s user 0,04s system 62% cpu 0,316 tot
al
➜  tugas-backend-alinarah git:(4-pencarian-artikel) ✗ time http GET :8000/api/s
earch q==history


http GET :8000/api/search q==histroy  0,17s user 0,03s system 58% cpu 0,335 tot
al
➜  tugas-backend-alinarah git:(4-pencarian-artikel) ✗ time http GET :8000/api/s
earch q==histroy



➜  tugas-backend-alinarah git:(4-pencarian-artikel) ✗ for i in {1..10}; do
    time http GET 'http://localhost:8000/api/search?q=history' > /dev/null
done
http GET 'http://localhost:8000/api/search?q=history' > /dev/null  0,15s user 0,04s system 56% cpu 0,332 total
http GET 'http://localhost:8000/api/search?q=history' > /dev/null  0,15s user 0,03s system 60% cpu 0,300 total
http GET 'http://localhost:8000/api/search?q=history' > /dev/null  0,17s user 0,01s system 61% cpu 0,300 total
http GET 'http://localhost:8000/api/search?q=history' > /dev/null  0,16s user 0,02s system 61% cpu 0,306 total
http GET 'http://localhost:8000/api/search?q=history' > /dev/null  0,14s user 0,04s system 61% cpu 0,298 total
http GET 'http://localhost:8000/api/search?q=history' > /dev/null  0,16s user 0,02s system 61% cpu 0,301 total
http GET 'http://localhost:8000/api/search?q=history' > /dev/null  0,13s user 0,05s system 61% cpu 0,299 total
http GET 'http://localhost:8000/api/search?q=history' > /dev/null  0,16s user 0,02s system 60% cpu 0,297 total
http GET 'http://localhost:8000/api/search?q=history' > /dev/null  0,14s user 0,04s system 60% cpu 0,303 total
http GET 'http://localhost:8000/api/search?q=history' > /dev/null  0,16s user 0,02s system 60% cpu 0,300 total


Endpoint	Rata-rata
/api/ping	271 ms
/api/search?q=history	324 ms
Selisih	~52 ms

masih belum dalam <100 ms 


Benchmark Endpoint

Endpoint /api/search menggunakan Meilisearch sebagai search engine untuk melakukan pencarian pada data artikel.

Hasil pengujian performa menggunakan HTTPie:

Endpoint	Rata-rata
/api/ping	271 ms
/api/search?q=history	324 ms
Overhead search	~53 ms

Target soal adalah endpoint pencarian merespons di bawah 100 ms.

Berdasarkan hasil benchmark saat ini, endpoint /api/search masih belum memenuhi target performa <100 ms.

Status:  Belum memenuhi target <100 ms

Search Engine

Pencarian artikel dilakukan menggunakan Meilisearch, sehingga query pencarian tidak dilakukan secara langsung menggunakan LIKE pada PostgreSQL.

Flow pencarian:

Client
  ↓
GET /api/search?q=history
  ↓
Laravel API
  ↓
Laravel Scout
  ↓
Meilisearch
  ↓
Search Results

Selisih waktu antara endpoint /api/ping dan /api/search sekitar 53 ms. Hasil ini menunjukkan adanya overhead tambahan dari proses pencarian melalui Laravel → Scout → Meilisearch.

Catatan: hasil benchmark dapat dipengaruhi oleh environment pengujian seperti Docker, web server, PHP/Laravel, koneksi ke Meilisearch, dan kondisi mesin saat benchmark dilakukan.


➜  tugas-backend-alinarah git:(5-statistuk-cache-redis) ✗ http GET :8000/api/stats/top-authors
HTTP/1.1 200 OK
Access-Control-Allow-Origin: *
Cache-Control: no-cache, private
Connection: close
Content-Type: application/json
Date: Tue, 15 Sep 2026 08:59:08 GMT
Host: localhost:8000
X-Cache: HIT
X-Powered-By: PHP/8.4.1

[
    {
        "total_views": 5390242,
        "user_id": 126,
        "username": "averya"
    },
    {
        "total_views": 4469145,
        "user_id": 150,
        "username": "stellas"
    },
    {
        "total_views": 4135914,
        "user_id": 83,
        "username": "dylanw"
    }
]




http GET :8000/api/stats/top-tags   
HTTP/1.1 200 OK
Access-Control-Allow-Origin: *
Cache-Control: no-cache, private
Connection: close
Content-Type: application/json
Date: Tue, 15 Sep 2026 09:00:05 GMT
Host: localhost:8000
X-Cache: HIT
X-Powered-By: PHP/8.4.1

[
    {
        "count": 22456,
        "tag": "history"
    },
    {
        "count": 19248,
        "tag": "english"
    },
    {
        "count": 18847,
        "tag": "american"
    },
    {
        "count": 18847,
        "tag": "classic"
    },
    {
        "count": 17644,
        "tag": "love"
    }
]





