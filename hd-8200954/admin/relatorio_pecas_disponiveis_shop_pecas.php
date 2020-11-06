<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "gerencia";

include 'autentica_admin.php';
include 'funcoes.php';

$layout_menu = "financeiro";
$title = "PEÇAS DISPONÍVEIS NO SHOP PEÇAS";

if(isset($_POST['btn_pesquisar'])){

	$posto           = $_POST["posto"];
	$codigo_posto    = $_POST["codigo_posto"];
	$descricao_posto = $_POST["descricao_posto"];
	$referencia      = $_POST["peca_referencia"];
	$descricao       = $_POST["peca_descricao"];

	if($login_fabrica == 20){

		$download = (isset($_POST["download"])) ? $_POST["download"] : "";

	}

	if(strlen($codigo_posto) > 0 && strlen($descricao_posto)){
		
		$cond_posto = " AND tbl_vitrine.posto = {$posto} ";

	}

	if(strlen($referencia) > 0 && strlen($descricao) > 0){

		$referencia = trim($referencia);
		$cond_peca = " AND tbl_vitrine.peca = (SELECT peca FROM tbl_peca WHERE referencia = '{$referencia}' AND fabrica = {$login_fabrica}) ";

	}

	$sql = "SELECT 
				tbl_vitrine.vitrine,
				tbl_vitrine.posto,
				(
					SELECT 
						tbl_posto_fabrica.codigo_posto || ' - ' || tbl_posto.nome  
					FROM tbl_posto_fabrica 
					INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica} 
					WHERE 
						tbl_posto_fabrica.posto = tbl_vitrine.posto 
				) AS dados_posto,
				tbl_vitrine.data_input AS data_cadastro,
				tbl_vitrine.peca,
				tbl_peca.referencia,
				tbl_peca.descricao,
				tbl_vitrine.qtde 
			FROM tbl_vitrine  
			INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_vitrine.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica} 
			INNER JOIN tbl_peca ON tbl_vitrine.peca = tbl_peca.peca AND tbl_peca.fabrica = {$login_fabrica} 
			WHERE 
				tbl_vitrine.ativo IS TRUE  
				{$cond_posto} 
				{$cond_peca}";

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
                        <th><font color='#FFFFFF'>Data da Disponibilidade</font></th>
                    </tr>
                </thead>
                <tbody>";
        	fwrite($fp, $head);

        	for ($i = 0; $i < $num; $i++) {
			
				$vitrine       = trim(pg_fetch_result($result, $i, "vitrine"));
				$posto         = trim(pg_fetch_result($result, $i, "posto"));
				$dados_posto   = trim(pg_fetch_result($result, $i, "dados_posto"));
				$data_cadastro = trim(pg_fetch_result($result, $i, "data_cadastro"));
				$peca          = trim(pg_fetch_result($result, $i, "peca"));
				$referencia    = trim(pg_fetch_result($result, $i, "referencia"));
				$descricao     = trim(pg_fetch_result($result, $i, "descricao"));
				$qtde          = trim(pg_fetch_result($result, $i, "qtde"));

				list($data, $hora) = explode(" ", $data_cadastro);
				list($ano, $mes, $dia) = explode("-", $data);
				$data_cadastro = $dia."/".$mes."/".$ano;

				$body = "<tr>";
					$body .= "<td align='center'>".$referencia."</td>";
					$body .= "<td>".$descricao."</td>";
					$body .= "<td align='center'>".$qtde."</td>";
					$body .= "<td>".$dados_posto."</td>";
					$body .= "<td align='center'>".$data_cadastro."</td>";
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

	        $head = "Código Peça;Descrição Peça;Qtde;Nome do Vendedor;Data da Disponibilidade \n";
        	fwrite($fp, $head);

        	for ($i = 0; $i < $num; $i++) {
			
				$vitrine       = trim(pg_fetch_result($result, $i, "vitrine"));
				$posto         = trim(pg_fetch_result($result, $i, "posto"));
				$dados_posto   = trim(pg_fetch_result($result, $i, "dados_posto"));
				$data_cadastro = trim(pg_fetch_result($result, $i, "data_cadastro"));
				$peca          = trim(pg_fetch_result($result, $i, "peca"));
				$referencia    = trim(pg_fetch_result($result, $i, "referencia"));
				$descricao     = trim(pg_fetch_result($result, $i, "descricao"));
				$qtde          = trim(pg_fetch_result($result, $i, "qtde"));

				list($data, $hora) = explode(" ", $data_cadastro);
				list($ano, $mes, $dia) = explode("-", $data);
				$data_cadastro = $dia."/".$mes."/".$ano;

				$body = $referencia.";";
				$body .= $descricao.";";
				$body .= $qtde.";";
				$body .= $dados_posto.";";
				$body .= $data_cadastro."\n";

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

include 'cabecalho_new.php';

$plugins = array(
	"dataTable",
	"shadowbox",
	"autocomplete"
);

include("plugin_loader.php");

?>

	<script>

		$(function(){

			Shadowbox.init();

			$(document).on("click", "span[rel=lupa]", function () {
				$.lupa($(this));
			});

		});

		function retorna_posto(retorno){

		    $("#posto").val(retorno.posto);
		    $("#codigo_posto").val(retorno.codigo);
			$("#descricao_posto").val(retorno.nome);

		}

		function retorna_peca(retorno){

	        $("input[name='peca_referencia']").val(retorno.referencia);
	        $("input[name='peca_descricao']").val(retorno.descricao);

	    }

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

			<div class="span3">
				<div class='control-group'>

					<input type="hidden" name="posto" id="posto" value="<? echo $posto?>">

					<label class='control-label' for='codigo_posto'>Código Posto</label>
					<div class='controls controls-row'>
						<div class='span10 input-append'>
							<input type="text" name="codigo_posto" id="codigo_posto" class='span11' value="<?php echo $codigo_posto ?>" >
							<span class='add-on' rel="lupa">
								<i class='icon-search' ></i>
								<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
							</span>
						</div>

					</div>
				</div>
			</div>

			<div class="span5">

				<div class='control-group'>
					<label class='control-label' for='descricao_posto'>Razão Social</label>
					<div class='controls controls-row'>
						<div class='span11 input-append'>
							<input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<?php echo $descricao_posto ?>" >
							<span class='add-on' rel="lupa">
								<i class='icon-search' ></i>
								<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
							</span>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="row-fluid">
								
			<div class="span2"></div>

			<div class="span3">
				<div class='control-group' >
					<label class="control-label">Referência Peça</label>
					<div class="controls controls-row">
						<div class="span9 input-append">
							<input  name="peca_referencia" class="span12" type="text" value="<?=$peca_referencia?>" />
							<span class="add-on" rel="lupa" >
								<i class="icon-search"></i>
							</span>
							<input type="hidden" name="lupa_config" tipo="peca" parametro="referencia" />
						</div>
					</div>
				</div>
			</div>

			<div class="span5">
				<div class='control-group' >
					<label class="control-label" >Descrição Peça</label>
					<div class="controls controls-row">
						<div class="span11 input-append">
							<input name="peca_descricao" class="span12" type="text" value="<?=$peca_descricao?>" />
							<span class="add-on" rel="lupa" >
								<i class="icon-search"></i>
							</span>
							<input type="hidden" name="lupa_config" tipo="peca" parametro="descricao" />
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

if(isset($_POST["btn_pesquisar"])){

	if($num > 0){

		echo "<table class='table table-bordered table-striped' id='relatorio' style='width: 1200px !important; margin: 0 auto;'>";

			echo "<thead>";

				echo "<tr>";
					echo "<td colspan='6' class='titulo_tabela tac'>Peças disponíveis no Shop</td>";
				echo "</tr>";

				echo "<tr class='titulo_coluna'>";

					echo "<th nowrap>Código Peça</th>";
					echo "<th nowrap>Descrição Peça</th>";
					echo "<th nowrap>Qtde</th>";
					echo "<th nowrap>Nome do Vendedor</th>";
					echo "<th nowrap>Data da Disponibilidade</th>";

				echo "</tr>";

			echo "</thead>";

			echo "<tbody>";

			for ($i = 0; $i < $num; $i++) {
				
				$vitrine       = trim(pg_fetch_result($result, $i, "vitrine"));
				$posto         = trim(pg_fetch_result($result, $i, "posto"));
				$dados_posto   = trim(pg_fetch_result($result, $i, "dados_posto"));
				$data_cadastro = trim(pg_fetch_result($result, $i, "data_cadastro"));
				$peca          = trim(pg_fetch_result($result, $i, "peca"));
				$referencia    = trim(pg_fetch_result($result, $i, "referencia"));
				$descricao     = trim(pg_fetch_result($result, $i, "descricao"));
				$qtde          = trim(pg_fetch_result($result, $i, "qtde"));

				list($data, $hora) = explode(" ", $data_cadastro);
				list($ano, $mes, $dia) = explode("-", $data);
				$data_cadastro = $dia."/".$mes."/".$ano;

				echo "<tr>";
					echo "<td class='tac'><a href='peca_cadastro.php?peca={$peca}' target='_blank'>".$referencia."</a></td>";
					echo "<td>".$descricao."</td>";
					echo "<td class='tac'>".$qtde."</td>";
					echo "<td><a href='posto_cadastro.php?posto={$posto}' target='_blank'>".$dados_posto."</a></td>";
					echo "<td class='tac'>".$data_cadastro."</td>";
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