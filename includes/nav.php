<!-- menu on top of every page. on small screens the links fold into the ☰ button -->
<nav id="nav" class="navbar navbar-expand-md">
    <div class="container-fluid">
        <?php if(isset($hideNavLinks) && $hideNavLinks){ ?>
            <!-- battle page: only the title, no links out (Pause > Exit Battle is the way out) -->
            <span class="navbar-brand">DotA</span>
        <?php } else { ?>
            <a class="navbar-brand" href="<?php echo $root; ?>index.php">DotA</a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav-links" aria-controls="nav-links" aria-expanded="false" aria-label="Menu">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="nav-links">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="<?php echo $root; ?>how_to_play.php">How to Play</a></li>
                    <?php if(isset($_SESSION["account_id"])){ ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo $root; ?>saves.php">Saves</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo $root; ?>records.php">Records</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo $root; ?>account.php">Profile</a></li>
                        <?php if(isset($_SESSION["role"]) && $_SESSION["role"] == "admin"){ ?>
                            <li class="nav-item"><a class="nav-link nav-admin" href="<?php echo $root; ?>admin/index.php">Admin</a></li>
                        <?php } ?>
                    <?php } ?>
                </ul>

                <ul class="navbar-nav">
                    <?php if(isset($_SESSION["account_id"])){ ?>
                        <li class="nav-item"><span class="navbar-text me-3"><?php echo htmlspecialchars($_SESSION["username"]); ?></span></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo $root; ?>logout.php">Log Out</a></li>
                    <?php } else { ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo $root; ?>login.php">Log In</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo $root; ?>signup.php">Sign Up</a></li>
                    <?php } ?>
                </ul>
            </div>
        <?php } ?>
    </div>
</nav>
