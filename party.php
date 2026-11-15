<?php
    session_start();
    include("config.php");
    include("db.php");
    include("queries.php");
    include("functions.php");

    if(!isset($_SESSION["account_id"])){
        header("Location: login.php");
        exit();
    }

    $saveId = isset($_GET["save"]) ? (int) $_GET["save"] : 0;
    $save = getSave($saveId, $_SESSION["account_id"]);

    // not your save
    if(!$save){
        header("Location: saves.php");
        exit();
    }

    $party = getParty($saveId);
    $items = getInventory($saveId);
    $message = "";

    if($_SERVER["REQUEST_METHOD"] == "POST"){
        $action = isset($_POST["action"]) ? $_POST["action"] : "";

        // the unit and item have to be from this save
        $unit = false;
        foreach($party as $member){
            if(isset($_POST["unit_id"]) && $member["unit_id"] == $_POST["unit_id"]){
                $unit = $member;
            }
        }

        $item = false;
        foreach($items as $row){
            if(isset($_POST["instance_id"]) && $row["instance_id"] == $_POST["instance_id"]){
                $item = $row;
            }
        }

        if($action == "upgrade" && $unit && $unit["upgrade_points"] > 0){
            $stat = isset($_POST["stat"]) ? $_POST["stat"] : "";

            if($stat == "max_hp"){
                $unit["max_hp"] = $unit["max_hp"] + $config["point_hp"];
                $unit["current_hp"] = $unit["current_hp"] + $config["point_hp"];
            } else if($stat == "attack"){
                $unit["attack"] = $unit["attack"] + $config["point_attack"];
            } else if($stat == "defense"){
                $unit["defense"] = $unit["defense"] + $config["point_defense"];
            } else if($stat == "speed"){
                $unit["speed"] = $unit["speed"] + $config["point_speed"];
            } else {
                $stat = "";    // not a real stat, don't use the point
            }

            if($stat != ""){
                $unit["upgrade_points"] = $unit["upgrade_points"] - 1;
                updateUnit($unit);
            }

        } else if($action == "equip" && $unit && $item){
            // only 1 weapon, 1 armor and 1 accessory each: take off the old one first
            foreach($items as $row){
                if($row["equipped_by"] == $unit["unit_id"] && $row["slot_type"] == $item["slot_type"]){
                    unequipItem($row["instance_id"], $saveId);
                }
            }
            equipItem($item["instance_id"], $unit["unit_id"], $saveId);

        } else if($action == "unequip" && $item){
            unequipItem($item["instance_id"], $saveId);
        }

        // reload so refreshing the page doesn't do it again
        header("Location: party.php?save=" . $saveId);
        exit();
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Party</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div id="container">
        <div id="top-bar">
            <h2>Party - Slot <?php echo $save["slot_number"]; ?></h2>
            &nbsp;&nbsp; Floor <?php echo $save["current_floor"]; ?> / <?php echo $config["total_floors"]; ?>
            <a href="saves.php" style="float: right;">Back to saves</a>
        </div>

        <?php foreach($party as $unit){ $withItems = applyEquipment($unit, $items); ?>
            <div class="save-box">
                <h3><?php echo htmlspecialchars($unit["unit_name"]); ?> <span class="small-text"><?php echo $config["classes"][$unit["unit_class"]]["label"]; ?> Lv <?php echo $unit["level"]; ?></span></h3>

                <p class="small-text">XP <?php echo $unit["xp"]; ?> / <?php echo xpNeeded($unit["level"]); ?></p>
                <br>

                <!-- stats: own stats, and with items in () -->
                <table class="stat-table">
                    <tr><td>HP</td><td><?php echo $unit["max_hp"]; ?></td><td class="small-text">(<?php echo $withItems["max_hp"]; ?>)</td></tr>
                    <tr><td>ATK</td><td><?php echo $unit["attack"]; ?></td><td class="small-text">(<?php echo $withItems["attack"]; ?>)</td></tr>
                    <tr><td>DEF</td><td><?php echo $unit["defense"]; ?></td><td class="small-text">(<?php echo $withItems["defense"]; ?>)</td></tr>
                    <tr><td>SPD</td><td><?php echo $unit["speed"]; ?></td><td class="small-text">(<?php echo $withItems["speed"]; ?>)</td></tr>
                </table>
                <br>

                <p>Upgrade points: <b><?php echo $unit["upgrade_points"]; ?></b></p>
                <?php if($unit["upgrade_points"] > 0){ ?>
                    <?php foreach(["max_hp" => "+" . $config["point_hp"] . " HP", "attack" => "+" . $config["point_attack"] . " ATK", "defense" => "+" . $config["point_defense"] . " DEF", "speed" => "+" . $config["point_speed"] . " SPD"] as $stat => $label){ ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="upgrade">
                            <input type="hidden" name="unit_id" value="<?php echo $unit["unit_id"]; ?>">
                            <input type="hidden" name="stat" value="<?php echo $stat; ?>">
                            <button type="submit" class="small-button"><?php echo $label; ?></button>
                        </form>
                    <?php } ?>
                <?php } ?>
                <br><br>

                <p>Equipped:</p>
                <?php $hasItem = false; foreach($items as $item){ if($item["equipped_by"] == $unit["unit_id"]){ $hasItem = true; ?>
                    <p class="small-text">
                        <?php echo ucfirst($item["slot_type"]); ?>: <?php echo htmlspecialchars($item["item_name"]); ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="unequip">
                            <input type="hidden" name="instance_id" value="<?php echo $item["instance_id"]; ?>">
                            <button type="submit" class="small-button">Take off</button>
                        </form>
                    </p>
                <?php } } ?>
                <?php if(!$hasItem){ ?>
                    <p class="small-text">Nothing</p>
                <?php } ?>

                <br>
                <p class="small-text">
                    Skills:
                    <?php foreach($config["skills"][$unit["unit_class"]] as $skill){ ?>
                        <br><?php echo $skill["name"]; ?> <?php if($unit["level"] < $skill["level"]){ echo "(Lv " . $skill["level"] . ")"; } ?>
                    <?php } ?>
                </p>
            </div>
        <?php } ?>

        <div class="clear"></div>
        <br>

        <h3>Inventory</h3>
        <br>
        <?php if(count($items) == 0){ ?>
            <p class="small-text">No items yet. Win battles to find some.</p>
        <?php } else { ?>
            <table class="item-table" border="1">
                <tr>
                    <th>Item</th>
                    <th>Type</th>
                    <th>Rarity</th>
                    <th>Bonus</th>
                    <th>Found on</th>
                    <th>Equip</th>
                </tr>
                <?php foreach($items as $item){ ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item["item_name"]); ?></td>
                        <td><?php echo ucfirst($item["slot_type"]); ?></td>
                        <td class="rarity-<?php echo $item["rarity"]; ?>"><?php echo ucfirst($item["rarity"]); ?></td>
                        <td class="small-text">
                            <?php
                                // only show the stats it changes, like "ATK x1.20"
                                $bonus = [];
                                if($item["hp_mult"] != 1){ $bonus[] = "HP x" . $item["hp_mult"]; }
                                if($item["atk_mult"] != 1){ $bonus[] = "ATK x" . $item["atk_mult"]; }
                                if($item["def_mult"] != 1){ $bonus[] = "DEF x" . $item["def_mult"]; }
                                if($item["spd_mult"] != 1){ $bonus[] = "SPD x" . $item["spd_mult"]; }
                                echo implode(", ", $bonus);
                            ?>
                        </td>
                        <td>Floor <?php echo $item["obtained_floor"]; ?></td>
                        <td>
                            <form method="POST">
                                <input type="hidden" name="action" value="equip">
                                <input type="hidden" name="instance_id" value="<?php echo $item["instance_id"]; ?>">
                                <select name="unit_id">
                                    <?php foreach($party as $unit){ ?>
                                        <option value="<?php echo $unit["unit_id"]; ?>" <?php if($item["equipped_by"] == $unit["unit_id"]){ echo "selected"; } ?>>
                                            <?php echo htmlspecialchars($unit["unit_name"]); ?>
                                        </option>
                                    <?php } ?>
                                </select>
                                <button type="submit" class="small-button"><?php echo $item["equipped_by"] ? "Move" : "Equip"; ?></button>
                            </form>
                        </td>
                    </tr>
                <?php } ?>
            </table>
        <?php } ?>
    </div>
</body>
</html>
