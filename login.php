<?php
    session_start();
    include("includes/config.php");
    include("includes/db.php");
    include("includes/queries.php");

    if(isset($_SESSION["account_id"])){
        header("Location: index.php");
        exit();
    }

    $errors = [];
    $username = "";

    if($_SERVER["REQUEST_METHOD"] == "POST"){
        $username = trim($_POST["username"]);
        $password = $_POST["password"];

        $account = findAccount($username);

        if(!$account || !password_verify($password, $account["password"])){
            $errors[] = "Wrong username or password.";
        } else {
            session_regenerate_id(true);
            $_SESSION["account_id"] = $account["account_id"];
            $_SESSION["username"] = $account["username"];
            $_SESSION["role"] = $account["role"];
            header("Location: index.php");
            exit();
        }
    }
?>

<?php
    $pageTitle = "Log In";
    include("includes/header.php");
    include("includes/nav.php");
?>
    <div class="form-box">
        <h2>Log In</h2>

        <?php foreach($errors as $error){ ?>
            <p class="error"><?php echo $error; ?></p>
        <?php } ?>

        <form method="POST" action="login.php">
            <label for="username">Username</label><br>
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required autofocus><br>

            <label for="password">Password</label><br>
            <input type="password" id="password" name="password" required><br>

            <button type="submit">Log In</button>
        </form>

        <p class="form-links">No account yet? <a href="signup.php">Sign up</a></p>
        <p class="form-links"><a href="index.php">Back</a></p>
    </div>
<?php include("includes/footer.php"); ?>
