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

echo -en '\n'



