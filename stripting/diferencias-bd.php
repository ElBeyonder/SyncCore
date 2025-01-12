<?php

require_once 'conn.php';

// Inicializar array para diferencias
$diferencias = [];

// Obtener todas las tablas de la base de datos local
$tablesLocal = [];
$resultLocal = $connLocal->query("SHOW TABLES");
while ($row = $resultLocal->fetch_array()) {
    $tablesLocal[] = $row[0];
}

// Obtener todas las tablas de la base de datos en la nube
$tablesCloud = [];
$resultCloud = $connCloud->query("SHOW TABLES");
while ($row = $resultCloud->fetch_array()) {
    $tablesCloud[] = $row[0];
}

// Verificar diferencias en tablas
foreach ($tablesLocal as $tabla) {
    if (!in_array($tabla, $tablesCloud)) {
        $diferencias[] = [
            'base_datos' => 'Nube',
            'tabla' => $tabla,
            'columna' => 'Tabla no existe en la nube'
        ];
    } else {
        // Comparar columnas de tablas que existen en ambas bases de datos
        $columnsLocal = [];
        $columnsCloud = [];

        $resultLocalColumns = $connLocal->query("SHOW COLUMNS FROM $tabla");
        while ($row = $resultLocalColumns->fetch_assoc()) {
            $columnsLocal[$row['Field']] = [
                'Type' => $row['Type'],
                'Null' => $row['Null'],
                'Default' => $row['Default'],
                'Extra' => $row['Extra']
            ];
        }

        $resultCloudColumns = $connCloud->query("SHOW COLUMNS FROM $tabla");
        while ($row = $resultCloudColumns->fetch_assoc()) {
            $columnsCloud[$row['Field']] = [
                'Type' => $row['Type'],
                'Null' => $row['Null'],
                'Default' => $row['Default'],
                'Extra' => $row['Extra']
            ];
        }

        // Comparar columnas locales con las de la nube
        foreach ($columnsLocal as $column => $details) {
            if (!isset($columnsCloud[$column])) {
                $diferencias[] = [
                    'base_datos' => 'Nube',
                    'tabla' => $tabla,
                    'columna' => "Columna no existe: $column"
                ];
            } else {
                $cloudDetails = $columnsCloud[$column];
                if (
                    strcasecmp($cloudDetails['Type'], $details['Type']) !== 0 ||
                    $cloudDetails['Null'] !== $details['Null'] ||
                    $cloudDetails['Default'] !== $details['Default'] ||
                    $cloudDetails['Extra'] !== $details['Extra']
                ) {
                    $diferencias[] = [
                        'base_datos' => 'Nube',
                        'tabla' => $tabla,
                        'columna' => "Diferencia en columna $column (Local: {$details['Type']}, Nube: {$cloudDetails['Type']})"
                    ];
                }
            }
        }

        // Verificar si hay columnas en la nube que no están en la local
        foreach ($columnsCloud as $column => $details) {
            if (!isset($columnsLocal[$column])) {
                $diferencias[] = [
                    'base_datos' => 'Local',
                    'tabla' => $tabla,
                    'columna' => "Columna no existe: $column"
                ];
            }
        }
    }
}

// Verificar si hay tablas en la nube que no están en la local
foreach ($tablesCloud as $tabla) {
    if (!in_array($tabla, $tablesLocal)) {
        $diferencias[] = [
            'base_datos' => 'Local',
            'tabla' => $tabla,
            'columna' => 'Tabla no existe en la local'
        ];
    }
}

// Generar tabla HTML con las diferencias
echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr><th>Base de Datos</th><th>Tabla</th><th>Columna</th></tr>";

if (!empty($diferencias)) {
    foreach ($diferencias as $diff) {
        echo "<tr>";
        echo "<td>{$diff['base_datos']}</td>";
        echo "<td>{$diff['tabla']}</td>";
        echo "<td>{$diff['columna']}</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='3'>No se encontraron diferencias entre las bases de datos</td></tr>";
}

echo "</table>";

// Cerrar conexiones
$connLocal->close();
$connCloud->close();

