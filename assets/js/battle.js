// battle controls: keyboard (WASD / arrows, Enter / Space, Esc) and mouse

let selected = 0;        // menu button: 0 attack, 1 skill, 2 item, 3 run
let mode = "menu";       // "menu", "skills", "target", "ally", "wait", "intro", "paused" or "over"
let target = 0;          // which enemy (or party member for heals) is picked
let skillSelected = 0;   // which skill in the skill list
let usingSkill = -1;     // the skill being aimed, -1 = normal attack
let popupShown = false;  // the victory / game over box is already showing

// turn timer and pause
const playerModes = ["menu", "skills", "target", "ally"];    // the player is picking a move
let timeLeft = turnSeconds;  // seconds left for this turn (turnSeconds comes from config.php)
let timerTurn = "";          // which turn the timer is counting, like "3-1" (round 3, 2nd member)
let pausedFrom = "";         // the mode to go back to after Resume
let pauseSelected = 0;       // 0 = Resume, 1 = Exit Battle
let exiting = false;         // Exit Battle was pressed

const commands = ["attack", "skill", "item", "run"];

// ---------- sounds and hit effects ----------

// plays assets/sounds/<name>.mp3, if the file is missing nothing happens
function playSound(name) {
    const sound = new Audio("assets/sounds/" + name + ".mp3");
    sound.volume = 0.5;
    sound.play().catch(function () {});
}

// shakes a party or enemy box and flashes it white (box id like "party-0" or "enemy-2")
function shake(id) {
    const box = document.getElementById(id);
    box.style.animation = "shake 0.4s, hit-flash 0.3s";
    setTimeout(function () {
        box.style.animation = "";
    }, 400);
}

// green glow on a box that got healed
function healGlow(id) {
    const box = document.getElementById(id);
    box.style.animation = "heal-glow 0.6s";
    setTimeout(function () {
        box.style.animation = "";
    }, 600);
}

// status effects: the emoji and name shown under the hp bar
const statusLooks = {
    burn: { icon: "🔥", name: "Burn" },
    poison: { icon: "☠", name: "Poison" },
    stun: { icon: "💫", name: "Stun" }
};

// fills the status line with tags like "🔥 Burn 2" (2 = turns left)
// and gives the box the glow of its strongest effect (stun, then burn, then poison)
function showStatus(box, lineId, status) {
    const line = document.getElementById(lineId);
    line.innerHTML = "";

    const names = Object.keys(status);
    for (let i = 0; i < names.length; i++) {
        const look = statusLooks[names[i]] || { icon: "", name: names[i] };
        const tag = document.createElement("span");
        tag.className = "status-tag status-" + names[i];
        tag.textContent = look.icon + " " + look.name + " " + status[names[i]];
        line.appendChild(tag);
    }

    const glowOrder = ["stun", "burn", "poison"];
    for (let i = 0; i < glowOrder.length; i++) {
        if (status[glowOrder[i]] !== undefined) {
            box.className = box.className + " has-" + glowOrder[i];
            return;
        }
    }
}

// shakes the whole battle screen, for critical hits
function screenShake() {
    const screen = document.getElementById("container");
    screen.style.animation = "screen-shake 0.35s";
    setTimeout(function () {
        screen.style.animation = "";
    }, 350);
}

// true if a "Critical hit!" line in the log is about this fighter taking this much damage
function wasCrit(newLog, name, damage) {
    for (let i = 0; i < newLog.length; i++) {
        const line = newLog[i];
        if (line.includes("Critical hit!") && line.includes(name) && line.includes(" " + damage + " damage")) {
            return true;
        }
    }
    return false;
}

// the "-25" or "+30" that floats up over a box (big = critical hit)
function floatNumber(id, text, color, big) {
    const box = document.getElementById(id);
    const number = document.createElement("div");

    number.className = big ? "float-number is-crit" : "float-number";
    number.innerText = text;
    number.style.color = color;
    box.appendChild(number);

    setTimeout(function () {
        number.remove();
    }, 1000);
}

// saves everyone's hp so we can compare after the turn
function getHps() {
    const hps = { party: [], enemies: [] };
    for (let i = 0; i < battle.party.length; i++) {
        hps.party.push(battle.party[i].current_hp);
    }
    for (let i = 0; i < battle.enemies.length; i++) {
        hps.enemies.push(battle.enemies[i].current_hp);
    }
    return hps;
}

// compares the old hp to the new hp: shake + numbers + sound
function showEffects(oldHps, newLog) {
    let gotHit = false;
    let gotHealed = false;
    let gotCrit = false;

    const groups = [["party", battle.party], ["enemies", battle.enemies]];
    for (let g = 0; g < groups.length; g++) {
        const name = groups[g][0];
        const list = groups[g][1];
        const prefix = name == "party" ? "party-" : "enemy-";

        for (let i = 0; i < list.length; i++) {
            const change = list[i].current_hp - oldHps[name][i];

            if (change < 0) {
                const fighterName = name == "party" ? list[i].unit_name : list[i].name;
                const isCrit = wasCrit(newLog, fighterName, -change);

                shake(prefix + i);
                if (isCrit) {
                    floatNumber(prefix + i, change + " CRIT!", "gold", true);
                    gotCrit = true;
                } else {
                    floatNumber(prefix + i, change, "red");
                }
                gotHit = true;
            } else if (change > 0) {
                healGlow(prefix + i);
                floatNumber(prefix + i, "+" + change, "lightgreen");
                gotHealed = true;
            }
        }
    }

    if (gotCrit) {
        screenShake();
    }

    // only one sound at a time
    if (battle.result == "won") {
        playSound("victory");
    } else if (battle.result == "lost") {
        playSound("defeat");
    } else if (newLog.join(" ").includes("Critical hit!")) {
        playSound("crit");
    } else if (gotHit) {
        playSound("hit");
    } else if (gotHealed) {
        playSound("heal");
    }
}

// the party member whose turn it is
function actor() {
    return battle.party[battle.order[battle.turn]];
}

// hp bar color and width
// the red "drain" bar behind it stays at the old hp for a moment, then slides down
function setBar(id, fighter) {
    const bar = document.getElementById(id);
    const drain = document.getElementById(id.replace("-bar-", "-drain-"));
    const percent = fighter.current_hp / fighter.max_hp * 100;

    bar.style.width = percent + "%";

    const oldPercent = drain.dataset.percent === undefined ? percent : Number(drain.dataset.percent);
    drain.dataset.percent = percent;
    if (percent < oldPercent) {
        setTimeout(function () {
            drain.style.width = drain.dataset.percent + "%";
        }, 400);
    } else if (percent > oldPercent || drain.style.width == "") {
        // healed, or the first time the bar is drawn: no delay
        drain.style.width = percent + "%";
    }

    if (percent > 50) {
        bar.style.backgroundColor = "green";
    } else if (percent > 20) {
        bar.style.backgroundColor = "orange";
    } else {
        bar.style.backgroundColor = "red";
    }
}

// makes the skill buttons for whoever's turn it is
function showSkills() {
    const list = document.getElementById("skill-list");
    const unit = actor();
    const unitSkills = skills[unit.unit_class];

    list.innerHTML = "";

    for (let i = 0; i < unitSkills.length; i++) {
        const button = document.createElement("button");
        let text = unitSkills[i].name;

        if (unit.level < unitSkills[i].level) {
            text = text + " (Lv " + unitSkills[i].level + ")";
        } else if (unit.cooldowns[i] > 0) {
            text = text + " (ready in " + unit.cooldowns[i] + ")";
        }

        button.innerText = text;
        button.className = "command";
        if (skillSelected == i) {
            button.className = "command is-selected";
        }
        button.onclick = function () {
            chooseSkill(i);
        };

        list.appendChild(button);
    }
}

function updateScreen() {
    if (battle.result != "") {
        mode = "over";
    }

    // party
    for (let i = 0; i < battle.party.length; i++) {
        const unit = battle.party[i];
        const box = document.getElementById("party-" + i);

        document.getElementById("party-hp-" + i).innerText = "HP " + unit.current_hp + " / " + unit.max_hp;
        setBar("party-bar-" + i, unit);

        box.className = "slot";
        if (unit.current_hp <= 0) {
            box.className = box.className + " is-down";
        }
        if (mode != "over" && battle.order[battle.turn] == i) {
            box.className = box.className + " is-acting";
        }
        if (mode == "ally" && target == i) {
            box.className = box.className + " is-selected";
        }
        showStatus(box, "party-status-" + i, unit.status);
    }

    // enemies
    for (let i = 0; i < battle.enemies.length; i++) {
        const enemy = battle.enemies[i];
        const box = document.getElementById("enemy-" + i);

        document.getElementById("enemy-hp-" + i).innerText = "HP " + enemy.current_hp + " / " + enemy.max_hp;
        setBar("enemy-bar-" + i, enemy);

        box.className = "slot enemy-slot";
        if (enemy.is_boss) {
            box.className = box.className + " is-boss";
        }
        if (enemy.current_hp <= 0) {
            box.className = box.className + " is-down";
        }
        if (mode == "target" && target == i) {
            box.className = box.className + " is-selected";
        }
        showStatus(box, "enemy-status-" + i, enemy.status);
    }

    // menu buttons
    for (let i = 0; i < 4; i++) {
        if (mode == "menu" && selected == i) {
            document.getElementById("cmd-" + i).className = "command is-selected";
        } else {
            document.getElementById("cmd-" + i).className = "command";
        }
    }

    // skill list replaces the menu while picking a skill
    if (mode == "skills") {
        document.getElementById("menu").style.display = "none";
        document.getElementById("skill-list").style.display = "block";
        showSkills();
    } else {
        document.getElementById("skill-list").style.display = "none";
        if (mode != "over") {
            document.getElementById("menu").style.display = "block";
        }
    }

    document.getElementById("round").innerText = "Round " + battle.round;

    // message on top of the menu
    const message = document.getElementById("message");

    if (mode == "over") {
        document.getElementById("menu").style.display = "none";
        message.innerText = "The battle is over.";

        if (!popupShown) {
            showEndPopup();
        }
    } else if (mode == "intro") {
        message.innerText = "...";
    } else if (mode == "skills") {
        message.innerText = "Which skill? (Esc to go back)";
    } else if (mode == "target" && usingSkill >= 0) {
        message.innerText = "Use " + skills[actor().unit_class][usingSkill].name + " on which enemy? (Esc to go back)";
    } else if (mode == "target") {
        message.innerText = "Attack which enemy? (Esc to go back)";
    } else if (mode == "ally") {
        message.innerText = "Use " + skills[actor().unit_class][usingSkill].name + " on who? (Esc to go back)";
    } else if (mode == "wait") {
        message.innerText = "...";
    } else if (mode == "paused") {
        message.innerText = "Paused.";
    } else {
        message.innerText = "What will " + actor().unit_name + " do?";
    }

    checkNewTurn();
    showTimer();
}

function addLog(text) {
    const log = document.getElementById("battle-log");
    const line = document.createElement("div");

    line.innerText = text;
    log.appendChild(line);
    log.scrollTop = log.scrollHeight;
}

// sends the action to battle_action.php and shows what happened
function sendAction(command) {
    mode = "wait";
    updateScreen();

    fetch("battle_action.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ command: command, target: target, skill: usingSkill })
    })
    .then(response => response.json())
    .then(data => {
        mode = "menu";
        usingSkill = -1;

        if (data.error) {
            updateScreen();
            document.getElementById("message").innerText = data.error;
            return;
        }

        // Exit Battle: the battle is saved as "fled", go back to the saves
        if (exiting) {
            window.location.href = "saves.php";
            return;
        }

        const oldHps = getHps();
        battle = data.battle;
        showEffects(oldHps, data.new_log);

        for (let i = 0; i < data.new_log.length; i++) {
            addLog(data.new_log[i]);
        }

        selected = 0;
        skillSelected = 0;
        updateScreen();
    })
    .catch(error => {
        exiting = false;
        mode = "menu";
        usingSkill = -1;
        updateScreen();
        document.getElementById("message").innerText = "Connection problem. Try again.";
    });
}

// first enemy (or party member) that is still alive
function firstAlive(list) {
    for (let i = 0; i < list.length; i++) {
        if (list[i].current_hp > 0) {
            return i;
        }
    }
    return 0;
}

// move to the next (+1) or previous (-1) one that is still alive
function moveTarget(list, step) {
    for (let tries = 0; tries < list.length; tries++) {
        target = target + step;

        if (target < 0) {
            target = list.length - 1;
        }
        if (target >= list.length) {
            target = 0;
        }

        if (list[target].current_hp > 0) {
            return;
        }
    }
}

function chooseCommand(number) {
    if (mode != "menu" && mode != "target") {
        return;
    }

    selected = number;
    usingSkill = -1;
    playSound("select");

    if (commands[number] == "attack") {
        mode = "target";
        target = firstAlive(battle.enemies);
        updateScreen();
    } else if (commands[number] == "skill") {
        mode = "skills";
        skillSelected = 0;
        updateScreen();
    } else {
        sendAction(commands[number]);
    }
}

function chooseSkill(number) {
    if (mode != "skills") {
        return;
    }

    const unit = actor();
    const skill = skills[unit.unit_class][number];
    skillSelected = number;
    playSound("select");

    if (unit.level < skill.level) {
        updateScreen();
        document.getElementById("message").innerText = skill.name + " unlocks at Lv " + skill.level + ".";
        return;
    }
    if (unit.cooldowns[number] > 0) {
        updateScreen();
        document.getElementById("message").innerText = skill.name + " is ready in " + unit.cooldowns[number] + " rounds.";
        return;
    }

    usingSkill = number;

    if (skill.target == "enemy") {
        mode = "target";
        target = firstAlive(battle.enemies);
        updateScreen();
    } else if (skill.target == "ally") {
        mode = "ally";
        target = battle.order[battle.turn];    // starts on yourself
        updateScreen();
    } else {
        sendAction("skill");    // all enemies or all allies, no need to pick
    }
}

// clicking an enemy attacks it (or uses the skill being aimed)
function clickEnemy(number) {
    if (battle.enemies[number].current_hp <= 0) {
        return;
    }

    if (mode == "target") {
        target = number;
        sendAction(usingSkill >= 0 ? "skill" : "attack");
    } else if (mode == "menu") {
        target = number;
        usingSkill = -1;
        sendAction("attack");
    }
}

// clicking a party member while picking who to heal
function clickAlly(number) {
    if (mode == "ally" && battle.party[number].current_hp > 0) {
        target = number;
        sendAction("skill");
    }
}

document.addEventListener("keydown", function (event) {
    const key = event.key.toLowerCase();

    const up = key == "w" || key == "arrowup";
    const down = key == "s" || key == "arrowdown";
    const left = key == "a" || key == "arrowleft";
    const right = key == "d" || key == "arrowright";
    const enter = key == "enter" || key == " ";
    const back = key == "escape" || key == "backspace";
    const pauseKey = key == "p";

    if (!(up || down || left || right || enter || back || pauseKey)) {
        return;
    }

    // stops the page from scrolling and stops a clicked button from being pressed again
    event.preventDefault();

    // holding Enter down shouldn't send many attacks
    if (event.repeat && enter) {
        return;
    }

    // pause box: arrows pick Resume / Exit Battle, Esc or P resumes
    if (mode == "paused") {
        if (back || pauseKey) {
            resumeGame();
        } else if (enter) {
            if (pauseSelected == 0) {
                resumeGame();
            } else {
                exitBattle();
            }
        } else {
            pauseSelected = pauseSelected == 0 ? 1 : 0;
            showPauseButtons();
        }
        return;
    }

    // P pauses, and so does Esc on the main menu (there's nothing to go back to there)
    if (pauseKey || (mode == "menu" && back)) {
        pauseGame();
        return;
    }

    if (mode == "menu") {
        // the menu is 2 x 2
        if (left || right) {
            if (selected % 2 == 0) {
                selected = selected + 1;
            } else {
                selected = selected - 1;
            }
        } else if (up || down) {
            if (selected < 2) {
                selected = selected + 2;
            } else {
                selected = selected - 2;
            }
        } else if (enter) {
            chooseCommand(selected);
            return;
        }
    } else if (mode == "skills") {
        const count = skills[actor().unit_class].length;

        if (up || left) {
            skillSelected = (skillSelected - 1 + count) % count;
        } else if (down || right) {
            skillSelected = (skillSelected + 1) % count;
        } else if (enter) {
            chooseSkill(skillSelected);
            return;
        } else if (back) {
            mode = "menu";
        }
    } else if (mode == "target" || mode == "ally") {
        const list = mode == "target" ? battle.enemies : battle.party;

        if (up || left) {
            moveTarget(list, -1);
        } else if (down || right) {
            moveTarget(list, 1);
        } else if (enter) {
            sendAction(usingSkill >= 0 ? "skill" : "attack");
            return;
        } else if (back) {
            mode = usingSkill >= 0 ? "skills" : "menu";
            usingSkill = -1;
        }
    } else if (mode == "intro" && enter) {
        closeIntro();
        return;
    } else if (mode == "over" && enter) {
        if (battle.cleared) {
            window.location.href = "saves.php";
        } else {
            document.getElementById("continue-form").submit();
        }
        return;
    }

    // little click when moving around the menus
    if (mode == "menu" || mode == "skills" || mode == "target" || mode == "ally") {
        playSound("select");
    }

    updateScreen();
});

// ---------- big box in the middle (floor intro, victory, game over) ----------

// look: "" for a normal box, "boss" (red flash) or "victory" (gold burst)
function showPopup(title, lines, withButtons, hint, look) {
    document.getElementById("popup-title").innerText = title;
    document.getElementById("popup-box").className = look ? "is-" + look : "";

    const text = document.getElementById("popup-text");
    text.innerHTML = "";
    for (let i = 0; i < lines.length; i++) {
        const line = document.createElement("p");
        line.innerText = lines[i];
        // "Aldric reached Lv 3!" lines are shown in gold with a small pop
        if (lines[i].includes(" reached Lv ")) {
            line.className = "level-up-line";
            line.innerText = "⬆ LEVEL UP! " + lines[i];
        }
        text.appendChild(line);
    }

    document.getElementById("popup-buttons").style.display = withButtons ? "block" : "none";
    document.getElementById("popup-hint").innerText = hint;
    document.getElementById("popup").style.display = "block";
}

function hidePopup() {
    document.getElementById("popup").style.display = "none";
}

// "Floor 5" box when a battle starts
function showIntro() {
    const names = [];
    let boss = "";

    for (let i = 0; i < battle.enemies.length; i++) {
        names.push(battle.enemies[i].name);
        if (battle.enemies[i].is_boss) {
            boss = battle.enemies[i].name;
        }
    }

    let lines = [];
    if (boss != "") {
        lines = ["BOSS: " + boss];
    } else {
        lines = ["Enemies: " + names.join(", ")];
    }

    mode = "intro";
    updateScreen();
    showPopup("Floor " + battle.floor, lines, false, "Press Enter to start", boss != "" ? "boss" : "");

    // closes by itself after a bit
    setTimeout(closeIntro, 2500);
}

function closeIntro() {
    if (mode == "intro") {
        hidePopup();
        mode = "menu";
        updateScreen();
    }
}

// victory / game over / escaped / tower cleared
function showEndPopup() {
    popupShown = true;

    // the reward lines are in the log, after "Victory!" or "Your party has fallen"
    let start = battle.log.length;
    for (let i = 0; i < battle.log.length; i++) {
        if (battle.log[i].startsWith("Victory!") || battle.log[i].startsWith("Your party has fallen")) {
            start = i + 1;
        }
    }
    const lines = battle.log.slice(start);

    const button = document.getElementById("continue-button");
    document.getElementById("continue-form").style.display = "inline";

    if (battle.cleared) {
        showPopup("Tower Cleared!", ["You beat the Final Developer!"].concat(lines), true, "Thanks for playing!", "victory");
        document.getElementById("continue-form").style.display = "none";
    } else if (battle.result == "won") {
        button.innerHTML = '<i class="bi bi-arrow-up-circle" aria-hidden="true"></i> Next Floor';
        showPopup("Victory!", lines, true, "Enter = Next Floor", "victory");
    } else if (battle.result == "lost") {
        button.innerHTML = '<i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Try Again';
        showPopup("Game Over", ["Your party has fallen on floor " + battle.floor + "."].concat(lines), true, "Enter = Try Again");
    } else {
        button.innerHTML = '<i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Try Again';
        showPopup("You Escaped", ["You ran away from floor " + battle.floor + "."], true, "Enter = Try Again");
    }
}

// ---------- turn timer ----------

// a new party member's turn: start again from turnSeconds
function checkNewTurn() {
    const turnKey = battle.round + "-" + battle.turn;

    if (turnKey != timerTurn) {
        timerTurn = turnKey;
        timeLeft = turnSeconds;
    }
}

function showTimer() {
    const timer = document.getElementById("timer");

    if (playerModes.includes(mode) || mode == "paused") {
        timer.innerText = "Time left: " + timeLeft + "s";
        timer.className = timeLeft <= 5 ? "timer-low" : "";
    } else {
        timer.innerText = "";
    }
}

// every second: only counts down while the player is picking a move
// (not during the intro, pause, end screens, or while waiting for the server)
setInterval(function () {
    if (!playerModes.includes(mode)) {
        return;
    }

    timeLeft--;

    if (timeLeft <= 0) {
        timeLeft = 0;
        usingSkill = -1;
        sendAction("skip");    // the server writes "... took too long and lost their turn!"
        return;
    }

    showTimer();
}, 1000);

// ---------- pause ----------

function pauseGame() {
    if (!playerModes.includes(mode)) {
        return;
    }

    pausedFrom = mode;
    mode = "paused";
    pauseSelected = 0;

    document.getElementById("pause").style.display = "block";
    showPauseButtons();
    updateScreen();
}

function resumeGame() {
    if (mode != "paused") {
        return;
    }

    mode = pausedFrom;
    document.getElementById("pause").style.display = "none";
    updateScreen();
}

function showPauseButtons() {
    // classList.toggle adds or removes only "is-selected", the bootstrap classes stay
    document.getElementById("resume-button").classList.toggle("is-selected", pauseSelected == 0);
    document.getElementById("exit-button").classList.toggle("is-selected", pauseSelected == 1);
}

function exitBattle() {
    if (mode != "paused") {
        return;
    }
    if (!confirm("Leave this battle? It counts as running away: no XP, and you stay on this floor.")) {
        return;
    }

    exiting = true;
    usingSkill = -1;
    document.getElementById("pause").style.display = "none";
    sendAction("exit");
}

// start
updateScreen();

// a brand new battle starts with the floor intro
if (battle.result == "" && battle.round == 1 && battle.turn == 0 && battle.log.length == 1) {
    showIntro();
}
document.getElementById("battle-log").scrollTop = document.getElementById("battle-log").scrollHeight;
