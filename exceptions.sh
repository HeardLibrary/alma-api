#!/bin/bash

HOSTNAME=libvm15.library.vanderbilt.edu; export HOSTNAME
SHELL=/bin/bash; export SHELL
TERM=xterm; export TERM
USER=root; export USER
LD_LIBRARY_PATH=/opt/remi/php74/root/usr/lib64; export LD_LIBRARY
PATH=/usr/local/rvm/gems/ruby-2.6.3/bin:/usr/local/rvm/gems/ruby-2.6.3@global/bin:/usr/local/rvm/rubies/ruby-2.6.3/bin:/usr/lib64/qt-3.3/bin:/opt/remi/php74/root/usr/bin:/opt/remi/php74/root/usr/sbin:/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin:/usr/local/rvm/bin:/root/bin; export PATH
LANG=en_US.UTF-8; export LANG

datevar=$(date +"%Y%m%d")

cd /apps/alma/alma-api

chmod 776 sync_exception_staff_json.php 

php sync_exception_staff_json.php

