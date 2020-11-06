<?php
/**
 *
 * admin/extrato_agrupado.php
 *
 * @author  Francisco Ambrozio
 * @version 2011.12.28
 *
 */

$admin_privilegios = 'financeiro';

include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'autentica_admin.php';

$layout_menu = 'financeiro';
$title = 'Extratos';

include_once 'cabecalho.php';


$pesquisa = 1;
$msg_erro = '';
$resultado = '';

if (!empty($_POST['btn_acao'])) {
	$pesquisa = 0;
	$codigo_posto = trim($_POST['codigo_posto']);
	$posto_nome   = trim($_POST['posto_nome']);

	if($login_fabrica == 3){
		$data_inicial 	= trim($_POST['data_inicial']);
		$data_final 	= trim($_POST['data_final']);

		if (strlen ($data_inicial) > 0 and strlen ($data_final) > 0){
	        $x_data_inicial = substr ($data_inicial,6,4) . "-" . substr ($data_inicial,3,2) . "-" . substr ($data_inicial,0,2);
	    
	        $x_data_final = substr ($data_final,6,4) . "-" . substr ($data_final,3,2) . "-" . substr ($data_final,0,2);

	         $meses = 12;
		    if (strtotime($x_data_inicial) < strtotime($x_data_final . " -$meses month")) {
		        $msg_erro .= traduz("Período não pode ser maior que $meses meses.",$con,$cook_idioma)."<Br>";
		        $pesquisa = 1;
		    }
	    }

	   

		if (strlen ($x_data_inicial) > 0 AND strlen ($x_data_final) > 0)
	        $cond_data = " AND       tbl_extrato_conferencia.data_conferencia BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";

	}

	if (!empty($codigo_posto) and !empty($posto_nome)) {
		$sql_posto = "SELECT posto FROM tbl_posto_fabrica JOIN tbl_posto USING (posto)
					WHERE codigo_posto = '$codigo_posto' and TRIM(nome) = '$posto_nome' and fabrica = $login_fabrica";
		$query_posto = pg_query($con, $sql_posto);

		if (pg_num_rows($query_posto) == 0) {
			$msg_erro.= 'Posto não encontrado.';
		} else {
			$posto = pg_fetch_result($query_posto, 0, 'posto');
			
		}

	} else {
		$msg_erro.= 'Preencha as informações do posto.';
		$pesquisa = 1;
	}

if (!empty($codigo_posto) and !empty($posto_nome) and strlen ($data_inicial) == 0 and strlen ($data_final) == 0) {
	$limite = " LIMIT 12 ";
}

	if (($pesquisa == 0) and empty($msg_erro)) {
		$sql = "SELECT DISTINCT tbl_extrato_agrupado.codigo,
						(select to_char(tbl_extrato_conferencia.data_conferencia,'DD/MM/YYYY')
						FROM  tbl_extrato_conferencia
						join tbl_extrato_agrupado ex_a using(extrato)
						WHERE cancelada IS NOT TRUE
						AND   ex_a.codigo = tbl_extrato_agrupado.codigo
						GROUP BY ex_a.codigo,
						tbl_extrato_conferencia.data_conferencia
						ORDER BY data_conferencia DESC LIMIT 1) as data_conferencia,
						(select tbl_extrato_conferencia.data_conferencia
						FROM  tbl_extrato_conferencia
						join tbl_extrato_agrupado ex_a using(extrato)
						WHERE cancelada IS NOT TRUE
						AND   ex_a.codigo = tbl_extrato_agrupado.codigo
						GROUP BY ex_a.codigo,
						tbl_extrato_conferencia.data_conferencia
						ORDER BY data_conferencia DESC LIMIT 1) as dt_conferencia,
						to_char(tbl_extrato_conferencia.previsao_pagamento,'DD/MM/YYYY') as previsao_pg
				FROM tbl_extrato
				JOIN tbl_extrato_conferencia USING(extrato)
				JOIN tbl_extrato_agrupado ON tbl_extrato_conferencia.extrato=tbl_extrato_agrupado.extrato
				WHERE posto = $posto
				AND   fabrica = $login_fabrica
				AND   cancelada IS NOT TRUE
				AND   tbl_extrato_agrupado.aprovado   IS NOT NULL
				$cond_data
				GROUP BY tbl_extrato_agrupado.codigo,
						tbl_extrato_conferencia.data_conferencia,
						tbl_extrato_conferencia.previsao_pagamento
				ORDER BY dt_conferencia DESC
				$limite";
		$res = pg_query($con,$sql);
		$rows = pg_num_rows($res);

		if($rows > 0){
			$resultado.= "<br/><table width='850' border='1' cellspacing='2' Cellpadding='3' align='center'>";
			$resultado.= "<caption nowrap style='height: 65px;'><b>Relação de conferência(s) / Previsão de Pagamento</b><br/><strong>Posto:</strong> $codigo_posto - $posto_nome</caption>";
			$resultado.= "<thead>";
				$resultado.= "<tr class='menu_top'>";
					$resultado.= "<th>DT CONFERÊNCIA</th>";
					$resultado.= "<th>CÓDIGO AGRUPADOR</th>";
					$resultado.= "<th>EXTRATOS AGRUPADOS</th>";
					if($login_fabrica != 3){
						$resultado.= "<th>ENCONTRO DE CONTAS</th>";
					}
					$resultado.= "<th>NOTA FISCAL</th>";
					$resultado.= "<th>TOTAL DA NOTA FISCAL</th>";
					if($login_fabrica != 3){
						$resultado.= "<th>SALDO A PAGAR</th>";
					}
					$resultado.= "<th>PREVISÃO DE PGTO</th>";
				$resultado.= "</tr>";
			$resultado.= "</thead>";
			$resultado.= "<tbody>";

			for($i=0; $i<$rows; $i++) {
				$codigo           = pg_fetch_result($res,$i,'codigo');
				$data_conferencia = pg_fetch_result($res,$i,'data_conferencia');
				$previsao_pg      = pg_fetch_result($res,$i,'previsao_pg');

				$extratos = "";
				$total="";
				$notas= "";
				$sqle = " SELECT DISTINCT to_char(tbl_extrato.data_geracao,'DD/MM/YYYY') as extrato
							from tbl_extrato_conferencia
							JOIN tbl_extrato_agrupado USING(extrato)
							JOIN tbl_extrato ON tbl_extrato.extrato = tbl_extrato_conferencia.extrato
							WHERE cancelada IS NOT TRUE
							AND   codigo = '$codigo'
							AND   posto = $posto
							AND   tbl_extrato_agrupado.aprovado   IS NOT NULL
							AND   fabrica = $login_fabrica ";
				$rese = pg_query($con,$sqle);
				$rowse = pg_num_rows($rese);

				if($rowse > 0){
					for($j=0; $j<$rowse; $j++) {
						$extratos.= ($j > 0) ? "<br>" : "";
						$extratos.= pg_fetch_result($rese,$j,'extrato');
					}
				}

				$sqle = " SELECT DISTINCT nota_fiscal
							from tbl_extrato_conferencia
							JOIN tbl_extrato_agrupado USING(extrato)
							JOIN tbl_extrato ON tbl_extrato.extrato = tbl_extrato_conferencia.extrato
							WHERE cancelada IS NOT TRUE
							AND   codigo='$codigo'
							AND   posto = $posto
							AND   tbl_extrato_agrupado.aprovado   IS NOT NULL
							AND   fabrica = $login_fabrica ";
				$rese = pg_query($con,$sqle);
				$saldo = 0;
				$rowse = pg_num_rows($rese);

				if($rowse > 0){
					$notas = pg_fetch_result($rese,0,'nota_fiscal');
					if(!empty($notas)) {
						$sqlnf = "SELECT	DISTINCT
											to_char(posto_data_transacao,'DD/MM/YYYY') as posto_data_transacao,
											nf_numero_nf                    ,
											nf_valor_do_encontro_contas     ,
											encontro_titulo_a_pagar         ,
											encontro_valor_liquido
									FROM    tbl_encontro_contas
									WHERE   fabrica = $login_fabrica
									AND     posto = $posto
									AND     nf_numero_nf = '$notas'
									AND     posto_data_transacao >current_date - interval '1 year' ";
						$resnf = pg_query($con,$sqlnf);
						$ver_nf_conta = "";
						$rowsnf = pg_num_rows($resnf);
						if($rowsnf > 0){
							$ver_nf_conta = "<a href='javascript:verEncontroContas(\"$notas\")'><u>ABATIMENTO DA MO</u></a>";
							$nf_contas  ="<table width='95%' border='1' cellspacing='2' Cellpadding='3'>";
							$nf_contas .= "<thead>";
							$nf_contas .= "<tr>";
							$nf_contas .= "<th colspan='3'>NOTA DE MÃO DE OBRA</th>";
							$nf_contas .= "<th colspan='2'>NOTAS DE COMPRA ABATIDAS DA MÃO DE OBRA</th>";
							$nf_contas .= "</tr>";
							$nf_contas .= "<tr>";
							$nf_contas .= "<th>Data Do Abatimento</th>";
							$nf_contas .= "<th>Nota de mão de obra</th>";
							$nf_contas .= "<th>Valor do encontro</th>";
							$nf_contas .= "<th>Nota de Compra</th>";
							$nf_contas .= "<th>Valor Abatido</th>";
							$nf_contas .= "</tr>";
							$nf_contas .= "</thead>";
							$nf_contas .= "<tbody>";

							for($n =0;$n<$rowsnf;$n++) {
								$posto_data_transacao            = pg_fetch_result($resnf,$n,'posto_data_transacao');
								$nf_numero_nf                    = pg_fetch_result($resnf,$n,'nf_numero_nf');
								$nf_valor_do_encontro_contas     = pg_fetch_result($resnf,$n,'nf_valor_do_encontro_contas');
								$encontro_titulo_a_pagar         = pg_fetch_result($resnf,$n,'encontro_titulo_a_pagar');
								$encontro_valor_liquido          = pg_fetch_result($resnf,$n,'encontro_valor_liquido');

								$nf_contas .= "<tr style='text-align:center'>";
								$nf_contas .= "<td>$posto_data_transacao</td>";
								$nf_contas .= "<td>$nf_numero_nf</td>";
								$nf_contas .= "<td>".number_format($nf_valor_do_encontro_contas,2,",",".")."</td>";
								$nf_contas .= "<td>$encontro_titulo_a_pagar</td>";
								$nf_contas .= "<td>".number_format($encontro_valor_liquido,2,",",".")."</td>";
								$nf_contas .= "</tr>";
								$saldo = $nf_valor_do_encontro_contas;
							}
							$nf_contas .= "</tbody></table>";
						}
					}
				}

				$sqlt = "SELECT sum(tbl_extrato_conferencia_item.mao_de_obra)  as total
								FROM tbl_extrato
								JOIN tbl_extrato_agrupado USING(extrato)
								JOIN tbl_extrato_conferencia ON tbl_extrato.extrato = tbl_extrato_conferencia.extrato
								JOIN tbl_extrato_conferencia_item USING(extrato_conferencia)
								WHERE tbl_extrato_agrupado.codigo ='$codigo'
								AND   tbl_extrato.fabrica = $login_fabrica
								AND   tbl_extrato.posto  = $posto
								AND   tbl_extrato_agrupado.aprovado   IS NOT NULL
								and   cancelada IS NOT TRUE";
				$rest = pg_query($con,$sqlt);
				$total = pg_fetch_result($rest,0,'total');

				$sql_av = " SELECT
								extrato,
								historico,
								valor,
								tbl_extrato_lancamento.admin,
								debito_credito,
								lancamento
							FROM tbl_extrato_lancamento
							JOIN tbl_extrato_agrupado USING(extrato)
							WHERE tbl_extrato_agrupado.codigo='$codigo'
							AND fabrica = $login_fabrica
							AND   tbl_extrato_agrupado.aprovado   IS NOT NULL
							AND (tbl_extrato_lancamento.admin IS NOT NULL OR lancamento in (103,104))";
				$res_av = pg_query ($con,$sql_av);
				$rows_av = pg_num_rows($res_av);

				$total_avulso = 0;

				if($rows_av > 0){
					for($k=0; $k < $rows_av; $k++){
						$valor           = trim(pg_fetch_result($res_av, $k, 'valor'));
						$debito_credito  = trim(pg_fetch_result($res_av, $k, 'debito_credito'));
						$lancamento      = trim(pg_fetch_result($res_av, $k, 'lancamento'));

						if($debito_credito == 'D'){
							if ($lancamento == 78 AND $valor>0){
								$valor = $valor * -1;
							}
						}
						$total_avulso = $valor + $total_avulso;
					}
				}

				$total +=$total_avulso;
				if(!empty($saldo)) {
					$saldo  = $total - $saldo;
				}

				$cor = ($i%2) ? "#CCCCFF" : "#FFFFFF";

				$resultado.= "<tr class='table_line' bgcolor='$cor'>";
				$resultado.=  "<td>$data_conferencia</td>";
				$resultado.=  "<td><font color='red' size='2'><b>$codigo</b></font></td>";
				$resultado.=  "<td><a href='javascript:verExtrato(\"$codigo\")'><u>VER EXTRATOS</u></a></td>";
				if($login_fabrica != 3){
					$resultado.= "<td nowrap>$ver_nf_conta</td>";
				}
				$resultado.= "<td>$notas</td>";
				$resultado.= "<td><b>".number_format($total,2,",",".")."</b></td>";
				if($login_fabrica != 3){
					$resultado.= "<td><b>".number_format($saldo,2,",",".")."</b></td>";
				}
				$resultado.= "<td>$previsao_pg</td>";
				$resultado.= "</tr>";

				$resultado.= "<tr class='table_line' bgcolor='#FFFFFF'>";
				$resultado.= "<td colspan='100%' align='center'>";
				$resultado.= "<div id='$codigo' class='esconde'>";
				$sqle = " SELECT DISTINCT to_char(tbl_extrato.data_geracao,'DD/MM/YYYY') as				data_geracao,
							tbl_extrato.extrato
							from tbl_extrato_conferencia
							JOIN tbl_extrato_agrupado USING(extrato)
							JOIN tbl_extrato ON tbl_extrato.extrato = tbl_extrato_conferencia.extrato
							WHERE cancelada IS NOT TRUE
							AND   codigo='$codigo'
							AND   tbl_extrato_agrupado.aprovado   IS NOT NULL
							AND   posto = $posto
							AND   fabrica = $login_fabrica
							ORDER BY tbl_extrato.extrato";
				$rese = pg_query($con,$sqle);
				$rowsee = pg_num_rows($rese);

				if($rowsee > 0){
					$resultado.= "<table width='95%' border='1' cellspacing='2' Cellpadding='3' align='center'>";
					$resultado.= "<thead>";
					$resultado.= "<tr>";
					$resultado.= "<th>Extrato</th>";
					$resultado.= "<th>Total</th>";
					$resultado.= "</tr>";
					$resultado.= "</thead>";
					$resultado.= "<tbody>";
					for($j =0;$j<$rowsee;$j++) {

						$extrato = pg_fetch_result($rese,$j,'extrato');

						$sql_av = " SELECT
								extrato,
								historico,
								valor,
								admin,
								debito_credito,
								lancamento
							FROM tbl_extrato_lancamento
							WHERE extrato = $extrato
							AND fabrica = $login_fabrica
							AND (admin IS NOT NULL OR lancamento in (103,104))";
						$res_av = pg_query ($con,$sql_av);
						$rows_avv = pg_num_rows($res_av);

						$total_avulso = 0;

						if($rows_avv > 0){
							for($k=0; $k < $rows_avv; $k++){
								$valor           = trim(pg_fetch_result($res_av, $k, 'valor'));
								$debito_credito  = trim(pg_fetch_result($res_av, $k, 'debito_credito'));
								$lancamento      = trim(pg_fetch_result($res_av, $k, 'lancamento'));

								if($debito_credito == 'D'){
									if ($lancamento == 78 AND $valor>0){
										$valor = $valor * -1;
									}
								}
								$total_avulso = $valor + $total_avulso;
							}
						}


						$sqlt = "SELECT sum(tbl_extrato_conferencia_item.mao_de_obra)  as total
								FROM tbl_extrato
								JOIN tbl_extrato_agrupado USING(extrato)
								JOIN tbl_extrato_conferencia ON tbl_extrato.extrato = tbl_extrato_conferencia.extrato
								JOIN tbl_extrato_conferencia_item USING(extrato_conferencia)
								WHERE tbl_extrato.extrato = $extrato
								AND   tbl_extrato.fabrica = $login_fabrica
								AND   tbl_extrato.posto  = $posto
								AND   tbl_extrato_agrupado.aprovado   IS NOT NULL
								and   cancelada IS NOT TRUE";
						$rest = pg_query($con,$sqlt);
						$total = pg_fetch_result($rest,0,'total');

						$total +=$total_avulso;
						$resultado.= "<tr style='text-align:center'>";
						$resultado.= "<td>";
						$resultado.= pg_fetch_result($rese,$j,'data_geracao');
						$resultado.= "</td>";
						$resultado.= "<td>";
						$resultado.= number_format($total,2,",",".");
						$resultado.= "</td>";
						$resultado.= "</tr>";
					}
					$resultado.= "</tbody>";
					$resultado.= "</table>";
				}
				$resultado.= "</div>";
				$resultado.= "<div id='$notas' class='esconde'>";
				$resultado.= $nf_contas;
				$resultado.= "</div>";
				$resultado.= "</td>";
				$resultado.= "</tr>";
			}
			$resultado.= "</tbody>";
			$resultado.= "</table>";
		}

		$sql = " SELECT
					extrato_nota_avulsa,
					tbl_extrato_nota_avulsa.nota_fiscal   ,
					valor_original,
					to_char(data_geracao,'DD/MM/YYYY') as data_geracao,
					to_char(data_lancamento,'DD/MM/YYYY') as data_lancamento,
					to_char(data_emissao,'DD/MM/YYYY') as data_emissao,
					to_char(tbl_extrato_nota_avulsa.previsao_pagamento,'DD/MM/YYYY') as previsao_pagamento
				FROM tbl_extrato_nota_avulsa
				JOIN tbl_extrato USING(extrato)
				WHERE tbl_extrato.fabrica = $login_fabrica
				AND   posto   = $posto
				ORDER BY previsao_pagamento DESC limit 12";
		$res = pg_query($con,$sql);
		$rows = pg_num_rows($res);

		if($rows > 0){
			$resultado.= "<br/>";
			$resultado.= "<table width='850' border='1' cellspacing='2' Cellpadding='3' align='center'>";
			$resultado.= "<caption>Nota Avulsa do extrato</caption>";
			$resultado.= "<tr class='menu_top'>";
			$resultado.= "<td align='center'>Extrato</td>";
			$resultado.= "<td align='center'>NF</td>";
			$resultado.= "<td align='center'>Valor (R$)</td>";
			$resultado.= "<td align='center'>Previsão Pagamento</td>";
			$resultado.= "</tr>";
			for($i =0;$i<$rows;$i++) {
				$extrato_nota_avulsa= pg_fetch_result($res,$i,'extrato_nota_avulsa');
				$data_lancamento   = pg_fetch_result($res,$i,'data_lancamento');
				$nota_fiscal       = pg_fetch_result($res,$i,'nota_fiscal');
				$data_emissao      = pg_fetch_result($res,$i,'data_emissao');
				$data_geracao      = pg_fetch_result($res,$i,'data_geracao');
				$valor_original    = number_format(pg_fetch_result($res,$i,'valor_original'),2,",",".");
				$previsao_pagamento= pg_fetch_result($res,$i,'previsao_pagamento');
				$cor = ($i%2) ? "#CCCCFF" : "#FFFFFF";

				$resultado.= "<tr style='font-size: 10px;text-align:center; background-color:$cor' >";
				$resultado.= "<td>$data_geracao</td>";
				$resultado.= "<td>$nota_fiscal</td>";
				$resultado.= "<td>$valor_original</td>";
				$resultado.= "<td nowrap>$previsao_pagamento</td>";
				$resultado.= "</tr>";
			}
			$resultado.= "</table>";

		}

		if (empty($resultado)) {
			$resultado.= '<br/><br/><strong>Nenhum resultado encontrado.</strong>';
		} else {
			$resultado.= '<br/><br/><a href="extrato_agrupado.php"><input type="button" value="Nova pesquisa" /></a><br/><br/>';
		}
	}
}

?>
<?php include "javascript_calendario.php"; ?>
<?php include "../js/js_css.php"; ?>
<?php include("plugin_loader.php"); ?>
<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all">
<script src="../plugins/shadowbox/shadowbox.js" type="text/javascript"></script>

<script type="text/javascript">
	$().ready(function(){  
		Shadowbox.init();
		$(".date").datepick();
        $("#data_inicial").mask("99/99/9999");
        $("#data_final").mask("99/99/9999");
    });

	function verExtrato(codigo) {
		var codigo = document.getElementById(codigo);
		if (codigo.style.display){
			codigo.style.display = "";
		}else{
			codigo.style.display = "block";
		}
	}

	function verEncontroContas(nf) {
		var nf = document.getElementById(nf);
		if (nf.style.display){
			nf.style.display = "";
		}else{
			nf.style.display = "block";
		}
	}

	function pesquisaPosto(campo,tipo){
        var campo = campo.value;

        if (jQuery.trim(campo).length > 2){
            Shadowbox.open({
                content:	"posto_pesquisa_2_nv.php?"+tipo+"="+campo+"&tipo="+tipo,
                player:	    "iframe",
                title:		"Pesquisa Posto",
                width:	    800,
                height:	    500
            });
        }else
            alert("Informar toda ou parte da informação para realizar a pesquisa!");
	}

	function retorna_posto(codigo_posto,posto,nome,cnpj,cidade,estado,credenciamento,num_posto){
        gravaDados('codigo_posto',codigo_posto);
        gravaDados('posto_nome',nome);
    }

    function gravaDados(name, valor){
        try{
            $("input[name="+name+"]").val(valor);
        } catch(err){
            return false;
        }
    }


</script>

<style type="text/css">
	.menu_top {
		text-align: center;
		font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
		font-size: 10px;
		font-weight: bold;
		border: 1px solid;
		color:#ffffff;
		background-color: #596D9B
	}

	.table_line {
		text-align: center;
		font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
		font-size: 10px;
		font-weight: normal;
		border: 0px solid;
	}

	.esconde{
		display:none;
		text-align: center;
		font-family: Verdana, Tahoma, Arial, Geneva, Helvetica, sans-serif;
		font-size: 10 px;
	}

	.titulo_tabela{
		background-color:#596d9b;
		font: bold 14px "Arial";
		color:#FFFFFF;
		text-align:center;
	}

	.titulo_coluna{
		background-color:#596d9b;
		font: bold 11px "Arial";
		color:#FFFFFF;
		text-align:center;
	}

	.msg_erro{
		background-color:#FF0000;
		font: bold 16px "Arial";
		color:#FFFFFF;
		text-align:center;
	}

	.formulario{
		background-color:#D9E2EF;
		font:11px Arial;
	}

	.subtitulo{
		background-color: #7092BE;
		font:bold 11px Arial;
		color: #FFFFFF;
		text-align:center;
	}

	table.tabela tr td{
		font-family: verdana;
		font-size: 11px;
		border-collapse: collapse;
		border:1px solid #596d9b;
	}
</style>

<?php
if ($pesquisa == 1) {
	echo '<form name="frm_consulta" method="post" action="' , $_SERVER['PHP_SELF'] , '">';
	echo '<table border="0" cellspacing="0" cellpadding="6" align="center" class="formulario" width="700">';
	if (!empty($msg_erro)) {
		echo '<tr class="msg_erro"><td colspan="5">' , $msg_erro , '</td></tr>';
	}
	echo '<tr class="titulo_tabela">';
		echo '<td colspan="5">Parâmetros de Pesquisa</td>';
	echo '</tr>';
if($login_fabrica == 3){
	echo "<tr>";
		echo "<td width='30'>&nbsp;</td>";
		echo "<td align='left'>Data Inicial
		<Br>
		<input type='text' size='12' maxlength='10' name='data_inicial' id='data_inicial' rel='data' value='$data_inicial' class='frm date' />";

		echo "</td>";
		echo "<td align='left'>Data Final<br> 
				<INPUT type='text' size='12' maxlength='10'  name='data_final' id='data_final' rel='data' value='$data_final' class='frm date' />
		</td>";
	echo "</tr>";
}
	echo "<tr >";
        echo "<td width='30'>&nbsp;</td>";
        echo "<td align='left'>";
            echo "Código Posto <br><input type='text' name='codigo_posto' size='15' value='$codigo_posto' class='frm'>";
            echo "<img border='0' src='imagens/lupa.png' style='cursor: pointer;' align='absmiddle' alt='Clique aqui para pesquisar postos pelo código' onclick='javascript: pesquisaPosto(document.frm_consulta.codigo_posto, \"codigo\")'>";
        echo "</td>";
        echo "<td align='left'>";
            echo "Nome Posto<br><input type='text' name='posto_nome' size='30' value='$posto_nome' class='frm'>";
            echo "<img border='0' src='imagens/lupa.png' style='cursor: pointer;' align='absmiddle' alt='Clique aqui para pesquisar postos pelo código' onclick='javascript: pesquisaPosto (document.frm_consulta.posto_nome, \"nome\")'>";
        echo "</td>";
    echo "</tr>";

	echo "<tr>";
        echo "<td colspan='3' align='center'><input type='submit' name='btn_acao' value='Pesquisar'><br><br></td>";
    echo "</tr>";

	echo '</table>';
	echo '</form>';
}

echo $resultado;

include_once 'rodape.php';

?>
