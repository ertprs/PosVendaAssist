<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="cadastros";
include 'autentica_admin.php';

if (strlen($_POST["revenda"]) > 0) $revenda  = trim($_POST["revenda"]);
if (strlen($_GET["revenda"]) > 0)  $revenda  = trim($_GET["revenda"]);

#-------------------- Descredenciar -----------------

$btn_descredenciar = $_POST ['btn_descredenciar'];
if ($btn_descredenciar == "descredenciar" and strlen($revenda) > 0 ) {
	$revenda = $_POST['revenda'];
	$sql = "DELETE FROM tbl_revenda WHERE revenda = $revenda;";
	$res = pg_exec ($con,$sql);
	header ("Location: $PHP_SELF");
	exit;
}

#-------------------- GRAVAR -----------------

$btn_acao = $_POST ['btn_acao'];

if ($btn_acao == "gravar") {

	if (strlen($_POST["nome"]) > 0) $nome  = trim($_POST["nome"]);
	if (strlen($_GET["nome"]) > 0)  $nome  = trim($_GET["nome"]);

	if (strlen($_POST["cnpj"]) > 0) $cnpj  = trim($_POST["cnpj"]);
	if (strlen($_GET["cnpj"]) > 0)  $cnpj  = trim($_GET["cnpj"]);

	if (strlen($_POST["cidade"]) > 0) $cidade  = trim($_POST["cidade"]);
	if (strlen($_GET["cidade"]) > 0)  $cidade  = trim($_GET["cidade"]);

	if (strlen($_POST["estado"]) > 0) $estado  = trim($_POST["estado"]);
	if (strlen($_GET["estado"]) > 0)  $estado  = trim($_GET["estado"]);

	if (strlen($_POST["endereco"]) > 0) $endereco  = trim($_POST["endereco"]);
	if (strlen($_GET["endereco"]) > 0)  $endereco  = trim($_GET["endereco"]);

	if (strlen($_POST["numero"]) > 0) $numero  = trim($_POST["numero"]);
	if (strlen($_GET["numero"]) > 0)  $numero  = trim($_GET["numero"]);

	if (strlen($_POST["complemento"]) > 0) $complemento  = trim($_POST["complemento"]);
	if (strlen($_GET["complemento"]) > 0)  $complemento  = trim($_GET["complemento"]);

	if (strlen($_POST["bairro"]) > 0) $bairro  = trim($_POST["bairro"]);
	if (strlen($_GET["bairro"]) > 0)  $bairro  = trim($_GET["bairro"]);

	if (strlen($_POST["cep"]) > 0) $cep  = trim($_POST["cep"]);
	if (strlen($_GET["cep"]) > 0)  $cep  = trim($_GET["cep"]);

	if (strlen($_POST["complemento"]) > 0) $complemento  = trim($_POST["complemento"]);
	if (strlen($_GET["complemento"]) > 0)  $complemento  = trim($_GET["complemento"]);

	if (strlen($_POST["contato"]) > 0) $contato  = trim($_POST["contato"]);
	if (strlen($_GET["contato"]) > 0)  $contato  = trim($_GET["contato"]);

	if (strlen($_POST["email"]) > 0) $email  = trim($_POST["email"]);
	if (strlen($_GET["email"]) > 0)  $email  = trim($_GET["email"]);

	if (strlen($_POST["fone"]) > 0) $fone  = trim($_POST["fone"]);
	if (strlen($_GET["fone"]) > 0)  $fone  = trim($_GET["fone"]);

	if (strlen($_POST["fax"]) > 0) $fax  = trim($_POST["fax"]);
	if (strlen($_GET["fax"]) > 0)  $fax  = trim($_GET["fax"]);

	if (strlen($_POST["contato"]) > 0) $contato  = trim($_POST["contato"]);
	if (strlen($_GET["contato"]) > 0)  $contato  = trim($_GET["contato"]);

	if (strlen($_POST["ie"]) > 0) $ie  = trim($_POST["ie"]);
	if (strlen($_GET["ie"]) > 0)  $ie  = trim($_GET["ie"]);


if (strlen($cnpj) > 0){
		// HD 37000
		function Valida_CNPJ($cnpj){
			$cnpj = preg_replace( "@[./-]@", "", $cnpj );
			if( strlen( $cnpj ) <> 14 or !is_numeric( $cnpj ) ){
				return false;
			}
			$k = 6;
			$soma1 = "";
			$soma2 = "";
			for( $i = 0; $i < 13; $i++ ){
				$k = $k == 1 ? 9 : $k;
				$soma2 += ( $cnpj{$i} * $k );
				$k--;
				if($i < 12){
					if($k == 1){
						$k = 9;
						$soma1 += ( $cnpj{$i} * $k );
						$k = 1;
					}else{
					$soma1 += ( $cnpj{$i} * $k );
					}
				}
			}

			$digito1 = $soma1 % 11 < 2 ? 0 : 11 - $soma1 % 11;
			$digito2 = $soma2 % 11 < 2 ? 0 : 11 - $soma2 % 11;

			return ( $cnpj{12} == $digito1 and $cnpj{13} == $digito2 ) ? "certo" : "errado" ;
		}

	$xcnpj = str_replace (".","",$cnpj);
	$xcnpj = str_replace ("-","",$xcnpj);
	$xcnpj = str_replace ("/","",$xcnpj);
	$xcnpj = str_replace (" ","",$xcnpj);

	$valida_cnpj = Valida_CNPJ("$xcnpj");

	if($valida_cnpj == 'errado') {
		if($login_fabrica == 1) {
			$msg_erro ="CNPJ da revenda inválida";
		}
		else $msg_erro ="CNPJ da revenda inválida";
	}

	$xcnpj = "'".$xcnpj."'";
}else{
		$msg_erro = "Digite o CNPJ.";
	}

if(strlen($msg_erro)==0){
$pais="BR";
if($login_fabrica ==20){
	$sql = "select pais from tbl_admin where admin =$login_admin and fabrica = 20";
	$res = pg_exec ($con,$sql) ;
	if(pg_numrows($res) >0) $pais = pg_result ($res, 0, pais);
}
if($pais == "BR"){
	$verifica_cnpj = str_replace ("'","",$xcnpj);
	if (strlen ($verifica_cnpj)   <> 0 and strlen ($verifica_cnpj)   <> 14) $msg_erro = "Tamanho do CNPJ da revenda inválido.";
}

if(strlen($msg_erro)==0){

		if (strlen($nome) == 0){
			// verifica se revenda já está cadastrada
			$sql = "SELECT	tbl_revenda.*                     ,
							tbl_cidade.nome   AS cidade_nome  ,
							tbl_cidade.estado AS cidade_estado
					FROM	tbl_revenda
					JOIN	tbl_cidade USING (cidade)
					WHERE	tbl_revenda.cnpj = $xcnpj ";
			$res = @pg_exec ($con,$sql);

			if (@pg_numrows($res) > 0) {
				$msg_erro	 = "Revenda já está cadastrada.";
				$revenda     = pg_result($res,0,revenda);
				$ie          = pg_result($res,0,ie);
				$nome        = pg_result($res,0,nome);
				$fone        = pg_result($res,0,fone);
				$fax         = pg_result($res,0,fax);
				$contato     = pg_result($res,0,contato);
				$endereco    = pg_result($res,0,endereco);
				$numero      = pg_result($res,0,numero);
				$complemento = pg_result($res,0,complemento);
				$bairro      = pg_result($res,0,bairro);
				$cep         = pg_result($res,0,cep);
				$cidade      = pg_result($res,0,cidade_nome);
				$estado      = pg_result($res,0,cidade_estado);
				$email       = pg_result($res,0,email);
			}else{
				$msg_erro = "Revenda não cadastrada, favor completar os dados de cadastro";
			}
		}
	}
	#----------------------------- Dados ---------------------

	if (strlen($revenda) > 0)
		$xrevenda = "'".$revenda."'";
	else
		$xrevenda = 'null';

	if (strlen($ie) > 0){
		$ie	= str_replace ("'","\\'",$ie);
		$xie = "'".$ie."'";
	}else{
		$xie = 'null';
	}

	if (strlen($nome) > 0){
		$xnome		= str_replace ("'","\\'",$nome);
		$xnome = "'".$xnome."'";
	}else{
		$xnome = 'null';
	}

	if (strlen($endereco) > 0){
		$endereco	= str_replace ("'","\\'",$endereco);
		$xendereco = "'".$endereco."'";
	}else{
		$xendereco = 'null';
	}

	if (strlen($numero) > 0)
		$xnumero = "'".$numero."'";
	else
		$xnumero = 'null';

	if (strlen($complemento) > 0)
		$xcomplemento = "'".$complemento."'";
	else
		$xcomplemento = 'null';

	if (strlen($bairro) > 0){
		$bairro = str_replace ("'","\\'",$bairro);
		$xbairro = "'".$bairro."'";
	}else{
		$xbairro = 'null';
	}

	if (strlen($cep) > 0){
		$xcep = str_replace (".","",$cep);
		$xcep = str_replace ("-","",$xcep);
		$xcep = "'".$xcep."'";
	}else{
		$xcep = 'null';
	}

	if (strlen($email) > 0)
		$xemail = "'".$email."'";
	else
		$xemail = 'null';

	if (strlen($fone) > 0)
		$xfone = "'".$fone."'";
	else
		$xfone = 'null';

	if (strlen($fax) > 0)
		$xfax = "'".$fax."'";
	else
		$xfax = 'null';

	if (strlen($contato) > 0){
		$contato = str_replace ("'","\\'",$contato);
		$xcontato = "'".$contato."'";
	}else{
		$xcontato = 'null';
	}

	if (strlen($cidade) == 0) {
		$msg_erro = "Favor informar a cidade.";
	}else{
		$cidade = str_replace ("'","\\'",$cidade);
	}

	if (strlen($estado) == 0) {
		$msg_erro = "Favor informar o estado.";
	}
}
	

	if (strlen ($msg_erro) == 0){

		// verifica se cidade já está cadastrada tbl_cidade (seleciona cidade e estado)
		$sql = "SELECT	cidade
				FROM	tbl_cidade
				WHERE	nome   = '$cidade'
				AND		estado = '$estado'";
		$res = @pg_exec ($con,$sql);

		if(@pg_numrows($res) > 0){
			$cod_cidade = pg_result($res,0,cidade);
		}else{
			$cidade = strtoupper($cidade);
			$estado = strtoupper($estado);

			$sql = "INSERT INTO tbl_cidade(
						nome,
						estado
					)VALUES(
						'$cidade',
						'$estado'
					)";
			$res = @pg_exec ($con,$sql);

			$res		= @pg_exec ($con,"SELECT CURRVAL ('seq_cidade')");
			$cod_cidade	= pg_result ($res,0,0);
		}

		if (strlen ($revenda) > 0) {
			// update
			$sql = "UPDATE tbl_revenda SET
						nome		= $xnome        ,
						cnpj		= $xcnpj        ,
						endereco	= $xendereco    ,
						numero		= $xnumero      ,
						complemento	= $xcomplemento ,
						bairro		= $xbairro      ,
						cep			= $xcep         ,
						cidade		= $cod_cidade  ,
						contato		= $xcontato     ,
						email		= $xemail       ,
						fone		= $xfone        ,
						fax			= $xfax         ,
						ie			= $xie
					WHERE tbl_revenda.revenda = '$revenda'";

		}else{

			#-------------- INSERT ---------------
			$sql = "INSERT INTO tbl_revenda (
						nome       ,
						cnpj       ,
						endereco   ,
						numero     ,
						complemento,
						bairro     ,
						cep        ,
						cidade     ,
						contato    ,
						email      ,
						fone       ,
						fax        ,
						ie
					) VALUES (
						$xnome       ,
						$xcnpj       ,
						$xendereco   ,
						$xnumero     ,
						$xcomplemento,
						$xbairro     ,
						$xcep        ,
						$cod_cidade ,
						$xcontato    ,
						$xemail      ,
						$xfone       ,
						$xfax        ,
						$xie
					)";
		}
		
		
		$res = @pg_exec ($con,$sql);
		if (strlen(pg_errormessage ($con)) > 0) $msg_erro = pg_errormessage ($con);

		if(strlen($revenda)==0 and strlen($msg_erro)==0) {
			$sql = "SELECT CURRVAL ('seq_revenda')";
			$res = pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);
			$revenda = pg_result($res,0,0);

			if ($valida_cnpj=='errado') {
				$sql = "update tbl_revenda set ativo = 'f' where revenda = $revenda";
				$res = pg_exec($con,$sql);
			}


		}
	}
	if(strlen($msg_erro) ==0 and strlen($revenda) >0){
		$sql = "SELECT revenda
				FROM  tbl_revenda_compra
				WHERE revenda = $revenda
				AND   fabrica = $login_fabrica";
		$res = pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);
		if(pg_numrows($res)==0){
			$sql = "INSERT INTO tbl_revenda_compra (
						revenda,
						fabrica
					) VALUES (
						$revenda,
						$login_fabrica
					)";
			$res = pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);
		}
	}
	if(strlen($msg_erro) == 0){
		$msg = "Gravado com Sucesso!";
	}

}

#-------------------- Pesquisa Revenda -----------------
if (strlen($revenda) > 0 and strlen ($msg_erro) == 0 ) {
	$sql = "SELECT	tbl_revenda.revenda      ,
					tbl_revenda.nome         ,
					tbl_revenda.endereco     ,
					tbl_revenda.bairro       ,
					tbl_revenda.complemento  ,
					tbl_revenda.numero       ,
					tbl_revenda.cep          ,
					tbl_revenda.cnpj         ,
					tbl_revenda.fone         ,
					tbl_revenda.fax          ,
					tbl_revenda.contato      ,
					tbl_revenda.fax          ,
					tbl_revenda.email        ,
					tbl_revenda.ie           ,
					tbl_cidade.nome AS cidade,
					tbl_cidade.estado
			FROM	tbl_revenda
			JOIN	tbl_cidade USING(cidade)
			WHERE	tbl_revenda.revenda = $revenda ";
	$res = @pg_exec ($con,$sql);

	if (@pg_numrows ($res) > 0) {
		$nome             = trim(pg_result($res,0,nome));
		$cnpj             = trim(pg_result($res,0,cnpj));
		$cnpj             = substr($cnpj,0,2) .".". substr($cnpj,2,3) .".". substr($cnpj,5,3) ."/". substr($cnpj,8,4) ."-". substr($cnpj,12,2);
		$endereco         = trim(pg_result($res,0,endereco));
		$numero           = trim(pg_result($res,0,numero));
		$complemento      = trim(pg_result($res,0,complemento));
		$bairro           = trim(pg_result($res,0,bairro));
		$cep              = trim(pg_result($res,0,cep));
		$cidade           = trim(pg_result($res,0,cidade));
		$estado           = trim(pg_result($res,0,estado));
		$email            = trim(pg_result($res,0,email));
		$fone             = trim(pg_result($res,0,fone));
		$fax              = trim(pg_result($res,0,fax));
		$contato          = trim(pg_result($res,0,contato));
		$ie               = trim(pg_result($res,0,ie));
	}
}

$visual_black = "manutencao-admin";

$title     = "TEMPLATE CADASTRO";

include 'cabecalho.php';

?>
<script language="JavaScript" src="js/jquery.js"></script>
<script language="JavaScript" src="js/jquery.maskedinput.js"></script>
<script type="text/javascript" src="js/jquery.alphanumeric.js"></script> <!-- Para bloquear números ou letras -->
<script language='javascript' src='ajax.js'></script>
<script language='javascript' src='ajax_cep.js'></script>
<script language="JavaScript">
function fnc_pesquisa_codigo_posto (codigo, nome) {
    var url = "";
    if (codigo != "" && nome == "") {
        url = "pesquisa_posto.php?codigo=" + codigo;
        janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=600,height=400,top=18,left=0");
        janela.focus();
    }
}

function fnc_pesquisa_nome_posto (codigo, nome) {
    var url = "";
    if (codigo == "" && nome != "") {
        url = "pesquisa_posto.php?nome=" + nome;
        janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=600,height=400,top=18,left=0");
        janela.focus();
    }

	else{
		alert("Preencha toda ou parte da informação para realizar a pesquisa!");
	}
}

function fnc_revenda_pesquisa (campo, campo2, tipo) {
	if (tipo == "nome" ) {
		var xcampo = campo;
	}

	if (tipo == "cnpj" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "revenda_pesquisa.php?forma=reload&campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
		janela.retorno = "<? echo $PHP_SELF ?>";
		janela.nome	= campo;
		janela.cnpj	= campo2;
		janela.focus();
	}

	else{
		alert("Preencha toda ou parte da informação para realizar a pesquisa!");
	}
}


$(function(){
		$("#cep").maskedinput("99.999-999");
		$("#fone").maskedinput("(99)9999-9999");
		$("#fax").maskedinput("(99)9999-9999");
		
		//Permite Apenas números e (. / - )
		$("#cnpj").numeric({allow:"./-"});
	
		//Não permite números
		$("#cidade").alpha();
		$("#estado").alpha();
	});


  function formata_cnpj(cnpj, form){    
        var mycnpj = "";
        mycnpj = mycnpj + cnpj;
        myrecord = "cnpj";
        myform = form;

        if (mycnpj.length == 2){
            mycnpj = mycnpj + ".";
            window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
        }
        if (mycnpj.length == 6){
            mycnpj = mycnpj + ".";
            window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
        }
        if (mycnpj.length == 10){
            mycnpj = mycnpj + "/";
            window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
        }
        if (mycnpj.length == 15){
            mycnpj = mycnpj + "-";
            window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
        }
    }

	function validaEmail(){
		  var obj = eval("document.forms[0].email");
		  var txt = obj.value;
		  if ((txt.length != 0) && ((txt.indexOf("@") < 1) || (txt.indexOf('.') < 7)))
		  {
			alert('Email incorreto');
			obj.focus();
		  }

     }
</script>

<style type="text/css">

.titulo_tabela{
background-color:#596d9b;
font: bold 14px "Arial";
color:#FFFFFF;
text-align:center;
}

.formulario{
background-color:#D9E2EF;
font:11px Arial;
text-align:left;
}

.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}


.texto_avulso{
	font: 14px Arial; color: rgb(89, 109, 155);
	background-color: #d9e2ef;
	text-align: center;
	width:700px;
	margin: 0 auto;
	border-collapse: collapse;
	border:1px solid #596d9b;
}
</style>


<br>
<div class="texto_avulso">
	Para incluir uma nova revenda, preencha somente seu CNPJ e clique em gravar.
	<br>
	Faremos uma pesquisa para verificar se a revenda já está cadastrada em nosso banco de dados.
</div>
<br>
<form name="frm_revenda" method="post" action="">
<input type="hidden" name="revenda" value="<? echo $revenda ?>">

<table width='700' align='center' border='0' cellpadding="1" cellspacing="1" class="formulario">
	<? if (strlen ($msg) > 0) { ?>
		<tr class="sucesso">
			<td colspan="6"> <? echo $msg; ?></td>
		</tr>
	<? } ?>

	<? if (strlen ($msg_erro) > 0) { ?>
		<tr class="msg_erro">
			<td colspan="6"> <? echo $msg_erro; ?></td>
		</tr>
	<? } ?>

	<tr>
		<td colspan="6" class="titulo_tabela">
			Informações Cadastrais
		</td>
	</tr>
	<tr>
		<td width='15'>&nbsp;</td>
		<td>CNPJ</td>
		<td>I.E.</td>
		<td>Fone</td>
		<td colspan="2">Fax</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td >
			<input class='frm' type="text" name="cnpj" id="cnpj" size="20" maxlength="18" value="<? echo $cnpj ?>" onkeyup="formata_cnpj(this.value,'frm_revenda')">&nbsp;<img src='../imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_revenda_pesquisa (document.frm_revenda.nome,document.frm_revenda.cnpj,'cnpj')">
		</td>
		<td>
			<input class='frm' type="text" name="ie" size="18" maxlength="20" value="<? echo $ie ?>" style="width:100px">
		</td>
		<td>
			<input class='frm' type="text" name="fone" id="fone" size="15" maxlength="20" value="<? echo $fone ?>">
		</td>
		<td colspan="2">
			<input class='frm' type="text" name="fax" id="fax" size="15" maxlength="20" value="<? echo $fax ?>">
		</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td>Contato</td>
		<td colspan ='4'>Razão Social</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td class="formulario"><input class='frm' type="text" name="contato" size="30" maxlength="30" value="<? echo $contato ?>" style="width:100px"></td>
		<td colspan ='4' class="formulario"><input  class='frm' type="text" name="nome" size="50" maxlength="60" value="<? echo $nome ?>" style="width:300px">&nbsp;<img src='../imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_revenda_pesquisa (document.frm_revenda.nome,document.frm_revenda.cnpj,'nome')"></td>
	</tr>

	<tr>
		<td colspan="4">&nbsp;</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td colspan="2" >Endereço</td>
		<td>Número</td>
		<td colspan="2">Complemento</td>
	</tr>
	<tr >
		<td>&nbsp;</td>
		<td colspan="2">
			<input type="text" name="endereco" class='frm' size="40" maxlength="50" value="<? echo $endereco ?>">
		</td>
		<td>
			<input type="text" name="numero" id="numero" size="10" class='frm' maxlength="10" value="<? echo $numero ?>">
		</td>
		<td colspan="2">
			<input type="text" name="complemento" size="35" class='frm' maxlength="40" value="<? echo $complemento ?>">
		</td>
	</tr>
	<tr>
		<td width='15'>&nbsp;</td>
		<td colspan="2">Bairro</td>
		<td>CEP</td>
		<td width='170'>Cidade</td>
		<td>Estado</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td colspan="2">
			<input type="text" class='frm' name="bairro" size="30" maxlength="20" value="<? echo $bairro ?>">
		</td>
		<td>
			<input type="text" class='frm' name="cep"  id="cep"  size="10" maxlength="8" value="<? echo $cep ?>" onblur="buscaCEP(this.value, document.frm_revenda.endereco, document.frm_revenda.bairro, document.frm_revenda.cidade, document.frm_revenda.estado) ;">
		</td>
		<td width='170'>
			<input type="text" class='frm' name="cidade" id="cidade" size="35"  size="30" maxlength="30" value="<? echo $cidade ?>">
		</td>
		<td>
			<input type="text" class='frm' name="estado" id="estado" size="2"  maxlength="2"  size="30" value="<? echo $estado ?>">
		</td>
	</tr>

	<tr>
		<td width='15'>&nbsp;</td>
		<td colspan="6">E-mail</td>
	</tr>
	<tr>
		<td width='5'>&nbsp;</td>
		<td colspan="6">
			<input type="text" class='frm' name="email" size="40" maxlength="50" value="<? echo $email ?>" onblur="validaEmail();">
		</td>
 	</tr>
	<tr>
		<td colspan="6">&nbsp;</td>
	</tr>
	<tr>
		<td colspan="6" align="center">
			<input type='hidden' name='btn_acao' value=''>
			<input type="button"  value="Gravar"  onclick="javascript: if (document.frm_revenda.btn_acao.value == '' ) { document.frm_revenda.btn_acao.value='gravar' ; document.frm_revenda.submit() } else { alert ('Aguarde submissão') } return false;" ALT="Gravar formulário" border='0'>

			<input type='hidden' class='frm' name='btn_descredenciar' value=''>
			<input type="button" value="Apagar"  onclick="javascript: if (document.frm_revenda.btn_descredenciar.value == '' ) { if(confirm('Deseja realmente EXCLUIR esta REVENDA?') == true) { document.frm_revenda.btn_descredenciar.value='descredenciar'; document.frm_revenda.submit(); }else{ return; }; } else { alert ('Aguarde submissão') } return false;" ALT="Apagar a Ordem de Serviço" border='0'>

			<input type="button" value="Limpar"  onclick="javascript: window.location='<? echo $PHP_SELF ?>'; return false;" ALT="Limpar campos" border='0'>
		</td>
	</tr>
</table>

</form>

<? include "rodape.php"; ?>
