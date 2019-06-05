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
echo 'Сейчас надо будет заснуть на 40 секунд, чтобы успела развернуться postgres-ка'
sleep 5
echo 'Осталось еще 35 секунд...'
sleep 5
echo 'Осталось еще 30 секунд...'
sleep 5
echo 'Осталось еще 25 секунд...'
sleep 5
echo 'Осталось еще 20 секунд...'
sleep 5
echo 'Осталось еще 15 секунд...'
sleep 5
echo 'Осталось еще 10 секунд...'
sleep 5
echo 'Осталось еще 5 секунд...'
sleep 5
echo 'Сон завершился. По идее postgres-ка уже поднялась и сейчас мы будем закачивать дамп!'


