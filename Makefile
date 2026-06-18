.PHONY: up down build restart migrate fresh tinker test horizon-logs bash

up:
	docker compose up -d

down:
	docker compose down

build:
	docker compose up -d --build

restart:
	docker compose restart

migrate:
	docker compose exec app php artisan migrate

fresh:
	docker compose exec app php artisan migrate:fresh --seed

tinker:
	docker compose exec app php artisan tinker

test:
	docker compose exec app php artisan test

horizon-logs:
	docker compose logs horizon

bash:
	docker compose exec app bash
