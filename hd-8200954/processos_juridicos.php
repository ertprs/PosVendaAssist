<?
//BETA -> Fábio 04/12/2006
// Processos e acompanhamentos

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
//include 'cabecalho.php';

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<HTML>
<HEAD>
<TITLE> Menu de Processos </TITLE>
<META NAME="Author" CONTENT="">
<META NAME="Keywords" CONTENT="">
<META NAME="Description" CONTENT="">

<script language="JavaScript">

function trim(cp) {
   var txt = new String(cp.value);
   while((txt.charAt(0)==" ")||(txt.charAt(txt.length-1)==" "))
      txt = txt.replace(/^ /,"").replace(/ $/,"");
   return cp.value = txt;
}

</script>

</HEAD>
<BODY>
<h2>Processos Juridicos</h2>
<form name='processos' action='<? echo $PHP_SELF ?>' method='post'>
<br>Estado: 
<select name='txtestado'>
	<option value='BA' <? if($txtestado=="BA") echo "selected" ?>>Bahia</option>
	<option value='SP'<? if($txtestado=="SP") echo "selected" ?>>São Paulo</option>
	<option value='MG'<? if($txtestado=="MG") echo "selected" ?>>Minas Gerais</option>
	<option value='RJ' <? if($txtestado=="RJ") echo "selected" ?>>Rio de Janeiro</option>
	<option value='ES'<? if($txtestado=="ES") echo "selected" ?>>Espirito Santo</option>
	<option value='RN'<? if($txtestado=="RN") echo "selected" ?>>Rio Grande do Norte</option>
	<option value='PR'<? if($txtestado=="PR") echo "selected" ?>>Paraná</option>
	<option value='AL'<? if($txtestado=="AL") echo "selected" ?>>Alagoas</option>
	<option value='GO'<? if($txtestado=="GO") echo "selected" ?>>Goiás</option>
	<option value='PI'<? if($txtestado=="PI") echo "selected" ?>>Piauí</option>
	<option value='MA'<? if($txtestado=="MA") echo "selected" ?>>Maranhão</option>
	<option value='DF'<? if($txtestado=="DF") echo "selected" ?>>Distrito Federal</option>
	<option value='PB'<? if($txtestado=="PB") echo "selected" ?>>Paraíba</option>
	<option value='CE'<? if($txtestado=="CE") echo "selected" ?>>Ceará</option>
</select>
Número do Processo: <input type='text' name='txtprocesso'>
<input type='submit' name='enviar'>
</form>



<?	
if (isset($_POST['txtprocesso']) AND trim($_POST['txtprocesso'])!="" ){
	$processo = trim($_POST['txtprocesso']);
	$estado = trim($_POST['txtestado']);


//	$arquivo = fopen ("log_sp.txt","w");
	//fputs ($arquivo, "key Down Arrow\r\nkey Down Arrow\r\nkey Down Arrow\r\nkey Down Arrow\r\nkey Down Arrow\r\nkey Down Arrow");
	//fputs ($arquivo, "key <delete>\r\nkey <delete>\r\nkey <delete>\r\nkey <delete>\r\nkey <delete>\r\n");	
	//for ($i=0;$i<lenght($i);$i++){
 	//	fputs ($arquivo, "key $\r\n");
	//}


///////////////////// RIO DE JANEIRO -> Processo: 01427-1992-007-01-01-4

// PAGINA:	http://www.tj.rj.gov.br/scripts/weblink.mgw
// 4564654684

if ($estado=='RJ'){
	$URL="form1=true&CONS=1&PGM=WEBCAPITAL01&LAB=CONxWEB&MGWLPN=DIGITAL1A&Consulta=Pesquisar&N=2006110121";
	//echo ´$URL | lynx -post_data -cookie_save_file=teste.cok "http://www.tj.rj.gov.br/scripts/weblink.mgw"´;
	
	;
	$res = file("http://srv7.tj.rj.gov.br/consultaProcessoWeb/consultaProc.do?numProcesso=$processo&back=2");
	//preg_match_all("/\.value\s*=\s*[\"\']([^\"\']*)[\"\']/", $res, $matches);

 	if (trim($res[106]) =='Processo inexistente.')
 		echo "Processo inexistente!<br>";
 	else{
 		echo "Processo existe!<br>";
 	}
 	
	$res =  `echo '$URL' | lynx -post_data "http://www.tj.rj.gov.br/scripts/weblink.mgw"`;
	$res = nl2br($res);
	$res = preg_matchi_replace("\[1\].*$","",$res);	
	echo $res;
	
}
/////////////////////////////////////////////////////////////////////////////////////////////////


///////////////////// RIO GRANDE DO NORTE ////////////////////////////////////////// 
/*
	Processo: 1980015953
	Processo: 001910008117
	http://www.tjrn.gov.br:8080/sitetj/pages/pesquisa/frame_pesquisa_seij.jsp
	http://ww2.tjrn.gov.br/cpopg/pcpoResultadoPG.jsp?CDP=010000N2Y0000&nuProcesso=1980015953&nuRecurso=0&cbPesquisa=NMPARTE&cdForo=1
	$URL="formProcesso=true&cbPesquisa=NUMPROC&dePesquisa=001910008117&submit=Pesquisar";
	echo `$URL | lynx -post_data -cookie_save_file=teste.cok "http://ww2.tjrn.gov.br/cpopg/pcpoSelecaoPG.jsp"`;
*/

if ($estado=='RN'){
	
	$teste = `echo 'formProcesso=true&cbPesquisa=NUMPROC&dePesquisa=001910008117&submit=Pesquisar' | lynx -post_data -cookie_save_file=teste.cok "http://ww2.tjrn.gov.br/cpopg/pcpoSelecaoPG.jsp"`;
	$teste = nl2br($teste);
//	echo preg_match("/[(.*)?]//", $teste);

	preg_match("Dados do Processo(.*)",$teste,$txtdados);
	$txtdados = preg_matchi_replace("Desenvolvido.*$","",$txtdados[0]);	
	//$txtdados = preg_matchi_replace("\[(.*)\]?","",$txtdados);	

	$txtdados = str_replace("[8]Data [crescente.gif]   Movimento<br />","",$txtdados);
	$txtdados = str_replace("[_] Todas as Partes [_] Todas as Movimentações Todos os dados<br />","",$txtdados);
	$txtdados = str_replace("[BUTTON]<br />","",$txtdados);
	$txtdados = str_replace("[rodapeFinal.gif]","",$txtdados);
	$txtdados = str_replace("[doc2.gif]","",$txtdados);
   
	echo ($txtdados);
	//preg_match(\"/(,*)Dados", $teste,$p);
	//print_r($p);
    	//$teste = preg_split('/(.*)Dados(.*)/', $teste);
	//echo $teste[1];
//	preg_match("Curiosidades</font></a><br>(.*(<br>)?)",$arquivo,$txtcuriosidades1);							
}


/////////////////////////////////////////////////////////////////////////////////////////////////// 


/////////////////////      ESPIRITO SANTO      ////////////////////////////////////////// 
/*
	Processo: 1980015953
	Processo: 001910008117

	http://ww2.tj.es.gov.br/Novo/desc_proces.cfm
	http://ww2.tj.es.gov.br/Novo/cons_proces.cfm
	$URL="cons_proces=true&seInstancia=1&sePesquisar=1&edNumProcesso=1980015953&seJuizo=1&seComarca=0&buPesquisar=Pesquisar";
	echo `$URL | lynx -post_data -cookie_save_file=teste.cok "http://ww2.tj.es.gov.br/Novo/desc_proces.cfm"`;
*/

if ($estado=='ES'){
	$URL="cons_proces=true&seInstancia=1&sePesquisar=1&edNumProcesso=1980015953&seJuizo=1&seComarca=0&buPesquisar=Pesquisar";
	echo ` echo '$URL' | lynx -post_data -cookie_save_file=teste.cok "http://ww2.tj.es.gov.br/Novo/desc_proces.cfm"`;
} 

/////////////////////////////////////////////////////////////////////////////////////////////////// 


/////////////////////             B A H I A         ////////////////////////////////////////// 
/*

VARIAS COMARCAS - 	PEGAR COMARCAS EM: http://www.tj.ba.gov.br/consulta.htm

URL="frmconsulta=true&rbinstancia=1&cbcomarca=67512000&natureza=CV&numero=";
echo $URL | lynx -post_data -cookie_save_file=teste.cok "http://www.tj.ba.gov.br/processos/numeroprocesso/numproc.wsp";
*/

if ($estado=='BA'){
	$URL="frmconsulta=true&rbinstancia=1&cbcomarca=67512000&natureza=CV&numero=";
	$teste = `echo '$URL' | lynx -post_data -cookie_save_file=teste.cok "http://www.tj.ba.gov.br/processos/numeroprocesso/numproc.wsp"`;
	$teste = nl2br($teste);
	$teste = preg_matchi_replace("\[1\]Voltar.*$","",$teste);	
	echo $teste;
}
/////////////////////////////////////////////////////////////////////////////////////////////////// 


/////////////////////         MINAS GERAIS       ////////////////////////////////////////// 
/*
comrCodigo -> comarca -> 0-4
listaProcessos -> processo -> 4-12

URL="frm_lista=true&select=1&txtProcesso=1234567892114&comrCodigo=1234&numero=1&listaProcessos=567892114&submit=Pesquisar";
echo $URL | lynx -post_data -cookie_save_file=teste.cok "http://www.tjmg.gov.br/juridico/sf/proc_resultado.jsp";
*/
if ($estado=='MG'){
	$URL="frm_lista=true&select=1&txtProcesso=1234567892114&comrCodigo=1234&numero=1&listaProcessos=567892114&submit=Pesquisar";
	echo `$URL | lynx -post_data -cookie_save_file=teste.cok "http://www.tjmg.gov.br/juridico/sf/proc_resultado.jsp"`;
}
/////////////////////////////////////////////////////////////////////////////////////////////////////// 


////////////////////////           P A R A N A         ////////////////////////////////////////// 
/*
URL="frmconsulta=true&rbinstancia=1&cbcomarca=67512000&natureza=CV&numero=";
echo $URL | lynx -post_data -cookie_save_file=teste.cok "http://www.tj.ba.gov.br/processos/numeroprocesso/numproc.wsp";
*/

if ($estado=='PR'){
	$URL="txt_pesquisa=$processo&pesquisa=Pesquisar&rdo_tipo_pesquisa=1&cbo_pesquisa=1&cbo_cartorio=1&cbo_comarca=1";
	$teste =  `echo '$URL' | lynx -post_data -cookie_save_file=teste.cok "http://www.assejepar.com.br/cgi-bin/cons_processo.asp"`;
	$teste = nl2br($teste);
	$teste = preg_matchi_replace("^.*\[12\]Fale Conosco","",$teste);	
	$teste = preg_matchi_replace("\[13\]Para ajuda, clique aqui","",$teste);	
	$teste = preg_matchi_replace("Busca Alfabética::       \(_\) Pesquisa Exata    \(_\) Qualquer Ordem<br \/>","",$teste);	
	$teste = preg_matchi_replace("\[14\]Consulta.*$","",$teste);	
	echo $teste;
}
/////////////////////////////////////////////////////////////////////////////////////////////////// 


////////////////////////            G O I A S           ////////////////////////////////////////// 
/*

NÃO É POSSÍVEL, POIS TEM VALIDAÇÃO DE IMAGEM

*/
/////////////////////////////////////////////////////////////////////////////////////////////////// 


////////////////////////         A L A G O A S         ////////////////////////////////////////// 
/*
URL="frmconsulta=true&rbinstancia=1&cbcomarca=67512000&natureza=CV&numero=";
echo $URL | lynx -post_data -cookie_save_file=teste.cok "http://www.tj.ba.gov.br/processos/numeroprocesso/numproc.wsp";
*/

if ($estado=='AL'){
	$URL = "formProcesso=true&urlComarca=http://www2.tj.al.gov.br/cpopg/pcpoSelecaoPG.jsp?cdForo=1&cdForo=1&cbPesquisa=NUMPROC&dePesquisa=$processo&chProcAtivos=SIM";
	$teste =  `echo '$URL' | lynx -post_data -cookie_save_file=teste.cok "http://www2.tj.al.gov.br/cpopg/pcpoSelecaoPG.jsp"`;
	$teste = nl2br($teste);
	$teste = preg_matchi_replace("\[cabecalho.jpg\].*Primeiro Grau","",$teste);	
	$teste = preg_matchi_replace("Pesquisar por(.*)processos ativos","",$teste);	
	$teste = preg_matchi_replace("\[rodapeFinal.*$","",$teste);	

	echo $teste;

	$res = file("http://www2.tj.al.gov.br/cpopg/pcpoSelecaoPG.jsp?cdForo=1&cbPesquisa=NUMPROC&dePesquisa=$processo&chProcAtivos=true");
	//print_r($res);
		
/*		cdForo = 
                      1=Maceió
                      58=Arapiraca
                      49=Penedo
                      53=São Miguel dos Campos
*/

}
/////////////////////////////////////////////////////////////////////////////////////////////////// 




// PEDIR PARA MILENA NUMERO DE PROCESSOS PARA :
		// SAO PAULO
		// MINAS GERIAS
		// RIO DE JANEIRO






	//echo 'lynx -accept_all_cookies -cmd_script=log_sp.txt http://www.google.com.br/search?hl=pt-BR\&q=teste\&btnG=Pesquisa+Google\&meta= > fontes.txt';
}

?>

</BODY>
</HTML>
