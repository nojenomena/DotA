<?php
    // picture uploads. returns ["path" => "uploads/...", "error" => ""]
    // $folder is like "uploads/enemies/", $prefix is the start of the new file name

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

        // __DIR__ is includes/, so ../ is the project folder
        if(!move_uploaded_file($file["tmp_name"], __DIR__ . "/../" . $folder . $name)){
            return ["path" => "", "error" => "Couldn't save the picture. Try again."];
        }

        return ["path" => $folder . $name, "error" => ""];
    }

    // deletes an uploaded file (only things inside uploads/)
    function deleteUpload($path){
        if($path == "" || strpos($path, "uploads/") !== 0 || strpos($path, "..") !== false){
            return;
        }

        $file = __DIR__ . "/../" . $path;
        if(file_exists($file)){
            unlink($file);
        }
    }
