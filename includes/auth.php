<?php
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

    // password rules, used by sign up and change password. returns a list of errors
    function passwordErrors($password, $confirm){
        global $config;

        $errors = [];

        if(strlen($password) < $config["password_min"] || strlen($password) > $config["password_max"]){
            $errors[] = "Password must be " . $config["password_min"] . "-" . $config["password_max"] . " characters long.";
        }

        if(!preg_match("/[A-Z]/", $password) || !preg_match("/[0-9]/", $password)){
            $errors[] = "Password needs at least 1 uppercase letter and 1 number.";
        }

        if($password != $confirm){
            $errors[] = "Passwords do not match.";
        }

        return $errors;
    }
