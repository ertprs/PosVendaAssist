<?php
	/**
	 *	@description Relatorio Pesquisa de Satisfação - HD 408341
	 *  @author Brayan L. Rastelli.
	 **/
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include "autentica_admin.php";
?>

<?
	if ( isset($_POST['gerar']) ) {
		$cond = array();
		if($_POST["data_inicial"]) $data_inicial = trim ($_POST["data_inicial"]);
		if($_POST["data_final"]) $data_final = trim($_POST["data_final"]);
		if( empty($data_inicial) OR empty($data_final) ) {
			$msg_erro["msg"][]    = "Data Inválida";
			$msg_erro["campos"][] = "data";
		}
			
		if(strlen($msg_erro)==0) {
			list($di, $mi, $yi) = explode("/", $data_inicial);
			list($df, $mf, $yf) = explode("/", $data_final);
			if(!checkdate($mi,$di,$yi) || !checkdate($mf,$df,$yf)) {
				$msg_erro["msg"][]    = "Data Inválida";
				$msg_erro["campos"][] = "data";
			}
		}
		if(strlen($msg_erro)==0) {
			$aux_data_inicial = "$yi-$mi-$di";
			$aux_data_final = "$yf-$mf-$df";
		
			if(strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
				$msg_erro["msg"][]    = "Data Inválida";
				$msg_erro["campos"][] = "data";
			}
			if(strlen($msg_erro)==0) {
				if (strtotime("$aux_data_inicial + 1 month" ) < strtotime($aux_data_final)) {
					$msg_erro["msg"][]    = "O intervalo entre as datas não pode ser maior que um mês.";
					$msg_erro["campos"][] = "data";
				}	
			}
			if(empty($msg_erro)) {
				$cond[] .= " AND o.data_abertura BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' ";
			}
		}

		$referencia = trim ($_POST['produto_referencia']);
		$xatendente 	= (int) trim ($_POST['xatendente']);
		if ( !empty($referencia) ) {
			
			$sql = "SELECT produto
					FROM tbl_produto
					JOIN tbl_linha USING(linha)
					WHERE referencia = '$referencia'";
			$res = pg_query($con,$sql);
			if (pg_num_rows($res)) {
				$produto = pg_result($res,0,0);
				$cond[] = " AND o.produto = $produto ";
			} else {
				$msg_erro["msg"][]    = "Produto não encontrado";
				$msg_erro["campos"][] = "produto";
			}
		}
		if ( !empty($xatendente) ) {
			$cond[] = ' AND r.admin = ' . $xatendente;
		}
		if ( !empty($_POST['estado']) ) {
		
			$cond[] = " AND o.consumidor_estado = '".$_POST['estado']."'";
		
		}
		$pesquisa = (int) $_POST['pesquisa'];
		if ( !empty ($pesquisa) ) {
		
			$cond[] = " AND r.pesquisa = $pesquisa ";	
		
		}
		else {
			$msg_erro["msg"][]    = "Escolha uma pesquisa";
			$msg_erro["campos"][] = "pesquisa";
		}
	}
	$layout_menu = "callcenter";
	$title = "RELATÓRIO DE PESQUISA DE SATISFAÇÃO POR OS";
	include "cabecalho_new.php";
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
	$().ready(function(){
		$(".table-more").hide();
		$(".expand").click(function(){
			var id = $(this).attr('id');
			$(".admin_"+id).toggle();
		});
		Shadowbox.init();
		$.datepickerLoad(Array("data_final", "data_inicial"));
		$.autocompleteLoad(Array("produto"));
		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});
	});
	function retorna_produto (retorno) {
		$("#produto_referencia").val(retorno.referencia);
		$("#produto_descricao").val(retorno.descricao);
	}
	
</script>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
		<h4><?=$msg_erro["msg"][0]?></h4>
    </div>
<?php
}
?>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form action="<?=$_SERVER['PHP_SELF'];?>" method="POST" name="frm" class='form-search form-inline tc_formulario'>
	<div class="titulo_tabela">Parâmetros de Pesquisa</div>
	<br />
		<div class='row-fluid'>
			<div class='span2'></div>
				<div class='span4'>
					<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
						<label for="data_inicial">Data Inicial</label>
						<div class='controls controls-row'>
							<div class='span4'>
								<h5 class='asteristico'>*</h5>
									<input type="text" name="data_inicial" class="span12" id="data_inicial"  value="<?=isset($_POST['data_inicial'])?$_POST['data_inicial'] : '' ?>" />
							</div>
						</div>
					</div>
				</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label for="data_final">Data Final</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<h5 class='asteristico'>*</h5>
								<input type="text" name="data_final" id="data_final" class="span12" value="<?=isset($_POST['data_final'])?$_POST['data_final'] : ''?>"/>
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='produto_referencia'>Referência Produto</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" name="produto_referencia" id="produto_referencia" value="<?php echo $produto_referencia;?>" maxlength="20" class='span12'>
							<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
							<input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='produto_descricao'>Descrição Produto</label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<input type="text" name="produto_descricao" id="produto_descricao" value="<?php echo $produto_descricao;?>" size="30" maxlength="50" class='frm'>
							<span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>		
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("linha", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='linha'>Atendente</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<select name="xatendente">
								 <option value=''></option>
								<?	$sql = "SELECT admin, login
											from tbl_admin
											where fabrica = $login_fabrica
											and ativo is true
											and (privilegios like '%call_center%' or privilegios like '*') order by login";
									$res = pg_exec($con,$sql);
									if(pg_numrows($res)>0){
										for($i=0;pg_numrows($res)>$i;$i++){
											$atendente = pg_result($res,$i,admin);
											$atendente_nome = pg_result($res,$i,login);
											echo "<option value='$atendente'";
												if($xatendente == $atendente) {
														echo "selected";
													}
											echo ">$atendente_nome</option>";
										}
									}
								?>
							</select>
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("familia", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='familia'>Estado</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<?php
							  $array_estado = array("AC"=>"AC - Acre","AL"=>"AL - Alagoas","AM"=>"AM - Amazonas",
							    					"AP"=>"AP - Amapá", "BA"=>"BA - Bahia", "CE"=>"CE - Ceará","DF"=>"DF - Distrito Federal",
								 					"ES"=>"ES - Espírito Santo", "GO"=>"GO - Goiás","MA"=>"MA - Maranhão","MG"=>"MG - Minas Gerais",
								    				"MS"=>"MS - Mato Grosso do Sul","MT"=>"MT - Mato Grosso", "PA"=>"PA - Pará","PB"=>"PB - Paraíba",
									  				"PE"=>"PE - Pernambuco","PI"=>"PI - Piauí","PR"=>"PR - Paraná","RJ"=>"RJ - Rio de Janeiro",
									    			"RN"=>"RN - Rio Grande do Norte","RO"=>"RO - Rondônia","RR"=>"RR - Roraima",
												 	"RS"=>"RS - Rio Grande do Sul", "SC"=>"SC - Santa Catarina","SE"=>"SE - Sergipe",
										    		"SP"=>"SP - São Paulo","TO"=>"TO - Tocantins");
							?>
							<select name="estado" class="frm" id="estado">
								<option value=""></option>
								<?php
							    	foreach ($array_estado as $k => $v) {
									    echo '<option value="'.$k.'"'.($estado == $k ? ' selected="selected"' : '').'>'.$v."</option>\n";
									}
								?>
							</select>
						</div>
						<div class='span2'></div>
					</div>
				</div>
			</div>
		</div>	
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span8'>
				<div class='control-group <?=(in_array("pesquisa", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='linha'>Pesquisa</label>
					<div class='controls controls-row'>
						<div class='span12'>
							<h5 class='asteristico'>*</h5>
							<select id="pesquisa" name="pesquisa" class="frm span12">
								<option value=""></option>
								<?php 
									$sql = "SELECT pesquisa,descricao
											FROM tbl_pesquisa
											WHERE fabrica = $login_fabrica";
									$res = pg_query($con,$sql);
									for ( $i = 0; $i < pg_num_rows($res); $i++ ) {
									
										$xpesquisa = pg_result($res,$i,'pesquisa');
										$xselected = $_POST['pesquisa'] == $xpesquisa ? 'selected' : '';
										$xdescricao= pg_result($res,$i,'descricao');
										echo '<option value="'.$xpesquisa.'" '.$xselected.'>'.$xdescricao.'</option>';
									
									}
								?>
							</select>
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>	
		<input class="tac btn" type="submit" name="gerar" value="Consultar" />
		<br /><br />
	</form>

</div>

<?php 
	if ( isset($_POST['gerar']) && count($msg_erro) == 0) { // requisicao de relatorio
		
		$link_xls = "xls/relatorio_atendimento_orientacao_uso_$login_fabrica_" . date("d-m-y") . '.xls';
		if (file_exists($link_xls))
			exec("rm -f $link_xls");
		if ( is_writable("xls/") ) 
			$file = fopen($link_xls, 'a+');
		else
			echo 'Sem Permissão de escrita';
		$cond = implode (" ", $cond);
		ob_start();

		$sqlx = "SELECT
					x.admin,
					x.nome_completo,
					x.posto,
					x.os,
					ARRAY_TO_JSON(ARRAY_AGG(DISTINCT(x.os, tri_resp.descricao, p.descricao))) as respostas
			   FROM (
					  SELECT DISTINCT
							 a.admin,
							 a.nome_completo,
							 o.posto,
							 o.os,
							 r.pergunta,
							 r.tipo_resposta_item
						FROM tbl_resposta r
						JOIN tbl_os o ON o.os = r.os
						JOIN tbl_admin a ON a.admin = r.admin AND a.fabrica = $login_fabrica
				   	   WHERE o.fabrica = $login_fabrica
				   	   $cond
					ORDER BY o.os
					) x
					JOIN tbl_pergunta p ON p.pergunta = x.pergunta AND p.fabrica = $login_fabrica
					JOIN tbl_tipo_resposta_item tri_resp ON tri_resp.tipo_resposta_item = x.tipo_resposta_item
					JOIN tbl_tipo_resposta tr ON tr.tipo_resposta = p.tipo_resposta AND tr.fabrica = $login_fabrica
					JOIN tbl_tipo_resposta_item tri ON tri.tipo_resposta = tr.tipo_resposta
				GROUP BY x.admin, x.nome_completo, x.posto, x.os;";

		$resx  = pg_query($con,$sqlx);
		$dadospPesquisa = pg_fetch_all($resx);


		foreach ($dadospPesquisa as $key => $rows) {
			$dadosRespostas = json_decode(utf8_encode($rows["respostas"]),1);
			$conteudo[$rows["admin"]][$rows["posto"]][$rows["os"]]["respostas"] = $dadosRespostas;
		}

		foreach($conteudo as $admin => $rowsPosto) {
		    foreach ($rowsPosto as $oss) {
				$maximoDeOs =  (count($oss) > $maximoDeOs) ? count($oss) : $maximoDeOs;
			}
		}
		echo "<table class='table table-bordered' style='max-width:90% !important'>";
		foreach ($conteudo as $admin => $arrposto) {

			$sqlAdmin   = "SELECT nome_completo FROM tbl_admin WHERE admin = $admin";
			$resAdmin   = pg_query($con, $sqlAdmin);
			$nome_admin = pg_fetch_result($resAdmin, 0, 0);

			foreach ($arrposto as $posto => $xrespostas) {

				$conteudoFinal = array();
				foreach ($xrespostas as $os => $respostas) {
					foreach ($respostas["respostas"] as $kResp => $vResp) {
						$conteudoFinal[utf8_decode($vResp["f3"])][$vResp["f1"]] = utf8_decode($vResp["f2"]);
					}
				}

				$sqlPosto   = "SELECT nome FROM tbl_posto WHERE posto = $posto";
				$resPosto   = pg_query($con,$sqlPosto);
				$nome_posto = pg_fetch_result($resPosto, 0, 0);

				echo '
				<tr class="expand titulo_coluna">
					<th nowrap>Inspetor</th>
					<th nowrap class="tal">'.$nome_admin.'</th>';
					for ($i=0; $i < $maximoDeOs; $i++) { 
						echo '<th>OS</th>';
					}
				echo '</tr>';

				echo '
					<tr>
						<td nowrap>SAE</td>
						<td nowrap class="tal">'.$nome_posto.'</td>';
						foreach ($xrespostas as $os => $respostas) {
							echo '<td>'.$os.'</td>';
						}
						for ($i = count($xrespostas); $i < $maximoDeOs; $i++) {//preenche vazio
							echo '<td nowrap>&nbsp;</td>';
						} 
				echo '</tr>';
				foreach ($conteudoFinal as $pergunta => $xosss) {
					echo '
						<tr>
							<td nowrap colspan="2">'.$pergunta.'</td>';
							foreach ($xosss as $xoss => $resposta) {
								echo '<td nowrap>'.$resposta.'</td>';
							}

							for ($i = count($xrespostas); $i < $maximoDeOs; $i++) {//preenche vazio
								echo '<td nowrap>&nbsp;</td>';
							} 
					echo '</tr>';
				}
			}
		}
		echo "</table>";

		if (@pg_num_rows($resx)) {
			
			$dados_relatorio = ob_get_contents();
		
			fwrite($file,$dados_relatorio);
		
		}
	}
	else if ( isset($_POST['gerar']) ) { // Erro de validacao
		echo '<div id="erro" class="msg_erro" style="display:none;">'.$msg_erro.'</div>';
	}
?>

<?php
	if ( isset ($file) && !empty($dados_relatorio) ) {
		echo "<button class='download' onclick=\"window.open('$link_xls') \">Download XLS</button>";
		fclose($file);
	} else if(count($msg_erro) == 0 && isset($_POST['gerar']) ) { ?>
		<div class="alert alert-warning"><h4>Não foram encontrados resultados para essa pesquisa</h4></div>
	<?php }
?>
<script type="text/javascript">
	<?php if ( !empty($msg_erro) ){ ?>
			$("#erro").appendTo("#msg").fadeIn("slow");
	<?php } ?>
</script>
<?php include 'rodape.php'; ?>