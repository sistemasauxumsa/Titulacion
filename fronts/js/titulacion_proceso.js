// Variables globales (datosAlumno ya está declarada en titulacion.js)
let folioActual = null;
let sistemaTitulacion = null;

// Inicializar sistema cuando el documento esté listo
$(document).ready(function() {
    sistemaTitulacion = new SistemaTitulacion();
});

// Función para cargar modalidades cuando se llega a la página 4
function cargarModalidades() {
    console.log('cargarModalidades() iniciada');
    console.log('datosAlumno completo:', datosAlumno);
    console.log('carrerasAlumno:', carrerasAlumno);
    
    if (!datosAlumno) {
        console.error('No hay datos del alumno');
        return;
    }

    // Si no hay niv en datosAlumno, intentar obtenerlo de carrerasAlumno
    if (datosAlumno.niv === undefined || datosAlumno.niv === null) {
        console.log('niv no está en datosAlumno, buscando en carrerasAlumno...');
        
        if (carrerasAlumno && carrerasAlumno.length > 0) {
            // Usar la primera carrera (o la que coincida con id_carrera si existe)
            var carrera = carrerasAlumno[0];
            if (datosAlumno.id_carrera) {
                var carreraEncontrada = carrerasAlumno.find(c => c.id == datosAlumno.id_carrera);
                if (carreraEncontrada) {
                    carrera = carreraEncontrada;
                }
            }
            
            console.log('Carrera a usar:', carrera);
            
            // Asignar datos de la carrera
            datosAlumno.id_carrera = carrera.id;
            datosAlumno.nombre_carrera = carrera.nombre;
            datosAlumno.nivel = carrera.nivel;
            
            // Obtener niv
            if (carrera.niv !== undefined && carrera.niv !== null && carrera.niv !== '') {
                datosAlumno.niv = parseInt(carrera.niv, 10);
                console.log('niv asignado desde carrera.niv:', datosAlumno.niv);
            } else if (carrera.nivel === 'Licenciatura') {
                datosAlumno.niv = 5;
                console.log('niv asignado desde nivel (Licenciatura):', datosAlumno.niv);
            } else if (carrera.nivel === 'Maestría') {
                datosAlumno.niv = 6;
                console.log('niv asignado desde nivel (Maestría):', datosAlumno.niv);
            }
            
            // Calcular y guardar id_grado_academico
            if (datosAlumno.niv === 5) {
                datosAlumno.id_grado_academico = 1; // Licenciatura
            } else if (datosAlumno.niv === 6) {
                datosAlumno.id_grado_academico = 3; // Maestría
            }
            console.log('id_grado_academico asignado:', datosAlumno.id_grado_academico);
        }
    }

    // Usar el niv numérico de la carrera seleccionada (5=Licenciatura, 6=Maestría)
    const nivCarrera = datosAlumno.niv;
    console.log('Niv de la carrera:', nivCarrera);

    let id_grado_academico = null;

    // Mapear niv de carrera a id_grado_academico de titmodalidades
    // 5 -> 1 (Licenciatura)
    // 6 -> 3 (Maestría)
    if (parseInt(nivCarrera, 10) === 5) {
        id_grado_academico = 1;
    } else if (parseInt(nivCarrera, 10) === 6) {
        id_grado_academico = 3;
    }

    console.log('ID Grado Académico desde niv de carrera:', id_grado_academico);
    
    if (!id_grado_academico) {
        console.error('No se pudo determinar el grado académico desde el niv de la carrera');
        document.getElementById('select_modalidad').innerHTML = '<option value="">Error: No se pudo determinar el nivel académico</option>';
        return;
    }
    
    const url = `api_titulacion.php?action=modalidades&niv=${datosAlumno.niv}`;
    console.log('URL de consulta (usando niv):', url);
    
    fetch(url)
        .then(response => {
            console.log('Respuesta HTTP:', response.status);
            return response.text(); // Cambiar a text() para ver la respuesta cruda
        })
        .then(text => {
            console.log('Respuesta cruda:', text);
            try {
                const data = JSON.parse(text);
                console.log('Datos parseados:', data);
                if (data.success) {
                    const select = document.getElementById('select_modalidad');
                    select.innerHTML = '<option value="">Selecciona una modalidad...</option>';
                    
                    data.data.forEach(modalidad => {
                        const option = document.createElement('option');
                        option.value = modalidad.id;
                        option.textContent = modalidad.nombre;
                        select.appendChild(option);
                    });
                    console.log('Modalidades cargadas:', data.data.length);
                } else {
                    console.error('Error al cargar modalidades:', data.error);
                    document.getElementById('select_modalidad').innerHTML = '<option value="">Error al cargar modalidades</option>';
                }
            } catch (e) {
                console.error('Error al parsear JSON:', e);
                console.error('Texto que causó error:', text.substring(330, 350));
                document.getElementById('select_modalidad').innerHTML = '<option value="">Error en respuesta del servidor</option>';
            }
        })
        .catch(error => {
            console.error('Error en fetch:', error);
            document.getElementById('select_modalidad').innerHTML = '<option value="">Error de conexión</option>';
        });
}

function cargarDocumentosExistentes() {
    if (!datosAlumno || !datosAlumno.curp || !datosAlumno.id_carrera) {
        return;
    }
    
    // Si ya hay un folio creado, cargar sus documentos
    if (folioActual && folioActual.id) {
        fetch(`api_titulacion.php?action=documentos_folio&id_folio=${folioActual.id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Marcar los documentos que ya existen
                    data.documentos.forEach(doc => {
                        const badge = document.getElementById(`badge-doc-${doc.id_doc_tit}`);
                        if (badge) {
                            if (doc.estado === 'aprobado') {
                                badge.className = 'badge bg-success';
                                badge.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>Aprobado';
                            } else if (doc.estado === 'rechazado') {
                                badge.className = 'badge bg-danger';
                                badge.innerHTML = '<i class="bi bi-x-circle-fill me-1"></i>Rechazado';
                            } else if (doc.estado === 'en_revision') {
                                badge.className = 'badge bg-info';
                                badge.innerHTML = '<i class="bi bi-eye-fill me-1"></i>En revisión';
                            }
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Error al cargar documentos existentes:', error);
            });
    }
}

// Función para cargar documentos requeridos según modalidad seleccionada (SIN crear folio)
function cargarDocumentos() {
    const idModalidad = document.getElementById('select_modalidad').value;
    
    if (!idModalidad || !datosAlumno) {
        document.getElementById('documentos_requeridos').innerHTML = `
            <div class="alert alert-info">
                <h5><i class="bi bi-info-circle-fill me-2"></i>Selecciona una modalidad para ver los documentos requeridos</h5>
            </div>
        `;
        return;
    }

    // Guardar modalidad seleccionada
    datosAlumno.id_modalidad_tit = idModalidad;
    console.log('Modalidad seleccionada:', idModalidad);

    // Cargar documentos directamente por modalidad (sin crear folio aún)
    cargarDocumentosPorModalidad(idModalidad);
}

// Nueva función: cargar documentos por modalidad sin necesidad de folio
function cargarDocumentosPorModalidad(idModalidad) {
    fetch(`api_titulacion.php?action=documentos&modalidad_id=${idModalidad}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                documentosRequeridos = data.data;
                
                // Si hay un folio actual, cargar documentos existentes primero
                if (folioActual && folioActual.id) {
                    cargarDocumentosExistentes(folioActual.id).then(() => {
                        // Después de cargar los existentes, mostrar los documentos combinados
                        mostrarDocumentosParaSubir(data.data);
                    });
                } else {
                    // Si no hay folio, mostrar los documentos requeridos normales
                    mostrarDocumentosParaSubir(data.data);
                }
            } else {
                console.error('Error al cargar documentos:', data.error);
                document.getElementById('documentos_requeridos').innerHTML = `<div class="alert alert-danger">Error: ${data.error}</div>`;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('documentos_requeridos').innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Error al cargar los documentos requeridos. Por favor, intenta nuevamente.
                </div>
            `;
        });
}

// Mostrar documentos para subir (versión simplificada sin folio)
function mostrarDocumentosParaSubir(documentos) {
    const container = document.getElementById('documentos_requeridos');
    container.innerHTML = '';

    if (documentos.length === 0) {
        container.innerHTML = `
            <div class="alert alert-warning">
                <h5><i class="bi bi-exclamation-triangle-fill me-2"></i>No hay documentos configurados para esta modalidad</h5>
            </div>
        `;
        return;
    }

    const documentosDiv = document.createElement('div');
    documentosDiv.className = 'row';

    documentos.forEach(doc => {
        const col = document.createElement('div');
        col.className = 'col-md-6 mb-3';
        
        col.innerHTML = `
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="card-title">
                        <i class="bi bi-file-earmark-text me-2"></i>${doc.nombre || doc.nom}
                    </h6>
                    <div class="mb-2">
                        <span class="badge bg-info">
                            <i class="bi bi-upload me-1"></i>Pendiente de subida
                        </span>
                    </div>
                    <div class="mb-2">
                        <input type="file" class="form-control" id="doc-${doc.id}" 
                               accept=".pdf,.jpg,.jpeg,.png">
                    </div>
                    <button class="btn btn-primary btn-sm" onclick="subirDocumentoTemporal(${doc.id}, 'doc-${doc.id}')">
                        <i class="bi bi-upload me-1"></i>Subir Documento
                    </button>
                </div>
            </div>
        `;
        
        documentosDiv.appendChild(col);
    });

    container.appendChild(documentosDiv);
    
    // Mostrar mensaje informativo
    const infoDiv = document.createElement('div');
    infoDiv.className = 'alert alert-info mt-3';
    infoDiv.innerHTML = `
        <i class="bi bi-info-circle-fill me-2"></i>
        Los documentos se guardarán temporalmente. Al finalizar se creará el folio oficial.
    `;
    container.appendChild(infoDiv);
}

// Variable para guardar los documentos requeridos y sus archivos temporales
let documentosRequeridos = [];
let archivosTemporales = {}; // Mapa: id_documento -> File

// Mostrar documentos para subir (versión con selección de archivos temporal)
function mostrarDocumentosParaSubir(documentos) {
    console.log('mostrarDocumentosParaSubir llamada con:', documentos);
    console.log('documentosExistentes disponibles:', window.documentosExistentes);
    
    const container = document.getElementById('documentos_requeridos');
    container.innerHTML = '';
    
    // Guardar lista de documentos requeridos
    documentosRequeridos = documentos || [];
    archivosTemporales = {}; // Limpiar archivos previos

    if (!documentos || documentos.length === 0) {
        container.innerHTML = '<div class="alert alert-info">No hay documentos requeridos para esta modalidad.</div>';
        return;
    }
    
    // Combinar documentos requeridos con existentes si hay datos
    const documentosConEstado = documentos.map(doc => {
        const existente = window.documentosExistentes ? 
            window.documentosExistentes.find(d => d.id_doc_tit === doc.id) : null;
        
        console.log(`Documento ${doc.id}:`, { requerido: doc, existente: existente });
        
        if (existente) {
            return {
                ...doc,
                estado: existente.estado,
                comentarios: existente.comentarios
            };
        }
        return doc;
    });
    
    console.log('Documentos con estado final:', documentosConEstado);
    
    // Crear contenedor principal
    const infoDiv = document.createElement('div');
    infoDiv.className = 'mb-4';
    infoDiv.innerHTML = `
        <h5 class="mb-3"><i class="bi bi-file-earmark-text me-2"></i>Documentos Requeridos</h5>
        <p class="text-muted">Por favor, sube los siguientes documentos en formato PDF.</p>
    `;
    container.appendChild(infoDiv);

    // Crear grid para documentos
    const documentosDiv = document.createElement('div');
    documentosDiv.className = 'row';

    documentosConEstado.forEach(doc => {
        const docDiv = document.createElement('div');
        docDiv.className = 'col-md-6 col-lg-4 mb-3';
        
        // Verificar si este documento ya existe y está rechazado
        const existeRechazado = doc.estado === 'rechazado';
        
        docDiv.innerHTML = `
            <div id="doc-card-${doc.id}" class="card h-100 ${existeRechazado ? 'border-danger border-2' : ''}">
                <div class="card-header d-flex justify-content-between align-items-center ${existeRechazado ? 'bg-danger text-white' : 'bg-light'}">
                    <span><i class="bi bi-file-earmark-text me-2"></i>${doc.nombre}</span>
                    <span id="badge-doc-${doc.id}" class="badge ${existeRechazado ? 'bg-warning text-dark' : 'bg-secondary'}">
                        ${existeRechazado ? '<i class="bi bi-exclamation-triangle-fill me-1"></i>Rechazado' : '<i class="bi bi-clock me-1"></i>Pendiente'}
                    </span>
                </div>
                <div class="card-body">
                    <!-- Los comentarios de rechazo se agregarán dinámicamente -->
                    
                    <div class="mb-2">
                        <label for="archivo-${doc.id}" class="form-label">
                            ${existeRechazado ? 'Seleccionar nuevo archivo PDF (requerido):' : 'Seleccionar archivo PDF:'}
                        </label>
                        <input type="file" class="form-control" id="archivo-${doc.id}" 
                               accept=".pdf,application/pdf" 
                               ${existeRechazado ? 'required' : ''}
                               onchange="seleccionarArchivo(${doc.id}, this)">
                        ${existeRechazado ? '<small class="text-danger"><i class="bi bi-info-circle me-1"></i>Este documento fue rechazado y debe ser subido nuevamente</small>' : ''}
                    </div>
                    
                    <div id="info-archivo-${doc.id}" style="display: none;" class="mt-2">
                        <small class="text-success">
                            <i class="bi bi-check-circle-fill me-1"></i>
                            Archivo seleccionado: <span id="nombre-archivo-${doc.id}"></span>
                        </small>
                    </div>
                </div>
            </div>
        `;
        
        documentosDiv.appendChild(docDiv);
    });
    
    container.appendChild(documentosDiv);
    
    // Deshabilitar botón finalizar inicialmente
    const btnFinalizar = document.getElementById('btn_finalizar');
    if (btnFinalizar) {
        btnFinalizar.disabled = true;
    }
    
    // Verificar si hay documentos aprobados que no necesitan ser subido de nuevo
    if (documentos && documentos.length > 0) {
        const documentosRequeridos = documentos.filter(doc => doc.estado !== 'aprobado');
        const documentosRechazados = documentos.filter(doc => doc.estado === 'rechazado');
        
        if (documentosRequeridos.length === 0) {
            // Todos los documentos están aprobados, habilitar botón
            if (btnFinalizar) {
                btnFinalizar.disabled = false;
            }
        } else {
            // Hay documentos pendientes o rechazados
            if (documentosRechazados.length > 0) {
                // Mostrar mensaje especial para documentos rechazados
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-warning mt-3';
                alertDiv.innerHTML = `
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>Atención:</strong> Tienes ${documentosRechazados.length} documento(s) rechazado(s). 
                    Por favor, sube nuevamente los archivos requeridos para continuar.
                `;
                container.appendChild(alertDiv);
            }
        }
    }
    
    // Verificar si todos los documentos están listos
    verificarDocumentosCompletos();
}

// Función llamada cuando se selecciona un archivo
function seleccionarArchivo(idDocumento, input) {
    const archivo = input.files[0];
    
    if (!archivo) {
        return;
    }
    
    // Validar que sea PDF
    if (archivo.type !== 'application/pdf') {
        alert('Solo se permiten archivos PDF');
        input.value = '';
        return;
    }
    
    // Validar tamaño máximo (10MB)
    const tamanoMaximo = 10 * 1024 * 1024; // 10MB
    if (archivo.size > tamanoMaximo) {
        alert('El archivo no debe superar los 10MB');
        input.value = '';
        return;
    }
    
    // Actualizar UI inmediatamente
    const badge = document.getElementById(`badge-doc-${idDocumento}`);
    badge.className = 'badge bg-success';
    badge.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>Listo';
    
    const infoArchivo = document.getElementById(`info-archivo-${idDocumento}`);
    const nombreArchivo = document.getElementById(`nombre-archivo-${idDocumento}`);
    nombreArchivo.textContent = archivo.name;
    infoArchivo.style.display = 'block';
    
    // Cambiar color del card (temporalmente desactivado para depurar)
    // const card = document.getElementById(`doc-card-${idDocumento}`);
    // if (card) {
    //     card.classList.add('border-success');
    // }
    
    console.log('Archivo seleccionado para doc', idDocumento, ':', archivo.name);
    console.log('Badge actualizado a Listo');
    
    // Convertir archivo a base64
    const reader = new FileReader();
    reader.onload = function(e) {
        const archivoBase64 = e.target.result;
        
        // Guardar archivo temporalmente con base64
        archivosTemporales[idDocumento] = {
            file: archivo,
            base64: archivoBase64,
            nombre: archivo.name,
            tamano: archivo.size,
            tipo: archivo.type
        };
        
        // Verificar si todos los documentos están listos
        verificarDocumentosCompletos();
    };
    reader.readAsDataURL(archivo);
}

// Verificar que todos los documentos requeridos tengan archivo seleccionado
function verificarDocumentosCompletos() {
    const totalDocumentos = documentosRequeridos.length;
    const documentosConArchivo = Object.keys(archivosTemporales).length;
    
    console.log('Verificando documentos:', documentosConArchivo, 'de', totalDocumentos);
    console.log('Documentos requeridos:', documentosRequeridos);
    console.log('Archivos temporales:', archivosTemporales);
    
    const todosCompletos = totalDocumentos > 0 && documentosConArchivo === totalDocumentos;
    
    const btnFinalizar = document.getElementById('btn_finalizar');
    if (!btnFinalizar) {
        console.error('No se encontró el botón btn_finalizar');
        return;
    }
    
    btnFinalizar.disabled = !todosCompletos;
    
    console.log('¿Todos completos?', todosCompletos, 'Botón deshabilitado:', btnFinalizar.disabled);
    
    if (todosCompletos) {
        btnFinalizar.classList.remove('btn-secondary');
        btnFinalizar.classList.add('btn-success');
        btnFinalizar.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i>Finalizar Proceso';
    } else {
        btnFinalizar.classList.remove('btn-success');
        btnFinalizar.classList.add('btn-secondary');
        btnFinalizar.innerHTML = '<i class="bi bi-clock-fill me-2"></i>Finalizar Proceso';
    }
}

// Variable para prevenir doble clic
let finalizandoProceso = false;

// Finalizar proceso: crear folio y subir todos los documentos
function finalizarProceso() {
    console.log('=== FINALIZAR PROCESO INICIADO ===');
    console.log('Timestamp:', new Date().toISOString());
    
    // Prevenir doble clic
    if (finalizandoProceso) {
        console.log('⚠️ Proceso ya en curso, ignorando clic adicional');
        return;
    }
    
    const idModalidad = document.getElementById('select_modalidad').value;
    console.log('Modalidad seleccionada:', idModalidad);
    
    if (!idModalidad) {
        alert('Error: No has seleccionado una modalidad');
        return;
    }
    
    console.log('Documentos requeridos:', documentosRequeridos);
    console.log('Archivos temporales:', archivosTemporales);
    
    const totalDocumentos = documentosRequeridos.length;
    const documentosConArchivo = Object.keys(archivosTemporales).length;
    
    console.log(`Documentos: ${documentosConArchivo} de ${totalDocumentos}`);
    
    if (documentosConArchivo < totalDocumentos) {
        alert(`Faltan ${totalDocumentos - documentosConArchivo} documentos por seleccionar`);
        return;
    }

    // Usar confirmación personalizada (jAlert parece tener problemas)
    console.log('Mostrando confirmación personalizada...');
    
    // Crear modal de confirmación
    const modalHtml = `
        <div class="modal fade" id="confirmModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="bi bi-question-circle me-2"></i>¿Finalizar proceso?
                        </h5>
                    </div>
                    <div class="modal-body">
                        <p>Se creará el folio oficial y se subirán todos los documentos. ¿Estás seguro?</p>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Documentos a subir:</strong> ${documentosConArchivo} de ${totalDocumentos}
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-2"></i>Cancelar
                        </button>
                        <button type="button" class="btn btn-success" id="btnConfirmFinalizar">
                            <i class="bi bi-check-circle me-2"></i>Sí, finalizar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Eliminar modal existente si hay
    const existingModal = document.getElementById('confirmModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Agregar modal al body
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
    modal.show();
    
    // Event listener para el botón de confirmar
    document.getElementById('btnConfirmFinalizar').addEventListener('click', function() {
        console.log('✅ Usuario confirmó con modal personalizado');
        modal.hide();
        document.getElementById('confirmModal').remove();
        procesarFinalizacion(idModalidad);
    });
    
    // Event listener para cuando se cierra el modal
    document.getElementById('confirmModal').addEventListener('hidden.bs.modal', function() {
        console.log('❌ Modal cerrado sin confirmar');
        finalizandoProceso = false;
        // Verificar que el modal exista antes de intentar removerlo
        const modalElement = document.getElementById('confirmModal');
        if (modalElement) {
            modalElement.remove();
        }
    });
}

// Función separada para procesar la finalización
function procesarFinalizacion(idModalidad) {
    console.log('procesarFinalizacion() iniciada con idModalidad:', idModalidad);
    
    // Marcar que estamos procesando
    finalizandoProceso = true;
    
    // Mostrar indicador de carga
    const btnFinalizar = document.getElementById('btn_finalizar');
    btnFinalizar.disabled = true;
    btnFinalizar.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Procesando...';

    // Crear folio primero
    const datos = {
        action: 'crear_folio',
        curp: datosAlumno.curp,
        id_grado_academico: datosAlumno.id_grado_academico,
        id_carrera: datosAlumno.id_carrera,
        id_modalidad_tit: idModalidad
    };
    
    console.log('Datos a enviar:', datos);

    fetch('api_titulacion.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(datos)
    })
    .then(response => {
        console.log('Respuesta recibida, status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Datos de respuesta:', data);
        if (data.success) {
            folioActual = {
                id: data.id_folio,
                folio: data.folio
            };
            
            // Mostrar información del folio
            document.getElementById('folio_generado').textContent = data.folio;
            document.getElementById('info_folio').style.display = 'block';
            
            // Ahora subir todos los documentos
            subirTodosLosDocumentos(data.id_folio);
        } else {
            alert('Error al crear folio: ' + data.error);
            btnFinalizar.disabled = false;
            verificarDocumentosCompletos();
        }
    })
    .catch(error => {
        console.error('Error en fetch:', error);
        alert('Error al crear folio: ' + error.message);
        finalizandoProceso = false; // Resetear flag en caso de error
        btnFinalizar.disabled = false;
        verificarDocumentosCompletos();
    });
}

// Subir todos los documentos seleccionados al folio
function subirTodosLosDocumentos(idFolio) {
    console.log('subirTodosLosDocumentos() iniciada con idFolio:', idFolio);
    console.log('Archivos a subir:', Object.keys(archivosTemporales));
    
    const promesas = [];
    
    for (const [idDocumento, archivoData] of Object.entries(archivosTemporales)) {
        const promesa = new Promise((resolve, reject) => {
            // Obtener el nombre del documento desde documentosRequeridos
            const docInfo = documentosRequeridos.find(doc => doc.id == idDocumento);
            const nombreDocumento = docInfo ? docInfo.nombre : `doc_${idDocumento}`;
            
            // Generar nombre de archivo con formato: nombredelarchivo_curpalumno
            const timestamp = Date.now();
            const extension = archivoData.nombre.split('.').pop();
            
            // Limpiar el nombre del documento para usarlo como nombre de archivo
            const nombreDocumentoLimpio = nombreDocumento.replace(/[^a-zA-Z0-9_]/g, '_').toLowerCase();
            const nombreArchivo = `${nombreDocumentoLimpio}_${datosAlumno.curp}.${extension}`;
            
            // Ruta donde se guardará el archivo (carpeta: curp_idcarrera_folio para evitar sobreescribir)
            const rutaArchivo = `archivos/${datosAlumno.curp}_${datosAlumno.id_carrera}_${idFolio}/${nombreArchivo}`;
            
            const datos = {
                action: 'subir_documento',
                id_folio: idFolio,
                id_doc_tit: idDocumento,
                ruta_archivo: rutaArchivo,
                nombre_archivo: archivoData.nombre,
                tamano_archivo: archivoData.tamano,
                tipo_archivo: archivoData.tipo,
                archivo_base64: archivoData.base64 // Enviar el archivo en base64
            };
            
            console.log('Enviando archivo:', archivoData.nombre, 'a ruta:', rutaArchivo);
            
            fetch('api_titulacion.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(datos)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('✅ Archivo subido:', archivoData.nombre);
                    resolve(data);
                } else {
                    console.error('❌ Error al subir archivo:', archivoData.nombre, data.error);
                    reject(data.error);
                }
            })
            .catch(error => {
                console.error('❌ Error en la petición:', error);
                reject(error);
            });
        });
        
        promesas.push(promesa);
    }
    
    // Esperar a que todos los archivos se suban
    Promise.all(promesas)
        .then(resultados => {
            console.log('✅ Todos los archivos subidos exitosamente');
            actualizarEstadoFinalizado(idFolio);
        })
        .catch(error => {
            console.error('❌ Error al subir archivos:', error);
            alert('Error al subir algunos archivos: ' + error);
            finalizandoProceso = false;
        });
}

// Actualizar estado del proceso a finalizado
function actualizarEstadoFinalizado(idFolio) {
    const datos = {
        action: 'actualizar_estado_proceso',
        id_folio: idFolio,
        estado: 'completado'
    };

    fetch('api_titulacion.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(datos)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Proceso de titulación finalizado exitosamente');
            document.getElementById('estado_proceso').textContent = 'Completado';
            document.getElementById('btn_finalizar').disabled = true;
            // Limpiar archivos temporales
            archivosTemporales = {};
            documentosRequeridos = [];
        } else {
            alert('Error al finalizar proceso: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al finalizar proceso');
    });
}

// Función para crear nuevo folio
function crearNuevoFolio(idModalidad) {
    console.log('crearNuevoFolio() iniciada con idModalidad:', idModalidad);
    console.log('datosAlumno:', datosAlumno);
    
    const datos = {
        action: 'crear_folio',
        curp: datosAlumno.curp,
        id_grado_academico: datosAlumno.id_grado_academico,
        id_carrera: datosAlumno.id_carrera,
        id_modalidad_tit: idModalidad
    };
    
    console.log('Datos a enviar:', datos);
    console.log('Verificación campos:');
    console.log('- curp:', datos.curp);
    console.log('- id_grado_academico:', datos.id_grado_academico);
    console.log('- id_carrera:', datos.id_carrera);
    console.log('- id_modalidad_tit:', datos.id_modalidad_tit);

    fetch('api_titulacion.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(datos)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            folioActual = {
                id: data.id_folio,
                folio: data.folio
            };
            
            // Mostrar información del folio
            document.getElementById('folio_generado').textContent = data.folio;
            document.getElementById('info_folio').style.display = 'block';
            
            // Cargar documentos requeridos
            cargarDocumentosRequeridos(data.id_folio);
        } else {
            console.error('Error al crear folio:', data.error);
            alert('Error al crear folio: ' + data.error);
        }
    })
    .catch(error => console.error('Error:', error));
}

// Función para cargar documentos requeridos
function cargarDocumentosRequeridos(idFolio) {
    fetch(`api_titulacion.php?action=documentos_requeridos&id_folio=${idFolio}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarDocumentos(data.data);
            } else {
                console.error('Error:', data.error);
            }
        })
        .catch(error => console.error('Error:', error));
}

// Función para cargar documentos existentes
function cargarDocumentosExistentes(idFolio) {
    console.log('Cargando documentos existentes para folio:', idFolio);
    
    if (!idFolio) {
        console.log('No hay folio, omitiendo carga de documentos existentes');
        return Promise.resolve();
    }
    
    return fetch(`api_titulacion.php?action=documentos_folio&id_folio=${idFolio}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Documentos existentes cargados:', data.data);
                // Guardar los documentos existentes en una variable global
                window.documentosExistentes = data.data;
                
                // Marcar los documentos que ya existen y actualizar sus estados
                data.data.forEach(doc => {
                    const badge = document.getElementById(`badge-doc-${doc.id_doc_tit}`);
                    const card = document.getElementById(`doc-card-${doc.id_doc_tit}`);
                    const fileInput = document.getElementById(`archivo-${doc.id_doc_tit}`);
                    
                    if (badge) {
                        if (doc.estado === 'aprobado') {
                            badge.className = 'badge bg-success';
                            badge.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>Aprobado';
                            // Deshabilitar input de archivo para documentos aprobados
                            if (fileInput) fileInput.disabled = true;
                            if (card) card.classList.add('opacity-75');
                        } else if (doc.estado === 'rechazado') {
                            badge.className = 'badge bg-danger';
                            badge.innerHTML = '<i class="bi bi-x-circle-fill me-1"></i>Rechazado';
                            
                            // Agregar comentarios si existen
                            if (doc.comentarios && doc.comentarios.trim() !== '') {
                                const cardBody = card ? card.querySelector('.card-body') : null;
                                if (cardBody) {
                                    // Eliminar comentarios anteriores si existen
                                    const existingComments = cardBody.querySelector('.alert-danger');
                                    if (existingComments) existingComments.remove();
                                    
                                    // Agregar nuevos comentarios
                                    const commentsDiv = document.createElement('div');
                                    commentsDiv.className = 'alert alert-danger mb-2';
                                    commentsDiv.innerHTML = `
                                        <small><strong><i class="bi bi-exclamation-triangle-fill me-1"></i>Motivo de rechazo:</strong> ${doc.comentarios}</small>
                                    `;
                                    cardBody.insertBefore(commentsDiv, cardBody.firstChild);
                                }
                            }
                            
                            // Asegurar que el input de archivo esté habilitado para re-subida
                            if (fileInput) {
                                fileInput.disabled = false;
                                fileInput.required = true; // Hacerlo obligatorio
                            }
                            if (card) {
                                card.classList.add('border-danger', 'border-2');
                                card.classList.remove('opacity-75');
                            }
                        } else if (doc.estado === 'en_revision') {
                            badge.className = 'badge bg-info';
                            badge.innerHTML = '<i class="bi bi-eye-fill me-1"></i>En revisión';
                            if (fileInput) fileInput.disabled = true;
                            if (card) card.classList.add('opacity-75');
                        }
                    }
                });
                
                // Mostrar información del folio existente
                fetch(`api_titulacion.php?action=folio_curp&curp=${datosAlumno.curp}`)
                    .then(response => response.json())
                    .then(folioData => {
                        if (folioData.success && folioData.data) {
                            document.getElementById('folio_generado').textContent = folioData.data.folio;
                            document.getElementById('estado_proceso').textContent = folioData.data.estado_proceso;
                            document.getElementById('info_folio').style.display = 'block';
                        }
                    });
            } else {
                console.error('Error:', data.error);
            }
        })
        .catch(error => {
            console.error('Error en cargarDocumentosExistentes:', error);
            alert('Error al cargar documentos existentes. Por favor, recarga la página.');
        });
}

// Función para mostrar documentos en la interfaz
function mostrarDocumentos(documentos) {
    const container = document.getElementById('documentos_requeridos');
    container.innerHTML = '';

    if (documentos.length === 0) {
        container.innerHTML = `
            <div class="alert alert-warning">
                <h5><i class="bi bi-exclamation-triangle-fill me-2"></i>No hay documentos configurados para esta modalidad</h5>
            </div>
        `;
        return;
    }

    const documentosDiv = document.createElement('div');
    documentosDiv.className = 'row';

    documentos.forEach(doc => {
        const col = document.createElement('div');
        col.className = 'col-md-6 mb-3';
        
        const estadoClass = doc.estado_actual === 'aprobado' ? 'success' : 
                          doc.estado_actual === 'rechazado' ? 'danger' : 
                          doc.estado_actual === 'en_revision' ? 'warning' : 'info';
        
        const estadoIcon = doc.estado_actual === 'aprobado' ? 'check-circle-fill' : 
                         doc.estado_actual === 'rechazado' ? 'x-circle-fill' : 
                         doc.estado_actual === 'en_revision' ? 'clock-fill' : 'upload';

        col.innerHTML = `
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="card-title">
                        <i class="bi bi-file-earmark-text me-2"></i>${doc.nombre_documento || doc.nombre}
                    </h6>
                    <div class="mb-2">
                        <span class="badge bg-${estadoClass}">
                            <i class="bi bi-${estadoIcon} me-1"></i>
                            ${doc.estado_actual === 'pendiente_subida' ? 'Pendiente de subida' : 
                              doc.estado_actual === 'pendiente' ? 'Pendiente de revisión' : 
                              doc.estado_actual}
                        </span>
                    </div>
                    
                    ${doc.estado_actual === 'pendiente_subida' ? `
                        <div class="mb-2">
                            <input type="file" class="form-control form-control-sm" id="doc-${doc.id}" 
                                   accept=".pdf,.jpg,.jpeg,.png" onchange="seleccionarArchivo(${doc.id}, this)">
                        </div>
                        <div id="info-archivo-${doc.id}" class="small text-muted" style="display:none;">
                            <i class="bi bi-paperclip me-1"></i><span id="nombre-archivo-${doc.id}"></span>
                        </div>
                    ` : `
                        <div class="mb-2">
                            <small class="text-muted">
                                ${doc.fecha_subida ? `Subido: ${new Date(doc.fecha_subida).toLocaleDateString()}` : ''}
                                ${doc.fecha_revision ? `<br>Revisado: ${new Date(doc.fecha_revision).toLocaleDateString()}` : ''}
                            </small>
                        </div>
                        ${doc.comentarios ? `
                            <div class="alert alert-warning small">
                                <strong>Comentarios:</strong> ${doc.comentarios}
                            </div>
                        ` : ''}
                    `}
                </div>
            </div>
        `;
        
        documentosDiv.appendChild(col);
    });

    container.appendChild(documentosDiv);
    
    // Habilitar botón de finalizar si todos están subidos
    verificarTodosDocumentosSubidos(documentos);
}

// Función para verificar si todos los documentos están subidos (para folios existentes)
function verificarTodosDocumentosSubidos(documentos) {
    // Esta función se mantiene para compatibilidad con folios existentes
    const todosSubidos = documentos.every(doc => 
        doc.estado_actual !== 'pendiente_subida' && doc.estado_actual !== 'pendiente'
    );
    
    const btnFinalizar = document.getElementById('btn_finalizar');
    if (btnFinalizar) {
        btnFinalizar.disabled = !todosSubidos;
    }
}

// Modificar la función cambiarPagina para cargar modalidades al llegar a página 4
function cambiarPagina(pagina) {
    console.log('cambiando a página:', pagina);
    
    // Ocultar todas las páginas
    document.querySelectorAll('.pagina-content').forEach(p => {
        p.classList.remove('active');
    });
    
    // Mostrar página seleccionada
    document.getElementById(`pagina-${pagina}`).classList.add('active');
    
    // Actualizar botones de navegación
    document.querySelectorAll('.btn-group button').forEach((btn, index) => {
        if (index < pagina) {
            btn.classList.remove('btn-outline-secondary');
            btn.classList.add('btn-primary');
        } else {
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-outline-secondary');
        }
        btn.disabled = index > pagina;
    });
    
    // Controlar visibilidad de la sección de búsqueda de folio
    const consultaFolioSection = document.getElementById('consulta_folio_section');
    if (consultaFolioSection) {
        if (pagina === 1) {
            // Mostrar sección de búsqueda en página 1
            consultaFolioSection.style.display = 'block';
        } else {
            // Ocultar sección de búsqueda en páginas 2, 3, 4
            consultaFolioSection.style.display = 'none';
        }
    }
    
    // Cargar datos específicos de la página
    if (pagina === 2 && datosAlumno) {
        console.log('Llamando a mostrarDatosAlumnoPagina2()');
        mostrarDatosAlumnoPagina2();
    } else if (pagina === 3) {
        console.log('Llegando a página 3');
        console.log('carrerasAlumno disponibles:', carrerasAlumno);
        if (carrerasAlumno) {
            console.log('Llamando a mostrarSeleccionCarreraPagina3()');
            mostrarSeleccionCarreraPagina3();
        } else {
            console.log('No hay carrerasAlumno, no se puede mostrar selección');
        }
    } else if (pagina === 4) {
        console.log('Llegando a página 4, datosAlumno:', datosAlumno);
        if (datosAlumno) {
            console.log('Llamando a cargarModalidades()');
            cargarModalidades();
        } else {
            console.error('No hay datosAlumno al llegar a página 4');
        }
    }
}
