<?php
    session_start();
    http_response_code(403);
?>

<?php
    $pageTitle = "Access Denied";
    include("includes/header.php");
    include("includes/nav.php");
?>
    <div class="form-box" style="text-align: center;">
        <h2>Access Denied</h2>
        <p>You don't have access to this page.</p>
        <p class="hint" style="margin-top: 10px;">Only admins can open it.</p>

        <p class="form-links"><a href="index.php">Back to home</a></p>
    </div>
<?php include("includes/footer.php"); ?>
