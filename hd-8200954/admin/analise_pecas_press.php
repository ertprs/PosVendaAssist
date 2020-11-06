<?php
	
	include "dbconfig.php";
	include "includes/dbconnect-inc.php";

	$admin_privilegios = "cadastros";
	include "autentica_admin.php";

	include "funcoes.php";

	/* S3 Upload */
	include_once S3CLASS;
    $s3 = new AmazonTC("analise_peca", $login_fabrica);

	/* Consulta */

	function form_data($data, $format = ""){

		if(strlen($data) == 0){
			return "";
		}

		if($format == "-"){

			list($a, $m, $d) = explode("-", $data);
			$data = $d."/".$m."/".$a;

		}else{

			list($d, $m, $a) = explode("/", $data);
			$data = $a."-".$m."-".$d;

		}

		return $data;

	}

	function form_data_hora($data){

		list($data, $hora) = explode(" ", $data);

		list($a, $m, $d) = explode("-", $data);

		list($hms, $ml) = explode(".", $hora);

		list($horas, $minutos, $segundos) = explode(":", $hms);

		return $d."/".$m."/".$a." às ".$horas.":".$minutos."hs";

	}

	function get_data_analise_peca($analise_peca, $opt = ""){

		global $con, $login_fabrica;

		$sql = "SELECT DATE(inicio_analise) AS data FROM tbl_analise_peca WHERE analise_peca = {$analise_peca} AND fabrica = {$login_fabrica}";
		$res = pg_query($con, $sql);

		$data = pg_fetch_result($res, 0, "data");
		list($ano, $mes, $dia) = explode("-", $data);

		if($opt == "mes"){
			return $mes;
		}

		if($opt == "ano"){
			return $ano;
		}

		return false;

	}

	function get_nome_admin($admin = ""){

		global $con, $login_fabrica;

		if(strlen($admin) == 0){
			return "";
		}else{

			$sql = "SELECT login, nome_completo FROM tbl_admin WHERE admin = {$admin} AND fabrica = {$login_fabrica}";
			$res = pg_query($con, $sql);

			$login = pg_fetch_result($res, 0, "login");
			$nome_completo = pg_fetch_result($res, 0, "nome_completo");

			return $nome_completo." (".$login.")";


		}

	}

	if(isset($_GET["analise_peca"])){

		$analise_peca = trim($_GET["analise_peca"]);

		$sql = "SELECT 
					tbl_analise_peca.posto,
					tbl_analise_peca.data_abertura,
					tbl_analise_peca.nota_fiscal,
					tbl_analise_peca.data_nf,
					tbl_origem_recebimento.descricao AS origem_recebimento,
					tbl_tecnico.nome AS tecnico,
					tbl_analise_peca.inicio_analise,
					tbl_analise_peca.termino_analise,
					tbl_analise_peca.termino_final,
					tbl_status_analise_peca.descricao AS status_analise_peca,
					tbl_analise_peca.data_entrega,
					tbl_analise_peca.autorizacao,
					tbl_analise_peca.responsavel_recebimento,
					tbl_analise_peca.nf_saida,
					tbl_analise_peca.data_nf_saida,
					tbl_analise_peca.volume,
					tbl_analise_peca.admin_inicio,
					tbl_analise_peca.admin_termino,
					tbl_analise_peca.motivo,
					tbl_analise_peca.observacao 
				FROM tbl_analise_peca 
				INNER JOIN tbl_origem_recebimento ON tbl_analise_peca.origem_recebimento = tbl_origem_recebimento.origem_recebimento 
				INNER JOIN tbl_tecnico ON tbl_tecnico.tecnico = tbl_analise_peca.tecnico 
				INNER JOIn tbl_status_analise_peca ON tbl_status_analise_peca.status_analise_peca = tbl_analise_peca.status_analise_peca 
				WHERE 
					tbl_analise_peca.analise_peca = {$analise_peca} 
					AND tbl_analise_peca.fabrica = {$login_fabrica}";
		$res = pg_query($con, $sql);

		if(pg_num_rows($res) > 0){

			$posto                   = pg_fetch_result($res, 0, "posto");
			$data_abertura           = pg_fetch_result($res, 0, "data_abertura");
			$nota_fiscal             = pg_fetch_result($res, 0, "nota_fiscal");
			$data_nota_fiscal        = pg_fetch_result($res, 0, "data_nf");
			$origem_recebimento      = pg_fetch_result($res, 0, "origem_recebimento");
			$tecnico                 = pg_fetch_result($res, 0, "tecnico");
			$inicio_analise          = pg_fetch_result($res, 0, "inicio_analise");
			$termino_analise         = pg_fetch_result($res, 0, "termino_analise");
			$termino_final           = pg_fetch_result($res, 0, "termino_final");
			$posicao_peca            = pg_fetch_result($res, 0, "status_analise_peca");
			$data_entrega_expedicao  = pg_fetch_result($res, 0, "data_entrega");
			$autorizado              = pg_fetch_result($res, 0, "autorizacao");
			$responsavel_recebimento = pg_fetch_result($res, 0, "responsavel_recebimento");
			$nota_fiscal_saida       = pg_fetch_result($res, 0, "nf_saida");
			$data_nf_saida       	 = pg_fetch_result($res, 0, "data_nf_saida");
			$volume                  = pg_fetch_result($res, 0, "volume");
			$observacao              = pg_fetch_result($res, 0, "observacao");
			$motivo              	 = pg_fetch_result($res, 0, "motivo");

			$admin_inicio            = get_nome_admin(pg_fetch_result($res, 0, "admin_inicio"));
			$admin_termino           = get_nome_admin(pg_fetch_result($res, 0, "admin_termino"));

			if(strlen($posto) > 0){

				$sql = "SELECT 
								tbl_posto_fabrica.codigo_posto, 
								tbl_posto.nome 
							FROM tbl_posto 
							INNER JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto 
							WHERE 
								tbl_posto_fabrica.posto = {$posto} 
								AND tbl_posto_fabrica.fabrica = {$login_fabrica}";
				$res = pg_query($con, $sql);

				$codigo_posto = pg_fetch_result($res, 0, "codigo_posto");
				$descricao_posto = pg_fetch_result($res, 0, "nome");

			}

			$sql = "SELECT 
						tbl_analise_peca_item.peca,
						tbl_peca.referencia,
						tbl_peca.descricao,
						tbl_peca.produto_acabado,
						tbl_analise_peca_item.numero_serie,
						tbl_analise_peca_item.lote,
						tbl_analise_peca_item.qtde,
						tbl_analise_peca_item.laudo_defeito_constatado,
						tbl_analise_peca_item.laudo_analise,
						tbl_analise_peca_item.procede_reclamacao,
						tbl_analise_peca_item.garantia,
						tbl_analise_peca_item.laudo_apos_reparo,
						tbl_analise_peca_item.enviar_peca_nova,
						tbl_analise_peca_item.sucatear_peca,
						tbl_analise_peca_item.baixa_no_estoque,
						tbl_analise_peca_item.lancar_no_clain,
						tbl_analise_peca_item.gasto_nao_justifica_devolucao 
					FROM tbl_analise_peca_item 
					INNER JOIN tbl_peca ON tbl_analise_peca_item.peca = tbl_peca.peca AND tbl_peca.fabrica = {$login_fabrica} 
					WHERE tbl_analise_peca_item.analise_peca = {$analise_peca}";
			$res = pg_query($con, $sql);

			$produtos_pecas = array();

			for($i = 0; $i < pg_num_rows($res); $i++){

				$peca                          = pg_fetch_result($res, $i, "peca");
				$referencia                    = pg_fetch_result($res, $i, "referencia");
				$descricao                     = pg_fetch_result($res, $i, "descricao");
				$categoria                     = (pg_fetch_result($res, $i, "produto_acabado") == "t") ? "Produto" : "Peca";
				$numero_serie                  = pg_fetch_result($res, $i, "numero_serie");
				$lote                          = pg_fetch_result($res, $i, "lote");
				$qtde                          = pg_fetch_result($res, $i, "qtde");
				$laudo_defeito_constatado      = pg_fetch_result($res, $i, "laudo_defeito_constatado");
				$laudo_analise                 = pg_fetch_result($res, $i, "laudo_analise");
				$procede_reclamacao            = pg_fetch_result($res, $i, "procede_reclamacao");
				$garantia                      = pg_fetch_result($res, $i, "garantia");
				$laudo_apos_reparo             = pg_fetch_result($res, $i, "laudo_apos_reparo");
				$enviar_peca_nova              = pg_fetch_result($res, $i, "enviar_peca_nova");
				$sucatear_peca                 = pg_fetch_result($res, $i, "sucatear_peca");
				$baixa_no_estoque              = pg_fetch_result($res, $i, "baixa_no_estoque");
				$lancar_no_clain               = pg_fetch_result($res, $i, "lancar_no_clain");
				$gasto_nao_justifica_devolucao = pg_fetch_result($res, $i, "gasto_nao_justifica_devolucao");

				$produtos_pecas[] = array(
					"categoria"                            => $categoria,
					"referencia"                           => $referencia,
					"descricao"                            => $descricao,
					"numero_serie"                         => $numero_serie,
					"numero_lote"                          => $lote,
					"quantidade"                           => $qtde,
					"defeito_constatado"                   => $laudo_defeito_constatado,
					"resultado_analise"                    => $laudo_analise,
					"procede_reclamacao"                   => (($procede_reclamacao == "t") 			? "Sim" : "Não"),
					"garantia"                             => (($garantia == "t") 						? "Sim" : "Não"),
					"laudo_apos_reparo"                    => (($laudo_apos_reparo == "t") 				? "Aprovado" : "Reprovado"),
					"enviar_peca_nova"                     => (($enviar_peca_nova == "t") 				? "Sim" : "Não"),
					"sucatear_peca"                        => (($sucatear_peca == "t") 					? "Sim" : "Não"),
					"baixa_no_estoque"                     => (($baixa_no_estoque == "t") 				? "Sim" : "Não"),
					"lancar_no_clain"                      => (($lancar_no_clain == "t") 				? "Sim" : "Não"),
					"gasto_nao_justificavel_com_devolucao" => (($gasto_nao_justifica_devolucao == "t") 	? "Sim" : "Não")
				);

			}

			$parcial = (strlen($termino_final) == 0) ? "(Parcial)" : "";

			$data_abertura          = form_data($data_abertura, "-");
			$data_nota_fiscal       = form_data($data_nota_fiscal, "-");
			$data_entrega           = form_data($data_entrega, "-");
			$inicio_analise         = (strlen($inicio_analise) > 0) ? form_data_hora($inicio_analise) : "Análise de Peças ainda não iniciada";
			$termino_analise        = (strlen($termino_analise) > 0) ? form_data_hora($termino_analise) : "";
			$data_nf_saida          = form_data($data_nf_saida, "-");
			$data_entrega_expedicao = form_data($data_entrega_expedicao, "-");

			$data_termino_analise   = (strlen($termino_final) > 0) ? form_data_hora($termino_final) : $termino_analise;

		}else{

			$msg_erro = "<div class='alert alert-danger tac'><h4>Nenhuma Análise de Peças Localizada com esse código - {$analise_peca}</h4></div>";

		}

	}

	/* Fim Consulta */

	$layout_menu = "cadastro";

	$title = "ANÁLISE DE PEÇAS - CONSULTA";

	include "cabecalho_new.php";

	$plugins = array();

	include("plugin_loader.php");

	if(strlen($msg_erro) == 0){
?>

		<style>

			.logo-print{
				display: none !important;
			}

			@media print
			{
				*{
					font-family: arial !important;
					font-size: 12px !important;
				}
			    .no-print, .anexos_analise{
			        display: none !important;
			    }
			    #loading, #helpdesk, #tc_menu{
			    	display: none !important;
			    }
			    button{
			    	display: none;
			    }
			    .logo-print{
			    	display: block !important;
			    	padding-bottom: 10px !important;
			    }
			    .titulo_coluna{
			    	font-weight: bold !important;
			    }
			    .titulo_tabela{
			    	font-weight: bold !important;
			    	text-align: center !important;
			    	font-size: 18px !important;
			    	padding: 5px;
			    	border-bottom: 1px solid #ccc !important;
			    	margin-bottom: 10px !important;
			    }
			}

		</style>

		<div class="container">

			<div class="logo-print"><img src=<?php echo $imagensLogo[$login_fabrica][0]; ?> /></div>
			
			<?php

			if(strlen($data_termino_analise) > 0){
				echo "<div class='alert alert-error'><h4>Esta Análise está Finalizada {$parcial} - {$data_termino_analise}</h4> </div>";
			}

			?>

			<table class="table table-bordered" style="width: 100%;">

				<tr>
					<td class="titulo_tabela tac" colspan="4">Dados da Análise de Peças</td>
				</tr>

				<tr>

					<td class="titulo_coluna" width="20%">Código de Análise</td>
					<td><strong class="text-error" width="30%"><?=$analise_peca?></strong></td>
					<td class="titulo_coluna" width="20%">Posto Autorizado</td>
					<td width="30%"><?=$codigo_posto?> - <?=$descricao_posto?></td>

				</tr>
				
				<tr>

					<td class="titulo_coluna">Data de Abertura</td>
					<td><?=$data_abertura?></td>
					<td class="titulo_coluna">Nota Fiscal</td>
					<td><?=$nota_fiscal?></td>

				</tr>

				<tr>

					
					<td class="titulo_coluna">Data da Nota Fiscal</td>
					<td><?=$data_nota_fiscal?></td>
					<td class="titulo_coluna">Origem de Recebimento</td>
					<td><?=$origem_recebimento?></td>

				</tr>

				<tr>

					<td class="titulo_coluna">Autorizado por</td>
					<td><?=$autorizado?></td>
					<td class="titulo_coluna">Técnico</td>
					<td><?=$tecnico?></td>
					

				</tr>

				<tr>

					<td class="titulo_coluna">Início Análise</td>
					<td colspan="3"><strong class="text-info"><?=$inicio_analise?></strong></td>

				</tr>

			</table>

		</div>

		<br />

		<div class="container">

			<table class="table table-bordered" style="width: 100%;">

				<tr>
					<td class="titulo_tabela tac" colspan="4">Histórico de Interações</td>
				</tr>

				<tr>

					<td class="titulo_coluna" width="20%">Início da Análise</td>
					<td width="30%"><?=$admin_inicio?></td>
					<td class="titulo_coluna" width="20%">Fim da Análise</td>
					<td width="30%"><?=$admin_termino?></td>

				</tr>

			</table>

		</div>

		<br />

		<?php

		$cont = 1;

		foreach ($produtos_pecas as $key => $value) {

			$arr_categoria                            = $value["categoria"];
			$arr_referencia                           = $value["referencia"];
			$arr_descricao                            = $value["descricao"];
			$arr_numero_serie                         = $value["numero_serie"];
			$arr_numero_lote                          = $value["numero_lote"];
			$arr_quantidade                           = $value["quantidade"];
			$arr_defeito_constatado                   = $value["defeito_constatado"];
			$arr_resultado_analise                    = $value["resultado_analise"];
			$arr_procede_reclamacao                   = $value["procede_reclamacao"];
			$arr_garantia                             = $value["garantia"];
			$arr_laudo_apos_reparo                    = $value["laudo_apos_reparo"];
			$arr_enviar_peca_nova                     = $value["enviar_peca_nova"];
			$arr_sucatear_peca                        = $value["sucatear_peca"];
			$arr_baixa_no_estoque                     = $value["baixa_no_estoque"];
			$arr_lancar_no_clain                      = $value["lancar_no_clain"];
			$arr_gasto_nao_justificavel_com_devolucao = $value["gasto_nao_justificavel_com_devolucao"];
			$arr_excluido							  = $value["excluido"];

			?>

			<table class="table table-bordered" style="width: 100%;">

				<tr>
					<td class="titulo_tabela tac" colspan="4">Peça Analisada #<?php echo $cont++; ?></td>
				</tr>

				<tr>

					<td class="titulo_coluna">Categoria</td>
					<td class="titulo_coluna">Referência Peça</td>
					<td class="titulo_coluna" colspan="2">Descrição Peça</td>

				</tr>

				<tr>

					<td><?=$arr_categoria?></td>
					<td><?=$arr_referencia?></td>
					<td colspan="2"><?=$arr_descricao?></td>

				</tr>

				<tr>

					<td class="titulo_coluna" colspan="2">Número de Série</td>
					<td class="titulo_coluna">Número do Lote</td>
					<td class="titulo_coluna">Quantidade</td>

				</tr>

				<tr>

					<td  colspan="2"><?=$arr_numero_serie?></td>
					<td ><?=$arr_numero_lote?></td>
					<td ><?=$arr_quantidade?></td>

				</tr>

				<tr>

					<td class="titulo_coluna" colspan="2">Defeito Constatado</td>
					<td class="titulo_coluna" colspan="2">Resultado da Análise</td>

				</tr>


				<tr>

					<td colspan="2"><?=$arr_defeito_constatado?></td>
					<td colspan="2"><?=$arr_resultado_analise?></td>

				</tr>

				<tr>

					<td class="titulo_coluna tac" width="25%">Procede Reclamação</td>
					<td class="titulo_coluna tac" width="25%">Garantia</td>
					<td class="titulo_coluna tac" width="25%">Laudo após reparo</td>
					<td class="titulo_coluna tac" width="25%">Enviar peça nova</td>

				</tr>

				<tr>

					<td class="tac"><?=$arr_procede_reclamacao?></td>
					<td class="tac"><?=$arr_garantia?></td>
					<td class="tac"><?=$arr_laudo_apos_reparo?></td>
					<td class="tac"><?=$arr_enviar_peca_nova?></td>

				</tr>

				<tr>

					<td class="titulo_coluna tac">Sucatear Peça</td>
					<td class="titulo_coluna tac">Baixar no Estoque</td>
					<td class="titulo_coluna tac">Lançar no Claim</td>
					<td class="titulo_coluna tac">Gasto Não Justificado c/ Devolução</td>

				</tr>

				<tr>

					<td class="tac"><?=$arr_sucatear_peca?></td>
					<td class="tac"><?=$arr_baixa_no_estoque?></td>
					<td class="tac"><?=$arr_lancar_no_clain?></td>
					<td class="tac"><?=$arr_gasto_nao_justificavel_com_devolucao?></td>

				</tr>
				
			</table>

			<br />

			<?php

		}

		?>

		<div class="container">

			<table class="table table-bordered" style="width: 100%;">

				<tr>

					<td class="titulo_tabela tac" colspan="4">Informações do Saída (Expedição)</td>

				</tr>

				<tr>

					<td class="titulo_coluna"width="25%">Posição da Peça</td>
					<td width="25%"><?=$posicao_peca?></td>
					<td class="titulo_coluna" width="25%">Entrega à Expediçao / Estoque</td>
					<td width="25%"><?=$data_entrega_expedicao?></td>

				</tr>

				<tr>

					<td class="titulo_coluna">Responsável Recebimento</td>
					<td><?=$responsavel_recebimento?></td>
					<td class="titulo_coluna">Nota Fiscal de Saída</td>
					<td><?=$nota_fiscal_saida?></td>

				</tr>

				<tr>

					<td class="titulo_coluna">Data da NF de Saída</td>
					<td><?=$data_nf_saida?></td>
					<td class="titulo_coluna">Volume</td>
					<td><?=$volume?></td>

				</tr>
					
			</table>	

		</div>

		<?php if(strlen(trim($observacao)) > 0){ ?>

		<br />

		<table class="table table-bordered" style="width: 100%;">

			<tr>

				<td class="titulo_tabela tac">Observação</td>

			</tr>

			<tr>

				<td><?=$observacao?></td>

			</tr>

		</table>

		<?php } ?>

		<br />

		<table class="table table-bordered anexos_analise" style="width: 100%;">

			<tr>

				<td class="titulo_tabela tac">Anexo(s)</td>

			</tr>

			<tr>

				<td class="tac">
					
					<?php

					$ano = get_data_analise_peca($analise_peca, "ano");
					$mes = get_data_analise_peca($analise_peca, "mes");

					$anexos_analise = $s3->getObjectList("{$login_fabrica}_{$analise_peca}_", false, $ano, $mes);

		            if (count($anexos_analise)) {
		                foreach ($anexos_analise as $key => $value) {

							$anexo_link   = $s3->getLink(basename($anexos_analise[$key]), false, $ano, $mes);

							$ext = strtolower(preg_replace("/.+\./", "", basename($anexos_analise[$key])));
					 		if ($ext == "pdf") {
					 			$anexo_imagem = "imagens/pdf_icone.png";
					 		} else if (in_array($ext, array("doc", "docx"))) {
					 			$anexo_imagem = "imagens/docx_icone.png";
					 		} else {
								$anexo_imagem = $s3->getLink("thumb_".basename($anexos_analise[$key]), false, $ano, $mes);
					 		}

							echo "<a href='{$anexo_link}' target='_blank' style='margin: 10px;'>";
		                    	echo "<img src='{$anexo_imagem}' class='anexo_thumb' style='width: 100px; height: 90px;' />";
		                    echo "</a>";

		                    echo "<script>setupZoom();</script>";

		                }
		            } else {
		                echo "Sem Arquivo(s) anexado(s) <br />";
		            }

					?>

				</td>

			</tr>

		</table>

		<?php if(strlen(trim($motivo)) > 0){ ?>

		<br />

		<table class="table table-bordered" style="width: 100%;">

			<tr>

				<td class="titulo_tabela tac" colspan="3">Histórico de Reabertura</td>

			</tr>

			<tr>

				<td class="titulo_coluna tac">Data</td>
				<td class="titulo_coluna tac">Motivo</td>
				<td class="titulo_coluna tac">Admin</td>

			</tr>


			<?php

			$motivo = json_decode($motivo, true);

			foreach ($motivo as $key => $value) {

				$admin            = $value["admin"];
				$descricao_motivo = utf8_decode($value["motivo"]);
				$data             = $value["data"];

				$sql_admin = "SELECT nome_completo, email FROM tbl_admin WHERE admin = {$admin} AND fabrica = {$login_fabrica}";
				$res_admin = pg_query($con, $sql_admin);

				$nome 	= pg_fetch_result($res_admin, 0, "nome_completo");
				$email 	= pg_fetch_result($res_admin, 0, "email");

				echo "
					<tr>	
						<td class='tac' nowrap>{$data}</td>
						<td>{$descricao_motivo}</td>
						<td>{$nome} ($email)</td>
					</tr>";
			}

			?>

		</table>

		<?php } ?>

		<br /> <br />

		<p class="tac">
			<button type="button" class="btn btn-large" onclick="window.print();">Imprimir</button>
		</p>

		<br /> <br />

<?php

	}else{
		echo $msg_erro;
	}

	include "rodape.php";

?>
