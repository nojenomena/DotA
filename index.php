<?php
    session_start();
?>

<?php
    $pageTitle = "Defense of the Alphards";
    include("includes/header.php");
    include("includes/nav.php");
?>
    <main class="center-page">
        <div class="title-box">
            
            <h1 id="title">Defense of the Alphards</h1>
            <p id="subtitle">Climb the tower. Defeat the Developers.</p>

            <?php if(isset($_GET["deleted"])){ ?>
                <div class="alert alert-warning py-2">Your account was deleted.</div>
            <?php } ?>

            <div class="menu-buttons">
                <?php if(isset($_SESSION["account_id"])){ ?>
                    <p class="mb-3">Welcome, <b><?php echo htmlspecialchars($_SESSION["username"]); ?></b>!</p>
                    <a class="btn btn-light w-100 mb-2" href="saves.php"><i class="bi bi-play-fill" aria-hidden="true"></i> Continue</a>
                    <a class="btn btn-outline-light w-100 mb-2" href="how_to_play.php"><i class="bi bi-question-circle" aria-hidden="true"></i> How to Play</a>
                    <a class="btn btn-outline-light w-100 mb-2" href="account.php"><i class="bi bi-person-circle" aria-hidden="true"></i> Profile</a>
                    <a class="btn btn-outline-light w-100" href="logout.php"><i class="bi bi-box-arrow-right" aria-hidden="true"></i> Log Out</a>
                <?php } else { ?>
                    <a class="btn btn-light w-100 mb-2" href="login.php"><i class="bi bi-box-arrow-in-right" aria-hidden="true"></i> Log In</a>
                    <a class="btn btn-outline-light w-100 mb-2" href="signup.php"><i class="bi bi-person-plus" aria-hidden="true"></i> Sign Up</a>
                    <a class="btn btn-outline-light w-100" href="how_to_play.php"><i class="bi bi-question-circle" aria-hidden="true"></i> How to Play</a>
                <?php } ?>
            </div>
        </div>
    </main>
<?php include("includes/footer.php"); ?>
