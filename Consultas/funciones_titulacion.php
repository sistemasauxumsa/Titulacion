<?php
require_once 'Consultas/db_connection.php';

/**
 * Funcionalidades para el sistema de titulación
 */

// 1. Obtener modalidades por grado académico
function obtenerModalidadesPorGrado($id_grado_academico) {
    $conn = getDatabaseConnection();
    
    $sql = "SELECT id, nombre FROM titmodalidades WHERE id_grado_academico = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_grado_academico);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $modalidades = [];
    
    while ($row = $result->fetch_assoc()) {
        $modalidades[] = $row;
    }
    
    $stmt->close();
    $conn->close();
    
    return $modalidades;
}

// 1b. Obtener modalidades por carrera específica
function obtenerModalidadesPorCarrera($id_carrera) {
    error_log("=== INICIO obtenerModalidadesPorCarrera ===");
    error_log("ID Carrera recibido: $id_carrera");
    
    $conn = getDatabaseConnection();
    
    if (!$conn) {
        error_log("ERROR: No se pudo conectar a la base de datos");
        return [];
    }
    
    // Primero obtener el nivel de la carrera
    $sql_nivel = "SELECT niv FROM carreras WHERE id = ?";
    error_log("SQL Nivel: $sql_nivel");
    
    $stmt_nivel = $conn->prepare($sql_nivel);
    if (!$stmt_nivel) {
        error_log("ERROR: No se pudo preparar SQL nivel: " . $conn->error);
        $conn->close();
        return [];
    }
    
    $stmt_nivel->bind_param("i", $id_carrera);
    $stmt_nivel->execute();
    $result_nivel = $stmt_nivel->get_result();
    
    if ($result_nivel->num_rows === 0) {
        error_log("No se encontró carrera con ID: $id_carrera");
        $stmt_nivel->close();
        $conn->close();
        return [];
    }
    
    $nivel = $result_nivel->fetch_assoc();
    $niv = $nivel['niv'];
    $stmt_nivel->close();
    
    error_log("Carrera ID: $id_carrera, Nivel: $niv");
    
    // Mapear nivel a grado académico
    $id_grado_academico = null;
    if ($niv == 5) {
        $id_grado_academico = 1; // Licenciatura
    } else if ($niv == 6) {
        $id_grado_academico = 3; // Maestría
    }
    
    if (!$id_grado_academico) {
        error_log("Nivel no mapeado: $niv");
        $conn->close();
        return [];
    }
    
    error_log("Grado académico mapeado: $id_grado_academico");
    
    // Ahora obtener modalidades por grado académico
    $sql = "SELECT id, nombre FROM titmodalidades WHERE id_grado_academico = ?";
    error_log("SQL Modalidades: $sql");
    error_log("Parámetro: $id_grado_academico");
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("ERROR: No se pudo preparar SQL modalidades: " . $conn->error);
        $conn->close();
        return [];
    }
    
    $stmt->bind_param("i", $id_grado_academico);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $modalidades = [];
    
    error_log("Modalidades encontradas: " . $result->num_rows);
    
    while ($row = $result->fetch_assoc()) {
        $modalidades[] = $row;
        error_log("Modalidad: " . $row['id'] . " - " . $row['nombre']);
    }
    
    $stmt->close();
    $conn->close();
    
    error_log("=== FIN obtenerModalidadesPorCarrera ===");
    error_log("Modalidades a devolver: " . json_encode($modalidades));
    
    return $modalidades;
}

// 2. Obtener documentos por modalidad
function obtenerDocumentosPorModalidad($id_modalidad) {
    $conn = getDatabaseConnection();
    
    $sql = "SELECT td.id, tdoc.nom as nombre 
            FROM titdocumentos td
            JOIN titdocumentacion tdoc ON td.iddoc = tdoc.id
            WHERE td.id_modalidad = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_modalidad);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $documentos = [];
    
    while ($row = $result->fetch_assoc()) {
        $documentos[] = $row;
    }
    
    $stmt->close();
    $conn->close();
    
    return $documentos;
}

// 3. Generar folio único
function generarFolio() {
    $conn = getDatabaseConnection();
    
    $anio_actual = date('Y');
    
    $sql = "SELECT COALESCE(MAX(CAST(SUBSTRING(folio, 6) AS UNSIGNED)), 0) + 1 as consecutivo
            FROM titfolios_estudiantes 
            WHERE folio LIKE ?";
    
    $patron = $anio_actual . '-%';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $patron);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $consecutivo = $row['consecutivo'];
    
    $folio = $anio_actual . '-' . str_pad($consecutivo, 5, '0', STR_PAD_LEFT);
    
    $stmt->close();
    $conn->close();
    
    return $folio;
}

// 4. Crear nuevo folio para estudiante
function crearFolioEstudiante($curp, $id_grado_academico, $id_carrera, $id_modalidad_tit) {
    $conn = getDatabaseConnection();
    
    // Generar folio directamente aquí para usar la misma conexión
    $anio_actual = date('Y');
    $sql = "SELECT COALESCE(MAX(CAST(SUBSTRING(folio, 6) AS UNSIGNED)), 0) + 1 as consecutivo
            FROM titfolios_estudiantes 
            WHERE folio LIKE ?";
    $patron = $anio_actual . '-%';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $patron);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $consecutivo = $row['consecutivo'];
    $folio = $anio_actual . '-' . str_pad($consecutivo, 5, '0', STR_PAD_LEFT);
    $stmt->close();
    
    // Ahora insertar el folio
    $sql = "INSERT INTO titfolios_estudiantes (folio, curp, id_grado_academico, id_carrera, id_modalidad_tit) 
            VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssiii", $folio, $curp, $id_grado_academico, $id_carrera, $id_modalidad_tit);
    
    if ($stmt->execute()) {
        $id_folio = $conn->insert_id;
        $stmt->close();
        $conn->close();
        return ['success' => true, 'folio' => $folio, 'id_folio' => $id_folio];
    } else {
        $error = $stmt->error;
        $stmt->close();
        $conn->close();
        return ['success' => false, 'error' => $error];
    }
}

// 5. Subir documento (actualizar si ya existe)
function subirDocumento($id_folio, $id_doc_tit, $ruta_archivo, $nombre_archivo, $tamano_archivo, $tipo_archivo, $archivo_base64) {
    $conn = getDatabaseConnection();
    
    try {
        // Verificar si el documento ya existe
        $sql_check = "SELECT id, ruta_archivo FROM titdocumentos_folio WHERE id_folio = ? AND id_doc_tit = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("ii", $id_folio, $id_doc_tit);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows > 0) {
            // El documento ya existe, actualizarlo
            $row = $result_check->fetch_assoc();
            $ruta_anterior = $row['ruta_archivo'];
            $id_documento_folio = $row['id'];
            
            // Eliminar archivo anterior si existe
            $ruta_anterior_completa = __DIR__ . '/../' . $ruta_anterior;
            if (file_exists($ruta_anterior_completa)) {
                unlink($ruta_anterior_completa);
            }
            
            // Crear directorio si no existe
            $directorio = dirname($ruta_archivo);
            $ruta_completa = __DIR__ . '/../' . $directorio;
            
            if (!is_dir($ruta_completa)) {
                mkdir($ruta_completa, 0755, true);
            }
            
            // Guardar nuevo archivo
            if ($archivo_base64) {
                if (strpos($archivo_base64, 'data:') === 0) {
                    $partes = explode(',', $archivo_base64);
                    $base64 = end($partes);
                } else {
                    $base64 = $archivo_base64;
                }
                
                $contenido = base64_decode($base64);
                if ($contenido === false) {
                    $stmt_check->close();
                    $conn->close();
                    return ['success' => false, 'error' => 'Error al decodificar el archivo base64'];
                }
                
                $ruta_completa_archivo = __DIR__ . '/../' . $ruta_archivo;
                if (file_put_contents($ruta_completa_archivo, $contenido) === false) {
                    $stmt_check->close();
                    $conn->close();
                    return ['success' => false, 'error' => 'Error al guardar el archivo'];
                }
            }
            
            // Actualizar registro en base de datos
            $sql_update = "UPDATE titdocumentos_folio 
                          SET ruta_archivo = ?, estado = 'pendiente', comentarios = NULL, 
                              fecha_revision = NULL, revisado_por = NULL, fecha_subida = NOW()
                          WHERE id = ?";
            
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("si", $ruta_archivo, $id_documento_folio);
            
            if ($stmt_update->execute()) {
                $stmt_check->close();
                $stmt_update->close();
                $conn->close();
                return ['success' => true, 'message' => 'Documento actualizado correctamente'];
            } else {
                $stmt_check->close();
                $stmt_update->close();
                $conn->close();
                return ['success' => false, 'error' => 'Error al actualizar el documento'];
            }
        } else {
            // Documento nuevo, insertarlo
            $stmt_check->close();
            
            // Crear directorio si no existe
            $directorio = dirname($ruta_archivo);
            $ruta_completa = __DIR__ . '/../' . $directorio;
            
            if (!is_dir($ruta_completa)) {
                mkdir($ruta_completa, 0755, true);
            }
            
            // Guardar archivo físicamente
            if ($archivo_base64) {
                if (strpos($archivo_base64, 'data:') === 0) {
                    $partes = explode(',', $archivo_base64);
                    $base64 = end($partes);
                } else {
                    $base64 = $archivo_base64;
                }
                
                $contenido = base64_decode($base64);
                if ($contenido === false) {
                    $conn->close();
                    return ['success' => false, 'error' => 'Error al decodificar el archivo base64'];
                }
                
                $ruta_completa_archivo = __DIR__ . '/../' . $ruta_archivo;
                if (file_put_contents($ruta_completa_archivo, $contenido) === false) {
                    $conn->close();
                    return ['success' => false, 'error' => 'Error al guardar el archivo'];
                }
            }
            
            // Insertar en base de datos
            $sql = "INSERT INTO titdocumentos_folio (id_folio, id_doc_tit, ruta_archivo) 
                    VALUES (?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iis", $id_folio, $id_doc_tit, $ruta_archivo);
            
            if ($stmt->execute()) {
                $stmt->close();
                $conn->close();
                return ['success' => true, 'message' => 'Documento subido correctamente'];
            } else {
                $stmt->close();
                $conn->close();
                return ['success' => false, 'error' => 'Error al guardar el documento'];
            }
        }
        
    } catch (Exception $e) {
        $conn->close();
        return ['success' => false, 'error' => 'Excepción: ' . $e->getMessage()];
    }
}

// 6. Actualizar estado de documento
function actualizarEstadoDocumento($id_documento, $estado, $comentarios = null, $revisado_por = null) {
    $conn = getDatabaseConnection();
    
    $sql = "UPDATE titdocumentos_folio 
            SET estado = ?, comentarios = ?, revisado_por = ?, fecha_revision = NOW() 
            WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $estado, $comentarios, $revisado_por, $id_documento);
    
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        return ['success' => true];
    } else {
        $error = $stmt->error;
        $stmt->close();
        $conn->close();
        return ['success' => false, 'error' => $error];
    }
}

// 7. Consultar estado completo por folio
function consultarEstadoPorFolio($folio) {
    $conn = getDatabaseConnection();
    
    $sql = "SELECT 
                fe.folio,
                fe.curp,
                fe.estado_proceso as estado_general,
                td.nombre as tipo_documento,
                tdf.ruta_archivo,
                tdf.estado as estado_documento,
                tdf.comentarios,
                tdf.fecha_subida,
                tdf.fecha_revision,
                tdf.revisado_por
            FROM titfolios_estudiantes fe
            LEFT JOIN titdocumentos_folio tdf ON fe.id = tdf.id_folio
            LEFT JOIN titdocumentos td ON tdf.id_doc_tit = td.id
            WHERE fe.folio = ?
            ORDER BY tdf.fecha_subida";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $folio);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $documentos = [];
    
    while ($row = $result->fetch_assoc()) {
        $documentos[] = $row;
    }
    
    $stmt->close();
    $conn->close();
    
    return $documentos;
}

// 8. Obtener folio por CURP
function obtenerFolioPorCurp($curp) {
    $conn = getDatabaseConnection();
    
    $sql = "SELECT id, folio, estado_proceso FROM titfolios_estudiantes WHERE curp = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $curp);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $folio = $result->fetch_assoc();
    
    $stmt->close();
    $conn->close();
    
    return $folio;
}

// 9. Actualizar estado general del proceso
function actualizarEstadoProceso($id_folio, $estado) {
    $conn = getDatabaseConnection();
    
    $sql = "UPDATE titfolios_estudiantes SET estado_proceso = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $estado, $id_folio);
    
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        return ['success' => true];
    } else {
        $error = $stmt->error;
        $stmt->close();
        $conn->close();
        return ['success' => false, 'error' => $error];
    }
}

// 10. Obtener carreras del estudiante por CURP
function obtenerCarrerasEstudiante($curp) {
    $conn = getDatabaseConnection();
    
    $sql = "SELECT DISTINCT c.id, c.nombre, ga.id as id_grado, ga.nombre as grado_nombre
            FROM alumnos a
            JOIN inscripciones i ON a.id = i.id_alumno
            JOIN carreras c ON i.id_carrera = c.id
            JOIN grados_academicos ga ON c.id_grado_academico = ga.id
            WHERE a.curp = ? AND i.estado = 'activo'";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $curp);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $carreras = [];
    
    while ($row = $result->fetch_assoc()) {
        $carreras[] = $row;
    }
    
    $stmt->close();
    $conn->close();
    
    return $carreras;
}

// 11. Verificar documentos ya subidos por folio
function verificarDocumentosSubidos($id_folio) {
    $conn = getDatabaseConnection();
    
    $sql = "SELECT td.id, td.nombre, tdf.id as id_documento_subido, 
                   tdf.estado, tdf.ruta_archivo, tdf.fecha_subida
            FROM titdocumentos td
            JOIN titdocumentos td_mod ON td.id = td_mod.iddoc
            LEFT JOIN titdocumentos_folio tdf ON td_mod.id = tdf.id_doc_tit AND tdf.id_folio = ?
            WHERE td_mod.id_modalidad = (SELECT id_modalidad_tit FROM titfolios_estudiantes WHERE id = ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id_folio, $id_folio);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $documentos = [];
    
    while ($row = $result->fetch_assoc()) {
        $documentos[] = $row;
    }
    
    $stmt->close();
    $conn->close();
    
    return $documentos;
}

// 12. Obtener documentos requeridos por modalidad (solo los faltantes)
function obtenerDocumentosRequeridos($id_folio) {
    $conn = getDatabaseConnection();
    
    // Primero obtener la modalidad del folio
    $sql_modalidad = "SELECT id_modalidad_tit FROM titfolios_estudiantes WHERE id = ?";
    $stmt = $conn->prepare($sql_modalidad);
    $stmt->bind_param("i", $id_folio);
    $stmt->execute();
    $result = $stmt->get_result();
    $modalidad = $result->fetch_assoc();
    $id_modalidad = $modalidad['id_modalidad_tit'];
    $stmt->close();
    
    // Obtener todos los documentos de la modalidad
    $sql_docs = "SELECT td.id, td.nombre 
                 FROM titdocumentos td
                 JOIN titdocumentacion tdoc ON td.iddoc = tdoc.id
                 WHERE td.id_modalidad = ?";
    $stmt = $conn->prepare($sql_docs);
    $stmt->bind_param("i", $id_modalidad);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $documentos_requeridos = [];
    
    while ($row = $result->fetch_assoc()) {
        // Verificar si ya está subido
        $sql_check = "SELECT id FROM titdocumentos_folio 
                      WHERE id_folio = ? AND id_doc_tit = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("ii", $id_folio, $row['id']);
        $stmt_check->execute();
        $check_result = $stmt_check->get_result();
        
        if ($check_result->num_rows == 0) {
            // No está subido, agregar a la lista de requeridos
            $documentos_requeridos[] = [
                'id' => $row['id'],
                'nombre' => $row['nombre'],
                'subido' => false
            ];
        } else {
            // Ya está subido
            $documentos_requeridos[] = [
                'id' => $row['id'],
                'nombre' => $row['nombre'],
                'subido' => true,
                'id_documento_subido' => $check_result->fetch_assoc()['id']
            ];
        }
        
        $stmt_check->close();
    }
    
    $stmt->close();
    $conn->close();
    
    return $documentos_requeridos;
}

// 13. Obtener estado completo de documentos por folio
function obtenerEstadoCompletoDocumentos($id_folio) {
    $conn = getDatabaseConnection();
    
    $sql = "SELECT 
                td.id,
                td.nombre as nombre_documento,
                tdf.id as id_documento_subido,
                tdf.estado,
                tdf.ruta_archivo,
                tdf.comentarios,
                tdf.fecha_subida,
                tdf.fecha_revision,
                tdf.revisado_por,
                CASE 
                    WHEN tdf.id IS NULL THEN 'pendiente_subida'
                    ELSE tdf.estado
                END as estado_actual
            FROM titdocumentos td
            JOIN titdocumentos td_mod ON td.id = td_mod.iddoc
            LEFT JOIN titdocumentos_folio tdf ON td_mod.id = tdf.id_doc_tit AND tdf.id_folio = ?
            WHERE td_mod.id_modalidad = (SELECT id_modalidad_tit FROM titfolios_estudiantes WHERE id = ?)
            ORDER BY td.nombre";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id_folio, $id_folio);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $documentos = [];
    
    while ($row = $result->fetch_assoc()) {
        $documentos[] = $row;
    }
    
    $stmt->close();
    $conn->close();
    
    return $documentos;
}

// 7. Consultar folio y sus documentos
function consultarFolio($folio) {
    $conn = getDatabaseConnection();
    
    try {
        // Validar formato del folio para prevenir inyección SQL
        if (!preg_match('/^\d{4}-\d{5}$/', $folio)) {
            error_log("Intento de consulta con formato inválido: $folio");
            $conn->close();
            return ['success' => false, 'error' => 'Formato de folio inválido'];
        }
        
        // Log de consulta
        error_log("Consultando folio: $folio desde IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'desconocida'));
        
        // Buscar el folio
        $sql = "SELECT tf.id, tf.folio, tf.curp, tf.estado_proceso as estado_proceso, tf.fecha_creacion,
                       c.nom as nombre_carrera, tm.nombre as nombre_modalidad
                FROM titfolios_estudiantes tf
                LEFT JOIN carreras c ON tf.id_carrera = c.id
                LEFT JOIN titmodalidades tm ON tf.id_modalidad_tit = tm.id
                WHERE tf.folio = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $folio);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            error_log("Folio no encontrado: $folio");
            $stmt->close();
            $conn->close();
            return ['success' => false, 'error' => 'Folio no encontrado'];
        }
        
        $folio_data = $result->fetch_assoc();
        $stmt->close();
        
        // Verificar si el folio está activo (no cancelado o eliminado)
        if (isset($folio_data['estado_proceso']) && 
            in_array(strtolower($folio_data['estado_proceso']), ['cancelado', 'eliminado', 'inactivo'])) {
            error_log("Intento de acceso a folio inactivo: $folio");
            $conn->close();
            return ['success' => false, 'error' => 'El folio no está activo'];
        }
        
        // Buscar documentos del folio
        $sql_docs = "SELECT tdf.id, tdf.id_doc_tit, tdf.ruta_archivo, tdf.estado, tdf.comentarios,
                           tdoc.nom as nombre_documento, tdf.fecha_subida
                    FROM titdocumentos_folio tdf
                    LEFT JOIN titdocumentos td ON tdf.id_doc_tit = td.id
                    LEFT JOIN titdocumentacion tdoc ON td.iddoc = tdoc.id
                    WHERE tdf.id_folio = ?
                    ORDER BY tdf.id_doc_tit";
        
        $stmt_docs = $conn->prepare($sql_docs);
        $stmt_docs->bind_param("i", $folio_data['id']);
        $stmt_docs->execute();
        $result_docs = $stmt_docs->get_result();
        
        $documentos = [];
        while ($doc = $result_docs->fetch_assoc()) {
            $documentos[] = $doc;
        }
        
        $stmt_docs->close();
        $conn->close();
        
        return [
            'success' => true,
            'folio' => $folio_data,
            'documentos' => $documentos
        ];
        
    } catch (Exception $e) {
        $conn->close();
        return ['success' => false, 'error' => 'Excepción: ' . $e->getMessage()];
    }
}

// 8. Obtener documentos de un folio específico
function obtenerDocumentosFolio($id_folio) {
    $conn = getDatabaseConnection();
    
    $sql = "SELECT tdf.id_doc_tit, tdf.estado, tdf.comentarios, tdf.fecha_subida,
                   tdoc.nom as nombre_documento
            FROM titdocumentos_folio tdf
            LEFT JOIN titdocumentos td ON tdf.id_doc_tit = td.id
            LEFT JOIN titdocumentacion tdoc ON td.iddoc = tdoc.id
            WHERE tdf.id_folio = ?
            ORDER BY tdf.id_doc_tit";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_folio);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $documentos = [];
    while ($row = $result->fetch_assoc()) {
        $documentos[] = $row;
    }
    
    $stmt->close();
    $conn->close();
    
    return $documentos;
}

// 9. Subir documento rechazado
function subirDocumentoRechazado($postData, $fileData) {
    try {
        error_log("subirDocumentoRechazado: Iniciando función");
        error_log("postData: " . print_r($postData, true));
        error_log("fileData: " . print_r($fileData, true));
        
        $conn = getDatabaseConnection();
        
        $idDocTit = $postData['id_doc_tit'] ?? null;
        $folio = $postData['folio'] ?? null;
        
        if (!$idDocTit || !$folio) {
            error_log("subirDocumentoRechazado: Faltan datos requeridos");
            return ['success' => false, 'error' => 'Faltan datos requeridos'];
        }
        
        if (!isset($fileData['archivo']) || $fileData['archivo']['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Error al subir el archivo'];
        }
        
        $archivo = $fileData['archivo'];
        
        // Validar que sea PDF
        $fileType = mime_content_type($archivo['tmp_name']);
        error_log("Tipo de archivo detectado: " . $fileType);
        if ($fileType !== 'application/pdf') {
            error_log("Error: El archivo no es PDF, es: " . $fileType);
            return ['success' => false, 'error' => 'El archivo debe estar en formato PDF'];
        }
        
        // Validar tamaño (máximo 5MB)
        error_log("Tamaño del archivo: " . $archivo['size'] . " bytes");
        if ($archivo['size'] > 5 * 1024 * 1024) {
            error_log("Error: El archivo excede el tamaño máximo");
            return ['success' => false, 'error' => 'El archivo no debe superar los 5MB'];
        }
        
        // Obtener información del folio
        $stmt = $conn->prepare("SELECT id FROM titfolios_estudiantes WHERE folio = ?");
        $stmt->bind_param("s", $folio);
        $stmt->execute();
        $result = $stmt->get_result();
        
        error_log("Buscando folio: " . $folio);
        error_log("Resultados encontrados: " . $result->num_rows);
        
        if ($result->num_rows === 0) {
            // Para depuración: mostrar todos los folios existentes
            $stmt_all = $conn->prepare("SELECT folio FROM titfolios_estudiantes LIMIT 10");
            $stmt_all->execute();
            $result_all = $stmt_all->get_result();
            $folios_existentes = [];
            while ($row = $result_all->fetch_assoc()) {
                $folios_existentes[] = $row['folio'];
            }
            error_log("Folios existentes: " . implode(', ', $folios_existentes));
            
            return ['success' => false, 'error' => 'Folio no encontrado: ' . $folio];
        }
        
        $folioData = $result->fetch_assoc();
        $idFolio = $folioData['id'];
        
        // Obtener ruta del archivo anterior para borrarlo
        $stmt = $conn->prepare("SELECT ruta_archivo FROM titdocumentos_folio WHERE id_folio = ? AND id_doc_tit = ?");
        $stmt->bind_param("is", $idFolio, $idDocTit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $docActual = $result->fetch_assoc();
            $rutaAnterior = $docActual['ruta_archivo'];
            
            // Borrar archivo anterior si existe
            if ($rutaAnterior && file_exists($rutaAnterior)) {
                unlink($rutaAnterior);
                error_log("Archivo anterior borrado: " . $rutaAnterior);
            }
        }
        
        // Crear directorio si no existe (usando carpeta única por proceso: curp_carrera_folio)
        // Extraer CURP y carrera del folio para crear carpeta única
        $stmt_info = $conn->prepare("SELECT curp, id_carrera FROM titfolios_estudiantes WHERE folio = ?");
        $stmt_info->bind_param("s", $folio);
        $stmt_info->execute();
        $result_info = $stmt_info->get_result();
        
        if ($result_info->num_rows > 0) {
            $folio_info = $result_info->fetch_assoc();
            $curp = $folio_info['curp'];
            $id_carrera = $folio_info['id_carrera'];
            // Obtener el ID del folio para crear carpeta única
            $stmt_id = $conn->prepare("SELECT id FROM titfolios_estudiantes WHERE folio = ?");
            $stmt_id->bind_param("s", $folio);
            $stmt_id->execute();
            $result_id = $stmt_id->get_result();
            $id_folio_num = $result_id->fetch_assoc()['id'];
            
            // Usar carpeta única: archivos/CURP_CARRERA_FOLIO/
            $uploadDir = "archivos/{$curp}_{$id_carrera}_{$id_folio_num}/";
        } else {
            // Fallback si no encuentra información
            $uploadDir = "archivos/{$folio}/";
        }
        
        error_log("Directorio de subida (carpeta única CURP_CARRERA_FOLIO): " . $uploadDir);
        
        // La carpeta ya debería existir, pero crearla si no existe
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
            error_log("Directorio creado (no existía): " . $uploadDir);
        } else {
            error_log("Usando directorio existente: " . $uploadDir);
        }
        
        // Generar nombre del archivo con formato: nombredelarchivo_curpalumno
        // Obtener el nombre del documento desde titdocumentacion donde está el campo 'nom'
        $stmt_doc = $conn->prepare("SELECT nom FROM titdocumentacion WHERE id = ?");
        $stmt_doc->bind_param("i", $idDocTit);
        $stmt_doc->execute();
        $result_doc = $stmt_doc->get_result();
        
        if ($result_doc->num_rows > 0) {
            $doc_info = $result_doc->fetch_assoc();
            $nombre_documento = $doc_info['nom'];
            // Limpiar el nombre del documento para usarlo como nombre de archivo
            $nombre_archivo = preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower($nombre_documento));
            $fileName = "{$nombre_archivo}_{$curp}.pdf";
        } else {
            // Fallback al formato anterior
            $fileName = "doc_{$idDocTit}_{$curp}.pdf";
        }
        
        $filePath = $uploadDir . $fileName;
        error_log("Nombre del archivo generado: " . $fileName);
        
        // Mover archivo
        error_log("Intentando mover archivo de " . $archivo['tmp_name'] . " a " . $filePath);
        if (!move_uploaded_file($archivo['tmp_name'], $filePath)) {
            error_log("Error: No se pudo mover el archivo");
            return ['success' => false, 'error' => 'Error al guardar el archivo'];
        }
        error_log("Archivo movido exitosamente a: " . $filePath);
        
        // Actualizar en base de datos: cambiar estado a pendiente y limpiar comentarios
        $stmt = $conn->prepare("
            UPDATE titdocumentos_folio 
            SET estado = 'pendiente', 
                comentarios = NULL, 
                fecha_subida = NOW(),
                ruta_archivo = ?
            WHERE id_folio = ? AND id_doc_tit = ?
        ");
        $stmt->bind_param("sis", $filePath, $idFolio, $idDocTit);
        
        error_log("Ejecutando UPDATE en base de datos");
        error_log("Parámetros: filePath=" . $filePath . ", idFolio=" . $idFolio . ", idDocTit=" . $idDocTit);
        
        if ($stmt->execute()) {
            error_log("UPDATE ejecutado correctamente");
            $conn->close();
            error_log("Documento actualizado correctamente a estado pendiente");
            return ['success' => true, 'message' => 'Documento subido correctamente y está pendiente de revisión'];
        } else {
            error_log("Error en UPDATE: " . $stmt->error);
            // Eliminar archivo si falla la base de datos
            if (file_exists($filePath)) {
                unlink($filePath);
                error_log("Archivo eliminado debido a error en BD");
            }
            $conn->close();
            return ['success' => false, 'error' => 'Error al actualizar la base de datos'];
        }
        
    } catch (Exception $e) {
        if (isset($conn)) {
            $conn->close();
        }
        return ['success' => false, 'error' => 'Excepción: ' . $e->getMessage()];
    }
}

?>
