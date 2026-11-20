<?php
    // copy this file to db_config.php and fill it in (db_config.php is in .gitignore, never push it)

    // Supabase > Connect > Session pooler
    $dbHost = "";
    $dbPort = "5432";
    $dbName = "postgres";
    $dbUser = "";
    $dbPassword = "";

    // Supabase Storage for enemy pictures. leave empty to save pictures in uploads/ instead
    // Supabase > Project Settings > API: project URL and the secret (or service_role) key
    $supabaseUrl = "";       // like https://abcdefgh.supabase.co
    $supabaseKey = "";       // secret key: never put it in javascript or github
    $storageBucket = "";     // a PUBLIC bucket, like enemy-pictures
