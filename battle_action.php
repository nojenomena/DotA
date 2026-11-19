<?php
    session_start();
    include("includes/config.php");
    include("includes/functions.php");

    header("Content-Type: application/json");

    if(!isset($_SESSION["account_id"]) || !isset($_SESSION["battle"])){
        echo json_encode(["error" => "No battle found. Go back to your saves."]);
        exit();
    }

    $battle = $_SESSION["battle"];
    $newLog = [];

    if(!isset($battle["party"][0]["status"])){
        echo json_encode(["error" => "This battle is from an older version. Go back to your saves and press Play."]);
        exit();
    }

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
    }

    // this loop keeps going while someone has to be skipped (stunned or knocked out by poison)
    $safety = 0;
    while($battle["result"] == "" && $safety < 20){
        $safety++;

        // ---------- enemy phase: everyone in the party has gone ----------
        if($battle["turn"] >= count($battle["order"])){
            foreach(getTurnOrder($battle["enemies"]) as $e){
                // burn, poison and stun first
                list($battle["enemies"][$e], $lines, $stunned) = tickStatus($battle["enemies"][$e], $battle["enemies"][$e]["name"]);
                $newLog = array_merge($newLog, $lines);

                $enemy = $battle["enemies"][$e];

                if($enemy["current_hp"] == 0){
                    $newLog[] = $enemy["name"] . " is defeated!";
                    continue;
                }
                if($stunned){
                    continue;
                }
                if(allDown($battle["party"])){
                    break;
                }

                // use its skill sometimes, otherwise a normal attack
                $skill = isset($config["enemy_skills"][$enemy["type"]]) ? $config["enemy_skills"][$enemy["type"]] : false;

                if($skill && rand(1, 100) <= $skill["chance"]){
                    $newLog[] = $enemy["name"] . " uses " . $skill["name"] . "!";

                    if($skill["target"] == "all"){
                        $targets = getTurnOrder($battle["party"]);
                    } else {
                        $targets = [pickTarget($battle["party"], $enemy["is_boss"])];
                    }
                    $power = $skill["power"];
                } else {
                    $skill = false;
                    $targets = [pickTarget($battle["party"], $enemy["is_boss"])];
                    $power = 1;
                }

                foreach($targets as $t){
                    $victim = $battle["party"][$t];

                    list($damage, $isCrit) = getDamage((int) round($enemy["attack"] * $power), $victim["defense"]);
                    $battle["party"][$t]["current_hp"] = max(0, $victim["current_hp"] - $damage);

                    if($skill){
                        $line = $victim["unit_name"] . " takes " . $damage . " damage!";
                    } else {
                        $line = $enemy["name"] . " attacks " . $victim["unit_name"] . " for " . $damage . " damage!";
                    }
                    if($isCrit){
                        $line = $line . " Critical hit!";
                    }
                    $newLog[] = $line;

                    if($battle["party"][$t]["current_hp"] == 0){
                        $newLog[] = $victim["unit_name"] . " is knocked out!";
                    } else if($skill && $skill["effect"] != ""){
                        $effect = $config["status_effects"][$skill["effect"]];
                        $battle["party"][$t]["status"][$skill["effect"]] = $effect["turns"];
                        $newLog[] = $victim["unit_name"] . " " . $effect["text"];
                    }
                }
            }

            if(allDown($battle["enemies"])){
                $battle["result"] = "won";
                break;
            }
            if(allDown($battle["party"])){
                $battle["result"] = "lost";
                $newLog[] = "Your party has fallen... Try this floor again.";
                break;
            }

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

        // ---------- start of a party member's turn: burn, poison and stun ----------
        $p = $battle["order"][$battle["turn"]];
        list($battle["party"][$p], $lines, $stunned) = tickStatus($battle["party"][$p], $battle["party"][$p]["unit_name"]);
        $newLog = array_merge($newLog, $lines);

        if($battle["party"][$p]["current_hp"] == 0){
            $newLog[] = $battle["party"][$p]["unit_name"] . " is knocked out!";

            if(allDown($battle["party"])){
                $battle["result"] = "lost";
                $newLog[] = "Your party has fallen... Try this floor again.";
                break;
            }
        }

        // can't act: go to the next one
        if($battle["party"][$p]["current_hp"] == 0 || $stunned){
            $battle["turn"]++;
            continue;
        }

        // this party member can act, wait for the player
        break;
    }

    // the battle just ended: give the rewards and save the result for the records page
    if($battle["result"] != ""){
        include("includes/db.php");
        include("includes/queries.php");

        $xpGained = 0;

        // a win saves xp, levels, the drop and the next floor
        if($battle["result"] == "won"){
            $newLog[] = "Victory! All enemies were defeated.";
            $newLog = array_merge($newLog, giveRewards($battle));
            $xpGained = battleXp($battle);

            if($battle["floor"] >= $config["total_floors"]){
                $battle["cleared"] = true;
            }
        }

        // a loss still gives some xp (running away gives nothing)
        if($battle["result"] == "lost"){
            $newLog = array_merge($newLog, giveLossXp($battle));
            $xpGained = (int) round(battleXp($battle) * $config["xp_on_loss"] / 100);
        }

        addBattleResult($battle["save_id"], $battle["floor"], $battle["result"], $battle["round"], $xpGained);
    }

    // keep only the last lines so the session doesn't get too big
    $battle["log"] = array_slice(array_merge($battle["log"], $newLog), -$config["log_max_lines"]);
    $_SESSION["battle"] = $battle;

    echo json_encode(["battle" => $battle, "new_log" => $newLog]);
