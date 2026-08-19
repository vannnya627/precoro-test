### Clone project:
```
git clone https://github.com/vannnya627/precoro-test.git
```
---
### Start Docker containers:
```
docker compose up -d
```
---
### Install PHP dependencies:
```
docker compose exec -u www-data -w /var/www/project php-fpm composer install
```
---
### Generate JWT keys:
```
docker compose exec php-fpm php bin/console lexik:jwt:generate-keypair --skip-if-exists
```
---
### Run database migrations:
```
docker compose exec php-fpm php bin/console doctrine:migrations:migrate --no-interaction
```
---
### Check the documentation: http://localhost/api/doc or http://localhost/api/doc.json 
### Check Symfony Profiler: http://localhost/_profiler

### Before running "Functional Tests" for the first time, you need to prepare a test database:

### MySQL:
```
docker compose exec php-fpm php bin/console doctrine:database:create --env=test --if-not-exists
```
```
docker compose exec php-fpm php bin/console doctrine:migrations:migrate --env=test --no-interaction
```
---
### Run tests:
```
docker compose exec php-fpm php bin/phpunit 
```
### PCOV(one of command):

```
 docker compose exec php-fpm php -d pcov.enabled=1 vendor/bin/phpunit tests/ --coverage-html coverage_report/
```
### or
```
docker compose exec php-fpm php -d pcov.enabled=1 vendor/bin/phpunit tests/ --coverage-text
```
### Php-cs-fixer:
```
docker compose exec php-fpm ./vendor/bin/php-cs-fixer fix
```
### PhpStan:
```
docker compose exec php-fpm ./vendor/bin/phpstan analyze --memory-limit 256M
```
