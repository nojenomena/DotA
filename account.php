<?php
    session_start();
    include("includes/config.php");
    include("includes/db.php");
    include("includes/queries.php");

    if(!isset($_SESSION["account_id"])){
        header("Location: login.php");
        exit();
    }

    $errors = [];

    // delete account: needs the password first
    if($_SERVER["REQUEST_METHOD"] == "POST"){
        $password = isset($_POST["password"]) ? $_POST["password"] : "";
        $account = findAccount($_SESSION["username"]);

        if(!$account || !password_verify($password, $account["password"])){
            $errors[] = "Wrong password.";
        } else {
            deleteAccount($_SESSION["account_id"]);

            session_unset();
            session_destroy();
            header("Location: index.php?deleted=1");
            exit();
        }
    }
?>

<?php
    $pageTitle = "Account";
    include("includes/header.php");
    include("includes/nav.php");
?>
    <div class="form-box">
        <h2>Account</h2>
        <p>Logged in as <b><?php echo htmlspecialchars($_SESSION["username"]); ?></b></p>
        <br>
        <hr>
        <br>

        <h3>Delete Account</h3>
        <p class="hint" style="margin-bottom: 15px;">This deletes your account, all your saves, party members and items. You can't undo this.</p>

        <?php foreach($errors as $error){ ?>
            <p class="error"><?php echo $error; ?></p>
        <?php } ?>

        <form method="POST" action="account.php" onsubmit="return confirm('Are you really sure? Everything will be deleted.');">
            <label for="password">Type your password to confirm</label><br>
            <input type="password" id="password" name="password" required><br>

            <button type="submit" class="delete-button">Delete My Account</button>
        </form>

        <p class="form-links"><a href="index.php">Back</a></p>
    </div>
<?php include("includes/footer.php"); ?>
