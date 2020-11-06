<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';
include 'funcoes.php';

$btnemail = $_POST['btnemail'];
$codigo_posto = $_POST['codigo_posto'];
if (strlen ($btnemail) > 0) {
	//echo `uuencode /tmp/britania/$codigo_posto-encontro-contas.txt $codigo_posto-econtro-contas.txt | mailsubj "Encontro de Contas do posto $codigo_posto" tulio@telecontrol.com.br sirlei@britania.com.br  avbaccin@bol.com.br `;

	$arquivo = "/tmp/britania/$codigo_posto-encontro-contas.txt";

	if (!unlink($arquivo)) $msg_erro["msg"][] = "Não foi possível excluir arquivo";

	header ("Location: $PHP_SELF");
	exit;
}


$codigo_posto   = $_POST['codigo_posto'];
$descricao_posto     = $_POST['descricao_posto'];
$data_inicial   = $_POST['data_inicial'];
$data_final     = $_POST['data_final'];
$tipo_relatorio = $_POST['tipo_relatorio'];

$sql = "";
if ( isset($_POST['codigo_posto']) && isset($_POST['descricao_posto']) && isset($_POST['data_final'])) {
	if((!strlen ($codigo_posto) > 0) && (!strlen ($descricao_posto) > 0) && (!strlen ($data_final) > 0)) {
		$msg_erro["msg"][]    = "Preencha todos os campos obrigatórios";
		$msg_erro["campos"][] = "posto";
		$msg_erro["campos"][] = "data";
	}
	else if((!strlen ($codigo_posto) > 0) && (!strlen ($descricao_posto) > 0) && (strlen ($data_final) > 0)){
		$msg_erro["msg"][] = 'Preencha todos os campos obrigatórios';
		$msg_erro["campos"][] = "posto";
	}
	else if((strlen ($codigo_posto) > 0) && (strlen ($descricao_posto) > 0) && (!strlen ($data_final) > 0)) {
		$msg_erro["msg"][]    = "Preencha todos os campos obrigatórios";
		$msg_erro["campos"][] = "data";
	}
	else if((strlen ($codigo_posto) > 0) && (strlen ($descricao_posto) > 0) && (strlen ($data_final) > 0)){
		list($df, $mf, $yf) = explode("/", $data_final);
		if (!checkdate($mf, $df, $yf)) {
			$msg_erro["msg"][]    = "Data Inválida";
			$msg_erro["campos"][] = "data";
		}else{
		 	if ( isset($_POST['codigo_posto']) || isset($_POST['descricao_posto']) ) {
				if (strlen ($descricao_posto) > 0) {
					$sql = "SELECT posto, codigo_posto, nome FROM tbl_posto JOIN tbl_posto_fabrica USING (posto) 
							WHERE tbl_posto_fabrica.fabrica = $login_fabrica AND tbl_posto.nome ILIKE '%$descricao_posto%' ORDER BY nome";
				}
				if (strlen ($codigo_posto) > 0) {
					$sql = "SELECT posto, codigo_posto, nome FROM tbl_posto JOIN tbl_posto_fabrica USING (posto) 
							WHERE tbl_posto_fabrica.fabrica = $login_fabrica AND tbl_posto_fabrica.codigo_posto = '$codigo_posto'";
				}
				
				$verifica_erro = "nao";
				
				if (strlen ($sql) > 0) {
					$res = pg_exec ($con,$sql);
					if (pg_numrows($res) == 0) {
						$msg_erro["msg"][]    = "Posto não encontrado";
						$msg_erro["campos"][] = "posto";
					}
					if (pg_numrows($res) == 1) {
						$relatorio = true;
						$posto        = trim(pg_result($res,0,posto));
						$codigo_posto = trim(pg_result($res,0,codigo_posto));
						$descricao_posto   = trim(pg_result($res,0,nome));
					}
					if (pg_numrows($res) > 1) {
						$escolhe_posto = true;
					}
				}
			}
		}
	}
}
?>

<?
$layout_menu = "financeiro";
$title = "Encontro de Contas";
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

<script type="text/javascript">

	$(function() {
		$.datepickerLoad(Array("data_final", "data_inicial"));
		$.autocompleteLoad(Array("produto", "peca", "posto"));
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});
	});

	function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
    }

</script>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name="frm_encontro" method="post" action="<? $PHP_SELF ?>" class='form-search form-inline tc_formulario'>
	<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
	<br/>
	<div class='row-fluid'>
		<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='codigo_posto'>Código Posto</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<h5 class='asteristico'>*</h5>
							<input type="text" name="codigo_posto" id="codigo_posto" value="<? echo $codigo_posto ?>" class='span12'>
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='descricao_posto'>Nome Posto</label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<h5 class='asteristico'>*</h5>
							<input type="text" name="descricao_posto" id="descricao_posto" value="<? echo $descricao_posto ?>" class='span12'>&nbsp;
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
						</div>
					</div>
				</div>
			</div>
		<div class='span2'></div>
	</div>
	<div class='row-fluid'>
		<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial' id="font_form">Data Vencto. Final</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<h5 class='asteristico'>*</h5>
								<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value='<?php echo isset($data_final)? $data_final : '' ?>'>
						</div>
					</div>
				</div>
			</div>
		<div class='span4'></div>
		<div class='span2'></div>
	</div>
	<p><br/>
			<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
	</p><br/>	
</form>
<div>

<!-- ----------------- RELATORIO  EM ABERTO --------------------- -->

<?
if ($relatorio == true ) { 
  
	$data_final = trim($_POST["data_final"]);
	$data_final = str_replace ("-","",$data_final);
	$data_final = str_replace ("/","",$data_final);
	$data_final = str_replace (" ","",$data_final);
	$data_final = str_replace (".","",$data_final);
	$xdata_final = substr($data_final,4,4) ."-". substr($data_final,2,2) ."-". substr($data_final,0,2);

	echo "<br>\n";

	$sql = "SELECT  to_char(tbl_conta_corrente.data_vencimento, 'DD/MM/YYYY') AS data_vencimento,
					TRIM (tbl_conta_corrente.documento)                    AS nota_fiscal ,
					TRIM (tbl_conta_corrente.serie)                        AS serie       ,
					TRIM (tbl_conta_corrente.tipo)                         AS tipo        ,
					TRIM (tbl_conta_corrente.parcela)                      AS parcela     ,
					tbl_conta_corrente.valor                                              ,
					tbl_conta_corrente.valor_saldo
			FROM    tbl_conta_corrente
			WHERE   tbl_conta_corrente.posto = $posto
			AND     tbl_conta_corrente.fabrica = $login_fabrica
			AND     trim(tbl_conta_corrente.tipo) IN ('AT','AU','AL')
			AND     (trim(tbl_conta_corrente.representante) = '870' OR tbl_conta_corrente.representante IS NULL)
			AND     tbl_conta_corrente.valor_saldo > 0
			AND     tbl_conta_corrente.data_vencimento <= '$xdata_final'
			ORDER BY tbl_conta_corrente.data_vencimento ";
	$res_credito = pg_exec ($con,$sql);

	$sql = "SELECT  to_char(tbl_conta_corrente.data_vencimento, 'DD/MM/YYYY') AS data_vencimento,
					TRIM (tbl_conta_corrente.documento)                    AS nota_fiscal ,
					tbl_conta_corrente.tipo                                               ,
					tbl_conta_corrente.valor                                              ,
					tbl_conta_corrente.valor_saldo
			FROM    tbl_conta_corrente
			WHERE   tbl_conta_corrente.posto = $posto
			AND     tbl_conta_corrente.fabrica = $login_fabrica
			AND     trim(tbl_conta_corrente.tipo) IN ('MO')
			AND     (trim(tbl_conta_corrente.representante) = '870' OR tbl_conta_corrente.representante IS NULL)
			AND     tbl_conta_corrente.valor_saldo > 0
			AND     tbl_conta_corrente.data_vencimento <= '$xdata_final'
			ORDER BY tbl_conta_corrente.data_vencimento ";
	$res_mo = pg_exec ($con,$sql);

	$sql = "SELECT  to_char(tbl_conta_corrente.data_vencimento, 'DD/MM/YYYY') AS data_vencimento,
					TRIM (tbl_conta_corrente.documento)                    AS nota_fiscal ,
					TRIM (tbl_conta_corrente.serie)                        AS serie       ,
					TRIM (tbl_conta_corrente.tipo)                         AS tipo        ,
					TRIM (tbl_conta_corrente.parcela)                      AS parcela     ,
					tbl_conta_corrente.valor                                              ,
					tbl_conta_corrente.valor_saldo
			FROM    tbl_conta_corrente
			WHERE   tbl_conta_corrente.posto = $posto
			AND     tbl_conta_corrente.fabrica = $login_fabrica
			AND     trim(tbl_conta_corrente.tipo) IN ('DP','IM')
			AND     trim(tbl_conta_corrente.representante) = '870'
			AND     tbl_conta_corrente.valor_saldo > 0
			AND     tbl_conta_corrente.data_vencimento <= '$xdata_final'
			ORDER BY tbl_conta_corrente.data_vencimento ";
	$res_debito = pg_exec ($con,$sql);

	$saldo  = 0 ;
	$rec_db = 0 ;
	$rec_cr = 0 ;

	if ($login_fabrica == 3) {
		echo `mkdir /tmp/britania 2> /dev/null`;
		$arquivo = "/tmp/britania/$codigo_posto-encontro-contas.txt";
		$fp = fopen ($arquivo,"w");
	}

	echo "<table class='table-fixed' align='center' border='0'>";
	echo "<tr>";
	echo "<td align='center' colspan='4' bgcolor='#FF6666'><div id='font_form'><b>Débitos</b></div></td>";
	echo "<td></td>";
	echo "<td align='center' colspan='5' bgcolor='#6666FF'><div id='font_form'><b>Créditos</b></div></td>";
	echo "<td></td>";
	echo "<td align='center' bgcolor='#dddddd' rowspan='2'><div id='font_form'><b>Saldo</b></div></td>";
	echo "</tr>";

	echo "<tr>";
	echo "<td align='center' bgcolor='#FF6666'><div id='font_form'><b>Nota</b></div></td>";
	echo "<td align='center' bgcolor='#FF6666'><div id='font_form'><b>Tipo</b></div></td>";
	echo "<td align='center' bgcolor='#FF6666'><div id='font_form'><b>Vencimento</b></div></td>";
	echo "<td align='center' bgcolor='#FF6666'><div id='font_form'><b>Valor</b></div></td>";
	echo "<td></td>";
	echo "<td align='center' colspan='2' bgcolor='#6666FF'><div id='font_form'><b>Nota</b></div></td>";
	echo "<td align='center' bgcolor='#6666FF'><div id='font_form'><b>Tipo</b></div></td>";
	echo "<td align='center' bgcolor='#6666FF'><div id='font_form'><b>Vencimento</b></div></td>";
	echo "<td align='center' bgcolor='#6666FF'><div id='font_form'><b>Valor</b></div></td>";
	echo "<td></td>";
	echo "</tr>";

	while ($rec_db < pg_numrows ($res_debito) or $rec_cr < pg_numrows ($res_credito) ) {
		$proximo_debito  = false;
		$proximo_credito = false;

		if ($saldo == 0) {
			$proximo_debito  = true;
			$proximo_credito = true;
		}

		if ($saldo > 0) {
			$proximo_debito = true;
		}

		if ($saldo < 0) {
				$proximo_credito = true;
		}

		if ($proximo_debito  and $rec_db >= pg_numrows ($res_debito) )  $proximo_credito = true;
		if ($proximo_credito and $rec_cr >= pg_numrows ($res_credito) ) $proximo_debito  = true;


		$nota_debito  = "";
		$tipo_debito  = "";
		$data_debito  = "";
		$valor_debito = "";

		$nota_credito  = "";
		$tipo_credito  = "";
		$data_credito  = "";
		$valor_credito = "";

		if ($proximo_debito and $rec_db < pg_numrows ($res_debito) ) {
			$nota_debito  = pg_result ($res_debito,$rec_db,nota_fiscal) ;
			$tipo_debito  = pg_result ($res_debito,$rec_db,tipo) ;
			$data_debito  = pg_result ($res_debito,$rec_db,data_vencimento) ;
			$valor_debito = number_format (pg_result ($res_debito,$rec_db,valor_saldo),2,",",".") ;
			$saldo = $saldo - pg_result ($res_debito,$rec_db,valor_saldo);

			$arq_tipo_debito     = pg_result ($res_debito,$rec_db,tipo) ;
			$arq_serie_debito    = pg_result ($res_debito,$rec_db,serie) ;
			$arq_nota_debito     = pg_result ($res_debito,$rec_db,nota_fiscal) ;
			$arq_parcela_debito  = pg_result ($res_debito,$rec_db,parcela) ;
			$arq_saldo_debito    = pg_result ($res_debito,$rec_db,valor_saldo) ;

			$rec_db++;
		}

		if ($proximo_credito and $rec_cr < pg_numrows ($res_credito) ) {
			$nota_credito  = pg_result ($res_credito,$rec_cr,nota_fiscal) ;
			$tipo_credito  = pg_result ($res_credito,$rec_cr,tipo) ;
			$data_credito  = pg_result ($res_credito,$rec_cr,data_vencimento) ;
			$valor_credito = number_format (pg_result ($res_credito,$rec_cr,valor_saldo),2,",",".") ;
			$saldo = $saldo + pg_result ($res_credito,$rec_cr,valor_saldo);

			$arq_tipo_credito     = pg_result ($res_credito,$rec_cr,tipo) ;
			$arq_serie_credito    = pg_result ($res_credito,$rec_cr,serie) ;
			$arq_nota_credito     = pg_result ($res_credito,$rec_cr,nota_fiscal) ;
			$arq_parcela_credito  = pg_result ($res_credito,$rec_cr,parcela) ;
			$arq_saldo_credito    = pg_result ($res_credito,$rec_cr,valor_saldo) ;

			$rec_cr++;
		}

		#------- Grava arquivo de Ordens de Baixas --------
		if ($login_fabrica == 3 and $rec_cr <= pg_numrows ($res_credito) and $rec_db <= pg_numrows ($res_debito) ) {

			if ($saldo < 0) {
				$abater = $arq_saldo_credito ;
				if (strlen ($nota_debito) > 0) $abater = $arq_saldo_debito + $saldo ;
			}else{
				$abater = $arq_saldo_credito - $saldo ;
	#				if (strlen ($nota_credito) > 0) $abater = $arq_saldo_debito ;
			}


			fwrite ($fp,"1");
			fwrite ($fp,";");

			fwrite ($fp,"101");
			fwrite ($fp,";");

			fwrite ($fp,$codigo_posto );
			fwrite ($fp,";");


			
			fwrite ($fp,$arq_tipo_debito );
			fwrite ($fp,";");

			fwrite ($fp,$arq_serie_debito );
			fwrite ($fp,";");

			fwrite ($fp,$arq_nota_debito );
			fwrite ($fp,";");

			fwrite ($fp,$arq_parcela_debito );
			fwrite ($fp,";");

			fwrite ($fp, $arq_saldo_debito );
			fwrite ($fp,";");

			fwrite ($fp, $abater );
			fwrite ($fp,";");


			#---- Credito ----
			
			fwrite ($fp,$arq_tipo_credito );
			fwrite ($fp,";");

			fwrite ($fp,$arq_serie_credito );
			fwrite ($fp,";");

			fwrite ($fp,$arq_nota_credito );
			fwrite ($fp,";");

			fwrite ($fp,$arq_parcela_credito );
			fwrite ($fp,";");

			fwrite ($fp, $arq_saldo_credito );
			fwrite ($fp,";");

			fwrite ($fp, $abater );
			fwrite ($fp,";");

			
			
			fwrite ($fp,"\r\n");
		}




		#------- Imprime Linha -------------#
		echo "<tr>";
		echo "<td align='center'><div id='font_form'>$nota_debito</div></td>";
		echo "<td align='center'><div id='font_form'>$tipo_debito</div></td>";
		echo "<td align='center'><div id='font_form'>$data_debito</div></td>";
		echo "<td align='right'><div id='font_form'>$valor_debito</div></td>";
		echo "<td></td>";
		echo "<td align='center'><div id='font_form'>" ;
		if ($tipo_credito == "MO" and 1==2) echo "<input type='checkbox' name='entra_acerto'>";
		echo "</div></td>";
		echo "<td align='center'><div id='font_form'>$nota_credito</div></td>";
		echo "<td align='center'><div id='font_form'>$tipo_credito</div></td>";
		echo "<td align='center'><div id='font_form'>$data_credito</div></td>";
		echo "<td align='right'><div id='font_form'>$valor_credito</div></td>";
		echo "<td></td>";
		if ($saldo < 0) {
			$cor = "#FFF2F2";
		}else{
			$cor = "#F4F7FB";
		}
		echo "<td bgcolor='$cor' align='right'><div id='font_form'><b>" . number_format ($saldo,2,",",".") . "</b></div></td>";
		echo "</tr>";

	}

	echo "</table>";
	if ($login_fabrica == 3) {
		fclose ($fp);
	}



	#------------------- Relação de Mao de Obra ---------#

	echo "<p>";

	echo "<table class='table-fixed' align='center' border='0'>";
	echo "<tr>";
	echo "<td align='center' colspan='3' bgcolor='#66FF66'><div id='font_form'><b>Mão-de-Obra</b></div></td>";
	echo "</tr>";

	echo "<tr>";
	echo "<td align='center' bgcolor='#66FF66'><div id='font_form'><b>Nota</b></div></td>";
	echo "<td align='center' bgcolor='#66FF66'><div id='font_form'><b>Vencimento</b></div></td>";
	echo "<td align='center' bgcolor='#66FF66'><div id='font_form'><b>Valor</b></div></td>";
	echo "</tr>";

	$total_mo = 0;

	for ($i = 0 ; $i < pg_numrows ($res_mo); $i++) {
		$nota  = pg_result ($res_mo,$i,nota_fiscal) ;
		$data  = pg_result ($res_mo,$i,data_vencimento) ;
		$total_mo += pg_result ($res_mo,$i,valor_saldo) ;
		$valor = number_format (pg_result ($res_mo,$i,valor_saldo),2,",",".") ;

		echo "<tr>";
		echo "<td align='center'><div id='font_form'>$nota</div></td>";
		echo "<td align='center'><div id='font_form'>$data</div></td>";
		echo "<td align='right'><div id='font_form'>$valor</div></td>";
		echo "</tr>";
	}

	echo "<tr>";
	echo "<td align='center' colspan='2' bgcolor='#66FF66'><div id='font_form'><b>Total Mão-de-Obra</b></div></td>";
	echo "<td align='right' bgcolor='#66FF66'><div id='font_form'><b>" . number_format ($total_mo,2,",",".") . "</b></div></td>";
	echo "</tr>";

	echo "</table>";




	echo "<p><center><h4>Final de Relatório</h4></center>";

	if ($login_fabrica == 3) {
		
		echo "<center><form name='frm_envio' method='post' action='$PHP_SELF'><input class='btn' type='submit' value='Enviar Ordens de Baixa para EMS-5' name='btnemail'><input type='hidden' name='codigo_posto' value='$codigo_posto'></form></center>";

	}
	$achou = "sim";
}else{
	$achou = "nao";
}

if($achou == "nao" && $verifica_erro == "nao") { ?>
	<div class="container">
		<div class="alert">
			    <h4>Nenhum resultado encontrado</h4>
		</div>
	</div>
</div>
<? }
include_once 'rodape.php';
?>