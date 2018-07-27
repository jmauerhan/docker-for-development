# Project Startup

##### public/index.php
```php
<?php
header('Access-Control-Allow-Origin: *');

$current = date('H:i:s \o\n l, F jS Y');
$result  = (object)[
    'message' => 'Hello! It is ' . $current
];

echo json_encode($result);
```

##### Run from project directory:
```sh
docker run -d -v .:/var/www -p 8080:80 php:latest php -S 0.0.0.0:80 -t /var/www/public
```
(note the container id in the output)

##### View Site:
http://localhost:8080

##### Kill running container
```sh
docker kill (container id)
```

### Dockerfile
To include PDO/Postgres

```
#Dockerfile

FROM php:latest

RUN apt-get update && apt-get install -y libpq-dev
RUN docker-php-ext-install pgsql pdo pdo_pgsql

CMD php -S 0.0.0.0:80 -t /var/www/public 
```

```sh
docker build .
```
(note container id)

```sh
docker run -d -v .:/var/www -p 8080:80 (container id)
```

##### Tag Image
```sh
docker build . -t jmauerhan/demo:latest
```

##### View Images
```sh
docker images
```

##### Run from tagged image
```sh
docker run -d -v .:/var/www -p 8080:80 jmauerhan/demo 
```

### Docker-Compose

##### docker-compose.yml
```yml
version: '3'
services:
 api:
   build:
     context: .
   volumes:
     - .:/var/www
   ports:
     - "8080:80"
   environment:
     DB_USERNAME: postgres
     DB_PASSWORD: postgres
     DB_DATABASE: app
     DB_HOST: db
 db:
   image: postgres:latest
   ports:
     - "5001:5432"
   environment:
     POSTGRES_USER: postgres
     POSTGRES_PASSWORD: postgres
     POSTGRES_DB: app
```

##### Bring up docker-compose network
```sh
docker-compose up -d 
```

### Installing Composer & Dependencies

##### Dockerfile
```
#Dockerfile

FROM php:latest

RUN apt-get update && apt-get install -y libpq-dev zip unzip
RUN docker-php-ext-install pgsql pdo pdo_pgsql
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www

CMD php -S 0.0.0.0:80 -t /var/www/public
```

##### Rebuild
```sh
docker-compose down
docker-compose build
docker-compose up -d
docker-compose exec api bash
composer install
```

##### phinx.yml
```yml
paths:
    migrations: '%%PHINX_CONFIG_DIR%%/db/migrations'
    seeds: '%%PHINX_CONFIG_DIR%%/db/seeds'

environments:
    default_migration_table: phinxlog
    default_database: development

    development:
        adapter: pgsql
        host: db
        name: app
        user: postgres
        pass: postgres
        port: 5432
        charset: utf8

version_order: creation
```

##### Run phinx commands on running container
```sh
vendor/bin/phinx migrate
vendor/bin/phinx seed:run
```

##### Update index.php
```php
<?php
header('Access-Control-Allow-Origin: *');

$current = date('H:i:s \o\n l, F jS Y');
$result  = (object)[
    'message' => 'Hello! It is ' . $current
];

$user = $_ENV['DB_USERNAME'];
$pass = $_ENV['DB_PASSWORD'];
$db   = $_ENV['DB_DATABASE'];
$host = $_ENV['DB_HOST'];
$dsn  = "pgsql:host={$host} user={$user} password={$pass} dbname={$db}";
$pdo  = new PDO($dsn);

$users = [];
$sql   = "SELECT * FROM users";
$row   = $pdo->query($sql);
if ($row !== false) {
    $users = $row->fetchAll(PDO::FETCH_COLUMN);
}

$result->users = $users;

echo json_encode($result);
```
##### View Site:
http://localhost:8080

### Containers are ephemeral
##### Run commands
```sh
docker-compose down
docker-compose up
```
##### View Site:
http://localhost:8080

##### Create Volume
```sh
docker-compose down
docker volume create --name pgdata -d local
```

##### docker-compose.yml
```yml
version: '3'
services:
 api:
   build:
    context: .
   volumes:
    - .:/var/www
   ports:
     - "8080:80"
   environment:
     DB_USERNAME: postgres
     DB_PASSWORD: postgres
     DB_DATABASE: app
     DB_HOST: db
 db:
   image: postgres:latest
   ports:
     - "5001:5432"
   environment:
     POSTGRES_USER: postgres
     POSTGRES_PASSWORD: postgres
     POSTGRES_DB: app
   volumes:
     - pgdata:/var/lib/postgresql/data
volumes:
  pgdata:
    external: true
```

##### run commands
```sh
docker-compose up -d
docker-compose exec api bash
vendor/bin/phinx migrate
vendor/bin/phinx seed:run
```
##### View Site:
http://localhost:8080

### Commit Vendors & Code for deploy
##### Dockerfile
```
FROM php:latest

RUN apt-get update && apt-get install -y libpq-dev libpq-dev zip unzip
RUN docker-php-ext-install pgsql pdo pdo_pgsql
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

ADD composer.json composer.lock /var/www/
WORKDIR /var/www
RUN composer install
ADD . .

CMD php -S 0.0.0.0:80 -t /var/www/public
```
##### run commands
```sh
docker-compose down
docker build .
```

### Push to Repo
```sh
docker run -d -p 5000:5000 --name registry registry:2
docker build . -t localhost:5000/jmauerhan/demo:beta
docker push localhost:5000/jmauerhan/demo:beta
docker images
```

##### Start New Project for Front-End

##### docker-compose.yml
```yml
#docker-compose.yml

version: '3'
services:
  front-end:
    image: php:latest
    volumes:
      - .:/var/www
    ports:
      - "8080:80"
    command: php -S 0.0.0.0:80 -t /var/www/public
  api:
    image: localhost:5000/jmauerhan/demo:beta
    ports:
      - "3001:80"
    environment:
      DB_USERNAME: postgres
      DB_PASSWORD: postgres
      DB_DATABASE: app
      DB_HOST: db
  db:
    image: postgres:latest
    environment:
      POSTGRES_USER: postgres
      POSTGRES_PASSWORD: postgres
      POSTGRES_DB: app
```

##### run commands
```sh
docker-compose up -d
docker-compose exec api bash
vendor/bin/phinx migrate
vendor/bin/phinx seed:run
```

##### View API:
http://localhost:3001
##### View Site:
http://localhost:8080
