<?php
require_once 'conn.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // Obtener todas las tablas en la base de datos local
    $tables = [];
    $result = $connLocal->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");

    while ($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }

    $diferencias = [];
    $errores = [];

    foreach ($tables as $tabla) {
        try {
            // Verificar si la tabla existe en la nube
            $existsInCloud = false;
            $result = $connCloud->query("SHOW TABLES LIKE '$tabla'");
            if ($result->num_rows > 0) $existsInCloud = true;

            if (!$existsInCloud) {
                // Crear la tabla en la nube
                $create = $connLocal->query("SHOW CREATE TABLE `$tabla`");
                $createRow = $create->fetch_assoc();
                $connCloud->query($createRow['Create Table']);
                $diferencias[] = "Tabla creada en la nube: $tabla";
                continue; // La nueva tabla ya está sincronizada
            }

            // Obtener columnas locales
            $localColumns = [];
            $result = $connLocal->query("SHOW FULL COLUMNS FROM `$tabla`");
            while ($row = $result->fetch_assoc()) {
                $localColumns[$row['Field']] = $row;
            }

            // Obtener columnas en la nube
            $cloudColumns = [];
            $result = $connCloud->query("SHOW FULL COLUMNS FROM `$tabla`");
            while ($row = $result->fetch_assoc()) {
                $cloudColumns[$row['Field']] = $row;
            }

            // Sincronizar columnas
            $previous = null;
            foreach ($localColumns as $name => $local) {
                $alter = '';
                $cloud = $cloudColumns[$name] ?? null;

                // Preparar definición de columna
                $definition = "`{$name}` {$local['Type']}";
                $definition .= ($local['Null'] == 'NO') ? ' NOT NULL' : ' NULL';

                // Manejar valores por defecto
                if ($local['Default'] !== null) {
                    if (strtoupper($local['Default']) === 'CURRENT_TIMESTAMP') {
                        $definition .= " DEFAULT CURRENT_TIMESTAMP";
                    } else {
                        $default = $connCloud->real_escape_string($local['Default']);
                        $definition .= is_numeric($local['Default'])
                            ? " DEFAULT {$default}"
                            : " DEFAULT '{$default}'";
                    }
                } elseif ($local['Null'] === 'YES') {
                    $definition .= " DEFAULT NULL";
                }

                // Manejar extras (auto_increment, etc)
                $definition .= " {$local['Extra']}";

                // Manejar comentarios
                if (!empty($local['Comment'])) {
                    $comment = $connCloud->real_escape_string($local['Comment']);
                    $definition .= " COMMENT '{$comment}'";
                }

                // Posicionamiento
                if ($previous !== null) {
                    $definition .= " AFTER `{$previous}`";
                }

                if (!$cloud) {
                    // Columna no existe - AGREGAR
                    $alter = "ALTER TABLE `$tabla` ADD COLUMN {$definition}";
                    $diferencias[] = "Columna añadida en $tabla: {$name}";
                } else {
                    // Verificar diferencias
                    $diff = [];
                    foreach (['Type', 'Null', 'Default', 'Extra', 'Comment'] as $prop) {
                        if ($local[$prop] != $cloud[$prop]) {
                            $diff[] = $prop;
                        }
                    }

                    // Verificar posición
                    $currentPos = array_search($name, array_keys($cloudColumns));
                    $desiredPos = array_search($name, array_keys($localColumns));
                    if ($currentPos != $desiredPos) $diff[] = 'Position';

                    if (!empty($diff)) {
                        // Conversión de datos para cambios de tipo
                        if (in_array('Type', $diff)) {
                            $this->handleDataConversion($connCloud, $tabla, $name, $local['Type']);
                        }

                        // MODIFICAR columna
                        $alter = "ALTER TABLE `$tabla` MODIFY COLUMN {$definition}";
                        $diferencias[] = "Columna modificada en $tabla: {$name} (" . implode(', ', $diff) . ")";
                    }
                }

                if ($alter) {
                    $connCloud->query($alter);
                }

                $previous = $name;
            }

            // Eliminar columnas obsoletas (OPCIONAL - descomentar si se necesita)
            /*
            $columnsToRemove = array_diff(array_keys($cloudColumns), array_keys($localColumns));
            foreach ($columnsToRemove as $col) {
                $connCloud->query("ALTER TABLE `$tabla` DROP COLUMN `$col`");
                $diferencias[] = "Columna eliminada en $tabla: $col";
            }
            */

        } catch (mysqli_sql_exception $e) {
            $errores[] = "Error en tabla $tabla: " . $e->getMessage();
            continue;
        }
    }

    // Mostrar resultados
    echo "<h3>Cambios realizados:</h3>";
    foreach ($diferencias as $msg) {
        echo "$msg<br>";
    }

    echo "<h3>Errores:</h3>";
    foreach ($errores as $error) {
        echo "$error<br>";
    }

} catch (mysqli_sql_exception $e) {
    die("Error crítico: " . $e->getMessage());
} finally {
    $connLocal->close();
    $connCloud->close();
}

/**
 * Maneja la conversión de datos segura para cambios de tipo de columna
 */
function handleDataConversion($conn, $table, $column, $newType) {
    $type = strtolower($newType);

    if (strpos($type, 'int') !== false) {
        $conn->query("UPDATE `$table` SET `$column` = 0 WHERE `$column` IS NULL OR `$column` = ''");
    } elseif (strpos($type, 'date') !== false || strpos($type, 'time') !== false) {
        $conn->query("UPDATE `$table` SET `$column` = NULL WHERE `$column` = ''");
    } elseif (strpos($type, 'decimal') !== false || strpos($type, 'float') !== false) {
        $conn->query("UPDATE `$table` SET `$column` = 0 WHERE `$column` IS NULL OR `$column` = ''");
    }
}
?>