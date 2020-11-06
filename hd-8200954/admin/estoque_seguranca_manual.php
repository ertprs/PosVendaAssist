<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

if($_GET['ajax']){

	$saldo = $_GET['saldo'];
	$posto = $_GET['posto'];
	$peca  = $_GET['peca'];

	$sql = "UPDATE tbl_estoque_posto SET estoque_minimo = $saldo WHERE  fabrica = $login_fabrica AND posto = $posto AND peca = $peca";
	$res = pg_query($con,$sql);

	if(pg_last_error($con)){
		echo pg_last_error($con);
	}else{
		$sql = "SELECT referencia, descricao FROM tbl_peca WHERE peca = $peca AND fabrica = $login_fabrica";
		$res = pg_query($con,$sql);

		$ref  = pg_fetch_result($res, 0, 'referencia');
		$desc = utf8_encode(pg_fetch_result($res, 0, 'descricao'));

		$msg = "O saldo de estoque da pe&ccedil;a $ref - $desc foi atualizado";

		echo "ok|".$msg;
	}

	exit;
}

if($_POST){
	$codigo_posto = $_POST['codigo_posto'];
	$posto_nome	  = $_POST['posto_nome'];

	$sql = "SELECT posto FROM tbl_posto_fabrica 
				WHERE fabrica = $login_fabrica 
				AND codigo_posto = '$codigo_posto'
				AND controle_estoque_manual IS TRUE";
	$res = pg_query($con,$sql);

	if(pg_num_rows($res) > 0){
		$posto = pg_fetch_result($res, 0, 'posto');
	}else{
		$msg_erro = "Posto não controla estoque";
	}
}

$layout_menu = "callcenter";
$titulo = "Estoque Segurança Manual";
$title = "Estoque Segurança Manual";
include 'cabecalho.php';

include "javascript_pesquisas_novo.php"; 

?>
<script language="javascript" src="js/assist.js"></script>
<script language='javascript' src='ajax.js'></script>
<script type="text/javascript" src="js/jquery-1.3.2.js"></script>
<script type="text/javascript" src="js/jquery.corner.js"></script>
<script type="text/javascript" src="js/thickbox.js"></script>
<link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen" />
<script src="../plugins/shadowbox/shadowbox.js"	type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all">
<!--[if IE]>
<link rel="stylesheet" href="css/thickbox_ie.css" type="text/css" media="screen" />
<![endif]-->
<script language="JavaScript">

$(function () {
	Shadowbox.init();

	$("input[id^=saldo_seguranca_]").change(function(){
		var saldo = $(this).val();
		var peca  = $(this).attr('rel');
		var posto = $("#posto").val();

		if(saldo != ""){
			$.ajax({
				url: '<?php echo $PHP_SERVER['PHP_SELF']?>',
				typeData: 'GET',
				data: 'ajax=sim&saldo='+saldo+'&posto='+posto+'&peca='+peca,
				success: function(data){
					var retorno = data.split('|');
					if(retorno[0] != "ok"){
						alert(retorno[0]);
					}else{
						$("#sucesso").html(retorno[1]).show();
						setTimeout(function(){
							$("#sucesso").hide();
						},2000);
					}
				}
			});
		}
	})
});



function retorna_posto (codigo_posto, posto, nome, cnpj, cidade, estado, credenciamento, num_posto)
{
	gravaDados("codigo_posto", codigo_posto);
	gravaDados("posto_nome", nome);
}
</script>

<style type='text/css'>

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
.sucesso{
	background-color:green;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}
.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align: left;
}
.subtitulo {
    background-color: #7092BE;
	color: #FFFFFF;
    font: bold 11px Arial;
    text-align: center;
}
table.tabela{
	margin: auto;
}
table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}
#sucesso{
	margin: auto;
	width: 700px;
}
</style>

<div id='sucesso' class='sucesso'></div>
<form name='frm_consulta' method='post' action='<?=$PHP_SERVER['PHP_SELF']?>'>
	<table cellspacing='1' cellpadding='3' align='center' width='700px' class='formulario'>
		<?php
			if($msg_erro){
		?>
				<tr class='msg_erro'>
					<td colspan='3'><?=$msg_erro?></td>
				</tr>
		<?php
			}
		?>
		<tr>
			<td colspan='3' class='titulo_tabela'>Parâmetros de Pesquisa</td>
		</tr>

		<tr><td>&nbsp;</td></tr>
		
		<tr>
			<td width='23%'>&nbsp;</td>
			<td width='180px'>
				Código Posto <br />
				<input type='text' name='codigo_posto' id='codigo_posto' size='8' value='<?=$codigo_posto?>' class='frm'>
				<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto ('', document.getElementById('codigo_posto'), '')">
			</td>
			<td>
				Nome Posto <br />
				<input type='text' name='posto_nome' id='posto_nome' size='30' value='<?=$posto_nome?>' class='frm'>
				<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto ('', '', document.getElementById('posto_nome'))">
			</td>
		</tr>

		<tr><td>&nbsp;</td></tr>

		<tr>
			<td colspan='3' align='center'>
				<input type='hidden' id='posto' name='posto' value='<?=$posto?>'>
				<input type='submit' name='btn_acao' value='Pesquisar'>
			</td>
		</tr>

		<tr><td>&nbsp;</td></tr>
	</table>
</form> <br />

<?php
	if($posto AND empty($msg_erro)){

		$sql = "SELECT tbl_peca.peca,
						tbl_peca.descricao,
						tbl_peca.referencia,
						tbl_estoque_posto.qtde,
						tbl_estoque_posto.estoque_minimo
					FROM tbl_estoque_posto
					JOIN tbl_peca ON tbl_estoque_posto.peca = tbl_peca.peca AND tbl_peca.fabrica = $login_fabrica
					WHERE tbl_estoque_posto.fabrica = $login_fabrica
					AND tbl_estoque_posto.posto = $posto";
		$res = pg_query($con,$sql);

		if(pg_num_rows($res) > 0){
?>
			<table align='center' width='700' class='tabela'>
				<tr class='titulo_coluna'>
					<th>Referência</th>
					<th>Descrição</th>
					<th>Saldo</th>
					<th>Estoque Segurança</th>
				</tr>
<?php
			for($i = 0; $i < pg_num_rows($res); $i++){
				$referencia 	= pg_fetch_result($res, $i, 'referencia');
				$descricao 		= pg_fetch_result($res, $i, 'descricao');
				$peca 			= pg_fetch_result($res, $i, 'peca');
				$estoque_minimo = pg_fetch_result($res, $i, 'estoque_minimo');
				$qtde 			= pg_fetch_result($res, $i, 'qtde');

				$cor = ($i % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

?>
				<tr bgcolor='<?=$cor?>'>
					<td align='left'><?=$referencia?></td>
					<td align='left'><?=$descricao?></td>
					<td align='center'><?=$qtde?></td>
					<td align='center'>
						<input type='text' name='saldo_seguranca_<?=$i?>' id='saldo_seguranca_<?=$i?>' rel='<?=$peca?>' size='5' class='frm' style='text-align:center' value='<?=$estoque_minimo?>'>
					</td>
				</tr>
<?php
			}
			echo "</table>";
		}
	}

	include "rodape.php";
?>