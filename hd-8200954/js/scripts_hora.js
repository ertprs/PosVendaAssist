// -----------------------------------
// mascara hora em input text
// campo = document.<nomeForm>.<nomeCampo>
// -----------------------------------
function mascara_hora(campo)
{
   var myhora = '';
   myhora = myhora + campo.value;
   if (myhora.length == 2){
      myhora = myhora + ':';
      campo.value = myhora;
   }
   if (myhora.length == 5){
      verifica_hora(campo);
   }
}

function verifica_hora(campo)
{
   hrs = (campo.value.substring(0,2));
   min = (campo.value.substring(3,5));

   // alert('hrs '+ hrs);
   // alert('min '+ min);

   situacao = "";
   // verifica hora
   if ((hrs < 00 ) || (hrs > 23) || ( min < 00) ||( min > 59)){
      situacao = "falsa";
   }
   if (campo.value == "") {
      situacao = "falsa";
   }
   if (situacao == "falsa") {
      alert("Hora inválida!");
      campo.value = "";
      campo.focus();
   }
}


// ----------------------------------------
// auto tab
/*
   Auto tabbing script- By JavaScriptKit.com (http://www.javascriptkit.com)
   This credit MUST stay intact for use
*/
// ----------------------------------------
function autotab(original,destination)
{
   if (original.getAttribute&&original.value.length==original.getAttribute("maxlength"))
      destination.focus()
}