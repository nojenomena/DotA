<?php
    session_start();
    include("includes/config.php");
    include("includes/auth.php");
    include("includes/functions.php");

    requireLogin();

    if(isset($_POST["save_id"])){
        include("includes/db.php");
        include("includes/queries.php");

        $save = getSave((int) $_POST["save_id"], $_SESSION["account_id"]);

        // this save already has a battle that isn't over: go back to it instead of starting a new one
        // (so leaving the page can't reset a fight)
        $unfinished = isset($_SESSION["battle"])
            && $save
            && $_SESSION["battle"]["save_id"] == $save["save_id"]
            && $_SESSION["battle"]["result"] == ""
            && isset($_SESSION["battle"]["party"][0]["status"]);

        if($save && !$unfinished){
            $party = getParty($save["save_id"]);
            $enemyList = getEnemies();

            // equipped items make the party stronger in battle
            $items = getInventory($save["save_id"]);
            foreach($party as $i => $unit){
                $party[$i] = applyEquipment($unit, $items);
            }

            if(count($party) == 0 || count($enemyList) == 0){
                exit("This save has no party, or there are no enemies (did you run dota.sql?)");
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

<?php
    $pageTitle = "Battle - Floor " . $battle["floor"];
    $hideNavLinks = true;    // no menu links in battle: Pause > Exit Battle is the way out
    include("includes/header.php");
    include("includes/nav.php");
?>
    <div id="container">

        <div id="top-bar" class="d-flex flex-wrap align-items-baseline gap-3">
            <h2>Floor <?php echo $battle["floor"]; ?> / <?php echo $config["total_floors"]; ?></h2>
            <span id="round">Round <?php echo $battle["round"]; ?></span>
            <button id="pause-button" class="btn btn-sm btn-outline-light ms-auto" onclick="pauseGame()"><i class="bi bi-pause-fill" aria-hidden="true"></i> Pause</button>
        </div>

        <!-- party on the left, enemies on the right (on a phone: party on top) -->
        <div class="row g-3">
            <div id="party-side" class="col-md-6">
                <?php foreach($battle["party"] as $i => $unit){ ?>
                    <div class="slot" id="party-<?php echo $i; ?>" onclick="clickAlly(<?php echo $i; ?>)">
                        <!-- class picture from config.php, or the first letter if the file isn't there -->
                        <?php $picture = $config["classes"][$unit["unit_class"]]["image"]; ?>
                        <?php if(file_exists($picture)){ ?>
                            <img class="sprite" src="<?php echo $picture; ?>" alt="<?php echo $config["classes"][$unit["unit_class"]]["label"]; ?>">
                        <?php } else { ?>
                            <div class="sprite"><?php echo strtoupper(substr($unit["unit_class"], 0, 1)); ?></div>
                        <?php } ?>
                        <b><?php echo htmlspecialchars($unit["unit_name"]); ?></b> - Lv <?php echo $unit["level"]; ?><br>
                        <span class="small-text"><?php echo $config["classes"][$unit["unit_class"]]["label"]; ?></span>
                        <div class="hp-bar-back"><div class="hp-bar" id="party-bar-<?php echo $i; ?>"></div></div>
                        <span class="hp-text" id="party-hp-<?php echo $i; ?>"></span>
                        <span class="small-text" id="party-status-<?php echo $i; ?>"></span>
                    </div>
                <?php } ?>
            </div>

            <!-- enemies on the right, click one to attack it -->
            <div id="enemy-side" class="col-md-6">
                <?php foreach($battle["enemies"] as $i => $enemy){ ?>
                    <div class="slot enemy-slot" id="enemy-<?php echo $i; ?>" onclick="clickEnemy(<?php echo $i; ?>)">
                        <?php if(!empty($enemy["image"])){ ?>
                            <img class="sprite" src="<?php echo htmlspecialchars($enemy["image"]); ?>" alt="<?php echo htmlspecialchars($enemy["name"]); ?>">
                        <?php } else { ?>
                            <div class="sprite"><?php echo strtoupper(substr($enemy["name"], 0, 1)); ?></div>
                        <?php } ?>
                        <b><?php echo htmlspecialchars($enemy["name"]); ?></b>
                        <?php if($enemy["is_boss"]){ ?><span style="color: red;">[BOSS]</span><?php } ?>
                        <br>
                        <div class="hp-bar-back"><div class="hp-bar" id="enemy-bar-<?php echo $i; ?>"></div></div>
                        <span class="hp-text" id="enemy-hp-<?php echo $i; ?>"></span>
                        <span class="small-text" id="enemy-status-<?php echo $i; ?>"></span>
                    </div>
                <?php } ?>
            </div>

        </div>

        <!-- log on the left, menu on the right (on a phone: menu first, then the log) -->
        <div class="row g-3 mt-1">
            <div class="col-md-7 order-2 order-md-1">
                <div id="battle-log">
                    <?php foreach($battle["log"] as $line){ ?>
                        <div><?php echo htmlspecialchars($line); ?></div>
                    <?php } ?>
                </div>
            </div>

            <div class="col-md-5 order-1 order-md-2">
                <div id="menu-box">
                    <p id="message"></p>
                    <p id="timer"></p>

                    <div id="menu">
                        <button class="command" id="cmd-0" onclick="chooseCommand(0)">Attack</button>
                        <button class="command" id="cmd-1" onclick="chooseCommand(1)">Skill</button><br>
                        <button class="command" id="cmd-2" onclick="chooseCommand(2)">Item</button>
                        <button class="command" id="cmd-3" onclick="chooseCommand(3)">Run</button>
                    </div>

                    <!-- battle.js puts the skill buttons here -->
                    <div id="skill-list" style="display: none;"></div>

                    <p class="hint" style="margin-top: 8px;">WASD / Arrows = move, Enter / Space = choose, Esc = back, P = pause</p>
                </div>
            </div>
        </div>
    </div>

    <!-- big box in the middle: floor intro, victory, game over (battle.js shows it) -->
    <div id="popup" style="display: none;">
        <div id="popup-box">
            <h1 id="popup-title"></h1>
            <div id="popup-text"></div>

            <div id="popup-buttons" style="display: none;">
                <form method="POST" action="battle.php" id="continue-form" style="display: inline;">
                    <input type="hidden" name="save_id" value="<?php echo $battle["save_id"]; ?>">
                    <button type="submit" id="continue-button" class="btn btn-light"><i class="bi bi-arrow-up-circle" aria-hidden="true"></i> Next Floor</button>
                </form>
                <a class="btn btn-outline-light" href="party.php?save=<?php echo $battle["save_id"]; ?>"><i class="bi bi-people" aria-hidden="true"></i> Party</a>
                <a class="btn btn-outline-light" href="records.php"><i class="bi bi-trophy" aria-hidden="true"></i> Records</a>
                <a class="btn btn-outline-light" href="saves.php"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to saves</a>
            </div>

            <p class="hint" id="popup-hint"></p>
        </div>
    </div>

    <!-- pause box (battle.js shows it) -->
    <div id="pause" style="display: none;">
        <div id="pause-box">
            <h1>Paused</h1>
            <p>The timer is stopped.</p>
            <div id="pause-buttons">
                <button id="resume-button" class="btn btn-outline-light" onclick="resumeGame()"><i class="bi bi-play-fill" aria-hidden="true"></i> Resume</button>
                <button id="exit-button" class="btn btn-outline-light" onclick="exitBattle()"><i class="bi bi-door-open" aria-hidden="true"></i> Exit Battle</button>
            </div>
            <p class="hint">Exit Battle counts as running away: no XP, you stay on this floor.</p>
            <p class="hint">Esc / P = resume</p>
        </div>
    </div>

    <script>
        // battle.js reads this
        let battle = <?php echo json_encode($battle, JSON_HEX_TAG | JSON_HEX_AMP); ?>;
        let skills = <?php echo json_encode($config["skills"], JSON_HEX_TAG | JSON_HEX_AMP); ?>;
        let turnSeconds = <?php echo (int) $config["turn_seconds"]; ?>;
    </script>
    <script src="assets/js/battle.js"></script>
<?php include("includes/footer.php"); ?>
