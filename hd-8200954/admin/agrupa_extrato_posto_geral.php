<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro";
include "autentica_admin.php";


if (strlen($_POST["codigo_posto"]) > 0) $codigo_posto = $_POST["codigo_posto"];
if (strlen($_GET["codigo_posto"])  > 0) $codigo_posto = $_GET["codigo_posto"];

$nome   = $_POST['nome'];
if (strlen($_GET['nome']) > 0) $nome = $_GET['nome'];

$msg_erro = "";

$layout_menu = "financeiro";
$title = "AGRUPAR EXTRATOS POR POSTO AUTORIZADO";

$btn_acao     = $_POST["btn_acao"];



if ($btn_acao=="gravar"){

	 $sql = "SELECT   DISTINCT tbl_extrato.extrato                                                                         ,
						tbl_extrato.posto                                                                       ,
						to_char(tbl_extrato_conferencia.data_conferencia, 'DD/MM/YYYY')as    data_conferencia   ,
						tbl_posto_fabrica.codigo_posto                                                          ,
						tbl_extrato_agrupado.codigo as codigo                                                   ,
						to_char(tbl_extrato.data_geracao, 'DDMMYY') AS data_geracao 
				FROM    tbl_extrato 
				JOIN tbl_extrato_conferencia using(extrato) 
				LEFT JOIN tbl_extrato_agrupado using(extrato) 
				JOIN tbl_posto ON tbl_extrato.posto = tbl_posto.posto 
				JOIN tbl_posto_fabrica ON tbl_extrato.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = $login_fabrica 
				WHERE   tbl_extrato.fabrica = $login_fabrica 
				AND     tbl_extrato_conferencia.nota_fiscal IS NULL
				AND     tbl_extrato_conferencia.cancelada IS NOT TRUE
				AND     tbl_extrato_conferencia.data_conferencia > '2010-01-01 00:00:00'
				AND tbl_extrato_agrupado.codigo IS NULL 
				ORDER BY tbl_extrato.posto , tbl_extrato.extrato desc";
	$res = @pg_query ($con,$sql);
	
	$codigo_posto_antigo = "";
	for ($x = 0 ; $x < @pg_num_rows($res) ; $x++){
		$resb = pg_query ($con,"BEGIN TRANSACTION");
		$extrato         = pg_fetch_result($res,$x,extrato);
		$posto           = pg_fetch_result($res,$x,posto);
		$selecionado     = $_POST["$extrato"];

		$codigo_posto = trim(pg_fetch_result($res,$x,codigo_posto));
		if($selecionado== true){
			$sql2 = "SELECT tbl_extrato.extrato                                                                     ,
							tbl_extrato.posto                                                                       ,
							to_char(tbl_extrato_conferencia.data_conferencia, 'DD/MM/YYYY')as    data_conferencia   ,
							tbl_posto_fabrica.codigo_posto                                                          ,
							tbl_extrato_agrupado.codigo as codigo                                                   ,
							to_char(tbl_extrato.data_geracao, 'DDMMYY') AS data_geracao 
							FROM    tbl_extrato 
							JOIN tbl_extrato_conferencia using(extrato) 
							LEFT JOIN tbl_extrato_agrupado using(extrato) 
							JOIN tbl_posto ON tbl_extrato.posto = tbl_posto.posto 
							JOIN tbl_posto_fabrica ON tbl_extrato.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = $login_fabrica 
							WHERE   tbl_extrato.fabrica = $login_fabrica 
							AND     tbl_extrato_conferencia.nota_fiscal IS NULL
							AND     tbl_extrato_agrupado.codigo IS NULL
							AND     tbl_extrato_conferencia.cancelada IS NOT TRUE
							AND     tbl_extrato_conferencia.data_conferencia > '2010-01-01 00:00:00'
							AND     tbl_extrato.posto = $posto
							ORDER BY  tbl_extrato.posto , tbl_extrato.extrato ASC";
			$res2 = pg_query ($con,$sql2);
			$soma = 0;
			if(pg_num_rows($res2) > 0){
				for ($x2 = 0 ; $x2 < pg_num_rows($res2) ; $x2++){
					$extrato_aux = trim(pg_fetch_result($res2,$x2,extrato));
					$posto_aux   =  trim(pg_fetch_result($res2,$x2,posto));
					$selecionado_aux     = $_POST["$extrato_aux"];
					if($selecionado_aux == true){
						if(strlen($data)==0){
							$data = trim(pg_fetch_result($res,$x,data_geracao));
						}

						if ($selecionado_aux == true){
							$soma++;
						}
					}
				}
				$qtd=$soma;

				if(strlen($qtd)==1){
					$qtd = '0'.$qtd;
				}
				$rand = rand(10,99);
				$codigo = $rand.$data.$qtd.$codigo_posto;
				
				if($soma > 0) {
					$sqlxx = " SELECT codigo 
						FROM tbl_extrato
						JOIN tbl_extrato_agrupado USING(extrato)
						WHERE posto = $posto_aux
						AND   fabrica = $login_fabrica
						AND   codigo = '$codigo';";
					$resxx = pg_query($con,$sqlxx);
					if(pg_num_rows($resxx) == 0){
						for ($x2 = 0 ; $x2 < pg_num_rows($res2) ; $x2++){
							$extrato_aux = trim(pg_fetch_result($res2,$x2,extrato));
							$selecionado_aux     = $_POST["$extrato_aux"];
							if($selecionado_aux == true){
								$sql3 = "SELECT extrato FROM tbl_extrato_agrupado where extrato = $extrato_aux";
								$res3 = pg_query($con,$sql3);
								if(pg_num_rows($res3)  == 0){
									$sqlx = "INSERT INTO tbl_extrato_agrupado (extrato,codigo,admin_agrupa)
											SELECT $extrato_aux,'$codigo',$login_admin
											FROM tbl_extrato
											LEFT JOIN tbl_extrato_agrupado USING(extrato)
											WHERE tbl_extrato.extrato = $extrato_aux
											AND tbl_extrato_agrupado.codigo IS NULL;";
									$resx = pg_query ($con,$sqlx);
									$msg_erro = pg_last_error($con);
								}
							}
						}

						
					}
				}
			}
		}
		$codigo_posto_antigo = $codigo_posto;

		if(strlen($msg_erro) == 0) {
			$resb = pg_query ($con,"COMMIT TRANSACTION");
		}else{
			$resb = pg_query ($con,"ROLLBACK TRANSACTION");	
		}
	}
}

if(empty($btn_acao)) {
	$cond = " AND tbl_extrato_agrupado.codigo IS NULL ";
}else{
	$cond = "  ";
}


include "cabecalho_new.php";
?>

<?
if(strlen($msg_erro)>0){
	echo "<DIV class='msg_erro' style='width:700px;'>".$msg_erro."</DIV>";
}
?>
<p>
<center>

<FORM METHOD='post' NAME='frm_extrato_conferencia_agrupa' ACTION="<?=$PHP_SELF?>">
<?
	$sql = "SELECT  DISTINCT tbl_extrato.extrato,
					sum(tbl_extrato_conferencia_item.mao_de_obra) as total,
					to_char(data_geracao,'DD/MM/YYYY') as data_geracao,
					tbl_extrato.posto,
					tbl_posto.nome as posto_nome,
					tbl_posto_fabrica.codigo_posto,
					tbl_posto_fabrica.parametros_adicionais,
					tbl_extrato_agrupado.codigo as codigo,
					to_char(tbl_extrato_conferencia.data_conferencia, 'DD/MM/YYYY') as data_conferencia,
					to_char(tbl_extrato_conferencia.data_conferencia, 'YYYY-MM-DD')  as dt_conf
				FROM tbl_extrato
				JOIN tbl_extrato_conferencia ON tbl_extrato.extrato = tbl_extrato_conferencia.extrato
				LEFT JOIN tbl_extrato_conferencia_item USING(extrato_conferencia)
				LEFT JOIN tbl_extrato_agrupado ON tbl_extrato.extrato = tbl_extrato_agrupado.extrato
				JOIN tbl_posto ON tbl_extrato.posto = tbl_posto.posto
				JOIN tbl_posto_fabrica ON tbl_extrato.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = $login_fabrica
				WHERE   tbl_extrato.fabrica = $login_fabrica
				AND     tbl_extrato_conferencia.cancelada IS NOT TRUE
				AND     tbl_extrato_conferencia.data_conferencia > '2010-01-01 00:00:00'
				AND     tbl_extrato_agrupado.codigo IS NULL
				AND     tbl_extrato_conferencia.nota_fiscal IS NULL
				AND     tbl_extrato_conferencia.caixa IS NOT NULL
				AND     tbl_extrato.valor_agrupado IS NULL
				GROUP BY tbl_extrato.extrato                ,
					tbl_extrato.total                       ,
					data_geracao                            ,
					tbl_extrato.posto                       ,
					tbl_posto.nome                          ,
					tbl_posto_fabrica.codigo_posto          ,
					tbl_posto_fabrica.parametros_adicionais ,
					tbl_extrato_agrupado.codigo             ,
					tbl_extrato_conferencia.data_conferencia,
					tbl_extrato_conferencia.data_conferencia
				ORDER BY tbl_extrato.posto asc, dt_conf asc , tbl_extrato.extrato asc";
	$res = pg_query ($con,$sql);

	if(pg_num_rows($res) > 0){
		echo "<table class='table table-striped table-bordered table-large'>";
		echo "<thead><tr class='titulo_coluna'><th>Selecionar</th><th>Dt Conferencia</th><th>Cod. Posto</th><th>Posto</th><th>Extrato</th><th>Total</th><th>Cod. Agrupado</th></tr></thead>";
		for ($x = 0 ; $x < pg_num_rows($res) ; $x++){

			$extrato               = trim(pg_fetch_result($res, $x, "extrato"));
			$posto                 = trim(pg_fetch_result($res, $x, "posto"));
			$codigo_posto          = trim(pg_fetch_result($res, $x, "codigo_posto"));
			$posto_nome            = trim(pg_fetch_result($res, $x, "posto_nome"));
			$data_conferencia      = trim(pg_fetch_result($res, $x, "data_conferencia"));
			$data_geracao          = trim(pg_fetch_result($res, $x, "data_geracao"));
			$total                 = trim(pg_fetch_result($res, $x, "total"));
			$codigo                = trim(pg_fetch_result($res, $x, "codigo"));
			$parametros_adicionais = trim(pg_fetch_result($res, $x, "parametros_adicionais"));
			
			$posto_nome = substr($posto_nome,0,35);

			if(empty($total)) {
				$total = 0;
			}
			$cor = ($x % 2 == 0) ? '#F7F5F0' : '#F1F4FA';

			if ($posto_anterior != $posto and !empty($posto_anterior)){ 
				echo (!empty($posto_anterior)) ?"<tr style='font-size: 10px; background-color:#D9E2EF' ><td colspan='3' style='background-color:#D9E2EF'></td><td colspan='2' nowrap style='text-align:center;font-size:14px ;background-color:#D9E2EF;font-weight:bold'>TOTAL</td><td><b>".number_format($total_posto,2,",",".")."</b></td><td>&nbsp;</td></tr>":"<tr style='font-size: 10px'><td colspan='100%'>&nbsp;</td></tr>";
			}

			$total_avulso = 0;
			$sql_av = " SELECT
				extrato,
				historico,
				valor,
				admin,
				debito_credito,
				lancamento,
				campos_adicionais
			FROM tbl_extrato_lancamento
			WHERE extrato = $extrato
			AND fabrica = $login_fabrica
			AND (admin IS NOT NULL OR lancamento in (103,104))";

			$res_av = pg_query ($con,$sql_av);

			$extrato_bloqueado_aprovacao = false;

			if(pg_num_rows($res_av) > 0){
				for($i=0; $i < pg_num_rows($res_av); $i++){
					$extrato         = trim(pg_fetch_result($res_av, $i, extrato));
					$historico       = trim(pg_fetch_result($res_av, $i, historico));
					$valor           = trim(pg_fetch_result($res_av, $i, valor));
					$debito_credito  = trim(pg_fetch_result($res_av, $i, debito_credito));
					$lancamento      = trim(pg_fetch_result($res_av, $i, lancamento));
					
					$campos_adicionais = json_decode(pg_fetch_result($res_av, $i, campos_adicionais), true);

					if($campos_adicionais['aprovacao'] == true){
						$extrato_bloqueado_aprovacao = true;
					}

					if($debito_credito == 'D'){ 
						if ($lancamento == 78 AND $valor>0){
							$valor = $valor * -1;
						}
					}

					$total_avulso =  $valor + $total_avulso;
				}
			}else{
				$total_avulso = 0 ;
			}
			$total += $total_avulso;
			
			if($total < 0) {
				//HD 283715: O usuário questionou divergência no agrupamento. O problema é que quando o total é negativo não estava mostrando na tela, mas na hora de totalizar ele considera o valor negativo
				//$total = 0 ;
			}

			echo "<tr bgcolor='$cor'>";

			echo "<td valign='middle'>";

			if($login_fabrica == 3){

				$bloqueado_pagamento = false;

				if(strlen($parametros_adicionais) > 0){

					$pa_arr = json_decode($parametros_adicionais, true);

					if(isset($pa_arr["bloqueado_pagamento"]) && $pa_arr["bloqueado_pagamento"] == "t"){
						$bloqueado_pagamento = true;
					}

				}

				if($bloqueado_pagamento === true){

					echo "POSTO BLOQUEADO PARA PAGAMENTO";

				}elseif($extrato_bloqueado_aprovacao == true){
					echo "Pendente de Aprovação";
				}else{
					$disabled = (strlen($codigo) > 0) ? "disabled" : "";
					echo "<input type='checkbox' name='$extrato' value='t' {$disabled} >";
				}

			}else{

				$disabled = (strlen($codigo) > 0) ? "disabled" : "";
				echo "<input type='checkbox' name='$extrato' value='t' {$disabled} >";

			}

			echo "</td>";
			
			echo "<td>$data_conferencia</td>";
			echo "<td>";
			
			#HD 225975
			$sqlP = "SELECT tbl_extrato.extrato
			FROM tbl_extrato
			LEFT JOIN tbl_extrato_conferencia ON tbl_extrato.extrato = tbl_extrato_conferencia.extrato
			WHERE   tbl_extrato.fabrica = $login_fabrica
			AND     tbl_extrato.posto   = $posto
			AND     tbl_extrato_conferencia.cancelada IS NOT TRUE
			AND     tbl_extrato.data_geracao > '2008-09-01 00:00:00'
			AND     tbl_extrato_conferencia.nota_fiscal IS NULL
			AND     tbl_extrato_conferencia.caixa IS NULL
			AND     (current_date - tbl_extrato.data_geracao::date) >= 60
			AND     tbl_extrato.admin_libera_pendencia IS NULL LIMIT 1";
			$resP = pg_query ($con,$sqlP);

			if(pg_numrows($resP)>0){
				echo "<a href='#' onclick=\"window.open('agrupa_extrato_posto_geral_detalhe.php?posto=$posto', 'Pagina', 'STATUS=NO, TOOLBAR=NO, LOCATION=NO, DIRECTORIES=NO, RESISABLE=NO, SCROLLBARS=YES, TOP=10, LEFT=10, WIDTH=650, HEIGHT=300');\">+&nbsp;</a>";
			}

			echo "$codigo_posto";
			echo "</td>";
			echo "<td>$posto_nome</td>";
			echo "<td>$data_geracao</td>";
			echo "<td>".number_format($total,2,",",".")."</td>";
			echo "<td>$codigo</td>";

			echo "<INPUT TYPE='hidden' name='codigo_posto$aux_extrato' value='$codigo_posto' >";
			echo "<INPUT TYPE='hidden' name='extrato$aux_extrato' value='$extrato' >";
			echo "<INPUT TYPE='hidden' name='nome$aux_extrato' value='$nome' >";
			echo "</tr>";
			
			if ($posto_anterior != $posto){
				$total_posto = 0;
				$total_posto = $total;
			}else{
				$total_posto += $total;
			}

			$posto_anterior = $posto;
			
			

		}
		echo "<input type='hidden' name='btn_acao' value=''>";
		echo"<tr><td bgcolor='#D9E2EF' colspan='100%'><center><input class='btn btn-primary' type='button' value='Gravar'  onclick=\"javascript: document.frm_extrato_conferencia_agrupa.btn_acao.value='gravar'; document.frm_extrato_conferencia_agrupa.submit()\"></center></td></tr>";
		echo "</table>";
	}else{
		echo "<div class='alert alert-warning'><h4>Nenhum extrato encontrado</h4></div>";
	}

?>
<p><p>
</form>
<? include "rodape.php"; ?>
