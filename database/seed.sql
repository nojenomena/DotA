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