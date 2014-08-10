mailalias.paw.at.it
===================

http://paw.at.it/mail/

a small webinterface for postfix aliases with mysql

copy src/mysql-aliases.cf to /etc/postfix/
add alias_maps and alias_database from src/main.cf to your /etc/postfix/main.cf

login to mysql
create a database named "mail":
create database mail;

[optional] create a user with permissions for the database
GRANT all privileges on mail.* TO mailUser@localhost IDENTIFIED BY 'Password<-ChangeMe' ;
GRANT all privileges on mail.* TO mailUser@127.0.0.1 IDENTIFIED BY 'Password<-ChangeMe' ;

logout of mysql and add the databaseschema to the database
mysql -u yourUser -p yourPassword mail < src/mail.sql


change databaseuser and password in src/php/mail.paw.at.it.php

copy src/php/ to your webserver-directory ( php5.5 and mysqli is required )
