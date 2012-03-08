# !/bin/bash
#

wget http://pecl.php.net/get/APC-3.1.9.tgz && \
	tar zxvf APC-3.1.9.tgz && \
	cd APC-3.1.9/ && \
	phpize && \
	./configure && make && make install && \
	echo "extension=\"apc.so\"" >> `php --ini | grep "Loaded Configuration" | sed -e "s|.*:\s*||"`

wget http://pecl.php.net/get/igbinary-1.1.1.tgz && \
	tar zxvf igbinary-1.1.1.tgz && cd igbinary-1.1.1 && \
	phpize && ./configure CFLAGS="-O2 -g" --enable-igbinary && \
	make && make install && \
	echo "extension=\"igbinary.so\"" >> `php --ini | grep "Loaded Configuration" | sed -e "s|.*:\s*||"`

pyrus install http://phptal.org/latest.tar.gz
phpenv rehash

pear channel-discover pear.phing.info
pear remote-list -c phing
pear install phing/phing
