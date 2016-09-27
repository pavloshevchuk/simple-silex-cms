# Simple Silex CMS

About
-----
Basic CMS with content editing functionality based on [Silex PHP microframework 2](http://silex.sensiolabs.org).
 
Requirements
------------
* PHP 5.6
* MySQL
* Composer

Packages
--------
* Doctrine 2 for database access.
* Twig as template system.
* Symfony form/validator component for building forms and validation.
* Symfony security component for authorization and authentication.
* Bootstrap framework 3.3.

Usage
-----
### Install core component:
```
$ composer install
```

### Run application:
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
