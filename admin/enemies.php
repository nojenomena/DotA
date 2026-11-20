<?php
    session_start();
    $root = "../";
    include("../includes/config.php");
    include("../includes/auth.php");

    requireAdmin();

    include("../includes/db.php");
    include("../includes/queries.php");
    include("../includes/upload.php");

    $errors = [];

    // empty form for "Add enemy"
    $form = [
        "enemy_name" => "", "is_boss" => false, "boss_floor" => "", "min_floor" => 1,
        "base_hp" => 50, "base_attack" => 10, "base_defense" => 5, "base_speed" => 5, "xp_reward" => 20,
        "image" => "",
    ];
    $editId = 0;

    // Edit link: fill the form with that enemy
    if(isset($_GET["edit"])){
        $enemy = getEnemy((int) $_GET["edit"]);
        if($enemy){
            $editId = (int) $enemy["enemy_id"];
            $form = $enemy;
            $form["is_boss"] = $enemy["is_boss"] == "t";
        }
    }

    if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete"])){
        $enemy = getEnemy((int) $_POST["enemy_id"]);

        if(!$enemy){
            $errors[] = "That enemy doesn't exist anymore.";
        } else if($enemy["is_boss"] == "f" && $enemy["min_floor"] == 1 && countFloorOneEnemies() <= 1){
            $errors[] = "You can't delete the last enemy that can appear on floor 1.";
        } else if(deleteEnemy($enemy["enemy_id"])){
            deleteUpload($enemy["image"]);    // its picture goes too
            header("Location: enemies.php?done=deleted");
            exit();
        } else {
            $errors[] = "Couldn't delete the enemy. Try again.";
        }
    } else if($_SERVER["REQUEST_METHOD"] == "POST"){
        $editId = isset($_POST["enemy_id"]) ? (int) $_POST["enemy_id"] : 0;

        $form = [
            "enemy_name"   => trim($_POST["enemy_name"]),
            "is_boss"      => isset($_POST["is_boss"]),
            "boss_floor"   => (int) $_POST["boss_floor"],
            "min_floor"    => (int) $_POST["min_floor"],
            "base_hp"      => (int) $_POST["base_hp"],
            "base_attack"  => (int) $_POST["base_attack"],
            "base_defense" => (int) $_POST["base_defense"],
            "base_speed"   => (int) $_POST["base_speed"],
            "xp_reward"    => (int) $_POST["xp_reward"],
        ];

        // the picture it has now (when editing)
        $oldImage = "";
        if($editId > 0){
            $old = getEnemy($editId);
            if($old && $old["image"] != ""){
                $oldImage = $old["image"];
            }
        }
        $form["image"] = $oldImage;

        // checks
        if(!preg_match("/^[A-Za-z0-9 ,.'-]+$/", $form["enemy_name"]) || strlen($form["enemy_name"]) > 50){
            $errors[] = "Name must be 1-50 characters: letters, numbers, spaces and , . ' -";
        } else if(enemyNameTaken($form["enemy_name"], $editId)){
            $errors[] = "Another enemy already has that name.";
        }

        if($form["base_hp"] < 1 || $form["base_hp"] > 9999){
            $errors[] = "HP must be 1 to 9999.";
        }
        if($form["base_attack"] < 0 || $form["base_attack"] > 999 || $form["base_defense"] < 0 || $form["base_defense"] > 999 || $form["base_speed"] < 0 || $form["base_speed"] > 999){
            $errors[] = "Attack, defense and speed must be 0 to 999.";
        }
        if($form["xp_reward"] < 0 || $form["xp_reward"] > 9999){
            $errors[] = "XP must be 0 to 9999.";
        }

        if($form["is_boss"]){
            // a boss guards one boss floor (5, 10, 15, 20) and only shows up there
            if($form["boss_floor"] < 1 || $form["boss_floor"] > $config["total_floors"] || $form["boss_floor"] % $config["boss_every"] != 0){
                $errors[] = "Boss floor must be one of the boss floors (every " . $config["boss_every"] . " floors, up to " . $config["total_floors"] . ").";
            } else if(bossFloorTaken($form["boss_floor"], $editId)){
                $errors[] = "Another boss already guards floor " . $form["boss_floor"] . ".";
            }
            $form["min_floor"] = $form["boss_floor"];
        } else {
            if($form["min_floor"] < 1 || $form["min_floor"] > $config["total_floors"]){
                $errors[] = "First floor must be 1 to " . $config["total_floors"] . ".";
            }
        }

        // editing the last floor 1 enemy into something else would leave floor 1 empty
        if($editId > 0){
            $old = getEnemy($editId);
            $wasFloorOne = $old && $old["is_boss"] == "f" && $old["min_floor"] == 1;
            $stillFloorOne = !$form["is_boss"] && $form["min_floor"] == 1;
            if($wasFloorOne && !$stillFloorOne && countFloorOneEnemies() <= 1){
                $errors[] = "This is the last enemy that can appear on floor 1, keep it a regular enemy on floor 1.";
            }
        }

        // new picture: only saved when everything else is ok
        $newImage = "";
        $hasFile = isset($_FILES["image"]) && $_FILES["image"]["error"] != UPLOAD_ERR_NO_FILE;
        if(empty($errors) && $hasFile){
            $folder = $form["is_boss"] ? "uploads/bosses/" : "uploads/enemies/";
            $upload = saveUpload($_FILES["image"], $folder, $form["is_boss"] ? "boss" : "enemy");

            if($upload["error"] != ""){
                $errors[] = $upload["error"];
            } else {
                $newImage = $upload["path"];
            }
        }

        if(empty($errors)){
            // which picture to keep: the new one, none (removed), or the old one
            if($newImage != ""){
                $form["image"] = $newImage;
            } else if(isset($_POST["remove_image"])){
                $form["image"] = "";
            }

            // postgres wants "t" / "f" for true / false
            $save = $form;
            $save["is_boss"] = $form["is_boss"] ? "t" : "f";
            $save["is_final_boss"] = ($form["is_boss"] && $form["boss_floor"] == $config["total_floors"]) ? "t" : "f";
            $save["boss_floor"] = $form["is_boss"] ? $form["boss_floor"] : null;
            $save["image"] = $form["image"] != "" ? $form["image"] : null;

            if($editId > 0){
                $ok = updateEnemy($editId, $save);
                $done = "updated";
            } else {
                $ok = addEnemy($save);
                $done = "added";
            }

            if($ok){
                // the old picture was replaced or removed: delete the file
                if($oldImage != "" && $oldImage != $form["image"]){
                    deleteUpload($oldImage);
                }
                header("Location: enemies.php?done=" . $done);
                exit();
            }
            deleteUpload($newImage);
            $errors[] = "Couldn't save the enemy. Try again.";
        }
    }

    $messages = ["added" => "Enemy added.", "updated" => "Enemy updated.", "deleted" => "Enemy deleted."];
    $done = isset($_GET["done"]) && isset($messages[$_GET["done"]]) ? $messages[$_GET["done"]] : "";

    $enemies = getEnemies();

    // bosses at the bottom, then by floor
    usort($enemies, function($a, $b){
        if($a["is_boss"] != $b["is_boss"]){
            return $a["is_boss"] == "t" ? 1 : -1;
        }
        return $a["min_floor"] - $b["min_floor"];
    });
?>

<?php
    $pageTitle = "Admin - Enemies";
    include("../includes/header.php");
    include("../includes/nav.php");
?>
    <div id="container">
        <h2>Enemies and Bosses</h2>
        <p class="hint mb-3"><a href="index.php">Back to dashboard</a></p>

        <?php if($done != ""){ ?>
            <div class="alert alert-success py-2"><?php echo $done; ?></div>
        <?php } ?>
        <?php foreach($errors as $error){ ?>
            <div class="alert alert-danger py-2"><?php echo $error; ?></div>
        <?php } ?>

        <div class="table-responsive">
        <table class="table table-bordered table-sm align-middle">
            <tr>
                <th>Picture</th>
                <th>Name</th>
                <th>Type</th>
                <th>Floor</th>
                <th>HP</th>
                <th>ATK</th>
                <th>DEF</th>
                <th>SPD</th>
                <th>XP</th>
                <th>Skill</th>
                <th></th>
            </tr>
            <?php foreach($enemies as $enemy){ ?>
                <tr>
                    <td>
                        <?php if($enemy["image"] != ""){ ?>
                            <img class="thumb" src="<?php echo htmlspecialchars(imageUrl($enemy["image"], $root)); ?>" alt="">
                        <?php } else { ?>
                            <span class="hint">none</span>
                        <?php } ?>
                    </td>
                    <td><?php echo htmlspecialchars($enemy["enemy_name"]); ?></td>
                    <td>
                        <?php
                            if($enemy["is_final_boss"] == "t"){
                                echo "<b>Final Boss</b>";
                            } else if($enemy["is_boss"] == "t"){
                                echo "<b>Boss</b>";
                            } else {
                                echo "Regular";
                            }
                        ?>
                    </td>
                    <td><?php echo $enemy["is_boss"] == "t" ? $enemy["boss_floor"] : $enemy["min_floor"] . "+"; ?></td>
                    <td><?php echo $enemy["base_hp"]; ?></td>
                    <td><?php echo $enemy["base_attack"]; ?></td>
                    <td><?php echo $enemy["base_defense"]; ?></td>
                    <td><?php echo $enemy["base_speed"]; ?></td>
                    <td><?php echo $enemy["xp_reward"]; ?></td>
                    <td class="small-text">
                        <?php
                            // skills are in config.php, found by the enemy's name
                            if(isset($config["enemy_skills"][$enemy["enemy_name"]])){
                                echo htmlspecialchars($config["enemy_skills"][$enemy["enemy_name"]]["name"]);
                            } else {
                                echo "-";
                            }
                        ?>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-light" onclick="window.location.href='enemies.php?edit=<?php echo $enemy["enemy_id"]; ?>#form'">Edit</button>
                        <form method="POST" action="enemies.php" style="display: inline;" onsubmit="return confirm('Delete this enemy?');">
                            <input type="hidden" name="enemy_id" value="<?php echo $enemy["enemy_id"]; ?>">
                            <input type="hidden" name="delete" value="1">
                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php } ?>
        </table>
        </div>
        <p class="hint" style="margin-top: 5px;">Floor "3+" = can show up from floor 3. Stats are for floor 1 and grow every floor.</p>

        <!-- add / edit form -->
        <div class="admin-form" id="form">
            <?php if($editId > 0){ ?>
                <h3>Edit: <?php echo htmlspecialchars($form["enemy_name"]); ?></h3>
            <?php } else { ?>
                <h3>Add an enemy</h3>
            <?php } ?>

            <!-- multipart/form-data is needed to send a file -->
            <form method="POST" action="enemies.php" enctype="multipart/form-data">
                <input type="hidden" name="enemy_id" value="<?php echo $editId; ?>">

                <label for="enemy_name" class="form-label">Name</label>
                <input type="text" class="form-control mb-3" id="enemy_name" name="enemy_name" maxlength="50" required value="<?php echo htmlspecialchars($form["enemy_name"]); ?>">
                <?php if($editId > 0){ ?>
                    <p class="hint mb-3" style="margin-top: -10px;">Changing the name removes its special skill (skills are found by name in config.php).</p>
                <?php } ?>

                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" id="is_boss" name="is_boss" <?php if($form["is_boss"]){ echo "checked"; } ?>>
                    <label for="is_boss" class="form-check-label">Boss</label>
                </div>

                <label for="boss_floor" class="form-label">Boss floor (only for bosses)</label>
                <input type="number" class="form-control mb-3" id="boss_floor" name="boss_floor" min="0" max="<?php echo $config["total_floors"]; ?>" step="<?php echo $config["boss_every"]; ?>" value="<?php echo (int) $form["boss_floor"]; ?>">

                <label for="min_floor" class="form-label">First floor it shows up on (regular enemies)</label>
                <input type="number" class="form-control mb-3" id="min_floor" name="min_floor" min="1" max="<?php echo $config["total_floors"]; ?>" value="<?php echo (int) $form["min_floor"]; ?>">

                <label for="base_hp" class="form-label">HP</label>
                <input type="number" class="form-control mb-3" id="base_hp" name="base_hp" min="1" max="9999" required value="<?php echo (int) $form["base_hp"]; ?>">

                <label for="base_attack" class="form-label">Attack</label>
                <input type="number" class="form-control mb-3" id="base_attack" name="base_attack" min="0" max="999" required value="<?php echo (int) $form["base_attack"]; ?>">

                <label for="base_defense" class="form-label">Defense</label>
                <input type="number" class="form-control mb-3" id="base_defense" name="base_defense" min="0" max="999" required value="<?php echo (int) $form["base_defense"]; ?>">

                <label for="base_speed" class="form-label">Speed</label>
                <input type="number" class="form-control mb-3" id="base_speed" name="base_speed" min="0" max="999" required value="<?php echo (int) $form["base_speed"]; ?>">

                <label for="xp_reward" class="form-label">XP reward</label>
                <input type="number" class="form-control mb-3" id="xp_reward" name="xp_reward" min="0" max="9999" required value="<?php echo (int) $form["xp_reward"]; ?>">

                <label for="image" class="form-label">Picture (JPG, PNG, GIF or WEBP, max <?php echo $config["upload_max_kb"] / 1024; ?> MB)</label>
                <?php if($form["image"] != ""){ ?>
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <img class="thumb" src="<?php echo htmlspecialchars(imageUrl($form["image"], $root)); ?>" alt="">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="remove_image" name="remove_image">
                            <label for="remove_image" class="form-check-label">Remove this picture</label>
                        </div>
                    </div>
                <?php } ?>
                <input type="file" class="form-control mb-3" id="image" name="image" accept="image/jpeg,image/png,image/gif,image/webp">

                <?php if($editId > 0){ ?>
                    <button type="submit" class="btn btn-light">Save Changes</button>
                    <a class="btn btn-outline-light" href="enemies.php">Cancel</a>
                <?php } else { ?>
                    <button type="submit" class="btn btn-light">Add Enemy</button>
                <?php } ?>
            </form>
        </div>
    </div>
<?php include("../includes/footer.php"); ?>
