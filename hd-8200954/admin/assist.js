// ************************************
// Criada em 13/04/2004 - Ricardo
// Verifica��o de tamanho m�nimo do campo
// Usar: onClick="javascript:fnc_tamanho_minimo(document.form.campo, 3);"
// ************************************
function fnc_tamanho_minimo(campo, caracteres)
{
	if (campo.value.length < caracteres)
	{
		alert('Para uma busca mais apurada digite, no m�nimo, ' + caracteres + ' letras ou n�meros.');
	}
}