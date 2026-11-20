<?php
    function findAccount($username){
        global $conn;

        $result = pg_query_params($conn, 'SELECT * FROM accounts WHERE LOWER(username) = LOWER($1)', [$username]);

        if(!$result){
            return false;
        }
        return pg_fetch_assoc($result);   // false if not found
    }

    function updatePassword($accountId, $password){
        global $conn;

        $hash = password_hash($password, PASSWORD_DEFAULT);
        return pg_query_params($conn, 'UPDATE accounts SET password = $1 WHERE account_id = $2', [$hash, $accountId]);
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

    // ---------- battle records ----------

    // saves how a battle ended (score = xp gained)
    function addBattleResult($saveId, $floor, $result, $rounds, $xpGained){
        global $conn;

        return pg_query_params($conn,
            'INSERT INTO battle_results (save_id, floor, result, rounds, xp_gained) VALUES ($1, $2, $3, $4, $5)',
            [$saveId, $floor, $result, $rounds, $xpGained]
        );
    }

    // the last battles of an account, newest first, with the slot number
    function getBattleResults($accountId, $limit){
        global $conn;

        $result = pg_query_params($conn,
            'SELECT battle_results.*, save_slots.slot_number
             FROM battle_results JOIN save_slots ON battle_results.save_id = save_slots.save_id
             WHERE save_slots.account_id = $1
             ORDER BY battle_results.finished_at DESC, battle_results.result_id DESC
             LIMIT $2',
            [$accountId, $limit]
        );

        $rows = [];
        if(!$result){
            return $rows;
        }
        while($row = pg_fetch_assoc($result)){
            $rows[] = $row;
        }
        return $rows;
    }

    // totals for each save: battles, wins, losses, total score
    function getSaveTotals($accountId){
        global $conn;

        $result = pg_query_params($conn,
            'SELECT save_slots.slot_number, save_slots.highest_floor,
                    COUNT(battle_results.result_id) AS battles,
                    COUNT(*) FILTER (WHERE battle_results.result = \'won\') AS wins,
                    COUNT(*) FILTER (WHERE battle_results.result = \'lost\') AS losses,
                    COALESCE(SUM(battle_results.xp_gained), 0) AS total_score
             FROM save_slots LEFT JOIN battle_results ON battle_results.save_id = save_slots.save_id
             WHERE save_slots.account_id = $1
             GROUP BY save_slots.save_id
             ORDER BY save_slots.slot_number',
            [$accountId]
        );

        $rows = [];
        if(!$result){
            return $rows;
        }
        while($row = pg_fetch_assoc($result)){
            $rows[] = $row;
        }
        return $rows;
    }

    // ---------- admin ----------

    // numbers for the admin dashboard
    function getAdminCounts(){
        global $conn;

        $result = pg_query($conn,
            'SELECT (SELECT COUNT(*) FROM accounts) AS players,
                    (SELECT COUNT(*) FROM accounts WHERE role = \'admin\') AS admins,
                    (SELECT COUNT(*) FROM save_slots) AS saves,
                    (SELECT COUNT(*) FROM save_slots WHERE is_cleared) AS cleared,
                    (SELECT COUNT(*) FROM battle_results) AS battles,
                    (SELECT COUNT(*) FROM battle_results WHERE result = \'won\') AS wins,
                    (SELECT COUNT(*) FROM battle_results WHERE result = \'lost\') AS losses,
                    (SELECT COUNT(*) FROM enemy_templates) AS enemies,
                    (SELECT COUNT(*) FROM item_templates) AS items'
        );

        if(!$result){
            return false;
        }
        return pg_fetch_assoc($result);
    }

    // ---------- admin: users ----------

    // every account with how many saves and battles it has
    function getAllAccounts(){
        global $conn;

        $result = pg_query($conn,
            'SELECT accounts.account_id, accounts.username, accounts.role, accounts.created_at,
                    COUNT(DISTINCT save_slots.save_id) AS saves,
                    COUNT(battle_results.result_id) AS battles
             FROM accounts
             LEFT JOIN save_slots ON save_slots.account_id = accounts.account_id
             LEFT JOIN battle_results ON battle_results.save_id = save_slots.save_id
             GROUP BY accounts.account_id
             ORDER BY accounts.account_id'
        );

        $accounts = [];
        if(!$result){
            return $accounts;
        }
        while($row = pg_fetch_assoc($result)){
            $accounts[] = $row;
        }
        return $accounts;
    }

    function setRole($accountId, $role){
        global $conn;

        return pg_query_params($conn, 'UPDATE accounts SET role = $1 WHERE account_id = $2', [$role, $accountId]);
    }

    // ---------- admin: enemies ----------

    function getEnemy($enemyId){
        global $conn;

        $result = pg_query_params($conn, 'SELECT * FROM enemy_templates WHERE enemy_id = $1', [$enemyId]);

        if(!$result){
            return false;
        }
        return pg_fetch_assoc($result);
    }

    // true if another enemy already has this name
    function enemyNameTaken($name, $enemyId){
        global $conn;

        $result = pg_query_params($conn, 'SELECT enemy_id FROM enemy_templates WHERE LOWER(enemy_name) = LOWER($1) AND enemy_id <> $2', [$name, $enemyId]);

        return $result && pg_num_rows($result) > 0;
    }

    // true if another boss already guards this floor
    function bossFloorTaken($floor, $enemyId){
        global $conn;

        $result = pg_query_params($conn, 'SELECT enemy_id FROM enemy_templates WHERE is_boss AND boss_floor = $1 AND enemy_id <> $2', [$floor, $enemyId]);

        return $result && pg_num_rows($result) > 0;
    }

    // how many regular enemies can show up on floor 1 (the game needs at least one)
    function countFloorOneEnemies(){
        global $conn;

        $result = pg_query($conn, 'SELECT COUNT(*) FROM enemy_templates WHERE NOT is_boss AND min_floor = 1');

        if(!$result){
            return 0;
        }
        return (int) pg_fetch_result($result, 0, 0);
    }

    function addEnemy($e){
        global $conn;

        return pg_query_params($conn,
            'INSERT INTO enemy_templates (enemy_name, is_boss, is_final_boss, boss_floor, min_floor, base_hp, base_attack, base_defense, base_speed, xp_reward, image)
             VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11)',
            [$e["enemy_name"], $e["is_boss"], $e["is_final_boss"], $e["boss_floor"], $e["min_floor"], $e["base_hp"], $e["base_attack"], $e["base_defense"], $e["base_speed"], $e["xp_reward"], $e["image"]]
        );
    }

    function updateEnemy($enemyId, $e){
        global $conn;

        return pg_query_params($conn,
            'UPDATE enemy_templates SET enemy_name = $1, is_boss = $2, is_final_boss = $3, boss_floor = $4, min_floor = $5,
                    base_hp = $6, base_attack = $7, base_defense = $8, base_speed = $9, xp_reward = $10, image = $11
             WHERE enemy_id = $12',
            [$e["enemy_name"], $e["is_boss"], $e["is_final_boss"], $e["boss_floor"], $e["min_floor"], $e["base_hp"], $e["base_attack"], $e["base_defense"], $e["base_speed"], $e["xp_reward"], $e["image"], $enemyId]
        );
    }

    function deleteEnemy($enemyId){
        global $conn;

        return pg_query_params($conn, 'DELETE FROM enemy_templates WHERE enemy_id = $1', [$enemyId]);
    }

    // ---------- admin: items ----------

    // every item with how many copies players have
    function getAllItems(){
        global $conn;

        $result = pg_query($conn,
            'SELECT item_templates.*, COUNT(inventory.instance_id) AS owned
             FROM item_templates
             LEFT JOIN inventory ON inventory.item_id = item_templates.item_id
             GROUP BY item_templates.item_id
             ORDER BY item_templates.min_floor, item_templates.item_name'
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

    function getItem($itemId){
        global $conn;

        $result = pg_query_params($conn, 'SELECT * FROM item_templates WHERE item_id = $1', [$itemId]);

        if(!$result){
            return false;
        }
        return pg_fetch_assoc($result);
    }

    function itemNameTaken($name, $itemId){
        global $conn;

        $result = pg_query_params($conn, 'SELECT item_id FROM item_templates WHERE LOWER(item_name) = LOWER($1) AND item_id <> $2', [$name, $itemId]);

        return $result && pg_num_rows($result) > 0;
    }

    function countOwned($itemId){
        global $conn;

        $result = pg_query_params($conn, 'SELECT COUNT(*) FROM inventory WHERE item_id = $1', [$itemId]);

        if(!$result){
            return 0;
        }
        return (int) pg_fetch_result($result, 0, 0);
    }

    function addItemTemplate($i){
        global $conn;

        return pg_query_params($conn,
            'INSERT INTO item_templates (item_name, slot_type, rarity, hp_mult, atk_mult, def_mult, spd_mult, min_floor, description)
             VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9)',
            [$i["item_name"], $i["slot_type"], $i["rarity"], $i["hp_mult"], $i["atk_mult"], $i["def_mult"], $i["spd_mult"], $i["min_floor"], $i["description"]]
        );
    }

    function updateItemTemplate($itemId, $i){
        global $conn;

        return pg_query_params($conn,
            'UPDATE item_templates SET item_name = $1, slot_type = $2, rarity = $3, hp_mult = $4, atk_mult = $5,
                    def_mult = $6, spd_mult = $7, min_floor = $8, description = $9
             WHERE item_id = $10',
            [$i["item_name"], $i["slot_type"], $i["rarity"], $i["hp_mult"], $i["atk_mult"], $i["def_mult"], $i["spd_mult"], $i["min_floor"], $i["description"], $itemId]
        );
    }

    function deleteItemTemplate($itemId){
        global $conn;

        return pg_query_params($conn, 'DELETE FROM item_templates WHERE item_id = $1', [$itemId]);
    }
