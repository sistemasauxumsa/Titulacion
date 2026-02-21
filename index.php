<?php
// Incluir configuración y consultas
$config = include('Consultas/configuracion_simple.php');
$redireccion_otroniv = $config['redireccion_otroniv'];
$curprediccionado = $config['curprediccionado'];
$cursoescolarinsc = $config['cursoescolarinsc'];
$paginas = $config['paginas'];
$total_paginas = $config['total_paginas'];
$pagina_actual = $config['pagina_actual'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width,height=device-height,initial-scale=1, shrink-to-fit=no">
	<title>Sistema de Titulación</title>
	
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
	
	<!-- jAlert - Comentado temporalmente -->
	<!--<link rel="stylesheet" href="fronts/jalert-master4/dist/jAlert.css"> 
	<script src="fronts/jalert-master4/dist/jAlert.min.js"></script>
	<script src="fronts/jalert-master4/dist/jAlert-functions.min.js"></script>-->
	
	<!-- CSS para este módulo -->
	<link href="fronts/css/index.css" rel="stylesheet">
	
	<!-- Funciones AJAX -->
	<script type="text/javascript" src="fronts/js/functions.ajax.js"></script>
	
	<!-- JavaScript de titulación -->
	<script type="text/javascript" src="fronts/js/titulacion.js"></script>
	
	<!-- Sistema de titulación -->
	<script type="text/javascript" src="fronts/js/sistema_titulacion.js"></script>
	
	<!-- Proceso de titulación -->
	<script type="text/javascript" src="fronts/js/titulacion_proceso.js"></script>
	
	<style>
		.pagina-content {
			display: none;
		}
		.pagina-content.active {
			display: block;
		}
	</style>
</head>

<body class="text-center">
	<script type="text/javascript"> 
		var redireccion_otroniv = "<?php echo $redireccion_otroniv; ?>";
		var curprediccionado = "<?php echo $curprediccionado; ?>";
		
		$(document).ready(function () {
			if (redireccion_otroniv==1){
				/*$.jAlert({
					'title': 'Bienvenido',
					'content': 'Has sido direccionado a PÁGINAS porque los datos de registro de la CURP corresponden a este nivel',
					'theme':'blue',
					'showAnimation': 'zoomInDown',
					'hideAnimation': 'zoomOutDown',
					'closeOnClick': 'true',
					'size': {'height': 'auto', 'width': '400px'},
					'onOpen': function(alert) {
						//setTimeout(function(){
						//    alert.closeAlert();
						//},790);
					}
				});*/
				alert('Has sido direccionado a PÁGINAS porque los datos de registro de la CURP corresponden a este nivel');
				if (curprediccionado!="0" || curprediccionado != "0"){
					$("#login_username").val(curprediccionado);
					$("#login_userbttn").show();
					$("#login_userbttn").prop("disabled",false);
				}
			}
		}); //fin de document ready
	</script>
	
	<div class="container">
		<!-- Proceso de Titulación (sección principal) -->
		<div id="proceso_titulacion">
		<div class="card mt-3 mb-3 border-0 card-shadow text-secondary" 
		style="background: url(fronts/imagenes/index.jpg) no-repeat center top fixed;">
			<div class="card-header text-white border-0 backcolor-header">
				
				<div class="row mt-2 mb-3 align-items-center">
					<div class="col-lg-3 col-md-4 col-sm-12">
						<img class="logo_img" src="fronts/imagenes/logo_white.png" alt="logo" />
					</div>
					<div class="col-lg-9 col-md-8 col-sm-12 head-text">
						<div class="title-1 fw-bold">Proceso de Titulación</div>
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
			<!-- Navegación entre páginas -->
			<div class="row mb-4">
				<div class="col-12">
					<div class="btn-group w-100" role="group">
						<button type="button" class="btn btn-primary" onclick="cambiarPagina(1)">
							<i class="bi-search"></i>
							<span class="d-none d-md-inline">Inicio del proceso - Verificación CURP</span>
						</button>
						<button type="button" class="btn btn-outline-secondary" onclick="cambiarPagina(2)" disabled>
							<i class="bi-person-fill"></i>
							<span class="d-none d-md-inline">Datos del Alumno</span>
						</button>
						<button type="button" class="btn btn-outline-secondary" onclick="cambiarPagina(3)" disabled>
							<i class="bi-book-fill"></i>
							<span class="d-none d-md-inline">Selección de Carrera</span>
						</button>
						<button type="button" class="btn btn-outline-secondary" onclick="cambiarPagina(4)" disabled>
							<i class="bi-file-earmark-arrow-up-fill"></i>
							<span class="d-none d-md-inline">Subida de Documentos</span>
						</button>
					</div>
				</div>
			</div>
			
			<!-- Sección de Consulta de Folio (dentro del mismo container) -->
			<div id="consulta_folio_section" class="card mb-4 shadow-sm bg-light" style="border-color: #0056b3 !important; border-width: 2px !important;">
				<div class="card-body py-3">
					<div class="row align-items-center">
						<div class="col-auto">
							<div class="bg-primary text-white rounded-circle p-2 me-3">
								<i class="bi bi-search fs-5"></i>
							</div>
						</div>
						<div class="col">
							<div class="row align-items-center">
								<div class="col-md-6">
									<h6 class="mb-1 text-muted">¿Ya tienes un folio?</h6>
									<p class="mb-0 small text-muted">Consulta el estado de tu proceso</p>
								</div>
								<div class="col-md-6">
									<div class="input-group input-group-sm">
										<input type="text" class="form-control" id="folio_consulta" placeholder="Ej: 2026-00001" maxlength="11">
										<button class="btn btn-outline-primary" type="button" onclick="consultarFolio()">
											<i class="bi bi-search"></i>
										</button>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
				
				<!-- Contenido dinámico según página -->
				<div class="row mb-3 g-3">
					<!-- Página 1: Verificación CURP -->
					<div id="pagina-1" class="pagina-content active">
						<div class="col-12">
							<div class="card step-card">
								<div class="card-body">
									<div class="border-0 text-start hstack text-dark" style="font-size:1.3em;">
										<i class="bi-search step-icon me-2"></i>
										<span class="text-secondary fw-bolder">Inicio de proceso - Verificación CURP</span>
									</div>
									<div class="text-start text-secondary mt-3">
										<p class="card-text fs-5">Ingresa tu CURP para verificar tu información en el sistema de titulación.</p>
										<div class="alert alert-info">
											<h5><i class="bi bi-info-circle-fill me-2"></i>Instrucciones para esta página:</h5>
											<ul class="mb-0">
												<li>Escribe tu CURP de 18 caracteres</li>
												<li>El sistema buscará tu información en la base de datos</li>
												<li>Si eres encontrado, pasarás a la página de datos</li>
												<li>Si no eres encontrado, recibirás un mensaje de error</li>
											</ul>
										</div>
										<div class="mt-3">
											<div class="row">
												<div class="col-md-8 mx-auto">
													<div class="input-group">
														<input type="text" id="curp_input" class="form-control" placeholder="Escribe tu CURP" maxlength="18" />
														<button class="btn btn-primary" type="button" id="btn_verificar_curp" disabled onclick="verificarCURP()">
															<i class="bi bi-search me-2"></i>Verificar
														</button>
													</div>
													<div class="form-text mt-2" id="msg_curp_validacion">El botón se habilitará cuando escribas una CURP válida de 18 caracteres.</div>
												</div>
											</div>
											<div id="resultado_curp" class="mt-3"></div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>

					<!-- Página 2: Datos del Alumno -->
					<div id="pagina-2" class="pagina-content">
						<div class="col-12">
							<div class="card step-card">
								<div class="card-body">
									<div class="border-0 text-start hstack text-dark" style="font-size:1.3em;">
										<i class="bi-person-check step-icon me-2"></i>
										<span class="text-secondary fw-bolder">Datos del Alumno</span>
									</div>
									<div class="text-start text-secondary mt-3">
										<p class="card-text fs-5">Aquí se mostrará tu información personal y las carreras en las que estás inscrito para el proceso de titulación.</p>
										<div class="alert alert-info">
											<h5><i class="bi bi-info-circle-fill me-2"></i>Datos del Alumno:</h5>
											<p>Si tus datos son correctos, podrás continuar con la selección de carrera para tu proceso de titulación.</p>
										</div>
										<div id="datos_alumno_mostrados" class="mt-3">
											<!-- Los datos del alumno se mostrarán aquí -->
										</div>
										<div class="mt-3">
											<button class="btn btn-secondary me-2" onclick="cambiarPagina(1)">
												<i class="bi bi-arrow-left-circle me-2"></i>Regresar
											</button>
											<button class="btn btn-success" onclick="cambiarPagina(3)">
												<i class="bi bi-arrow-right-circle me-2"></i>Continuar
											</button>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>

					<!-- Página 3: Selección de Carrera -->
					<div id="pagina-3" class="pagina-content">
						<div class="col-12">
							<div class="card step-card">
								<div class="card-body">
									<div class="border-0 text-start hstack text-dark" style="font-size:1.3em;">
										<i class="bi-book step-icon me-2"></i>
										<span class="text-secondary fw-bolder">Selección de Carrera</span>
									</div>
									<div class="text-start text-secondary mt-3">
										<p class="card-text fs-5">Selecciona la carrera para la cual deseas tramitar tu titulación.</p>
										<div class="alert alert-info">
											<h5><i class="bi bi-info-circle-fill me-2"></i>Selección de Carrera:</h5>
											<p>Elige la carrera correcta para tu proceso de titulación. Esta selección es importante para generar tus documentos correctamente.</p>
										</div>
										<div id="seleccion_carrera" class="mt-3">
											<!-- Las carreras se mostrarán aquí para selección -->
										</div>
										<div class="mt-3">
											<button class="btn btn-secondary me-2" onclick="cambiarPagina(2)">
												<i class="bi bi-arrow-left-circle me-2"></i>Regresar
											</button>
											<button class="btn btn-success" onclick="validarYContinuar()">
												<i class="bi bi-arrow-right-circle me-2"></i>Continuar
											</button>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>

					<!-- Página 4: Subida de Documentos -->
					<div id="pagina-4" class="pagina-content">
						<div class="col-12">
							<div class="card step-card">
								<div class="card-body">
									<div class="border-0 text-start hstack text-dark" style="font-size:1.3em;">
										<i class="bi-file-earmark-arrow-up step-icon me-2"></i>
										<span class="text-secondary fw-bolder">Subida de Documentos</span>
									</div>
									<div class="text-start text-secondary mt-3">
										<p class="card-text fs-5">Selecciona tu modalidad y sube los documentos necesarios para tu proceso de titulación.</p>
										
										<!-- Selección de Modalidad -->
										<div class="mb-4">
											<label for="select_modalidad" class="form-label fw-bold">
												<i class="bi-book me-2"></i>Selecciona Modalidad de Titulación:
											</label>
											<select class="form-select" id="select_modalidad" onchange="cargarDocumentos()">
												<option value="">Cargando modalidades...</option>
											</select>
										</div>

										<!-- Documentos Requeridos -->
										<div id="documentos_requeridos" class="mb-4">
											<div class="alert alert-info">
												<h5><i class="bi bi-info-circle-fill me-2"></i>Selecciona una modalidad para ver los documentos requeridos</h5>
											</div>
										</div>

										<!-- Información del Folio -->
										<div id="info_folio" class="alert alert-success" style="display: none;">
											<h5><i class="bi bi-check-circle-fill me-2"></i>Folio Generado</h5>
											<p><strong>Folio:</strong> <span id="folio_generado"></span></p>
											<p><strong>Estado:</strong> <span id="estado_proceso">Iniciado</span></p>
										</div>

										<!-- Botones de navegación -->
										<div class="mt-3">
											<button class="btn btn-secondary me-2" onclick="cambiarPagina(3)">
												<i class="bi bi-arrow-left-circle me-2"></i>Regresar
											</button>
											<button class="btn btn-primary" onclick="finalizarProceso()" id="btn_finalizar" disabled>
												<i class="bi bi-check-circle me-2"></i>Finalizar proceso
											</button>
										</div>
									</div>
									
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		
		</div>
		
		</div> <!-- Fin de proceso_titulacion -->
		
	
	<script>
	// Función para consultar folio
	function consultarFolio() {
		const folio = document.getElementById('folio_consulta').value.trim();
		
		if (!folio) {
			alert('Por favor ingresa un número de folio');
			return;
		}
		
		// Validar formato del folio (YYYY-NNNNN)
		const folioRegex = /^\d{4}-\d{5}$/;
		if (!folioRegex.test(folio)) {
			alert('Formato de folio inválido. Debe ser: YYYY-NNNNN (ej: 2026-00001)');
			return;
		}
		
		// Guardar folio en sessionStorage y redirigir
		sessionStorage.setItem('folio_consulta', folio);
		window.location.href = `consulta_folio.php`;
	}

	// Permitir consultar con Enter
	document.getElementById('folio_consulta').addEventListener('keypress', function(e) {
		if (e.key === 'Enter') {
			consultarFolio();
		}
	});
	</script>

</body>
</html>
