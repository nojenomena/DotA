<?php
    $dbConfigFile = __DIR__ . "/db_config.php";

    if(!file_exists($dbConfigFile)){
        exit("Missing db_config.php. Make sure db_config.php has all correct details.");
    }

    include($dbConfigFile);

    $conn = pg_connect("host='$dbHost' port='$dbPort' dbname='$dbName' user='$dbUser' password='$dbPassword' sslmode=require");

    if(!$conn){
        exit("Database connection failed");
    }