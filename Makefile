.PHONY: migrate-refresh import-dummyjson multiply-post scout-import all

migrate-refresh:
	php artisan migrate:refresh

import-dummyjson:
	php artisan import:dummyjson

multiply-post:
	php artisan posts:multiply 400

scout-import:
	php artisan scout:import "App\Models\Post"

all: migrate-refresh import-dummyjson multiply-post scout-import
