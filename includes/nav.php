<?php
    // which page is open, like "saves.php" (works on XAMPP and on Vercel), to highlight it in the menu
    $currentPath = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
    $currentPage = basename($currentPath);
    $inAdmin = strpos($currentPath, "/admin/") !== false || substr($currentPath, -6) == "/admin";

    $navLinks = [
        "how_to_play.php" => ["How to Play", "bi-question-circle"],
    ];
    if(isset($_SESSION["account_id"])){
        $navLinks["saves.php"] = ["Saves", "bi-floppy"];
        $navLinks["records.php"] = ["Records", "bi-trophy"];
        $navLinks["account.php"] = ["Profile", "bi-person-circle"];
    }
?>
<!-- menu on top of every page. on small screens the links fold into the menu button -->
<nav id="nav" class="navbar navbar-expand-md">
    <div class="container-fluid">
        <?php if(isset($hideNavLinks) && $hideNavLinks){ ?>
            <!-- battle page: only the title, no links out (Pause > Exit Battle is the way out) -->
            <span class="navbar-brand"><span class="logo logo-small" aria-hidden="true"></span> DotA <span class="brand-sub d-none d-lg-inline">Defense of the Alphards</span></span>
        <?php } else { ?>
            <a class="navbar-brand" href="<?php echo $root; ?>index.php"><span class="logo logo-small" aria-hidden="true"></span> DotA <span class="brand-sub d-none d-lg-inline">Defense of the Alphards</span></a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav-links" aria-controls="nav-links" aria-expanded="false" aria-label="Menu">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="nav-links">
                <ul class="navbar-nav me-auto">
                    <?php foreach($navLinks as $file => $link){ ?>
                        <li class="nav-item">
                            <a class="nav-link <?php if(!$inAdmin && $currentPage == $file){ echo "active"; } ?>" href="<?php echo $root . $file; ?>">
                                <i class="bi <?php echo $link[1]; ?>" aria-hidden="true"></i> <?php echo $link[0]; ?>
                            </a>
                        </li>
                    <?php } ?>
                    <?php if(isset($_SESSION["role"]) && $_SESSION["role"] == "admin"){ ?>
                        <li class="nav-item">
                            <a class="nav-link nav-admin <?php if($inAdmin){ echo "active"; } ?>" href="<?php echo $root; ?>admin/index.php">
                                <i class="bi bi-shield-lock" aria-hidden="true"></i> Admin
                            </a>
                        </li>
                    <?php } ?>
                </ul>

                <div class="nav-right">
                    <?php if(isset($_SESSION["account_id"])){ ?>
                        <span class="nav-user"><i class="bi bi-person" aria-hidden="true"></i> <?php echo htmlspecialchars($_SESSION["username"]); ?></span>
                        <a class="btn btn-sm btn-outline-light" href="<?php echo $root; ?>logout.php"><i class="bi bi-box-arrow-right" aria-hidden="true"></i> Log Out</a>
                    <?php } else { ?>
                        <a class="btn btn-sm btn-outline-light" href="<?php echo $root; ?>login.php"><i class="bi bi-box-arrow-in-right" aria-hidden="true"></i> Log In</a>
                        <a class="btn btn-sm btn-light" href="<?php echo $root; ?>signup.php"><i class="bi bi-person-plus" aria-hidden="true"></i> Sign Up</a>
                    <?php } ?>
                </div>
            </div>
        <?php } ?>
    </div>
</nav>
