-- =============================================================
-- Defense of the Alphards - database export
-- Run this whole file in Supabase: SQL Editor -> New query -> paste -> Run
-- Part 1: tables. Part 2: starting data (items, enemies, bosses).
-- Safe to run again: tables are only made if missing, data is skipped if it's there.
-- =============================================================


-- ======================= PART 1: TABLES =======================

-- accounts: one row per registered player
CREATE TABLE IF NOT EXISTS accounts (
    account_id   SERIAL PRIMARY KEY,
    username     VARCHAR(30)  NOT NULL UNIQUE,
    password     VARCHAR(255) NOT NULL,              -- password_hash() output, never plain text
    created_at   TIMESTAMPTZ  NOT NULL DEFAULT now()
);

-- save_slots: each player can have multiple save slots (up to 3)
CREATE TABLE IF NOT EXISTS save_slots (
    save_id        SERIAL PRIMARY KEY,
    account_id     INT         NOT NULL REFERENCES accounts(account_id) ON DELETE CASCADE,
    slot_number    SMALLINT    NOT NULL CHECK (slot_number >= 1),
    current_floor  INT         NOT NULL DEFAULT 1 CHECK (current_floor >= 1),
    highest_floor  INT         NOT NULL DEFAULT 1 CHECK (highest_floor >= 1),
    is_cleared     BOOLEAN     NOT NULL DEFAULT FALSE,   -- TRUE once the Final Developer is defeated
    created_at     TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at     TIMESTAMPTZ NOT NULL DEFAULT now(),
    UNIQUE (account_id, slot_number)
);


-- party_units: the player's party members in a save slot
CREATE TABLE IF NOT EXISTS party_units (
    unit_id         SERIAL PRIMARY KEY,
    save_id         INT         NOT NULL REFERENCES save_slots(save_id) ON DELETE CASCADE,
    unit_name       VARCHAR(30) NOT NULL,
    unit_class      VARCHAR(20) NOT NULL,                 -- matches a class key in config
    party_position  SMALLINT    NOT NULL CHECK (party_position >= 1),
    level           INT         NOT NULL DEFAULT 1 CHECK (level >= 1),
    xp              INT         NOT NULL DEFAULT 0 CHECK (xp >= 0),
    upgrade_points  INT         NOT NULL DEFAULT 0 CHECK (upgrade_points >= 0),
    max_hp          INT         NOT NULL CHECK (max_hp > 0),
    current_hp      INT         NOT NULL CHECK (current_hp >= 0),
    attack          INT         NOT NULL CHECK (attack >= 0),
    defense         INT         NOT NULL CHECK (defense >= 0),
    speed           INT         NOT NULL CHECK (speed >= 0),
    UNIQUE (save_id, party_position)
);


-- item_templates: the base stats for all items in the game
CREATE TABLE IF NOT EXISTS item_templates (
    item_id      SERIAL PRIMARY KEY,
    item_name    VARCHAR(50)  NOT NULL UNIQUE,
    slot_type    VARCHAR(20)  NOT NULL CHECK (slot_type IN ('weapon', 'armor', 'accessory')),
    rarity       VARCHAR(20)  NOT NULL CHECK (rarity IN ('common', 'rare', 'epic', 'legendary')),
    hp_mult      NUMERIC(4,2) NOT NULL DEFAULT 1.00 CHECK (hp_mult  > 0),
    atk_mult     NUMERIC(4,2) NOT NULL DEFAULT 1.00 CHECK (atk_mult > 0),
    def_mult     NUMERIC(4,2) NOT NULL DEFAULT 1.00 CHECK (def_mult > 0),
    spd_mult     NUMERIC(4,2) NOT NULL DEFAULT 1.00 CHECK (spd_mult > 0),
    min_floor    INT          NOT NULL DEFAULT 1 CHECK (min_floor >= 1),   -- earliest floor it can drop on
    description  TEXT
);


-- inventory: the items a player has in a save slot; equipped_by is NULL if not equipped
CREATE TABLE IF NOT EXISTS inventory (
    instance_id     SERIAL PRIMARY KEY,
    save_id         INT         NOT NULL REFERENCES save_slots(save_id) ON DELETE CASCADE,
    item_id         INT         NOT NULL REFERENCES item_templates(item_id) ON DELETE RESTRICT,
    equipped_by     INT         REFERENCES party_units(unit_id) ON DELETE SET NULL,
    obtained_floor  INT         NOT NULL DEFAULT 1,
    obtained_at     TIMESTAMPTZ NOT NULL DEFAULT now()
);


-- enemy_templates: the base stats for all enemies in the game, including bosses
CREATE TABLE IF NOT EXISTS enemy_templates (
    enemy_id      SERIAL PRIMARY KEY,
    enemy_name    VARCHAR(50) NOT NULL UNIQUE,
    is_boss       BOOLEAN     NOT NULL DEFAULT FALSE,
    is_final_boss BOOLEAN     NOT NULL DEFAULT FALSE,   -- the Final Developer
    boss_floor    INT         CHECK (boss_floor >= 1),  -- only for bosses: the floor they guard
    min_floor     INT         NOT NULL DEFAULT 1 CHECK (min_floor >= 1),
    base_hp       INT         NOT NULL CHECK (base_hp > 0),
    base_attack   INT         NOT NULL CHECK (base_attack >= 0),
    base_defense  INT         NOT NULL CHECK (base_defense >= 0),
    base_speed    INT         NOT NULL CHECK (base_speed >= 0),
    xp_reward     INT         NOT NULL DEFAULT 0 CHECK (xp_reward >= 0)
);


-- battle_results: one row for every finished battle, score = xp_gained (week 6, records.php)
CREATE TABLE IF NOT EXISTS battle_results (
    result_id    SERIAL PRIMARY KEY,
    save_id      INT         NOT NULL REFERENCES save_slots(save_id) ON DELETE CASCADE,
    floor        INT         NOT NULL,
    result       VARCHAR(10) NOT NULL CHECK (result IN ('won', 'lost', 'fled')),
    rounds       INT         NOT NULL,
    xp_gained    INT         NOT NULL DEFAULT 0,
    finished_at  TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- indexes for faster lookups
CREATE INDEX IF NOT EXISTS idx_save_slots_account ON save_slots(account_id);
CREATE INDEX IF NOT EXISTS idx_party_units_save   ON party_units(save_id);
CREATE INDEX IF NOT EXISTS idx_inventory_save     ON inventory(save_id);
CREATE INDEX IF NOT EXISTS idx_inventory_equipped ON inventory(equipped_by);
CREATE INDEX IF NOT EXISTS idx_battle_results_save ON battle_results(save_id);


-- row-level security policies to ensure players can only access their own data
ALTER TABLE accounts        ENABLE ROW LEVEL SECURITY;
ALTER TABLE save_slots      ENABLE ROW LEVEL SECURITY;
ALTER TABLE party_units     ENABLE ROW LEVEL SECURITY;
ALTER TABLE item_templates  ENABLE ROW LEVEL SECURITY;
ALTER TABLE inventory       ENABLE ROW LEVEL SECURITY;
ALTER TABLE enemy_templates ENABLE ROW LEVEL SECURITY;
ALTER TABLE battle_results  ENABLE ROW LEVEL SECURITY;

-- ======================= PART 2: STARTING DATA =======================

-- Equipment

INSERT INTO item_templates (item_name, slot_type, rarity, hp_mult, atk_mult, def_mult, spd_mult, min_floor, description) VALUES
    ('Wooden Sword',        'weapon',    'common',    1.00, 1.10, 1.00, 1.00, 1,  'Better than bare hands.'),
    ('Leather Vest',        'armor',     'common',    1.05, 1.00, 1.10, 1.00, 1,  'Smells like old sweat.'),
    ('Copper Ring',         'accessory', 'common',    1.00, 1.00, 1.00, 1.08, 1,  'Slightly lighter on your feet.'),
    ('Steel Blade',         'weapon',    'rare',      1.00, 1.20, 1.00, 1.00, 4,  'A real weapon at last.'),
    ('Chainmail',           'armor',     'rare',      1.10, 1.00, 1.20, 0.95, 4,  'Heavy, but it holds.'),
    ('Swift Boots Charm',   'accessory', 'rare',      1.00, 1.00, 1.00, 1.15, 4,  'Lets you act first more often.'),
    ('Runed Staff',         'weapon',    'epic',      1.00, 1.35, 1.00, 1.05, 9,  'Hums with stored power.'),
    ('Knight Plate',        'armor',     'epic',      1.20, 1.00, 1.30, 0.95, 9,  'Built for the upper floors.'),
    ('Amulet of Vigor',     'accessory', 'epic',      1.25, 1.05, 1.00, 1.00, 9,  'Your heart beats stronger.'),
    ('Debugger''s Edge',    'weapon',    'legendary', 1.00, 1.50, 1.00, 1.10, 12, 'Finds every weak point.'),
    ('Firewall Aegis',      'armor',     'legendary', 1.30, 1.00, 1.45, 1.00, 12, 'Nothing gets through.'),
    ('Root Access Crown',   'accessory', 'legendary', 1.15, 1.15, 1.15, 1.15, 12, 'Full permissions.')
ON CONFLICT (item_name) DO NOTHING;


-- Regular enemies (min_floor = first floor they can appear on)
INSERT INTO enemy_templates (enemy_name, is_boss, is_final_boss, boss_floor, min_floor, base_hp, base_attack, base_defense, base_speed, xp_reward) VALUES
    ('Bug',            FALSE, FALSE, NULL, 1,  40, 8,  3, 4, 15),
    ('Glitch Slime',   FALSE, FALSE, NULL, 1,  55, 9,  5, 3, 18),
    ('Null Pointer',   FALSE, FALSE, NULL, 3,  45, 12, 2, 8, 22),
    ('Memory Leak',    FALSE, FALSE, NULL, 6,  80, 10, 8, 2, 28),
    ('Infinite Loop',  FALSE, FALSE, NULL, 11, 70, 14, 6, 6, 32),
    ('Merge Conflict', FALSE, FALSE, NULL, 16, 90, 16, 9, 5, 40)
ON CONFLICT (enemy_name) DO NOTHING;


-- Developer bosses (one every 5 floors, Final Developer on 20)
INSERT INTO enemy_templates (enemy_name, is_boss, is_final_boss, boss_floor, min_floor, base_hp, base_attack, base_defense, base_speed, xp_reward) VALUES
    ('Stefan, the Architect',       TRUE, FALSE, 5,  5,  300, 16, 10, 6, 150),
    ('Paulo, the Repo Keeper',      TRUE, FALSE, 10, 10, 380, 18, 12, 7, 260),
    ('Jeno, the Database Warden',   TRUE, FALSE, 15, 15, 440, 20, 14, 8, 380),
    ('Jancy, the Final Developer',  TRUE, TRUE,  20, 20, 520, 22, 16, 9, 600)
ON CONFLICT (enemy_name) DO NOTHING;