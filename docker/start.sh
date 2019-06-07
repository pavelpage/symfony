#!/usr/bin/env bash

green=$(tput setf 2)
toend=$(tput hpa $(tput cols))$(tput cub 6)

echo 'Now, let start working with docker project!'
docker-compose up -d || exit
echo -en '\n'
echo -n "Docker was composed successfully! ${green}${toend}[OK]"
echo -en '\n'
echo 'And now installing composer dependencies.'
./composer-install.sh
echo -en '\n'
echo -n "Dependencies were installed ${green}${toend}[OK]"

cd ../

# setting small fix to allow upload files
chmod 777 public
chmod +x bin/console

echo -en '\n'
echo 'Runnin migrations and loading fixtures'
docker exec php-fpm php bin/console doctrine:migrations:migrate


echo -en '\n'
docker exec php-fpm php bin/console doctrine:fixtures:load

echo -en '\n'
echo 'Everything is ok'
