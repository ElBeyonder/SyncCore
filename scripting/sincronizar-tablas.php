
<?php
    // Importar las conexiones locales y en la nube desde conn.php
    require_once 'conn.php';

    // Verificar si la conexión fue exitosa
    if ($connLocal->connect_error || $connCloud->connect_error) {
        die("Error en la conexión: " . $connLocal->connect_error . " / " . $connCloud->connect_error);
    }

    // Función para obtener la lista de tablas de una base de datos
    function obtenerTablas($conn)
    {
        $tablas = [];
        $result = $conn->query("SHOW TABLES");
        while ($row = $result->fetch_array()) {
            $tablas[] = $row[0];
        }
        return $tablas;
    }

    // Función para obtener la estructura de una tabla (columnas y tipos)
    function obtenerEstructuraTabla($conn, $tabla)
    {
        $estructura = [];
        $result = $conn->query("SHOW COLUMNS FROM $tabla");
        while ($row = $result->fetch_assoc()) {
            $estructura[$row['Field']] = $row['Type'];
        }
        return $estructura;
    }

    // Obtener tablas de la base de datos local y en la nube
    $tablasLocal = obtenerTablas($connLocal);
    $tablasCloud = obtenerTablas($connCloud);

    // Sincronizar tablas
    foreach ($tablasLocal as $tabla) {
        // Si la tabla no existe en la nube, crearla
        if (!in_array($tabla, $tablasCloud)) {
            // Obtener la estructura de la tabla local
            $result = $connLocal->query("SHOW CREATE TABLE $tabla");
            $row = $result->fetch_assoc();
            $createTableSQL = $row['Create Table'];

            // Crear la tabla en la nube
            if ($connCloud->query($createTableSQL)) {
                echo "Tabla creada en la nube: $tabla<br>";
            } else {
                echo "Error creando la tabla en la nube: " . $connCloud->error . "<br>";
            }
        } else {
            // La tabla existe en ambas bases de datos, comparar estructura
            $estructuraLocal = obtenerEstructuraTabla($connLocal, $tabla);
            $estructuraCloud = obtenerEstructuraTabla($connCloud, $tabla);

            // Sincronizar columnas de la tabla
            foreach ($estructuraLocal as $columna => $tipo) {
                if (!isset($estructuraCloud[$columna])) {
                    // Añadir columna que no existe en la tabla en la nube
                    $columnAnterior = array_key_first($estructuraLocal); // Usar la primera columna como referencia para el AFTER
                    $alterSQL = "ALTER TABLE $tabla ADD $columna $tipo AFTER $columnAnterior";
                    if ($connCloud->query($alterSQL)) {
                        echo "Columna añadida en la nube: $columna en la tabla $tabla<br>";
                    } else {
                        echo "Error añadiendo columna en la nube: " . $connCloud->error . "<br>";
                    }
                } elseif ($estructuraCloud[$columna] !== $tipo) {
                    // Modificar columna si tiene un tipo diferente
                    $alterSQL = "ALTER TABLE $tabla MODIFY $columna $tipo";
                    if ($connCloud->query($alterSQL)) {
                        echo "Columna modificada en la nube: $columna en la tabla $tabla<br>";
                    } else {
                        echo "Error modificando columna en la nube: " . $connCloud->error . "<br>";
                    }
                }
            }
        }
    }

    // Cerrar las conexiones
    $connLocal->close();
    $connCloud->close();

