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
