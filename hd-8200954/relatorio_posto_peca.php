<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia";
include 'autentica_usuario.php';
include 'funcoes.php';
include "monitora.php";

#TRATAMENTO DA MESANGEM DE ERRO
$msg_erro = array();
$msgErrorPattern01 = "Preencha os campos obrigatórios.";
$msgErrorPattern02 = "O intervalo entre as datas não pode ser maior que 6 meses.";
$msgErrorPattern04 = "Nenhum resultado encontrado.";


if (strlen($_GET['data_inicial']) > 0) $data_inicial = $_GET['data_inicial'];
else                                   $data_inicial = $_POST['data_inicial'];

if (strlen($_GET['data_final']) > 0)   $data_final = $_GET['data_final'];
else                                   $data_final = $_POST['data_final'];

if (strlen($_GET['btn_gravar']) > 0)   $btn_gravar = $_GET['btn_gravar'];
else                                   $btn_gravar = $_POST['btn_gravar'];

if (strlen($_GET['baixar_xls']) > 0)   $baixar_xls = $_GET['baixar_xls'];
else                                   $baixar_xls = $_POST['baixar_xls'];

if (strlen($_GET['servico_realizado']) > 0) $servico_realizado = $_GET['servico_realizado'];
else                                        $servico_realizado = $_POST['servico_realizado'];

/***************************************************************************************
** Validacao da data, para nao permitir que o form seja submetido com campos em branco
***************************************************************************************/
if($_POST )
{
	if(empty($data_inicial) OR empty($data_final))
	{
		$msg_erro["msg"][]    = $msgErrorPattern01;
		$msg_erro["campos"][] = "data";
	}

	if(strlen($msg_erro)==0)
	{
		list($di, $mi, $yi) = explode("/", $data_inicial);
		if(!checkdate($mi,$di,$yi))
		{
			$msg_erro["msg"][]    = $msgErrorPattern01;
			$msg_erro["campos"][] = "data";
		}
	}

	if(strlen($msg_erro)==0)
	{
		list($df, $mf, $yf) = explode("/", $data_final);
		if(!checkdate($mf,$df,$yf))
		{
			$msg_erro["msg"][]    = $msgErrorPattern01;
			$msg_erro["campos"][] = "data";
		}
	}

	if(strlen($msg_erro)==0)
	{
		$aux_data_inicial = $yi."-".$mi."-".$di;
		$aux_data_final = "$yf-$mf-$df";
	}

	if(strlen($msg_erro)==0)
	{
		if(strtotime($aux_data_final) < strtotime($aux_data_inicial))
		{
			$msg_erro["msg"][]    = $msgErrorPattern01;
			$msg_erro["campos"][] = "data";
		}
	}

	if(strlen($msg_erro)==0 and !in_array($login_fabrica,array(15,122,81,114,124,123)))
	{
		if (strtotime($aux_data_inicial) < strtotime($aux_data_final . ' -6 month'))
		{
			$msg_erro["msg"][]    = $msgErrorPattern02;
			$msg_erro["campos"][] = "data";
		}
	}
}

$layout_menu = "gerencia";
$title 		 = "RELATÓRIO DE PEÇAS - DATA FINALIZADA";

include 'cabecalho_new.php';

$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable"
	);

include("plugin_loader.php");

?>

<script type="text/javascript" charset="utf-8">

	$(function() {
		$.datepickerLoad(Array("data_final", "data_inicial"));
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {	$.lupa($(this));});

		$.dataTableLoad({
			table: "#gridRelatorioPosto"
	 	});

	});
</script>

<div class="alert">
	<h4>Caso o relatório tenha mais de 500 registros será gerado automaticamente em Excel,<br> não sendo exibido na tela.</h4>
</div>

<?php if (count($msg_erro["msg"]) > 0) { ?>
    <div class="alert alert-error"> <h4><?php echo $msg_erro["msg"][0]; ?></h4> </div>
<?php } ?>

<div class="row"> <b class="obrigatorio pull-right">  * Campos obrigatórios </b> </div>
<form name='frm_relatorio' method='post' id='condicoes_cadastradas' action="<?=$PHP_SELF?>" align='center' class="form-search form-inline tc_formulario">
	<div class="titulo_tabela">Parâmetros de Pesquisa</div>
	<br />
	<div class="container tc_container">

		<div class='row-fluid'>
			<div class='span1'></div>
			<div class='span3'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'>Data Inicial</label>
					<div class='controls controls-row'>
						<div class='span12'>
							<h5 class='asteristico'>*</h5>
							<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
						</div>
					</div>
				</div>
			</div>
			<div class='span3'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_final'>Data Final</label>
					<div class='controls controls-row'>
						<div class='span12'>
							<h5 class='asteristico'>*</h5>
							<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='servico_realizado'>Serviço Realizado</label>
					<div class='controls controls-row'>
						<div class='span12'>
							<select name="servico_realizado" id="servico_realizado" class='span12' >
								<?php 
									$sql_servico = "SELECT servico_realizado, descricao 
													FROM tbl_servico_realizado
													WHERE fabrica = $login_fabrica 
													AND ativo IS TRUE";
									$res_servico = pg_query($con, $sql_servico);


									if (pg_num_rows($res_servico) > 0) {
										echo "<option value='' selected='selected' disabled='disabled'>Selecione um serviço</option>";

										for ($iServico = 0; $iServico < pg_num_rows($res_servico); $iServico++) {
											$id_servico_realizado = pg_fetch_result($res_servico, $iServico, 'servico_realizado');
											$descricao            = pg_fetch_result($res_servico, $iServico, 'descricao');

											echo "<option value='$id_servico_realizado'>$descricao</option>";
										}
									} else {
										echo "<option value='' selected='selected' disabled='disabled'>Nenhum Serviço Encontrado</option>";
									}
								?>
							</select>
						</div>
					</div>
				</div>
			</div>
			<div class='span1'></div>
		</div>

		<div class='row-fluid'>
			<div class='span1'></div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='descrição peça'>Download em Excel</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>

							<input type="checkbox" name="baixar_xls" value="t" <? if($baixar_xls=='t'){ echo 'checked'; } ?>>
						</div>
					</div>
				</div>
			</div>
		</div>

		<center>
			<input type='submit' name='btn_gravar' value='Pesquisar' class='btn' />
			<input type='hidden' name='acao' value="<? echo $acao; ?>" />
		</center>
		<br />

		</div>
	</form>

	<?php
	if(strlen($data_inicial) > 0 AND strlen($data_final) > 0 AND count($msg_erro["msg"]) == 0 ) {
		if (strlen($servico_realizado) > 0) {
			$servico_realizado = (int) $servico_realizado;
			$cond_servico      = "AND tbl_os_item.servico_realizado = $servico_realizado";
		}

		//* LIBERAR PARA POSTOS *//
		//$cond_posto = "AND   tbl_os.posto   = $login_posto";
		$cond_posto = "AND tbl_os.posto IN (6359,390306)";

		$sql = "SELECT  
				tbl_os.os                                         ,
				tbl_os.sua_os                                         ,
				tbl_os.serie                                                          ,
				tbl_os_item.preco                                                     ,
				tbl_os_item.custo_peca                                                ,
				tbl_os_item.qtde                                                      ,
				tbl_peca.referencia_fabrica                      AS peca_referencia_fabrica   ,
				tbl_peca.referencia                              AS peca_referencia   ,
				tbl_peca.descricao                               AS peca_descricao    ,
				tbl_produto.referencia_fabrica                   AS produto_referencia_fabrica ,
				tbl_produto.descricao                            AS produto_descricao ,
				tbl_produto.referencia                           AS produto_referencia,
				to_char (tbl_os.finalizada,'DD/MM/YYYY')         AS data_finalizada,
				tbl_servico_realizado.descricao                  AS servico_descricao
			FROM tbl_os
				JOIN tbl_produto       USING (produto)
				JOIN tbl_os_produto    USING (os)
				JOIN tbl_os_item       USING (os_produto)
				JOIN tbl_peca          ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = $login_fabrica
				JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = $login_fabrica
			WHERE tbl_os.fabrica = $login_fabrica
				$cond_posto
				AND   tbl_os.finalizada BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
				AND   tbl_os.finalizada IS NOT NULL
				$cond_servico
			ORDER BY tbl_peca.descricao,tbl_produto.descricao";
		$res = pg_query($con,$sql);
		$count = pg_num_rows($res);

		if ($count < 500 AND $baixar_xls !="t" and $count > 0 ) {

			$xreferenciaFabrica = "";

			if ($login_fabrica == 171) {
				$xreferenciaFabrica = "<th>Referência Fábrica</th>";
			}

			echo "	<br />
				<table id='gridRelatorioPosto' class='table table-striped table-bordered table-hover table-fixed'>
				<thead>
					<tr class='titulo_coluna'>
						<th>OS</th>
						{$xreferenciaFabrica}
						<th>Produto</th>
						<th>Serviço Realizado</th>
						<th>Nº Série</th>
						{$xreferenciaFabrica}
						<th>Peça</th>
						<th>Qtde Peças</th>
						<th>Data Finalizada</th>
					</tr>
				</thead>
				<tbody>";
					for ($i=0; $i<pg_num_rows($res); $i++) {
						$os                      = trim(pg_result($res,$i,os))            ;
						$sua_os                  = trim(pg_result($res,$i,sua_os))            ;
						$produto_referencia_fabrica      = trim(pg_result($res,$i,produto_referencia_fabrica));
						$produto_referencia      = trim(pg_result($res,$i,produto_referencia));
						$produto_descricao       = trim(pg_result($res,$i,produto_descricao)) ;
						$serie                   = trim(pg_result($res,$i,serie))             ;
						$peca_descricao          = trim(pg_result($res,$i,peca_descricao))    ;
						$peca_referencia_fabrica = trim(pg_result($res,$i,peca_referencia_fabrica))   ;
						$peca_referencia         = trim(pg_result($res,$i,peca_referencia))   ;
						$preco                   = trim(pg_result($res,$i,preco))             ;
						$data_finalizada         = trim(pg_result($res,$i,data_finalizada))   ;
						$qtde                    = trim(pg_result($res,$i,qtde))    		  ;
						$servico_descricao       = trim(pg_result($res,$i,servico_descricao));

						$qtde_pecas += $qtde;
						$preco = number_format ($preco,2,",",".");

						$referenciaPecaFabrica = "";
						$referenciaProdutoFabrica = "";
						if ($login_fabrica == 171) {
							$referenciaPecaFabrica = "<td>$peca_referencia_fabrica</td>";
							$referenciaProdutoFabrica = "<td>$produto_referencia_fabrica</td>";
						}
						echo "
						<tr>
							<td><a href='os_press.php?os=$os' target='_blank'>$sua_os</a></td>
							{$referenciaProdutoFabrica}
							<td>$produto_referencia - ".substr($produto_descricao,0,20)."</td>
							<td>$servico_descricao</td>
							<td>$serie</td>
							{$referenciaPecaFabrica}
							<td>$peca_referencia - $peca_descricao</td>
							<td>$qtde</td>
							<td>$data_finalizada</td>
						</tr>";
					}
					echo "
						</tbody>
						<tfoot>
							<tr>
								<td colspan='4' style='text-align:right;'><b>Total de Peças:</b></td>
								<td colspan='2'>$qtde_pecas</td>
							</tr>
						</tfoot>
			</table>";
	} else {
		flush();
		$data = date ("Y-m-d-H.i.s");

		$arquivo_nome     = "relatorio-posto-peca-$login_fabrica-$login_posto-$data.xls";
		$path             = "xls/";
		$path_tmp         = "/tmp/";

		$arquivo_completo     = $path.$arquivo_nome;
		$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

		echo `rm $arquivo_completo_tmp `;
		echo `rm $arquivo_completo `;

		$data = date("d/m/Y H:i:s");

		$fp = fopen ($arquivo_completo_tmp,"w");

		fputs ($fp,"<html>");
		fputs ($fp,"<head>");
		fputs ($fp,"<title>Relatório de Peças Por Posto - Data Finalizada - $data");
		fputs ($fp,"</title>");
		fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
		fputs ($fp,"</head>");
		fputs ($fp,"<body>");

		fputs ($fp,"<br><table id='gridRelatorioPostoPrint' class='table table-striped table-bordered table-hover table-fixed'>");
		fputs ($fp,"<tr class='titulo_tabela'>");

		fputs ($fp,"<td>OS</td>");
		if ($login_fabrica == 171) {
		fputs ($fp,"<td>REFERÊNCIA FÁBRICA</td>");
		}
		fputs ($fp,"<td>PRODUTO</td>");
		fputs ($fp,"<td>SERVIÇO REALIZADO</td>");
		fputs ($fp,"<td>SÉRIE</td>");
		if ($login_fabrica == 171) {
		fputs ($fp,"<td>REFERÊNCIA FÁBRICA</td>");
		}
		fputs ($fp,"<td>PEÇA</td>");
		fputs ($fp,"<td>QTDE PEÇAS</td>");
		fputs ($fp,"<td>DATA FINALIZADA</td>");

		fputs ($fp,"</tr>");

		for ($i=0; $i<$count; $i++) {
			$sua_os                  = trim(pg_result($res,$i,sua_os))            ;
			$produto_referencia      = trim(pg_result($res,$i,produto_referencia));
			$produto_descricao       = trim(pg_result($res,$i,produto_descricao)) ;
			$serie                   = trim(pg_result($res,$i,serie))             ;
			$peca_descricao          = trim(pg_result($res,$i,peca_descricao))    ;
			$peca_referencia         = trim(pg_result($res,$i,peca_referencia))   ;
			$preco                   = trim(pg_result($res,$i,preco))             ;
			$data_finalizada         = trim(pg_result($res,$i,data_finalizada))    ;
			$qtde                    = trim(pg_result($res,$i,qtde))    ;
			$produto_referencia_fabrica = trim(pg_result($res,$i,produto_referencia_fabrica));
			$peca_referencia_fabrica = trim(pg_result($res,$i,peca_referencia_fabrica))   ;
			$servico_descricao       = trim(pg_result($res,$i,servico_descricao))   ;

			$qtde_pecas += $qtde;
			$preco       = number_format ($preco,2,",",".")      ;

			fputs ($fp,"<tr>");
			fputs ($fp,"<td>$sua_os</td>");
			if ($login_fabrica == 171) {
			fputs ($fp,"<td>$produto_referencia_fabrica</td>");
			}
			fputs ($fp,"<td title='$produto_descricao'>$produto_referencia - ".substr($produto_descricao,0,20)."</td>");
			fputs ($fp,"<td>$servico_descricao</td>");
			fputs ($fp,"<td>$serie</td>");
			if ($login_fabrica == 171) {
			fputs ($fp,"<td>$peca_referencia_fabrica</td>");
			}
			fputs ($fp,"<td>$peca_referencia - $peca_descricao</td>");
			fputs ($fp,"<td>$qtde</td>");
			fputs ($fp,"<td>$data_finalizada</td>");
			fputs ($fp,"</tr>");
		}

		/*fputs ($fp,"<tr align='center'>");
		fputs ($fp,"<td colspan='4' align='right'><b>Total de Peças:</b></td>");
		fputs ($fp,"<td colspan='2' align='left'>$qtde_pecas</td>");
		fputs ($fp,"</tr>");*/
		fputs ($fp,"</table>");

		echo ` cp $arquivo_completo_tmp $path `;

		echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f $arquivo_completo $arquivo_completo_tmp `;

		if (pg_num_rows($res) > 0 ) {
			echo "	<br />
				<center>
					<div id='gerar_excel' class='btn_excel'>
						<span>
							<img src='http://posvenda.telecontrol.com.br/assist/admin/imagens/excel.png' />
						</span>
						<a onclick=\"window.location='xls/$arquivo_nome'\">
							<span class='txt'>Gerar Arquivo Excel</span>
						</a>
					</div>
				</center>";
		} else {
			$notFound = $msgErrorPattern04;
			echo '<div class="alert"><h4>'.$notFound.'</h4></div>';
		}
	}
}

include 'rodape.php';
?>
