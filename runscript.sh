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

chmod 776 expire_inactive_users.php
chmod 776 inactivations.php

php inactivations.php

if [ -s "vu_inactives.zip" ]; then
  sleep 10
  unzip vu_inactives.zip
  chmod 776 ils_student_inactive_export.xml
  sleep 10
  php expire_inactive_users.php sandbox vu
  sleep 10
  mv vu_inactives.zip user_data/Archive/vu_inactives_$datevar.zip
  sleep 10
  rm ils_student_inactive_export.xml
fi

if [ -s "vumc_inactives.zip" ]; then
  sleep 10
  unzip vumc_inactives.zip
  chmod 776 en_library_inactivate.medc.xml
  sleep 10
  php expire_inactive_users.php sandbox vumc
  sleep 10
  mv vumc_inactives.zip user_data/Archive/vumc_inactives_$datevar.zip
  sleep 10
  rm en_library_inactivate.medc.xml
fi