<?php
require_once 'conn.php';

// Función para obtener las tablas de una base de datos
function obtenerTablas($conexion) {
    $tablas = array();

    // Preparamos la consulta SQL
    $sql = "SHOW TABLES";
    $stmt = $conexion->prepare($sql);

    if ($stmt) {
        $stmt->execute();
        $resultado = $stmt->get_result();

        // Obtenemos los nombres de las tablas
        while ($fila = $resultado->fetch_array()) {
            $tablas[] = $fila[0];
        }

        $stmt->close();
    } else {
        die("Error en la consulta: " . $conexion->error);
    }

    return $tablas;
}

// Función para mostrar las tablas comunes
function mostrarTablasComunes($connLocal, $connCloud) {
    // Obtenemos las tablas de ambas conexiones
    $tablasLocal = obtenerTablas($connLocal);
    $tablasCloud = obtenerTablas($connCloud);

    // Filtramos las tablas comunes
    $tablasComunes = array_intersect($tablasLocal, $tablasCloud);

    // Creamos la tabla HTML para las tablas comunes
    echo '<table border="1" cellpadding="5" style="border-collapse: collapse; margin: 20px;">';
    echo '<tr style="background-color: #f0f0f0;">';
    echo '<th>Tablas Comunes (Local y Nube)</th>';
    echo '</tr>';

    foreach ($tablasComunes as $tabla) {
        echo '<tr>';
        echo '<td>' . $tabla . '</td>';
        echo '</tr>';
    }

    echo '</table>';
}

// Función para mostrar las tablas en la nube que no están en local
function mostrarTablasEnNubeNoEnLocal($connLocal, $connCloud) {
    // Obtenemos las tablas de ambas conexiones
    $tablasLocal = obtenerTablas($connLocal);
    $tablasCloud = obtenerTablas($connCloud);

    // Filtramos las tablas que están en la nube pero no en local
    $tablasNubeNoLocal = array_diff($tablasCloud, $tablasLocal);

    // Creamos la tabla HTML para las tablas en la nube que no están en local
    echo '<table border="1" cellpadding="5" style="border-collapse: collapse; margin: 20px;">';
    echo '<tr style="background-color: #f0f0f0;">';
    echo '<th>Tablas en la Nube pero no en Local</th>';
    echo '</tr>';

    foreach ($tablasNubeNoLocal as $tabla) {
        echo '<tr>';
        echo '<td>' . $tabla . '</td>';
        echo '</tr>';
    }

    echo '</table>';
}

// Función para mostrar las tablas en local que no están en la nube
function mostrarTablasEnLocalNoEnNube($connLocal, $connCloud) {
    // Obtenemos las tablas de ambas conexiones
    $tablasLocal = obtenerTablas($connLocal);
    $tablasCloud = obtenerTablas($connCloud);

    // Filtramos las tablas que están en local pero no en la nube
    $tablasLocalNoNube = array_diff($tablasLocal, $tablasCloud);

    // Creamos la tabla HTML para las tablas en local que no están en la nube
    echo '<table border="1" cellpadding="5" style="border-collapse: collapse; margin: 20px;">';
    echo '<tr style="background-color: #f0f0f0;">';
    echo '<th>Tablas en Local pero no en la Nube</th>';
    echo '</tr>';

    foreach ($tablasLocalNoNube as $tabla) {
        echo '<tr>';
        echo '<td>' . $tabla . '</td>';
        echo '</tr>';
    }

    echo '</table>';
}

// Llamada a las funciones para mostrar las tablas
mostrarTablasComunes($connLocal, $connCloud);
mostrarTablasEnNubeNoEnLocal($connLocal, $connCloud);
mostrarTablasEnLocalNoEnNube($connLocal, $connCloud);

// Cerramos las conexiones
$connLocal->close();
$connCloud->close();
