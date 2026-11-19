<?php
    session_start();
    include("includes/config.php");
    include("includes/db.php");
    include("includes/queries.php");

    if(!isset($_SESSION["account_id"])){
        header("Location: login.php");
        exit();
    }

    $totals = getSaveTotals($_SESSION["account_id"]);
    $battles = getBattleResults($_SESSION["account_id"], 30);

    // how each result looks in the table
    $resultText = ["won" => "Victory", "lost" => "Defeat", "fled" => "Escaped"];
    $resultColor = ["won" => "lightgreen", "lost" => "red", "fled" => "gray"];
?>
<?php
    $pageTitle = "Records";
    include("includes/header.php");
    include("includes/nav.php");
?>
    <div id="container">
        <div id="top-bar">
            <h2>Records</h2>
        </div>

        <h3>Your saves</h3>
        <br>
        <?php if(count($totals) == 0){ ?>
            <p class="small-text">No saves yet.</p>
        <?php } else { ?>
            <table class="item-table" border="1">
                <tr>
                    <th>Slot</th>
                    <th>Highest floor</th>
                    <th>Battles</th>
                    <th>Wins</th>
                    <th>Losses</th>
                    <th>Total score (XP)</th>
                </tr>
                <?php foreach($totals as $row){ ?>
                    <tr>
                        <td>Slot <?php echo $row["slot_number"]; ?></td>
                        <td><?php echo $row["highest_floor"]; ?> / <?php echo $config["total_floors"]; ?></td>
                        <td><?php echo $row["battles"]; ?></td>
                        <td><?php echo $row["wins"]; ?></td>
                        <td><?php echo $row["losses"]; ?></td>
                        <td><b><?php echo $row["total_score"]; ?></b></td>
                    </tr>
                <?php } ?>
            </table>
        <?php } ?>

        <br><br>
        <h3>Last <?php echo count($battles); ?> battles</h3>
        <br>
        <?php if(count($battles) == 0){ ?>
            <p class="small-text">No battles yet. Go climb the tower!</p>
        <?php } else { ?>
            <table class="item-table" border="1">
                <tr>
                    <th>Date</th>
                    <th>Slot</th>
                    <th>Floor</th>
                    <th>Result</th>
                    <th>Rounds</th>
                    <th>Score (XP)</th>
                </tr>
                <?php foreach($battles as $row){ ?>
                    <tr>
                        <td><?php echo date("M j, g:i A", strtotime($row["finished_at"])); ?></td>
                        <td><?php echo $row["slot_number"]; ?></td>
                        <td><?php echo $row["floor"]; ?></td>
                        <td style="color: <?php echo $resultColor[$row["result"]]; ?>;"><?php echo $resultText[$row["result"]]; ?></td>
                        <td><?php echo $row["rounds"]; ?></td>
                        <td><?php echo $row["xp_gained"]; ?></td>
                    </tr>
                <?php } ?>
            </table>
        <?php } ?>
    </div>
<?php include("includes/footer.php"); ?>
