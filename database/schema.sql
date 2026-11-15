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


-- indexes for faster lookups
CREATE INDEX IF NOT EXISTS idx_save_slots_account ON save_slots(account_id);
CREATE INDEX IF NOT EXISTS idx_party_units_save   ON party_units(save_id);
CREATE INDEX IF NOT EXISTS idx_inventory_save     ON inventory(save_id);
CREATE INDEX IF NOT EXISTS idx_inventory_equipped ON inventory(equipped_by);


-- row-level security policies to ensure players can only access their own data
ALTER TABLE accounts        ENABLE ROW LEVEL SECURITY;
ALTER TABLE save_slots      ENABLE ROW LEVEL SECURITY;
ALTER TABLE party_units     ENABLE ROW LEVEL SECURITY;
ALTER TABLE item_templates  ENABLE ROW LEVEL SECURITY;
ALTER TABLE inventory       ENABLE ROW LEVEL SECURITY;
ALTER TABLE enemy_templates ENABLE ROW LEVEL SECURITY;