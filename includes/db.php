<?php
    // database details: from db_config.php on your computer (XAMPP),
    // or from the environment variables on Vercel (db_config.php is never uploaded)
    $dbConfigFile = __DIR__ . "/db_config.php";

    if(file_exists($dbConfigFile)){
        include($dbConfigFile);
    } else {
        $dbHost = getenv("DB_HOST");
        $dbPort = getenv("DB_PORT") ? getenv("DB_PORT") : "5432";
        $dbName = getenv("DB_NAME") ? getenv("DB_NAME") : "postgres";
        $dbUser = getenv("DB_USER");
        $dbPassword = getenv("DB_PASSWORD");

        // supabase storage for the enemy pictures (optional)
        $supabaseUrl = getenv("SUPABASE_URL");
        $supabaseKey = getenv("SUPABASE_KEY");
        $storageBucket = getenv("STORAGE_BUCKET");
    }

    if(empty($dbHost)){
        exit("Missing database details. Make db_config.php from db_config.template.php (or set the environment variables on Vercel).");
    }

    // the @ hides the php warning (it shows the database address), we show our own message below
    $conn = @pg_connect("host='$dbHost' port='$dbPort' dbname='$dbName' user='$dbUser' password='$dbPassword' sslmode=require");

    if(!$conn){
        exit("Can't connect to the database right now. Please try again later.");
    }
