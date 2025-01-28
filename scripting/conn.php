
<?php

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "jeloda12";
    $connLocal = new mysqli($servername, $username, $password, $dbname);
    $connLocal->query("SET NAMES 'utf8mb4'");


    $servername = "192.99.84.36";
    $username = "ondersof_jeloda_root";
    $password = "Jeloda_1234";
    $dbname = "ondersof_jeloda_app";
    $connCloud = new mysqli($servername, $username, $password, $dbname);
    $connCloud->query("SET NAMES 'utf8mb4'");



