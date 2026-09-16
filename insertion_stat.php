<?php
    $conn = new mysqli("localhost","bisrepetitaplace_aerom","ur,V.BWY.#;9","bisrepetitaplace_aeromorning");


    // stat
    $sqlCmd = "select distinct(slider) as slider from stat";
    $result = $conn->query($sqlCmd);
    $lang = ["fr-FR", "en-US"];

    $date = strtotime("2023-07-01");
    $datefin = strtotime("2024-02-28");
    $all = [];

    $allSliders = $result->fetch_all(MYSQLI_ASSOC);

    $allArray = "";

    do {

        foreach ($allSliders as $slider){
            foreach ($lang as $l){
                $number = rand(0, 5);
                for ($i = 0; $i < $number; $i++){
                    array_push($all, "('" . $slider["slider"] . "','" . $l . "','" . date("Y-m-d", $date) . "')");
                    
                }
            }  
        }
        $date = strtotime("+1 day", $date);
    } while ($date < $datefin);

    var_dump(implode(", ", $all));

    $sqlInsert = "INSERT INTO `stat`(slider,langue, date) VALUES " . implode(", ", $all);
    $conn->query($sqlInsert);
    echo "Stat Finished <br>";


    // stat2
    $sqlCmd = "select distinct(slider) as slider from stat2";
    $result = $conn->query($sqlCmd);
    $lang = ["fr-FR", "en-US"];

    $date = strtotime("2023-07-01");
    $datefin = strtotime("2024-02-28");
    $all = [];

    $allSliders = $result->fetch_all(MYSQLI_ASSOC);

    $allArray = "";

    do {

        foreach ($allSliders as $slider){
            foreach ($lang as $l){
                $number = rand(0, 5);
                for ($i = 0; $i < $number; $i++){
                    array_push($all, "('" . $slider["slider"] . "','" . $l . "','" . date("Y-m-d", $date) . "')");
                    
                }
            }  
        }
        $date = strtotime("+1 day", $date);
    } while ($date < $datefin);

    var_dump(implode(", ", $all));

    $sqlInsert = "INSERT INTO `stat2`(slider,langue, date) VALUES " . implode(", ", $all);
    $conn->query($sqlInsert);
    echo "Stat2 Finished <br>";

    // stat3
    $sqlCmd = "select distinct(slider) as slider from stat3";
    $result = $conn->query($sqlCmd);
    $lang = ["fr-FR", "en-US"];

    $date = strtotime("2023-07-01");
    $datefin = strtotime("2024-02-28");
    $all = [];

    $allSliders = $result->fetch_all(MYSQLI_ASSOC);

    $allArray = "";

    do {

        foreach ($allSliders as $slider){
            foreach ($lang as $l){
                $number = rand(0, 5);
                for ($i = 0; $i < $number; $i++){
                    array_push($all, "('" . $slider["slider"] . "','" . $l . "','" . date("Y-m-d", $date) . "')");
                    
                }
            }  
        }
        $date = strtotime("+1 day", $date);
    } while ($date < $datefin);

    var_dump(implode(", ", $all));

    $sqlInsert = "INSERT INTO `stat3`(slider,langue, date) VALUES " . implode(", ", $all);
    $conn->query($sqlInsert);
    echo "Stat2 Finished <br>";
?>