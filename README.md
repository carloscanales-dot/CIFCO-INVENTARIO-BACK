<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Instala las tecnologias

```sh
php 8.2.29
composer 2.5.5 
```

## Instala las librerias en la ruta de proyecto

```sh
composer install
```

## copia el .env 

```sh
cp .env.example .env
```

## Modifica las variables de la Base de Datos del .env

```sh
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=name_database
DB_USERNAME=user_database
DB_PASSWORD=password_database
```

## Genera la llave

```sh
php artisan key:generate
```
## Genera el jwt

```sh
php artisan jwt:secret
```
## Levanta el sistema en ambiente desarrollo

```sh
php artisan serve
```
