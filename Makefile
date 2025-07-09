container = "laravel"

exec:
	docker-compose exec $(container) bash

refresh:
	docker-compose exec $(container) bash -c \
	"php artisan migrate:fresh && \
	php artisan db:seed"

build:
	@echo Building all containers
	docker-compose build

start:
	@echo Starting all containers
	docker-compose up -d

stop:
	@echo Stopping all containers
	docker-compose down

logs:
	docker-compose logs -f

status: ps

ps:
	@echo Getting status of all containers
	docker-compose ps

bash:
	@echo Attaching to $(container)
	docker-compose exec -it $(container) /bin/bash

sh:
	@echo Attaching to $(container)
	docker-compose exec -it $(container) /bin/sh