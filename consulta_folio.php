<?php
// Incluir configuración
$config = include('Consultas/configuracion_simple.php');
$cursoescolarinsc = $config['cursoescolarinsc'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,height=device-height,initial-scale=1, shrink-to-fit=no">
    <title>Consulta de Folio - Sistema de Titulación</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css?family=Noto+Sans&display=swap" rel="stylesheet">
    
    <!-- Bootstrap core CSS -->
    <link type="text/css" media="screen" href="fronts/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet" />
    <link type="text/css" media="screen" href="fronts/bootstrap-5.3.3/css/bootstrap-icons-1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" />

    <!-- jQuery -->
    <script type="text/ecmascript" src="fronts/jquery/jquery-3.6.1.min.js"></script> 
    
    <!-- Bootstrap JS -->
    <script src="fronts/bootstrap-5.3.3/js/bootstrap.bundle.min.js"></script>
    
    <!-- CSS para este módulo -->
    <link href="fronts/css/index.css" rel="stylesheet">
    
    <style>
        .estado-badge {
            font-size: 0.9em;
            padding: 0.5em 1em;
        }
        .documento-card {
            transition: all 0.3s ease;
        }
        .documento-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .loading {
            display: none;
            text-align: center;
            padding: 2rem;
        }
        .error-message {
            display: none;
        }
    </style>
</head>

<body class="text-center">
    <div class="container">
        <div class="card mt-3 mb-3 border-0 card-shadow text-secondary" 
        style="background: url(fronts/imagenes/index.jpg) no-repeat center top fixed;">
            <div class="card-header text-white border-0 backcolor-header">
                
                <div class="row mt-2 mb-3 align-items-center">
                    <div class="col-lg-3 col-md-4 col-sm-12">
                        <img class="logo_img" src="fronts/imagenes/logo_white.png" alt="logo" />
                    </div>
                    <div class="col-lg-9 col-md-8 col-sm-12 head-text">
                        <div class="title-1 fw-bold">Consulta de Folio</div>
                        <div class="mb-3 fs-4">Sistema de Titulación</div>
                        <div class="row">
                            <div class="col-lg-6 col-md-12 col-sm-12 mb-2">
                                <span id="cursoescolar" class="cursoescolar mt-3">Curso Escolar <?php echo $cursoescolarinsc; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card-body bg-white border-0">
                <!-- Botón de regreso -->
                <div class="row mb-4">
                    <div class="col-12">
                        <a href="index.php" class="btn btn-outline-primary">
                            <i class="bi bi-arrow-left me-2"></i>Regresar al Inicio
                        </a>
                    </div>
                </div>

                <!-- Loading -->
                <div id="loading" class="loading">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-2">Consultando folio...</p>
                </div>

                <!-- Error -->
                <div id="error_message" class="error-message">
                    <div class="alert alert-danger">
                        <h5><i class="bi bi-exclamation-triangle-fill me-2"></i>Error</h5>
                        <p id="error_text"></p>
                        <hr>
                        <small class="text-muted">Serás redirigido a la página principal en 3 segundos...</small>
                    </div>
                </div>

                <!-- Resultados -->
                <div id="resultados" style="display: none;">
                    <!-- Información del Folio -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-file-earmark-text me-2"></i>Información del Folio</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Folio:</strong> <span id="folio_numero"></span></p>
                                    <p><strong>CURP:</strong> <span id="folio_curp"></span></p>
                                    <p><strong>Estado:</strong> <span id="folio_estado" class="badge estado-badge"></span></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Carrera:</strong> <span id="folio_carrera"></span></p>
                                    <p><strong>Modalidad:</strong> <span id="folio_modalidad"></span></p>
                                    <p><strong>Fecha de Creación:</strong> <span id="folio_fecha"></span></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Documentos -->
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="bi bi-file-earmark-arrow-up me-2"></i>Documentos</h5>
                        </div>
                        <div class="card-body">
                            <div id="documentos_list">
                                <!-- Los documentos se cargarán aquí -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Variable global para guardar el folio actual
        let folioActual = '';
        
        $(document).ready(function() {
            // Obtener el folio desde sessionStorage
            const folio = sessionStorage.getItem('folio_consulta');
            
            if (folio) {
                // Limpiar el sessionStorage para que no se quede guardado
                sessionStorage.removeItem('folio_consulta');
                consultarFolio(folio);
            } else {
                mostrarError('No se proporcionó un número de folio');
                // Opcional: redirigir a la página principal después de unos segundos
                setTimeout(() => {
                    window.location.href = 'index.php';
                }, 3000);
            }
        });

        function consultarFolio(folio) {
            $('#loading').show();
            $('#error_message').hide();
            $('#resultados').hide();
            
            $.ajax({
                url: 'api_titulacion.php?action=consultar_folio&folio=' + encodeURIComponent(folio),
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    $('#loading').hide();
                    
                    if (response.success) {
                        mostrarResultados(response);
                    } else {
                        mostrarError(response.error);
                    }
                },
                error: function(xhr, status, error) {
                    $('#loading').hide();
                    mostrarError('Error de conexión: ' + error);
                }
            });
        }

        function mostrarResultados(data) {
            // Guardar el folio en variable global
            folioActual = data.folio.folio;
            console.log('Folio guardado en variable global:', folioActual);
            
            // Mostrar información del folio
            $('#folio_numero').text(data.folio.folio);
            $('#folio_curp').text(data.folio.curp);
            $('#folio_carrera').text(data.folio.nombre_carrera || 'No especificada');
            $('#folio_modalidad').text(data.folio.nombre_modalidad || 'No especificada');
            $('#folio_fecha').text(new Date(data.folio.fecha_creacion).toLocaleString());
            
            // Mostrar estado con color apropiado
            const estado = data.folio.estado_proceso;
            const $estadoBadge = $('#folio_estado');
            $estadoBadge.text(estado);
            
            // Quitar todas las clases de estado
            $estadoBadge.removeClass('bg-success bg-warning bg-danger bg-info bg-secondary');
            
            // Asignar clase según estado
            switch(estado.toLowerCase()) {
                case 'completado':
                    $estadoBadge.addClass('bg-success');
                    break;
                case 'en_revision':
                    $estadoBadge.addClass('bg-warning');
                    break;
                case 'aprobado':
                    $estadoBadge.addClass('bg-info');
                    break;
                case 'rechazado':
                    $estadoBadge.addClass('bg-danger');
                    break;
                default:
                    $estadoBadge.addClass('bg-secondary');
            }
            
            // Mostrar documentos
            mostrarDocumentos(data.documentos);
            
            $('#resultados').show();
        }

        function mostrarDocumentos(documentos) {
            const $documentosList = $('#documentos_list');
            
            if (documentos.length === 0) {
                $documentosList.html('<p class="text-muted">No hay documentos registrados para este folio.</p>');
                return;
            }
            
            console.log('Documentos recibidos:', documentos);
            
            let html = '<div class="row">';
            
            documentos.forEach(function(doc) {
                console.log('Documento individual:', doc);
                console.log('Comentarios del documento:', doc.comentarios);
                console.log('¿Tiene comentarios?', doc.comentarios && doc.comentarios.trim() !== '');
                const estadoClass = getEstadoClass(doc.estado);
                const estadoIcon = getEstadoIcon(doc.estado);
                const esRechazado = doc.estado.toLowerCase() === 'rechazado';
                const tieneComentarios = doc.comentarios && doc.comentarios.trim() !== '';
                
                html += `
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card documento-card h-100 ${esRechazado ? 'border-danger border-2' : ''}">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <i class="bi bi-file-earmark me-2"></i>${doc.nombre_documento}
                                </h6>
                                <div class="mb-2">
                                    <span class="badge ${estadoClass}">
                                        <i class="${estadoIcon} me-1"></i>${doc.estado}
                                    </span>
                                </div>
                                ${esRechazado && tieneComentarios ? `
                                    <div class="alert alert-danger mb-2 mt-2">
                                        <small>
                                            <strong><i class="bi bi-exclamation-triangle-fill me-1"></i>Motivo de rechazo:</strong><br>
                                            ${doc.comentarios}
                                        </small>
                                    </div>
                                ` : ''}
                                ${esRechazado ? `
                                    <div class="mb-2 mt-3">
                                        <label for="archivo_${doc.id_doc_tit}" class="form-label">
                                            <strong><i class="bi bi-upload me-1"></i>Subir nuevo archivo (PDF):</strong>
                                        </label>
                                        <input type="file" class="form-control" id="archivo_${doc.id_doc_tit}" 
                                               accept=".pdf,application/pdf" 
                                               onchange="previsualizarArchivo(${doc.id_doc_tit}, this)">
                                        <div id="vista_previa_${doc.id_doc_tit}" class="mt-2" style="display: none;">
                                            <small class="text-success">
                                                <i class="bi bi-check-circle-fill me-1"></i>
                                                Archivo seleccionado: <span id="nombre_archivo_${doc.id_doc_tit}"></span>
                                            </small>
                                        </div>
                                    </div>
                                    <button class="btn btn-primary btn-sm" onclick="subirDocumentoRechazado(${doc.id_doc_tit}, folioActual)">
                                        <i class="bi bi-cloud-upload me-1"></i>Subir Documento
                                    </button>
                                ` : ''}
                                ${doc.fecha_subida ? `` : ''}
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            $documentosList.html(html);
        }

        function getEstadoClass(estado) {
            switch(estado.toLowerCase()) {
                case 'aprobado': return 'bg-success';
                case 'pendiente': return 'bg-warning';
                case 'en_revision': return 'bg-info';
                case 'rechazado': return 'bg-danger';
                default: return 'bg-secondary';
            }
        }

        function getEstadoIcon(estado) {
            switch(estado.toLowerCase()) {
                case 'aprobado': return 'bi-check-circle-fill';
                case 'pendiente': return 'bi-clock-fill';
                case 'en_revision': return 'bi-eye-fill';
                case 'rechazado': return 'bi-x-circle-fill';
                default: return 'bi-question-circle-fill';
            }
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        function mostrarError(mensaje) {
            $('#error_text').text(mensaje);
            $('#error_message').show();
        }
        
        // Función para previsualizar el archivo seleccionado
        function previsualizarArchivo(idDoc, input) {
            const archivo = input.files[0];
            if (archivo) {
                $('#nombre_archivo_' + idDoc).text(archivo.name);
                $('#vista_previa_' + idDoc).show();
            } else {
                $('#vista_previa_' + idDoc).hide();
            }
        }
        
        // Función para subir documento rechazado
        function subirDocumentoRechazado(idDoc, folio) {
            const input = document.getElementById('archivo_' + idDoc);
            const archivo = input.files[0];
            
            if (!archivo) {
                alert('Por favor selecciona un archivo PDF.');
                return;
            }
            
            if (archivo.type !== 'application/pdf') {
                alert('El archivo debe estar en formato PDF.');
                return;
            }
            
            // Deshabilitar botón durante la subida
            const boton = event.target;
            const textoOriginal = boton.innerHTML;
            boton.disabled = true;
            boton.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Subiendo...';
            
            const formData = new FormData();
            formData.append('archivo', archivo);
            formData.append('id_doc_tit', idDoc);
            formData.append('folio', folio);
            formData.append('action', 'subir_documento_rechazado');
            
            console.log('Enviando FormData:', formData);
            console.log('URL: api_titulacion.php');
            console.log('Datos enviados:');
            console.log('- id_doc_tit:', idDoc);
            console.log('- folio:', folio);
            console.log('- action: subir_documento_rechazado');
            
            $.ajax({
                url: 'api_titulacion.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    console.log('Respuesta cruda:', response);
                    console.log('Tipo de respuesta:', typeof response);
                    
                    boton.disabled = false;
                    boton.innerHTML = textoOriginal;
                    
                    if (response && response.success) {
                        alert('¡Documento subido correctamente! Quedará en estado de revisión.');
                        // Recargar la página para mostrar el estado actualizado
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                    } else {
                        alert('Error al subir el documento: ' + (response ? response.error : 'Respuesta inválida'));
                    }
                },
                error: function(xhr, status, error) {
                    console.log('Error AJAX:', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText,
                        statusCode: xhr.status
                    });
                    
                    boton.disabled = false;
                    boton.innerHTML = textoOriginal;
                    alert('Error de conexión: ' + error + '\nRespuesta: ' + xhr.responseText);
                }
            });
        }
    </script>
</body>
</html>