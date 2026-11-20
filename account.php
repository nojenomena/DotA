<?php
    session_start();
    include("includes/config.php");
    include("includes/auth.php");
    include("includes/db.php");
    include("includes/queries.php");

    requireLogin();

    $passwordErrors = [];
    $deleteErrors = [];
    $account = findAccount($_SESSION["username"]);

    if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["change_password"])){
        // change password: old password first, then the same rules as sign up
        $current = isset($_POST["current_password"]) ? $_POST["current_password"] : "";
        $new = isset($_POST["new_password"]) ? $_POST["new_password"] : "";
        $confirm = isset($_POST["confirm_password"]) ? $_POST["confirm_password"] : "";

        if(!$account || !password_verify($current, $account["password"])){
            $passwordErrors[] = "Your current password is wrong.";
        } else {
            $passwordErrors = passwordErrors($new, $confirm);

            if(empty($passwordErrors) && password_verify($new, $account["password"])){
                $passwordErrors[] = "The new password is the same as the old one.";
            }
        }

        if(empty($passwordErrors)){
            if(updatePassword($_SESSION["account_id"], $new)){
                header("Location: account.php?changed=1");
                exit();
            }
            $passwordErrors[] = "Couldn't change the password. Try again.";
        }
    } else if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_account"])){
        // delete account: needs the password first
        $password = isset($_POST["password"]) ? $_POST["password"] : "";

        if(!$account || !password_verify($password, $account["password"])){
            $deleteErrors[] = "Wrong password.";
        } else {
            deleteAccount($_SESSION["account_id"]);

            session_unset();
            session_destroy();
            header("Location: index.php?deleted=1");
            exit();
        }
    }

    // stats from all saves (same numbers as the records page)
    $stats = ["saves" => 0, "battles" => 0, "wins" => 0, "losses" => 0, "best_floor" => 0, "score" => 0];
    foreach(getSaveTotals($_SESSION["account_id"]) as $row){
        $stats["saves"]++;
        $stats["battles"] = $stats["battles"] + $row["battles"];
        $stats["wins"] = $stats["wins"] + $row["wins"];
        $stats["losses"] = $stats["losses"] + $row["losses"];
        $stats["score"] = $stats["score"] + $row["total_score"];
        $stats["best_floor"] = max($stats["best_floor"], (int) $row["highest_floor"]);
    }
?>

<?php
    $pageTitle = "Profile";
    include("includes/header.php");
    include("includes/nav.php");
?>
    <div class="form-box">
        <h2>Profile</h2>

        <table border="1" class="item-table">
            <tr><td>Username</td><td><b><?php echo htmlspecialchars($_SESSION["username"]); ?></b></td></tr>
            <tr><td>Role</td><td><?php echo isAdmin() ? "Admin" : "Player"; ?></td></tr>
            <tr><td>Joined</td><td><?php echo $account ? date("F j, Y", strtotime($account["created_at"])) : "-"; ?></td></tr>
            <tr><td>Saves</td><td><?php echo $stats["saves"]; ?> / <?php echo $config["max_save_slots"]; ?></td></tr>
            <tr><td>Battles</td><td><?php echo $stats["battles"]; ?> (<?php echo $stats["wins"]; ?> won, <?php echo $stats["losses"]; ?> lost)</td></tr>
            <tr><td>Highest floor</td><td><?php echo $stats["best_floor"]; ?> / <?php echo $config["total_floors"]; ?></td></tr>
            <tr><td>Total score</td><td><?php echo $stats["score"]; ?> XP</td></tr>
        </table>
        <p class="hint" style="margin-top: 5px;">See every battle on the <a href="records.php">Records</a> page.</p>

        <br>
        <hr>
        <br>

        <h3>Change Password</h3>
        <br>

        <?php if(isset($_GET["changed"])){ ?>
            <p class="success">Password changed.</p>
        <?php } ?>
        <?php foreach($passwordErrors as $error){ ?>
            <p class="error"><?php echo $error; ?></p>
        <?php } ?>

        <form method="POST" action="account.php">
            <input type="hidden" name="change_password" value="1">

            <label for="current_password">Current password</label><br>
            <input type="password" id="current_password" name="current_password" required><br>

            <label for="new_password">New password</label><br>
            <input type="password" id="new_password" name="new_password" maxlength="<?php echo $config["password_max"]; ?>" required style="margin-bottom: 3px;"><br>
            <p class="hint" style="margin-bottom: 15px;">At least <?php echo $config["password_min"]; ?> characters, 1 uppercase letter and 1 number.</p>

            <label for="confirm_password">Confirm new password</label><br>
            <input type="password" id="confirm_password" name="confirm_password" maxlength="<?php echo $config["password_max"]; ?>" required><br>

            <button type="submit">Change Password</button>
        </form>

        <br>
        <hr>
        <br>

        <h3>Delete Account</h3>
        <p class="hint" style="margin-bottom: 15px;">This deletes your account, all your saves, party members and items. You can't undo this.</p>

        <?php foreach($deleteErrors as $error){ ?>
            <p class="error"><?php echo $error; ?></p>
        <?php } ?>

        <form method="POST" action="account.php" onsubmit="return confirm('Are you really sure? Everything will be deleted.');">
            <input type="hidden" name="delete_account" value="1">

            <label for="password">Type your password to confirm</label><br>
            <input type="password" id="password" name="password" required><br>

            <button type="submit" class="delete-button">Delete My Account</button>
        </form>

        <p class="form-links"><a href="index.php">Back</a></p>
    </div>
<?php include("includes/footer.php"); ?>
