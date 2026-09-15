.PHONY: migrate-refresh import-dummyjson multiply-post all

migrate-refresh:
	php artisan migrate:refresh

import-dummyjson:
	php artisan import:dummyjson

multiply-post:
	php artisan posts:multiply 400

all: migrate-refresh import-dummyjson multiply-post
