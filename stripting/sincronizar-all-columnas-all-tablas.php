<?php

require_once 'conn.php';

// Obtener todas las tablas en la base de datos local
$tables = [];
$result = $connLocal->query("SHOW TABLES");
while ($row = $result->fetch_array()) {
    $tables[] = $row[0];
}

$diferencias = [];

foreach ($tables as $tabla) {
    // Obtener la estructura completa de la tabla local
    $result = $connLocal->query("SHOW COLUMNS FROM $tabla");
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[$row['Field']] = [
            'Type' => $row['Type'],
            'Null' => $row['Null'],
            'Default' => $row['Default'],
            'Extra' => $row['Extra']
        ];
    }

    // Obtener la estructura completa de la tabla en la nube
    $result = $connCloud->query("SHOW COLUMNS FROM $tabla");
    $cloudColumns = [];
    while ($row = $result->fetch_assoc()) {
        $cloudColumns[$row['Field']] = [
            'Type' => $row['Type'],
            'Null' => $row['Null'],
            'Default' => $row['Default'],
            'Extra' => $row['Extra']
        ];
    }

    // Añadir o modificar columnas en la tabla en la nube para que coincidan con la tabla local
    $ordenColumnas = array_keys($columns);
    $prevColumn = null;

    foreach ($ordenColumnas as $column) {
        $details = $columns[$column];

        if (!isset($cloudColumns[$column])) {
            // Añadir la columna si no existe
            $alterSQL = "ALTER TABLE $tabla ADD $column {$details['Type']}";
            if ($prevColumn !== null) {
                $alterSQL .= " AFTER $prevColumn";
            }

            try {
                if ($connCloud->query($alterSQL)) {
                    $diferencias[] = "Columna añadida en la tabla $tabla: $column con tipo: {$details['Type']}";
                } else {
                    throw new Exception($connCloud->error);
                }
            } catch (Exception $e) {
                echo "Error añadiendo columna $column en la tabla $tabla: " . $e->getMessage() . "<br>";
            }
        } else {
            // Verificar si hay diferencias en el tipo o propiedades
            $cloudDetails = $cloudColumns[$column];
            $isDifferent = (
                strcasecmp($cloudDetails['Type'], $details['Type']) !== 0 ||
                $cloudDetails['Null'] !== $details['Null'] ||
                $cloudDetails['Default'] !== $details['Default'] ||
                $cloudDetails['Extra'] !== $details['Extra']
            );

            if ($isDifferent) {
                // Validar y transformar valores si es necesario antes de modificar
                if (strpos(strtolower($details['Type']), 'int') !== false) {
                    // Si la columna es numérica, actualizar valores incompatibles a 0
                    $connCloud->query("UPDATE $tabla SET $column = 0 WHERE $column IS NULL OR $column = ''");
                }

                $alterSQL = "ALTER TABLE $tabla MODIFY $column {$details['Type']}";
                if ($details['Null'] === 'NO') {
                    $alterSQL .= " NOT NULL";
                }
                if ($details['Default'] !== null) {
                    $defaultValue = is_numeric($details['Default']) ? $details['Default'] : "'{$details['Default']}'";
                    $alterSQL .= " DEFAULT $defaultValue";
                }
                if ($details['Extra']) {
                    $alterSQL .= " " . $details['Extra'];
                }

                try {
                    if ($connCloud->query($alterSQL)) {
                        $diferencias[] = "Columna modificada en la tabla $tabla: $column con tipo: {$details['Type']}";
                    } else {
                        throw new Exception($connCloud->error);
                    }
                } catch (Exception $e) {
                    echo "Error modificando columna $column en la tabla $tabla: " . $e->getMessage() . "<br>";
                }
            }
        }

        $prevColumn = $column;
    }
}

// Mostrar las diferencias detectadas
if (!empty($diferencias)) {
    foreach ($diferencias as $mensaje) {
        echo $mensaje . "<br>";
    }
} else {
    echo "No hay diferencias en las columnas entre las bases de datos local y en la nube.";
}

// Cerrar conexiones
$connLocal->close();
$connCloud->close();


