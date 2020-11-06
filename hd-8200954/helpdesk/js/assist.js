// ************************************
// Criada em 13/04/2004 - Ricardo
// Verificação de tamanho mínimo do campo
// Usar: onClick="javascript:fnc_tamanho_minimo(document.form.campo, 3);"
// ************************************
function fnc_tamanho_minimo(campo, caracteres)
{
	if (campo.value.length < caracteres)
	{
		alert('Para uma busca mais apurada digite, no mínimo, ' + caracteres + ' letras ou números.');
	}
}

// ************************************
// Criada em 13/04/2004 - Ricardo
// Preload em imagens do site
// Usar: onload="fnc_preload()"
// ************************************
function fnc_preload() 
{
	// lupa
	pI('../imagens_admin/btn_lupa.gif');
	pI('../imagens_admin/btn_pesquisar_400.gif');
}

// ************************************
// Criada em 13/04/2004 - Ricardo
// Utilizada pela funcao "fnc_preload()"
// ************************************
function pI()
{
	var d=document;
	if(d.images)
	{
		if(!d.MM_p)
			d.MM_p=new Array();
		var i,j=d.MM_p.length,a=pI.arguments; 
		for(i=0; i<a.length; i++)
			if (a[i].indexOf("#")!=0)
			{
				d.MM_p[j]=new Image;
				d.MM_p[j++].src=a[i];
			}
	}
}