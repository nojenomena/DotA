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

        $account = findAccount($username);

        // same message for both so nobody can guess which usernames exist
        if(!$account || !password_verify($password, $account["password"])){
            $errors[] = "Wrong username or password.";
        } else {
            session_regenerate_id(true);
            $_SESSION["account_id"] = $account["account_id"];
            $_SESSION["username"] = $account["username"];
            header("Location: index.php");
            exit();
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
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
</body>
</html>
