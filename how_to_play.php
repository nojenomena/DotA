<?php
    session_start();
    include("includes/config.php");
    include("includes/db.php");
    include("includes/queries.php");

    // the bosses come from the database, so admin changes show up here too
    $bosses = [];
    foreach(getEnemies() as $enemy){
        if($enemy["is_boss"] == "t"){
            $bosses[(int) $enemy["boss_floor"]] = $enemy["enemy_name"];
        }
    }
    ksort($bosses);

    $targetText = [
        "enemy"       => "one enemy",
        "all_enemies" => "all enemies",
        "ally"        => "one ally",
        "all_allies"  => "the whole party",
    ];
?>

<?php
    $pageTitle = "How to Play";
    include("includes/header.php");
    include("includes/nav.php");
?>
    <div id="container" class="rules">
        <h2>How to Play</h2>

        <h3>The goal</h3>
        <p>Your party climbs a tower of <b><?php echo $config["total_floors"]; ?> floors</b>. Every floor is one battle.
           Win to go up a floor. Beat the Final Developer on floor <?php echo $config["total_floors"]; ?> to clear the tower.</p>
        <p>Every <?php echo $config["boss_every"]; ?> floors there is a boss:</p>
        <ul>
            <?php foreach($bosses as $floor => $name){ ?>
                <li>Floor <?php echo $floor; ?>: <b><?php echo htmlspecialchars($name); ?></b></li>
            <?php } ?>
        </ul>

        <h3>Your party</h3>
        <p>You start a game in one of your <?php echo $config["max_save_slots"]; ?> save slots and name your party:
           a <b>Warrior</b>, a <b>Mage</b> and a <b>Cleric</b>.</p>

        <h3>Battles</h3>
        <ul>
            <li>Battles are turn-based. Each round your party picks their moves first, the fastest member (highest speed) goes first.
                Then the enemies attack, also fastest first.</li>
            <li><b>Attack</b> hits one enemy. Higher attack and lower enemy defense = more damage.
                Every hit has a <?php echo $config["crit_chance"]; ?>% chance to be a critical hit (x<?php echo $config["crit_multiplier"]; ?> damage).</li>
            <li><b>Skill</b> uses a class skill (see below). After using one you wait a few rounds before you can use it again.</li>
            <li><b>Run</b> has a <?php echo $config["run_chance"]; ?>% chance to escape. You can't run from bosses. Running keeps you on the same floor.</li>
            <li>Enemies have skills too, and bosses always attack the party member with the lowest HP.</li>
            <li>Each party member has <b><?php echo $config["turn_seconds"]; ?> seconds</b> to pick a move. If the time runs out, they lose their turn.</li>
            <li><b>Pause</b> (or P) stops the timer. From the pause box you can <b>Exit Battle</b>: it counts as running away (no XP, same floor).
                If you leave the page, pressing Play again brings you back to the same battle.</li>
            <li>You <b>win</b> when every enemy is down. You <b>lose</b> when your whole party is down, then you can try the same floor again.</li>
        </ul>

        <h3>Skills</h3>
        <table border="1" class="item-table">
            <tr><th>Class</th><th>Skill</th><th>Unlocks at</th><th>Hits</th><th>Effect</th><th>Wait</th></tr>
            <?php foreach($config["skills"] as $class => $classSkills){ ?>
                <?php foreach($classSkills as $skill){ ?>
                    <tr>
                        <td><?php echo $config["classes"][$class]["label"]; ?></td>
                        <td><?php echo $skill["name"]; ?></td>
                        <td>Lv <?php echo $skill["level"]; ?></td>
                        <td><?php echo $targetText[$skill["target"]]; ?></td>
                        <td><?php echo $skill["effect"] == "heal" ? "heals " . $config["heal_percent"] . "% HP" : ($skill["effect"] == "" ? "-" : ucfirst($skill["effect"])); ?></td>
                        <td><?php echo $skill["cooldown"]; ?> rounds</td>
                    </tr>
                <?php } ?>
            <?php } ?>
        </table>

        <h3>Status effects</h3>
        <ul>
            <?php foreach($config["status_effects"] as $name => $effect){ ?>
                <li><b><?php echo ucfirst($name); ?></b>:
                    <?php if($effect["damage"] > 0){ ?>
                        loses <?php echo $effect["damage"]; ?>% of max HP every turn for <?php echo $effect["turns"]; ?> turns.
                    <?php } else { ?>
                        skips <?php echo $effect["turns"]; ?> turn.
                    <?php } ?>
                </li>
            <?php } ?>
        </ul>

        <h3>Getting stronger</h3>
        <ul>
            <li>Winning gives XP to the whole party. Losing still gives <?php echo $config["xp_on_loss"]; ?>% of it.</li>
            <li>Every level up gives <?php echo $config["points_per_level"]; ?> upgrade points (max level <?php echo $config["max_level"]; ?>).
                Spend them on the <b>Party</b> page: +<?php echo $config["point_hp"]; ?> HP, +<?php echo $config["point_attack"]; ?> attack, +<?php echo $config["point_defense"]; ?> defense or +<?php echo $config["point_speed"]; ?> speed.</li>
            <li>Wins can drop items (bosses always drop one). Equip a weapon, armor and accessory on each member in the <b>Party</b> page.</li>
            <li>Your party is healed after every battle.</li>
        </ul>

        <h3>Score</h3>
        <p>Your score is the total XP you gained in battles. See it on the <b>Records</b> page.</p>

        <h3>Controls</h3>
        <table border="1" class="item-table">
            <tr><th>Key</th><th>What it does</th></tr>
            <tr><td>W A S D / Arrow keys</td><td>move in the menu, pick a target</td></tr>
            <tr><td>Enter / Space</td><td>choose, close the floor intro, Next Floor / Try Again</td></tr>
            <tr><td>Esc / Backspace</td><td>go back (on the main battle menu: pause)</td></tr>
            <tr><td>P</td><td>pause / resume</td></tr>
            <tr><td>Mouse</td><td>click a button, click an enemy to attack it, click a party member to heal them</td></tr>
        </table>

        <br>
        <?php if(isset($_SESSION["account_id"])){ ?>
            <button class="menu-button" onclick="window.location.href='saves.php'">Play</button>
        <?php } else { ?>
            <button class="menu-button" onclick="window.location.href='signup.php'">Sign Up to Play</button>
        <?php } ?>
    </div>
<?php include("includes/footer.php"); ?>
