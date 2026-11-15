<?php
    // game settings

    $config = [
        // accounts and saves
        "max_save_slots" => 3,
        "username_min"   => 3,
        "username_max"   => 20,
        "password_min"   => 8,
        "password_max"   => 72,
        "unit_name_max"  => 20,

        // tower
        "total_floors"   => 20,
        "boss_every"     => 5,     // boss on floors multuiple of 5

        // party (one of each, in this order)
        "starting_party" => ["warrior", "mage", "cleric"],

        // class stats at level 1, and how much they go up every level
        "classes" => [
            "warrior" => [
                "label" => "Warrior",
                "max_hp" => 120, "attack" => 12, "defense" => 10, "speed" => 5,
                "hp_up"  => 12,  "attack_up" => 2, "defense_up" => 2, "speed_up" => 1,
            ],
            "mage" => [
                "label" => "Mage",
                "max_hp" => 80,  "attack" => 16, "defense" => 5,  "speed" => 7,
                "hp_up"  => 7,   "attack_up" => 3, "defense_up" => 1, "speed_up" => 1,
            ],
            "cleric" => [
                "label" => "Cleric",
                "max_hp" => 95,  "attack" => 9,  "defense" => 7,  "speed" => 6,
                "hp_up"  => 9,   "attack_up" => 1, "defense_up" => 2, "speed_up" => 1,
            ],
        ],

        // levels
        "max_level"        => 30,
        "xp_base"          => 50,    // xp needed for level 1 to 2
        "xp_exponent"      => 1.3,   // how fast it grows
        "points_per_level" => 3,

        // enemies get stronger every floor (+12% per floor)
        "enemy_hp_per_floor"      => 0.12,
        "enemy_attack_per_floor"  => 0.10,
        "enemy_defense_per_floor" => 0.08,
        "enemy_speed_per_floor"   => 0.03,
        "enemy_xp_per_floor"      => 0.10,
        "enemies_min"             => 1,
        "enemies_max"             => 3,

        // combat
        "defense_constant" => 50,    // damage = attack * 50 / (50 + defense)
        "crit_chance"      => 10,    // percent
        "crit_multiplier"  => 1.5,
        "run_chance"       => 75,    // percent, can't run from bosses
        "log_max_lines"    => 50,
    ];
