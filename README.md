# pitchoun

[![Build status][ico-travis]][link-travis]

A simple URL shortener.

## Installation ##

Requirements: composer, npm.

```sh
composer install --optimize-autoloader --prefer-dist --no-dev
npm install
```

## Configuration ##

Edit `app/config/parameters.yml`.

## API usage ##

These examples assume the app is running on `yourdomain.test`.

To shorten a URL:

```sh
curl -s "http://yourdomain.test/api/shorten?url=http://www.example.com/"
# {"url":"http:\/\/yourdomain.test\/1"}
```

To get the original URL from a short URL:

```sh
curl -s "http://yourdomain.test/api/lengthen?url=http://www.example.com/"
# {"url":"http:\/\/yourdomain.test\/1"}
```

[ico-travis]: https://api.travis-ci.org/michelv/pitchoun.svg?branch=master
[link-travis]: https://travis-ci.org/nrk/predis
