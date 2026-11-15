<?php
    session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Defense of the Alphards</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div id="container" style="text-align: center;">
        <!-- PLACEHOLDER: logo picture (paulo) -->
        <h1 id="title">Defense of the Alphards</h1>
        <p id="subtitle">Climb the tower. Defeat the Developers.</p>

        <?php if(isset($_SESSION["account_id"])){ ?>
            <p>Welcome, <b><?php echo htmlspecialchars($_SESSION["username"]); ?></b>!</p>
            <br>
            <button class="menu-button" onclick="window.location.href='saves.php'">Continue</button>
            <br>
            <button class="menu-button" onclick="window.location.href='logout.php'">Log Out</button>
        <?php } else { ?>
            <button class="menu-button" onclick="window.location.href='login.php'">Log In</button>
            <br>
            <button class="menu-button" onclick="window.location.href='signup.php'">Sign Up</button>
        <?php } ?>
    </div>
</body>
</html>
