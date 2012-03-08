# !/bin/bash
#

wget http://pecl.php.net/get/APC-3.1.9.tgz && \
	tar zxvf APC-3.1.9.tgz && \
	cd APC-3.1.9/ && \
	phpize && \
	./configure && make && make install && \
	echo "extension=\"apc.so\"" >> `php --ini | grep "Loaded Configuration" | sed -e "s|.*:\s*||"`

pyrus install http://phptal.org/latest.tar.gz
phpenv rehash

pyrus install pear/PHP_CodeSniffer
phpenv rehash

pyrus install pear/phing
phpenv rehash

