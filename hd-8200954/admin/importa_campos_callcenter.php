<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,cadastro";
include 'autentica_admin.php';
include 'funcoes.php';

if ($_POST["btn_acao"] == "gravar") {
	$arquivo = isset($_FILES["arquivo_csv"]) ? $_FILES["arquivo_csv"] : false;

	if ($arquivo == false) {
		$msg_erro["msg"][] = "Por favor selecionar o arquivo";
	} else {
		if ($arquivo["type"] != "text/csv") {
			$validacao = explode(".", $arquivo["name"]);

			if ($validacao[1] != "csv") {
				$msg_erro["msg"][] = "Formato de arquivo incorreto";
			}
		} 

		if (empty($msg_erro["msg"])) {
			$dados = file_get_contents($arquivo['tmp_name']);
            if (mb_check_encoding($dados,'UTF-8')) {
                $dados = utf8_decode($dados);
            }

            $linhas = explode("\n", $dados);

            foreach ($linhas as $ln => $linha) {
            	$erro = "";
            	if (strlen($linha) > 0) {
            		$auxiliar = explode(";", $linha);

            		$numero_serie = trim($auxiliar[0]);
            		$nf_saida     = trim($auxiliar[1]);
            		$data_nf      = trim($auxiliar[2]);
            		$cnpj         = trim($auxiliar[3]);

            		$sql = "SELECT numero_serie FROM tbl_numero_serie WHERE serie = '$numero_serie' AND fabrica = $login_fabrica";
            		$res = pg_query($con, $sql);
            		$val = pg_fetch_result($res, 0, 'numero_serie');

            		if (strlen($val) == 0) {
            			$erro .= "Número de série \"$numero_serie\" inválido";
            		}

            		$sql = "SELECT cliente_admin FROM tbl_cliente_admin WHERE fabrica = $login_fabrica and cnpj = '$cnpj'";
            		$res = pg_query($con, $sql);
            		$val = pg_fetch_result($res, 0, 'cliente_admin');

            		if (strlen($val) == 0) {
            			$erro .= "CNPJ Cliente Fricon \"$cnpj\" inválido";
            		}

            		list($di, $mi, $yi) = explode("/", $data_nf);

					if(!checkdate($mi,$di,$yi)){
						$erro .= "Data \"$data_nf\" inválida";
					} else {
						$xdata_nf = "$yi-$mi-$di";
						$date_now = date("Y-m-d");

						if ($xdata_nf > $date_now) {
							$erro .= "Data \"$data_nf\" superior a data atual";
						}
					}

					if (empty($erro)) {
						$res = pg_query($con, "BEGIN");

						$sql = "UPDATE tbl_numero_serie SET cnpj = '$cnpj', data_venda = '$xdata_nf', nota_fiscal = '$nf_saida' WHERE fabrica = $login_fabrica AND serie = '$numero_serie'";
						$res = pg_query($con, $sql);

						if (pg_last_error()) {
							$erro .= "Erro ao importar as informações na linha $ln";
							$res = pg_query($con, "ROLLBACK");
						}else{
							$ok = "Número de série $numero_serie importado com sucesso.";
							$res = pg_query($con, "COMMIT");
						}
					}

					if (!empty($erro)) {
						$msg_erro["msg"][] = $erro;
					}
					$arquivo_retorno["log"][] = (strlen(trim($erro))>0) ? $erro : $ok;
            	}
            }

            if(count($arquivo_retorno)>0){
            	$data = date("Ymdhis");
            	$nome_arquivo = "xls/arquivo_".$login_fabrica."_".$data.".txt";
            	$file = fopen($nome_arquivo, 'a');
            	fwrite($file, implode("\r\n", $arquivo_retorno['log'] ));
            	fclose($file);
            }
            header("location: importa_campos_callcenter.php?msg=success&data=$data");
		}
	}
}

if ($_POST["btn_acao_2"] == "pesquisar") {
	$numero_serie = $_POST["numero_serie"];
	$data_nf      = $_POST["data_nf"];

	if (strlen($numero_serie) == 0 && strlen($data_nf) == 0) {
		$msg_erro["msg"][] = "Favor informar ao menos um campo para pesquisa";
	} else {
		$auxiliar          = "";
		$cond_numero_serie = "";
		$cond_data_nf      = "";

		if (strlen($numero_serie) > 0 && strlen($data_nf) > 0) {
			$auxiliar = "AND";
		}

		if (strlen($numero_serie) > 0) {
			$cond_numero_serie = "serie = '$numero_serie'";
		}

		if (strlen($data_nf) > 0) {
			list($di, $mi, $yi) = explode("/", $data_nf);

			if(!checkdate($mi,$di,$yi)){
				$msg_erro["msg"][] = "Data \"$data_nf\" inválida";
			} else {
				$xdata_nf     = "$yi-$mi-$di";
				$cond_data_nf = "data_venda = '$xdata_nf'";
			}
		}

		$sql = "SELECT serie, cnpj, TO_CHAR(data_venda, 'DD/MM/YYYY') AS data_venda, nota_fiscal FROM tbl_numero_serie WHERE $cond_numero_serie $auxiliar $cond_data_nf ORDER BY data_venda, serie";
		$res = pg_query($con, $sql);
		$row = pg_num_rows($res);

		$dados_numero_serie = array();

		for ($i = 0; $i < $row; $i++) { 
			$serie       = pg_fetch_result($res, $i, 'serie');
			$cnpj        = pg_fetch_result($res, $i, 'cnpj');
			$data_venda  = pg_fetch_result($res, $i, 'data_venda');
			$nota_fiscal = pg_fetch_result($res, $i, 'nota_fiscal');

			$dados_numero_serie[$i]["serie"]       = $serie;
			$dados_numero_serie[$i]["cnpj"]        = $cnpj;
			$dados_numero_serie[$i]["data_venda"]  = $data_venda;
			$dados_numero_serie[$i]["nota_fiscal"] = $nota_fiscal;

			unset($erie, $cnpj, $data_venda, $nota_fiscal);
		}
	}
}

$layout_menu = "cadastro";
$title = "Importação de Campos $login_fabrica_nome";
include 'cabecalho_new.php';


$plugins = array(
	"datepicker",
	"dataTable",
	"mask"
);

include("plugin_loader.php");
?>

<script type="text/javascript">
	$(function() {
		$.datepickerLoad(Array("data_nf"));
		$.dataTableLoad({ table: "#resultado_numero_serie" });
	});
</script>

<?php if (count($msg_erro["msg"]) > 0) { ?>
    <div class="alert alert-error">
		<h4><?=implode("<br><br>", $msg_erro["msg"])?></h4>
    </div>
<?php } ?>

<?php if ($_GET["msg"] == "success") { ?>
    <div class="alert alert-success">
		<h4>Arquivo importado com sucesso</h4>		
    </div>
    <div class="alert alert">
    	<h4>Ver Resultado</h4> <a href=<?="xls/arquivo_".$login_fabrica."_".$_GET['data'].".txt"?> download>Arquivo</a>
    </div>
<?php } ?>

<div class='container'>
    <div class="alert">
        <h4>Regras do Arquivo</h4> <br>
        <p style="font-size: 14px;">
        	Arquivo CSV separado por <b>; (ponto e vírgula)</b> conforme layout abaixo: <br><br>

			<b>Número de Série;NF Saída <?=$login_fabrica_nome;?>;Data NF <?=$login_fabrica_nome;?>;CNPJ Cliente <?=$login_fabrica_nome;?></b> <br><br>

			<b>Exemplo:</b> <br>

			0816000180<b>;</b>151020181<b>;</b>15/10/2018<b>;</b>09275178000110 <br>
			0816000187<b>;</b>151020182<b>;</b>15/10/2018<b>;</b>03282303000132 <br>
			0816000193<b>;</b>151020183<b>;</b>15/10/2018<b>;</b>07228427000190 <br>
        </p>
    </div>  
</div>
<form name='frm_cadastro' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' enctype="multipart/form-data">
	<div class='titulo_tabela '>Cadastro</div>
	<br/>
	<div class="row-fluid">
		<div class="span4"></div>
		<div class="span4">
			<input type='hidden' name='btn_acao' value=''>
			<label class='control-label'>Anexar Arquivo</label>
			<div class='controls controls-row'>
				<input type='file' name='arquivo_csv'>
			</div>	
		</div>
		<div class="span2"></div>
	</div>
	<div class="row-fluid">
		<br>	
		<center>
			<button class='btn btn-success' onclick="javascript: if (document.forms[0].btn_acao.value == '' ) { document.forms[0].btn_acao.value='gravar'; document.forms[0].submit(); } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0' style="cursor:pointer;">Gravar</button>
		</center>
	</div>
	<br>
</form>

<form name='frm_pesquisa' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>
	<div class='titulo_tabela '>Parametros de Pesquisa</div>
	<br/>
	<div class='row-fluid'>
	    <div class='span2'></div>
	        <div class='span4'>
	            <div class='control-group'>
	                <label class='control-label' for='data_inicial'>Número de Série</label>
	                <div class='controls controls-row'>
	                    <div class='span6'>
	                            <input type="text" name="numero_serie" id="numero_serie" class='span12' value="<?=$numero_serie;?>">
	                    </div>
	                </div>
	            </div>
	        </div>
	    <div class='span4'>
	        <div class='control-group'>
	            <label class='control-label' for='data_final'>Data NF <?=$login_fabrica_nome;?></label>
	            <div class='controls controls-row'>
	                <div class='span4'>
	                        <input type="text" name="data_nf" id="data_nf" class='span12' value="<?=$data_nf;?>">
	                </div>
	            </div>
	        </div>
	    </div>
	    <div class='span2'></div>
	</div>
	<div class="row-fluid">
		<br>	
		<center>
			<input type='hidden' name='btn_acao_2' value=''>
			<button class='btn' onclick="javascript: if (document.forms[1].btn_acao_2.value == '' ) { document.forms[1].btn_acao_2.value='pesquisar'; document.forms[1].submit(); } else { alert ('Aguarde submissão') }" ALT="Pesquisar Número Série" border='0' style="cursor:pointer;">Pesquisar</button>
		</center>
	</div>
	<br>
</form>

<?php if ($_POST["btn_acao_2"] == "pesquisar") {
	if (count($dados_numero_serie) > 0) { ?>
		<table id="resultado_numero_serie" class='table table-striped table-bordered table-hover table-fixed'>
			<thead>
				<tr class="titulo_coluna">
					<th>Número Série</th>
					<th>NF Saída <?=$login_fabrica_nome;?></th>
					<th>Data NF <?=$login_fabrica_nome;?></th>
					<th>CNPJ Cliente <?=$login_fabrica_nome;?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($dados_numero_serie as $key => $value) { ?>
					<tr>
						<td><?=$value["serie"];?></td>
						<td><?=$value["nota_fiscal"];?></td>	
						<td class="tac"><?=$value["data_venda"];?></td>	
						<td><?=$value["cnpj"];?></td>	
					</tr>
				<?php } ?>
			</tbody>
		</table>
	<?php } else { ?>
		<div class='container'>
	    	<div class="alert">
	        	<h4>Nenhum resultado encontrado</h4>
		    </div>  
		</div>	
	<?php }
}

include 'rodape.php' ?>
