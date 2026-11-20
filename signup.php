<?php
    session_start();
    include("includes/config.php");
    include("includes/auth.php");
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
        $confirm = $_POST["confirm_password"];

        // letters, numbers and _ only
        if(!preg_match("/^[A-Za-z0-9_]+$/", $username) || strlen($username) < $config["username_min"] || strlen($username) > $config["username_max"]){
            $errors[] = "Username must be " . $config["username_min"] . "-" . $config["username_max"] . " characters: letters, numbers and _ only.";
        }

        $errors = array_merge($errors, passwordErrors($password, $confirm));

        if(empty($errors) && findAccount($username)){
            $errors[] = "That username is already taken.";
        }

        if(empty($errors)){
            $accountId = createAccount($username, $password);

            if($accountId){
                session_regenerate_id(true);
                $_SESSION["account_id"] = $accountId;
                $_SESSION["username"] = $username;
                $_SESSION["role"] = "user";
                header("Location: index.php");
                exit();
            } else {
                $errors[] = "Something went wrong. Please try again.";
            }
        }
    }
?>

<?php
    $pageTitle = "Sign Up";
    include("includes/header.php");
    include("includes/nav.php");
?>
    <main class="center-page">
        <div class="form-box">
            <h2>Sign Up</h2>

            <?php foreach($errors as $error){ ?>
                <div class="alert alert-danger py-2"><?php echo $error; ?></div>
            <?php } ?>

            <form method="POST" action="signup.php">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" maxlength="<?php echo $config["username_max"]; ?>" value="<?php echo htmlspecialchars($username); ?>" required autofocus>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" maxlength="<?php echo $config["password_max"]; ?>" required>
                    <div class="hint mt-1">At least <?php echo $config["password_min"]; ?> characters, 1 uppercase letter and 1 number.</div>
                </div>

                <div class="mb-4">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" maxlength="<?php echo $config["password_max"]; ?>" required>
                </div>

                <button type="submit" class="btn btn-light w-100"><i class="bi bi-person-plus" aria-hidden="true"></i> Create Account</button>
            </form>

            <p class="form-links">Already have an account? <a href="login.php">Log in</a></p>
            <p class="form-links"><a class="btn btn-sm btn-outline-light" href="index.php"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back</a></p>
        </div>
    </main>
<?php include("includes/footer.php"); ?>
