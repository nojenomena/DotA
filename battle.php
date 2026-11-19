<?php
    session_start();
    include("config.php");
    include("functions.php");

    if(!isset($_SESSION["account_id"])){
        header("Location: login.php");
        exit();
    }

    if(isset($_POST["save_id"])){
        include("db.php");
        include("queries.php");

        $save = getSave((int) $_POST["save_id"], $_SESSION["account_id"]);

        if($save){
            $party = getParty($save["save_id"]);
            $enemyList = getEnemies();

            // equipped items make the party stronger in battle
            $items = getInventory($save["save_id"]);
            foreach($party as $i => $unit){
                $party[$i] = applyEquipment($unit, $items);
            }

            if(count($party) == 0 || count($enemyList) == 0){
                exit("This save has no party, or there are no enemies (did you run database/seed.sql?)");
            }

            $floor = (int) $save["current_floor"];
            $enemies = pickEnemies($enemyList, $floor);
            startBattle((int) $save["save_id"], $floor, $party, $enemies);
        }

        // reload without the POST so refreshing the page doesn't start a new battle
        header("Location: battle.php");
        exit();
    }

    if(!isset($_SESSION["battle"])){
        header("Location: saves.php");
        exit();
    }

    $battle = $_SESSION["battle"];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Battle - Floor <?php echo $battle["floor"]; ?></title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div id="container">

        <div id="top-bar">
            <h2>Floor <?php echo $battle["floor"]; ?> / <?php echo $config["total_floors"]; ?></h2>
            &nbsp;&nbsp;
            <span id="round">Round <?php echo $battle["round"]; ?></span>
            <a href="saves.php" style="float: right;">Saves</a>
        </div>

        <!-- party on the left -->
        <div id="party-side">
            <?php foreach($battle["party"] as $i => $unit){ ?>
                <div class="slot" id="party-<?php echo $i; ?>" onclick="clickAlly(<?php echo $i; ?>)">
                    <!-- PLACEHOLDER: unit picture -->
                    <div class="sprite"><?php echo strtoupper(substr($unit["unit_class"], 0, 1)); ?></div>
                    <b><?php echo htmlspecialchars($unit["unit_name"]); ?></b> - Lv <?php echo $unit["level"]; ?><br>
                    <span class="small-text"><?php echo $config["classes"][$unit["unit_class"]]["label"]; ?></span>
                    <div class="hp-bar-back"><div class="hp-bar" id="party-bar-<?php echo $i; ?>"></div></div>
                    <span class="hp-text" id="party-hp-<?php echo $i; ?>"></span>
                    <span class="small-text" id="party-status-<?php echo $i; ?>"></span>
                </div>
            <?php } ?>
        </div>

        <!-- enemies on the right, click one to attack it -->
        <div id="enemy-side">
            <?php foreach($battle["enemies"] as $i => $enemy){ ?>
                <div class="slot enemy-slot" id="enemy-<?php echo $i; ?>" onclick="clickEnemy(<?php echo $i; ?>)">
                    <!-- PLACEHOLDER: enemy picture -->
                    <div class="sprite"><?php echo strtoupper(substr($enemy["name"], 0, 1)); ?></div>
                    <b><?php echo htmlspecialchars($enemy["name"]); ?></b>
                    <?php if($enemy["is_boss"]){ ?><span style="color: red;">[BOSS]</span><?php } ?>
                    <br>
                    <div class="hp-bar-back"><div class="hp-bar" id="enemy-bar-<?php echo $i; ?>"></div></div>
                    <span class="hp-text" id="enemy-hp-<?php echo $i; ?>"></span>
                    <span class="small-text" id="enemy-status-<?php echo $i; ?>"></span>
                </div>
            <?php } ?>
        </div>

        <div class="clear"></div>
        <br>

        <!-- log on the left, menu on the right -->
        <div id="battle-log">
            <?php foreach($battle["log"] as $line){ ?>
                <div><?php echo htmlspecialchars($line); ?></div>
            <?php } ?>
        </div>

        <div id="menu-box">
            <p id="message"></p>

            <div id="menu">
                <button class="command" id="cmd-0" onclick="chooseCommand(0)">Attack</button>
                <button class="command" id="cmd-1" onclick="chooseCommand(1)">Skill</button><br>
                <button class="command" id="cmd-2" onclick="chooseCommand(2)">Item</button>
                <button class="command" id="cmd-3" onclick="chooseCommand(3)">Run</button>
            </div>

            <!-- battle.js puts the skill buttons here -->
            <div id="skill-list" style="display: none;"></div>

            <!-- PLACEHOLDER: real victory / game over screen later -->
            <div id="battle-over" style="display: none;">
                <form method="POST" action="battle.php" id="continue-form" style="display: inline;">
                    <input type="hidden" name="save_id" value="<?php echo $battle["save_id"]; ?>">
                    <button type="submit" id="continue-button">Next Floor</button>
                </form>
                <a href="party.php?save=<?php echo $battle["save_id"]; ?>">Party</a> |
                <a href="saves.php">Back to saves</a>
            </div>

            <p class="hint" style="margin-top: 8px;">WASD / Arrows = move, Enter / Space = choose, Esc = back</p>
        </div>

        <div class="clear"></div>
    </div>

    <script>
        // battle.js reads this
        let battle = <?php echo json_encode($battle, JSON_HEX_TAG | JSON_HEX_AMP); ?>;
        let skills = <?php echo json_encode($config["skills"], JSON_HEX_TAG | JSON_HEX_AMP); ?>;
    </script>
    <script src="battle.js"></script>
</body>
</html>
