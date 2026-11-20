<?php
    // ---------- Vercel only ----------
    // Vercel runs PHP from the api/ folder, so every page request comes here first (see vercel.json).
    // this file finds the page (like saves.php), turns on database sessions, and runs the page.
    // on XAMPP this file is never used.

    $projectFolder = dirname(__DIR__);    // the folder above api/

    // the only pages that can be opened (includes/ and other files can't)
    $pages = [
        "index.php", "login.php", "signup.php", "logout.php", "account.php", "how_to_play.php",
        "saves.php", "new_save.php", "party.php", "battle.php", "battle_action.php", "records.php",
        "access_denied.php",
        "admin/index.php", "admin/users.php", "admin/enemies.php", "admin/items.php",
    ];

    // which page: from vercel.json (?route=saves.php), or from the address itself
    if(isset($_GET["route"])){
        $page = $_GET["route"];
        unset($_GET["route"]);
    } else {
        $page = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
    }
    $page = ltrim($page, "/");

    if($page == ""){
        $page = "index.php";
    }
    if($page == "admin" || $page == "admin/"){
        $page = "admin/index.php";
    }

    // pictures, css, js and sounds when testing with "php -S" (on Vercel they never reach this file).
    // never for .php files: those must go through the list below
    $isPhp = strtolower(pathinfo($page, PATHINFO_EXTENSION)) == "php";
    if(PHP_SAPI == "cli-server" && !$isPhp && is_file($projectFolder . "/" . $page)){
        return false;
    }

    if(!in_array($page, $pages)){
        http_response_code(404);
        exit("Page not found.");
    }

    // sessions go in the database (see includes/session_db.php)
    include($projectFolder . "/includes/db.php");
    include($projectFolder . "/includes/session_db.php");
    session_set_save_handler(new DatabaseSessions(), true);

    // run the page from its own folder so its include("includes/...") and include("../includes/...") work
    chdir(dirname($projectFolder . "/" . $page));
    include($projectFolder . "/" . $page);
