<?php
    // true when the Supabase Storage details are filled in
    function storageReady(){
        global $supabaseUrl, $supabaseKey, $storageBucket;

        return !empty($supabaseUrl) && !empty($supabaseKey) && !empty($storageBucket);
    }

    // sends one request to Supabase Storage, returns true if it worked
    function storageRequest($method, $objectPath, $body, $contentType){
        global $supabaseUrl, $supabaseKey, $storageBucket;

        $headers = "apikey: " . $supabaseKey . "\r\n";

        // old style keys (they start with eyJ) also go in the Authorization header
        if(strpos($supabaseKey, "eyJ") === 0){
            $headers = $headers . "Authorization: Bearer " . $supabaseKey . "\r\n";
        }
        if($contentType != ""){
            $headers = $headers . "Content-Type: " . $contentType . "\r\n";
        }

        $context = stream_context_create([
            "http" => [
                "method"        => $method,
                "header"        => $headers,
                "content"       => $body,
                "ignore_errors" => true,    // so we can read the status code on errors too
                "timeout"       => 20,
            ],
        ]);

        $url = rtrim($supabaseUrl, "/") . "/storage/v1/object/" . $storageBucket . "/" . $objectPath;
        @file_get_contents($url, false, $context);

        // first line is like "HTTP/1.1 200 OK"
        if(!isset($http_response_header[0])){
            return false;
        }
        return preg_match("/ 2\d\d /", $http_response_header[0]) == 1;
    }

    // the web address of a picture in the public bucket
    function storagePublicUrl($objectPath){
        global $supabaseUrl, $storageBucket;

        return rtrim($supabaseUrl, "/") . "/storage/v1/object/public/" . $storageBucket . "/" . $objectPath;
    }

    function saveUpload($file, $folder, $prefix){
        global $config;

        if($file["error"] == UPLOAD_ERR_INI_SIZE || $file["error"] == UPLOAD_ERR_FORM_SIZE){
            return ["path" => "", "error" => "The picture is too big (max " . ($config["upload_max_kb"] / 1024) . " MB)."];
        }
        if($file["error"] != UPLOAD_ERR_OK){
            return ["path" => "", "error" => "The upload didn't work. Try again."];
        }

        // size
        if($file["size"] > $config["upload_max_kb"] * 1024){
            return ["path" => "", "error" => "The picture is too big (max " . ($config["upload_max_kb"] / 1024) . " MB)."];
        }

        // type: look inside the file, not at its name (a .php renamed to .png fails here)
        $info = @getimagesize($file["tmp_name"]);
        if(!$info || !isset($config["upload_types"][$info["mime"]])){
            return ["path" => "", "error" => "Only JPG, PNG, GIF or WEBP pictures are allowed."];
        }

        // new random name so files never clash and the original name is never used
        $name = $prefix . "_" . bin2hex(random_bytes(4)) . "." . $config["upload_types"][$info["mime"]];

        // cloud: send it to Supabase Storage, like enemies/enemy_1a2b3c4d.png
        if(storageReady()){
            $objectPath = str_replace("uploads/", "", $folder) . $name;

            if(!storageRequest("POST", $objectPath, file_get_contents($file["tmp_name"]), $info["mime"])){
                return ["path" => "", "error" => "Couldn't upload the picture to storage. Try again."];
            }
            return ["path" => storagePublicUrl($objectPath), "error" => ""];
        }

        // local: save it in uploads/ (__DIR__ is includes/, so ../ is the project folder)
        if(!move_uploaded_file($file["tmp_name"], __DIR__ . "/../" . $folder . $name)){
            return ["path" => "", "error" => "Couldn't save the picture. Try again."];
        }

        return ["path" => $folder . $name, "error" => ""];
    }

    // deletes an uploaded picture (from storage, or from uploads/)
    function deleteUpload($path){
        if($path == ""){
            return;
        }

        // a storage picture: take the part after the bucket name
        if(storageReady() && strpos($path, storagePublicUrl("")) === 0){
            storageRequest("DELETE", substr($path, strlen(storagePublicUrl(""))), "", "");
            return;
        }

        // a local picture: only things inside uploads/
        if(strpos($path, "uploads/") !== 0 || strpos($path, "..") !== false){
            return;
        }

        $file = __DIR__ . "/../" . $path;
        if(file_exists($file)){
            unlink($file);
        }
    }

    // the src for an <img>: web addresses stay the same, local files get $root in front
    function imageUrl($path, $root){
        if(strpos($path, "http") === 0){
            return $path;
        }
        return $root . $path;
    }
