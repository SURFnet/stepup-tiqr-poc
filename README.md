stepup-tiqr-poc
===============

	git clone https://github.com/SURFnet/stepup-tiqr-poc.git
	cd stepup-tiqr-poc

Many shortcuts:

- no dependencies except the tiqr libraries
- uses google chart api instead of phpqrencode (bad idea except for testing/demo purposes!)
- using error_log for logging


Install
===

        curl -sS https://getcomposer.org/installer | php
        ./composer.phar install

Run from the command line using PHP 5.4+ built-in HTTP server

	php -dinclude_path=`pwd`/vendor/joostd/tiqr-server/libTiqr/library/tiqr -S ip:port -t www

where ip is an IP address you're tiqr client can connect to (127.0.0.1 won't do if you want to use the tiqr app). Port is typically 8080 (80 requires root).

Use dump.sh to monitor state (when using the file stateStorage)

Ansible
===

	ansible all -m shell -a 'hostname'

	ansible -i .vagrant/provisioners/ansible/inventory/vagrant_ansible_inventory  --private-key=~/.vagrant.d/insecure_private_key -u vagrant all -m ping
	ansible -i ansible/inventory/stepup --private-key=~/.ssh/openstack.pem -u debian all -m ping 

Vagrant
===

	vagrant up
	ssh-add ~/.vagrant.d/insecure_private_key 

