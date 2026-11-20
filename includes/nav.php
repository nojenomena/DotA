<!-- menu on top of every page -->
<div id="nav">
    <a href="<?php echo $root; ?>index.php"><b>DotA</b></a>

    <?php if(isset($_SESSION["account_id"])){ ?>
        <a href="<?php echo $root; ?>saves.php">Saves</a>
        <a href="<?php echo $root; ?>records.php">Records</a>
        <a href="<?php echo $root; ?>account.php">Account</a>
        <?php if(isset($_SESSION["role"]) && $_SESSION["role"] == "admin"){ ?>
            <a href="<?php echo $root; ?>admin/index.php"><b>Admin</b></a>
        <?php } ?>
        <span style="float: right;">
            <?php echo htmlspecialchars($_SESSION["username"]); ?> |
            <a href="<?php echo $root; ?>logout.php">Log Out</a>
        </span>
    <?php } else { ?>
        <span style="float: right;">
            <a href="<?php echo $root; ?>login.php">Log In</a> |
            <a href="<?php echo $root; ?>signup.php">Sign Up</a>
        </span>
    <?php } ?>
</div>
