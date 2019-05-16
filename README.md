[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/carono/film-janitor/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/carono/film-janitor/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/carono/film-janitor/v/stable)](https://packagist.org/packages/carono/film-janitor)
[![Total Downloads](https://poser.pugx.org/carono/film-janitor/downloads)](https://packagist.org/packages/carono/film-janitor)
[![License](https://poser.pugx.org/carono/film-janitor/license)](https://packagist.org/packages/carono/film-janitor)
[![Build Status](https://travis-ci.org/carono/yii2-migrate.svg?branch=master)](https://travis-ci.org/carono/film-janitor)
[![CodeFactor](https://www.codefactor.io/repository/github/carono/film-janitor/badge)](https://www.codefactor.io/repository/github/carono/film-janitor)

### Уборщик фильмов и сериалов
Переименовывает файлы с фильмами и сериалами с помощью поиска в интернете

### Установка
Установите пакет глобально
>composer global require carono/film-janitor

в PATH добавте %APPDATA%\Composer\vendor\bin чтобы запускать команду 

Установите настройки поискового движка (Yandex XML по умолчанию)
> janitor set-engine-options

### Запуск
> janitor -d "d:\folder_with_films"

![](https://raw.githubusercontent.com/carono/film-janitor/master/screencast.gif)