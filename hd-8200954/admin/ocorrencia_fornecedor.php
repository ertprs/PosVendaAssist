 <?php 
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include 'autentica_admin.php';
include 'funcoes.php';

$btn_acao = $_POST['btn_acao'];
if(strlen($btn_acao)>0){
	$data_inicial = $_POST['data_inicial'];
	$data_final   = $_POST['data_final'];
	$fornecedor_id = $_POST["fornecedor_id"];
	$cnpj_fornecedor = $_POST["cnpj_fornecedor"];
	$nome_fornecedor = $_POST["nome_fornecedor"];
	$tipo_data = $_POST["tipo_data"];

	if ($data_inicial == 'dd/mm/aaaa' or $data_final == 'dd/mm/aaaa')
		$msg_erro = traduz('Data inválida!');

	if(strlen($msg_erro)==0){
		$xdata_inicial = dateFormat($data_inicial, 'dmy', 'y-m-d');
		$xdata_final   = dateFormat($data_final,   'dmy', 'y-m-d');

		if (is_bool($xdata_inicial) or is_bool($xdata_final) or
		    $xdata_inicial > $xdata_final)
			$msg_erro = traduz("Data inválida");
	}

	if (empty($data_inicial) or empty($data_final)){
		$msg_erro = "Informe o período da pesquisa.";
	}	


	if (strtotime($xdata_inicial.'+1 year') < strtotime($xdata_final) ) {
        $msg_erro = traduz('O intervalo entre as datas não pode ser maior que 1 ano');
    }

    
    $cond_data = " and tbl_os.data_abertura between '$xdata_inicial 00:00:00' and '$xdata_final 23:59:59' ";
    $data_pesquisa = 'abertura';
    if($tipo_data == "data_fechamento"){
    	$cond_data = " and tbl_os.data_fechamento between '$xdata_inicial 00:00:00' and '$xdata_final 23:59:59' ";
    	$data_pesquisa = 'fechamento';
    }

    if($fornecedor_id > 0 and strlen($cnpj_fornecedor)> 0 and strlen($nome_fornecedor)>0 ){
    	#$cond_fornecedor = " and tbl_os_campo_extra.campos_adicionais::JSON->>'cor_etiqueta_fornecedor' = '$fornecedor_id' ";
    	$cond_fornecedor = " and tbl_os_defeito_reclamado_constatado.campos_adicionais::JSON->>'cor_etiqueta_fornecedor' = '$fornecedor_id' ";
    }

    if(strlen(trim($msg_erro)) == 0){

    	//(select nome from tbl_fornecedor join tbl_fornecedor_fabrica using(fornecedor) where tbl_fornecedor_fabrica.fabrica = 30 and tbl_fornecedor.fornecedor = (tbl_os_campo_extra.campos_adicionais::JSON->>'cor_etiqueta_fornecedor')::int ) as fornecedor 

    	$sql_old = " SELECT tbl_os.os,
				tbl_os.data_abertura, tbl_peca.referencia as referencia_peca, tbl_peca.descricao as descricao_peca,
				tbl_produto.referencia as referencia_produto, tbl_produto.descricao as descricao_produto,
				tbl_defeito.descricao as descricao_defeito , 
				tbl_os_campo_extra.campos_adicionais, 
				tbl_os_item.qtde

				FROM tbl_os
				Join tbl_os_campo_extra on tbl_os_campo_extra.os = tbl_os.os
				join tbl_os_produto on tbl_os_produto.os = tbl_os.os
				join tbl_os_item on tbl_os_item.os_produto = tbl_os_produto.os_produto
				join tbl_peca on tbl_peca.peca = tbl_os_item.peca
				join tbl_produto on tbl_produto.produto = tbl_os_produto.produto

				join tbl_defeito on tbl_defeito.defeito = tbl_os_item.defeito and tbl_defeito.fabrica = $login_fabrica 

				WHERE tbl_os.fabrica = $login_fabrica 
				$cond_data
				$cond_fornecedor
				and tbl_os_campo_extra.campos_adicionais::JSON->>'defeito_cor_etiqueta_fornecedor' = 'sim' ";


		$sql = "SELECT 
			distinct
			tbl_os.os,tbl_os.sua_os, 
			tbl_os.data_abertura, tbl_peca.referencia as referencia_peca, tbl_peca.descricao as descricao_peca,
			tbl_produto.referencia as referencia_produto, tbl_produto.descricao as descricao_produto,
			tbl_defeito.descricao as descricao_defeito ,
			tbl_os_defeito_reclamado_constatado.campos_adicionais::JSON->>'cor_etiqueta_fornecedor' as fornecedor_etiqueta,
			tbl_os_item.qtde
			from tbl_os_defeito_reclamado_constatado 
			join tbl_os on tbl_os.os = tbl_os_defeito_reclamado_constatado.os
			left join tbl_os_produto on tbl_os_produto.os = tbl_os.os
			left join tbl_os_item on tbl_os_item.os_produto = tbl_os_produto.os_produto
			left join tbl_peca on tbl_peca.peca = tbl_os_item.peca
			left join tbl_produto on tbl_produto.produto = tbl_os.produto
			left join tbl_defeito on tbl_defeito.defeito = tbl_os_item.defeito and tbl_defeito.fabrica = $login_fabrica

			WHERE tbl_os_defeito_reclamado_constatado.campos_adicionais::JSON->>'cor_etiqueta_fornecedor' is not null
			$cond_data
			$cond_fornecedor
			and tbl_os.fabrica = $login_fabrica "; 			
    	$res_principal = pg_query($con, $sql); 

    	if(strlen(pg_last_error($con))>0){
    		$msg_erro = pg_last_error($con); 
    	}

    	if ($_REQUEST["gerar_excel"]) {

    		$data = date("d-m-Y-H:i");
			$fileName = "relatorio_ocorrencia_fornecedor-{$login_fabrica}-{$data}.xls";
			$file = fopen("/tmp/{$fileName}", "w");			

    		$head = "Data;OS;Cor Etiqueta;Fornecedor CNPJ;Produto;Peças;Qtde Peça; Defeito da Peça \n\r"; 

    		fwrite($file,"$head");
    		$body = ""; 
    		for($i=0; $i<pg_num_rows($res_principal); $i++){
				$os 			= pg_fetch_result($res_principal, $i, 'os');
				$sua_os 			= pg_fetch_result($res_principal, $i, 'sua_os');
				$data_abertura = mostra_data(pg_fetch_result($res_principal, $i, 'data_abertura'));
				$referencia_produto = pg_fetch_result($res_principal, $i, 'referencia_produto');
				$descricao_produto = pg_fetch_result($res_principal, $i, 'descricao_produto');
				$referencia_peca = pg_fetch_result($res_principal, $i, 'referencia_peca');
				$descricao_peca = pg_fetch_result($res_principal, $i, 'descricao_peca');
				$fornecedor = pg_fetch_result($res_principal, $i, 'fornecedor'); 
				$descricao_defeito = ucwords(pg_fetch_result($res_principal, $i, 'descricao_defeito'));
				$qtde = pg_fetch_result($res_principal, $i, 'qtde');


				$fornecedor_etiqueta = json_decode(pg_fetch_result($res_principal, $i, 'fornecedor_etiqueta'),true); 	
				#$sql_for = "SELECT campos_adicionais::JSON->>'cor_etiqueta' as cor_etiqueta FROM tbl_fornecedor WHERE fornecedor = ". $fornecedor_etiqueta;
				$sql_for = "SELECT nome_cor FROM tbl_cor WHERE cor = (SELECT campos_adicionais::JSON->>'cor_etiqueta' as cor_etiqueta FROM tbl_fornecedor WHERE fornecedor = $fornecedor_etiqueta)::int AND fabrica = $login_fabrica";
				$res_for = pg_query($con, $sql_for);
				if(pg_num_rows($res_for)>0){
					$cor_etiqueta = pg_fetch_result($res_for, 0, 'nome_cor');
					/*$cor_etiqueta = pg_fetch_result($res_for, 0, 'cor_etiqueta');

					$cor_etiqueta = str_replace("_", " ", $cor_etiqueta);
					$cor_etiqueta = ucwords($cor_etiqueta); */
				}

				$sqlCnpj  = "SELECT cnpj FROM tbl_fornecedor WHERE fornecedor = $fornecedor_etiqueta";
				$resCnpj  = pg_query($con, $sqlCnpj);
				$cnpj_for = pg_fetch_result($resCnpj, 0, 'cnpj');

				$body .= "$data_abertura;$sua_os;$cor_etiqueta;$cnpj_for;$referencia_produto-$descricao_produto; $referencia_peca-$descricao_peca;$qtde;$descricao_defeito \n\r"; 

			}
			fwrite($file,"$body");

			fclose($file);

			if (file_exists("/tmp/{$fileName}")) {
				system("mv /tmp/{$fileName} xls/{$fileName}");

				echo "xls/{$fileName}";
			}
			exit;
    	}

    }

	
}
$layout_menu = "gerencia";
$title = traduz("Ocorrência x Fornecedores");

include "cabecalho_new.php";

?>

<!--
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
-->
<?
$plugins = array(
	"mask",
	"datepicker",
	"shadowbox"
 );

include "plugin_loader.php";
?>
<script language='javascript' src='../ajax.js'></script>
<script type="text/javascript" charset="utf-8">
	$(function(){

		$("#data_inicial").datepicker().mask("99/99/9999");
		$("#data_final").datepicker().mask("99/99/9999");

		Shadowbox.init();

        $("span[rel=lupa]").click(function () {
            $.lupa($(this));
        });

	});

	function retorna_fornecedor(retorno) {
		$("#fornecedor_id").val(retorno.fornecedor);
		$("#cnpj").val(retorno.cnpj);
		$("#nome").val(retorno.nome);
	}

</script>

	<? if(strlen($msg_erro)>0){ ?>
		<div class='alert alert-danger'><? echo $msg_erro; ?></div>
	<? } ?>
<div class="row">
   	<b class="obrigatorio pull-right">  * <?=traduz("Campos obrigatórios ")?></b>
</div>
<FORM class='form-search form-inline tc_formulario' name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">

		<div class="titulo_tabela"><?=traduz('Parâmetros de Pesquisa')?></div>
			<div class="row-fluid">
				<div class="span2"></div>
				<div class="span4">
					<div class="control-group">
						<label class="control-label" for=''><?=traduz('Data Inicial')?></label>
						<div class='controls controls-row'>
							<h5 class='asteristico'>*</h5>
							<input class="span4" type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='frm' value="<? if (strlen($data_inicial) > 0) echo $data_inicial;  ?>">
							<!--<img border="0" src="imagens/lupa.png" align="absmiddle" onclick="javascript:showCal('DataInicial')" style="cursor: hand;" alt="Clique aqui para abrir o calendário">-->
						</div>
					</div>
				</div>
				<div class="span4">
					<div class="control-group">
						<label class="control-label" for=''><?=traduz('Data Final')?></label>
						<div class='controls controls-row'>
							<h5 class='asteristico'>*</h5>
							<input class="span4" type="text" name="data_final" id="data_final" size="12" maxlength="10" class='frm' value="<? if (strlen($data_final) > 0) echo $data_final; ?>">
							<!--<img border="0" src="imagens/lupa.png" align="absmiddle" 	onclick="javascript:showCal('DataFinal')" style="cursor: hand;" alt="Clique aqui para abrir o calendário">-->
						</div>
					</div>
				</div>
				<div class="span2"></div>
			</div>
			<div class='row-fluid'>
				<div class='span2'></div>
				<div class='span4'>
					<div class='control-group <?=(in_array('cnpj', $msg_erro["campos"])) ? 'error' : '';?>'>
						<label class='control-label' for='cnpj'>CPF / CNPJ</label>
						<div class='controls controls-row'>
							<div class='span7 input-append'>
								<input type="text" onkeyup="somenteNumeros(this);" id="cnpj" name="cnpj_fornecedor" class='span12' maxlength="20" value="<? echo $cnpj_fornecedor ?>" >
								<!-- <span class='add-on' rel="lupa" ><i class='icon-search' style="cursor: pointer" onclick="javascript: fnc_pesquisa_fornecedor (document.frm_fornecedor.nome,document.frm_fornecedor.cnpj,'cnpj')"></i></span> -->
								<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
								<input type="hidden" name="lupa_config" id="lupa_fornecedor" tipo="fornecedor" parametro="cnpj" />
								<input type="hidden" name="fornecedor_id" id="fornecedor_id" tipo="fornecedor_id" />
							</div>
						</div>
					</div>
				</div>
				<div class='span4'>
					<div class='control-group <?=(in_array('razao', $msg_erro["campos"])) ? 'error' : '';?>'>
						<label class='control-label' for='nome'>Nome / Razão Social</label>
						<div class='controls controls-row'>
							<div class='span12 input-append'>
								<input type="text" id="nome" name="nome_fornecedor" class='span12' value="<? echo $nome_fornecedor ?>" >
								<!-- <span class='add-on' rel="lupa" ><i class='icon-search' style="cursor: pointer" onclick="javascript: fnc_pesquisa_fornecedor (document.frm_fornecedor.nome,document.frm_fornecedor.cnpj,'nome')"></i></span> -->
								<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
								<input type="hidden" name="lupa_config" tipo="fornecedor" parametro="nome" />
							</div>
						</div>
					</div>
				</div>
				<div class='span2'></div>
			</div>

			<div class="row-fluid">
				<div class="span2"></div>
				<div class="span4">
					<div class="control-group">
						<label class="control-label" for=''> </label>
						<div class='controls controls-row'>
							<input type="radio" name="tipo_data" value="data_abertura" id="data_abertura" <?if($data_pesquisa == "abertura"){ echo "checked"; }?> >
							<label class="control-label" for=''> Data Abertura </label>							
							<input type="radio" name="tipo_data" value="data_fechamento" id="data_fechamento"  <?if($data_pesquisa == "fechamento"){ echo "checked"; }?>>
							<label class="control-label" for=''> Data Fechamento </label>
						</div>
					</div>
				</div>				
				<div class="span2"></div>
			</div>

			<br />
			<div class="row-fluid">
				<div class="span5"></div>
				<div class="span2">
					<input class="btn" type='submit' style="cursor:pointer" name='btn_acao' value='<?=traduz("Consultar")?>'>
				</div>
				<div class="span5"></div>
			</div>
</FORM>
<br />

<?
if(empty($msg_erro)){
	if( strlen($btn_acao) > 0 and pg_num_rows($res_principal) > 0 ){

		echo '<table width="700" align="center" class="table table-striped table-bordered table-fixed">';
						echo '<thead>';
							echo "<tr class='titulo_coluna' >";
								echo "<th>Data</th>"; 
								echo "<th>OS</th>"; 
								echo "<th>Cor Etiqueta</th>"; 
								echo "<th>Fornecedor CNPJ</th>"; 
								echo "<th>Produto</th>"; 
								echo "<th>Peças</th>"; 								
								echo "<th>Qtde Peça</th>"; 
								echo "<th>Defeito da Peças</th>"; 
							echo "</tr>";
						echo '</thead>';
						echo "<body>"; 

						for($i=0; $i<pg_num_rows($res_principal); $i++){
							$os 			= pg_fetch_result($res_principal, $i, 'os');
							$sua_os 			= pg_fetch_result($res_principal, $i, 'sua_os');
							$data_abertura = mostra_data(pg_fetch_result($res_principal, $i, 'data_abertura'));
							$referencia_produto = pg_fetch_result($res_principal, $i, 'referencia_produto');
							$descricao_produto = pg_fetch_result($res_principal, $i, 'descricao_produto');
							$referencia_peca = pg_fetch_result($res_principal, $i, 'referencia_peca');
							$descricao_peca = pg_fetch_result($res_principal, $i, 'descricao_peca');
							$fornecedor = pg_fetch_result($res_principal, $i, 'fornecedor'); 
							$descricao_defeito = ucwords(pg_fetch_result($res_principal, $i, 'descricao_defeito'));
							$qtde = pg_fetch_result($res_principal, $i, 'qtde');

							$fornecedor_etiqueta = json_decode(pg_fetch_result($res_principal, $i, 'fornecedor_etiqueta'),true); 	
							$sql_for = "SELECT nome_cor FROM tbl_cor WHERE cor = (SELECT campos_adicionais::JSON->>'cor_etiqueta' as cor_etiqueta FROM tbl_fornecedor WHERE fornecedor = $fornecedor_etiqueta)::int AND fabrica = $login_fabrica";
							$res_for = pg_query($con, $sql_for);
							if(pg_num_rows($res_for)>0){
								$cor_etiqueta = pg_fetch_result($res_for, 0, 'nome_cor');
							}

							$sqlCnpj  = "SELECT cnpj FROM tbl_fornecedor WHERE fornecedor = $fornecedor_etiqueta";
							$resCnpj  = pg_query($con, $sqlCnpj);
							$cnpj_for = trim(pg_fetch_result($resCnpj, 0, 'cnpj'));
							if (!empty($cnpj_for)) {
								$cnpj_for = (strlen($cnpj_for) > 11) ? MaskCamposPhp("##.###.###/####-##",$cnpj_for) : MaskCamposPhp("###.###.###-##",$cnpj_for);
							}

							echo "<tr>";								
								echo "<td class='tac'>$data_abertura</td>";
								echo "<td class='tac' nowrap><a href='os_press.php?os=$os' target='_blank'>$sua_os</a></td>";
								echo "<td class='tac' nowrap>".$cor_etiqueta." </td>";
								echo "<td class='tac' nowrap>".$cnpj_for." </td>";
								echo "<td>$referencia_produto - $descricao_produto</td>";
								echo "<td>$referencia_peca - $descricao_peca</td>";
								echo "<td class='tac'>$qtde</td>";
								echo "<td class='tac'>$descricao_defeito </td>";
							echo "</tr>";
						}
						echo "</body>";
						echo "</table>";
			$jsonPOST = excelPostToJson($_REQUEST);
			$jsonPOST = utf8_decode($jsonPOST);

			echo "<div class='container' style='text-align:center'> <b> Total de OS: ".pg_num_rows($res_principal)."</b></div>";

		?>	

			<br />
			<div id='gerar_excel' class="btn_excel">
				<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
				<span><img src='imagens/excel.png' /></span>
				<span class="txt"><?=traduz('Gerar Arquivo Excel')?></span>
			</div>

		<?php



	}

	if(strlen($btn_acao) > 0 and pg_num_rows($res_principal) == 0){
		echo '
				<div class="container">
				<div class="alert">
					    <h4>'.traduz("Nenhum resultado encontrado").'</h4>
				</div>
				</div>';
	}
}
?>

	

<? include "rodape.php" ?>
