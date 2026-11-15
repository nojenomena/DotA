// battle controls: keyboard (WASD / arrows, Enter / Space, Esc) and mouse

let selected = 0;      // menu button: 0 attack, 1 skill, 2 item, 3 run
let mode = "menu";     // "menu", "target", "wait" or "over"
let target = 0;        // which enemy is picked

const commands = ["attack", "skill", "item", "run"];

// hp bar color and width
function setBar(id, fighter) {
    const bar = document.getElementById(id);
    const percent = fighter.current_hp / fighter.max_hp * 100;

    bar.style.width = percent + "%";

    if (percent > 50) {
        bar.style.backgroundColor = "green";
    } else if (percent > 20) {
        bar.style.backgroundColor = "orange";
    } else {
        bar.style.backgroundColor = "red";
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
    }

    // menu buttons
    for (let i = 0; i < 4; i++) {
        if (mode == "menu" && selected == i) {
            document.getElementById("cmd-" + i).className = "command is-selected";
        } else {
            document.getElementById("cmd-" + i).className = "command";
        }
    }

    document.getElementById("round").innerText = "Round " + battle.round;

    // message on top of the menu
    const message = document.getElementById("message");

    if (mode == "over") {
        document.getElementById("menu").style.display = "none";
        document.getElementById("battle-over").style.display = "block";

        if (battle.result == "won") {
            message.innerText = "Victory!";
        } else if (battle.result == "lost") {
            message.innerText = "Defeat...";
        } else {
            message.innerText = "You escaped.";
        }
    } else if (mode == "target") {
        message.innerText = "Attack which enemy? (Esc to go back)";
    } else if (mode == "wait") {
        message.innerText = "...";
    } else {
        message.innerText = "What will " + battle.party[battle.order[battle.turn]].unit_name + " do?";
    }
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
        body: JSON.stringify({ command: command, target: target })
    })
    .then(response => response.json())
    .then(data => {
        mode = "menu";

        if (data.error) {
            updateScreen();
            document.getElementById("message").innerText = data.error;
            return;
        }

        battle = data.battle;
        for (let i = 0; i < data.new_log.length; i++) {
            addLog(data.new_log[i]);
        }

        selected = 0;
        updateScreen();
    })
    .catch(error => {
        mode = "menu";
        updateScreen();
        document.getElementById("message").innerText = "Connection problem. Try again.";
    });
}

// first enemy that is still alive
function firstEnemy() {
    for (let i = 0; i < battle.enemies.length; i++) {
        if (battle.enemies[i].current_hp > 0) {
            return i;
        }
    }
    return 0;
}

// move to the next (+1) or previous (-1) enemy that is still alive
function moveTarget(step) {
    for (let tries = 0; tries < battle.enemies.length; tries++) {
        target = target + step;

        if (target < 0) {
            target = battle.enemies.length - 1;
        }
        if (target >= battle.enemies.length) {
            target = 0;
        }

        if (battle.enemies[target].current_hp > 0) {
            return;
        }
    }
}

function chooseCommand(number) {
    if (mode != "menu" && mode != "target") {
        return;
    }

    selected = number;

    if (commands[number] == "attack") {
        mode = "target";
        target = firstEnemy();
        updateScreen();
    } else {
        sendAction(commands[number]);
    }
}

// clicking an enemy attacks it right away
function clickEnemy(number) {
    if ((mode == "menu" || mode == "target") && battle.enemies[number].current_hp > 0) {
        target = number;
        sendAction("attack");
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

    if (!(up || down || left || right || enter || back)) {
        return;
    }

    // stops the page from scrolling and stops a clicked button from being pressed again
    event.preventDefault();

    // holding Enter down shouldn't send many attacks
    if (event.repeat && enter) {
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
    } else if (mode == "target") {
        if (up || left) {
            moveTarget(-1);
        } else if (down || right) {
            moveTarget(1);
        } else if (enter) {
            sendAction("attack");
            return;
        } else if (back) {
            mode = "menu";
        }
    } else if (mode == "over" && enter) {
        window.location.href = "saves.php";
        return;
    }

    updateScreen();
});

// start
updateScreen();
document.getElementById("battle-log").scrollTop = document.getElementById("battle-log").scrollHeight;
