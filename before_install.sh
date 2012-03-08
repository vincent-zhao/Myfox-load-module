# !/bin/bash
#

# install apc
wget http://pecl.php.net/get/APC-3.1.9.tgz && \
	tar zxvf APC-3.1.9.tgz && \
	cd APC-3.1.9/ && \
	phpize && \
	./configure && make && make install && \
	echo "extension=\"apc.so\"" >> `php --ini | grep "Loaded Configuration" | sed -e "s|.*:\s*||"`

# install igbinary
wget http://pecl.php.net/get/igbinary-1.1.1.tgz && \
	tar zxvf igbinary-1.1.1.tgz && cd igbinary-1.1.1 && \
	phpize && ./configure CFLAGS="-O2 -g" --enable-igbinary && \
	make && make install && \
	echo "extension=\"igbinary.so\"" >> `php --ini | grep "Loaded Configuration" | sed -e "s|.*:\s*||"`

# install phing
mkdir phing && cd phing && \
	wget http://www.phing.info/get/phing-2.4.9.tgz && \
	tar zxvf phing-2.4.9.tgz && cd - 
