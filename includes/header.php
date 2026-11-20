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
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>

    <!-- Bootstrap 5.3.8 (grid, navbar, forms, tables, alerts), then our own styles on top -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
          integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="<?php echo $root; ?>assets/css/styles.css">
</head>
<!-- d-flex flex-column min-vh-100: the footer stays at the bottom and pages can be centered -->
<body class="d-flex flex-column min-vh-100">
