habitissimo-back-end-challenge
==============================

Delivery for the back end challenge of habitissimo.

## Requirements

In order to run this app you will need to have MySQL server and php installed.

## Installation

1. Remember to start mysql server daemon on system preferences.

2. Install dependencies using composer
```
php composer.phar install
```
3. Create DB schema (using doctrine)
```
bin/console doctrine:schema:create
```

## Start up

Start the synfony server by runing:
```
php bin/console server:run
```

### Test

You can run the functional tests using te following command from the root folder:
````
vendor/bin/simple-phpunit
````