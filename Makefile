.PHONY: migrate-refresh import-dummyjson refresh-import

migrate-refresh:
	php artisan migrate:refresh

import-dummyjson:
	php artisan import:dummyjson

refresh-import:
	php artisan migrate:refresh
	php artisan import:dummyjson
