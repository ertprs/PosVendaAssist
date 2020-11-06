<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "gerencia";

include 'autentica_admin.php';
include 'funcoes.php';

$layout_menu = "financeiro";
$title = "ACOMPANHAMENTO DE COMPRA E VENDA ENTRE POSTOS";

$msg_erro = array();

if(isset($_POST['btn_pesquisar'])){

	$data_inicial = $_POST["data_inicial"];
	$data_final = $_POST["data_final"];

	if($login_fabrica == 20){

		$download = (isset($_POST["download"])) ? $_POST["download"] : "";

	}

	if(strlen($data_inicial) == 0){
		$msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
    	$msg_erro["campos"][]   = "data_inicial";
	}

	if(strlen($data_final) == 0){
		$msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
    	$msg_erro["campos"][]   = "data_final";
	}

	if(strlen($data_inicial) > 0 && strlen($data_final) > 0 && count($msg_erro) == 0){

		list($d1, $m1, $a1) = explode("/", $data_inicial);
		list($d2, $m2, $a2) = explode("/", $data_final);

		$data1 = $a1."-".$m1."-".$d1;
		$data2 = $a2."-".$m2."-".$d2;

		$inicio = new DateTime($data1);
		$fim = new DateTime($data2);
		$intervalo = date_diff($inicio, $fim);

		if((int)$intervalo->days > 365){
			$msg_erro["msg"]["obg"] = "O intervalo entre as datas não pode ser maior que 12 meses";
		}

	}

	if(count($msg_erro) == 0){

		$sql = "SELECT 
					tbl_vitrine_pedido.vitrine_pedido,
					tbl_vitrine_pedido.posto_venda,
					(
						SELECT 
							tbl_posto_fabrica.codigo_posto || ' - ' || tbl_posto.nome  
						FROM tbl_posto_fabrica 
						INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica} 
						WHERE 
							tbl_posto_fabrica.posto = tbl_vitrine_pedido.posto_venda 
					) AS dados_posto_venda,
					tbl_vitrine_pedido.posto_compra,
					(
						SELECT 
							tbl_posto_fabrica.codigo_posto || ' - ' || tbl_posto.nome  
						FROM tbl_posto_fabrica 
						INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica} 
						WHERE 
							tbl_posto_fabrica.posto = tbl_vitrine_pedido.posto_compra 
					) AS dados_posto_compra,
					tbl_vitrine_pedido.data AS data_pedido,
					tbl_vitrine_pedido_item.peca,
					tbl_peca.referencia,
					tbl_peca.descricao,
					tbl_vitrine_pedido_item.qtde 
				FROM tbl_vitrine_pedido 
				INNER JOIN tbl_vitrine_pedido_item ON tbl_vitrine_pedido_item.vitrine_pedido = tbl_vitrine_pedido.vitrine_pedido 
				INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_vitrine_pedido.posto_compra AND tbl_posto_fabrica.fabrica = {$login_fabrica} 
				INNER JOIN tbl_peca ON tbl_vitrine_pedido_item.peca = tbl_peca.peca AND tbl_peca.fabrica = {$login_fabrica} 
				WHERE 
					tbl_vitrine_pedido.finalizado NOTNULL 
					AND tbl_vitrine_pedido.data BETWEEN '{$data1}' AND '{$data2}'";

		$result = pg_query($con, $sql);

		$num = pg_num_rows($result);

		if($login_fabrica == 20){

			if($download == "xls"){

				$file     = "xls/relatorio-compras-vendas-postos-{$login_fabrica}.xls";
		        $fileTemp = "/tmp/relatorio-compras-vendas-postos-{$login_fabrica}.xls" ;
		        $fp       = fopen($fileTemp,'w');

		        $head = "
                <table border='1'>
                    <thead>
                        <tr bgcolor='#596D9B'>
                            <th><font color='#FFFFFF'>Código Peça</font></th>
                            <th><font color='#FFFFFF'>Descrição Peça</font></th>
                            <th><font color='#FFFFFF'>Qtde</font></th>
                            <th><font color='#FFFFFF'>Nome do Vendedor</font></th>
                            <th><font color='#FFFFFF'>Nome do Comprador</font></th>
                            <th><font color='#FFFFFF'>Data da Compra</font></th>
                        </tr>
                    </thead>
                    <tbody>";
            	fwrite($fp, $head);

            	for ($i = 0; $i < $num; $i++) {
				
					$vitrine_pedido     = trim(pg_fetch_result($result, $i, "vitrine_pedido"));
					$posto_venda        = trim(pg_fetch_result($result, $i, "posto_venda"));
					$dados_posto_venda  = trim(pg_fetch_result($result, $i, "dados_posto_venda"));
					$posto_compra       = trim(pg_fetch_result($result, $i, "posto_compra"));
					$dados_posto_compra = trim(pg_fetch_result($result, $i, "dados_posto_compra"));
					$data_pedido        = trim(pg_fetch_result($result, $i, "data_pedido"));
					$peca               = trim(pg_fetch_result($result, $i, "peca"));
					$referencia         = trim(pg_fetch_result($result, $i, "referencia"));
					$descricao          = trim(pg_fetch_result($result, $i, "descricao"));
					$qtde               = trim(pg_fetch_result($result, $i, "qtde"));

					list($data, $hora) = explode(" ", $data_pedido);
					list($ano, $mes, $dia) = explode("-", $data);
					$data_pedido = $dia."/".$mes."/".$ano;

					$body = "<tr>";
						$body .= "<td align='center'>".$referencia."</td>";
						$body .= "<td>".$descricao."</td>";
						$body .= "<td align='center'>".$qtde."</td>";
						$body .= "<td>".$dados_posto_venda."</td>";
						$body .= "<td>".$dados_posto_compra."</td>";
						$body .= "<td align='center'>".$data_pedido."</td>";
					$body .= "</tr>";

					fwrite($fp, $body);

				}

				fwrite($fp, '</tbody></table>');
		        fclose($fp);

		        if(file_exists($fileTemp)){
		            system("mv $fileTemp $file");

		            if(file_exists($file)){
		                $file_download = $file;
		            }
		        }


			}else if($download == "txt"){

				$file     = "xls/relatorio-compras-vendas-postos-{$login_fabrica}.txt";
		        $fileTemp = "/tmp/relatorio-compras-vendas-postos-{$login_fabrica}.txt" ;
		        $fp       = fopen($fileTemp,'w');

		        $head = "Código Peça;Descrição Peça;Qtde;Nome do Vendedor;Nome do Comprador;Data da Compra \n";
            	fwrite($fp, $head);

            	for ($i = 0; $i < $num; $i++) {
				
					$vitrine_pedido     = trim(pg_fetch_result($result, $i, "vitrine_pedido"));
					$posto_venda        = trim(pg_fetch_result($result, $i, "posto_venda"));
					$dados_posto_venda  = trim(pg_fetch_result($result, $i, "dados_posto_venda"));
					$posto_compra       = trim(pg_fetch_result($result, $i, "posto_compra"));
					$dados_posto_compra = trim(pg_fetch_result($result, $i, "dados_posto_compra"));
					$data_pedido        = trim(pg_fetch_result($result, $i, "data_pedido"));
					$peca               = trim(pg_fetch_result($result, $i, "peca"));
					$referencia         = trim(pg_fetch_result($result, $i, "referencia"));
					$descricao          = trim(pg_fetch_result($result, $i, "descricao"));
					$qtde               = trim(pg_fetch_result($result, $i, "qtde"));

					list($data, $hora) = explode(" ", $data_pedido);
					list($ano, $mes, $dia) = explode("-", $data);
					$data_pedido = $dia."/".$mes."/".$ano;

					$body = $referencia.";";
					$body .= $descricao.";";
					$body .= $qtde.";";
					$body .= $dados_posto_venda.";";
					$body .= $dados_posto_compra.";";
					$body .= $data_pedido."\n";

					fwrite($fp, $body);

				}

		        fclose($fp);

		        if(file_exists($fileTemp)){
		            system("mv $fileTemp $file");

		            if(file_exists($file)){
		                $file_download = $file;
		            }
		        }

			}

		}

	}


}

include 'cabecalho_new.php';

$plugins = array(
	"datepicker",
	"mask",
	"dataTable"
);

include("plugin_loader.php");

?>

	<script>

		$(function(){

			$.datepickerLoad(Array("data_inicial", "data_final"));

			$("#data_inicial").mask("99/99/9999");
			$("#data_final").mask("99/99/9999");

		});

		<?php

		if(strlen($file_download) > 0){

			?>

			setTimeout(function(){
				window.open("<?php echo $file_download; ?>", "_blank");
				//location.href = "<?php echo $file_download; ?>";
			}, 1000);

			<?php

		}

		?>

	</script>

		<?php
	if ((count($msg_erro["msg"]) > 0) ) {
	?>
	    <div class="alert alert-error">
	        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
	    </div>
	<?php
	}
	?>

	<div class="row">
		<b class="obrigatorio pull-right">* Campos obrigatórios </b>
	</div>

	<form name='frm_relatorio' method='post' action='<?=$PHP_SELF?>' class='tc_formulario'>

		<div class="titulo_tabela">Paramêtros de Pesquisa</div>

		<br />

		<div class="row-fluid">

			<div class="span2"></div>

			<div class='span4'>
	            <div class='control-group <?=(in_array("data_inicial", $msg_erro["campos"])) ? "error" : ""?>'>
	                <label class='control-label' for='data_inicial'>Data Inicial</label>
	                <div class='controls controls-row'>
	                    <div class='span8'>
	                    	<h5 class='asteristico'>*</h5>
	                        <input type="text" name="data_inicial" id="data_inicial" size="12" class='span12' value= "<?=$data_inicial?>">
	                    </div>
	                </div>
	            </div>
	        </div>

	        <div class='span4'>
	            <div class='control-group <?=(in_array("data_final", $msg_erro["campos"])) ? "error" : ""?>'>
	                <label class='control-label' for='data_final'>Data Final</label>
	                <div class='controls controls-row'>
	                    <div class='span8'>
	                    	<h5 class='asteristico'>*</h5>
	                        <input type="text" name="data_final" id="data_final" size="12" class='span12' value= "<?=$data_final?>">
	                    </div>
	                </div>
	            </div>
	        </div>

	    </div>

	    <?php

	    if($login_fabrica == 20){

	    	?>

	    	<div class="row-fluid">

				<div class="span2"></div>

				<div class='span8 tac'>
					Realizar download do relatório <br />
					<input type="radio" name="download" value="xls" <?php echo ($download == "xls") ? "checked" : ""; ?> /> XLS 
					&nbsp; &nbsp; 
					<input type="radio" name="download" value="txt" <?php echo ($download == "txt") ? "checked" : ""; ?> /> TXT
				</div>

			</div>

	    	<?php

	    }

	    ?>

	    <p>
	    	<input type="submit" value="Pesquisar" class="btn">
	    	<input type='hidden' id="btn_click" name='btn_pesquisar' value="pesquisar" />
		</p>

		<br />

	</form>

</div>

<br />

<?php

if(count($msg_erro) == 0 && isset($_POST["btn_pesquisar"])){

	if($num > 0){

		echo "<table class='table table-bordered table-striped' id='relatorio' style='width: 1200px !important; margin: 0 auto;'>";

			echo "<thead>";

				echo "<tr>";
					echo "<td colspan='6' class='titulo_tabela tac'>Relação de compra e venda de peças</td>";
				echo "</tr>";

				echo "<tr class='titulo_coluna'>";

					echo "<th nowrap>Código Peça</th>";
					echo "<th nowrap>Descrição Peça</th>";
					echo "<th nowrap>Qtde</th>";
					echo "<th nowrap>Nome do Vendedor</th>";
					echo "<th nowrap>Nome do Comprador</th>";
					echo "<th nowrap>Data da Compra</th>";

				echo "</tr>";

			echo "</thead>";

			echo "<tbody>";

			for ($i = 0; $i < $num; $i++) {
				
				$vitrine_pedido     = trim(pg_fetch_result($result, $i, "vitrine_pedido"));
				$posto_venda        = trim(pg_fetch_result($result, $i, "posto_venda"));
				$dados_posto_venda  = trim(pg_fetch_result($result, $i, "dados_posto_venda"));
				$posto_compra       = trim(pg_fetch_result($result, $i, "posto_compra"));
				$dados_posto_compra = trim(pg_fetch_result($result, $i, "dados_posto_compra"));
				$data_pedido        = trim(pg_fetch_result($result, $i, "data_pedido"));
				$peca               = trim(pg_fetch_result($result, $i, "peca"));
				$referencia         = trim(pg_fetch_result($result, $i, "referencia"));
				$descricao          = trim(pg_fetch_result($result, $i, "descricao"));
				$qtde               = trim(pg_fetch_result($result, $i, "qtde"));

				list($data, $hora) = explode(" ", $data_pedido);
				list($ano, $mes, $dia) = explode("-", $data);
				$data_pedido = $dia."/".$mes."/".$ano;

				echo "<tr>";
					echo "<td class='tac'><a href='peca_cadastro.php?peca={$peca}' target='_blank'>".$referencia."</a></td>";
					echo "<td>".$descricao."</td>";
					echo "<td class='tac'>".$qtde."</td>";
					echo "<td><a href='posto_cadastro.php?posto={$posto_venda}' target='_blank'>".$dados_posto_venda."</a></td>";
					echo "<td><a href='posto_cadastro.php?posto={$posto_compra}' target='_blank'>".$dados_posto_compra."</a></td>";
					echo "<td class='tac'>".$data_pedido."</td>";
				echo "</tr>";

			}

			echo "</tbody>";
		echo "</table>";

		if($num > 50){

		?>

		<script>
	        $.dataTableLoad({
	            table : "#relatorio"
	        });
        </script>

		<?php

		}

	}else{

		echo "<div class='container'>";
			echo "<div class='alert alert-block alert-warning'><h4>Não foram Encontrados Resultados para esta Pesquisa</h4></div>";
		echo "</div>";

	}

}

?>

<br />

<?php

include 'rodape.php';

?>