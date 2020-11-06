<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="financeiro";
include "autentica_admin.php";
include "funcoes.php";


$layout_menu = "financeiro";
$title = "Impressão de Nota Fiscal ";

$total = $_GET['total'];

?>
<style type="text/css">


.table_obs2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

.table_line3 {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	background-color: #FFFFBB;
}

.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}


.titulo_coluna{
	background-color:#596d9b;
	font: bold 10px "Arial";
	color:#FFFFFF;
	text-align:center;
}


.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}


.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

.subtitulo{

	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 9px;
	border-collapse: collapse;
	border:1px solid #000000;
}
</style>



<?

if($tipo == 'p'){
	$cond2 .= " AND tbl_posto_fabrica_banco.tipo_conta = 'Poupança' and tbl_posto_fabrica_banco.ativo ";
	$lote_titulo = "PESSOA POUPANÇA";
}elseif($tipo == 'f'){
	$cond2 .= " AND tbl_posto_fabrica_banco.tipo_conta = 'Física' and tbl_posto_fabrica_banco.ativo";
	$lote_titulo = "PESSOA FÍSICA";
}elseif (empty($tipo)){
	//$cond2 .= " AND tbl_posto_fabrica_banco.tipo_conta <> 'Poupança' ";
	//$cond2 .= " AND tbl_posto_fabrica_banco.tipo_conta <> 'Física' ";
	$cond2 .= ' AND (tbl_posto_fabrica_banco.posto is null or tbl_posto_fabrica_banco.ativo is not true)';
	$lote_titulo = " PESSOA JURÍDICA ";
}

if($total == 'maior'){
	$imprimir = 'sim';
	$cond = " AND tbl_extrato_conferencia.valor_nf >= 8000 ";

	$lote_titulo = " Mão de Obra de Postos Autorizados acima de R$ 8.000,00 - {$lote_titulo}";


}elseif($total == 'menor'){
	$cond .= " AND tbl_extrato_conferencia.valor_nf < 8000 ";
}


$lote = date('dmy');
	

	$sql = "SELECT  to_char(CURRENT_DATE,'DDMMYY') as lote            ,
					to_char(tbl_extrato_conferencia.data_conferencia,'DD/MM/YYYY') as data_conferencia,
					tbl_admin.login                                                    ,
					to_char(data_geracao,'DD/MM/YYYY') as data_geracao                 ,
					tbl_extrato.posto                                                  ,
					tbl_posto.nome as posto_nome                                       ,
					tbl_posto_fabrica.codigo_posto                                     ,
					tbl_extrato_conferencia.valor_nf                                   ,
					tbl_extrato_conferencia.extrato                                    ,
					tbl_extrato_conferencia.nota_fiscal                                ,
					to_char(tbl_extrato_agrupado.aprovado,'DD/MM/YYYY') as aprovado    ,
					to_char(tbl_extrato_conferencia.data_lancamento_nota,'DD/MM/YYYY') as emissao    ,
					to_char(tbl_extrato_conferencia.previsao_pagamento,'DD/MM/YYYY') as previsao    ,
					aprovado.login  as aprovador                                       ,
					tbl_posto_fabrica_banco.cpf_favorecido as cpf_conta                ,
					tbl_posto_fabrica_banco.favorecido as favorecido_conta              ,
					tbl_banco.nome AS banco                                            ,
					tbl_banco.codigo AS codigo_banco                                   ,
					tbl_posto_fabrica_banco.agencia                                    ,
					tbl_posto_fabrica_banco.conta                                      ,
					tbl_posto_fabrica_banco.tipo_conta
				FROM tbl_extrato
				JOIN tbl_extrato_conferencia ON tbl_extrato.extrato = tbl_extrato_conferencia.extrato
				JOIN tbl_extrato_agrupado ON tbl_extrato.extrato = tbl_extrato_agrupado.extrato
				JOIN tbl_posto ON tbl_extrato.posto = tbl_posto.posto
				JOIN tbl_posto_fabrica ON tbl_extrato.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = $login_fabrica
				JOIN tbl_admin ON tbl_admin.admin = tbl_extrato_conferencia.admin
				LEFT JOIN tbl_posto_fabrica_banco on (tbl_posto_fabrica.fabrica = tbl_posto_fabrica_banco.fabrica and tbl_posto_fabrica.posto = tbl_posto_fabrica_banco.posto)
				LEFT JOIN tbl_banco ON tbl_posto_fabrica_banco.banco = tbl_banco.banco
				LEFT JOIN tbl_admin aprovado ON aprovado.admin  = tbl_extrato_agrupado.admin
				WHERE   tbl_extrato.fabrica = $login_fabrica
				AND     tbl_extrato_conferencia.cancelada IS NOT TRUE
				AND     tbl_extrato_conferencia.data_conferencia > '2011-01-01 00:00:00'
				AND     tbl_extrato_agrupado.codigo IS NOT NULL
				AND     tbl_extrato_conferencia.data_lancamento_nota::date = CURRENT_DATE
				AND     tbl_extrato_agrupado.aprovado IS NOT NULL
				$cond
				$cond2
				ORDER BY tbl_extrato_conferencia.data_lancamento_nota ";
	//echo nl2br($sql);
	$res = pg_query ($con,$sql);
	if(pg_num_rows($res) > 0){
		echo "<p style='text-align:center; font-size: 16px;font-weight:bold; font-family: Arial; line-height: 26px;'>
					Pagamento de {$lote_titulo}<br />
					Data de implantação: ".Date('d/m/Y')."<br />
					Data de Previsão de Pagamento  ___/___/______
			  </p><br />";

		$posto_anterior = "";
		$extrato_print = Array();
		for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
			$posto            = trim(pg_fetch_result($res,$x,'posto'));
			$codigo_posto     = trim(pg_fetch_result($res,$x,'codigo_posto'));
			$posto_nome       = trim(pg_fetch_result($res,$x,'posto_nome'));
			$login            = trim(pg_fetch_result($res,$x,'login'));
			$data_conferencia = trim(pg_fetch_result($res,$x,'data_conferencia'));
			$data_geracao     = trim(pg_fetch_result($res,$x,'data_geracao'));
			$valor_nf         = trim(pg_fetch_result($res,$x,'valor_nf'));
			$extrato          = pg_fetch_result($res,$x,'extrato');
			$aprovado         = trim(pg_fetch_result($res,$x,'aprovado'));
			$aprovador        = trim(pg_fetch_result($res,$x,'aprovador'));
			$lote             = trim(pg_fetch_result($res,$x,'lote'));
			$nota_fiscal      = trim(pg_fetch_result($res,$x,'nota_fiscal'));
			$emissao          = trim(pg_fetch_result($res,$x,'emissao'));
			$previsao         = trim(pg_fetch_result($res,$x,'previsao'));
			$cpf_conta        = trim(pg_fetch_result($res,$x,'cpf_conta'));
			$favorecido_conta = trim(pg_fetch_result($res,$x,'favorecido_conta'));
			$banco            = trim(pg_fetch_result($res,$x,'banco'));
			$codigo_banco     = trim(pg_fetch_result($res,$x,'codigo_banco'));
			$agencia          = trim(pg_fetch_result($res,$x,'agencia'));
			$conta            = trim(pg_fetch_result($res,$x,'conta'));
			$tipo_conta       = trim(pg_fetch_result($res,$x,'tipo_conta'));
			$posto_nome       = substr($posto_nome,0,35);
			$extrato_print[] = $extrato;
			
			$cor = ($x % 2 == 0) ? '#F7F5F0' : '#F1F4FA';

			$sqlt = " SELECT sum(tbl_extrato_conferencia_item.mao_de_obra)  as total
									FROM tbl_extrato
									JOIN tbl_extrato_conferencia ON tbl_extrato.extrato = tbl_extrato_conferencia.extrato
									JOIN tbl_extrato_conferencia_item USING(extrato_conferencia)
									WHERE tbl_extrato.extrato = $extrato
									AND   tbl_extrato.fabrica = $login_fabrica
									and   cancelada IS NOT TRUE";
			$rest = pg_query($con,$sqlt);
			$total = pg_fetch_result($rest,0,total);

			$total_avulso = 0;
			$sql_av = " SELECT
				extrato,
				historico,
				valor,
				tbl_extrato_lancamento.admin,
				debito_credito,
				lancamento
			FROM tbl_extrato_lancamento
			WHERE tbl_extrato_lancamento.extrato = $extrato
			AND fabrica = $login_fabrica
			AND (tbl_extrato_lancamento.admin IS NOT NULL OR lancamento in (103,104));";

			$res_av = pg_query ($con,$sql_av);

			if(pg_num_rows($res_av) > 0){
				
				for($i=0; $i < pg_num_rows($res_av); $i++){
					$extrato         = trim(pg_fetch_result($res_av, $i, extrato));
					$historico       = trim(pg_fetch_result($res_av, $i, historico));
					$valor           = trim(pg_fetch_result($res_av, $i, valor));
					$debito_credito  = trim(pg_fetch_result($res_av, $i, debito_credito));
					$lancamento      = trim(pg_fetch_result($res_av, $i, lancamento));
					
					if($debito_credito == 'D'){ 
						if ($lancamento == 78 AND $valor>0){
							$valor = $valor * -1;
						}
					}

					$total_avulso = $valor + $total_avulso;
				}
			}else{
				$total_avulso = 0 ;
			}
			
			$total += $total_avulso;

			if($total < 0) {
				$total = 0 ;
			}
			
			$label_extratos = "";
			$label_vl_extr  = "";
			$style          = "style='border:0px none'";
			
			
			if($posto != $posto_anterior) {
				echo "</table>";
				echo "<table width='800' ALIGN='CENTER' cellspacing='1' class='tabela'>";
				echo "<tr class='titulo_coluna'>";
				echo '<td><p>DATA DE CONFERÊNCIA</p></td>';
				echo '<td><p>CONFERENTE</p></td>';
				echo '<td><p>DATA APROVAÇÃO</p></td>';
				echo '<td><p>APROVADOR</p></td>';
				echo '</tr>';
				echo "<tr bgcolor='$cor' style='text-align:center'>";
				echo '<td>',$data_conferencia,'</td>';
				echo '<td>',$login,'</td>';
				echo '<td>',$aprovado,'</td>';
				echo '<td>&nbsp;',$aprovador,'</td>';
				echo '</tr>';
				echo "<tr bgcolor='$cor' style='text-align:center'>";
				echo "<td class='titulo_coluna'>Cod. Posto</td>";
				echo "<td>$codigo_posto</td>";
				echo "<td class='titulo_coluna'>Razão Social</td>";
				echo "<td>$posto_nome</td>";
				echo "</tr>";
				$label_extratos = "Extratos: ";
				$label_vl_extr  = "Vlr Extr: ";
				$style          = "class='titulo_coluna'";
			}
		

			echo "<tr bgcolor='$cor' style='text-align:center'>";
			echo "<td $style width='150'>$label_extratos</td>";
			echo "<td width='150'>$data_geracao</td>";
			echo "<td $style width='150'>$label_vl_extr</td>";
			echo "<td width='150'>".number_format($total,2,",",".")."</td>";
			echo "</tr>";
			
			if($posto != $posto_anterior) {
				echo "<tfoot>";
				echo "<tr bgcolor='$cor' style='text-align:center' >";
				echo "<td class='titulo_coluna'>Nota:</td>";
				echo "<td>$nota_fiscal</td>";
				echo "<td class='titulo_coluna'>Vlr. NF</td>";
				echo "<td>".number_format($valor_nf,2,",",".")."</td>";
				echo "</tr>";
				echo "<tr bgcolor='$cor' style='text-align:center'>";
				echo "<td class='titulo_coluna'>Emissão:</td>";
				echo "<td>$emissao</td>";
				echo "<td class='titulo_coluna'>Prev. Pgto: </td>";
				echo "<td>$previsao</td>";
				echo "</tr>";
				
				if(in_array($tipo,array('f','p'))) {
					echo "<tr bgcolor='$cor' style='text-align:center'>";
					echo "<td class='titulo_coluna'>Favorecido:</td>";
					echo "<td>$favorecido_conta</td>";
					echo "<td class='titulo_coluna'>CPF/CNPJ: </td>";
					echo "<td>$cpf_conta</td>";
					echo "</tr>";
					echo "<tr bgcolor='$cor' style='text-align:center'>";
					echo "<td class='titulo_coluna'>Cod. Banco:</td>";
					echo "<td>$codigo_banco</td>";
					echo "<td class='titulo_coluna'>Nome Banco: </td>";
					echo "<td>$banco</td>";
					echo "</tr>";
					echo "<tr bgcolor='$cor' style='text-align:center'>";
					echo "<td class='titulo_coluna'>Agêcia:</td>";
					echo "<td>$agencia</td>";
					echo "<td class='titulo_coluna'>Conta: </td>";
					echo "<td>$conta</td>";
					echo "</tr>";
				}
				echo "<tr>";
				echo "<td style='border:0px none' colspan='4'><br/><br>";
				for($j =0;$j<150;$j++) {
					echo "-";
				}
				echo "<br><br/></td>";
				echo "</tr>";
				echo "</tfoot>";
			}
			
			$posto_anterior = $posto;
		}
		echo "</table>";
		echo "<br/>";
		echo "<script language='JavaScript'>
					window.print();
			</script>";
	}else{
		echo "<h1>Nenhum extrato encontrado</h1>";
		echo "<script language='javascript'>";
		echo "window.opener=null; ";
		echo "window.open(\"\",\"_self\"); ";
		echo "setTimeout('window.close()',5000); ";
		echo "</script>";
	}
?>
<p><p>
<br/><br/><br/>
<table width='500' align='CENTER' style='font-weight:bold; position:absolute;left:270px' >
<tr><td nowrap>Aprovador Técnico</td><td nowrap>________________________</td></tr>
<tr><td nowrap><br/><br/><br/></td></tr>
<tr><td>Gerência</td><td nowrap>________________________</td></tr>
<tr><td nowrap><br/><br/><br/></td></tr>
<tr><td>Presidência</td><td nowrap>________________________</td></tr>
<tr><td nowrap><br/><br/><br/></td></tr>
</table>
</center>
<?php 

	if($imprimir == 'sim'){
		foreach ($extrato_print as $extrato) {
			echo "<script>
					window.open('extrato_posto_mao_obra_novo_britania_print.php?extrato={$extrato}&imprimir=sim','maior_oito_mil_{$extrato}','height=600, width=900, top=20, left=20, scrollbars=yes');
			  	</script>";	
		}
	}
 ?>