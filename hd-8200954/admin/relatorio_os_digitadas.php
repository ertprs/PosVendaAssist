<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios = "call_center";
include 'autentica_admin.php';
include 'funcoes.php';

$layout_menu = "callcenter";
$title = "RELATÓRIO OS DIGITADAS";

$btn_acao = $_REQUEST['btn_acao'];
if(strlen($btn_acao)>0){
	$data_inicial = $_REQUEST['data_inicial'];
	$data_final   = $_REQUEST['data_final'];

	if(strlen($data_inicial) > 0){
		$xdata_inicial =  fnc_formata_data_pg(trim($data_inicial));
		$xdata_inicial = str_replace("'","",$xdata_inicial);
	}else{
		$msg_erro["msg"][]    ="Data Inválida";
        $msg_erro["campos"][] = "data_inicial";
	}

	if(strlen($data_final) > 0){
		$xdata_final =  fnc_formata_data_pg(trim($data_final));
		$xdata_final = str_replace("'","",$xdata_final);
	}else{
		$msg_erro["msg"][]    ="Data Inválida";
        $msg_erro["campos"][] = "data_final";
	}

	if(!count($msg_erro["msg"])){
		$dat = explode ("/", $data_inicial );//tira a barra
        $d = $dat[0];
        $m = $dat[1];
        $y = $dat[2];
        if(!checkdate($m,$d,$y)){
            $msg_erro["msg"][]    ="Data Inválida";
            $msg_erro["campos"][] = "data_inicial";
        }
	}
	if(!count($msg_erro["msg"])){
		$dat = explode ("/", $data_final );//tira a barra
        $d = $dat[0];
        $m = $dat[1];
        $y = $dat[2];
        if(!checkdate($m,$d,$y)){
            $msg_erro["msg"][]    ="Data Inválida";
            $msg_erro["campos"][] = "data_final";
        }
	}

	if($xdata_inicial > $xdata_final){
		$msg_erro["msg"][]    ="Data Inicial maior que final";
        $msg_erro["campos"][] = "data_inicial";
    }else{
    	$sqlX = "SELECT '$xdata_inicial'::date + interval '6 months' > '$xdata_final'";
		$resX = pg_query($con,$sqlX);
		$periodo_meses = pg_fetch_result($resX,0,0);
		
		if($periodo_meses == 'f'){

			$msg_erro["msg"][] = "AS DATAS DEVEM SER NO MÁXIMO 6 MESES";
			$msg_erro["campos"][] = "data_inicial";
			$msg_erro["campos"][] = "data_final";

		}
    }

    if (count($msg_erro) == 0) {
    	$sql_pesq = "SELECT tbl_os.sua_os,
    						tbl_produto.referencia,
    						tbl_produto.descricao, 
    						tbl_linha.nome as linha, 
    						tbl_familia.descricao as familia, 
    						tbl_defeito_reclamado.descricao as defeito_reclamado, 
    						tbl_defeito_constatado.descricao as defeito_constatado,
    						SUM(tbl_os_item.qtde) as qtde
    				FROM tbl_os JOIN tbl_produto USING(produto)
    				JOIN tbl_os_produto ON (tbl_os.os = tbl_os_produto.os)
    				JOIN tbl_os_item ON (tbl_os_produto.os_produto = tbl_os_item.os_produto)
				JOIN tbl_familia ON (tbl_produto.familia = tbl_familia.familia AND tbl_familia.fabrica = $login_fabrica)
				JOIN tbl_linha ON (tbl_produto.linha  = tbl_linha.linha  AND tbl_linha.fabrica = $login_fabrica)
    				LEFT JOIN tbl_defeito_constatado ON (tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado AND tbl_defeito_constatado.fabrica = $login_fabrica)
    				LEFT JOIN tbl_defeito_reclamado ON (tbl_os.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado AND tbl_defeito_reclamado.fabrica = $login_fabrica)
    				WHERE tbl_os.fabrica = $login_fabrica
    				AND tbl_os.data_digitacao between '$xdata_inicial 00:00:00' AND '$xdata_final 23:59:59'
    				GROUP BY tbl_os.sua_os,
    						tbl_produto.referencia,
    						tbl_produto.descricao, 
    						tbl_linha.nome, 
    						tbl_familia.descricao, 
    						tbl_defeito_reclamado.descricao, 
    						tbl_defeito_constatado.descricao
    				 ";
    	//echo nl2br($sql_pesq);

    	$res_pesq = pg_query($con,$sql_pesq);

		if ($_POST["gerar_excel"]) {

			if (pg_num_rows($res_pesq)>0) {
				$data = date("d-m-Y-H-i");
				$fileName = "relatorio_od_digitadas_{$data}.csv";
				$file = fopen("/tmp/{$fileName}", "w");

				$head = "OS;Referência;Descrição;Linha;Família;Qtde Peças;Defeito Reclamado;Defeito Constatado;\r\n";
				
				fwrite($file, $head);
				$body = '';

				for ($x=0; $x<pg_num_rows($res_pesq);$x++){

					
					$x_sua_os 				= pg_fetch_result($res_pesq, $x,'sua_os');
					$x_referencia			= pg_fetch_result($res_pesq, $x,'referencia');
					$x_descricao 			= pg_fetch_result($res_pesq, $x,'descricao');
					$x_linha 				= pg_fetch_result($res_pesq, $x,'linha');
					$x_familia				= pg_fetch_result($res_pesq, $x,'familia');
					$x_defeito_reclamado 	= pg_fetch_result($res_pesq, $x,'defeito_reclamado');
					$x_defeito_constatado	= pg_fetch_result($res_pesq, $x,'defeito_constatado');

					
					$body .= $x_sua_os.";".$x_referencia.";".$x_descricao.";".$x_linha.";".$x_familia.";".$x_defeito_reclamado.";".$x_defeito_constatado;
					$body .= "\r\n";

				}
				$body = $body;
			    fwrite($file, $body);
			    fclose($file);
			    if (file_exists("/tmp/{$fileName}")) {

	                system("mv /tmp/{$fileName} xls/{$fileName}");

	                echo "xls/{$fileName}";
				}
			}
			exit;
		}
    	
    }	
}



include "cabecalho_new.php";



$plugins = array(
    "datepicker",
    "shadowbox",
    "mask",
    "dataTable"
);
include ("plugin_loader.php");

?>
<script>
$(function() {
    $.datepickerLoad(Array("data_final", "data_inicial"));
    $.autocompleteLoad(Array("produto"));
    Shadowbox.init();

    $("span[rel=lupa]").click(function () {
        $.lupa($(this));
    });

    var table = new Object();
	table['table'] = '#resultado_os_digitadas';
	table['type'] = 'full';
	$.dataTableLoad(table);

});
</script>

<!--Mensagem de erro-->
<?php if (count($msg_erro["msg"]) > 0) {	?>
    <div class="alert alert-error"> <h4><?php echo $msg_erro["msg"][0]; ?></h4> </div>
<?php 	}	?>
<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form class='form-search form-inline tc_formulario' name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">
    <div class="titulo_tabela">Parâmetros de Pesquisa</div>
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
			<input type='submit' style="cursor:pointer" name='btn_acao' value='Consultar'>
	</p><br/>
</FORM>
<br />

<!-- Tabela -->
<?
//Lista a Consulta dos Processos
if (isset($res_pesq)) {
	if(pg_num_rows($res_pesq) > 0){
	                    
	?>
	<form name="frm_tab" method="GET" class="form-search form-inline" enctype="multipart/form-data" >
		<table id="resultado_os_digitadas" class='table table-striped table-bordered table-hover table-large'>
			<thead>
				<tr class='titulo_coluna'>
					<td>OS</td>
					<td>Referência</td>
					<td>Descrição</td>
					<td>Linha</td>
					<td>Família</td>
					<td>Qtde Peças</td>
					<td>Defeito Constatado</td>
					<td>Defeito Reclamado</td>
				</tr>
			</thead>
			<tbody>
				<?
				for ($i = 0 ; $i < pg_num_rows($res_pesq) ; $i++) {

					$t_sua_os 				= pg_fetch_result($res_pesq, $i,'sua_os');
					$t_referencia			= pg_fetch_result($res_pesq, $i,'referencia');
					$t_descricao 			= pg_fetch_result($res_pesq, $i,'descricao');
					$t_linha 				= pg_fetch_result($res_pesq, $i,'linha');
					$t_familia				= pg_fetch_result($res_pesq, $i,'familia');
					$t_qtde 				= pg_fetch_result($res_pesq, $i,'qtde');
					$t_defeito_reclamado 	= pg_fetch_result($res_pesq, $i,'defeito_reclamado');
					$t_defeito_constatado	= pg_fetch_result($res_pesq, $i,'defeito_constatado');					

					?>	
					<tr>
						<td><?echo $t_sua_os?></td>
						<td><?echo $t_referencia?></td>
						<td><?echo $t_descricao?></td>
						<td><?echo $t_linha?></td>
						<td><?echo $t_familia?></td>
						<td><?echo $t_qtde?></td>
						<td><?echo $t_defeito_reclamado?></td>
						<td><?echo $t_defeito_constatado?></td>						
					</tr>
				<?
				}
				?>
			</tbody>
		</table>
	</form>
	<br />
		<?php
			$jsonPOST = excelPostToJson($_POST);
		?>

		<div id='gerar_excel' class="btn_excel">
			<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
			<span><img src='imagens/excel.png' /></span>
			<span class="txt">Gerar Excel</span>
		</div>
	<?
	}else{?>
	<div class="container">
		<div class="alert">
		    <h4>Nenhum resultado encontrado</h4>
		</div>
	</div>
	<?
	}
}
?>
<? include "rodape.php" ?>
