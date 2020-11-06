<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="gerencia";
include 'autentica_admin.php';
include 'funcoes.php';


if ($_POST["btn_acao"] == "submit") {
	 $data_inicial = $_POST['data_inicial'];
	 $data_final   = $_POST['data_final'];
	
		//INÍCIO VALIDAÇÃO DATAS
			
			if(empty($data_inicial) OR empty($data_final)){
				$msg_erro["msg"][] = "Peencha os campos obrigatórios";
				$msg_erro["campos"][] = "data_inicial";
				$msg_erro["campos"][] = "data_final";
			}

		if(strlen($msg_erro) == 0){
			if(!empty($data_inicial) and !empty($data_final)){
				if(strlen($msg_erro)==0){
					list($di, $mi, $yi) = explode("/", $data_inicial);
					if(!checkdate($mi,$di,$yi)) 
						$msg_erro["msg"][] = "Data inicial inválida";
						$msg_erro["campos"][] = "data_inicial";
				}
				
				if(strlen($msg_erro)==0){
					list($df, $mf, $yf) = explode("/", $data_final);
					if(!checkdate($mf,$df,$yf)) 
						$msg_erro["msg"][] = "Data final inválida";
						$msg_erro["campos"][] = "data_final";
				}

				if(strlen($msg_erro)==0){
					$aux_data_inicial = "$yi-$mi-$di";
					$aux_data_final = "$yf-$mf-$df";
				}
				
				if(strlen($msg_erro)==0){
				    if (strtotime($aux_data_inicial.'+1 month') < strtotime($aux_data_final) ) {
				    		$msg_erro["msg"][] = "O intervalo entre as datas não pode ser maior que 1 mês";
				        }
				}
				
				if(strlen($msg_erro)==0){
			        if(strtotime($aux_data_final) < strtotime($aux_data_inicial)){
			            $msg_erro["msg"][] = "Data inicial maior do que data final";
			        }
					
				}

			}
		}
		//FIM VALIDAÇÃO DATAS
		if(count($msg_erro["msg"]) == 0){

			if ($login_fabrica == 94) {
				$distinct = "DISTINCT ON (tbl_os_defeito_reclamado_constatado.defeito_constatado_reclamado)";

				$join_defeito = "LEFT JOIN tbl_os_defeito_reclamado_constatado ON tbl_os.os = tbl_os_defeito_reclamado_constatado.os 
					 LEFT JOIN tbl_defeito_constatado ON tbl_os_defeito_reclamado_constatado.defeito_constatado = tbl_defeito_constatado.defeito_constatado";
			}else{
				$distinct = "";

				$join_defeito = "LEFT JOIN tbl_defeito_constatado ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado ";
			}
						
			$relatorio_periodo = "PERÍODO DESTE RELATÓRIO:;".$data_inicial.";".$data_final; 
			$indece_coluna .="\nORDEM DE SERVIÇO;DATA ABERTURA;DATA FECHAMENTO;POSTO;";
			$indece_coluna .="LINHA DE PRODUTO;FAMÍLIA PRODUTO;PRODUTO;DEFEITO CONSTATADO;PEDIDO;";
			$indece_coluna .="COMPONENTE DESCRIÇÃO;SERIE;QTDE;SERVIÇO;NOTA FISCAL;DATA NF;OBS OS;";
			
			$fileName = "relatorio_os_periodo_{$login_admin}.csv";

			$file = fopen("/tmp/{$fileName}", "w");
			fwrite($file,$relatorio_periodo);
			fwrite($file,$indece_coluna);

			$sql_principal = "
			SELECT $distinct
			tbl_os.sua_os, 
			TO_CHAR(tbl_os.data_abertura, 'DD/MM/YYYY') AS data_abertura, 
			TO_CHAR(tbl_os.data_fechamento, 'DD/MM/YYYY') AS data_fechamento, 
			tbl_posto.nome AS posto_nome, 
			tbl_linha.nome AS linha_nome, 
			
			tbl_familia.descricao AS familia_descricao, 
			tbl_produto.referencia AS produto_referencia, 
			tbl_produto.descricao AS produto_descricao, 
			
			tbl_defeito_constatado.descricao AS defeito_constatado_descricao, 
			tbl_os_item.pedido,
			tbl_peca.peca,
			tbl_os.serie,
			tbl_peca.referencia AS peca_referencia, 
			tbl_peca.descricao AS peca_descricao, 
			tbl_os_item.qtde, 
			tbl_servico_realizado.descricao AS servico_realizado_descricao, 
			CASE
				WHEN tbl_os.obs = 'null' THEN
					''
				ELSE 
					tbl_os.obs
			END AS obs
			
			FROM 
			tbl_os 
			LEFT JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os 
			LEFT JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto 
			LEFT JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca 
			LEFT JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado 
			JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto 
			JOIN tbl_produto ON tbl_os.produto=tbl_produto.produto 
			JOIN tbl_linha ON tbl_produto.linha = tbl_linha.linha 
			JOIN tbl_familia ON tbl_produto.familia = tbl_familia.familia 
			$join_defeito
			
			WHERE 
			tbl_os.fabrica={$login_fabrica} 
			AND tbl_os.finalizada BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' ";
			$res_principal = pg_query($con, $sql_principal); 
			if(pg_num_rows($res_principal)>0){
				for($indice = 0; $indice < pg_num_rows($res_principal); $indice++) {
					extract(pg_fetch_assoc($res_principal));
					if($pedido > 0){
						$sql_secundario="
						SELECT 
						TRIM(tbl_faturamento.nota_fiscal) AS nota_fiscal , 
						TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS nota_fiscal_emissao 
						
						FROM 
						tbl_faturamento 
						JOIN tbl_faturamento_item ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento 
						
						WHERE 
						tbl_faturamento.fabrica = {$login_fabrica} 
						AND tbl_faturamento_item.pedido={$pedido}
						AND tbl_faturamento_item.peca={$peca}
						
						LIMIT 1
						";
						$res_secundario = pg_query($con, $sql_secundario); 
						if (pg_num_rows($res_secundario)){
							extract(pg_fetch_assoc($res_secundario));
						}else {
							unset($nota_fiscal);
							unset($nota_fiscal_emissao);
						} 
					}
					
					$linha = array();
						if ($login_fabrica == 94) {
							$linha[] = $sua_os;
							$linha[] = $data_abertura;
							$linha[] = $data_fechamento;
							$linha[] = $posto_nome;
							$linha[] = $linha_nome;
							$linha[] = $familia_descricao;
							$linha[] = $produto_referencia."-".$produto_descricao;
							$linha[] = $defeito_constatado_descricao;
						}else{
				     		if($sua_os == $sua_os_anterior){
								for($x = 0 ; $x < 8 ;$x++){
									$linha[] = " "; 
								}
							}else{
								$linha[] = $sua_os;
								$linha[] = $data_abertura;
								$linha[] = $data_fechamento;
								$linha[] = $posto_nome;
								$linha[] = $linha_nome;
								$linha[] = $familia_descricao;
								$linha[] = $produto_referencia."-".$produto_descricao;
								$linha[] = $defeito_constatado_descricao;
							}
						}
					$linha[] = $pedido;
					$linha[] = $peca_referencia."-".$peca_descricao;
					$linha[] = $serie;
					$linha[] = $qtde;
					$linha[] = $servico_realizado_descricao;
					$linha[] = $nota_fiscal;
					$linha[] = $nota_fiscal_emissao;
					if($sua_os == $sua_os_anterior){
						$linha[] = " "; 
					}else{
						$linha[] = $obs;
					}
					$linha = implode(";", $linha);
					$linha = str_replace("\n", "", $linha); 
					$linha = str_replace("\r", "", $linha);
					$linha = "\n" . $linha;
					
					fwrite($file,$linha);
				       $sua_os_anterior = $sua_os;
				}
			fclose($file);

			$zip_nome_do_arquivo = "relatorio_os_periodo_{$login_admin}.zip";
			$zip_diretorio_arquivo = $caminho_diretorio.$zip_nome_do_arquivo;

			

			if (file_exists("/tmp/{$fileName}")) {
				echo `cd /tmp && zip -q $zip_nome_do_arquivo $fileName`;

				system("mv /tmp/{$zip_nome_do_arquivo} xls/{$zip_nome_do_arquivo}");

				echo "<script>window.open('xls/{$zip_nome_do_arquivo}');</script>";
			}
			
			}else{
				$msg_erro="Não foram encontrados registros para esse período";
			}
		}
				
}
$title = "RELATÓRIO DE OS POR PERÍODO";
$layout_menu = "gerencia";

include 'cabecalho_new.php';

$plugins = array(
	"datepicker"
);

?>

<script language='javascript'>
	$(function() {
		$.datepickerLoad(Array("data_final", "data_inicial"));
	});
</script>

<div class="alert">
	Este Relatório considera a Data de Fechamento das Ordens de Serviço.
</div>
<br/>

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

<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
	<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
	<br/>

	<div class='row-fluid'>
		<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data_inicial", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'>Data Inicial</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<h5 class='asteristico'>*</h5>
								<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
						</div>
					</div>
				</div>
			</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("data_final", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='data_final'>Data Final</label>
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
	<p><br/>
		<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
		<input type='hidden' id="btn_click" name='btn_acao' value='' />
	</p><br/>
</form>

<div class="btn_download"><?php echo $btn_download; ?></div>
<?php 
	include "rodape.php";
?>
