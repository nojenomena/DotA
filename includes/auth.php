<?php
    // login and role checks. include this after session_start()
    // pages inside a folder (like admin/) set $root = "../" first so the links still work

    function isAdmin(){
        return isset($_SESSION["role"]) && $_SESSION["role"] == "admin";
    }

    // guests go to the login page
    function requireLogin(){
        global $root;

        if(!isset($_SESSION["account_id"])){
            header("Location: " . $root . "login.php");
            exit();
        }
    }

    // logged in but not an admin: access denied
    function requireAdmin(){
        global $root;

        requireLogin();

        if(!isAdmin()){
            header("Location: " . $root . "access_denied.php");
            exit();
        }
    }
