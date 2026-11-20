<?php
    session_start();
    $root = "../";    // this page is inside admin/, so links go up one folder
    include("../includes/config.php");
    include("../includes/auth.php");

    requireAdmin();

    include("../includes/db.php");
    include("../includes/queries.php");

    $counts = getAdminCounts();
?>

<?php
    $pageTitle = "Admin";
    include("../includes/header.php");
    include("../includes/nav.php");
?>
    <main class="center-page">
        <div class="form-box form-box-wide">
            <h2>Admin Dashboard</h2>
            <p class="hint text-center mb-3">Logged in as admin: <b><?php echo htmlspecialchars($_SESSION["username"]); ?></b></p>

            <?php if(!$counts){ ?>
                <div class="alert alert-danger py-2">Couldn't load the numbers. Try again.</div>
            <?php } else { ?>
                <table class="table table-bordered table-sm admin-table">
                    <tr><th colspan="2">Players</th></tr>
                    <tr><td>Accounts</td><td><?php echo $counts["players"]; ?></td></tr>
                    <tr><td>Admins</td><td><?php echo $counts["admins"]; ?></td></tr>
                    <tr><th colspan="2">Game</th></tr>
                    <tr><td>Saves</td><td><?php echo $counts["saves"]; ?></td></tr>
                    <tr><td>Towers cleared</td><td><?php echo $counts["cleared"]; ?></td></tr>
                    <tr><td>Battles played</td><td><?php echo $counts["battles"]; ?></td></tr>
                    <tr><td>Wins / Losses</td><td><?php echo $counts["wins"]; ?> / <?php echo $counts["losses"]; ?></td></tr>
                    <tr><th colspan="2">Game data</th></tr>
                    <tr><td>Enemies and bosses</td><td><?php echo $counts["enemies"]; ?></td></tr>
                    <tr><td>Items</td><td><?php echo $counts["items"]; ?></td></tr>
                </table>
            <?php } ?>

            <h3 class="mt-4 mb-3">Manage</h3>
            <!-- three buttons side by side (they stack on a phone) -->
            <div class="row g-2">
                <div class="col-sm-4"><a class="btn btn-light w-100" href="users.php">Users</a></div>
                <div class="col-sm-4"><a class="btn btn-light w-100" href="enemies.php">Enemies</a></div>
                <div class="col-sm-4"><a class="btn btn-light w-100" href="items.php">Items</a></div>
            </div>
        </div>
    </main>
<?php include("../includes/footer.php"); ?>
