<?php
    $projectFolder = dirname(__DIR__);

    $pages = [
        "index.php", "login.php", "signup.php", "logout.php", "account.php", "how_to_play.php",
        "saves.php", "new_save.php", "party.php", "battle.php", "battle_action.php", "records.php",
        "access_denied.php",
        "admin/index.php", "admin/users.php", "admin/enemies.php", "admin/items.php",
    ];

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

    $isPhp = strtolower(pathinfo($page, PATHINFO_EXTENSION)) == "php";
    if(PHP_SAPI == "cli-server" && !$isPhp && is_file($projectFolder . "/" . $page)){
        return false;
    }

    if(!in_array($page, $pages)){
        http_response_code(404);
        exit("Page not found.");
    }

    include($projectFolder . "/includes/db.php");
    include($projectFolder . "/includes/session_db.php");
    session_set_save_handler(new DatabaseSessions(), true);

    chdir(dirname($projectFolder . "/" . $page));
    include($projectFolder . "/" . $page);
