<?php
    // dates and times on the records page use Philippine time
    date_default_timezone_set("Asia/Manila");

    // game settings

    $config = [
        // accounts and saves
        "max_save_slots" => 3,
        "username_min"   => 3,
        "username_max"   => 20,
        "password_min"   => 8,
        "password_max"   => 72,
        "unit_name_max"  => 20,

        // picture uploads (admin enemy pictures)
        "upload_max_kb"  => 2048,    // 2 MB
        "upload_types"   => ["image/jpeg" => "jpg", "image/png" => "png", "image/gif" => "gif", "image/webp" => "webp"],

        // tower
        "total_floors"   => 20,
        "boss_every"     => 5,     // boss on floors multuiple of 5

        // party (one of each, in this order)
        "starting_party" => ["warrior", "mage", "cleric"],

        // class stats at level 1, and how much they go up every level
        "classes" => [
            "warrior" => [
                "label" => "Warrior",
                "image" => "assets/images/warrior.png",
                "max_hp" => 120, "attack" => 12, "defense" => 10, "speed" => 5,
                "hp_up"  => 12,  "attack_up" => 2, "defense_up" => 2, "speed_up" => 1,
            ],
            "mage" => [
                "label" => "Mage",
                "image" => "assets/images/mage.png",
                "max_hp" => 80,  "attack" => 16, "defense" => 5,  "speed" => 7,
                "hp_up"  => 7,   "attack_up" => 3, "defense_up" => 1, "speed_up" => 1,
            ],
            "cleric" => [
                "label" => "Cleric",
                "image" => "assets/images/cleric.png",
                "max_hp" => 95,  "attack" => 9,  "defense" => 7,  "speed" => 6,
                "hp_up"  => 9,   "attack_up" => 1, "defense_up" => 2, "speed_up" => 1,
            ],
        ],

        // levels
        "max_level"        => 30,
        "xp_base"          => 50,    // xp needed for level 1 to 2
        "xp_exponent"      => 1.3,   // how fast it grows
        "points_per_level" => 3,

        // what 1 upgrade point adds (spent on the party page)
        "point_hp"      => 5,
        "point_attack"  => 1,
        "point_defense" => 1,
        "point_speed"   => 1,

        // PLACEHOLDER: party heals to full after every battle
        "heal_after_battle" => true,

        // losing still gives this % of the battle's xp, so retrying a boss makes you a bit stronger
        "xp_on_loss" => 25,

        // skills: level = level needed, power = damage x attack (0 = no damage),
        // cooldown = ROUNDS before it can be used again
        // target: "enemy", "all_enemies", "ally" or "all_allies"
        "skills" => [
            "warrior" => [
                ["name" => "Shield Bash", "level" => 2, "power" => 0.8, "cooldown" => 3, "target" => "enemy",       "effect" => "stun"],
                ["name" => "Cleave",      "level" => 5, "power" => 0.9, "cooldown" => 4, "target" => "all_enemies", "effect" => ""],
            ],
            "mage" => [
                ["name" => "Fireball",    "level" => 2, "power" => 1.4, "cooldown" => 3, "target" => "enemy",       "effect" => "burn"],
                ["name" => "Toxic Cloud", "level" => 5, "power" => 0.6, "cooldown" => 5, "target" => "all_enemies", "effect" => "poison"],
            ],
            "cleric" => [
                ["name" => "Heal",        "level" => 2, "power" => 0,   "cooldown" => 3, "target" => "ally",        "effect" => "heal"],
                ["name" => "Sanctuary",   "level" => 5, "power" => 0,   "cooldown" => 5, "target" => "all_allies",  "effect" => "heal"],
            ],
        ],
        "heal_percent" => 20,    // heal skills give back 20% of max hp

        // status effects on enemies: damage = % of max hp every turn
        "status_effects" => [
            "burn"   => ["damage" => 8, "turns" => 2, "text" => "is burning!"],
            "poison" => ["damage" => 5, "turns" => 3, "text" => "is poisoned!"],
            "stun"   => ["damage" => 0, "turns" => 1, "text" => "is stunned!"],    // skips their turn
        ],

        // enemies get stronger every floor (+15% hp per floor)
        // PLACEHOLDER: up for testing
        "enemy_hp_per_floor"      => 0.15,
        "enemy_attack_per_floor"  => 0.18,
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

        // PLACEHOLDER: one skill per enemy (by enemy_name in the database)
        // chance = % chance to use it instead of a normal attack, power = damage x attack
        // target: "one" or "all" (whole party). effect: "burn", "poison", "stun" or ""
        // bosses always go after the party member with the lowest hp, others pick someone random
        "enemy_skills" => [
            "Bug"            => ["name" => "Poison Bite",  "chance" => 20, "power" => 0.8, "target" => "one", "effect" => "poison"],
            "Glitch Slime"   => ["name" => "Sticky Goo",   "chance" => 15, "power" => 0.6, "target" => "one", "effect" => "stun"],
            "Null Pointer"   => ["name" => "Crash",        "chance" => 20, "power" => 1.5, "target" => "one", "effect" => ""],
            "Memory Leak"    => ["name" => "Drain",        "chance" => 25, "power" => 0.5, "target" => "all", "effect" => ""],
            "Infinite Loop"  => ["name" => "Overheat",     "chance" => 20, "power" => 1.0, "target" => "one", "effect" => "burn"],
            "Merge Conflict" => ["name" => "Rebase",       "chance" => 25, "power" => 0.5, "target" => "all", "effect" => ""],

            "Stefan, the Architect"      => ["name" => "Blueprint Crush", "chance" => 25, "power" => 0.6, "target" => "all", "effect" => ""],
            "Paulo, the Repo Keeper"     => ["name" => "Force Push",      "chance" => 20, "power" => 1.2, "target" => "one", "effect" => "stun"],
            "Jeno, the Database Warden"  => ["name" => "Drop Table",      "chance" => 25, "power" => 0.5, "target" => "all", "effect" => "poison"],
            "Jancy, the Final Developer" => ["name" => "Hotfix Storm",    "chance" => 20, "power" => 0.5, "target" => "all", "effect" => "burn"],
        ],

        // drops after a win (percent)
        "drop_chance"      => 60,
        "boss_drop_chance" => 100,
        // out of 100: common 70, rare 22, epic 7, legendary 1
        "drop_rarity"      => ["common" => 70, "rare" => 22, "epic" => 7, "legendary" => 1],
        "boss_drop_rarity" => ["common" => 0, "rare" => 60, "epic" => 30, "legendary" => 10],
    ];
