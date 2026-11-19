<?php
    function findAccount($username){
        global $conn;

        $result = pg_query_params($conn, 'SELECT * FROM accounts WHERE LOWER(username) = LOWER($1)', [$username]);

        if(!$result){
            return false;
        }
        return pg_fetch_assoc($result);   // false if not found
    }

    function createAccount($username, $password){
        global $conn;

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $result = pg_query_params($conn, 'INSERT INTO accounts (username, password) VALUES ($1, $2) RETURNING account_id', [$username, $hash]);

        if(!$result){
            return false;
        }
        return pg_fetch_result($result, 0, 0);
    }

    function getSaves($accountId){
        global $conn;

        $result = pg_query_params($conn, 'SELECT * FROM save_slots WHERE account_id = $1 ORDER BY slot_number', [$accountId]);

        $saves = [];
        if(!$result){
            return $saves;
        }
        while($row = pg_fetch_assoc($result)){
            $row["party"] = getParty($row["save_id"]);
            $saves[$row["slot_number"]] = $row;
        }
        return $saves;
    }

    function getSave($saveId, $accountId){
        global $conn;

        $result = pg_query_params($conn, 'SELECT * FROM save_slots WHERE save_id = $1 AND account_id = $2', [$saveId, $accountId]);

        if(!$result){
            return false;
        }
        return pg_fetch_assoc($result);
    }

    function getParty($saveId){
        global $conn;

        $result = pg_query_params($conn, 'SELECT * FROM party_units WHERE save_id = $1 ORDER BY party_position', [$saveId]);

        $party = [];
        if(!$result){
            return $party;
        }
        while($row = pg_fetch_assoc($result)){
            foreach(["unit_id", "level", "xp", "upgrade_points", "max_hp", "current_hp", "attack", "defense", "speed"] as $key){
                $row[$key] = (int) $row[$key];
            }
            $party[] = $row;
        }
        return $party;
    }

    function createSave($accountId, $slot, $units){
        global $conn;

        $result = pg_query_params($conn, 'INSERT INTO save_slots (account_id, slot_number) VALUES ($1, $2) RETURNING save_id', [$accountId, $slot]);
        if(!$result){
            return false;
        }
        $saveId = pg_fetch_result($result, 0, 0);

        foreach($units as $unit){
            $result = pg_query_params($conn,
                'INSERT INTO party_units (save_id, unit_name, unit_class, party_position, max_hp, current_hp, attack, defense, speed)
                 VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9)',
                [$saveId, $unit["unit_name"], $unit["unit_class"], $unit["party_position"], $unit["max_hp"], $unit["current_hp"], $unit["attack"], $unit["defense"], $unit["speed"]]
            );

            if(!$result){
                pg_query_params($conn, 'DELETE FROM save_slots WHERE save_id = $1', [$saveId]);
                return false;
            }
        }

        return $saveId;
    }

    function getEnemies(){
        global $conn;

        $result = pg_query($conn, 'SELECT * FROM enemy_templates');

        $enemies = [];
        if(!$result){
            return $enemies;
        }
        while($row = pg_fetch_assoc($result)){
            $enemies[] = $row;
        }
        return $enemies;
    }

    // saves a party member's level, xp, points and stats
    function updateUnit($unit){
        global $conn;

        return pg_query_params($conn,
            'UPDATE party_units SET level = $1, xp = $2, upgrade_points = $3, max_hp = $4, current_hp = $5, attack = $6, defense = $7, speed = $8
             WHERE unit_id = $9',
            [$unit["level"], $unit["xp"], $unit["upgrade_points"], $unit["max_hp"], $unit["current_hp"], $unit["attack"], $unit["defense"], $unit["speed"], $unit["unit_id"]]
        );
    }

    function updateSaveProgress($saveId, $floor, $highest, $cleared){
        global $conn;

        return pg_query_params($conn,
            'UPDATE save_slots SET current_floor = $1, highest_floor = $2, is_cleared = $3, updated_at = now() WHERE save_id = $4',
            [$floor, $highest, $cleared ? "t" : "f", $saveId]
        );
    }

    // items of a save with their stats, and who has them equipped
    function getInventory($saveId){
        global $conn;

        $result = pg_query_params($conn,
            'SELECT inventory.instance_id, inventory.equipped_by, inventory.obtained_floor, item_templates.*
             FROM inventory JOIN item_templates ON inventory.item_id = item_templates.item_id
             WHERE inventory.save_id = $1 ORDER BY inventory.instance_id',
            [$saveId]
        );

        $items = [];
        if(!$result){
            return $items;
        }
        while($row = pg_fetch_assoc($result)){
            $items[] = $row;
        }
        return $items;
    }

    // a random item of that rarity that can drop on this floor (false if none)
    function getRandomItem($rarity, $floor){
        global $conn;

        $result = pg_query_params($conn, 'SELECT * FROM item_templates WHERE rarity = $1 AND min_floor <= $2 ORDER BY random() LIMIT 1', [$rarity, $floor]);

        if(!$result){
            return false;
        }
        return pg_fetch_assoc($result);
    }

    function addItem($saveId, $itemId, $floor){
        global $conn;

        return pg_query_params($conn, 'INSERT INTO inventory (save_id, item_id, obtained_floor) VALUES ($1, $2, $3)', [$saveId, $itemId, $floor]);
    }

    // save_id is checked too so you can only touch items from your own save
    function equipItem($instanceId, $unitId, $saveId){
        global $conn;

        return pg_query_params($conn, 'UPDATE inventory SET equipped_by = $1 WHERE instance_id = $2 AND save_id = $3', [$unitId, $instanceId, $saveId]);
    }

    function unequipItem($instanceId, $saveId){
        global $conn;

        return pg_query_params($conn, 'UPDATE inventory SET equipped_by = NULL WHERE instance_id = $1 AND save_id = $2', [$instanceId, $saveId]);
    }

    // ---------- delete ----------

    // deletes a save. its party and items are deleted too (ON DELETE CASCADE in schema.sql)
    function deleteSave($saveId, $accountId){
        global $conn;

        return pg_query_params($conn, 'DELETE FROM save_slots WHERE save_id = $1 AND account_id = $2', [$saveId, $accountId]);
    }

    // throws away an item
    function deleteItem($instanceId, $saveId){
        global $conn;

        return pg_query_params($conn, 'DELETE FROM inventory WHERE instance_id = $1 AND save_id = $2', [$instanceId, $saveId]);
    }

    // removes a party member. items they had on go back to the bag (ON DELETE SET NULL)
    function deleteUnit($unitId, $saveId){
        global $conn;

        return pg_query_params($conn, 'DELETE FROM party_units WHERE unit_id = $1 AND save_id = $2', [$unitId, $saveId]);
    }

    // deletes the account and everything in it (saves, party, items)
    function deleteAccount($accountId){
        global $conn;

        return pg_query_params($conn, 'DELETE FROM accounts WHERE account_id = $1', [$accountId]);
    }

    // adds one new party member to a save
    function addUnit($saveId, $unit){
        global $conn;

        return pg_query_params($conn,
            'INSERT INTO party_units (save_id, unit_name, unit_class, party_position, max_hp, current_hp, attack, defense, speed)
             VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9)',
            [$saveId, $unit["unit_name"], $unit["unit_class"], $unit["party_position"], $unit["max_hp"], $unit["current_hp"], $unit["attack"], $unit["defense"], $unit["speed"]]
        );
    }
