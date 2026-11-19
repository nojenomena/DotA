# Defense-of-the-Alphards-DotA-
A web-based game for CBAD

Turn-based RPG where your party climbs a 20-floor tower and fights the Developers.
PHP + PostgreSQL (Supabase) + plain HTML, CSS and JavaScript.

## Folders

```
/                    user pages (index.php, login.php, saves.php, battle.php ...)
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
    js/battle.js
    images/              game pictures
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

## Setup
1. Run `dota.sql` in Supabase (SQL Editor).
2. Copy `includes/db_config.template.php` to `includes/db_config.php` and fill in your Supabase session pooler details.
3. Put the folder in XAMPP's `htdocs` (or link it with `mklink /J`) and open it in the browser.
