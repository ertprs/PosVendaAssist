<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include "funcoes.php";
include 'autentica_admin.php';

$msg_erro = "";

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$busca      = $_GET["busca"];
	$tipo_busca = $_GET["tipo_busca"];
	if (strlen($q)>2){
		if ($tipo_busca=="posto"){
			$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
					FROM tbl_posto
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
					WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";
			$sql .=  ($busca == "codigo") ? " AND tbl_posto_fabrica.codigo_posto = '$q' " : " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
			$res = pg_query($con,$sql);
			if (pg_num_rows ($res) > 0) {
				for ($i=0; $i<pg_num_rows ($res); $i++ ){
					$cnpj = trim(pg_fetch_result($res,$i,cnpj));
					$nome = trim(pg_fetch_result($res,$i,nome));
					$codigo_posto = trim(pg_fetch_result($res,$i,codigo_posto));
					echo "$cnpj|$nome|$codigo_posto";
					echo "\n";
				}
			}
		}
	}
	
	exit;
}

$confirma_devolucao=$_GET['confirma_devolucao'];
$extrato=$_GET['extrato'];

if($confirma_devolucao=='sim' and strlen($extrato) > 0){
	$sql=" UPDATE tbl_extrato_extra set pecas_devolvidas='t'
			FROM  tbl_extrato
			WHERE tbl_extrato_extra.extrato=$extrato";
	$res=pg_query($con,$sql);
	$msg_erro = pg_errormessage ($con);

	if($login_fabrica == 40){
		$posto=$_GET['posto'];
		header("Location: $PHP_SELF?posto=$posto");
	}
}

if (strlen($_POST['btn_finalizar']) > 0) $btn_acao = $_POST['btn_finalizar'];

$codigo_posto = $_POST['codigo_posto'];
if (strlen ($codigo_posto) == 0) $codigo_posto = $_GET['codigo_posto'];

$posto_nome   = $_POST['posto_nome'];
if (strlen ($posto_nome) == 0) $posto_nome = $_GET['posto_nome'];

$posto = $_GET['posto'];

if($btn_acao){
	if(strlen($codigo_posto)==0 && strlen($posto_nome)==0)	$msg_erro = "Informe o Código ou o Nome do Posto.";
}

if(strlen($msg_erro)==0){
	if (strlen ($posto) > 0) {
		$sql = "SELECT	tbl_posto_fabrica.codigo_posto,
						tbl_posto.nome
				FROM tbl_posto_fabrica
				JOIN tbl_posto USING (posto)
				WHERE tbl_posto_fabrica.fabrica    = $login_fabrica
				AND tbl_posto_fabrica.posto = '$posto'";
		$res = pg_query ($con,$sql);
		$codigo_posto = pg_fetch_result ($res,0,0);
		$posto_nome   = pg_fetch_result ($res,0,1	);
	}

	if (strlen ($codigo_posto) > 0 AND strlen($posto) == 0) {
		$sql = "SELECT	tbl_posto_fabrica.posto
				FROM tbl_posto_fabrica
				WHERE tbl_posto_fabrica.fabrica    = $login_fabrica
				AND tbl_posto_fabrica.codigo_posto = '$codigo_posto'";
		$res = pg_query ($con,$sql);
		if(pg_num_rows($res) > 0){
			$posto = pg_fetch_result ($res,0,0);
		}
	}
}

include "cabecalho_new.php";

$admin_privilegios	= "gerencia";
$layout_menu 		= "gerencia";
$title 				= "RELATÓRIO DE DEVOLUÇÃO DE PEÇAS OBRIGATÓRIA";
$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable"
	);

include("plugin_loader.php");

?>

<script>
	
$(function(){

	Shadowbox.init();

	$.autocompleteLoad(Array("posto"));

	$("span[rel=lupa]").click(function () {
		$.lupa($(this));
	});


});

function retorna_posto(retorno){
    $("#codigo_posto").val(retorno.codigo);
	$("#descricao_posto").val(retorno.nome);
}

</script>

<?php if (strlen($msg_erro) > 0) { ?>
	<p><div class="alert alert-error">	<h4><?php echo $msg_erro; ?></h4></div></p>
<?php } ?>

<br />
<form name="frm_posto" method="post" action="<? echo $PHP_SELF ?>" class="form-search form-inline tc_formulario">
	<div class="titulo_tabela">Parâmetros de Pesquisa</div>
	<br />
	<div class="container tc_container">

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='codigo_posto'>Código Posto</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>" >
							<span class='add-on' rel="lupa">
								<i class='icon-search' ></i> 
								<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
							</span>
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='Razão Social'>Razão Social</label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<input type="text" name="posto_nome" id="descricao_posto" class='span12' value="<? echo $posto_nome ?>" >
							<span class='add-on' rel="lupa">
								<i class='icon-search' ></i>
								<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
							</span>
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>

	</div>

	<br />
	<center>
		<input type='hidden' name='btn_finalizar' value='0'>
		<input type="button" class='btn' value="Pesquisar" onclick="if (document.frm_posto.btn_finalizar.value == '0') { document.frm_posto.btn_finalizar.value='1'; document.frm_posto.submit() ; } else { alert ('Aguarde submissão da OS...'); }" alt='Clique AQUI para pesquisar'>
	</center>
	<br />

	<center>
		<input type="button" class='btn btn-warning' value="Peças em Devolução Obrigatória" onclick="window.open('peca_consulta.php');">
	</center>

	<br />

</form>
<br />

<?php

flush();
if (strlen ($codigo_posto) > 0 OR strlen ($posto) > 0 ) 
{
	if($login_fabrica == 40)
	{
		$cond = " AND tbl_extrato_extra.pecas_devolvidas IS NOT TRUE";
	}

	$sql = "SELECT	extrato,
			TO_CHAR (data_geracao,'DD/MM/YYYY') AS data_geracao,
			TO_CHAR (aprovado,'DD/MM/YYYY')     AS aprovado,
			total,
			pecas_devolvidas
		FROM tbl_extrato
		JOIN tbl_extrato_extra using(extrato)
		WHERE posto   = $posto
		AND   fabrica = $login_fabrica
		$cond
		ORDER BY extrato DESC
		LIMIT 30";
	$res = @pg_query ($con,$sql);

	if (@pg_num_rows($res) == 0)
	{
		echo "<p><div class='alert alert-warning'><h4>Não foram Encontrados Resultados para esta Pesquisa</h4></div></p>";
	}

	if (@pg_num_rows($res) > 0)
	{
		#hd 15606
		if($login_fabrica==6 or $login_fabrica == 40)
		{
			$xls  = "<div align='center' style='position: relative; center: 0'>";
			$xls .= "<table border='0' cellspacing='0' cellpadding='0'>";
			$xls .= "<tr height='18'>";
			$xls .= "<td width='18' bgcolor='#33CCFF'>&nbsp;</td>";
			$xls .= "<td align='left'><font size='1'><b>&nbsp; Devolução Confirmada</b></font></td>";
			$xls .= "</tr>";
			$xls .= "</table>";
			$xls .= "</div><br>";
		}
		$xls .= "<table width='700' align='center' border='0' class='tabela' cellspacing='1' cellpadding='2'>";
		$xls .= "<tr class='titulo_coluna'>";
		$xls .= "<td align='center' width = '25%'>Extrato</td>";
		$xls .= "<td align='center' width = '25%'>Data Geração</td>";
		$xls .= "<td align='center' width = '25%'>Data Aprovação</td>";
		$xls .= "<td align='right' width = '25%'>Total</td>";
		$xls .= "</tr>";
		
		for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) 
		{
			$extrato			= pg_fetch_result($res,$i,'extrato');
			$data_geracao   	= pg_fetch_result ($res,$i,'data_geracao');
			$aprovacao      	= pg_fetch_result ($res,$i,'aprovado');
			$total          	= pg_fetch_result ($res,$i,'total');
			$total          	= number_format($total,2,",",".");
			$pecas_devolvidas 	= pg_fetch_result ($res,$i,'pecas_devolvidas');

			$cor= ($i % 2 == 0) ? '#F1F4FA' : "#F7F5F0";
			if($login_fabrica==6 and $pecas_devolvidas=='t')	$cor= "#33CCFF";

			$xls .= "<tr bgcolor='$cor'>";
			$xls .= "<td align='center'>";
			$xls .= "<a target='_blank' href='extrato_consulta_os.php?extrato=$extrato&posto=$posto&relatorio=true'>$extrato</a></td>";
			$xls .= "<td align='center'>$data_geracao</td>";
			$xls .= "<td align='center'>$aprovacao</td>";
			$xls .= "<td align='right'>$total &nbsp;</td>";
			$xls .= "</tr>";
		}
		$xls .= "</table><br><br>";
		echo $xls;
		
		echo `rm /tmp/assist/relatorio-devolucao-obrigatoria-$login_fabrica.xls`;
		
		$data_xls = date("Y-m-d_H-i-s");
		$fp = fopen("xls/relatorio-devolucao-obrigatoria-$login_fabrica-$data_xls.xls","w");
		fputs($fp,$xls);
		
		//echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /www/assist/www/admin/xls/relatorio-devolucao-obrigatoria-$login_fabrica-$data_xls.xls /tmp/assist/relatorio-devolucao-obrigatoria-$login_fabrica.html`;
        echo"	<br />
				<center>
				<div id='gerar_excel' class='btn_excel'>
				    <span>
				    	<img src='http://posvenda.telecontrol.com.br/assist/admin/imagens/excel.png' />
			    	</span>
			    	<a href='xls/relatorio-devolucao-obrigatoria-$login_fabrica-$data_xls.xls'>
				    <span class='txt'>Gerar Arquivo Excel</span>
				    </a>
				</div>
				</center>";
		
	}
}

#----------------------- Lista Pecas de um extrato -----------------
$extrato = $_GET['extrato'];

if (strlen ($extrato) > 0) {
	## ESTAVA PEGANDO CONFORME A TELA EM QUE O POSTO VISUALIZA
	$sql = "SELECT	tbl_peca.referencia,
					tbl_peca.descricao ,
					tbl_extrato_extra.pecas_devolvidas,
					SUM (tbl_os_item.qtde) AS qtde
			FROM    tbl_os
			JOIN    tbl_os_extra          ON tbl_os.os                               = tbl_os_extra.os
			JOIN    tbl_produto           ON tbl_os.produto                          = tbl_produto.produto
			JOIN    tbl_os_produto        ON tbl_os.os                               = tbl_os_produto.os
			JOIN    tbl_os_item           ON tbl_os_produto.os_produto               = tbl_os_item.os_produto
			JOIN    tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
			JOIN    tbl_peca              ON tbl_os_item.peca                        = tbl_peca.peca
			JOIN    tbl_extrato           ON tbl_extrato.extrato                     = tbl_os_extra.extrato
			JOIN    tbl_extrato_extra     ON tbl_os_extra.extrato                    = tbl_extrato_extra.extrato
			WHERE   tbl_os_extra.extrato = $extrato
			AND     tbl_extrato.fabrica  = $login_fabrica ";
			if (($posto <> 17674 and $login_fabrica == 6) or $login_fabrica <> 6) {
				$sql .=" AND     tbl_peca.devolucao_obrigatoria      IS TRUE ";
			}
			if ( $login_fabrica <> 40 AND $login_fabrica <> 115 AND $login_fabrica <> 116) {
				$sql.="AND     tbl_os_item.liberacao_pedido        IS TRUE ";
			}
			$sql .= " AND     tbl_servico_realizado.gera_pedido   IS TRUE
			AND     tbl_servico_realizado.troca_de_peca IS TRUE ";
			
			$sql .= "GROUP BY tbl_peca.referencia, tbl_peca.descricao,tbl_extrato_extra.pecas_devolvidas
			ORDER BY SUM (tbl_os_item.qtde);";

	#HD 17436
	if ( in_array($login_fabrica, array(11,43,80,172)) ){
		$sql = "SELECT	tbl_peca.referencia,
						tbl_peca.descricao ,
						'' AS pecas_devolvidas,
						SUM (tbl_faturamento_item.qtde) AS qtde
				FROM    tbl_faturamento
				JOIN    tbl_faturamento_item USING(faturamento)
				JOIN    tbl_peca             USING(peca)
				WHERE   tbl_faturamento.extrato_devolucao = $extrato
				AND     tbl_faturamento.fabrica           = $login_fabrica
				AND     tbl_faturamento.distribuidor IS NULL
				AND     tbl_peca.devolucao_obrigatoria      IS TRUE
				GROUP BY tbl_peca.referencia, tbl_peca.descricao
				ORDER BY SUM (tbl_faturamento_item.qtde) DESC";
	}
	if ($login_fabrica==51){
		$sql = "SELECT	tbl_peca.referencia,
						tbl_peca.descricao ,
						'' AS pecas_devolvidas,
						to_char(tbl_faturamento.conferencia,'DD/MM/YYYY') AS conferencia,
						to_char(tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao,
						SUM (tbl_faturamento_item.qtde) AS qtde
				FROM    tbl_faturamento
				JOIN    tbl_faturamento_item USING(faturamento)
				JOIN    tbl_peca             USING(peca)
				WHERE   tbl_faturamento.extrato_devolucao = $extrato
				AND     tbl_faturamento.fabrica           = $login_fabrica
				AND     tbl_faturamento.distribuidor = 4311
				AND     tbl_peca.devolucao_obrigatoria IS TRUE
				GROUP BY tbl_peca.referencia, tbl_peca.descricao, tbl_faturamento.conferencia
						tbl_faturamento.emissao
				ORDER BY SUM (tbl_faturamento_item.qtde) DESC;";
	}
	$res = pg_query($con,$sql);

	if (pg_num_rows($res) == 0) {?>

		<div class="alert">
			<h4><?php
					echo "Não existem peças com devolução obrigatória ";
					echo ( in_array($login_fabrica, array(11,51,172)) ) ? "neste extrato." : "lançadas em suas Ordens de Serviço";
				?>
			</h4>
		</div>
	<?php
	}
	else
	{
		echo "	<table width='700' class='table table-striped table-bordered table-hover table-large' id='relatorio'>
				<thead>";

				if ( in_array($login_fabrica, array(11,172)) )
				{
					echo "
						<tr class='titulo_tabela'>
							<th align='center' colspan='5'>PEÇAS DE DEVOLUÇÃO OBRIGATÓRIA <br>DO POSTO \"".$posto_nome."\" O LGR ESTÁ ATRELADO AO EXTRATO  \"".$extrato."\" </th>
						</tr>";
				}
				else
				{
					echo "	
						<tr class='titulo_tabela'>
							<th align='center' colspan='3'>PEÇAS DE DEVOLUÇÃO OBRIGATÓRIA <br>DO POSTO \"".$posto_nome."\" O LGR ESTÁ ATRELADO AO EXTRATO  \"".$extrato."\" MAS É COMPOSTO PELAS NF DE REMESSA EM GARANTIA. </th>
						</tr>";
				}

		echo "	<tr class='titulo_tabela'>
					<th align='center' style='cursor:pointer;' >Cod. Peça</th>
					<th align='center' style='cursor:pointer;'>Descrição</th>	
					<th align='center'>Qtde</th>";
			
					if( in_array($login_fabrica, array(11,172)) )
					{
						echo "<th align='center' style='cursor:pointer;'>Num. N.F</th>";
						echo "<th align='center' style='cursor:pointer;'>Status</th>";
					}
		echo "	</tr>
				</thead>
				<tbody>";

		for ($i=0; $i < pg_num_rows($res); $i++)
		{
			$referencia_peca  = trim(pg_fetch_result ($res,$i,'referencia'));

			$sql_nf = "SELECT
						tbl_peca.referencia                               AS referencia,
						tbl_peca.descricao                                AS descricao,
						tbl_faturamento.nota_fiscal                       AS nota_fiscal,
						to_char(tbl_faturamento.emissao,'DD/MM/YYYY')     AS emissao,
						to_char(tbl_faturamento.conferencia,'DD/MM/YYYY') AS conferencia,
						CASE WHEN 
							tbl_faturamento.devolucao_concluida IS NOT TRUE OR tbl_faturamento.devolucao_concluida IS NULL THEN
							FALSE
						ELSE 
							TRUE 
						END                                               AS pecas_devolvidas,
						SUM (tbl_faturamento_item.qtde_inspecionada)      AS qtde_inspecionada,
						tbl_faturamento_item.qtde_inspecionada_real       AS qtde_inspecionada_real,
						SUM (tbl_faturamento_item.qtde)                   AS qtde
					FROM
						tbl_faturamento
						JOIN   tbl_faturamento_item                                   USING(faturamento)
						JOIN   tbl_peca                                               USING(peca)
						JOIN   tbl_posto         ON   tbl_faturamento.distribuidor    = tbl_posto.posto
						JOIN   tbl_posto_fabrica ON   tbl_posto_fabrica.posto         = tbl_posto.posto
						JOIN   tbl_extrato       ON   tbl_extrato.extrato             = tbl_faturamento.extrato_devolucao
					WHERE   tbl_faturamento.distribuidor             IS NOT NULL 
						AND     tbl_faturamento.fabrica              = $login_fabrica
						AND     tbl_faturamento.extrato_devolucao    = $extrato
						AND     tbl_peca.referencia                  = '$referencia_peca'
						AND     tbl_peca.devolucao_obrigatoria       IS TRUE
						AND     tbl_posto_fabrica.fabrica            = $login_fabrica
						AND     tbl_posto_fabrica.codigo_posto       = '$codigo_posto'
					GROUP BY 
						tbl_peca.referencia, 
						tbl_faturamento.nota_fiscal, 
						tbl_faturamento.posto, 
						tbl_faturamento.distribuidor, 
						tbl_faturamento.conferencia, 
						tbl_peca.descricao, 
						tbl_faturamento.emissao,
						devolucao_concluida,
						qtde_inspecionada_real
					ORDER BY SUM (tbl_faturamento_item.qtde) DESC";
			$res_nf = pg_query($con,$sql_nf);

			if ( in_array($login_fabrica, array(11,172)) )
			{
				$referencia_peca     = @trim(pg_fetch_result ($res,$i,'referencia'));
				$descricao_peca      = @trim(pg_fetch_result ($res,$i,'descricao'));
				$nota_fiscal         = @trim(pg_fetch_result ($res_nf,0,'nota_fiscal'));
				$qtde                = @trim(pg_fetch_result ($res,$i,'qtde'));
				$pecas_devolvidas    = @trim(pg_fetch_result ($res_nf,0,'pecas_devolvidas'));
				$conferida           = @trim(pg_fetch_result ($res_nf,0,'conferencia'));
				$emissao             = @trim(pg_fetch_result ($res_nf,0,'emissao'));
				$qtde_inspecionada   = @trim(pg_fetch_result ($res_nf,0,'qtde_inspecionada'));
			}
			else
			{
				$referencia_peca  = trim(pg_fetch_result ($res,$i,'referencia'));
				$descricao_peca   = trim(pg_fetch_result ($res,$i,'descricao'));
				$nota_fiscal      = @trim(pg_fetch_result ($res,$i,'nota_fiscal'));
				$qtde             = trim(pg_fetch_result ($res,$i,'qtde'));
				$pecas_devolvidas = trim(pg_fetch_result ($res,$i,'pecas_devolvidas'));
			}

			if ( in_array($login_fabrica, array(11,172)) ){
				if ($qtde_inspecionada == 0){
					if (strlen($nota_fiscal) > 0){
						$status_conf = 'N';
						$desc_status = 'NÃO CONFERIDA';
					}else{
						$status_conf = 'S';
						$desc_status = 'SEM NOTA EMITIDA';
					}
				}elseif ($qtde == $qtde_inspecionada){
					// NOTA CONFERIDA
					$status_conf = 'C';
					$desc_status = 'CONFERIDA TOTAL';
				}elseif ($qtde > $qtde_inspecionada){
					$status_conf = 'P';
					$desc_status = 'CONFERIDA PARCIALMENTE';
				}
			}
			
			$cor = ($i % 2 == 0) ? '#F1F4FA' :  "#F7F5F0";
			
			echo "<tr bgcolor='$cor' class='titulo_coluna' >
					<td align='center'>$referencia_peca</td>
					<td align='left'>$descricao_peca</td>
					<td align='center'>$qtde</td>";
			
			if( in_array($login_fabrica, array(11,172)) )
			{
				echo "	<td align='center'>$nota_fiscal</td>
						<td align='center'><label title='$desc_status'>$status_conf</label></td>";
			}
			echo "</tr>";
		}

		//HD 15606
		if(($login_fabrica==6 and $pecas_devolvidas=='f') or $login_fabrica == 40){
			echo "	<tr>
						<td colspan='100%' align='center' nowrap>
						<a href='$PHP_SELF?posto=$posto&extrato=$extrato&confirma_devolucao=sim'>
							<label class='btn btn-link'>Confirmar Devolução</label>
						</a>
						</td>
					</tr>";
		}
		echo "		</tbody>			
					</table>";
	}
}

if (1==2) {

	$sql = "SELECT  tbl_peca.referencia,
					tbl_peca.descricao ,
					tbl_os_item.qtde   ,
					to_char(tbl_faturamento.conferencia,'DD/MM/YYYY') AS conferencia     ,
					to_char(tbl_faturamento.emissao,'DD/MM/YYYY')     AS emissao         ,
					tbl_os.sua_os
			FROM    tbl_os
			JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto
			JOIN    tbl_os_produto    USING (os)
			JOIN    tbl_os_item       USING (os_produto)
			JOIN    tbl_peca          USING (peca)
			WHERE   tbl_os.fabrica                 = $login_fabrica
			AND     tbl_os.finalizada NOTNULL
			AND     tbl_peca.devolucao_obrigatoria is true
			AND     tbl_posto_fabrica.codigo_posto = $codigo_posto
			ORDER BY tbl_os_item.os_item DESC
			LIMIT 10 ";

	$res = pg_query($con,$sql);

	if (pg_num_rows($res) == 0) 
	{?>
		<div class="alert">
			<h4>Não existe peças com devolução obrigatória lançadas em suas Ordens de Serviço</h4>
		</div>
	<?php
	}
	else
	{
		echo "
			<table width='700' class='table table-striped table-bordered table-hover table-large'>
			<thead>
			<tr class='titulo_tabela'>
				<th align='center'>Cod. Peça</th>
				<th align='center'>Descrição</th>
				<th align='center'>Qtde 	</th>";

				if( in_array($login_fabrica, array(11,172)) )
				{
					echo "
					<th>NF    </th>
					<th>Status</th>";
				}

		echo "
			</tr>
			</thead>
			<tbody>";

		for ($i=0; $i < pg_num_rows($res); $i++)
		{
			$referencia_peca = trim(pg_fetch_result ($res,$i,referencia));
			$descricao_peca  = trim(pg_fetch_result ($res,$i,descricao));
			$qtde            = trim(pg_fetch_result ($res,$i,qtde));
			$cor = ($i % 2 == 0) ? '#F1F4FA' : "#F7F5F0";
			echo "
			<tr bgcolor='$cor' class='titulo_coluna'>
				<td align='center'>	$referencia_peca</td>
				<td align='left'>	$descricao_peca	</td>
				<td align='center'>	$qtde 			</td>";

			if( in_array($login_fabrica, array(11,172)) )
			{
				echo "<td align='center'>$nota_fiscal</td>";
				echo "<td align='center'>$status_conf</td>";
			}

			echo "
			</tr>
			</tbody>";
		}
	}
}

if( in_array($login_fabrica, array(11,172)) )
{
	if (strlen ($extrato) > 0) 
	{
		$sql = "SELECT	tbl_peca.referencia,
						tbl_peca.descricao ,
						'' AS pecas_devolvidas,
						tbl_faturamento.nota_fiscal,
						SUM (tbl_faturamento_item.qtde) AS qtde
				FROM    tbl_faturamento
				JOIN    tbl_faturamento_item USING(faturamento)
				JOIN    tbl_peca             USING(peca)
				WHERE   tbl_faturamento.extrato_devolucao = $extrato
				AND     tbl_faturamento.fabrica           = $login_fabrica
				AND     tbl_faturamento.distribuidor IS NULL
				AND     tbl_peca.devolucao_obrigatoria      IS FALSE
				GROUP BY tbl_faturamento.conferencia, tbl_faturamento.nota_fiscal, tbl_peca.referencia, tbl_peca.descricao
				ORDER BY SUM (tbl_faturamento_item.qtde) DESC ";
		$res = pg_query($con,$sql);

		if (pg_num_rows($res) == 0) 
		{?>
			<div class="alert">
				<h4>Não existe peças com devolução obrigatória lançadas em suas Ordens de Serviço</h4>
			</div>
		<?php
		}
		else
		{
			echo "	<table width='700' class='table table-striped table-bordered table-hover table-large' id='relatorio'>
					<thead>";

					if ( in_array($login_fabrica, array(11,172)) )
					{
						echo "
						<tr class='titulo-tabela'>
							<td align='center' colspan='5'>PEÇAS DE DEVOLUÇÃO NÃO OBRIGATÓRIA <br>DO POSTO \"".$posto_nome."\" O LGR ESTÁ ATRELADO AO EXTRATO \"".$extrato."\" </td>
						</tr>";
					}
					else
					{
						echo "
						<tr class='titulo_tabela'>
							<td align='center' colspan='3'>PEÇAS DE DEVOLUÇÃO NÃO OBRIGATÓRIA <br>DO POSTO \"".$posto_nome."\" O LGR ESTÁ ATRELADO AO EXTRATO \"".$extrato."\" MAS É COMPOSTO PELAS NF DE REMESSA EM GARANTIA. </td>
						</tr>";
					}

			echo "	<tr class='titulo_coluna'>
						<th align='center' class='menu_top' style='cursor:pointer;' >Cod. Peça</th>
						<th align='center' class='menu_top' style='cursor:pointer;'>Descrição</th>
						<th align='center'>Qtde</th>
					</tr>
					</thead>
					<tbody>";

			for ($i=0; $i < pg_num_rows($res); $i++)
			{
				$referencia_peca  = trim(pg_fetch_result ($res,$i,referencia));
				$descricao_peca   = trim(pg_fetch_result ($res,$i,descricao));
				$qtde             = trim(pg_fetch_result ($res,$i,qtde));
				$pecas_devolvidas = trim(pg_fetch_result ($res,$i,pecas_devolvidas));
				$cor = ($i % 2 == 0) ? '#F1F4FA' :  "#F7F5F0";
				
				echo "
					<tr class='table_line' bgcolor='$cor' >
						<td align='center'>	$referencia_peca</td>
						<td align='left'>	$descricao_peca	</td>
						<td align='center'>	$qtde 			</td>
					</tr>";
			}

			echo "	</tbody>
					</table>";
		}
	}
}
echo "<br /><br />";
include "rodape.php";
?>
