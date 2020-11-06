<center>
<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="cadastros";
include 'autentica_admin.php';
if (strlen($_GET["ajax"]) > 0)  $ajax  = trim($_GET["ajax"]);
if(strlen($ajax)>0){
	$imagem         = $_GET['imagem'];
	
	echo"<center>
		<img src='../revend/logos/$imagem' border='0'>
		</center>";

	exit;
}

if (strlen($_POST["revenda"]) > 0) $revenda  = trim($_POST["revenda"]);
if (strlen($_GET["revenda"]) > 0)  $revenda  = trim($_GET["revenda"]);
function reduz_imagem($img, $max_x, $max_y, $nome_foto) {

	list($width, $height) = getimagesize($img);
	$original_x = $width;
	$original_y = $height;

	if($original_x > $original_y) {
	   $porcentagem = (100 * $max_x) / $original_x;      
	} 
	else {
	   $porcentagem = (100 * $max_y) / $original_y;   
	}

	$tamanho_x = $original_x * ($porcentagem / 100);
	$tamanho_y = $original_y * ($porcentagem / 100);

	$image_p = imagecreatetruecolor($tamanho_x, $tamanho_y);
	$image   = imagecreatefromjpeg($img);
	imagecopyresampled($image_p, $image, 0, 0, 0, 0, $tamanho_x, $tamanho_y, $width, $height);

	imagejpeg($image_p, $nome_foto, 65);

}


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

	/*if (strlen($_POST["nome"]) > 0) $nome  = trim($_POST["nome"]);
	if (strlen($_GET["nome"]) > 0)  $nome  = trim($_GET["nome"]);*/
 
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

	if (strlen($_POST["senha"]) > 0) $senha  = trim($_POST["senha"]);
	if (strlen($senha) > 0){
		$xsenha = "'". $senha . "'";
	}else{
		$xsenha = "NULL";
	}





if (strlen($cnpj) > 0){
	$xcnpj = str_replace (".","",$cnpj);
	$xcnpj = str_replace ("-","",$xcnpj);
	$xcnpj = str_replace ("/","",$xcnpj);
	$xcnpj = str_replace (" ","",$xcnpj);
	$xxcnpj = $xcnpj;
	$xcnpj = "'".$xcnpj."'";
}else{
		$msg_erro = "Digite o CNPJ.";
	}

	
	$sql = "SELECT logo FROM tbl_revenda WHERE revenda = $revenda";
	$res = @pg_exec ($con,$sql);
	if(@pg_numrows($res) > 0) $nome_foto = pg_result($res,0,logo);	

	if (isset($_FILES['arquivos']) and strlen($msg_erro)==0){
		$Destino = '/www/assist/www/revend/logos/'; 

		$Fotos = $_FILES['arquivos'];
		$qtde_de_fotos = $_POST['qtde_de_fotos'];
	
		for ($i=0; $i<$qtde_de_fotos; $i++){

			 // retorna qndo nw tiver foto
			if (!isset($Fotos['tmp_name'][$i])) {
				continue;
			}
			$Nome    = $Fotos['name'][$i]; 
			$Tamanho = $Fotos['size'][$i]; 
			$Tipo    = $Fotos['type'][$i]; 
			$Tmpname = $Fotos['tmp_name'][$i];

			if (strlen($Nome)==0) continue;

			$Extensao = substr($Nome,strlen($Nome)-5,5);
			if(strlen($Extensao)>0){
				if(preg_match('/^image\/(pjpeg|jpeg)$/', $Tipo)){

					if(!is_uploaded_file($Tmpname)){
						$msg_erro .= "Não foi possível efetuar o upload.";
						break;
					}

					$tmp = explode(".",$Nome);
					$ext = $tmp[count($tmp)-1];

					if (strlen($extensao)==0){
						$ext = $Extensao;
					}


					$nome_foto  = "$xxcnpj"."$ext";
					$nome_foto = str_replace(" ","_",$nome_foto);
					//echo "$nome_foto<BR>a";
					$Caminho_foto  = $Destino . $nome_foto;

					reduz_imagem($Tmpname, 224, 34, $Caminho_foto);

				}else{
					$msg_erro .= "O formato da foto $Nome não é permitido!<br>";
				}
			}
		}
	}


	#----------------------------- Dados ---------------------
	if (strlen($nome_foto) > 0){
		$nome_foto = "'".$nome_foto."'";
	}else{
		$nome_foto = "null";
	}

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
		}else{
			$msg_erro = "Revenda não cadastrada, favor completar os dados de cadastro";
		}
	}

	$sql = "SELECT cidade FROM tbl_cidade WHERE UPPER(fn_retira_especiais(nome))) = UPPER(fn_retira_especiais('{$cidade}')) AND UPPER(estado) = UPPER('{$estado}')";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		$cod_cidade = pg_fetch_result($res, 0, "cidade");
	} else {
		$sql = "SELECT cidade, estado FROM tbl_ibge WHERE UPPER(fn_retira_especiais(cidade)) = UPPER(fn_retira_especiais('{$cidade}')) AND UPPER(estado) = UPPER('{$estado}')";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$cidade_ibge        = pg_fetch_result($res, 0, "cidade");
			$cidade_estado_ibge = pg_fetch_result($res, 0, "estado");

			$sql = "INSERT INTO tbl_cidade (
						nome, estado
					) VALUES (
						'{$cidade_ibge}', '{$cidade_estado_ibge}'
					) RETURNING cidade";
			$res = pg_query($con, $sql);

			$cod_cidade = pg_fetch_result($res, 0, "cidade");
		} else {
			$msg_erro .= "Cidade não encontrada";
		}
	}

	if (strlen ($msg_erro) == 0){


		if (strlen ($revenda) > 0) {
			// update
			$sql = "UPDATE tbl_revenda SET
						nome            = $xnome        ,
						cnpj            = $xcnpj        ,
						endereco        = $xendereco    ,
						numero          = $xnumero      ,
						complemento     = $xcomplemento ,
						bairro          = $xbairro      ,
						cep             = $xcep         ,
						cidade          = $cod_cidade  ,
						contato         = $xcontato     ,
						fone            = $xfone        ,
						fax             = $xfax         ,
						ie              = $xie          ,
						logo            = $nome_foto
					WHERE tbl_revenda.revenda = '$revenda'";
			$res = pg_exec ($con,$sql);
			$sql = "SELECT * from tbl_revenda_fabrica WHERE revenda=$revenda AND fabrica=$login_fabrica";
			$res = pg_exec ($con,$sql);
			if(pg_numrows($res)>0){
				$sql = "UPDATE tbl_revenda_fabrica SET email = $xemail,senha=$xsenha WHERE revenda=$revenda AND fabrica=$login_fabrica";
			}else{
				$sql     = "INSERT INTO tbl_revenda_fabrica (
							fabrica,
							revenda,
							email,
							senha
						) VALUES (
							$login_fabrica,
							$revenda,
							$xemail,
							$xsenha
						)";
			}
			$res = pg_exec ($con,$sql);

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
						fone       ,
						fax        ,
						ie         ,
						logo
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
						$xfone       ,
						$xfax        ,
						$xie         ,
						$nome_foto
					)";
			$res     = pg_exec ($con,$sql);
			$res     = @pg_exec ($con,"SELECT CURRVAL ('seq_revenda')");
			$revenda =  pg_result ($res,0,0);
			$sql     = "INSERT INTO tbl_revenda_fabrica (
						fabrica,
						revenda,
						email,
						senha
					) VALUES (
						$login_fabrica,
						$revenda,
						$xemail,
						$xsenha
					)";
			$res = pg_exec ($con,$sql);
		}
	//	echo $sql;


		if (pg_errormessage ($con) > 0) $msg_erro = pg_errormessage ($con);
	}

	if(strlen($msg_erro) == 0){
		if(strlen($senha)>0 and strlen($email)>0){
			$remetente    = "Telecontrol <telecontrol@telecontrol.com.br>"; 
			$destinatario = $email; 
			$assunto      = "Dados de acesso da revenda"; 
			$mensagem     = "Prezada Revenda segue abaixo informações para acesso ao sistema de revenda de Telecontrol:
			<BR><BR>
			<strong>Acesso:</strong> http://www.telecontrol.com.br/ <BR>
			<strong>Login:</strong> $email<BR>
			<strong>Senha:</strong> $senha
			 <BR><BR>
			Atenciosamente <BR><BR>
			Telecontrol
			"; 
			$headers="Return-Path: <telecontrol@telecontrol.com.br>\nFrom:".$remetente."\nContent-type: text/html\n"; 
			mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem), $headers);
		}
		header("Location: $PHP_SELF");
		exit;
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
					tbl_revenda.ie           ,
					tbl_revenda.logo         ,
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
		$fone             = trim(pg_result($res,0,fone));
		$fax              = trim(pg_result($res,0,fax));
		$contato          = trim(pg_result($res,0,contato));
		$ie               = trim(pg_result($res,0,ie));
		$logo             = pg_result($res,0,logo);
	}
}

$visual_black = "manutencao-admin";

$title     = "CADASTRO DE REVENDAS";
$cabecalho = "Cadastro de Revendas";

$layout_menu = "cadastro";
include 'cabecalho.php';

?>
<script type="text/javascript" src="js/jquery-latest.pack.js"></script>
<script type="text/javascript" src="js/thickbox.js"></script>
<link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen" />
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
		alert("Informe toda ou parte da informação para realizar a pesquisa");
	}
}

</script>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 1px solid;
	color:#596d9b;
	background-color: #B9C7E3;
}

.border {
	border: 1px solid #ced7e7;
}

.table_line {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #ffffff
}

input {
	font-size: 10px;
}

.top_list {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color:#596d9b;
	background-color: #d9e2ef
}

.line_list {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: normal;
	color:#596d9b;
	background-color: #ffffff
}

.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}


.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}


.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
}

.subtitulo{

	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.texto_avulso{
	font: 14px Arial; color: rgb(89, 109, 155);
	background-color: #d9e2ef;
	text-align: center;
	width:700px;
	margin: 0 auto;
}

</style>

<?

if (strlen ($msg_erro) > 0) {
	echo "<table width='700' align='center' border='1' bgcolor='#ffeeee'>";
	echo "<tr class='msg_erro'>";
	echo "<td>";
	echo $msg_erro;
	echo "</td>";
	echo "</tr>";
	echo "</table>";
}
echo "<table class='formulario' align='center' width='700' border='0' cellspacing='0' >";

echo "<tr height='20' class='titulo_tabela'>";
echo "<td align='right' colspan='3'><b>Cadastro de Produto</b></td>";
echo "<td align='right'><a href='revenda_inicial.php'>Menu de Revendas</a></td>";
echo "</tr>";
echo "<tr><td colspan='4' ><br>";
$aba = 4;
include "revenda_cabecalho.php";
echo "<br>&nbsp;</td></tr>";
echo "<tr><td colspan='4'>";
?>

<table width='700' align='center' border='0' bgcolor='#d9e2ef'>
<tr>
	<td align='center'>
		
		Para incluir uma nova revenda, preencha somente seu CNPJ e clique em gravar.
		<br>
		Faremos uma pesquisa para verificar se a revenda já está cadastrada em nosso banco de dados.
		
	</td>
</tr>
</table>

<form name="frm_revenda" method="post" action="<? echo $PHP_SELF ?>" enctype='multipart/form-data'>
<input type="hidden" name="revenda" value="<? echo $revenda ?>">
<table width='650' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr>
		<td><b><?echo $erro;?></b></td>
	</tr>
</table>

<table class="border" width='700'  class="formulario" align='center' border='0' cellpadding="1" cellspacing="3">
	<tr>
		<td colspan="5" class="titulo_tabela">Cadastro de Revendas</td>
	</tr>
	<tr>
		<td width="50">&nbsp;</td>
		<td>Cnpj</td>
		<td>I.E.</td>
		<td>Fone</td>
		<td>Fax</td>
	</tr>
	<tr >
		<td width="50">&nbsp;</td>
		<td><input type="text" name="cnpj" size="18" maxlength="18" value="<? echo $cnpj ?>" class='frm'>&nbsp;<img src='../imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_revenda_pesquisa (document.frm_revenda.nome,document.frm_revenda.cnpj,'cnpj')"></td>
		<td><input type="text" name="ie" size="18" maxlength="20" value="<? echo $ie ?>" style="width:100px" class='frm'></td>
		<td><input type="text" name="fone" size="15" maxlength="20" value="<? echo $fone ?>" class='frm'></td>
		<td><input type="text" name="fax" size="15" maxlength="20" value="<? echo $fax ?>" class='frm'></td>
	</tr>
	<tr>
		<td width="50">&nbsp;</td>
		<td>Contato</td>
		<td colspan ='3'>Razão Social</td>
	</tr>
	<tr>
		<td width="50">&nbsp;</td>
		<td><input type="text" name="contato" size="30" maxlength="30" value="<? echo $contato ?>" style="width:100px" class='frm'></td>
		<td colspan ='3'><input type="text" name="nome" size="50" maxlength="60" value="<? echo $nome ?>" style="width:300px" class='frm'>&nbsp;<img src='../imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_revenda_pesquisa (document.frm_revenda.nome,document.frm_revenda.cnpj,'nome')"></td>
	</tr>
	<tr>
		<td colspan="5" align="center">
			<a href='<? echo $PHP_SELF ?>?listar=todos#revendas'><img src="imagens/btn_listarevenda.gif"></a>
		</td>
	</tr>
</table>

<br>

<table class="border" width='700' align='center' class="formulario" border='0' cellpadding="1" cellspacing="3">
	<tr>
		<td width="20">&nbsp;</td>
		<td colspan="2">Endereço</td>
		<td>Número</td>
		<td colspan="2">Complemento</td>
	</tr>
	<tr>
		<td width="20">&nbsp;</td>
		<td colspan="2"><input type="text" name="endereco" size="42" maxlength="50" value="<? echo $endereco ?>" class='frm'></td>
		<td><input type="text" name="numero" size="10" maxlength="10" value="<? echo $numero ?>" class='frm'></td>
		<td colspan="2"><input type="text" name="complemento" size="35" maxlength="40" value="<? echo $complemento ?>" class='frm'></td>
	</tr>
	<tr>
		<td width="20">&nbsp;</td>
		<td colspan="2">Bairro</td>
		<td>Cep</td>
		<td>Cidade</td>
		<td>Estado</td>
	</tr>
	<tr>
		<td width="20">&nbsp;</td>
		<td colspan="2"><input type="text" name="bairro" size="40" maxlength="20" value="<? echo $bairro ?>" class='frm'></td>
		<td><input type="text" name="cep"    size="10" maxlength="8" value="<? echo $cep ?>" class='frm'></td>
		<td><input type="text" name="cidade" size="30" maxlength="30" value="<? echo $cidade ?>" class='frm'></td>
		<td><input type="text" name="estado" size="2"  maxlength="2"  value="<? echo $estado ?>" class='frm'></td>
	</tr>
</table>
<br>
<?
if(isset($revenda)){
$sql = "SELECT senha,email FROM tbl_revenda_fabrica WHERE revenda = $revenda AND fabrica = $login_fabrica";
$res = pg_exec($con,$sql);
if(pg_numrows($res)>0){
	$senha = pg_result($res,0,senha);
	$email = pg_result($res,0,email);
}
}
?>
<table class="border" width='700' class="formulario" align='center' border='0' cellpadding="1" cellspacing="3">
	<tr>
		<td width="50">&nbsp;</td>
		<td>E-mail</td>
		<td>Senha</td>
	</tr>
	<tr>
		<td width="50">&nbsp;</td>
		<td>
			<input type="text" name="email" size="40" maxlength="50" value="<? echo $email ?>" class='frm'>
		</td>
		<td>
			<input type="password" name="senha" size="30" maxlength="30" value="<? echo $senha ?>" class='frm'>
		</td>
 	</tr>
	<tr>
		<td width="50">&nbsp;</td>
		<td colspan='2'>Logo</td>
	</tr>
	<tr>
			<td width="50">&nbsp;</td>
			<td colspan='2'>
			<?php

			$qtde_imagens = 1;


			echo "<input type='hidden' name='qtde_de_fotos' value='$qtde_imagens'>";

			echo '<B>Selecione a imagem:</B> <input type="file" value="Procurar foto" name="arquivos[]" class="multi {accept:\'jpg|gif|png\', max:'.$qtde_imagens.', STRING: {remove:\'Remover\',selected:\'Selecionado: $file\',denied:\'Tipo de arquivo inválido: $ext!\'}}" size="30" />';
			if(strlen($logo)>0){
			echo "<BR><a href='$PHP_SELF?ajax=true&imagem=$logo&keepThis=trueTB_iframe=true&height=340&width=420' title='Logo' class='thickbox'>Imagem Cadastrada</a>";
			}
			?>
			</td>
	</tr>


</table>
<br>

<center>

<input type='hidden' name='btn_acao' value=''>
<input type="button" style="background:url(imagens_admin/btn_gravar.gif); width:75px; cursor:pointer;" value="&nbsp;" onclick="javascript: if (document.frm_revenda.btn_acao.value == '' ) { document.frm_revenda.btn_acao.value='gravar' ; document.frm_revenda.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0'>

<input type='hidden' name='btn_descredenciar' value=''>
<input type="button" style="background:url(imagens_admin/btn_apagar.gif); width:75px; cursor:pointer;" value="&nbsp;"  onclick="javascript: if (document.frm_revenda.btn_descredenciar.value == '' ) { if(confirm('Deseja realmente EXCLUIR esta REVENDA?') == true) { document.frm_revenda.btn_descredenciar.value='descredenciar'; document.frm_revenda.submit(); }else{ return; }; } else { alert ('Aguarde submissão') }" ALT="Apagar a Ordem de Serviço" border='0'>

<input type="button" style="background:url(imagens_admin/btn_limpar.gif); width:75px; cursor:pointer;" value="&nbsp;"  onclick="javascript: if (document.frm_revenda.btn_acao.value == '' ) { document.frm_revenda.btn_acao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" ALT="Limpar campos" border='0'>

</center>
<br>
</form>
<form name='frm_login' method='post' target='_blank' action='../index.php?ajax=sim&acao=validar&redir=sim'>
<input type="hidden" name="login">
<input type="hidden" name="senha">
<input type="hidden" name="btnAcao" value="Enviar">

<center>
<!-- HD 13832 não logar mais como posto...
<a href='javascript: alert("Atenção, irá abrir uma nova janela para que se trabalhe como se fosse esta revenda ! " + document.frm_revenda.cnpj.value); document.frm_login.login.value = document.frm_revenda.email.value ; document.frm_login.senha.value = document.frm_revenda.senha.value ; document.frm_login.submit() ; document.location = "<? echo $PHP_SELF ?>";'><img src="imagens/btn_comoestarevenda.gif" alt="Clique Aqui para acessar como se fosse este POSTO"></a>
-->
</center>
</form>
<p>
</td></tr></table>
<?
if ($_GET ['listar'] == 'todos') {
	$sql = "SELECT	tbl_revenda.revenda,
					tbl_revenda.nome           ,
					tbl_revenda.endereco        ,
					tbl_revenda.bairro         ,
					tbl_revenda.complemento    ,
					tbl_revenda.numero         ,
					tbl_revenda.cep            ,
					tbl_revenda.cnpj           ,
					tbl_revenda.fone           ,
					tbl_revenda.fax            ,
					tbl_revenda.contato        ,
					tbl_revenda.fax            ,
					tbl_revenda.email          ,
					tbl_revenda.ie             ,
					tbl_cidade.nome AS cidade  ,
					tbl_cidade.estado
			FROM     tbl_revenda
			JOIN     tbl_cidade USING(cidade)
			ORDER BY tbl_revenda.nome ASC";
	$res = pg_exec ($con,$sql);

	for ($i = 0; $i < pg_numrows ($res); $i++) {
		if ($i % 20 == 0) {
			if ($i > 0) echo "</table>";
			flush();

			echo "<table width='700' align='center' border='0' class='tabela'>";
			echo "<tr class='titulo_coluna'>";

			echo "<td style='width: 200px;'>";
			echo "<b>Cidade</b>";
			echo "</td>";

			echo "<td width='40'>";
			echo "<b>Estado</b>";
			echo "</td>";

			echo "<td >";
			echo "<b>Nome</b>";
			echo "</td>";

			echo "<td width='140' nowrap>";
			echo "<b>CNPJ</b>";
			echo "</td>";

			echo "</tr>";
		}

		$cnpj = pg_result ($res,$i,cnpj);
		$cnpj = substr($cnpj,0,2) .".". substr($cnpj,2,3) .".". substr($cnpj,5,3) ."/". substr($cnpj,8,4) ."-". substr($cnpj,12,2);

		$cor = ($i % 2 == 0) ? "#F7F5F0" : "#F1F4FA" ;
		echo "<tr bgcolor='$cor'>";

		echo "<td align='left'>";
		echo pg_result ($res,$i,cidade);
		echo "</td>";

		echo "<td align='center' width='40'>";
		echo pg_result ($res,$i,estado);
		echo "</td>";

		echo "<td align='left'>";
		echo "<a href='$PHP_SELF?revenda=" . pg_result ($res,$i,revenda) . "'>";
		echo pg_result ($res,$i,nome);
		echo "</a>";
		echo "</td>";

		echo "<td align='left' width='140' nowrap>";
		echo $cnpj;
		echo "</td>";

		echo "</tr>";
	}

	echo "</table>";
}

?>

<p>

<? include "rodape.php"; ?>
