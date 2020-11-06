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
$msgErrorPattern02 = traduz("O intervalo entre as datas não pode ser maior que 1 mês.");
$msgErrorPattern03 = traduz("Selecione posto ou peça para pesquisa.");
$msgErrorPattern04 = traduz("Nenhum resultado encontrado.");

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])) {
	$tipo_busca = $_GET["busca"];

	if (strlen($q)>2) {
		$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
		          FROM tbl_posto
		          JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
		         WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

		$sql .= ($tipo_busca == "codigo") ? " AND tbl_posto_fabrica.codigo_posto = '$q' " :  " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";

		$res = pg_query($con,$sql);
		if (pg_num_rows ($res) > 0) {

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

if (strlen($_GET['data_inicial']) > 0) $data_inicial = $_GET['data_inicial'];
else                                   $data_inicial = $_POST['data_inicial'];

if (strlen($_GET['data_final']) > 0)   $data_final = $_GET['data_final'];
else                                   $data_final = $_POST['data_final'];

if (strlen($_GET['codigo_posto']) > 0) $codigo_posto = $_GET['codigo_posto'];
else                                   $codigo_posto = $_POST['codigo_posto'];

if (strlen($_GET['referencia']) > 0)   $referencia = $_GET['referencia'];
else                                   $referencia = $_POST['referencia'];

if (strlen($_GET['descricao']) > 0)    $descricao = $_GET['descricao'];
else                                   $descricao = $_POST['descricao'];

if (strlen($_GET['btn_gravar']) > 0)   $btn_gravar = $_GET['btn_gravar'];
else                                   $btn_gravar = $_POST['btn_gravar'];

if (strlen($_GET['baixar_xls']) > 0)   $baixar_xls = $_GET['baixar_xls'];
else                                   $baixar_xls = $_POST['baixar_xls'];

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
		if (strtotime($aux_data_inicial) < strtotime($aux_data_final . ' -1 month'))
		{
			$msg_erro["msg"][]    = $msgErrorPattern02;
			$msg_erro["campos"][] = "data";
		}
	}
}


if ($login_fabrica == 15 and (count($_POST['codigo_posto']) == 0 AND count($_POST['referencia']) == 0))
{
	$msg_erro["msg"][]    = $msgErrorPattern03;
	$msg_erro["campos"][] = "posto";
}

if(strlen($codigo_posto)>0) {

	$sql = "SELECT posto
	          FROM tbl_posto_fabrica
	         WHERE codigo_posto = '$codigo_posto' AND fabrica = $login_fabrica";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res)<1) {
		$msg_erro["msg"][]    = $msgErrorPattern01;
		$msg_erro["campos"][] = "posto";
	} else {
		$posto = pg_result($res,0,0);
		if(strlen($posto)==0) {
			$msg_erro["msg"][]    = $msgErrorPattern01;
			$msg_erro["campos"][] = "posto";
		} else {
			$cond_3 = " AND   tbl_os.posto   = $posto ";
		}
	}
}


if($_POST ) {
	if(strlen($codigo_posto)==0  || strlen($descricao_posto)==0) {
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
		$.autocompleteLoad(Array("produto", "peca", "posto"));
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {	$.lupa($(this));});


		$.dataTableLoad({
			table: "#gridRelatorioPosto"
	 	});

	});

	function retorna_posto(retorno){
		$("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
	}

</script>

<div class="alert">
	<h4><?php echo traduz("Caso o relatório tenha mais de 500 registros será gerado automaticamente em Excel,<br> não sendo exibido na tela."); ?></h4>
</div>

<?php if (count($msg_erro["msg"]) > 0) { ?>
    <div class="alert alert-error"> <h4><?php echo $msg_erro["msg"][0]; ?></h4> </div>
<?php } ?>

<div class="row"> <b class="obrigatorio pull-right">  * <?=traduz('Campos obrigatórios')?> </b> </div>
<form name='frm_relatorio' method='post' id='condicoes_cadastradas' action="<?=$PHP_SELF?>" align='center' class="form-search form-inline tc_formulario">
	<div class="titulo_tabela"><?php echo traduz("Parâmetros de Pesquisa"); ?></div>
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
					<label class='control-label' for='descricao_posto'><?php echo traduz("Razão Social"); ?></label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<h5 class='asteristico'>*</h5>
							<input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>

		<?php if($login_fabrica==3 OR $login_fabrica == 15){ ?>
			<div class='row-fluid'>
				<div class='span2'></div>
				<div class='span4'>
					<div class='control-group'>

						<?php
						$sql = "SELECT * FROM tbl_marca WHERE tbl_marca.fabrica = $login_fabrica ORDER BY tbl_marca.nome;";
						$res = pg_query($con,$sql);

						if (pg_num_rows($res) > 0) {
							echo "<label class='control-label' for='Marca'>Marca</label>
								<div class='controls controls-row'>
									<div class='span7 input-append'>
										<select name='marca' class='frm'>
											<option value=''>Escolha</option>\n";
										for ($x = 0 ; $x < pg_num_rows($res) ; $x++) {
											$aux_marca = trim(pg_result($res,$x,marca));
											$aux_nome  = trim(pg_result($res,$x,nome));
											echo "<option value='$aux_marca'";
											echo ($marca == $aux_marca) ? " SELECTED " : "";
											echo ">$aux_nome</option>\n";
										}
										echo "
										</select>
									</div>
								</div>	";
							}
							?>

						</div>
					</div>
				</div>
			<?php } ?>

			<?php if($login_fabrica == 15){ ?>
				<div class='row-fluid'>
					<div class='span2'></div>
					<div class='span4'>
						<div class='control-group'>
							<label class='control-label' for='Referencia Peça'><?php echo traduz("Referência Peça"); ?></label>
							<div class='controls controls-row'>
								<div class='span7 input-append'>
									<input type="text" name="referencia" class='span12' value="<? echo $referencia ?>" >
									<span class='add-on' rel="lupa"><i class='icon-search'></i></span>
									<input type="hidden" name="lupa_config" tipo="peca" parametro="codigo" />
								</div>
							</div>
						</div>
					</div>
					<div class='span4'>
						<div class='control-group'>
							<label class='control-label' for='descrição peça'><?php echo traduz("Descrição Peça"); ?></label>
							<div class='controls controls-row'>
								<div class='span12 input-append'>
									<input type="text" name="descricao" class='span12' value="<? echo $descricao ?>">
									<span class='add-on' rel="lupa"><i class='icon-search'></i></span>
									<input type="hidden" name="lupa_config" tipo="peca" parametro="nome" />
								</div>
							</div>
						</div>
					</div>
					<div class='span2'></div>
				</div>
			<?php } ?>
			<br />
			<div class='row-fluid'>
				<div class='span2'></div>
				<div class='span4'>
					<div class='control-group'>
						<label class='control-label' for='descrição peça'><?php echo traduz("Download em Excel"); ?></label>
						<div class='controls controls-row'>
							<div class='span7 input-append'>

								<input type="checkbox" name="baixar_xls" value="t" <? if($baixar_xls=='t'){ echo 'checked'; } ?>>
							</div>
						</div>
					</div>
				</div>
			</div>

			<center>
				<input type='submit' name='btn_gravar' value='<?php echo traduz("Pesquisar"); ?>' class='btn' />
				<input type='hidden' name='acao' value="<? echo $acao; ?>" />
			</center>
			<br />

		</div>
	</form>

	<?php
	if(strlen($data_inicial) > 0 AND strlen($data_final)>0 AND count($msg_erro["msg"]) == 0 ) {
		if(strlen($marca)>0) {
			$cond_1 = "AND tbl_produto.marca = $marca";
		}

		if(strlen($referencia)>0) {
			$cond_2 = "AND (tbl_peca.referencia= '$referencia' or tbl_peca.referencia_pesquisa = '$referencia') AND tbl_peca.fabrica = $login_fabrica ";
		}

		if($login_fabrica == 163){
			$join_163 = " JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = $login_fabrica ";
			$cond_163 = " AND tbl_tipo_atendimento.fora_garantia IS NOT TRUE ";
		}

		######## Produto Composto - Fujitsu [138] HD 2541097 (01/10/2015)#########
		if (in_array($login_fabrica, array(138))) {
			$sql = "SELECT  
				tbl_os.os                                         ,
				tbl_os.sua_os                                         ,
				tbl_os_produto.serie                                                          ,
				tbl_os_item.preco                                                     ,
				tbl_os_item.custo_peca                                                ,
				tbl_os_item.qtde                                                      ,
				tbl_peca.referencia                              AS peca_referencia   ,
				tbl_peca.referencia_fabrica                      AS peca_referencia_fabrica   ,
				tbl_peca.descricao                               AS peca_descricao    ,
				tbl_produto.descricao                            AS produto_descricao ,
				tbl_produto.referencia_fabrica                   AS produto_referencia_fabrica ,
				tbl_produto.referencia                           AS produto_referencia,
				to_char (tbl_os.finalizada,'DD/MM/YYYY')         AS data_finalizada
				FROM tbl_os_produto
				JOIN tbl_produto USING(produto)
				JOIN tbl_os USING(os)
				JOIN tbl_os_item USING(os_produto)
				JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = $login_fabrica
				WHERE tbl_os.fabrica = $login_fabrica $condicao
				AND   tbl_os.finalizada BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
				AND   tbl_os.finalizada IS NOT NULL
				$cond_1
				$cond_2
				$cond_3
				ORDER BY tbl_peca.descricao,tbl_produto.descricao";
		} else {
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
				to_char (tbl_os.finalizada,'DD/MM/YYYY')         AS data_finalizada
				FROM tbl_os
				JOIN tbl_produto       USING (produto)
				JOIN tbl_os_produto    USING (os)
				JOIN tbl_os_item       USING (os_produto)
				JOIN tbl_peca          ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = $login_fabrica
				$join_163
				WHERE tbl_os.fabrica = $login_fabrica $condicao
				AND   tbl_os.finalizada BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
				AND   tbl_os.finalizada IS NOT NULL
				$cond_1
				$cond_2
				$cond_3
				$cond_163
				ORDER BY tbl_peca.descricao,tbl_produto.descricao";
		}

		$res = pg_query($con,$sql);
		$count = pg_num_rows($res);

		if ($count < 500 AND $baixar_xls!="t" and $count > 0 ) {

			$xreferenciaFabrica = "";

			if ($login_fabrica == 171) {
				$xreferenciaFabrica = "<th>" . traduz("Referência Fábrica") . "</th>";
			}

			echo "	<br />
				<table id='gridRelatorioPosto' class='table table-striped table-bordered table-hover table-fixed'>
				<thead>
					<tr class='titulo_coluna'>
						<th>". traduz("OS") . "</th>
						{$xreferenciaFabrica}
						<th>". traduz("Produto") . "</th>
						<th>". traduz("Nº Série") . "</th>
						{$xreferenciaFabrica}
						<th>". traduz("Peça") . "</th>
						<th>". traduz("Qtde Peças") . "</th>
						<th>". traduz("Data Finalizada") . "</th>
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

		$arquivo_nome     = "relatorio-posto-peca-$login_fabrica-$data.xls";
		$path             = "/www/assist/www/admin/xls/";
		$path_tmp         = "/tmp/";

		$arquivo_completo     = $path.$arquivo_nome;
		$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

		echo `rm $arquivo_completo_tmp `;
		echo `rm $arquivo_completo `;

		$data = date("d/m/Y H:i:s");

		$fp = fopen ($arquivo_completo_tmp,"w");

		fputs ($fp,"<html>");
		fputs ($fp,"<head>");
		fputs ($fp,"<title>" . traduz("Relatório de Peças Por Posto - Data Finalizada - $data"));
		fputs ($fp,"</title>");
		fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
		fputs ($fp,"</head>");
		fputs ($fp,"<body>");

		fputs ($fp,"<br><table id='gridRelatorioPostoPrint' class='table table-striped table-bordered table-hover table-fixed'>");
		fputs ($fp,"<tr class='titulo_tabela'>");

		fputs ($fp,"<td>OS</td>");
		if ($login_fabrica == 171) {
		fputs ($fp,"<td>" . traduz("REFERÊNCIA FÁBRICA") . "</td>");
		}
		fputs ($fp,"<td>" . traduz("PRODUTO") . "</td>");
		fputs ($fp,"<td>" . traduz("SÉRIE") . "</td>");
		if ($login_fabrica == 171) {
		fputs ($fp,"<td>" . traduz("REFERÊNCIA FÁBRICA") . "</td>");
		}
		fputs ($fp,"<td>" . traduz("PEÇA") . "</td>");
		fputs ($fp,"<td>" . traduz("QTDE PEÇAS") . "</td>");
		fputs ($fp,"<td>" . traduz("DATA FINALIZADA") . "</td>");

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

			$qtde_pecas += $qtde;
			$preco       = number_format ($preco,2,",",".")      ;

			fputs ($fp,"<tr>");
			fputs ($fp,"<td>$sua_os</td>");
			if ($login_fabrica == 171) {
			fputs ($fp,"<td>$produto_referencia_fabrica</td>");
			}
			fputs ($fp,"<td title='$produto_descricao'>$produto_referencia - ".substr($produto_descricao,0,20)."</td>");
			fputs ($fp,"<td>$serie</td>");
			if ($login_fabrica == 171) {
			fputs ($fp,"<td>$peca_referencia_fabrica</td>");
			}
			fputs ($fp,"<td>$peca_referencia - $peca_descricao</td>");
			fputs ($fp,"<td>$qtde</td>");
			fputs ($fp,"<td>$data_finalizada</td>");
			fputs ($fp,"</tr>");
		}

		fputs ($fp,"<tr align='center'>");
		fputs ($fp,"<td colspan='4' align='right'><b>Total de Peças:</b></td>");
		fputs ($fp,"<td colspan='2' align='left'>$qtde_pecas</td>");
		fputs ($fp,"</tr>");
		fputs ($fp,"</table>");

		echo ` cp $arquivo_completo_tmp $path `;

		echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f $arquivo_completo $arquivo_completo_tmp `;

		if ($login_fabrica ==15 && pg_numrows($resxls) > 0 || pg_num_rows( $res ) >0 ) {
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
		} else {
			$notFound = $msgErrorPattern04;
			echo '<div class="alert"><h4>'.$notFound.'</h4></div>';
		}
	}
}

include 'rodape.php';
?>
