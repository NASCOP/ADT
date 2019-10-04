#!/usr/bin/env bash

# BEGIN ########################################################################
echo -e "-- ------------------ --\n"
echo -e "-- BEGIN BOOTSTRAPING --\n"
echo -e "-- ------------------ --\n"

# VARIABLES ####################################################################
echo -e "-- Setting global variables\n"
APACHE_CONFIG=/etc/apache2/apache2.conf
PHP_INI=/etc/php5/apache2/php.ini
SITES_ENABLED=/etc/apache2/sites-enabled
PHPMYADMIN_CONFIG=/etc/phpmyadmin/config-db.php
DOCUMENT_ROOT=/var/www/html
APPLICATION_HOST=localhost
VIRTUAL_HOST=localhost
MYSQL_DATABASE=testadt
MYSQL_USER=root
MYSQL_PASSWORD=root

# BOX ##########################################################################
echo -e "-- Updating packages list\n"
apt-get update -y -qq
apt-get upgrade -qq
apt-get install -y python-software-properties build-essential vim curl git
apt-get autoremove -y -qq

# APACHE #######################################################################
echo -e "-- Installing Apache web server\n"
apt-get install -y apache2 > /dev/null 2>&1
apt-get install php5-curl > /dev/null 2>&1

echo -e "-- Adding ServerName to Apache config\n"
grep -q "ServerName ${VIRTUAL_HOST}" "${APACHE_CONFIG}" || echo "ServerName ${VIRTUAL_HOST}" >> "${APACHE_CONFIG}"

sudo a2enmod rewrite

echo -e "-- Allowing Apache override to all\n"
sed -i "s/AllowOverride None/AllowOverride All/g" ${APACHE_CONFIG}

echo -e "-- Updating vhost file\n"
cat > ${SITES_ENABLED}/000-default.conf <<EOF
<VirtualHost *:80>
    ServerName ${VIRTUAL_HOST}
    DocumentRoot ${DOCUMENT_ROOT}

    <Directory ${DOCUMENT_ROOT}>
        Options Indexes FollowSymlinks
        AllowOverride All
        Order allow,deny
        Allow from all
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/${VIRTUAL_HOST}-error.log
    CustomLog ${APACHE_LOG_DIR}/${VIRTUAL_HOST}-access.log combined
</VirtualHost>
EOF

echo -e "-- Restarting Apache web server\n"
service apache2 restart

# MYSQL ########################################################################
echo -e "-- Installing MySQL server\n"
debconf-set-selections <<< "mysql-server mysql-server/root_password password ${MYSQL_PASSWORD}"
debconf-set-selections <<< "mysql-server mysql-server/root_password_again password ${MYSQL_PASSWORD}"

echo -e "-- Installing MySQL packages\n"
apt-get install -y mysql-server > /dev/null 2>&1
apt-get install -y libapache2-mod-auth-mysql > /dev/null 2>&1
apt-get install -y php5-mysql > /dev/null 2>&1

echo -e "-- Setting up a dummy MySQL database\n"
mysql -u${MYSQL_USER} -p${MYSQL_PASSWORD} -h ${APPLICATION_HOST} -e "CREATE DATABASE IF NOT EXISTS ${MYSQL_DATABASE}"

# PHPMYADMIN ###################################################################
echo -e "-- Installing phpMyAdmin GUI\n"
debconf-set-selections <<< "phpmyadmin phpmyadmin/dbconfig-install boolean true"
debconf-set-selections <<< "phpmyadmin phpmyadmin/app-password-confirm password ${MYSQL_PASSWORD}"
debconf-set-selections <<< "phpmyadmin phpmyadmin/mysql/admin-pass password ${MYSQL_PASSWORD}"
debconf-set-selections <<< "phpmyadmin phpmyadmin/mysql/app-pass password ${MYSQL_PASSWORD}"
debconf-set-selections <<< "phpmyadmin phpmyadmin/reconfigure-webserver multiselect apache2"

echo -e "-- Installing phpMyAdmin package\n"
apt-get install -y phpmyadmin > /dev/null 2>&1

echo -e "-- Setting up phpMyAdmin GUI login user\n"
sed -i "s/dbuser='phpmyadmin'/dbuser='${MYSQL_USER}'/g" ${PHPMYADMIN_CONFIG}

echo -e "-- Restarting Apache web server\n"
sudo service apache2 restart

# PHP ##########################################################################
echo -e "-- Add PPA for PHP\n"
add-apt-repository -y ppa:ondrej/php > /dev/null 2>&1

echo -e "-- Updating packages list\n"
apt-get update -y -qq

echo -e "-- Installing PHP\n"
apt-get install -y libapache2-mod-php5.6 > /dev/null 2>&1
apt-get install -y php5.6 > /dev/null 2>&1
apt-get install -y php5.6-cli > /dev/null 2>&1
apt-get install -y php5.6-mcrypt > /dev/null 2>&1
apt-get install -y unzip > /dev/null 2>&1
apt-get install -y php5-curl 
php5enmod curl
echo -e "-- Enabling PHP mcrypt module\n"
php5enmod mcrypt

echo -e "-- Turning PHP error reporting on\n"
sed -i "s/short_open_tag = .*/short_open_tag = On/" ${PHP_INI}
sed -i "s/error_reporting = .*/error_reporting = E_ALL/" ${PHP_INI}
sed -i "s/display_errors = .*/display_errors = On/" ${PHP_INI}
sed -i "s/post_max_size = .*/post_max_size = 64M/" ${PHP_INI}
sed -i "s/upload_max_filesize = .*/upload_max_filesize = 64M/" ${PHP_INI}

sudo service apache2 restart

echo -e "-- Installing Composer\n"
curl -Ss https://getcomposer.org/installer | php > /dev/null
mv composer.phar /usr/bin/composer
cd
unzip ${DOCUMENT_ROOT}/ADT/tools/backup_db/testadt_new_site.sql.zip
mysql -u${MYSQL_USER} -p${MYSQL_PASSWORD} -h ${APPLICATION_HOST} ${MYSQL_DATABASE} < testadt_new_site.sql
# END ##########################################################################
echo -e "-- ---------------- --"
echo -e "-- END BOOTSTRAPING --"
echo -e "-- ---------------- --"