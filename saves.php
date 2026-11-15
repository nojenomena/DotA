<?php
    session_start();
    include("config.php");
    include("db.php");
    include("queries.php");

    if(!isset($_SESSION["account_id"])){
        header("Location: login.php");
        exit();
    }

    $saves = getSaves($_SESSION["account_id"]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Save Slots</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div id="container">
        <div id="top-bar">
            <h2>Choose a Save</h2>
            <span style="float: right;">
                <?php echo htmlspecialchars($_SESSION["username"]); ?> | <a href="logout.php">Log Out</a>
            </span>
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
</body>
</html>
