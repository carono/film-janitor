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