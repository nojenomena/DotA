<?php
    // top of every page. set $pageTitle before including this
    // pages inside a folder (like admin/) set $root = "../" so the css link still works
    if(!isset($pageTitle)){
        $pageTitle = "Defense of the Alphards";
    }
    if(!isset($root)){
        $root = "";
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="<?php echo $root; ?>assets/css/styles.css">
</head>
<body>
