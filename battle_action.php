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

    } else if($command == "skill" || $command == "item"){
        // PLACEHOLDER: the turn is not used up
        echo json_encode(["error" => "Skills and items are not done yet."]);
        exit();

    } else {
        echo json_encode(["error" => "Unknown command."]);
        exit();
    }

    if($battle["result"] == "" && allDown($battle["enemies"])){
        $battle["result"] = "won";
        $newLog[] = "Victory! All enemies were defeated.";
    }

    // next party member's turn. if everyone went, the enemies attack
    if($battle["result"] == ""){
        $battle["turn"]++;

        if($battle["turn"] >= count($battle["order"])){
            // PLACEHOLDER: enemies just attack someone random (smarter enemies later)
            foreach(getTurnOrder($battle["enemies"]) as $e){
                $alive = getTurnOrder($battle["party"]);
                if(count($alive) == 0){
                    break;
                }

                $enemy = $battle["enemies"][$e];
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

            if(allDown($battle["party"])){
                $battle["result"] = "lost";
                $newLog[] = "Your party has fallen...";
            } else {
                // new round
                $battle["round"]++;
                $battle["order"] = getTurnOrder($battle["party"]);
                $battle["turn"] = 0;
                $newLog[] = "- Round " . $battle["round"] . " -";
            }
        }
    }

    // keep only the last lines so the session doesn't get too big
    $battle["log"] = array_slice(array_merge($battle["log"], $newLog), -$config["log_max_lines"]);
    $_SESSION["battle"] = $battle;

    echo json_encode(["battle" => $battle, "new_log" => $newLog]);
