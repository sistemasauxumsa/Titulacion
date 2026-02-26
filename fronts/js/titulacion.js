// Variables globales para mantener datos
var datosAlumno = null;
var carrerasAlumno = null;

function validarYContinuar() {
    console.log('validarYContinuar() iniciada');
    console.log('datosAlumno:', datosAlumno);
    
    // Si ya hay una carrera asignada (caso de una sola carrera), verificar que esté completa
    if (datosAlumno.id_carrera) {
        console.log('Carrera ya asignada:', datosAlumno.id_carrera);
        console.log('Continuando a página 4...');
        cambiarPagina(4);
        return;
    }
    
    // Si no hay carrera asignada, buscar el radio button seleccionado (caso de múltiples carreras)
    var carreraSeleccionada = $('input[name="carrera_seleccionada"]:checked').val();
    if (!carreraSeleccionada) {
        alert('Por favor selecciona una carrera antes de continuar.');
        return;
    }
    
    console.log('Carrera seleccionada (radio):', carreraSeleccionada);
    
    // Buscar la carrera seleccionada en el array de carreras
    var carreraInfo = carrerasAlumno.find(c => c.id == carreraSeleccionada);
    
    console.log('Estructura real de carreraInfo:', carreraInfo);
    console.log('Estructura real de carrerasAlumno:', carrerasAlumno);
    
    if (carreraInfo) {
        // Guardar la carrera seleccionada en datosAlumno
        datosAlumno.id_carrera = carreraInfo.id;
        datosAlumno.nombre_carrera = carreraInfo.nombre;
        datosAlumno.nivel = carreraInfo.nivel;

        // Guardar el niv numérico real de la carrera (backend)
        if (carreraInfo.niv !== undefined && carreraInfo.niv !== null && carreraInfo.niv !== '') {
            datosAlumno.niv = parseInt(carreraInfo.niv, 10);
        } else {
            // Fallback si solo viene texto
            if (carreraInfo.nivel === 'Licenciatura') {
                datosAlumno.niv = 5;
            } else if (carreraInfo.nivel === 'Maestría') {
                datosAlumno.niv = 6;
            }
        }
        
        // Determinar ID de grado académico según el nivel
        if (carreraInfo.nivel === 'Licenciatura') {
            datosAlumno.id_grado_academico = 1;
        } else if (carreraInfo.nivel === 'Maestría') {
            datosAlumno.id_grado_academico = 3;
        } else if (carreraInfo.nivel === 'Doctorado') {
            datosAlumno.id_grado_academico = 4;
        }
        
        console.log('datosAlumno después de guardar:', datosAlumno);
        console.log('Carrera seleccionada y guardada:', datosAlumno.id_carrera, 'Grado:', datosAlumno.id_grado_academico);
    } else {
        console.error('No se encontró información de la carrera seleccionada');
    }
    
    // Continuar a la página 4
    cambiarPagina(4);
}

function cambiarPagina(pagina) {
    // Validar que se seleccione carrera si va de página 3 a 4
    if (pagina === 4) {
        var carreraSeleccionada = $('input[name="carrera_seleccionada"]:checked').val();
        if (!carreraSeleccionada && !datosAlumno.id_carrera) {
            alert('Por favor selecciona una carrera antes de continuar.');
            return;
        }
    }
    
    // Ocultar todas las páginas
    $('.pagina-content').hide();
    
    // Mostrar la página seleccionada
    $('#pagina-' + pagina).show();
    
    // Actualizar botones de navegación
    actualizarBotonesNavegacion(pagina);
    
    // Cargar datos específicos de la página
    if (pagina === 2 && datosAlumno) {
        mostrarDatosAlumnoPagina2();
    } else if (pagina === 3 && carrerasAlumno) {
        mostrarSeleccionCarreraPagina3();
    }
}

function actualizarBotonesNavegacion(paginaActual) {
    // Actualizar botones del grupo de navegación
    $('.btn-group button').each(function(index) {
        var numPagina = index + 1;
        $(this).removeClass('btn-primary btn-success btn-outline-secondary');
        
        if (numPagina < paginaActual) {
            $(this).addClass('btn-success');
        } else if (numPagina === paginaActual) {
            $(this).addClass('btn-primary');
        } else {
            $(this).addClass('btn-outline-secondary');
        }
    });
    
    // Actualizar barra de progreso
    var progreso = (paginaActual / 4) * 100;
    $('.progress-bar').css('width', progreso + '%')
        .attr('aria-valuenow', paginaActual)
        .text('Página ' + paginaActual + ' de 4');
}

function verificarCURP() {
    var curp = document.getElementById('curp_input').value.toUpperCase();
    
    if (curp.length !== 18) {
        mostrarResultado('error', 'La CURP debe tener exactamente 18 caracteres.');
        return;
    }
    
    // Limpiar cualquier folio completado existente al verificar nueva CURP
    sessionStorage.removeItem('folio_completado');
    sessionStorage.removeItem('curp_procesada');
    sessionStorage.removeItem('folio_timestamp');
    
    // Mostrar loading
    mostrarResultado('info', '<div class="spinner-border spinner-border-sm me-2" role="status"></div>Buscando en la base de datos...');
    
    // Realizar consulta AJAX a la base de datos
    $.ajax({
        url: 'Consultas/buscar_alumno_v2.php',
        type: 'POST',
        data: { curp: curp },
        dataType: 'json',
        success: function(response) {
            console.log('Respuesta AJAX recibida:', response);
            
            if (response.success) {
                // Guardar datos globalmente
                datosAlumno = response.alumno;
                carrerasAlumno = response.carreras || [];
                
                console.log('datosAlumno guardados:', datosAlumno);
                console.log('carrerasAlumno guardadas:', carrerasAlumno);
                
                // Verificar si hay carreras válidas
                if (!carrerasAlumno || carrerasAlumno.length === 0) {
                    mostrarResultado('error', 'No tienes carreras válidas para titularse. Asegúrate de haber completado todos tus requisitos académicos y servicio social.<br><br><button class="btn btn-warning btn-sm" onclick="mostrarSoporte()"><i class="bi bi-headset me-2"></i>Contacta a Soporte</button>');
                    return;
                }
                
                // Mostrar éxito breve y cambiar a página 2 inmediatamente
                mostrarResultado('success', '<i class="bi bi-check-circle-fill me-2"></i>¡Alumno encontrado! Redirigiendo a tus datos...');
                
                setTimeout(function() {
                    cambiarPagina(2);
                }, 1500);
            } else {
                // Verificar tipo de error
                if (response.es_adeudo) {
                    mostrarAdeudos(response.adeudos, response.importe_total, response.cantidad_adeudos);
                } else if (response.es_servicio_social) {
                    mostrarServicioSocial(response.servicio_social);
                } else {
                    // Otro tipo de error
                    mostrarResultado('error', response.message || 'CURP no encontrada');
                }
            }
        },
        error: function(xhr, status, error) {
            console.error('Error en AJAX:', xhr, status, error);
            console.error('Respuesta texto:', xhr.responseText);
            mostrarResultado('error', 'Error al verificar CURP. Intente nuevamente.');
        }
    });
}

function mostrarServicioSocial(servicio_social) {
    var resultadoDiv = document.getElementById('resultado_curp');
    
    var serviciosHtml = '<div class="mt-3"><h6><i class="bi bi-people-fill me-2"></i>Detalle de Servicio Social:</h6><div class="list-group">';
    
    servicio_social.detalle.forEach(function(servicio) {
        var estadoBadge = servicio.concluido == 1 ? 'bg-success' : 
                         (servicio.estatus == 1 ? 'bg-warning' : 'bg-secondary');
        
        serviciosHtml += `
            <div class="list-group-item">
                <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1">${servicio.nombre_carrera}</h6>
                    <span class="badge ${estadoBadge} rounded-pill">${servicio.estado_descripcion}</span>
                </div>
                <p class="mb-1">
                    <small class="text-muted">
                        <strong>Tipo:</strong> Servicio Social | 
                        <strong>Estatus:</strong> ${servicio.estatus == 1 ? 'Activo' : 'Inactivo'} | 
                        <strong>Concluido:</strong> ${servicio.concluido == 1 ? 'Sí' : 'No'}
                    </small>
                </p>
            </div>
        `;
    });
    
    serviciosHtml += '</div></div>';
    
    resultadoDiv.innerHTML = `
        <div class="alert alert-warning" role="alert">
            <h5><i class="bi bi-exclamation-triangle-fill me-2"></i>No puedes tramitar tu titulación</h5>
            <p class="mb-2">Tienes <strong>${servicio_social.total_servicios} servicio(s) social(es)</strong> registrado(s), pero <strong>ninguno completado</strong>.</p>
            <p class="mb-3">Debes completar tu servicio social antes de poder continuar con el proceso de titulación.</p>
            ${serviciosHtml}
            <div class="mt-3">
                <button class="btn btn-warning btn-sm" onclick="mostrarSoporte()">
                    <i class="bi bi-headset me-2"></i>Contacta a Soporte
                </button>
                <button class="btn btn-info btn-sm ms-2" onclick="location.reload()">
                    <i class="bi bi-arrow-clockwise me-2"></i>Verificar nuevamente
                </button>
            </div>
        </div>
    `;
}

function mostrarAdeudos(adeudos, importe_total, cantidad_adeudos) {
    var resultadoDiv = document.getElementById('resultado_curp');
    
    var adeudosHtml = '<div class="mt-3"><h6><i class="bi bi-exclamation-triangle-fill me-2"></i>Detalle de adeudos:</h6><div class="list-group">';
    
    adeudos.forEach(function(adeudo) {
        adeudosHtml += `
            <div class="list-group-item">
                <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1">${adeudo.nombre}</h6>
                    <span class="badge bg-danger rounded-pill">$${parseFloat(adeudo.importe).toFixed(2)}</span>
                </div>
                <p class="mb-1">
                    <small class="text-muted">
                        <strong>Nivel:</strong> ${adeudo.nivel} | 
                        <strong>Vencimiento:</strong> ${adeudo.fecha_vencimiento} | 
                        <strong>Importe original:</strong> $${parseFloat(adeudo.importe_original).toFixed(2)}
                    </small>
                </p>
            </div>
        `;
    });
    
    adeudosHtml += '</div></div>';
    
    resultadoDiv.innerHTML = `
        <div class="alert alert-danger" role="alert">
            <h5><i class="bi bi-exclamation-triangle-fill me-2"></i>No puedes tramitar tu titulación</h5>
            <p class="mb-2">Tienes <strong>${cantidad_adeudos} adeudo(s) pendiente(s)</strong> por un total de <strong>$${parseFloat(importe_total).toFixed(2)}</strong>.</p>
            <p class="mb-3">Debes regularizar tus adeudos antes de poder continuar con el proceso de titulación.</p>
            ${adeudosHtml}
            <div class="mt-3">
                <button class="btn btn-warning btn-sm" onclick="mostrarSoporte()">
                    <i class="bi bi-headset me-2"></i>Contacta a Soporte para Pago
                </button>
                <button class="btn btn-info btn-sm ms-2" onclick="location.reload()">
                    <i class="bi bi-arrow-clockwise me-2"></i>Verificar nuevamente
                </button>
            </div>
        </div>
    `;
}

function mostrarDatosAlumnoPagina2() {
    console.log('mostrarDatosAlumnoPagina2() llamada');
    console.log('datosAlumno:', datosAlumno);
    console.log('carrerasAlumno:', carrerasAlumno);
    
    if (!datosAlumno) {
        console.log('No hay datosAlumno');
        return;
    }
    
    var carrerasHtml = '';
    if (carrerasAlumno && carrerasAlumno.length > 0) {
        carrerasHtml = '<div class="mt-3"><h6><i class="bi bi-book-fill me-2"></i>Carreras inscritas:</h6><ul class="list-group">';
        carrerasAlumno.forEach(function(carrera) {
            carrerasHtml += '<li class="list-group-item d-flex justify-content-between align-items-center"><span>' + carrera.nombre + '</span><span class="badge bg-primary rounded-pill">' + carrera.nivel + '</span></li>';
        });
        carrerasHtml += '</ul></div>';
    }
    
    document.getElementById('datos_alumno_mostrados').innerHTML = `
        <div class="alert alert-success">
            <h5><i class="bi bi-person-check-fill me-2"></i>Datos del Alumno</h5>
            <div class="row mt-3">
                <div class="col-md-6">
                    <strong>Nombre completo:</strong><br>
                    ${datosAlumno.nombres} ${datosAlumno.apaterno} ${datosAlumno.amaterno}
                </div>
                <div class="col-md-6">
                    <strong>CURP:</strong><br>
                    ${datosAlumno.curp}
                </div>
            </div>
            ${carrerasHtml}
        </div>
    `;
    
    console.log('Datos del alumno mostrados en página 2');
}

function mostrarSeleccionCarreraPagina3() {
    try {
        console.log('mostrarSeleccionCarreraPagina3() iniciada');
        console.log('carrerasAlumno:', carrerasAlumno);

        if (!carrerasAlumno || carrerasAlumno.length === 0) {
            console.log('No hay carrerasAlumno');
            document.getElementById('seleccion_carrera').innerHTML = '';
            return;
        }

        // Si solo hay una carrera, asignar automáticamente y mostrar mensaje
        if (carrerasAlumno.length === 1) {
            console.log('Solo hay una carrera, asignando automáticamente');
            console.log('carrerasAlumno[0]:', carrerasAlumno[0]);

            // Asignar la única carrera inmediatamente
            datosAlumno.id_carrera = carrerasAlumno[0].id;
            datosAlumno.nombre_carrera = carrerasAlumno[0].nombre;
            datosAlumno.nivel = carrerasAlumno[0].nivel;

            // Guardar el niv numérico
            if (carrerasAlumno[0].niv !== undefined && carrerasAlumno[0].niv !== null && carrerasAlumno[0].niv !== '') {
                datosAlumno.niv = parseInt(carrerasAlumno[0].niv, 10);
                // Asignar id_grado_academico según el niv
                if (datosAlumno.niv === 5) {
                    datosAlumno.id_grado_academico = 1; // Licenciatura
                } else if (datosAlumno.niv === 6) {
                    datosAlumno.id_grado_academico = 3; // Maestría
                }
            } else {
                // Fallback si solo viene texto
                if (carrerasAlumno[0].nivel === 'Licenciatura') {
                    datosAlumno.niv = 5;
                    datosAlumno.id_grado_academico = 1;
                } else if (carrerasAlumno[0].nivel === 'Maestría') {
                    datosAlumno.niv = 6;
                    datosAlumno.id_grado_academico = 3;
                }
            }

            console.log('Carrera asignada automáticamente:', datosAlumno.id_carrera, 'niv:', datosAlumno.niv);
            console.log('datosAlumno completo:', datosAlumno);

            // Mostrar mensaje de carrera preseleccionada
            console.log('✅ Mostrando mensaje de carrera preseleccionada...');
            var seleccionHtml = '<div class="alert alert-info mt-3"><i class="bi bi-info-circle-fill me-2"></i>Carrera preseleccionada: <strong>' + carrerasAlumno[0].nombre + '</strong> (' + carrerasAlumno[0].nivel + ')</div>';
            seleccionHtml += '<div class="mt-2"><small class="text-muted">Haz clic en "Continuar" para proceder con esta carrera.</small></div>';
            document.getElementById('seleccion_carrera').innerHTML = seleccionHtml;
            console.log('✅ Mensaje de carrera preseleccionada mostrado correctamente');
            return;
        }

        // Si hay múltiples carreras, mostrar selección con radios
        var carrerasHtml = '<div class="mt-3"><h6><i class="bi bi-book-fill me-2"></i>Selecciona tu carrera:</h6><div class="list-group">';
        carrerasAlumno.forEach(function(carrera, index) {
            var badge = (carrera.niv !== undefined && carrera.niv !== null && carrera.niv !== '') ? ('Nivel ' + carrera.niv) : (carrera.nivel || '');
            carrerasHtml += `
                <label class="list-group-item list-group-item-action">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <input type="radio" name="carrera_seleccionada" value="${carrera.id}" class="form-check-input me-2">
                            <strong>${carrera.nombre}</strong>
                        </div>
                        <span class="badge bg-primary rounded-pill">${badge}</span>
                    </div>
                </label>
            `;
        });
        carrerasHtml += '</div></div>';

        document.getElementById('seleccion_carrera').innerHTML = carrerasHtml;
    } catch (error) {
        console.error('ERROR en mostrarSeleccionCarreraPagina3:', error);
        console.error('Stack trace:', error.stack);
    }
}

function mostrarDatosAlumno(alumno, carreras) {
    var resultadoDiv = document.getElementById('resultado_curp');
    
    var carrerasHtml = '';
    if (carreras && Object.keys(carreras).length > 0) {
        carrerasHtml = '<div class="mt-3"><h6><i class="bi bi-book-fill me-2"></i>Carreras inscritas:</h6><ul class="list-group">';
        Object.keys(carreras).forEach(function(key) {
            var carrera = carreras[key];
            carrerasHtml += '<li class="list-group-item d-flex justify-content-between align-items-center"><span>' + carrera.nom + '</span><span class="badge bg-primary rounded-pill">Nivel ' + carrera.niv + '</span></li>';
        });
        carrerasHtml += '</ul></div>';
    } else {
        carrerasHtml = '<div class="alert alert-warning mt-3"><i class="bi bi-exclamation-triangle-fill me-2"></i>No se encontraron carreras de nivel 5 o superior inscritas.</div>';
    }
    
    resultadoDiv.innerHTML = `
        <div class="alert alert-success" role="alert">
            <h5><i class="bi bi-person-check-fill me-2"></i>Alumno Encontrado</h5>
            <div class="row mt-3">
                <div class="col-md-6">
                    <strong>Nombre completo:</strong><br>
                    ${alumno.nombres} ${alumno.apaterno} ${alumno.amaterno}
                </div>
                <div class="col-md-6">
                    <strong>CURP:</strong><br>
                    ${alumno.curp}
                </div>
            </div>
            ${carrerasHtml}
            <div class="mt-3">
                <button class="btn btn-success" onclick="cambiarPagina(2)">
                    <i class="bi bi-arrow-right-circle me-2"></i>Continuar con el proceso
                </button>
            </div>
        </div>
    `;
}

function mostrarSoporte() {
    alert('Si eres un caso extemporáneo de titulación, por favor contacta a soporte técnico:\n\n' +
          'Correo: controlescolar@umsa.edu.mx\n' +
          'Teléfono: (999) XXX-XXXX\n' +
          'Horario: Lunes a Viernes 9:00 AM - 6:00 PM\n\n' +
          'Menciona que eres un caso extemporáneo de titulación y proporciona tu CURP.');
}

function mostrarResultado(tipo, mensaje) {
    var resultadoDiv = document.getElementById('resultado_curp');
    var alertClass = tipo === 'success' ? 'alert-success' : (tipo === 'info' ? 'alert-info' : 'alert-danger');
    var iconClass = tipo === 'success' ? 'bi-check-circle-fill' : (tipo === 'info' ? 'bi-hourglass-split' : 'bi-x-circle-fill');
    
    resultadoDiv.innerHTML = `
        <div class="alert ${alertClass}" role="alert">
            <h5><i class="bi ${iconClass} me-2"></i>${tipo === 'success' ? 'Éxito' : (tipo === 'info' ? 'Procesando' : 'Error')}</h5>
            <p>${mensaje}</p>
        </div>
    `;
}

// function finalizarProceso() {
//     $.jAlert({
//         'title': '¡Proceso Completado!',
//         'content': 'Felicidades, has completado el proceso de titulación. Tu progreso ha sido guardado.',
//         'theme': 'green',
//         'showAnimation': 'zoomInDown',
//         'hideAnimation': 'zoomOutDown',
//         'closeOnClick': 'true',
//         'size': {'height': 'auto', 'width': '400px'},
//         'onClose': function() {
//             // Opcional: redirigir a una página de confirmación
//             // window.location.href = 'confirmacion.php';
//         }
//     });
// }

// Inicialización cuando el documento está listo
$(document).ready(function() {
    // Comportamiento del botón principal para iniciar en página 1
    $('#btn_registrarse').click(function() {
        cambiarPagina(1);
    });
    
    // Validación de CURP en tiempo real
    $('#curp_input').on('input', function() {
        var curp = $(this).val().toUpperCase();
        $(this).val(curp);
        
        var btnVerificar = $('#btn_verificar_curp');
        var msgValidacion = $('#msg_curp_validacion');
        
        console.log('CURP:', curp, 'Longitud:', curp.length);
        
        // Primero validar longitud
        if (curp.length < 18) {
            btnVerificar.prop('disabled', true);
            $(this).removeClass('is-valid is-invalid');
            msgValidacion.text('Faltan ' + (18 - curp.length) + ' caracteres.');
            msgValidacion.removeClass('text-success text-danger').addClass('text-muted');
            return;
        }
        
        // Si tiene 18 caracteres, validar formato
        if (curp.length === 18) {
            try {
                var isValid = curpValida(curp);
                console.log('curpValida result:', isValid);
                
                if (isValid) {
                    btnVerificar.prop('disabled', false);
                    $(this).removeClass('is-invalid').addClass('is-valid');
                    msgValidacion.text('¡CURP válida! Puedes continuar.');
                    msgValidacion.removeClass('text-muted text-danger').addClass('text-success');
                    console.log('✅ Botón habilitado');
                } else {
                    btnVerificar.prop('disabled', true);
                    $(this).removeClass('is-valid').addClass('is-invalid');
                    msgValidacion.text('CURP inválida. Revisa el formato.');
                    msgValidacion.removeClass('text-muted text-success').addClass('text-danger');
                    console.log('❌ Botón deshabilitado - CURP inválida');
                }
            } catch (error) {
                console.error('Error en validación:', error);
                btnVerificar.prop('disabled', false); // Habilitar por si falla la función
                msgValidacion.text('CURP con 18 caracteres. Puedes intentar verificar.');
                msgValidacion.removeClass('text-danger').addClass('text-warning');
            }
        }
    });
    
});
