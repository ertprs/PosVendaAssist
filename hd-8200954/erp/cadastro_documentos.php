<?php
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';
include '../funcoes.php';

$gravar = $_POST["gravar"]; 
if(strlen($gravar)>0) {

	$pessoa    = $_POST["pessoa"]; 
	$titulo    = $_POST["titulo"]; 
	$descricao = $_POST["descricao"]; 

	if(strlen($titulo)==0)    $msg_erro = "Escreva o Título do Documento";
	if(strlen($descricao)==0) $msg_erro = "Escreva o Descrição do Documento";

	if (isset($_FILES['arquivos']) AND strlen($msg_erro)==0){
		$res = pg_exec ($con,"BEGIN TRANSACTION");
		$Destino = '/www/assist/www/erp/documentos/'; 

		$Arquivos = $_FILES['arquivos'];

		for ($i=0; $i<6; $i++){
			$arquivo_doc = isset($Arquivos['tmp_name'][$i]) ? $Arquivos['tmp_name'][$i] : FALSE;       
			if (!$arquivo_doc) continue;
			
			$Nome    = $Arquivos['name'][$i]; 
			$Tamanho = $Arquivos['size'][$i]; 
			$Tipo    = $Arquivos['type'][$i]; 
			$Tmpname = $Arquivos['tmp_name'][$i];
			
			if (strlen($Nome)==0) continue;

			if(!is_uploaded_file($Tmpname)){
				$msg_erro .= "Não foi possível efetuar o upload.";
				break;
			}

			$nome_documento  = "documento_$login_empresa-$login_loja-$id_pessoa-$i-$Nome";
			$nome_documento  = str_replace(" ","_",$nome_documento);

			$Caminho_documento  = $Destino . $nome_documento;
			if(move_uploaded_file($Tmpname,$Caminho_documento)){
			$sql = "INSERT INTO tbl_pessoa_documento         
						(titulo,descricao, caminho,pessoa,empregado)
					VALUES ('$titulo','$descricao','$Caminho_documento',$pessoa,$login_empregado)";
			$res = pg_exec ($con,$sql);
			$msg_erro .= pg_errormessage($con);

			}
		} 
	}

	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		$msg = "Arquivo gravado com sucesso!";
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}




if(strlen($_GET["pessoa"])>0) $pessoa = $_GET["pessoa"];
if(strlen($pessoa)>0){
	$sql = "SELECT nome FROM tbl_pessoa WHERE pessoa = $pessoa";
	$res = pg_exec($sql);
	if(pg_numrows($res)>0){
		$nome = pg_result($res,0,0);
	}
}



?>
<html>
<head>
<title>Cadastro de Arquivos</title>

<script type="text/javascript" src="jquery/jquery-latest.pack.js"></script>
	<script src="jquery/jquery.MultiFile.js" type="text/javascript" language="javascript"></script>
<script src="jquery/jquery.MetaData.js" type="text/javascript" language="javascript"></script>
<style>
.ok{
	font-family: Verdana;
	font-size: 12px;
	color:blue;
	border:#39AED5 1px solid; background-color: #B0DFEE;
}
</style>
<link type="text/css" rel="stylesheet" href="css/estilo.css">
</head>
<body>
<br>
<center>
<?
if (strlen($msg_erro)>0) echo "<div class='Erro'>$msg_erro</div>";


if (strlen($ok)>0 OR strlen($msg)>0) {
	echo "<center><br><div class='ok'>$msg</div></center>";
}else{
?>
<table class='HD' align='center' width='500' border='0' class='tabela'>
<form action="<? echo $PHP_SELF ?>" method="post" enctype="multipart/form-data" name="formFoto">
<input  type="hidden" name="documento" value="<? echo $documento ?>">
<input type='hidden' name='pessoa' value='<?=$pessoa?>'>
	<tr height='20'>
		<td  colspan='2'><? echo "<b>$nome</b>";?></td>
	</tr>
	<tr height='3'>
		<td  colspan='2'>&nbsp;</td>
	</tr>
	<tr>
		<td class='Label'>Titulo</td>
		<td align='left'><input class="Caixa" type="text" name="titulo" size="50" maxlength="50" value="<? echo $referencia ?>"></td>
	</tr>
	<tr>
		<td class='Label'>Descrição</td>
		<td><textarea class="Caixa" name="descricao" rows='3' cols='70'><? echo $descricao ?></textarea></td>
	</tr> 
	<tr>
		<td class='Label' valign='top'>Arquivo</td>
		<td><input type="file" name="arquivos[]" class='multi {accept:"jpg|gif|png|xls|doc|pdf|ppt", max:6, STRING: {remove:"Remover",selected:"Selecionado: $file",denied:"Tipo de arquivo inválido: $ext!"}}' /></td>
	</tr>
	<tr>
		<td colspan='2'  align='center'><input type="submit" name="gravar" value="Gravar Documento" style='width: 140px;height:30px;font-size:12px '> </td>
	</tr>
</form>
</table>
<?}

$sql = $sql = "SELECT
				pessoa_documento,
				titulo          ,
				descricao       ,
				data            ,
				caminho         ,
				(
					SELECT nome 
					FROM tbl_pessoa 
					JOIN tbl_empregado USING (pessoa) 
					WHERE empregado = tbl_pessoa_documento.empregado
				) AS empregado_nome
		FROM tbl_pessoa_documento 
		WHERE pessoa = $pessoa
		";
$res = pg_exec ($con,$sql);

if (@pg_numrows($res) > 0) {

	echo "<P><font size='2'><b>Documentos Cadastrados</b>";
	echo "<table border='0' cellpadding='2' cellspacing='0' class='HD' align='center' width='550'>";
	echo "<TR height='20' bgcolor='#DDDDDD' align='center'>";
	echo "<TD width='100' align='left'><b>Título</b></TD>";
	echo "<TD width='100' align='left'><b>Descrição</b></TD>";
	echo "<TD width='100' ><b>Data Cadastro</b></TD>";
	echo "<TD width='100' ><b>Colaborador</b></TD>";
	echo "<TD width='100' colspan='2'><b>Opções</b></TD>";
	echo "</TR>";

	for ($i=0; $i<pg_numrows($res); $i++){

		$x=$i+1;

		$pessoa_documento  = pg_result($res,$i,pessoa_documento);
		$titulo            = pg_result($res,$i,titulo);
		$descricao         = pg_result($res,$i,descricao);
		$data              = pg_result($res,$i,data); 
		$caminho           = pg_result($res,$i,caminho); 
		$empregado_nome    = pg_result($res,$i,empregado_nome); 

		$caminho = str_replace("/www/assist/www/erp/","",$caminho);

		if($cor1=="#eeeeee")$cor1 = '#ffffff';
		else                $cor1 = '#eeeeee';

		echo "<TR bgcolor='$cor1'class='Conteudo'>";
		echo "<TD align='left'>$titulo</TD>";
		echo "<TD align='center'nowrap>$descricao</TD>";
		echo "<TD align='center'nowrap>$data</TD>";
		echo "<TD align='center'nowrap>$empregado_nome</TD>";
		echo "<TD align='center'nowrap><a href='$caminho' target='_blank'>Ver</a></TD>";
		echo "<TD align='center'nowrap>Excluir</TD>";
		echo "</TR>";

	}
echo " </TABLE>";

}else{
    echo "<b>Nenhuma conta de banco lançada</b>";
}
?>
</body>
</html>