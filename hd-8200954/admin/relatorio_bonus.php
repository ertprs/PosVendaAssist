<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';

if ($_POST["btn_acao"] == "submit") {
	$codigo_posto       = $_POST['codigo_posto'];
	$descricao_posto    = $_POST['descricao_posto'];
	$mes_pesquisa		= $_POST['mes'];

	if(strlen($mes_pesquisa) == 0){
		$msg_erro["msg"][] = "Selecione o mês para pesquisa";
		$msg_erro["campos"][] = "mes";
	}else{
		if(!empty($codigo_posto)) {
			$cond = " and codigo_posto = '$codigo_posto'";
		}

		$sql1 = "SELECT extrato_programado - interval '3 months' as extrato_programado, posto,codigo_posto, nome
				FROM tbl_posto_fabrica
				join tbl_posto using(posto)
				WHERE fabrica = $login_fabrica
				AND CREDENCIAMENTO <> 'DESCREDENCIADO'
				$cond
				AND extrato_programado IS NOT NULL
				ORDER BY tipo_posto DESC";
		$res1 = pg_query($con,$sql1);
	}
}

function porcentagem($valor,$total){
	$porcentagem = round($valor*100/$total);
	return $porcentagem;
}

$layout_menu = "gerencia";
$title = "RELATÓRIO BONUS POSTO";
Include 'cabecalho_new.php';


$plugins = array(
	"autocomplete",
	"shadowbox",
	"dataTable"
);

include("plugin_loader.php");
?>

<script type="text/javascript">

	$(function() {
		$.autocompleteLoad(Array("posto"));
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});
	});

	function listaOs(tipo_lista,cd_posto,mes_pesquisa){
		Shadowbox.open({
            content:    "posto_bonus_lista_os.php?os="+tipo_lista+"&posto="+cd_posto+"&mes_pesquisa="+mes_pesquisa,
            player: "iframe",
            title:  "Lista OSs"//,
            // width:  900,
            // // height: 355,
            // options: {
            // 	modal: true
            // }
        });
   	}

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


<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
		<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
		<br/>

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='codigo_posto'>Código Posto</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>" >
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
							<input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >&nbsp;
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<!-- HD-3192754 -->
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("mes", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='mes'>Mês</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<select name="mes" id="mes">
								<option value=""></option>
								<?php
								$sql_mes = "SELECT extrato_programado as extrato_programado
											FROM tbl_posto_fabrica
											JOIN tbl_posto using(posto)
											WHERE fabrica = $login_fabrica
											AND CREDENCIAMENTO <> 'DESCREDENCIADO'
											AND extrato_programado IS NOT NULL
											ORDER BY tipo_posto DESC";
								$res_mes = pg_query($con,$sql_mes);
								 $mes_extenso = array(
							        'Jan' => 'Janeiro',
							        'Feb' => 'Fevereiro',
							        'Mar' => 'Marco',
							        'Apr' => 'Abril',
							        'May' => 'Maio',
							        'Jun' => 'Junho',
							        'Jul' => 'Julho',
							        'Aug' => 'Agosto',
							        'Nov' => 'Novembro',
							        'Sep' => 'Setembro',
							        'Oct' => 'Outubro',
							        'Dec' => 'Dezembro'
							    );
								$data_limite = strtotime("2016-03-01");
								$data = strtotime(pg_fetch_result($res_mes, 0, 'extrato_programado'));

								while ($data > $data_limite) {
								    $datax = date("M-Y", $data);
									$datax = explode('-', $datax);
									$mes_s = $datax[0];
									$ano_s = $datax[1];

									$selected_linha = ( isset($mes_pesquisa) and (strtotime($mes_pesquisa) == $data) ) ? "SELECTED" : '' ;

									echo "<option value='".date('Y-m-d',$data)."'".$selected_linha.">";
										echo $mes_extenso[$mes_s].' - '.$ano_s;
									echo "</option>";
									$data = strtotime(date("Y-m-d", $data)." -3 months");
								}
								?>
							</select>
						</div>
					</div>
				</div>
			</div>
			<div class='span6'></div>
		</div>
		<!-- // FIM HD-3192754 -->
		<p><br/>
			<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
		</p><br/>
</form>
</div>

<?php
if (isset($res1)) {
		if (pg_num_rows($res1) > 0) {
			echo "<br />";

		?>
			<table id="resultado_os_atendimento" class='table table-striped table-bordered table-hover table-large' >
				<thead>
					<tr class='titulo_coluna' >
						<th>Classificação</th>
						<th>Posto</th>
						<th>Os</th>
						<th>Os +20 dias</th>
                        <th>Os troca</th>
						<th>Os Reincidente</th>
                        <th>Os reprovada</th>
						<th>Os com componente</th>
					</tr>
				</thead>
				<tbody>
					<?php
			for ($i = 0; $i < pg_num_rows($res1); $i++) {
				$posto = pg_fetch_result($res1,$i,'posto');
				$codigo_posto = pg_fetch_result($res1,$i,'codigo_posto');
				$nome = pg_fetch_result($res1,$i,'nome');
				$extrato_programado	= pg_fetch_result($res1,$i,'extrato_programado');

				$sqlp = "SELECT (current_date - '$extrato_programado'::date)/30";
				$resp = pg_query($con,$sqlp);
				$mes = pg_fetch_result($resp,0,0);

				#if($mes < 3 or empty($mes)) continue;

				$data_consulta_final = $mes_pesquisa;
				$data_consulta_inicial = strtotime($mes_pesquisa." -2 months");
				$data_consulta_inicial = date('Y-m-d',$data_consulta_inicial);

				$cond_data = "AND data_geracao BETWEEN '$data_consulta_inicial 00:00:00' AND '$data_consulta_final 23:59:59';";
				$sql = "SELECT  	os,
									data_fechamento,
									data_abertura,
									data_geracao,
									tbl_os.os_reincidente,
									(data_fechamento - data_abertura) as dias,
									tbl_os.tipo_atendimento,
									tbl_os_troca.os_troca
						into temp tmp_ex_$posto
						FROM tbl_os
						JOIN tbl_os_extra USING(OS)
						JOIn tbl_extrato USING (extrato,fabrica,posto)
						left join tbl_os_troca using(os)
						where tbl_os.fabrica = $login_fabrica
						and tbl_os.posto = $posto
						$cond_data

						create index tmp_ex_os_$posto on tmp_ex_$posto(os);

						SELECT * FROM tmp_ex_$posto;
						";
				$res = pg_query($con,$sql);
				$conta_os = 0 ;
				if(pg_num_rows($res) > 0) {
					for($j=0;$j<pg_num_rows($res);$j++) {
						$os              = pg_fetch_result($res,$j,'os');
						$data_fechamento = pg_fetch_result($res,$j,'data_fechamento');
						$data_abertura   = pg_fetch_result($res,$j,'data_abertura');
						$os_troca		 = pg_fetch_result($res,$j,'os_troca');
						$dias            = pg_fetch_result($res,$j,'dias');

						if(empty($os_troca)) {
							$sql_os = "SELECT (emissao - digitacao_item::date) as dias_item
										FROM tbl_os_item
										JOIN tbl_os_produto USING(os_produto)
										JOIN tbl_faturamento_item using(peca, pedido,os_item)
										JOIN tbl_faturamento using(faturamento)
										where tbl_os_produto.os = $os";
							$res_os = pg_query($con,$sql_os);
							if(pg_num_rows($res_os) > 0) {
								$dias_item = pg_fetch_result($res_os,0,'dias_item');

								if(($dias - ($dias_item + 10)) > 20) {
									$conta_os++;
								}
							}elseif($dias > 20){
								#HD-3192754
								if($mes_pesquisa == "2016-10-01"){
								}else{
									$conta_os++;
								}
							}
						}
					}
				}else{
					continue;
				}

				$qtde = 0 ;
				$sql = "SELECT COUNT(1) from tmp_ex_$posto where tipo_atendimento <> 243";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res) > 0) {
					$qtde = pg_fetch_result($res,0,0);

					if($qtde == 0) continue;
				}

				#troca com falha na analise;
				$sql = "SELECT count(*) FROM tbl_os_troca join tbl_os using(os) join tmp_ex_$posto using(os) where posto = $posto and fabrica = $login_fabrica and causa_troca = 382 ";
				$res = pg_query($con,$sql);
				$os_troca = 0 ;
				if(pg_num_rows($res) > 0) {
					$analise_falha = pg_fetch_result($res,0,0);
				}


				#os reincidente
				$sql = "SELECT count(1) from  tmp_ex_$posto  where os_reincidente";
				$res = pg_query($con,$sql);
				$os_reincidente = 0 ;
				if(pg_num_rows($res) > 0) {
					$os_reincidente = pg_fetch_result($res,0,0);
				}

				#os com auditoria reprovada
				$sql = "SELECT count(1) from tbl_auditoria_os join tbl_os using(os) join tbl_admin ON tbl_admin.admin = tbl_auditoria_os.admin
					where (
					tbl_auditoria_os.reprovada::date between '$extrato_programado'::date and current_date
					or tbl_auditoria_os.cancelada::date between '$extrato_programado'::date and current_date)
					and posto = $posto
				   and tbl_admin.fabrica = $login_fabrica";
				$res = pg_query($con,$sql);
				$os_reprovada = 0 ;
				if(pg_num_rows($res) > 0) {
					$os_reprovada = pg_fetch_result($res,0,0);
				}


				$os_componente = 0 ;
				$ouro = false;
				$prata = false;
				if(((int)$qtde * 0.05) >= $conta_os) {
					$prata = true;
				}

				if($prata) {
					if($analise_falha > 1) {
						$prata = false;
					}
				}

				if($prata) {
					if($os_reprovada > 0) {
						$prata = false;
					}
				}

				if($prata) {
					if($qtde * 0.05 < $os_reincidente) {
						$prata = false;
					}
				}


				if($conta_os == 0 ) {
					$ouro = true;
				}
				if($ouro) {
					if($analise_falha > 0) {
						$ouro = false;
					}
				}
				if($ouro) {
					if($os_reprovada > 0) $ouro = false;
				}
				if($ouro) {
					if($qtde * 0.02 < $os_reincidente) $ouro = false;
				}

				# os usando componente
				$sql = "select count(1) as itens,sum(case when gera_pedido and acessorio then 1 when gera_pedido is false then 1 else 0 end) as acessorio, os
						from tbl_os_item
						join tbl_os_produto using(os_produto)
						join tmp_ex_$posto using(os)
						join tbl_peca using(peca)
						join tbl_servico_realizado using(servico_realizado)
						where (tbl_os_item.parametros_adicionais !~* 'recall' or tbl_os_item.parametros_adicionais isnull)
						group by os";
				$os_componente = 0 ;
				$res = pg_query($con,$sql);
				if(pg_num_rows($res) > 0) {
					for($k=0;$k<pg_num_rows($res);$k++) {
						$itens = pg_fetch_result($res,$k,'itens');
						$acessorio = pg_fetch_result($res,$k,'acessorio');

						if($itens == $acessorio) {
							$os_componente++;
						}
					}

					if($qtde *0.4 > $os_componente) {
						$prata = false;
					}
					if($qtde *0.7 > $os_componente) {
						$ouro = false;
					}
				}else{
					$prata = false;
					$ouro = false;
				}

				$posto_prata = "";
				$posto_c = "Sem Classificação";
				if($prata and !$ouro) {
					$posto_prata = " style='background-color:#2F4F4F;font-weight:bold' class='info'";
					$posto_c = "Prata";
				}
				if($ouro) {
					$posto_prata = " style='background-color:#FFD700 !important; font-weight:bold' class='warning'";
					$posto_c = "Ouro";
				}

				#if(!$ouro and !$prata) continue;
				?>
				<tr <?=$posto_prata?>>
					<td><?=$posto_c?></td>
					<td><?=$codigo_posto?> - <?=$nome?></td>
					<td class='tac' onclick="javascript: listaOs ('os',<?=$posto?>,<?=strtotime($mes_pesquisa)?>)" style="cursor:pointer;" ><?=$qtde?></td>
					<td class='tac' onclick="javascript: listaOs ('osdias',<?=$posto?>,<?=strtotime($mes_pesquisa)?>)" style="cursor:pointer;"><?=$conta_os?></td>
					<td class='tac' onclick="javascript: listaOs ('ostroca',<?=$posto?>,<?=strtotime($mes_pesquisa)?>)" style="cursor:pointer;"><?=$analise_falha?></td>
					<td class='tac' onclick="javascript: listaOs ('osreincidente',<?=$posto?>,<?=strtotime($mes_pesquisa)?>)" style="cursor:pointer;"><?php echo $os_reincidente; echo ' ('.porcentagem($os_reincidente,$qtde).'%)'; ?></td>
					<td class='tac' onclick="javascript: listaOs ('osreprovada',<?=$posto?>,<?=strtotime($mes_pesquisa)?>)" style="cursor:pointer;"><?=$os_reprovada?></td>
					<td class='tac' onclick="javascript: listaOs ('oscomponente',<?=$posto?>,<?=strtotime($mes_pesquisa)?>)" style="cursor:pointer;"><?php echo $os_componente; echo ' ('.porcentagem($os_componente,$qtde).'%)'; ?></td>
				</tr>
			<?php
			}?>
				</tbody>
			</table>

			<?php
			if (pg_num_rows($res1) > 50) {
			?>
				<script>
					$.dataTableLoad({ table: "#resultado_os_atendimento" });
				</script>
			<?php
			}
			?>

			<br />

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



include 'rodape.php';?>
