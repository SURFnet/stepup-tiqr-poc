run: vendor
	php -S localhost:8080 -t www

test:
	curl http://localhost:8080/sp/metadata

composer.phar:
	curl -s https://getcomposer.org/installer | php

composer.json:
	php composer.phar require "silex/silex:~1.2"
	php composer.phar require "twig/twig:1.*"
	php composer.phar require "fr3d/xmlseclibs: ~1.3"


vendor: composer.phar
	php composer.phar install

realclean:
	rm composer.phar composer.lock
	rm -r vendor

### Vagrant

Vagrantfile:
	vagrant init chef/debian-7.4

