DOCKER_COMPOSE != which docker-compose
RUN = $(DOCKER_COMPOSE) run --rm
EXEC = $(DOCKER_COMPOSE) exec
USER != echo `id -u`:`id -g`

BASH = $(if $(DOCKER_COMPOSE), $(EXEC) web,)
CONSOLE ?= $(BASH) bin/console

WRITABLE_DIRS = var/cache var/logs var/sessions var/imports


# IMAGE

build: env up npm composer cs-fix
	$(DOCKER_COMPOSE) build web

push:
	docker push pakumaz/spending-monitor

up:
	$(DOCKER_COMPOSE) up -d web

env:
	cp --no-clobber .env.dist .env

bash: up
	$(EXEC) web /bin/bash

npm:
	$(RUN) -u $(USER) npm

composer:
	$(RUN) -u $(USER) composer

cs-fix:
	$(RUN) web bin/php-cs-fixer fix

setfacl:
	setfacl -RL -m u:www-data:rwX -m u:`whoami`:rwX $(WRITABLE_DIRS)
	setfacl -dRL -m u:www-data:rwX -m u:`whoami`:rwX $(WRITABLE_DIRS)

clean:
	$(RUN) web rm -rf node_modules vendor var/cache/* var/logs/* var/sessions/* web/vendor


# APP


init: cache database-init fixtures

update: database-update

cache: cache-clear writable
cache-clear:
	$(CONSOLE) cache:clear --env=prod
	$(CONSOLE) cache:clear --env=dev

writable:
	$(BASH) chown -R www-data:www-data $(WRITABLE_DIRS)

database-init:
	$(CONSOLE) doctrine:database:drop --force --if-exists
	$(CONSOLE) doctrine:database:create
	$(CONSOLE) doctrine:schema:create

database-update:
	$(CONSOLE) doctrine:migration:migrate --no-interaction --allow-no-migration

fixtures:
	$(CONSOLE) doctrine:fixtures:load --no-interaction

xdbon:
	$(BASH) ln -sf /usr/local/etc/php/mods-available/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini
	$(if $(DOCKER_COMPOSE), $(DOCKER_COMPOSE) restart web)

xdboff:
	$(BASH) rm -f /usr/local/etc/php/conf.d/xdebug.ini
	$(if $(DOCKER_COMPOSE), $(DOCKER_COMPOSE) restart web)