<?php
    session_start();
    include("includes/config.php");
    include("includes/auth.php");
    include("includes/db.php");
    include("includes/queries.php");
    include("includes/functions.php");

    requireLogin();

    $saveId = isset($_GET["save"]) ? (int) $_GET["save"] : 0;
    $save = getSave($saveId, $_SESSION["account_id"]);

    // not your save
    if(!$save){
        header("Location: saves.php");
        exit();
    }

    $party = getParty($saveId);
    $items = getInventory($saveId);

    // find the empty roles. position 1 = warrior, 2 = mage, 3 = cleric (same order as starting_party)
    // $emptyRoles looks like [2 => "mage"] if the mage was dismissed
    $emptyRoles = [];
    foreach($config["starting_party"] as $i => $class){
        $position = $i + 1;
        $taken = false;

        foreach($party as $member){
            if($member["party_position"] == $position){
                $taken = true;
            }
        }

        if(!$taken){
            $emptyRoles[$position] = $class;
        }
    }
    // message from the last action (like "Lyra left the party")
    $message = "";
    if(isset($_SESSION["party_message"])){
        $message = $_SESSION["party_message"];
        unset($_SESSION["party_message"]);
    }

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

        } else if($action == "discard" && $item){
            deleteItem($item["instance_id"], $saveId);

        } else if($action == "delete" && $unit){
            deleteUnit($unit["unit_id"], $saveId);
            $_SESSION["party_message"] = $unit["unit_name"] . " left the party. ";

        } else if($action == "recruit"){
            // a new Lv 1 for an empty role. the form only sends the position,
            // the class comes from $emptyRoles so nobody can send a fake class
            $position = isset($_POST["position"]) ? (int) $_POST["position"] : 0;
            $newName = isset($_POST["new_name"]) ? trim($_POST["new_name"]) : "";

            if(!isset($emptyRoles[$position])){
                $_SESSION["party_message"] = "That spot is already taken.";
            } else if(!preg_match("/^[A-Za-z0-9 ]+$/", $newName) || strlen($newName) > $config["unit_name_max"]){
                $_SESSION["party_message"] = "The new name must be 1-" . $config["unit_name_max"] . " characters: letters, numbers and spaces.";
            } else {
                addUnit($saveId, createUnit($newName, $emptyRoles[$position], $position));
                $_SESSION["party_message"] = $newName . " joined!";
            }
        }

        // reload so refreshing the page doesn't do it again
        header("Location: party.php?save=" . $saveId);
        exit();
    }
?>

<?php
    $pageTitle = "Party";
    include("includes/header.php");
    include("includes/nav.php");
?>
    <div id="container">
        <div id="top-bar" class="d-flex flex-wrap align-items-baseline gap-3">
            <h2>Party - Slot <?php echo $save["slot_number"]; ?></h2>
            <span>Floor <?php echo $save["current_floor"]; ?> / <?php echo $config["total_floors"]; ?></span>
            <a class="btn btn-sm btn-outline-light ms-auto" href="saves.php"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to saves</a>
        </div>

        <?php if($message != ""){ ?>
            <div class="alert alert-warning py-2"><?php echo htmlspecialchars($message); ?></div>
        <?php } ?>

        <!-- one column per member: 3 side by side on a laptop, stacked on a phone -->
        <div class="row g-3">
            <?php foreach($party as $unit){ $withItems = applyEquipment($unit, $items); ?>
                <div class="col-md-6 col-lg-4">
                    <div class="save-box h-100">
                        <?php $picture = $config["classes"][$unit["unit_class"]]["image"]; ?>
                        <?php if(file_exists($picture)){ ?>
                            <img class="unit-pic" src="<?php echo $picture; ?>" alt="">
                        <?php } ?>
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
                                    <button type="submit" class="btn btn-sm btn-outline-light"><?php echo $label; ?></button>
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
                                    <button type="submit" class="btn btn-sm btn-outline-light"><i class="bi bi-x-lg" aria-hidden="true"></i> Take off</button>
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
                        <br>

                        <!-- dismiss: this spot becomes empty, recruit someone new after -->
                        <form method="POST" onsubmit="return confirm('Dismiss this party member? They will be gone forever.');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="unit_id" value="<?php echo $unit["unit_id"]; ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-person-x" aria-hidden="true"></i> Dismiss</button>
                        </form>
                    </div>
                </div>
            <?php } ?>

            <!-- one box for every empty role -->
            <?php foreach($emptyRoles as $position => $class){ ?>
                <div class="col-md-6 col-lg-4">
                    <div class="save-box h-100">
                        <?php $picture = $config["classes"][$class]["image"]; ?>
                        <?php if(file_exists($picture)){ ?>
                            <img class="unit-pic empty-pic" src="<?php echo $picture; ?>" alt="">
                        <?php } ?>
                        <h3>Empty <span class="small-text"><?php echo $config["classes"][$class]["label"]; ?></span></h3>
                        <p class="small-text">Recruit a new Lv 1 <?php echo $config["classes"][$class]["label"]; ?>.</p>
                        <br>

                        <form method="POST">
                            <input type="hidden" name="action" value="recruit">
                            <input type="hidden" name="position" value="<?php echo $position; ?>">
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" name="new_name" placeholder="Name" aria-label="Name" maxlength="<?php echo $config["unit_name_max"]; ?>" required>
                                <button type="submit" class="btn btn-light"><i class="bi bi-person-plus" aria-hidden="true"></i> Recruit</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php } ?>
        </div>

        <h3 class="mt-4 mb-3">Inventory</h3>
        <?php if(count($items) == 0){ ?>
            <p class="small-text">No items yet. Win battles to find some.</p>
        <?php } else { ?>
            <div class="table-responsive">
            <table class="table table-bordered table-sm align-middle">
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
                                <select name="unit_id" class="form-select form-select-sm d-inline-block w-auto" aria-label="Party member">
                                    <?php foreach($party as $unit){ ?>
                                        <option value="<?php echo $unit["unit_id"]; ?>" <?php if($item["equipped_by"] == $unit["unit_id"]){ echo "selected"; } ?>>
                                            <?php echo htmlspecialchars($unit["unit_name"]); ?>
                                        </option>
                                    <?php } ?>
                                </select>
                                <button type="submit" class="btn btn-sm btn-outline-light"><?php echo $item["equipped_by"] ? "Move" : "Equip"; ?></button>
                            </form>
                            <form method="POST" class="mt-1" onsubmit="return confirm('Throw away this item? It will be gone forever.');">
                                <input type="hidden" name="action" value="discard">
                                <input type="hidden" name="instance_id" value="<?php echo $item["instance_id"]; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash" aria-hidden="true"></i> Throw away</button>
                            </form>
                        </td>
                    </tr>
                <?php } ?>
            </table>
            </div>
        <?php } ?>
    </div>
<?php include("includes/footer.php"); ?>
