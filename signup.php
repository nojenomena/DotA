<?php
    session_start();
    include("config.php");
    include("db.php");
    include("queries.php");

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

        if(strlen($password) < $config["password_min"] || strlen($password) > $config["password_max"]){
            $errors[] = "Password must be " . $config["password_min"] . "-" . $config["password_max"] . " characters long.";
        }

        if(!preg_match("/[A-Z]/", $password) || !preg_match("/[0-9]/", $password)){
            $errors[] = "Password needs at least 1 uppercase letter and 1 number.";
        }

        if($password != $confirm){
            $errors[] = "Passwords do not match.";
        }

        if(empty($errors) && findAccount($username)){
            $errors[] = "That username is already taken.";
        }

        if(empty($errors)){
            $accountId = createAccount($username, $password);

            if($accountId){
                session_regenerate_id(true);
                $_SESSION["account_id"] = $accountId;
                $_SESSION["username"] = $username;
                header("Location: index.php");
                exit();
            } else {
                $errors[] = "Something went wrong. Please try again.";
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="form-box">
        <h2>Sign Up</h2>

        <?php foreach($errors as $error){ ?>
            <p class="error"><?php echo $error; ?></p>
        <?php } ?>

        <form method="POST" action="signup.php">
            <label for="username">Username</label><br>
            <input type="text" id="username" name="username" maxlength="<?php echo $config["username_max"]; ?>" value="<?php echo htmlspecialchars($username); ?>" required autofocus><br>

            <label for="password">Password</label><br>
            <input type="password" id="password" name="password" maxlength="<?php echo $config["password_max"]; ?>" required style="margin-bottom: 3px;"><br>
            <p class="hint" style="margin-bottom: 15px;">At least <?php echo $config["password_min"]; ?> characters, 1 uppercase letter and 1 number.</p>

            <label for="confirm_password">Confirm Password</label><br>
            <input type="password" id="confirm_password" name="confirm_password" maxlength="<?php echo $config["password_max"]; ?>" required><br>

            <button type="submit">Create Account</button>
        </form>

        <p class="form-links">Already have an account? <a href="login.php">Log in</a></p>
        <p class="form-links"><a href="index.php">Back</a></p>
    </div>
</body>
</html>
