stepup-tiqr-poc
===============

Simple demo project explaining the use of the tiqr php library.

Many shortcuts:

- no dependencies except the tiqr libraries
- uses google chart api instead of phpqrencode (bad idea except for testing/demo purposes!)
- using error_log for logging

Goal is to have a simple tiqr demo that can be run with the php builtin web server.

Install
===

        curl -sS https://getcomposer.org/installer | php
        ./composer.phar install

Run from the command line using PHP 5.4+ built-in HTTP server

	php -dinclude_path=`pwd`/vendor/joostd/tiqr-server/libTiqr/library/tiqr -S ip:port -t www

where ip is an IP address you're tiqr client can connect to (127.0.0.1 won't do if you want to use the tiqr app). Port is typically 8080 (80 requires root).

Use dump.sh to monitor state (when using the file stateStorage)

Doesn't use more advanced features, like push notifications and step-up authentication.

Command Line Client
===

Alternatively, simulate the tiqr app using curl (in which case you can use localhost), eg:

curl http://192.0.2.1:8080/enrol.php --data uid=jd --data displayName=John+Doe
{"service":{"displayName":"tiqr demo","identifier":"192.0.2.1","logoUrl":"https:\/\/demo.tiqr.org\/img\/tiqrRGB.png","infoUrl":"https:\/\/www.tiqr.org","authenticationUrl":"http:\/\/192.0.2.1:8080\/tiqr.php","ocraSuite":"OCRA-1:HOTP-SHA1-6:QH10-S","enrollmentUrl":"http:\/\/192.0.2.1:8080\/tiqr.php?otp=41deaf45869f0144a9d4c0272d6a09a4504d53b4e03707011ca19a33bf1d332c"},"identity":{"identifier":"1","displayName":"1"}}


	$ curl http://localhost:8080/newuser.php
	 --data uid=1 --data displayName=1
	 --data sid=1
	 
	$ curl http://localhost:8080/tiqr.php -G --data key=41b4157315d02bbec5b4bb7dadd09fb13181c33fa4436afd4c1552f88ae6fce7

	curl
	  --data operation=register
	  --data secret=3132333435363738393031323334353637383930313233343536373839303132
	   http://localhost:8080/tiqr.php?otp=50d3bc969f5c4975143ecaf27e3cc30451a2c9fcb673828ddd60b46d327e0bb9

	cat /tmp/1.json 
