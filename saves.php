<?php
    session_start();
    include("includes/config.php");
    include("includes/db.php");
    include("includes/queries.php");

    if(!isset($_SESSION["account_id"])){
        header("Location: login.php");
        exit();
    }

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
        <div id="top-bar">
            <h2>Choose a Save</h2>
        </div>

        <?php for($slot = 1; $slot <= $config["max_save_slots"]; $slot++){ ?>
            <div class="save-box">
                <h3>Slot <?php echo $slot; ?></h3>

                <?php if(isset($saves[$slot])){ $save = $saves[$slot]; ?>
                    <p>
                        <b>Floor <?php echo $save["current_floor"]; ?> / <?php echo $config["total_floors"]; ?></b>
                        <?php if($save["is_cleared"] == "t"){ ?><span style="color: yellow;">(Cleared!)</span><?php } ?>
                    </p>
                    <p class="small-text">Highest floor: <?php echo $save["highest_floor"]; ?></p>
                    <br>

                    <?php foreach($save["party"] as $unit){ ?>
                        <p>
                            <b><?php echo htmlspecialchars($unit["unit_name"]); ?></b>
                            <span class="small-text"><?php echo $config["classes"][$unit["unit_class"]]["label"]; ?> Lv <?php echo $unit["level"]; ?></span><br>
                            <span class="small-text">HP <?php echo $unit["current_hp"]; ?>/<?php echo $unit["max_hp"]; ?> - ATK <?php echo $unit["attack"]; ?> - DEF <?php echo $unit["defense"]; ?> - SPD <?php echo $unit["speed"]; ?></span>
                        </p>
                    <?php } ?>

                    <!-- starts a battle for this save -->
                    <form method="POST" action="battle.php">
                        <input type="hidden" name="save_id" value="<?php echo $save["save_id"]; ?>">
                        <button type="submit">Play</button>
                    </form>
                    <button onclick="window.location.href='party.php?save=<?php echo $save["save_id"]; ?>'">Party</button>

                    <form method="POST" onsubmit="return confirm('Delete this save? Your party and items will be gone forever.');">
                        <input type="hidden" name="delete_save" value="<?php echo $save["save_id"]; ?>">
                        <button type="submit" class="delete-button">Delete Save</button>
                    </form>
                <?php } else { ?>
                    <p class="small-text" style="text-align: center; margin-top: 80px;">Empty</p>
                    <button onclick="window.location.href='new_save.php?slot=<?php echo $slot; ?>'" style="margin-top: 70px;">New Game</button>
                <?php } ?>
            </div>
        <?php } ?>

        <div class="clear"></div>
        <br>
        <p><a href="index.php">Back to title</a></p>
    </div>
<?php include("includes/footer.php"); ?>
