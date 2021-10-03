<?php

error_reporting(E_ALL);
ini_set("display_errors", 1);

//MySQL details
define('__DB_SERVER', 'localhost');
define('__DB_USERNAME', 'debian-sys-maint');
define('__DB_PASSWORD', '1YpePilzZDvsu13A');
define('__DB_NAME', 'torrent');
define('__DB_TABLE', 'peers');

//Peer announce interval (Seconds)
define('__INTERVAL', 1800);

//Time out if peer is this late to re-announce (Seconds)
define('__TIMEOUT', 60);

//Minimum announce interval (Seconds)
//Most clients honor this, but not all
//This is not enforced server side
define('__INTERVAL_MIN', 600);

//Never encode more than this number of peers in a single request
define('__MAX_PPR', 25);

?>