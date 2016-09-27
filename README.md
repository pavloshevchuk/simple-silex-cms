# Simple Silex CMS

General
-------
Install core component:
```
$ composer install
```

Application
-----------
Run application:
```
$ php -S localhost:8000 -t public
```

Doctrine
--------
Generate entities:
```
$ php bin/doctrine-console orm:generate-entities "./src" --profile=mysql
```

Update schema:
```
$ php bin/doctrine-console orm:schema-tool:update --force --profile=mysql
```
