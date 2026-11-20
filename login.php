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
    <main class="center-page">
        <div class="form-box">
            <h2>Log In</h2>

            <?php foreach($errors as $error){ ?>
                <div class="alert alert-danger py-2"><?php echo $error; ?></div>
            <?php } ?>

            <form method="POST" action="login.php">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required autofocus>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>

                <button type="submit" class="btn btn-light w-100"><i class="bi bi-box-arrow-in-right" aria-hidden="true"></i> Log In</button>
            </form>

            <p class="form-links">No account yet? <a href="signup.php">Sign up</a></p>
            <p class="form-links"><a class="btn btn-sm btn-outline-light" href="index.php"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back</a></p>
        </div>
    </main>
<?php include("includes/footer.php"); ?>
