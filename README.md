## Tugas Backend Al-Inarah


### Testing REST API

Testing endpoint REST API dapat dilakukan menggunakan file:

```bash 
api-test.http

```

### Import Data DummyJSON

Untuk melakukan import data dari DummyJSON:

```bash

php artisan import:dummyjson
```

Proses import menggunakan Laravel Queue Batch, sehingga queue worker harus dijalankan terlebih dahulu.

Buka terminal baru dan jalankan:

```bash

php artisan queue:work
```

Kemudian pada terminal lainnya jalankan:

```bash
php artisan import:dummyjson
```

