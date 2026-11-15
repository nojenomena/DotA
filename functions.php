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
        // PLACEHOLDER: knocked out units come back with 1 hp until the time nga sturyahon ta if ma heal man gd sila
        foreach($party as $i => $unit){
            if($unit["current_hp"] <= 0){
                $party[$i]["current_hp"] = 1;
            }
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
            "log"     => [implode(", ", $names) . " appeared!"],
        ];
    }
