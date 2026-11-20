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
    <div id="container">
        <h2>Admin Dashboard</h2>
        <p class="hint" style="margin-bottom: 20px;">Logged in as admin: <b><?php echo htmlspecialchars($_SESSION["username"]); ?></b></p>

        <?php if(!$counts){ ?>
            <p class="error">Couldn't load the numbers. Try again.</p>
        <?php } else { ?>
            <table border="1" class="admin-table">
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

        <h3 style="margin-top: 25px;">Manage</h3>
        <button class="menu-button" onclick="window.location.href='users.php'">Users</button>
        <button class="menu-button" onclick="window.location.href='enemies.php'">Enemies</button>
        <button class="menu-button" onclick="window.location.href='items.php'">Items</button>
    </div>
<?php include("../includes/footer.php"); ?>
