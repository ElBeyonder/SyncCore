<?php

    require_once 'conn.php';


    $tables = [];
    $result = $connLocal->query("SHOW TABLES");
    if ($result === false) {
        die("Error al obtener las tablas locales: " . $connLocal->error);
    }

    while ($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }

    $diferencias = [];
    $tablasSincronizadas = []; // Array para almacenar las tablas que fueron sincronizadas

    foreach ($tables as $tabla) {
        // Verificar si la tabla existe en la base de datos en la nube
        $result = $connCloud->query("SHOW TABLES LIKE '$tabla'");
        if ($result === false || $result->num_rows === 0) {
            continue; // Si la tabla no existe en la nube, saltamos al siguiente
        }

        // Obtener la estructura completa de la tabla local
        $result = $connLocal->query("SHOW COLUMNS FROM $tabla");
        if ($result === false) {
            die("Error al obtener las columnas de la tabla $tabla en la base local: " . $connLocal->error);
        }

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
        if ($result === false) {
            die("Error al obtener las columnas de la tabla $tabla en la base en la nube: " . $connCloud->error);
        }

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
                        $updateSQL = "UPDATE $tabla SET $column = 0 WHERE $column IS NULL OR $column = ''";
                        $connCloud->query($updateSQL);
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

        // Almacenar la tabla que fue sincronizada
        $tablasSincronizadas[] = $tabla;
    }

    // Mostrar las diferencias detectadas
    if (!empty($diferencias)) {
        echo "<table border='1'>
                <tr><th>Acción</th><th>Tabla</th><th>Columna</th><th>Tipo</th></tr>";
        foreach ($diferencias as $mensaje) {
            echo "<tr><td>$mensaje</td><td></td><td></td><td></td></tr>";
        }
        echo "</table>";
    } else {
        echo "No hay diferencias en las columnas entre las bases de datos local y en la nube.";
    }

    // Mostrar las tablas sincronizadas
    if (!empty($tablasSincronizadas)) {
        echo "<h3>Tablas sincronizadas:</h3>";
        echo "<ul>";
        foreach ($tablasSincronizadas as $tabla) {
            echo "<li>$tabla</li>";
        }
        echo "</ul>";
    } else {
        echo "No se sincronizaron tablas.";
    }

    // Cerrar conexiones
    $connLocal->close();
    $connCloud->close();
