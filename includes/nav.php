<!-- menu on top of every page -->
<div id="nav">
    <?php if(isset($hideNavLinks) && $hideNavLinks){ ?>
        <!-- battle page: only the title, no links out (Pause > Exit Battle is the way out) -->
        <b>DotA</b>
    <?php } else { ?>
        <a href="<?php echo $root; ?>index.php"><b>DotA</b></a>
        <a href="<?php echo $root; ?>how_to_play.php">How to Play</a>

        <?php if(isset($_SESSION["account_id"])){ ?>
            <a href="<?php echo $root; ?>saves.php">Saves</a>
            <a href="<?php echo $root; ?>records.php">Records</a>
            <a href="<?php echo $root; ?>account.php">Profile</a>
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
    <?php } ?>
</div>
