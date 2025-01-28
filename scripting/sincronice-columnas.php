
<?php

    require_once 'conn.php';

    // Obtener la estructura completa de la tabla local
    $tabla = 'venta';
    $result = $connLocal->query("SHOW COLUMNS FROM $tabla");
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        // Almacenar el nombre de la columna y su estructura completa
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

    // Array para almacenar las diferencias
    $diferencias = [];

    // Eliminar columnas que no están en la tabla local
    foreach ($cloudColumns as $column => $details) {
        if (!isset($columns[$column])) {
            // Si la columna no está en la tabla local, eliminarla en la nube
            $alterSQL = "ALTER TABLE $tabla DROP COLUMN $column";
            if ($connCloud->query($alterSQL)) {
                $diferencias[$column] = "Columna eliminada: $column";
            } else {
                echo "Error eliminando columna $column: " . $connCloud->error . "<br>";
            }
        }
    }

    // Añadir o modificar columnas en la tabla en la nube para que coincidan con la tabla local
    $ordenColumnas = array_keys($columns); // Orden de las columnas locales
    $prevColumn = null; // Variable para rastrear la columna anterior

    foreach ($ordenColumnas as $column) {
        $details = $columns[$column];

        if (!isset($cloudColumns[$column])) {
            // Si la columna no está en la tabla en la nube, añadirla en la posición correcta
            $alterSQL = "ALTER TABLE $tabla ADD $column {$details['Type']}";

            // Si hay una columna anterior, agregar "AFTER"
            if ($prevColumn !== null) {
                $alterSQL .= " AFTER $prevColumn";
            }

            if ($connCloud->query($alterSQL)) {
                $diferencias[$column] = "Columna añadida: $column con tipo: {$details['Type']}";
            } else {
                echo "Error añadiendo columna $column: " . $connCloud->error . "<br>";
            }
        } else {
            // Si la columna existe en ambas tablas, comparar todas las propiedades
            $cloudDetails = $cloudColumns[$column];

            $isDifferent = (
                strcasecmp($cloudDetails['Type'], $details['Type']) !== 0 ||
                $cloudDetails['Null'] !== $details['Null'] ||
                $cloudDetails['Default'] !== $details['Default'] ||
                $cloudDetails['Extra'] !== $details['Extra']
            );

            if ($isDifferent) {
                // Modificar la columna para que coincida
                $alterSQL = "ALTER TABLE $tabla MODIFY $column {$details['Type']}";

                // Manejar atributos adicionales como NULL, DEFAULT, etc.
                if ($details['Null'] === 'NO') {
                    $alterSQL .= " NOT NULL";
                }
                if ($details['Default'] !== null) {
                    $alterSQL .= " DEFAULT '{$details['Default']}'";
                }
                if ($details['Extra']) {
                    $alterSQL .= " " . $details['Extra'];
                }

                if ($connCloud->query($alterSQL)) {
                    $diferencias[$column] = "Columna modificada: $column con tipo: {$details['Type']}";
                } else {
                    echo "Error modificando columna $column: " . $connCloud->error . "<br>";
                }
            }
        }

        // Actualizar la columna anterior para la próxima iteración
        $prevColumn = $column;
    }

    // Mostrar las diferencias detectadas
    if (!empty($diferencias)) {
        foreach ($diferencias as $column => $mensaje) {
            echo $mensaje . "<br>";
        }
    } else {
        echo "No hay diferencias en las columnas entre la base de datos local y la nube.";
    }

    // Cerrar conexiones
    $connLocal->close();
    $connCloud->close();

