<?php
    session_start();
?>

<?php
    $pageTitle = "Defense of the Alphards";
    include("includes/header.php");
    include("includes/nav.php");
?>
    <div id="container" style="text-align: center;">
        <!-- PLACEHOLDER: logo picture (paulo) -->
        <h1 id="title">Defense of the Alphards</h1>
        <p id="subtitle">Climb the tower. Defeat the Developers.</p>

        <?php if(isset($_GET["deleted"])){ ?>
            <p style="color: yellow; margin-bottom: 20px;">Your account was deleted.</p>
        <?php } ?>

        <?php if(isset($_SESSION["account_id"])){ ?>
            <p>Welcome, <b><?php echo htmlspecialchars($_SESSION["username"]); ?></b>!</p>
            <br>
            <button class="menu-button" onclick="window.location.href='saves.php'">Continue</button>
            <br>
            <button class="menu-button" onclick="window.location.href='account.php'">Profile</button>
            <br>
            <button class="menu-button" onclick="window.location.href='logout.php'">Log Out</button>
        <?php } else { ?>
            <button class="menu-button" onclick="window.location.href='login.php'">Log In</button>
            <br>
            <button class="menu-button" onclick="window.location.href='signup.php'">Sign Up</button>
            <br>
            <button class="menu-button" onclick="window.location.href='how_to_play.php'">How to Play</button>
        <?php } ?>
    </div>
<?php include("includes/footer.php"); ?>
