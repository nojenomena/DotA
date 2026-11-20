<?php
    $dbConfigFile = __DIR__ . "/db_config.php";

    if(!file_exists($dbConfigFile)){
        exit("Missing db_config.php. Make sure db_config.php has all correct details.");
    }

    include($dbConfigFile);

    // the @ hides the php warning (it shows the database address), we show our own message below
    $conn = @pg_connect("host='$dbHost' port='$dbPort' dbname='$dbName' user='$dbUser' password='$dbPassword' sslmode=require");

    if(!$conn){
        exit("Can't connect to the database right now. Please try again later.");
    }