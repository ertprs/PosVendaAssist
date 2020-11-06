<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';
include 'funcoes.php';
include "monitora.php";

#TRATAMENTO DA MESANGEM DE ERRO
$msg_erro = array();
$msgErrorPattern01 = traduz("Preencha os campos obrigatórios.");
$msgErrorPattern02 = traduz("O intervalo entre as datas não pode ser maior que 1 ano.");
$msgErrorPattern03 = traduz("Selecione posto ou peça para pesquisa.");
$msgErrorPattern04 = traduz("Nenhum resultado encontrado.");

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"]))
{
	$tipo_busca = $_GET["busca"];

	if (strlen($q)>2)
	{
		$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
		          FROM tbl_posto
		          JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
		         WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

		$sql .= ($tipo_busca == "codigo") ? " AND tbl_posto_fabrica.codigo_posto = '$q' " :  " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";

		$res = pg_exec($con,$sql);
		if (pg_numrows ($res) > 0)
		{
			for ($i=0; $i<pg_numrows ($res); $i++ )
			{
				$cnpj			= trim(pg_result($res,$i,cnpj));
				$nome			= trim(pg_result($res,$i,nome));
				$codigo_posto	= trim(pg_result($res,$i,codigo_posto));
				echo "$codigo_posto|$nome|$cnpj";
				echo "\n";
			}
		}
	}
	exit;
}

$data_inicial = $_POST['data_inicial'];
$data_final = $_POST['data_final'];
$codigo_posto = $_POST['codigo_posto'];
$referencia = $_POST['referencia'];
$descricao = $_POST['descricao'];
$btn_gravar = $_POST['btn_gravar'];
$baixar_xls = $_POST['baixar_xls'];

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
		if (strtotime($aux_data_inicial) < strtotime($aux_data_final . ' -1 year'))
		{
			$msg_erro["msg"][]    = $msgErrorPattern02;
			$msg_erro["campos"][] = "data";
		}
	}
}

if($_POST ){
	if (count($_POST['codigo_posto']) == 0 AND count($_POST['referencia']) == 0)
	{
		$msg_erro["msg"][]    = $msgErrorPattern03;
		$msg_erro["campos"][] = "posto";
	}
}

if(strlen($codigo_posto)>0)
{
	$sql = "SELECT posto
	          FROM tbl_posto_fabrica
	         WHERE codigo_posto = '$codigo_posto' AND fabrica = $login_fabrica";
	$res = @pg_exec($con,$sql);
	if(pg_num_rows($res)<1)
	{
		$msg_erro["msg"][]    = $msgErrorPattern01;
		$msg_erro["campos"][] = "posto";
	}
	else
	{
		$posto = pg_result($res,0,0);
		if(strlen($posto)==0)
		{
			$msg_erro["msg"][]    = $msgErrorPattern01;
			$msg_erro["campos"][] = "posto";
		}
		else
		{
			$cond_3 = " AND   tbl_os.posto   = $posto ";
		}
	}
}


if($_POST )
{
	if(strlen($codigo_posto)==0  || strlen($posto_nome)==0)
	{
		//echo 'aqui';
		$msg_erro["msg"][]    = $msgErrorPattern01;
		$msg_erro["campos"][] = "posto";
	}
}

$layout_menu = "gerencia";
$title 		 = traduz("RELATÓRIO DE PEÇAS POR POSTO - DATA FINALIZADA");

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
		$.autocompleteLoad(Array("posto"));
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {	$.lupa($(this));});

		function formatItem(row) { return row[0] + " - " + row[1];}

		function formatResult(row) { return row[0]; }

		$.dataTableLoad({
			table: "#gridRelatorioPosto"
	 	});

	});

	function retorna_posto(retorno){
		$("#codigo_posto").val(retorno.codigo);
		$("#posto_nome").val(retorno.nome);
	}

</script>

<?php if (count($msg_erro["msg"]) > 0) {	?>
    <div class="alert alert-error"> <h4><?php echo $msg_erro["msg"][0]; ?></h4> </div>
<?php 	}	?>

<div class="row"> <b class="obrigatorio pull-right">* <?php echo traduz("Campos obrigatórios");?></b> </div>
<form name='frm_relatorio' method='POST' id='condicoes_cadastradas' action="<?=$PHP_SELF?>" align='center' class="form-search form-inline tc_formulario">
	<div class="titulo_tabela"><?php echo traduz("Parâmetros de Pesquisa");?> </div>
	<br />
	<div class="container tc_container">

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'><?php echo traduz("Data Inicial"); ?></label>
					<div class='controls controls-row'>
						<div class='span4'>
							<h5 class='asteristico'>*</h5>
							<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_final'><?php echo traduz("Data Final"); ?></label>
					<div class='controls controls-row'>
						<div class='span4'>
							<h5 class='asteristico'>*</h5>
							<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='codigo_posto'><?php echo traduz("Código Posto"); ?></label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<h5 class='asteristico'>*</h5>
							<input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='posto_nome'><?php echo traduz("Razão Social"); ?></label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<h5 class='asteristico'>*</h5>
							<input type="text" name="posto_nome" id="posto_nome" class='span12' value="<? echo $posto_nome ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>


		<!-- <br />
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='descrição peça'>Download em Excel</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>

							<input type="checkbox" name="baixar_xls" value="t" <? //if($baixar_xls=='t'){ echo 'checked'; } ?>>
						</div>
					</div>
				</div>
			</div>
		</div>
 -->
		<center>
			<input type='submit' name='btn_gravar' value='Pesquisar' class='btn' />
			<input type='hidden' name='acao' value="<? echo $acao; ?>" />
		</center>
		<br />

		</div>
	</form>

	<?php
	if(strlen($data_inicial) > 0 AND strlen($data_final)>0 AND count($msg_erro["msg"]) == 0 )
	{


	/*
	*os (tbl_os.os),
	*nota fiscal (tbl_faturamento.nota_fiscal),
	*série (tbl_faturamento.serie),
	*data de abertura (tbl_os.data_abertura),
	*data de faturamento (tbl_faturamento.emissao),
	*dias entre abertura e faturamento (tbl_os.data_abertura - tbl_faturamento.emissao)
	*/
		$sql = "SELECT  tbl_os.os,
						tbl_faturamento.nota_fiscal,
						tbl_faturamento.serie,
						to_char (tbl_faturamento.emissao - tbl_os.data_abertura,'DD' )  as dias_em_aberto,
						to_char (tbl_os.data_abertura,'DD/MM/YYYY')         AS data_finalizada,
						to_char (tbl_faturamento.emissao,'DD/MM/YYYY')         AS data_finalizada
				FROM tbl_os
				INNER JOIN tbl_faturamento_item  ON tbl_os.os = tbl_faturamento_item.os
				INNER JOIN tbl_faturamento    USING (faturamento)
				WHERE tbl_os.fabrica = {$login_fabrica}
					AND   tbl_os.data_abertura BETWEEN '{$aux_data_inicial} 00:00:00' AND '{$aux_data_final} 23:59:59'
					{$cond_3}
				ORDER BY tbl_os.data_abertura ";
	// echo nl2br($sql); exit;
		$res = pg_query($con,$sql);

		$count = pg_num_rows($res);

		if ($count > 0 )
		{

			$data = date ("Y-m-d-H.i.s");

			$arquivo_nome     = "relatorio-posto-peca-$login_fabrica-$data.xls";
			$path             = "/www/assist/www/admin/xls/";
			$path_tmp         = "/tmp/";

			$arquivo_completo     = $path.$arquivo_nome;
			$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

			echo `rm $arquivo_completo_tmp `;
			echo `rm $arquivo_completo `;

			$data = date ("d/m/Y H:i:s");

			$fp = fopen ($arquivo_completo_tmp,"w");

			fputs ($fp,"<html>");
			fputs ($fp,"<head>");
			fputs ($fp,"<title>" . traduz("Relatório de Peças Por Posto - Data Finalizada") . "-" . $data);
			fputs ($fp,"</title>");
			fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
			fputs ($fp,"</head>");
			fputs ($fp,"<body>");

			fputs ($fp,"<br><table border='0' cellpadding='2' cellspacing='0' class='formulario' align='center' width='700px'>");
			fputs ($fp,"<tr class='titulo_tabela'>");
			fputs ($fp,"<td >" . traduz("OS") . "</td>");
			fputs ($fp,"<td >" . traduz("Nota Fiscal") . "</td>");
			fputs ($fp,"<td >" . traduz("Nº Série") . "</td>");
			fputs ($fp,"<td >" . traduz("Data Abertura") . "</td>");
			fputs ($fp,"<td >" . traduz("Qtde Faturamento") . "</td>");
			fputs ($fp,"<td >" . traduz("Dias em aberto") . "</td>");

			fputs ($fp,"</tr>");

			echo "	<br />
				<table id='gridRelatorioPosto' class='table table-striped table-bordered table-hover table-fixed'>
				<thead>
					<tr class='titulo_coluna'>
						<th>" . traduz("OS") ."  			  </th>
						<th>" . traduz("Nota Fiscal") ."  	  </th>
						<th>" . traduz("Nº Série") ."  		  </th>
						<th>" . traduz("Data Abertura") ."    </th>
						<th>" . traduz("Qtde Faturamento") ." </th>
						<th>" . traduz("Dias em aberto") ."   </th>
					</tr>
				</thead>
				<tbody>";

					for ($i=0; $i<pg_numrows($res); $i++)
					{
						$os              = trim(pg_result($res,$i,"os"));
						$nota_fiscal     = trim(pg_result($res,$i,"nota_fiscal"));
						$serie           = trim(pg_result($res,$i,"serie"));
						$dias_em_aberto  = trim(pg_result($res,$i,"dias_em_aberto"));
						$data_finalizada = trim(pg_result($res,$i,"data_finalizada"));
						$data_finalizada = trim(pg_result($res,$i,"data_finalizada"));

						$cor 		= ($i%2) ? '#F7F5F0' : '#F1F4FA';

			echo "	<tr align='center'>
						<td bgcolor='$cor' align='left' ><a href='os_press?os=$os' target='_blank'>$sua_os</a></td>
						<td bgcolor='$cor' title='$nota_fiscal'>$nota_fiscal</td>
						<td bgcolor='$cor' align='center' > $serie</td>
						<td bgcolor='$cor' align='left' >	$dias_em_aberto	</td>
						<td bgcolor='$cor' align='center' > $data_finalizada</td>
						<td bgcolor='$cor' align='center' > $data_finalizada</td>
					</tr>";

					fputs ($fp,"<tr class='formulario'>");
					fputs ($fp,"<td bgcolor='$cor' align='left'>$os</td>");
					fputs ($fp,"<td bgcolor='$cor' align='left'>$nota_fiscal</td>");
					fputs ($fp,"<td bgcolor='$cor' aling='left'>$serie</td>");
					fputs ($fp,"<td bgcolor='$cor' align='left'>$dias_em_aberto</td>");
					fputs ($fp,"<td bgcolor='$cor' align='left'>$data_finalizada</td>");
					fputs ($fp,"<td bgcolor='$cor' align='left'>$data_finalizada</td>");
					fputs ($fp,"</tr>");

				}

			echo "	<tr align='center'>
						<td colspan='4' bgcolor='$cor'>		<b>Total</b>	</td>
						<td bgcolor='$cor'align='center'> 	$count 	</td>
						<td bgcolor='$cor'>					&nbsp;			</td>
					</tr>
				</tbody>
			</table>";


			fputs ($fp,"<tr align='center'>");
			fputs ($fp,"<td colspan='4' bgcolor='$cor'><b>Total</b></td>");
			fputs ($fp,"<td bgcolor='$cor'>$count</td>");
			fputs ($fp,"<td bgcolor='$cor'>&nbsp;</td>");
			fputs ($fp,"</tr>");
			fputs ($fp,"</table>");

			echo ` cp $arquivo_completo_tmp $path `;

			echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f $arquivo_completo $arquivo_completo_tmp `;
		if (pg_num_rows($resxls) > 0 || pg_num_rows( $res ) >0 )
		{
			echo "	<br />
					<center>
					<div id='gerar_excel' class='btn_excel'>
					    <span>
					    	<img src='http://posvenda.telecontrol.com.br/assist/admin/imagens/excel.png' />
				    	</span>
				    	<a onclick=\"window.location='xls/$arquivo_nome'\">
					    <span class='txt'>" . traduz("Gerar Arquivo Excel") . "</span>
					    </a>
					</div>
					</center>";
		}
	}
	else
	{
		$notFound = $msgErrorPattern04;
		echo '<div class="alert"><h4>'.$notFound.'</h4></div>';
	}
}
include 'rodape.php';
?>