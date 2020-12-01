Blossom Server is a socket server written in PHP. Here are a few tips to help you get it up and running.

1. Install PHP with "php5-cli". This is the command I installed PHP on my server with, you may need something slightly different.
sudo aptitude install libapache2-mod-php5 php5 php5-common php5-curl php5-dev php5-mcrypt php5-memcache php5-mhash php5-mysql php5-pspell php5-snmp php5-sqlite php5-xmlrpc php5-xsl php5-cli

2. If you're going to use the database integration features of Blossom Server, mysql must be installed. I use version 5, but I don't see any reason why version 4 wouldn't work.

3. Three example servers have been included in this zip. They are "multiplayer_server.php", "policy_server.php", and "query_server.php".