<?php
// API endpoints para el sistema de titulación
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0); // NO mostrar errores en pantalla (rompe JSON)
ini_set('log_errors', 1);     // Sí guardar en log

// Control de buffer de salida
ob_start();

// Manejador de errores para capturar errores PHP
set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Manejador de excepciones
set_exception_handler(function($exception) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'error' => 'Error del servidor: ' . $exception->getMessage()
    ]);
    exit;
});

require_once 'Consultas/funciones_titulacion.php';

error_log("API: Script iniciado, action: " . ($_POST['action'] ?? $_GET['action'] ?? 'ninguna'));
error_log("API: Método: " . $_SERVER['REQUEST_METHOD']);
error_log("API: POST data: " . print_r($_POST, true));
error_log("API: FILES data: " . print_r($_FILES, true));

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'GET':
        if (isset($_GET['action'])) {
            switch ($_GET['action']) {
                case 'modalidades':
                    try {
                        if (isset($_GET['niv'])) {
                            // Nuevo: obtener modalidades por niv de carrera (5=Lic, 6=Mae)
                            $niv = intval($_GET['niv']);
                            error_log("API: Solicitando modalidades para niv: " . $niv);
                            
                            // Mapear niv a id_grado_academico
                            $id_grado = null;
                            if ($niv === 5) {
                                $id_grado = 1; // Licenciatura
                            } else if ($niv === 6) {
                                $id_grado = 3; // Maestría
                            }
                            
                            if ($id_grado) {
                                error_log("API: Llamando obtenerModalidadesPorGrado con id_grado: " . $id_grado);
                                $modalidades = obtenerModalidadesPorGrado($id_grado);
                                $response = ['success' => true, 'data' => $modalidades];
                                error_log("API: Respuesta preparada: " . json_encode($response));
                                echo json_encode($response);
                            } else {
                                echo json_encode(['success' => false, 'error' => 'Niv no válido: ' . $niv]);
                            }
                        } else if (isset($_GET['id_grado'])) {
                            $modalidades = obtenerModalidadesPorGrado($_GET['id_grado']);
                            echo json_encode(['success' => true, 'data' => $modalidades]);
                        } else if (isset($_GET['id_carrera'])) {
                            // Nuevo: obtener modalidades por carrera específica
                            error_log("API: Solicitando modalidades para carrera ID: " . $_GET['id_carrera']);
                            $modalidades = obtenerModalidadesPorCarrera($_GET['id_carrera']);
                            error_log("API: Modalidades devueltas: " . json_encode($modalidades));
                            echo json_encode(['success' => true, 'data' => $modalidades]);
                        } else {
                            echo json_encode(['success' => false, 'error' => 'Falta id_grado o id_carrera']);
                        }
                    } catch (Exception $e) {
                        error_log("API ERROR en modalidades: " . $e->getMessage());
                        echo json_encode(['success' => false, 'error' => 'Error interno: ' . $e->getMessage()]);
                    }
                    break;
                    
                case 'documentos':
                    if (isset($_GET['modalidad_id'])) {
                        // Nuevo: obtener documentos por modalidad (sin necesidad de folio)
                        $documentos = obtenerDocumentosPorModalidad($_GET['modalidad_id']);
                        echo json_encode(['success' => true, 'data' => $documentos]);
                    } else if (isset($_GET['id_modalidad'])) {
                        $documentos = obtenerDocumentosPorModalidad($_GET['id_modalidad']);
                        echo json_encode(['success' => true, 'data' => $documentos]);
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Falta modalidad_id o id_modalidad']);
                    }
                    break;
                    
                case 'estado_folio':
                    if (isset($_GET['folio'])) {
                        $estado = consultarEstadoPorFolio($_GET['folio']);
                        echo json_encode(['success' => true, 'data' => $estado]);
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Falta folio']);
                    }
                    break;
                    
                case 'folio_curp':
                    if (isset($_GET['curp'])) {
                        $folio = obtenerFolioPorCurp($_GET['curp']);
                        echo json_encode(['success' => true, 'data' => $folio]);
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Falta curp']);
                    }
                    break;
                    
                case 'carreras_estudiante':
                    if (isset($_GET['curp'])) {
                        $carreras = obtenerCarrerasEstudiante($_GET['curp']);
                        echo json_encode(['success' => true, 'data' => $carreras]);
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Falta curp']);
                    }
                    break;
                    
                case 'documentos_requeridos':
                    if (isset($_GET['id_folio'])) {
                        $documentos = obtenerDocumentosRequeridos($_GET['id_folio']);
                        echo json_encode(['success' => true, 'data' => $documentos]);
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Falta id_folio']);
                    }
                    break;
                    
                case 'estado_completo_documentos':
                    if (isset($_GET['id_folio'])) {
                        $documentos = obtenerEstadoCompletoDocumentos($_GET['id_folio']);
                        echo json_encode(['success' => true, 'data' => $documentos]);
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Falta id_folio']);
                    }
                    break;
                    
                case 'documentos_folio':
                    if (isset($_GET['id_folio'])) {
                        $documentos = obtenerDocumentosFolio($_GET['id_folio']);
                        echo json_encode(['success' => true, 'data' => $documentos]);
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Falta id_folio']);
                    }
                    break;
                    
                case 'consultar_folio':
                    if (isset($_GET['folio'])) {
                        $resultado = consultarFolio($_GET['folio']);
                        echo json_encode($resultado);
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Falta folio']);
                    }
                    break;
                    
                default:
                    echo json_encode(['success' => false, 'error' => 'Acción no válida']);
            }
        }
        break;
        
    case 'POST':
        // Verificar action en $_POST (para FormData) o en $input (para JSON)
        $action = $_POST['action'] ?? $input['action'] ?? null;
        error_log("API: POST action detectado: " . $action);
        
        if ($action) {
            switch ($action) {
                case 'crear_folio':
                    $required = ['curp', 'id_grado_academico', 'id_carrera', 'id_modalidad_tit'];
                    error_log("API crear_folio - Datos recibidos: " . json_encode($input));
                    error_log("API crear_folio - Campos requeridos: " . json_encode($required));
                    
                    // Verificar campo por campo
                    foreach ($required as $field) {
                        if (!isset($input[$field])) {
                            error_log("API crear_folio - CAMPO FALTANTE: $field");
                        } else {
                            error_log("API crear_folio - $field = " . $input[$field]);
                        }
                    }
                    
                    if (allFieldsExist($required, $input)) {
                        $resultado = crearFolioEstudiante(
                            $input['curp'],
                            $input['id_grado_academico'],
                            $input['id_carrera'],
                            $input['id_modalidad_tit']
                        );
                        echo json_encode($resultado);
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Faltan campos requeridos']);
                    }
                    break;
                    
                case 'subir_documento':
                    $required = ['id_folio', 'id_doc_tit', 'ruta_archivo', 'archivo_base64'];
                    if (allFieldsExist($required, $input)) {
                        $resultado = subirDocumento(
                            $input['id_folio'],
                            $input['id_doc_tit'],
                            $input['ruta_archivo'],
                            $input['nombre_archivo'] ?? null,
                            $input['tamano_archivo'] ?? null,
                            $input['tipo_archivo'] ?? null,
                            $input['archivo_base64'] ?? null
                        );
                        echo json_encode($resultado);
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Faltan campos requeridos']);
                    }
                    break;
                    
                case 'actualizar_estado':
                    $required = ['id_documento', 'estado'];
                    if (allFieldsExist($required, $input)) {
                        $resultado = actualizarEstadoDocumento(
                            $input['id_documento'],
                            $input['estado'],
                            $input['comentarios'] ?? null,
                            $input['revisado_por'] ?? null
                        );
                        echo json_encode($resultado);
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Faltan campos requeridos']);
                    }
                    break;
                    
                case 'actualizar_estado_proceso':
                    $required = ['id_folio', 'estado'];
                    if (allFieldsExist($required, $input)) {
                        $resultado = actualizarEstadoProceso($input['id_folio'], $input['estado']);
                        echo json_encode($resultado);
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Faltan campos requeridos']);
                    }
                    break;
                    
                case 'subir_documento_rechazado':
                    error_log("API: Caso subir_documento_rechazado ejecutado");
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        error_log("API: Método POST detectado");
                        $resultado = subirDocumentoRechazado($_POST, $_FILES);
                        error_log("API: Resultado: " . print_r($resultado, true));
                        echo json_encode($resultado);
                    } else {
                        error_log("API: Método no permitido: " . $_SERVER['REQUEST_METHOD']);
                        echo json_encode(['success' => false, 'error' => 'Método no permitido']);
                    }
                    break;
                    
                default:
                    echo json_encode(['success' => false, 'error' => 'Acción no válida']);
            }
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Método no permitido']);
}

function allFieldsExist($fields, $data) {
    foreach ($fields as $field) {
        if (!isset($data[$field])) {
            return false;
        }
    }
    return true;
}

// Enviar buffer de salida
ob_end_flush();
?>
