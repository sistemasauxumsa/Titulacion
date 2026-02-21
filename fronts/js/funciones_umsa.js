
//esta funcion solo valida el FORMATO de la fecha en dd/mm/yyyy
function validarFormatoFecha(campo) {
      var RegExPattern = /^\d{1,2}\/\d{1,2}\/\d{2,4}$/;
      if ((campo.match(RegExPattern)) && (campo!='')) {
            return true;
      } else {
            return false;
      }
}

//esta funcion checa si la fecha es real,si existe, en formato dd/mm/yyyy
function existeFecha(fecha){
      var fechaf = fecha.split("/");
      var day = fechaf[0];
      var month = fechaf[1];
	  if (month > 12) { return false;}
      var year = fechaf[2];
      var date = new Date(year,month,'0');
      if((day-0)>(date.getDate()-0)){
            return false;
      }
      return true;
}


//var fecha = "13/09/1985";
//esta funcion utiliza las 2 funciones anteriores
function ValidarFecha(fecha){
   if(validarFormatoFecha(fecha)){
      if(existeFecha(fecha)){
			return true;
      }else{
			return false;
      }
   }else
   {	  return false;
   }
}

//compara si la fecha 1 es mayor que la fecha2, en formato dd/mm/yyyy
function Validar2Fechas(date1,date2){
      var fecha1 = date1.split("/");
	  var fecha2 = date2.split("/");
	  var f1 = new Date(fecha1[2], fecha1[1]-1, fecha1[0]); //se genera la fecha, pasando año, mes y dia, a mes se le resta 1 porque enero=0
      var f2 = new Date(fecha2[2], fecha2[1]-1, fecha2[0]);
 
      if(f1 > f2){
          return true; //la fecha 1 es mayor que la 2
      }  else { return false; } //la fecha 1 es igual o menor que la 2
}

//funcion que prepara un numero con comas como separador de miles y . como separador de decimales para
//que se pueda realizar alguna operacion con ella, si el importe no es numerico, el importe será 0
function MyparseFloat(importe){
		var valor = importe;
		if (valor== 0 || valor =="0"){ return 0;}
		valor = valor.replace(',','');
		if ( !$.isNumeric( valor ) ) { valor=0; }
	    valor = parseFloat(valor);
		return valor;
}



//funcion para formatear numeros
var formatNumber = {
 separador: ",", // separador para los miles
 sepDecimal: '.', // separador para los decimales
 formatear:function (num){
  num +='';
  var splitStr = num.split('.');
  var splitLeft = splitStr[0];
  var splitRight = splitStr.length > 1 ? this.sepDecimal + splitStr[1] : '';
  var regx = /(\d+)(\d{3})/;
  while (regx.test(splitLeft)) {
  splitLeft = splitLeft.replace(regx, '$1' + this.separador + '$2');
  }
  return this.simbol + splitLeft  +splitRight;
 },
 new:function(num, simbol){
  this.simbol = simbol ||'';
  return this.formatear(num);
 }
}  //ejemplo de uso: formatNumber.new(123456779.18, "$") // retorna "$123,456,779.18"
//otro ejemplo: formatNumber.new(123456779) // retorna "$123,456,779"
