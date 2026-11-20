# Defense-of-the-Alphards-DotA-
A web-based game for CBAD by Mga Mango

Turn-based RPG where your party climbs a 20-floor tower and fights the Developers.
PHP + PostgreSQL (Supabase) + plain HTML, CSS and JavaScript.

## How to play
- Make an account, then start a New Game in one of your 3 save slots.
- Your party is always a Warrior, a Mage and a Cleric. You only pick their names.
- Every floor is one battle. Win to go up, lose and you try the same floor again.
- There is a boss every 5 floors: Stefan (5), Paulo (10), Jeno (15) and Jancy, the Final Developer (20).
- Battles are turn-based, the fastest unit goes first.
  - **Attack** hits one enemy.
  - **Skill** uses a class skill (unlocks at Lv 2 and Lv 5, some have a cooldown).
  - **Run** has a chance to escape (you stay on the same floor).
- Winning gives XP and sometimes an item. Losing still gives a bit of XP.
- In the **Party** page you spend upgrade points, equip items, dismiss and recruit members.
- The **Records** page shows your saves and your last battles. Score = XP gained.

## Controls
| Key | What it does |
|-----|--------------|
| W A S D / Arrow keys | move in the menu, pick a target |
| Enter / Space | choose, close the floor intro, Next Floor / Try Again |
| Esc / Backspace | go back |

The mouse works too: click a button, click an enemy to attack it, click a party member to heal them.

## Setup (Windows + XAMPP + Supabase)
1. **Database**
   - Open your Supabase project, go to **SQL Editor**, paste all of `dota.sql` and press **Run**.
   - It is safe to run again, it skips what already exists.
2. **Database password**
   - Copy `includes/db_config.template.php` and name the copy `includes/db_config.php`.
   - In Supabase press **Connect**, pick **Session pooler**, and copy the host, port, database name and user into it.
   - Put your database password in `$dbPassword`.
   - `db_config.php` is in `.gitignore`, never push it.
3. **XAMPP**
   - In `php.ini` (XAMPP Control Panel > Apache > Config) make sure this line does NOT start with `;`
     ```
     extension=pgsql
     ```
   - Restart Apache after changing it.
4. **Open the game**
   - Put the folder in `C:\xampp\htdocs\`, or link it so you don't have to copy it every time:
     ```
     mklink /J "C:\xampp\htdocs\Dota-MgaMango" "C:\Users\User\Desktop\Proggers Files\Dota-MgaMango"
     ```
   - Open `http://localhost/Dota-MgaMango/` in the browser.

## Problems
| You see | Fix |
|---------|-----|
| `Missing db_config.php` | do step 2 of the setup |
| `Can't connect to the database right now` | check `db_config.php`, and check that the Supabase project is not paused |
| `Call to undefined function pg_connect()` | do step 3 of the setup |
| "Connection problem" when a battle ends | run `dota.sql` again (a table is missing) |
| `This save has no party, or there are no enemies` | run `dota.sql` again (the enemy list is empty) |
| Old look or old buttons after an update | press Ctrl + F5 |

## Folders

```
/                    user pages (index.php, login.php, saves.php, party.php, battle.php, records.php ...)
battle_action.php    does one battle turn (battle.js sends the action here)
includes/            reusable includes
    db.php               database connection
    db_config.php        your database password (NOT in git, make it from the template)
    config.php           all game numbers (stats, skills, enemies, drops)
    functions.php        game formulas (damage, xp, rewards ...)
    queries.php          all the SQL
    header.php           top of every page (<head>, css)
    nav.php              menu on top of every page
    footer.php           bottom of every page
assets/
    css/styles.css
    js/battle.js         battle controls, popups, sounds and hit effects
    images/              game pictures
    sounds/              select, hit, crit, heal, victory, defeat (.mp3)
uploads/             media added by users
    units/  enemies/  bosses/
admin/               admin pages only
dota.sql             database export (tables + starting data)
```

## Rules
- User pages go in the root folder. Admin pages go in `admin/` only.
- Pages inside a folder (like `admin/`) set `$root = "../";` before including `../includes/header.php`.
- Every page starts with `includes/header.php` and `includes/nav.php`, and ends with `includes/footer.php`.
- New numbers go in `includes/config.php`, new SQL goes in `includes/queries.php`.
- SQL always uses `pg_query_params` with `$1, $2 ...`, never put a variable inside the SQL text.
- Anything a player typed is printed with `htmlspecialchars`.
- A missing sound file is fine, the game just stays quiet.
