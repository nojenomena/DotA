<?php
    session_start();
    include("config.php");
    include("functions.php");

    header("Content-Type: application/json");

    if(!isset($_SESSION["account_id"]) || !isset($_SESSION["battle"])){
        echo json_encode(["error" => "No battle found. Go back to your saves."]);
        exit();
    }

    $battle = $_SESSION["battle"];
    $newLog = [];

    $input = json_decode(file_get_contents("php://input"), true);
    $command = isset($input["command"]) ? $input["command"] : "";
    $target = isset($input["target"]) ? (int) $input["target"] : -1;
    $skillNumber = isset($input["skill"]) ? (int) $input["skill"] : -1;

    if($battle["result"] != ""){
        echo json_encode(["error" => "The battle is over."]);
        exit();
    }

    // the party member whose turn it is
    $actor = $battle["order"][$battle["turn"]];
    $unit = $battle["party"][$actor];

    if($command == "attack"){
        if(!isset($battle["enemies"][$target]) || $battle["enemies"][$target]["current_hp"] <= 0){
            echo json_encode(["error" => "Pick an enemy that is still standing."]);
            exit();
        }

        $enemy = $battle["enemies"][$target];
        list($damage, $isCrit) = getDamage($unit["attack"], $enemy["defense"]);
        $battle["enemies"][$target]["current_hp"] = max(0, $enemy["current_hp"] - $damage);

        $line = $unit["unit_name"] . " attacks " . $enemy["name"] . " for " . $damage . " damage!";
        if($isCrit){
            $line = $line . " Critical hit!";
        }
        $newLog[] = $line;

        if($battle["enemies"][$target]["current_hp"] == 0){
            $newLog[] = $enemy["name"] . " is defeated!";
        }

    } else if($command == "skill"){
        $skills = $config["skills"][$unit["unit_class"]];

        if(!isset($skills[$skillNumber])){
            echo json_encode(["error" => "Pick a skill."]);
            exit();
        }

        $skill = $skills[$skillNumber];

        if($unit["level"] < $skill["level"]){
            echo json_encode(["error" => $skill["name"] . " unlocks at Lv " . $skill["level"] . "."]);
            exit();
        }

        if($unit["cooldowns"][$skillNumber] > 0){
            echo json_encode(["error" => $skill["name"] . " is not ready yet (" . $unit["cooldowns"][$skillNumber] . " more rounds)."]);
            exit();
        }

        // who gets hit or healed
        if($skill["target"] == "enemy"){
            if(!isset($battle["enemies"][$target]) || $battle["enemies"][$target]["current_hp"] <= 0){
                echo json_encode(["error" => "Pick an enemy that is still standing."]);
                exit();
            }
            $targets = [$target];
        } else if($skill["target"] == "ally"){
            if(!isset($battle["party"][$target]) || $battle["party"][$target]["current_hp"] <= 0){
                echo json_encode(["error" => "Pick a party member that is still standing."]);
                exit();
            }
            $targets = [$target];
        } else if($skill["target"] == "all_enemies"){
            $targets = getTurnOrder($battle["enemies"]);
        } else {
            $targets = getTurnOrder($battle["party"]);
        }

        $newLog[] = $unit["unit_name"] . " uses " . $skill["name"] . "!";

        foreach($targets as $t){
            if($skill["effect"] == "heal"){
                $ally = $battle["party"][$t];
                $heal = (int) round($ally["max_hp"] * $config["heal_percent"] / 100);
                $heal = min($heal, $ally["max_hp"] - $ally["current_hp"]);

                $battle["party"][$t]["current_hp"] = $ally["current_hp"] + $heal;
                $newLog[] = $ally["unit_name"] . " recovers " . $heal . " HP.";
            } else {
                $enemy = $battle["enemies"][$t];
                list($damage, $isCrit) = getDamage((int) round($unit["attack"] * $skill["power"]), $enemy["defense"]);
                $battle["enemies"][$t]["current_hp"] = max(0, $enemy["current_hp"] - $damage);

                $line = $enemy["name"] . " takes " . $damage . " damage!";
                if($isCrit){
                    $line = $line . " Critical hit!";
                }
                $newLog[] = $line;

                if($battle["enemies"][$t]["current_hp"] == 0){
                    $newLog[] = $enemy["name"] . " is defeated!";
                } else if($skill["effect"] != ""){
                    $effect = $config["status_effects"][$skill["effect"]];
                    $battle["enemies"][$t]["status"][$skill["effect"]] = $effect["turns"];
                    $newLog[] = $enemy["name"] . " " . $effect["text"];
                }
            }
        }

        $battle["party"][$actor]["cooldowns"][$skillNumber] = $skill["cooldown"];

    } else if($command == "run"){
        $bossFight = false;
        foreach($battle["enemies"] as $enemy){
            if($enemy["is_boss"]){
                $bossFight = true;
            }
        }

        if($bossFight){
            $newLog[] = "You can't run from a Developer!";
        } else if(rand(1, 100) <= $config["run_chance"]){
            $battle["result"] = "fled";
            $newLog[] = "Your party got away safely!";
        } else {
            $newLog[] = "Couldn't get away!";
        }

    } else if($command == "item"){
        // PLACEHOLDER: the turn is not used up
        echo json_encode(["error" => "No items you can use in battle yet."]);
        exit();

    } else {
        echo json_encode(["error" => "Unknown command."]);
        exit();
    }

    if($battle["result"] == "" && allDown($battle["enemies"])){
        $battle["result"] = "won";
    }

    // next party member's turn. if everyone went, the enemies attack
    if($battle["result"] == ""){
        $battle["turn"]++;

        if($battle["turn"] >= count($battle["order"])){
            // PLACEHOLDER: enemies just attack someone random (smarter enemies later)
            foreach(getTurnOrder($battle["enemies"]) as $e){
                $enemy = $battle["enemies"][$e];

                // burn, poison and stun happen first
                $stunned = false;
                foreach($enemy["status"] as $name => $turns){
                    $effect = $config["status_effects"][$name];

                    if($effect["damage"] > 0){
                        $damage = max(1, (int) round($enemy["max_hp"] * $effect["damage"] / 100));
                        $battle["enemies"][$e]["current_hp"] = max(0, $battle["enemies"][$e]["current_hp"] - $damage);
                        $newLog[] = $enemy["name"] . " takes " . $damage . " " . $name . " damage!";
                    }

                    if($name == "stun"){
                        $stunned = true;
                        $newLog[] = $enemy["name"] . " is stunned and can't move!";
                    }

                    if($turns <= 1){
                        unset($battle["enemies"][$e]["status"][$name]);
                    } else {
                        $battle["enemies"][$e]["status"][$name] = $turns - 1;
                    }
                }

                if($battle["enemies"][$e]["current_hp"] == 0){
                    $newLog[] = $enemy["name"] . " is defeated!";
                    continue;
                }
                if($stunned){
                    continue;
                }

                $alive = getTurnOrder($battle["party"]);
                if(count($alive) == 0){
                    break;
                }

                $t = $alive[array_rand($alive)];
                $victim = $battle["party"][$t];

                list($damage, $isCrit) = getDamage($enemy["attack"], $victim["defense"]);
                $battle["party"][$t]["current_hp"] = max(0, $victim["current_hp"] - $damage);

                $line = $enemy["name"] . " attacks " . $victim["unit_name"] . " for " . $damage . " damage!";
                if($isCrit){
                    $line = $line . " Critical hit!";
                }
                $newLog[] = $line;

                if($battle["party"][$t]["current_hp"] == 0){
                    $newLog[] = $victim["unit_name"] . " is knocked out!";
                }
            }

            if(allDown($battle["enemies"])){
                $battle["result"] = "won";
            } else if(allDown($battle["party"])){
                $battle["result"] = "lost";
                $newLog[] = "Your party has fallen... Try this floor again.";
            } else {
                // new round, skills cool down by 1
                $battle["round"]++;
                $battle["order"] = getTurnOrder($battle["party"]);
                $battle["turn"] = 0;

                foreach($battle["party"] as $i => $member){
                    foreach($member["cooldowns"] as $c => $rounds){
                        if($rounds > 0){
                            $battle["party"][$i]["cooldowns"][$c] = $rounds - 1;
                        }
                    }
                }

                $newLog[] = "- Round " . $battle["round"] . " -";
            }
        }
    }

    // a win saves xp, levels, the drop and the next floor to the database
    if($battle["result"] == "won"){
        include("db.php");
        include("queries.php");

        $newLog[] = "Victory! All enemies were defeated.";
        $newLog = array_merge($newLog, giveRewards($battle));

        if($battle["floor"] >= $config["total_floors"]){
            $battle["cleared"] = true;
        }
    }

    // keep only the last lines so the session doesn't get too big
    $battle["log"] = array_slice(array_merge($battle["log"], $newLog), -$config["log_max_lines"]);
    $_SESSION["battle"] = $battle;

    echo json_encode(["battle" => $battle, "new_log" => $newLog]);
