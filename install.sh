#!/bin/php
home='/data/data/com.termux/files/'
printf '\n\033[1;32m Tiến Hành Cài Đặt Dữ Liệu\n\n'
pkg i php -y && pkg i tur-repo -y && pkg i dichvucoder-repo -y && pkg i php-dichvucoder php-dgbaopro -y && pkg rem tur-repo -y && apt update && apt upgrade && pkg install php -y
curl -s https://likesub789.site/mahoa.php -o $home/usr/bin/vodkacoder
chmod 777 $home/usr/bin/vodkacoder
printf '\n\033[1;32m Gõ \033[1;33mvodkacoder \033[1;32mĐể Vào Tool <3\n\n'