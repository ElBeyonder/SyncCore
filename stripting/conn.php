
<?php

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "workplay_new";
    $connLocal = new mysqli($servername, $username, $password, $dbname);
    $connLocal->query("SET NAMES 'utf8mb4'");


    $servername = "5.161.53.211";
    $username = "bonospac_boletos_paco_root";
    $password = "Bonos_paco_1234";
    $dbname = "bonospac_boletos_paco";
    $connCloud = new mysqli($servername, $username, $password, $dbname);
    $connCloud->query("SET NAMES 'utf8mb4'");



