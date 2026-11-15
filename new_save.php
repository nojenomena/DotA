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
            $names[$i] = trim($_POST["unit_name"][$i]);

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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Game</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
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
</body>
</html>
