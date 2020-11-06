<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";

include 'autentica_admin.php';
include 'funcoes.php';

if($_POST){
	$referencia = $_POST['referencia'];
	$descricao  = $_POST['descricao'];

	if(empty($referencia)){
		$msg_erro = "Informe uma peça para pesquisa";
	}
}
$title = "Relatório produto por peça";
$layout_menu = "callcenter";
include "cabecalho.php";
?>

<script type="text/javascript" src="plugins/shadowbox/shadowbox.js"></script>
<link rel="stylesheet" type="text/css" href="plugins/shadowbox/shadowbox.css" media="all">
<script type='text/javascript'>
	
	$().ready(function() {
		Shadowbox.init();
	});

	function pesquisaPeca(peca,tipo){

		if (jQuery.trim(peca.value).length > 2){
			Shadowbox.open({
				content:	"peca_pesquisa_nv.php?"+tipo+"="+peca.value,
				player:	"iframe",
				title:		"Peça",
				width:	800,
				height:	500
			});
		}else{
			alert("Informar toda ou parte da informação para realizar a pesquisa!");
			peca.focus();
		}

	}
	
	function retorna_dados_peca(peca,referencia,descricao,ipi,origem,estoque,unidade,ativo){
		gravaDados('referencia',referencia);
		gravaDados('descricao',descricao);
	}


	function gravaDados(name, valor){
		try{
			$("input[name="+name+"]").val(valor);
		} catch(err){
			return false;
		}
	}

</script>

<style type="text/css">

body {
	margin: 0px;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
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

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

</style>
<?php
	if($msg_erro){
?>
		<table align='center' width='700'>
			<tr class='msg_erro'>
				<td><?=$msg_erro?>
			</tr>
		</table>
<?php
	}
?>

<form name='frm_consulta' method='post'>
	<table align='center' width='700' class='formulario'>
		<caption class='titulo_tabela'>Parâmetros de Pesquisa</caption>
		<tr><td>&nbsp;</td></tr>
		<tr>
			<td width="150">&nbsp;</td>
			<td>
				Referência <br />
				<input class='frm' type='text' name='referencia' value='<?=$referencia;?>' size='8' maxlength='20'>
				<a href="javascript: pesquisaPeca(document.frm_consulta.referencia,'referencia')">
				<IMG SRC='imagens/lupa.png' style='cursor : pointer'></a>
			</td>
			<td>
				Descrição <br />
				<input class='frm' type='text' name='descricao' value='<?=$descricao;?>' size='30' maxlength='50'>
				<a href="javascript: pesquisaPeca(document.frm_consulta.descricao,'descricao')">
				<IMG SRC='imagens/lupa.png' style='cursor : pointer'></a>
			</td>
		</tr>
		<tr><td>&nbsp;</td></tr>
		<tr>
			<td colspan='3' align='center'>
				<input type='submit' value='Pesquisar'>
			</td>
		</tr>
		<tr><td>&nbsp;</td></tr>
	</table>
</form>

<?php
	if($_POST AND empty($msg_erro)){
		$sql = "SELECT  DISTINCT
					tbl_produto.referencia,
					tbl_produto.descricao
			FROM    tbl_produto
			JOIN    tbl_lista_basica USING (produto)
			JOIN    tbl_peca         USING (peca)
			JOIN    tbl_linha        USING (linha)
			WHERE   tbl_linha.fabrica     = $login_fabrica
			AND     tbl_peca.referencia = '$referencia'
			AND     tbl_peca.fabrica = $login_fabrica
			ORDER BY tbl_produto.referencia;";
		$res = pg_query ($con,$sql);

		if (pg_numrows($res) > 0) {
?>	
			<br />
			<table align='center' width='700' class='tabela'>
				<caption class='titulo_tabela'>Equipamentos que Possuem esta Peça</caption>
				<tr class='titulo_coluna'>
					<th>Referência</th>
					<th>Descrição</th>
				</tr>
<?php
			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				$ref = trim(pg_fetch_result($res,$i,'referencia'));
				$des = trim(pg_fetch_result($res,$i,'descricao'));
				$cor = ($i % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

?>
				<tr bgcolor='<?=$cor?>'>
					<td align='left'><?=$ref?></td>
					<td align='left'><?=$des?></td>
				</tr>
<?php
			}
		}
?>
			</table>
<?php
	}
	include "rodape.php";
?>
