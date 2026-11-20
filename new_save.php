<?php
    session_start();
    include("includes/config.php");
    include("includes/auth.php");
    include("includes/db.php");
    include("includes/queries.php");
    include("includes/functions.php");

    requireLogin();

    $slot = isset($_GET["slot"]) ? (int) $_GET["slot"] : 0;
    $saves = getSaves($_SESSION["account_id"]);

    // wrong slot number, or the slot already has a save
    if($slot < 1 || $slot > $config["max_save_slots"] || isset($saves[$slot])){
        header("Location: saves.php");
        exit();
    }

    $errors = [];
    $names = ["", "", ""];

    if($_SERVER["REQUEST_METHOD"] == "POST"){
        $units = [];

        foreach($config["starting_party"] as $i => $class){
            $names[$i] = isset($_POST["unit_name"][$i]) ? trim($_POST["unit_name"][$i]) : "";

            if(!preg_match("/^[A-Za-z0-9 ]+$/", $names[$i]) || strlen($names[$i]) > $config["unit_name_max"]){
                $errors[] = "Your " . $config["classes"][$class]["label"] . "'s name must be 1-" . $config["unit_name_max"] . " characters: letters, numbers and spaces.";
            } else {
                $units[] = createUnit($names[$i], $class, $i + 1);
            }
        }

        if(empty($errors)){
            if(createSave($_SESSION["account_id"], $slot, $units)){
                header("Location: saves.php");
                exit();
            } else {
                $errors[] = "Couldn't create the save. Please try again.";
            }
        }
    }
?>

<?php
    $pageTitle = "New Game";
    include("includes/header.php");
    include("includes/nav.php");
?>
    <div class="form-box" style="width: 420px;">
        <h2>New Game - Slot <?php echo $slot; ?></h2>
        <p class="hint" style="text-align: center; margin-bottom: 15px;">Name your party.</p>

        <?php foreach($errors as $error){ ?>
            <p class="error"><?php echo $error; ?></p>
        <?php } ?>

        <form method="POST" action="new_save.php?slot=<?php echo $slot; ?>">
            <?php foreach($config["starting_party"] as $i => $class){ $stats = $config["classes"][$class]; ?>
                <label for="unit_<?php echo $i; ?>">
                    <?php echo $stats["label"]; ?>
                    <span class="hint">(HP <?php echo $stats["max_hp"]; ?>, ATK <?php echo $stats["attack"]; ?>, DEF <?php echo $stats["defense"]; ?>, SPD <?php echo $stats["speed"]; ?>)</span>
                </label><br>
                <input type="text" id="unit_<?php echo $i; ?>" name="unit_name[<?php echo $i; ?>]" maxlength="<?php echo $config["unit_name_max"]; ?>" value="<?php echo htmlspecialchars($names[$i]); ?>" required><br>
            <?php } ?>

            <button type="submit">Start Climbing</button>
        </form>

        <p class="form-links"><a href="saves.php">Back</a></p>
    </div>
<?php include("includes/footer.php"); ?>
