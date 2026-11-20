<?php
    session_start();
    $root = "../";
    include("../includes/config.php");
    include("../includes/auth.php");

    requireAdmin();

    include("../includes/db.php");
    include("../includes/queries.php");

    $slots = ["weapon", "armor", "accessory"];
    $rarities = array_keys($config["drop_rarity"]);    // common, rare, epic, legendary
    $stats = ["hp_mult" => "HP", "atk_mult" => "Attack", "def_mult" => "Defense", "spd_mult" => "Speed"];

    $errors = [];

    // empty form for "Add item"
    $form = [
        "item_name" => "", "slot_type" => "weapon", "rarity" => "common",
        "hp_mult" => "1.00", "atk_mult" => "1.00", "def_mult" => "1.00", "spd_mult" => "1.00",
        "min_floor" => 1, "description" => "",
    ];
    $editId = 0;

    // Edit link: fill the form with that item
    if(isset($_GET["edit"])){
        $item = getItem((int) $_GET["edit"]);
        if($item){
            $editId = (int) $item["item_id"];
            $form = $item;
        }
    }

    if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete"])){
        $itemId = (int) $_POST["item_id"];
        $owned = countOwned($itemId);

        // players have it in their inventory: don't take it away from them
        if($owned > 0){
            $errors[] = "Players own " . $owned . " of this item, so it can't be deleted. You can still edit it.";
        } else if(deleteItemTemplate($itemId)){
            header("Location: items.php?done=deleted");
            exit();
        } else {
            $errors[] = "Couldn't delete the item. Try again.";
        }
    } else if($_SERVER["REQUEST_METHOD"] == "POST"){
        $editId = isset($_POST["item_id"]) ? (int) $_POST["item_id"] : 0;

        $form = [
            "item_name"   => trim($_POST["item_name"]),
            "slot_type"   => $_POST["slot_type"],
            "rarity"      => $_POST["rarity"],
            "min_floor"   => (int) $_POST["min_floor"],
            "description" => trim($_POST["description"]),
        ];
        foreach($stats as $key => $label){
            $form[$key] = number_format((float) $_POST[$key], 2, ".", "");
        }

        // checks
        if(!preg_match("/^[A-Za-z0-9 ,.'-]+$/", $form["item_name"]) || strlen($form["item_name"]) > 50){
            $errors[] = "Name must be 1-50 characters: letters, numbers, spaces and , . ' -";
        } else if(itemNameTaken($form["item_name"], $editId)){
            $errors[] = "Another item already has that name.";
        }

        if(!in_array($form["slot_type"], $slots)){
            $errors[] = "Pick a slot: weapon, armor or accessory.";
        }
        if(!in_array($form["rarity"], $rarities)){
            $errors[] = "Pick a rarity.";
        }

        foreach($stats as $key => $label){
            if($form[$key] < 0.5 || $form[$key] > 3){
                $errors[] = $label . " bonus must be 0.50 to 3.00 (1.00 = no change).";
            }
        }

        if($form["min_floor"] < 1 || $form["min_floor"] > $config["total_floors"]){
            $errors[] = "First floor must be 1 to " . $config["total_floors"] . ".";
        }
        if(strlen($form["description"]) > 200){
            $errors[] = "Description can be up to 200 characters.";
        }

        if(empty($errors)){
            if($editId > 0){
                $ok = updateItemTemplate($editId, $form);
                $done = "updated";
            } else {
                $ok = addItemTemplate($form);
                $done = "added";
            }

            if($ok){
                header("Location: items.php?done=" . $done);
                exit();
            }
            $errors[] = "Couldn't save the item. Try again.";
        }
    }

    $messages = ["added" => "Item added.", "updated" => "Item updated.", "deleted" => "Item deleted."];
    $done = isset($_GET["done"]) && isset($messages[$_GET["done"]]) ? $messages[$_GET["done"]] : "";

    $items = getAllItems();
?>

<?php
    $pageTitle = "Admin - Items";
    include("../includes/header.php");
    include("../includes/nav.php");
?>
    <div id="container">
        <h2>Items</h2>
        <p class="hint" style="margin-bottom: 15px;"><a href="index.php">Back to dashboard</a></p>

        <?php if($done != ""){ ?>
            <p class="success"><?php echo $done; ?></p>
        <?php } ?>
        <?php foreach($errors as $error){ ?>
            <p class="error"><?php echo $error; ?></p>
        <?php } ?>

        <table border="1" class="item-table">
            <tr>
                <th>Name</th>
                <th>Slot</th>
                <th>Rarity</th>
                <th>HP</th>
                <th>ATK</th>
                <th>DEF</th>
                <th>SPD</th>
                <th>Floor</th>
                <th>Owned</th>
                <th></th>
            </tr>
            <?php foreach($items as $item){ ?>
                <tr>
                    <td>
                        <?php echo htmlspecialchars($item["item_name"]); ?><br>
                        <span class="small-text"><?php echo htmlspecialchars($item["description"]); ?></span>
                    </td>
                    <td><?php echo ucfirst($item["slot_type"]); ?></td>
                    <td class="rarity-<?php echo $item["rarity"]; ?>"><?php echo ucfirst($item["rarity"]); ?></td>
                    <td><?php echo $item["hp_mult"]; ?></td>
                    <td><?php echo $item["atk_mult"]; ?></td>
                    <td><?php echo $item["def_mult"]; ?></td>
                    <td><?php echo $item["spd_mult"]; ?></td>
                    <td><?php echo $item["min_floor"]; ?>+</td>
                    <td><?php echo $item["owned"]; ?></td>
                    <td>
                        <button class="small-button" onclick="window.location.href='items.php?edit=<?php echo $item["item_id"]; ?>#form'">Edit</button>
                        <form method="POST" action="items.php" style="display: inline;" onsubmit="return confirm('Delete this item?');">
                            <input type="hidden" name="item_id" value="<?php echo $item["item_id"]; ?>">
                            <input type="hidden" name="delete" value="1">
                            <button type="submit" class="small-button delete-button">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php } ?>
        </table>
        <p class="hint" style="margin-top: 5px;">Bonuses multiply the stat: 1.20 = +20%, 0.95 = -5%. Floor "4+" = can drop from floor 4.</p>

        <!-- add / edit form -->
        <div class="admin-form" id="form">
            <?php if($editId > 0){ ?>
                <h3>Edit: <?php echo htmlspecialchars($form["item_name"]); ?></h3>
            <?php } else { ?>
                <h3>Add an item</h3>
            <?php } ?>

            <form method="POST" action="items.php">
                <input type="hidden" name="item_id" value="<?php echo $editId; ?>">

                <label for="item_name">Name</label><br>
                <input type="text" id="item_name" name="item_name" maxlength="50" required value="<?php echo htmlspecialchars($form["item_name"]); ?>"><br>

                <label for="slot_type">Slot</label><br>
                <select id="slot_type" name="slot_type">
                    <?php foreach($slots as $slot){ ?>
                        <option value="<?php echo $slot; ?>" <?php if($form["slot_type"] == $slot){ echo "selected"; } ?>><?php echo ucfirst($slot); ?></option>
                    <?php } ?>
                </select><br>

                <label for="rarity">Rarity</label><br>
                <select id="rarity" name="rarity">
                    <?php foreach($rarities as $rarity){ ?>
                        <option value="<?php echo $rarity; ?>" <?php if($form["rarity"] == $rarity){ echo "selected"; } ?>><?php echo ucfirst($rarity); ?></option>
                    <?php } ?>
                </select><br>

                <?php foreach($stats as $key => $label){ ?>
                    <label for="<?php echo $key; ?>"><?php echo $label; ?> bonus</label><br>
                    <input type="number" id="<?php echo $key; ?>" name="<?php echo $key; ?>" min="0.5" max="3" step="0.01" required value="<?php echo htmlspecialchars($form[$key]); ?>"><br>
                <?php } ?>

                <label for="min_floor">First floor it can drop on</label><br>
                <input type="number" id="min_floor" name="min_floor" min="1" max="<?php echo $config["total_floors"]; ?>" required value="<?php echo (int) $form["min_floor"]; ?>"><br>

                <label for="description">Description</label><br>
                <input type="text" id="description" name="description" maxlength="200" value="<?php echo htmlspecialchars($form["description"]); ?>"><br>

                <?php if($editId > 0){ ?>
                    <button type="submit">Save Changes</button>
                    <button type="button" onclick="window.location.href='items.php'">Cancel</button>
                <?php } else { ?>
                    <button type="submit">Add Item</button>
                <?php } ?>
            </form>
        </div>
    </div>
<?php include("../includes/footer.php"); ?>
