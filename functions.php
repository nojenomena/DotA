<?php
    // game formulas

    function isBossFloor($floor){
        global $config;

        return $floor % $config["boss_every"] == 0;
    }

    // makes a new level 1 party member
    function createUnit($name, $class, $position){
        global $config;

        $stats = $config["classes"][$class];

        return [
            "unit_name"      => $name,
            "unit_class"     => $class,
            "party_position" => $position,
            "level"          => 1,
            "xp"             => 0,
            "upgrade_points" => 0,
            "max_hp"         => $stats["max_hp"],
            "current_hp"     => $stats["max_hp"],
            "attack"         => $stats["attack"],
            "defense"        => $stats["defense"],
            "speed"          => $stats["speed"],
        ];
    }

    // turns an enemy_templates row into an enemy for this floor (stronger on higher floors)
    function makeEnemy($row, $floor){
        global $config;

        $floorsUp = $floor - 1;
        $hp = (int) round($row["base_hp"] * (1 + $config["enemy_hp_per_floor"] * $floorsUp));

        return [
            "name"       => $row["enemy_name"],
            "type"       => $row["enemy_name"],    // name stays the same even when it becomes "Bug 2"
            "is_boss"    => $row["is_boss"] == "t",    // postgres gives "t" or "f"
            "max_hp"     => $hp,
            "current_hp" => $hp,
            "attack"     => (int) round($row["base_attack"] * (1 + $config["enemy_attack_per_floor"] * $floorsUp)),
            "defense"    => (int) round($row["base_defense"] * (1 + $config["enemy_defense_per_floor"] * $floorsUp)),
            "speed"      => (int) round($row["base_speed"] * (1 + $config["enemy_speed_per_floor"] * $floorsUp)),
            "xp_reward"  => (int) round($row["xp_reward"] * (1 + $config["enemy_xp_per_floor"] * $floorsUp)),
        ];
    }

    // boss floor = that floor's boss, other floors = 1 to 3 random regular enemies
    function pickEnemies($rows, $floor){
        global $config;

        $enemies = [];

        if(isBossFloor($floor)){
            foreach($rows as $row){
                if($row["is_boss"] == "t" && $row["boss_floor"] == $floor){
                    $enemies[] = makeEnemy($row, $floor);
                }
            }
        }

        if(count($enemies) == 0){
            $pool = [];
            foreach($rows as $row){
                if($row["is_boss"] == "f" && $row["min_floor"] <= $floor){
                    $pool[] = $row;
                }
            }

            $count = rand($config["enemies_min"], $config["enemies_max"]);
            for($i = 0; $i < $count; $i++){
                $enemies[] = makeEnemy($pool[array_rand($pool)], $floor);
            }
        }

        // two enemies with the same name get a number: "Bug 1", "Bug 3"
        $names = [];
        foreach($enemies as $enemy){
            $names[] = $enemy["name"];
        }
        foreach($enemies as $i => $enemy){
            if(count(array_keys($names, $enemy["name"])) > 1){
                $enemies[$i]["name"] = $enemy["name"] . " " . ($i + 1);
            }
        }

        return $enemies;
    }

    // returns [damage, isCrit]
    function getDamage($attack, $defense){
        global $config;

        $k = $config["defense_constant"];
        $damage = $attack * $k / ($k + $defense);
        $damage = $damage * rand(90, 110) / 100;    // randomizer

        $isCrit = rand(1, 100) <= $config["crit_chance"];
        if($isCrit){
            $damage = $damage * $config["crit_multiplier"];
        }

        return [max(1, (int) round($damage)), $isCrit];
    }

    // true if everyone in the list has 0 hp
    function allDown($fighters){
        foreach($fighters as $fighter){
            if($fighter["current_hp"] > 0){
                return false;
            }
        }
        return true;
    }

    // list of who is still alive, fastest first (works for party and enemies)
    function getTurnOrder($fighters){
        $order = [];
        foreach($fighters as $i => $fighter){
            if($fighter["current_hp"] > 0){
                $order[] = $i;
            }
        }

        usort($order, function($a, $b) use ($fighters){
            return $fighters[$b]["speed"] - $fighters[$a]["speed"];
        });

        return $order;
    }

    // saves a new battle in the session
    function startBattle($saveId, $floor, $party, $enemies){
        global $config;

        foreach($party as $i => $unit){
            // battles start at full hp (the party heals after every battle)
            $party[$i]["current_hp"] = $unit["max_hp"];
            $party[$i]["status"] = [];

            // one cooldown per skill, 0 = ready
            $party[$i]["cooldowns"] = [];
            foreach($config["skills"][$unit["unit_class"]] as $skill){
                $party[$i]["cooldowns"][] = 0;
            }
        }

        foreach($enemies as $i => $enemy){
            $enemies[$i]["status"] = [];    // like ["burn" => 2] = burning for 2 more turns
        }

        $names = [];
        foreach($enemies as $enemy){
            $names[] = $enemy["name"];
        }

        $_SESSION["battle"] = [
            "save_id" => $saveId,
            "floor"   => $floor,
            "round"   => 1,
            "party"   => $party,
            "enemies" => $enemies,
            "order"   => getTurnOrder($party),   // party members' turns this round
            "turn"    => 0,                      // whose turn in "order"
            "result"  => "",
            "cleared" => false,                  // true after beating the final boss
            "log"     => [implode(", ", $names) . " appeared!"],
        ];
    }

    // party stats with their equipped items (items = rows from getInventory)
    function applyEquipment($unit, $items){
        foreach($items as $item){
            if($item["equipped_by"] == $unit["unit_id"]){
                $unit["max_hp"]  = (int) round($unit["max_hp"] * $item["hp_mult"]);
                $unit["attack"]  = (int) round($unit["attack"] * $item["atk_mult"]);
                $unit["defense"] = (int) round($unit["defense"] * $item["def_mult"]);
                $unit["speed"]   = (int) round($unit["speed"] * $item["spd_mult"]);
            }
        }
        return $unit;
    }

    function xpNeeded($level){
        global $config;

        return (int) round($config["xp_base"] * pow($level, $config["xp_exponent"]));
    }

    // adds xp and does the level ups. "levels_gained" says how many
    function addXp($unit, $xp){
        global $config;

        $class = $config["classes"][$unit["unit_class"]];
        $unit["xp"] = $unit["xp"] + $xp;
        $unit["levels_gained"] = 0;

        while($unit["level"] < $config["max_level"] && $unit["xp"] >= xpNeeded($unit["level"])){
            $unit["xp"] = $unit["xp"] - xpNeeded($unit["level"]);
            $unit["level"]++;
            $unit["levels_gained"]++;
            $unit["upgrade_points"] = $unit["upgrade_points"] + $config["points_per_level"];

            $unit["max_hp"]  = $unit["max_hp"] + $class["hp_up"];
            $unit["attack"]  = $unit["attack"] + $class["attack_up"];
            $unit["defense"] = $unit["defense"] + $class["defense_up"];
            $unit["speed"]   = $unit["speed"] + $class["speed_up"];
        }

        // no more xp at max level
        if($unit["level"] >= $config["max_level"]){
            $unit["xp"] = 0;
        }

        return $unit;
    }

    // picks a rarity using the weights in config, or "" for no drop
    function rollDrop($isBoss){
        global $config;

        if($isBoss){
            $chance = $config["boss_drop_chance"];
            $rarities = $config["boss_drop_rarity"];
        } else {
            $chance = $config["drop_chance"];
            $rarities = $config["drop_rarity"];
        }

        if(rand(1, 100) > $chance){
            return "";
        }

        $roll = rand(1, array_sum($rarities));
        foreach($rarities as $rarity => $weight){
            if($roll <= $weight){
                return $rarity;
            }
            $roll = $roll - $weight;
        }
        return "";
    }

    // after a win: xp, level ups, a drop and the next floor, all saved to the database
    // needs db.php and queries.php. returns the lines for the battle log
    function giveRewards($battle){
        global $config;

        $lines = [];
        $saveId = $battle["save_id"];
        $floor = $battle["floor"];

        // xp from every enemy
        $xp = 0;
        $bossFight = false;
        foreach($battle["enemies"] as $enemy){
            $xp = $xp + $enemy["xp_reward"];
            if($enemy["is_boss"]){
                $bossFight = true;
            }
        }
        $lines[] = "Everyone gets " . $xp . " XP.";

        // the real stats come from the database, not the battle (the battle has the item bonuses)
        foreach(getParty($saveId) as $unit){
            $unit = addXp($unit, $xp);

            if($config["heal_after_battle"]){
                $unit["current_hp"] = $unit["max_hp"];
            }

            updateUnit($unit);

            if($unit["levels_gained"] > 0){
                $lines[] = $unit["unit_name"] . " reached Lv " . $unit["level"] . "! (+" . ($unit["levels_gained"] * $config["points_per_level"]) . " upgrade points)";
            }
        }

        // item drop
        $rarity = rollDrop($bossFight);
        if($rarity != ""){
            $item = getRandomItem($rarity, $floor);
            if($item){
                addItem($saveId, $item["item_id"], $floor);
                $lines[] = "Found an item: " . $item["item_name"] . " (" . $rarity . ")!";
            }
        }

        // next floor
        $save = getSave($saveId, $_SESSION["account_id"]);
        $cleared = $save["is_cleared"] == "t";

        if($floor >= $config["total_floors"]){
            $cleared = true;
            $nextFloor = $floor;
            $lines[] = "The Final Developer is defeated! You cleared the tower!";
        } else {
            $nextFloor = $floor + 1;
            $lines[] = "On to floor " . $nextFloor . "!";
        }

        updateSaveProgress($saveId, $nextFloor, max($nextFloor, $save["highest_floor"]), $cleared);

        return $lines;
    }

    // bosses go after the party member with the lowest hp, other enemies pick someone random
    function pickTarget($party, $isBoss){
        $alive = getTurnOrder($party);

        if(!$isBoss){
            return $alive[array_rand($alive)];
        }

        $target = $alive[0];
        foreach($alive as $i){
            if($party[$i]["current_hp"] < $party[$target]["current_hp"]){
                $target = $i;
            }
        }
        return $target;
    }

    // burn, poison and stun at the start of someone's turn (party member or enemy)
    // returns [the fighter after damage, the log lines, true if stunned]
    function tickStatus($fighter, $name){
        global $config;

        $lines = [];
        $stunned = false;

        foreach($fighter["status"] as $effect => $turns){
            $damagePercent = $config["status_effects"][$effect]["damage"];

            if($damagePercent > 0){
                $damage = max(1, (int) round($fighter["max_hp"] * $damagePercent / 100));
                $fighter["current_hp"] = max(0, $fighter["current_hp"] - $damage);
                $lines[] = $name . " takes " . $damage . " " . $effect . " damage!";
            }

            if($effect == "stun"){
                $stunned = true;
                $lines[] = $name . " is stunned and can't move!";
            }

            // one turn less, gone at 0
            if($turns <= 1){
                unset($fighter["status"][$effect]);
            } else {
                $fighter["status"][$effect] = $turns - 1;
            }
        }

        return [$fighter, $lines, $stunned];
    }

    // after a loss: a bit of xp so you get stronger by trying again (no item, same floor)
    // needs db.php and queries.php. returns the lines for the battle log
    function giveLossXp($battle){
        global $config;

        $xp = 0;
        foreach($battle["enemies"] as $enemy){
            $xp = $xp + $enemy["xp_reward"];
        }
        $xp = (int) round($xp * $config["xp_on_loss"] / 100);

        $lines = [];
        $lines[] = "Your party learned something from that fight: +" . $xp . " XP.";

        foreach(getParty($battle["save_id"]) as $unit){
            $unit = addXp($unit, $xp);

            if($config["heal_after_battle"]){
                $unit["current_hp"] = $unit["max_hp"];
            }

            updateUnit($unit);

            if($unit["levels_gained"] > 0){
                $lines[] = $unit["unit_name"] . " reached Lv " . $unit["level"] . "! (+" . ($unit["levels_gained"] * $config["points_per_level"]) . " upgrade points)";
            }
        }

        return $lines;
    }
