
<?php

    require_once 'conn.php'; // Incluye los archivos de conexión a ambas bases de datos

    $tabla = 'empresa'; // Nombre de la tabla a sincronizar
    // Convertir los datos en arrays asociativos basados en la clave primaria
    $clavePrimaria = 'id'; // Cambia esto por la clave primaria de tu tabla

    // Función para obtener los datos de una tabla
    function obtenerDatosTabla($conn, $tabla){
        $query = "SELECT * FROM $tabla";
        $result = $conn->query($query);
        $datos = [];
        while ($row = $result->fetch_assoc()) {
            $datos[] = $row;
        }
        return $datos;
    }

    // Obtener los datos de la tabla local
    $datosLocal = obtenerDatosTabla($connLocal, $tabla);

    // Obtener los datos de la tabla en la nube
    $datosCloud = obtenerDatosTabla($connCloud, $tabla);

    $datosLocalPorId = [];
    foreach ($datosLocal as $fila) {
        $datosLocalPorId[$fila[$clavePrimaria]] = $fila;
    }

    $datosCloudPorId = [];
    foreach ($datosCloud as $fila) {
        $datosCloudPorId[$fila[$clavePrimaria]] = $fila;
    }

    // Sincronizar datos: Insertar o actualizar registros
    foreach ($datosLocalPorId as $id => $filaLocal) {
        if (!isset($datosCloudPorId[$id])) {
            // Si el registro no existe en la nube, insertarlo
            $columnas = implode(", ", array_keys($filaLocal));
            $valores = implode(", ", array_map(function ($valor) use ($connCloud) {
                return "'" . $connCloud->real_escape_string($valor) . "'";
            }, $filaLocal));

            $insertSQL = "INSERT INTO $tabla ($columnas) VALUES ($valores)";
            if ($connCloud->query($insertSQL)) {
                echo "Registro $id insertado en la nube.<br>";
            } else {
                echo "Error insertando registro $id: " . $connCloud->error . "<br>";
            }
        } else {
            // Si el registro existe, verificar si hay diferencias y actualizarlas
            $updates = [];
            foreach ($filaLocal as $columna => $valorLocal) {
                $valorCloud = $datosCloudPorId[$id][$columna];
                if ($valorLocal != $valorCloud) {
                    $updates[] = "$columna = '" . $connCloud->real_escape_string($valorLocal) . "'";
                }
            }

            if (!empty($updates)) {
                $updateSQL = "UPDATE $tabla SET " . implode(", ", $updates) . " WHERE $clavePrimaria = $id";
                if ($connCloud->query($updateSQL)) {
                    echo "Registro $id actualizado en la nube.<br>";
                } else {
                    echo "Error actualizando registro $id: " . $connCloud->error . "<br>";
                }
            }
        }
    }

    // Eliminar registros en la nube que no existen en la base de datos local
    /*foreach ($datosCloudPorId as $id => $filaCloud) {
        if (!isset($datosLocalPorId[$id])) {
            $deleteSQL = "DELETE FROM $tabla WHERE $clavePrimaria = $id";
            if ($connCloud->query($deleteSQL)) {
                echo "Registro $id eliminado de la nube.<br>";
            } else {
                echo "Error eliminando registro $id: " . $connCloud->error . "<br>";
            }
        }
    }*/

    // Cerrar conexiones
    $connLocal->close();
    $connCloud->close();

    echo "Sincronización completada.";


