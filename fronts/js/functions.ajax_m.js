
   
	function curpValida(curp) {
    var re = /^([A-Z][AEIOUX][A-Z]{2}\d{2}(?:0[1-9]|1[0-2])(?:0[1-9]|[12]\d|3[01])[HM](?:AS|B[CS]|C[CLMSH]|D[FG]|G[TR]|HG|JC|M[CNS]|N[ETL]|OC|PL|Q[TR]|S[PLR]|T[CSL]|VZ|YN|ZS)[B-DF-HJ-NP-TV-Z]{3}[A-Z\d])(\d)$/,
        validado = curp.match(re);
	
    if (!validado)  //Coincide con el formato general?
    	return false;
    
    //Validar que coincida el dígito verificador
    function digitoVerificador(curp17) {
        //Fuente https://consultas.curp.gob.mx/CurpSP/
        var diccionario  = "0123456789ABCDEFGHIJKLMNÑOPQRSTUVWXYZ",
            lngSuma      = 0.0,
            lngDigito    = 0.0;
        for(var i=0; i<17; i++)
            lngSuma = lngSuma + diccionario.indexOf(curp17.charAt(i)) * (18 - i);
        lngDigito = 10 - lngSuma % 10;
        if (lngDigito == 10) return 0;
        return lngDigito;
    }
  
    if (validado[2] != digitoVerificador(validado[1])) 
    	return false;
        
    return true; //Validado
}


//Handler para el evento cuando cambia el input
//Lleva la CURP a mayúsculas para validarlo
function validarInput(input) {
	
    var curp = input.value.toUpperCase();
	$("#login_username").val(curp);
    if (curpValida(curp)) { // Acá se comprueba
		$("#login_userbttn").show( "slide");
		$("#login_userbttn").prop( "disabled",false );
		
    } else {
    	$("#login_userbttn").hide("slide");
		$("#login_userbttn").prop( "disabled",true );
		
    }
     
}
	
	
function redirect_by_post(purl, pparameters, in_new_tab) {  //funcion para enviar por post desde javascript
			pparameters = (typeof pparameters == 'undefined') ? {} : pparameters;
			in_new_tab = (typeof in_new_tab == 'undefined') ? true : in_new_tab;
			var form = document.createElement("form");
			$(form).attr("id", "reg-form").attr("name", "reg-form").attr("action", purl).attr("method", "post").attr("enctype", "multipart/form-data");
			if (in_new_tab) {
					$(form).attr("target", "_blank");
			}
			$.each(pparameters, function(key) {
					$(form).append('<input type="text" name="' + key + '" value="' + this + '" />');
			});
			document.body.appendChild(form);
			form.submit();
			document.body.removeChild(form);
			return false;
}	
	



$(document).ready(function(){	
	
	$("#login_userbttn").hide();
	$("#msg_curp").hide();
	
   
    setTimeout(function() {
		$("#btn_registrarse").removeClass("btn-secondary");
		$("#btn_registrarse").addClass("btn_IniciaProceso");
		$("#btn_registrarse").addClass("btn-light");
	}, 1300);
   
   
	if ( $('#activo').val() == 0){
		$("#login_userbttn").hide();
	}
	
	
	$("#login_userbttn").prop( "disabled",true );
	
	var timeSlide = 500;
	
	$('#timer').hide(0);
	
	
	$("#login_username").keypress(function(e) {
        if(e.which == 13) {
		  return false;
        }
		
    });
	
	$('#login_userbttn').click(function(){
		
		$('#timer').fadeIn(200);
		$('#box-info').show();
		//$('#txtiniciasesion').html('Preparando información..');
		//$("#login_userbttn").prop( "disabled",true );
		$("#login_userbttn").hide();
		//$('#box-info').html('<div>Estoy en ello..</div>');
		var lacurp = $('#login_username').val().toUpperCase();
		redirect_by_post('menu', { tipo:0, curp:lacurp},false); //true es para abrir nueva pestaña,false en la misma  
	
	});	
	
		
	$(".ancla").click(function(evento){
	  //Anulamos la funcionalidad por defecto del evento
	  evento.preventDefault();
	  //Creamos el string del enlace ancla
	  var codigo = "#" + $(this).data("ancla");
	  //Funcionalidad de scroll lento para el enlace ancla en 3 segundos
	  //$("html,body").animate({scrollTop: $(codigo).offset().top}, 1000);
	  //$('html,body').animate({scrollTop: $(codigo).offset().top-$('#div_encabezado').height()}, 1000);
	  //var btnSel = $(this).attr("id");
	  //if (btnSel=="btn_registrarse"){ $('#login_username').focus();}
	  //$('#login_username').focus();
	   $("html,body").animate({scrollTop: $(codigo).offset().top}, 800, function(){
			$('#login_username').focus();
	   });
	  
	  
	});
	
	$("#login_username").on("focus",function(){
		setTimeout(function() {
			$("#msg_curp").show(1200,function(){
				$("#msg_curp").addClass("text-dark");
				setTimeout(function() {
					$("#msg_curp").removeClass("text-dark");
					$("#msg_curp").addClass("text-secondary");
				},1500);
			});
		}, 2200);
		
	});
	
	$('#iralicenciaturas').click(function(){
		window.location.href = "../licenciaturas/index.php";
	});
	
});




