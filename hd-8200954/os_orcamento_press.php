<?
	include "dbconfig.php";
	include "includes/dbconnect-inc.php";
	include "autentica_usuario.php";
	include "funcoes.php";

	$layout_menu = "os";
	$title = traduz("confirmacao.ordem.servico.orcamento", $con, $cook_idioma);

	include "cabecalho.php";

	$os_orcamento = trim($_GET['os_orcamento']);
	if(strlen($os_orcamento) > 0){
		$sql = "SELECT 
				tbl_os_orcamento.os_orcamento		,
				tbl_os_orcamento.consumidor_nome	,
				tbl_os_orcamento.consumidor_fone	,
				tbl_os_orcamento.consumidor_email	,
				tbl_produto.referencia				,
				tbl_produto.descricao				,
				tbl_os_orcamento.abertura			,
				tbl_os_orcamento.orcamento_envio		,
				tbl_os_orcamento.orcamento_aprovacao	,
				tbl_os_orcamento.orcamento_aprovado	,
				tbl_os_orcamento.conserto			,
				tbl_os_orcamento.data_digitacao		
			FROM 
				tbl_os_orcamento 
				JOIN tbl_produto ON (tbl_produto.produto = tbl_os_orcamento.produto)
			WHERE 
				tbl_os_orcamento.posto = $login_posto
				AND tbl_os_orcamento.fabrica = $login_fabrica
				AND tbl_os_orcamento.os_orcamento = $os_orcamento;";

		$res = pg_exec($con, $sql);
		if(pg_numrows($res) == 1){
			$consumidor_nome	= pg_fetch_result($res,0,consumidor_nome);
			$produto_referencia	= pg_fetch_result($res,0,referencia);
			$produto_descricao	= pg_fetch_result($res,0,descricao);
			$abertura			= mostra_data_hora(pg_fetch_result($res,0,abertura));
			$orcamento_envio	= mostra_data_hora(pg_fetch_result($res,0,orcamento_envio));
			$orcamento_aprovacao	= mostra_data_hora(pg_fetch_result($res,0,orcamento_aprovacao));
			$orcamento_aprovado	= pg_fetch_result($res,0,orcamento_aprovado);
			$conserto			= mostra_data_hora(pg_fetch_result($res,0,conserto));
			$consumidor_nome	= pg_fetch_result($res,0,consumidor_nome);
			$consumidor_fone		= pg_fetch_result($res,0,consumidor_fone);
			$consumidor_email	= pg_fetch_result($res,0,consumidor_email);
			$data_digitacao		= mostra_data_hora(pg_fetch_result($res,0,data_digitacao));
		}else{
			//echo "<script>window.location.href='os_orcamento_consulta.php';</script>";
			exit;
		}
	}else{
		//echo "<script>window.location.href='os_orcamento_consulta.php';</script>";
		exit;
	}

	function verificaStatusForaGarantia($status){
		return ($status == 'f') ? " style='color: #CC0000;' " :  " style='color: #009966;' " ;
	}
?>
<link rel="stylesheet" type="text/css" href="css/lupas/lupas.css">
<style type="text/css">
	.Titulo {
		text-align: center;
		font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
		font-size: 10px;
		font-weight: bold;
		color: #FFFFFF;
		background-color: #596D9B;
	}
	.Conteudo {
		font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
		font-size: 10px;
		font-weight: normal;
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
		font: bold 14px "Arial";
		color:#FFFFFF;
		text-align:center;
		width: 700px;
		padding: 3px 0;
		margin: 0 auto;
	}


	.formulario{
		background-color:#D9E2EF;
		font:11px Arial;
		text-align:left;
		border: 1px solid #596d9b;
	}

	.subtitulo{

		background-color: #7092BE;
		font:bold 14px Arial;
		color: #FFFFFF;
		text-align:center;
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
		border-collapse: collapse;
		border:1px solid #596d9b;
	}

	.informacao{
		font: 14px Arial; color:rgb(89, 109, 155);
		background-color: #C7FBB5;
		text-align: center;
		width:700px;
		margin: 0 auto;
		border-collapse: collapse;
		border:1px solid #596d9b;
	}

	.espaco{
		padding-left:80px; 
		width: 220px;
	}

	.lp_tabela th{
		text-align: left;
		background: #CED7E7;
		color: #000;
		text-align: right;
		padding-right: 5px;
	}

	.lp_tabela th.title{
		background: #485989;
		text-transform:uppercase;
		text-align: left;
		color: #FFF;
	}

	.lp_tabela{
		background: #36497C;
	}

	.lp_tabela td{
		background: #F4F7FB;
	}

	.os_orcamento{
		font-size: 28px;
		text-align: center;
		font-weight: 800;
	}
</style>
<br>
<table border='0' cellspacing='1' cellspading='0' style='width: 700px' class='lp_tabela'>
	<tr class='title'>
		<th colspan='6' style='text-align: center' class='title'><?php echo $title;?></th>
	</tr>
	<tr>
		<td rowspan='5' colspan='2' width='200px'><div class='os_orcamento' <?php echo verificaStatusForaGarantia($orcamento_aprovado);?>><?php echo $os_orcamento;?><div></td>
	</tr>
	<tr>
		<th colspan='4' class='title'><?php echo traduz("datas.da.os", $con, $cook_idioma);?></th>
	</tr>
	<tr>
		<th><?php echo traduz("entrada", $con, $cook_idioma);?></th>
		<td><?php echo $abertura;?></td>
		<th><?php echo traduz("orcamento", $con, $cook_idioma);?></th>
		<td><?php echo $orcamento_envio;?></td>
	</tr>
	<tr>
		<th><?php echo traduz("aprovacao", $con, $cook_idioma);?></th>
		<td><?php echo $orcamento_aprovacao;?></td>
		<th><?php echo traduz("conserto", $con, $cook_idioma);?></th>
		<td><?php echo $conserto;?></td>
	</tr>
	<tr>
		<th><?php echo traduz("digitacao", $con, $cook_idioma);?></th>
		<td colspan='5'><?php echo $data_digitacao;?></td>
	</tr>
	<tr class='title'>
		<th colspan='6' width='200px' style='text-align: center' class='title'><?php echo traduz("informacoes.do.produto", $con, $cook_idioma);?></th>
	</tr>
	<tr>
		<th><?php echo traduz("referencia.do.produto", $con, $cook_idioma);?></th>
		<td><?php echo $produto_referencia;?></td>
		<th><?php echo traduz("descricao.do.produto", $con, $cook_idioma);?></th>
		<td colspan='3'><?php echo $produto_descricao;?></td>
	</tr>
	<tr class='title'>
		<th colspan='6' width='200px' style='text-align: center' class='title'><?php echo traduz("informacoes.sobre.consumidor", $con, $cook_idioma);?></th>
	</tr>
	<tr>
		<th><?php echo traduz("nome.consumidor", $con, $cook_idioma);?></th>
		<td colspan='5'><?php echo $consumidor_nome;?></td>
	</tr>
	<tr>
		<th><?php echo traduz("fone", $con, $cook_idioma);?></th>
		<td><?php echo $consumidor_fone;?></td>
		<th><?php echo traduz("email", $con, $cook_idioma);?></th>
		<td colspan='3'><?php echo $consumidor_email;?></td>
	</tr>
	<tr>
		<td colspan='6' style='text-align: center;'>
			<br>
			<input type='button' value=' <?php echo traduz("imprimir", $con, $cook_idioma);?> ' onclick="javascript: window.open('os_orcamento_print.php?os_orcamento=<?php echo $os_orcamento;?>');" />
			<br><br>
		</td>
	</tr>
</table>
<?php 
	include "rodape.php";