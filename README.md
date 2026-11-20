# Defense-of-the-Alphards-DotA-
A web-based game for CBAD by Mga Mango

Turn-based RPG where your party climbs a 20-floor tower and fights the Developers.
PHP + PostgreSQL (Supabase) + plain HTML, CSS and JavaScript.

## Live site
- **URL:** https://YOUR-PROJECT.vercel.app  <!-- PLACEHOLDER: put the real Vercel address here -->
- **Platform:** Vercel (PHP with the `vercel-php` runtime, Mumbai region `bom1`) + Supabase (PostgreSQL database and Storage for enemy pictures, region `ap-south-1`)

### Test accounts (made only for grading)
| Role | Username | Password |
|------|----------|----------|
| Admin | PLACEHOLDER | PLACEHOLDER |
| Regular user | PLACEHOLDER | PLACEHOLDER |

### Members
| Name | Role in the project |
|------|---------------------|
| PLACEHOLDER | |
| PLACEHOLDER | |
| PLACEHOLDER | |
| PLACEHOLDER | |

## How to play
- Make an account, then start a New Game in one of your 3 save slots.
- Your party is always a Warrior, a Mage and a Cleric. You only pick their names.
- Every floor is one battle. Win to go up, lose and you try the same floor again.
- There is a boss every 5 floors: Stefan (5), Paulo (10), Jeno (15) and Jancy, the Final Developer (20).
- Battles are turn-based, the fastest unit goes first.
  - **Attack** hits one enemy.
  - **Skill** uses a class skill (unlocks at Lv 2 and Lv 5, some have a cooldown).
  - **Run** has a chance to escape (you stay on the same floor).
  - Every party member has 20 seconds to pick a move, or they lose their turn. **Pause** stops the timer.
  - **Exit Battle** (in the pause box) counts as running away. Leaving the page and pressing Play again resumes the same battle.
- Winning gives XP and sometimes an item. Losing still gives a bit of XP.
- In the **Party** page you spend upgrade points, equip items, dismiss and recruit members.
- The **Records** page shows your saves and your last battles. Score = XP gained.

## Controls
| Key | What it does |
|-----|--------------|
| W A S D / Arrow keys | move in the menu, pick a target |
| Enter / Space | choose, close the floor intro, Next Floor / Try Again |
| Esc / Backspace | go back (on the main battle menu: pause) |
| P | pause / resume (Pause > Exit Battle leaves the fight) |

The mouse works too: click a button, click an enemy to attack it, click a party member to heal them.

The full rules are also in the game: **How to Play** in the menu.

## Roles
| Page or feature | Guest | Player | Admin |
|-----------------|-------|--------|-------|
| Home, How to Play, Log In, Sign Up | Yes | Yes | Yes |
| Saves, battles, Party, Records, Profile (own data only) | No | Yes | Yes |
| Admin dashboard, manage users, enemies (with pictures) and items | No | No | Yes |

A player who opens an admin page gets **Access Denied**. To make someone an admin, run this in the Supabase SQL Editor:
```sql
UPDATE accounts SET role = 'admin' WHERE username = 'their_username';
```
They have to log out and log in again.

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

## Enemy pictures (Supabase Storage)
Without these settings, pictures are saved in `uploads/` (fine on XAMPP). On Vercel they **must** go to Supabase Storage,
because Vercel can't save files.
1. Supabase > **Storage** > **New bucket**, name it `enemy-pictures` and turn on **Public bucket**.
2. Supabase > **Project Settings** > **API Keys**: copy the project URL and a **secret** key (or the old `service_role` key).
3. Put them in `db_config.php` (`$supabaseUrl`, `$supabaseKey`, `$storageBucket`) and/or in the Vercel environment variables below.
   The secret key can do anything in your project: never put it in JavaScript or on GitHub.

## Deploy on Vercel
1. Run `dota.sql` in Supabase again (it adds the `sessions` table that Vercel needs).
2. Push the project to GitHub (`db_config.php` stays on your computer, it's in `.gitignore`).
3. On vercel.com: **Add New** > **Project** > import the GitHub repo. Framework preset: **Other**, no build command.
4. **Environment Variables** (same values as your `db_config.php`):

   | Name | Value |
   |------|-------|
   | `DB_HOST` | the Session pooler host, like `aws-0-ap-south-1.pooler.supabase.com` |
   | `DB_PORT` | `5432` |
   | `DB_NAME` | `postgres` |
   | `DB_USER` | the pooler user, like `postgres.abcdefgh` |
   | `DB_PASSWORD` | your database password |
   | `SUPABASE_URL` | like `https://abcdefgh.supabase.co` |
   | `SUPABASE_KEY` | the secret key |
   | `STORAGE_BUCKET` | `enemy-pictures` |

5. Project **Settings** > **Build and Deployment** > **Node.js Version**: `22.x` (what `vercel-php` supports).
6. **Deploy**, then open the address Vercel gives you.

How it works on Vercel: `vercel.json` sends every `.php` address to `api/index.php`, which only allows the real pages,
keeps sessions in the database (`includes/session_db.php`), and runs the page. CSS, JS and sounds are served as normal files.
XAMPP never uses `api/` or `vercel.json`.

## Problems
| You see | Fix |
|---------|-----|
| `Missing database details` | do step 2 of the setup (on Vercel: check the environment variables) |
| `Can't connect to the database right now` | check `db_config.php`, and check that the Supabase project is not paused |
| `Call to undefined function pg_connect()` | do step 3 of the setup |
| "Connection problem" when a battle ends | run `dota.sql` again (a table is missing) |
| `This save has no party, or there are no enemies` | run `dota.sql` again (the enemy list is empty) |
| Old look or old buttons after an update | press Ctrl + F5 |
| `Couldn't upload the picture to storage` | check the 3 Supabase Storage settings, and that the bucket exists and is public |
| Logged out after every click on Vercel | run `dota.sql` again (the `sessions` table is missing) |
| Vercel page shows an error right away | Vercel > your project > **Logs** shows the PHP error |

## Folders

```
/                    user pages (index.php, login.php, saves.php, party.php, battle.php, records.php,
                     account.php = profile, how_to_play.php, access_denied.php ...)
battle_action.php    does one battle turn (battle.js sends the action here)
includes/            reusable includes
    db.php               database connection (db_config.php, or Vercel environment variables)
    db_config.php        your database password (NOT in git, make it from the template)
    auth.php             requireLogin(), requireAdmin(), password rules
    config.php           all game numbers (stats, skills, enemies, drops, upload limits)
    functions.php        game formulas (damage, xp, rewards ...)
    queries.php          all the SQL
    upload.php           picture uploads (Supabase Storage or uploads/)
    session_db.php       sessions in the database (Vercel only)
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
admin/               admin pages only (dashboard, users, enemies, items)
api/index.php        Vercel only: runs the pages on Vercel
vercel.json          Vercel settings (runtime, region, which addresses go to api/index.php)
.vercelignore        files Vercel doesn't get (db_config.php, dota.sql ...)
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
