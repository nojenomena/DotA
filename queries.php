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
