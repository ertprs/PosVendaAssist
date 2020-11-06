<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "call_center";

include 'autentica_admin.php';
include 'funcoes.php';

$btn_acao       = $_POST['btn_acao'];

function uper_acentos($texto){
	$array1 = array('á', 'à', 'â', 'ã', 'é', 'è', 'ê', 'í', 'ì', 'î', 'ó', 'ò', 'ô', 'õ', 'ú', 'ù', 'û', 'ç' , '\'');
	$array2 = array('Á', 'À', 'Â', 'Ã', 'É', 'È', 'Ê', 'Í', 'Ì', 'Î', 'Ó', 'Ò', 'Ô', 'Õ', 'Ú', 'Ù', 'Û', 'Ç' , '');
	return str_replace( $array1, $array2, $texto );
}

if($btn_acao == "submit"){
	$data_inicial		 = $_POST["data_inicial"];
	$data_final			 = $_POST["data_final"];
	$hd_chamado          = $_POST["hd_chamado"];
	$cliente			 = $_POST["cliente"];
	$cpf				 = $_POST["cpf"];
	$centro_distribuicao = $_POST['centro_distribuicao'];

	# Validações
	if ((!strlen($data_inicial) || !strlen($data_final)) && !strlen($hd_chamado)) {
		$msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
		$msg_erro["campos"][]   = "data";
	}


	if(!count($msg_erro["msg"])){

		if(strlen($hd_chamado) > 0){
			$cond = " AND tbl_hd_chamado_extra.hd_chamado = $hd_chamado ";
		}

		if(strlen($data_inicial) > 0 && strlen($data_final) > 0){

			list($d,$m,$y) = explode("/", $data_inicial);

			if(!checkdate($m, $d, $y)){
				$msg_erro['msg'] = "Data inicial inválida";
			}else{
				$data_inicial_formatada = "$y-$m-$d 00:00:00";
			}


			list($d,$m,$y) = explode("/", $data_final);

			if(!checkdate($m, $d, $y)){
				$msg_erro['msg'] = "Data final inválida";
			}else{
				$data_final_formatada = "$y-$m-$d 23:59:59";
			}

			$cond .= " AND tbl_hd_chamado.data BETWEEN '$data_inicial_formatada' and '$data_final_formatada' ";

		}

		if(!empty($cliente)){
			$cond .= " AND tbl_hd_chamado_extra.nome ilike '$cliente%' ";
		}

		if($login_fabrica == 151){
            if($centro_distribuicao != 'mk_vazio') {
            	$campo_parametros_adicionais = ",tbl_produto.parametros_adicionais::json->>'centro_distribuicao' AS centro_distribuicao ";
            	$join_parametros_adicionais = "JOIN tbl_produto ON tbl_produto.fabrica_i = tbl_hd_chamado.fabrica";
                $cond .= " AND tbl_produto.parametros_adicionais::json->>'centro_distribuicao' = '$centro_distribuicao'";
            }
        }

		if(!empty($cpf)){
			$cpf = str_replace("-", "", $cpf);
			$cpf = str_replace(".", "", $cpf);
			$cpf = str_replace("/", "", $cpf);

			$cond .= " AND tbl_hd_chamado_extra.cpf = '$cpf' ";
		}

	}

	if(count($msg_erro['msg']) == 0){

		$sql = "SELECT  DISTINCT
						tbl_hd_chamado_extra.hd_chamado,
						tbl_hd_chamado_extra.nome,
						tbl_hd_chamado_extra.endereco,
						tbl_hd_chamado_extra.numero,
						tbl_hd_chamado_extra.complemento,
						tbl_hd_chamado_extra.cep,
						tbl_hd_chamado_extra.bairro,
						tbl_hd_chamado_extra.fone,
						tbl_hd_chamado_extra.celular,
						tbl_hd_chamado_extra.email,
						tbl_hd_chamado_extra.cpf,
						tbl_cidade.nome AS cidade,
						tbl_cidade.estado
						$campo_parametros_adicionais 
					FROM tbl_hd_chamado
					JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
					JOIN tbl_cidade ON tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
					$join_parametros_adicionais
					WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
					AND tbl_hd_chamado.titulo <> 'Help-Desk Posto'
					$cond";

		//die(nl2br($sql));
		$resSubmit = pg_query($con,$sql);		
	}

	if ($_POST["gerar_excel"] || $_POST["gerar_csv"]) {
		if (pg_num_rows($resSubmit) > 0) {

            $data = date("d-m-Y-H:i");

            if ($_POST["gerar_csv"]) {
                $fileName = "relatorio_sigep-{$login_fabrica}-{$data}.csv";
                $thead = "PROTOCOLO;FIXO;CPF;NOME;E-MAIL;A/C;CONTATO;CEP;ENDERECO;NUMERO;COMPLEMENTO;BAIRRO;CIDADE;ESTADO;TELEFONE;CELULAR;FAX";
                if($login_fabrica == 151){
                	$thead .= ";Centro Distribuição";
                }
                $thead .= "\r\n";
            } else {
                $fileName = "relatorio_sigep-{$login_fabrica}-{$data}.txt";
                $thead = "1SIGEP DESTINATARIO NACIONAL\n";
            }

            $file = fopen("/tmp/{$fileName}", "w");
            fwrite($file, $thead);

			for($j = 0; $j < pg_num_rows($resSubmit); $j++){

				$hd_chamado 	= pg_fetch_result($resSubmit, $j, 'hd_chamado');
				$cpf 			= pg_fetch_result($resSubmit, $j, 'cpf');
				$cpf 			= preg_replace('/\D/','',$cpf);
				$nome 			= pg_fetch_result($resSubmit, $j, 'nome');
				$email 			= pg_fetch_result($resSubmit, $j, 'email');
				$cep 			= pg_fetch_result($resSubmit, $j, 'cep');
				$cep			= preg_replace('/\D/','',$cep);
				$endereco 		= pg_fetch_result($resSubmit, $j, 'endereco');
				$numero 		= pg_fetch_result($resSubmit, $j, 'numero');
				$complemento 	= pg_fetch_result($resSubmit, $j, 'complemento');
				$bairro 		= pg_fetch_result($resSubmit, $j, 'bairro');
				$fone 			= pg_fetch_result($resSubmit, $j, 'fone');
				$fone			= preg_replace('/\D/','',$fone);
				$celular 		= pg_fetch_result($resSubmit, $j, 'celular');
				$celular		= preg_replace('/\D/','',$celular);
				$cidade 		= pg_fetch_result($resSubmit, $j, 'cidade');
				$estado 		= pg_fetch_result($resSubmit, $j, 'estado');

				$numero      = str_replace("/", "", $numero);
				$bairro      = uper_acentos($bairro);
				$endereco    = uper_acentos($endereco);
				$parametros_adicionais  = pg_fetch_result($resSubmit, $j, 'centro_distribuicao');


				if ($_POST["gerar_csv"]) {
					$nome        = $nome;
					$email       = $email;
					$endereco    = $endereco;
					$complemento = $complemento;
					$bairro      = $bairro;
					$cidade      = $cidade;
					$estado      = $estado;
					$numero      = $numero;

	            	$body = $hd_chamado.";2;".$cpf.";".$nome.";".$email.";".$aos_cuidados.";".$contato.";".$cep.";";
	            	$body .= $endereco.";".$numero.";".$complemento.";".$bairro.";".$cidade.";".$estado.";".$fone.";".$celular.";".$fax;
	            	if($login_fabrica == 151){						
						if($parametros_adicionais == "mk_nordeste"){
							$body .= ";MK Nordeste";	
						}else if($parametros_adicionais == "mk_sul") {
							$body .= ";MK Sul";	
						} else{
							$body .= ";";	
						}						
					}
					$body .= "\r\n";
		        } else {
		            $body = "2";
		            $body .= str_pad($cpf,14," ",STR_PAD_RIGHT);
		            $body .= str_pad($nome,50," ",STR_PAD_RIGHT);
		            $body .= str_pad($email,50," ",STR_PAD_RIGHT);
		            $body .= str_pad($aos_cuidados,50," ",STR_PAD_RIGHT);
		            $body .= str_pad($contato,50," ",STR_PAD_RIGHT);
		            $body .= str_pad($cep,8," ",STR_PAD_RIGHT);
		            $body .= str_pad($endereco,50," ",STR_PAD_RIGHT);
		            $body .= str_pad($numero,6," ",STR_PAD_RIGHT);
		            $body .= str_pad($complemento,30," ",STR_PAD_RIGHT);
		            $body .= str_pad($bairro,50," ",STR_PAD_RIGHT);
		            $body .= str_pad($cidade,50," ",STR_PAD_RIGHT);
		            $body .= str_pad($fone,18," ",STR_PAD_RIGHT);
		            $body .= str_pad($celular,12," ",STR_PAD_RIGHT);
		            $body .= str_pad($fax,12," ",STR_PAD_RIGHT);
		            $body .= "\n";
		        }

				fwrite($file, $body);

			}

            if ($_POST["gerar_excel"]) {
            	$tfoot = "9";
                $tfoot .= str_pad(pg_num_rows($resSubmit),6,"0",STR_PAD_LEFT);
                fwrite($file, $tfoot);
            }

			fclose($file);

			if (file_exists("/tmp/{$fileName}")) {
				system("mv /tmp/{$fileName} xls/{$fileName}");
				echo "xls/{$fileName}";
			}

		}

		exit;
	}
}

$layout_menu = "callcenter";
$title = "RELATÓRIO SIGEP";

include_once "cabecalho_new.php";

$plugins = array(
    "autocomplete",
    "shadowbox",
    "datepicker",
    "mask",
    "dataTable"
);

include("plugin_loader.php");

$inputs = array(
	"hd_chamado" => array(
		"span"     => 8,
		"label"    => "Nº Atendimento",
		"type"     => "input/text",
		"width"    => 5
	),
	"data_inicial" => array(
		"span"     => 4,
		"label"    => "Data Inicial",
		"type"     => "input/text",
		"width"    => 5,
		"required" => true
	),
	"data_final" => array(
		"span"     => 4,
		"label"    => "Data Final",
		"type"     => "input/text",
		"width"    => 5,
		"required" => true
	),
	"cliente" => array(
		"span"     => 4,
		"label"    => "Nome Cliente",
		"type"     => "input/text",
		"width"    => 12
	),
	"cpf" => array(
		"span"     => 4,
		"label"    => "CPF Cliente",
		"type"     => "input/text",
		"width"    => 12
	),
);

?>

<script>
	$(function(){

		Shadowbox.init();

		$.datepickerLoad(Array("data_final", "data_inicial"));

		$('#data_inicial').mask("99/99/9999");
		$('#data_final').mask("99/99/9999");

	});
</script>

	<div class="container">

		<?php
		/* Erro */
		if (count($msg_erro["msg"]) > 0) {
		?>
			<div class="alert alert-error">
				<h4><?php echo implode("<br />", $msg_erro["msg"])?></h4>
			</div>
		<?php } ?>

		<div class="container">
			<strong class="obrigatorio pull-right"> * Campos obrigatórios </strong>
		</div>

		<form name='frm_relatorio' method='POST' action='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>

			<div class='titulo_tabela'>Parâmetros de Pesquisa</div> <br/>

			<? echo montaForm($inputs,null);?>

			<?php if($login_fabrica == 151){ ?>         
	            <div class='row-fluid'>
	                <div class='span2'></div>
	                <div class='span4'>
	                    <div class='control-group'>
	                        <label class='control-label' for='centro_distribuicao'>Centro Distribuição</label>
	                        <div class='controls controls-row'>
	                            <div class='span12 input-append'>
	                                <select name="centro_distribuicao" id="centro_distribuicao">
	                                    <option value="mk_vazio" name="mk_vazio"<?php echo ($centro_distribuicao == "mk_vazio") ? "SELECTED" : ""; ?>>ESCOLHA</option>
	                                    <option value="mk_nordeste" name="mk_nordeste"<?php echo ($centro_distribuicao == "mk_nordeste") ? "SELECTED" : ""; ?>>MK Nordeste</option>
	                                    <option value="mk_sul" name="mk_sul"<?php echo ($centro_distribuicao == "mk_sul") ? "SELECTED" : ""; ?>>MK Sul</option>    
	                                </select>
	                            </div>                          
	                        </div>                      
	                    </div>
	                </div>
	            </div>                       
        	<?php } ?>

			<p>
				<br/>
				<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
				<input type='hidden' id="btn_click" name='btn_acao' value='' />
			</p>

			<br/>

		</form>

	</div>

</div>

<?php

	if($btn_acao == "submit"){

		if (pg_num_rows($resSubmit) > 0) {

			$count = pg_num_rows($resSubmit);

			?>
			<table align="center" id="resultado_atendimento" class='table table-striped table-bordered table-hover table-large' >
				<thead>
					<tr class='titulo_coluna' >
						<th>Protocolo</th>
						<th>Fixo</th>
						<th>CNPJ/CPF</th>
						<th>Nome</th>
						<th>Email</th>
						<th>Aos Cuidados</th>
						<th>Contato</th>
						<th>CEP</th>
						<th>Logradouro</th>
						<th>Número</th>
						<th>Complemento</th>
						<th>Bairro</th>
						<th>Cidade</th>
						<th>Estado</th>
						<th>Telefone</th>
						<th>Celular</th>
						<th>Fax</th>
						<?php 
							if($login_fabrica == 151){
								echo "<th>Centro Distribuição</th>";
							}
						?>
            		</tr>
                </thead>
				<tbody>
		<?php

				for($i = 0; $i < pg_num_rows($resSubmit); $i++){

					$hd_chamado 	= pg_fetch_result($resSubmit, $i, 'hd_chamado');
					$cpf 			= pg_fetch_result($resSubmit, $i, 'cpf');
					$nome 			= uper_acentos(pg_fetch_result($resSubmit, $i, 'nome'));
					$email 			= pg_fetch_result($resSubmit, $i, 'email');
					$cep 			= pg_fetch_result($resSubmit, $i, 'cep');
					$endereco 		= uper_acentos(pg_fetch_result($resSubmit, $i, 'endereco'));
					$numero 		= pg_fetch_result($resSubmit, $i, 'numero');
					$complemento 	= uper_acentos(pg_fetch_result($resSubmit, $i, 'complemento'));
					$bairro 		= uper_acentos(pg_fetch_result($resSubmit, $i, 'bairro'));
					$fone 			= pg_fetch_result($resSubmit, $i, 'fone');
					$celular 		= pg_fetch_result($resSubmit, $i, 'celular');
					$cidade 		= uper_acentos(pg_fetch_result($resSubmit, $i, 'cidade'));
					$estado 		= uper_acentos(pg_fetch_result($resSubmit, $i, 'estado'));
					$parametros_adicionais  = pg_fetch_result($resSubmit, $i, 'centro_distribuicao');

					echo "<tr>
							<td><button class='btn btn-link' onclick=\"window.open('callcenter_interativo_new.php?callcenter=$hd_chamado')\">$hd_chamado</button></td>
							<td class='tac'>2</td>
							<td>$cpf</td>
							<td>$nome</td>
							<td>$email</td>
							<td></td>
							<td></td>
							<td>$cep</td>
							<td>$endereco</td>
							<td class='tac'>$numero</td>
							<td>$complemento</td>
							<td>$bairro</td>
							<td>$cidade</td>
							<td>$estado</td>
							<td>$fone</td>
							<td>$celular</td>
							<td></td>";

					if($login_fabrica == 151){						
						if($parametros_adicionais == "mk_nordeste"){
							echo "<td>MK Nordeste</td>";
						}else if($parametros_adicionais == "mk_sul") {
							echo "<td>MK Sul</td>";	
						} else{
							echo "<td>&nbsp;</td>";	
						}						
					}

					echo "</tr>";
				}

				echo "</table>";

				if ($count > 50) {
				?>
					<script>
						$.dataTableLoad({ table: "#resultado_atendimento" });
					</script>
				<?php
				}
			?>
				<br />

			<?php
				$jsonPOST       = excelPostToJson($_POST);
				$jsonPOSTcsv    = csvPostToJson($_POST);
			?>

			<div id='gerar_excel' class="btn_excel">
				<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
				<span class="txt">Gerar Arquivo SIGEP</span>
			</div>
            <br />
            <div id='gerar_csv' class="btn_excel">
                <input type="hidden" id="jsonPOSTcsv" value='<?=$jsonPOSTcsv?>' />
                <span class="txt"><img src='imagens/excel.png' />Gerar Planilha</span>
            </div>

		<?php
		}else{
			echo '
			<div class="container">
				<div class="alert">
				    <h4>Nenhum resultado encontrado</h4>
				</div>
			</div>';
		}

	}

/* Rodapé */
include 'rodape.php';
?>
