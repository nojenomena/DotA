<?php
    session_start();
    $root = "../";
    include("../includes/config.php");
    include("../includes/auth.php");

    requireAdmin();

    include("../includes/db.php");
    include("../includes/queries.php");

    $errors = [];

    if($_SERVER["REQUEST_METHOD"] == "POST"){
        $accountId = isset($_POST["account_id"]) ? (int) $_POST["account_id"] : 0;

        // an admin can't lock themselves out
        if($accountId == $_SESSION["account_id"]){
            $errors[] = "You can't change or delete your own account here.";
        } else if(isset($_POST["role"])){
            if($_POST["role"] != "user" && $_POST["role"] != "admin"){
                $errors[] = "Unknown role.";
            } else if(setRole($accountId, $_POST["role"])){
                header("Location: users.php?done=role");
                exit();
            } else {
                $errors[] = "Couldn't change the role. Try again.";
            }
        } else if(isset($_POST["delete"])){
            if(deleteAccount($accountId)){
                header("Location: users.php?done=deleted");
                exit();
            } else {
                $errors[] = "Couldn't delete the account. Try again.";
            }
        }
    }

    $messages = [
        "role"    => "Role changed. It takes effect the next time they log in.",
        "deleted" => "Account deleted, with all its saves.",
    ];
    $done = isset($_GET["done"]) && isset($messages[$_GET["done"]]) ? $messages[$_GET["done"]] : "";

    $accounts = getAllAccounts();
?>

<?php
    $pageTitle = "Admin - Users";
    include("../includes/header.php");
    include("../includes/nav.php");
?>
    <div id="container">
        <h2>Users</h2>
        <p class="mb-3"><a class="btn btn-sm btn-outline-light" href="index.php"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to dashboard</a></p>

        <?php if($done != ""){ ?>
            <div class="alert alert-success py-2"><?php echo $done; ?></div>
        <?php } ?>
        <?php foreach($errors as $error){ ?>
            <div class="alert alert-danger py-2"><?php echo $error; ?></div>
        <?php } ?>

        <div class="table-responsive">
        <table class="table table-bordered table-sm align-middle">
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Role</th>
                <th>Saves</th>
                <th>Battles</th>
                <th>Joined</th>
                <th></th>
            </tr>
            <?php foreach($accounts as $account){ ?>
                <tr>
                    <td><?php echo $account["account_id"]; ?></td>
                    <td><?php echo htmlspecialchars($account["username"]); ?></td>
                    <td><?php echo $account["role"] == "admin" ? "<b>Admin</b>" : "User"; ?></td>
                    <td><?php echo $account["saves"]; ?></td>
                    <td><?php echo $account["battles"]; ?></td>
                    <td><?php echo date("M j, Y", strtotime($account["created_at"])); ?></td>
                    <td>
                        <?php if($account["account_id"] == $_SESSION["account_id"]){ ?>
                            <span class="hint">(you)</span>
                        <?php } else { ?>
                            <form method="POST" action="users.php" style="display: inline;">
                                <input type="hidden" name="account_id" value="<?php echo $account["account_id"]; ?>">
                                <?php if($account["role"] == "admin"){ ?>
                                    <input type="hidden" name="role" value="user">
                                    <button type="submit" class="btn btn-sm btn-outline-light"><i class="bi bi-person" aria-hidden="true"></i> Make User</button>
                                <?php } else { ?>
                                    <input type="hidden" name="role" value="admin">
                                    <button type="submit" class="btn btn-sm btn-outline-light"><i class="bi bi-shield-plus" aria-hidden="true"></i> Make Admin</button>
                                <?php } ?>
                            </form>
                            <form method="POST" action="users.php" style="display: inline;" onsubmit="return confirm('Delete this account and all its saves?');">
                                <input type="hidden" name="account_id" value="<?php echo $account["account_id"]; ?>">
                                <input type="hidden" name="delete" value="1">
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash" aria-hidden="true"></i> Delete</button>
                            </form>
                        <?php } ?>
                    </td>
                </tr>
            <?php } ?>
        </table>
        </div>
    </div>
<?php include("../includes/footer.php"); ?>
