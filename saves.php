<?php
    session_start();
    include("includes/config.php");
    include("includes/auth.php");
    include("includes/db.php");
    include("includes/queries.php");

    requireLogin();

    // delete button
    if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_save"])){
        $saveId = (int) $_POST["delete_save"];
        deleteSave($saveId, $_SESSION["account_id"]);

        // stop the battle too if it was from this save
        if(isset($_SESSION["battle"]) && $_SESSION["battle"]["save_id"] == $saveId){
            unset($_SESSION["battle"]);
        }

        header("Location: saves.php");
        exit();
    }

    $saves = getSaves($_SESSION["account_id"]);
?>

<?php
    $pageTitle = "Save Slots";
    include("includes/header.php");
    include("includes/nav.php");
?>
    <div id="container">
        <div id="top-bar" class="d-flex flex-wrap align-items-baseline gap-3">
            <h2>Choose a Save</h2>
        </div>

        <!-- one column per slot: 3 side by side on a laptop, stacked on a phone -->
        <div class="row g-3">
            <?php for($slot = 1; $slot <= $config["max_save_slots"]; $slot++){ ?>
                <div class="col-md-6 col-lg-4">
                    <div class="save-box h-100 d-flex flex-column">
                        <h3>Slot <?php echo $slot; ?></h3>

                        <?php if(isset($saves[$slot])){ $save = $saves[$slot]; ?>
                            <p class="mb-1">
                                <b>Floor <?php echo $save["current_floor"]; ?> / <?php echo $config["total_floors"]; ?></b>
                                <?php if($save["is_cleared"] == "t"){ ?><span style="color: yellow;">(Cleared!)</span><?php } ?>
                            </p>
                            <p class="small-text mb-3">Highest floor: <?php echo $save["highest_floor"]; ?></p>

                            <?php foreach($save["party"] as $unit){ ?>
                                <div class="d-flex align-items-start mb-2">
                                    <?php $picture = $config["classes"][$unit["unit_class"]]["image"]; ?>
                                    <?php if(file_exists($picture)){ ?>
                                        <img class="mini-pic" src="<?php echo $picture; ?>" alt="">
                                    <?php } ?>
                                    <div>
                                        <b><?php echo htmlspecialchars($unit["unit_name"]); ?></b>
                                        <span class="small-text"><?php echo $config["classes"][$unit["unit_class"]]["label"]; ?> Lv <?php echo $unit["level"]; ?></span><br>
                                        <span class="small-text">HP <?php echo $unit["current_hp"]; ?>/<?php echo $unit["max_hp"]; ?> - ATK <?php echo $unit["attack"]; ?> - DEF <?php echo $unit["defense"]; ?> - SPD <?php echo $unit["speed"]; ?></span>
                                    </div>
                                </div>
                            <?php } ?>

                            <!-- buttons at the bottom of the box -->
                            <div class="mt-auto pt-2">
                                <!-- starts (or continues) a battle for this save -->
                                <form method="POST" action="battle.php">
                                    <input type="hidden" name="save_id" value="<?php echo $save["save_id"]; ?>">
                                    <button type="submit" class="btn btn-light w-100 mb-2"><i class="bi bi-play-fill" aria-hidden="true"></i> Play</button>
                                </form>
                                <a class="btn btn-outline-light w-100 mb-2" href="party.php?save=<?php echo $save["save_id"]; ?>"><i class="bi bi-people" aria-hidden="true"></i> Party</a>

                                <form method="POST" onsubmit="return confirm('Delete this save? Your party and items will be gone forever.');">
                                    <input type="hidden" name="delete_save" value="<?php echo $save["save_id"]; ?>">
                                    <button type="submit" class="btn btn-outline-danger w-100"><i class="bi bi-trash" aria-hidden="true"></i> Delete Save</button>
                                </form>
                            </div>
                        <?php } else { ?>
                            <p class="small-text text-center my-auto py-5">Empty</p>
                            <a class="btn btn-light w-100 mt-auto" href="new_save.php?slot=<?php echo $slot; ?>"><i class="bi bi-plus-lg" aria-hidden="true"></i> New Game</a>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>
        </div>

        <p class="mt-4"><a class="btn btn-sm btn-outline-light" href="index.php"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to title</a></p>
    </div>
<?php include("includes/footer.php"); ?>
