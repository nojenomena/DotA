<?php
    session_start();
    http_response_code(403);
?>

<?php
    $pageTitle = "Access Denied";
    include("includes/header.php");
    include("includes/nav.php");
?>
    <main class="center-page">
        <div class="form-box text-center">
            <h2>Access Denied</h2>
            <p>You don't have access to this page.</p>
            <p class="hint mt-2">Only admins can open it.</p>

            <a class="btn btn-light w-100 mt-4" href="index.php"><i class="bi bi-house" aria-hidden="true"></i> Back to home</a>
        </div>
    </main>
<?php include("includes/footer.php"); ?>
