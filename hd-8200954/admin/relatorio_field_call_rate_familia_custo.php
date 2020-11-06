<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

if($login_fabrica == 20) $admin_privilegios="gerencia";
else $admin_privilegios="financeiro";

include 'autentica_admin.php';

/*if(1==1){
	header("Location: menu_callcenter.php");
exit;
}
*/

$layout_menu = "financeiro";
$title = traduz("FIELD CALL RATE FAMÍLIA DE PRODUTO X CUSTO");

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");
#-------------------- Gerando Relatório -----------------
$btn_acao = $_POST['btn_acao'];
if (strlen ($btn_acao) > 0) {

	if($login_fabrica == 24){
		$matriz_filial = $_POST["matriz_filial"];
		if(strlen($matriz_filial)>0){
			$cond_matriz_filial = " AND substr(tbl_os.serie,length(tbl_os.serie) - 1, 2) = '$matriz_filial' ";
		}	
	}

	$data_inicial = trim($_POST["data_inicial"]);
	$data_final   = trim($_POST["data_final"]);
	$mes          = trim($_POST['mes']);
	$ano          = trim($_POST['ano']);

	if($login_fabrica == 51 || $login_fabrica == 50){

		if( (!empty($data_inicial) or !empty($data_final)) AND  (!empty($mes) or !empty($ano))){
			$erro = 'Apenas um dos Filtro de Data deve ser Utilizado';
		}

		if(strlen($erro) == 0){
			if (strlen($mes) > 0 and strlen($ano) > 0) {
				$aux_data_inicial = date("Y-m-01", mktime(0, 0, 0, $mes, 1, $ano));
				$aux_data_final   = date("Y-m-t", mktime(0, 0, 0, $mes, 1, $ano));
			}
			else{
				$erro ='Informe o Mês e o Ano';
			}
		}

	}
	else{
		if (strlen($_POST["data_inicial"]) == 0)    $erro = "Data inválida";
		//if ($_POST["data_inicial"] == 'dd/mm/aaaa') $erro = "Data inválida";
		//if ($_POST["data_final"] == 'dd/mm/aaaa')   $erro = "Data inválida";
	}

	if (!empty($data_inicial) && !empty($data_final)) {
		list($dia_i, $mes_i, $ano_i) = explode("/", $data_inicial);
		list($dia_f, $mes_f, $ano_f) = explode("/", $data_final);

		if (!checkdate($mes_i, $dia_i, $ano_i) && !checkdate($mes_f, $dia_f, $ano_f)) {
			$erro = traduz("Data inválida");
		}else if (strtotime("{$ano_i}-{$mes_i}-{$dia_i}") > strtotime("{$ano_f}-{$mes_f}-{$dia_f}")) {
			$erro = traduz("Data Inicial não pode ser maior que data final");
		}else {
			$data1 = new DateTime( "{$ano_i}-{$mes_i}-{$dia_i}" );
			$data2 = new DateTime( "{$ano_f}-{$mes_f}-{$dia_f}" );

			$intervalo = $data1->diff( $data2 );

			if($intervalo->y > 1 or ($intervalo->m > 6 and $intervalo->y == 1)) {
				$erro = traduz("O intervalo entre as datas não pode ser maior 18 meses.");
			}

			if ($intervalo->y == 0 AND $intervalo->m == 0 AND $intervalo->days < 27) {
				$erro = traduz("O intervalo entre as datas não pode ser menor que 1 mês.");
			}
		}


		if (strlen($erro) == 0) {

			$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");

			if (strlen ( pg_errormessage ($con) ) > 0) {
				$erro = pg_errormessage ($con) ;
			}

			if (strlen($erro) == 0) $aux_data_inicial = @pg_result ($fnc,0,0);
		}

		if (strlen($erro) == 0) {
			if (strlen($_POST["data_final"]) == 0) $erro = "Data inválida";
			if (strlen($erro) == 0) {

				$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_final')");

				if (strlen ( pg_errormessage ($con) ) > 0) {
					$erro = pg_errormessage ($con) ;
				}

				if (strlen($erro) == 0) $aux_data_final = @pg_result ($fnc,0,0);
			}
		}
	}

	if(strlen($_POST["linha"]) > 0) $linha = trim($_POST["linha"]);

	if(strlen($_POST["estado"]) > 0){
		$estado = trim($_POST["estado"]);
		$mostraMsgEstado = "<br>no ESTADO $estado";
	}

	$produto_referencia = trim($_POST['produto_referencia']); // HD 2003 TAKASHI
	$produto_descricao  = trim($_POST['produto_descricao']) ;// HD 2003 TAKASHI

	if(strlen($produto_referencia)>0 and strlen($produto_descricao)>0){ // HD 2003 TAKASHI
		$sql = "SELECT produto
				from tbl_produto
				join tbl_familia using(familia)
				where tbl_familia.fabrica = $login_fabrica
				and tbl_produto.referencia = '$produto_referencia'";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$produto = pg_result($res,0,produto);
		}

	}

		$criterio         = trim($_POST["criterio"]);
		$tipo_atendimento = trim($_POST["tipo_atendimento"]);
		$familia          = trim($_POST["familia"]);
		$origem           = trim($_POST["origem"]);
		$serie_inicial    = trim($_POST["serie_inicial"]);
		$serie_final      = trim($_POST["serie_final"]);

		if(strlen($serie_inicial) > 0 AND strlen($serie_final)>0 AND ($serie_final - $serie_inicial < 13)) {

			for($x = $serie_inicial ; $x <= $serie_final ; $x++){
				if($x == $serie_final) $aux = "$aux'$x'";
				else                   $aux = "'$x',".$aux;

			}
		}
	if (strlen($erro) > 0) {
		$data_inicial_01 = $_POST["data_inicial_01"];
		$data_final_01   = $_POST["data_final_01"];

		$data_inicial = trim($_POST["data_inicial_01"]);
		$data_inicial = $data_inicial." 00:00:00";
		$data_final   = trim($_POST["data_final_01"]);
		$data_final   = $data_final." 23:59:59";

		$linha            = trim($_POST["linha"]);
		$estado           = trim($_POST["estado"]);

		//$msg  = "<b>Foi(foram) detectado(s) o(s) seguinte(s) erro(s): </b><br>";
		$msg = $erro;


	}else $listar = "ok";
	if ($listar == "ok") {
		unset( $cond_1, $cond_2, $cond_3, $cond_4, $cond_5, $cond_6, $cond_7, $cond_8);

		if (strlen ($estado)          > 0 ) $cond_2 = "AND tbl_posto.estado        = '$estado' ";
		if (strlen ($posto)           > 0 ) $cond_3 = "AND tbl_posto.posto         = $posto ";
		if (strlen ($tipo_atendimento)> 0 ) $cond_5 = "AND tbl_os.tipo_atendimento = $tipo_atendimento";
		if (strlen ($familia)         > 0 ) $cond_6 = "AND tbl_produto.familia     = $familia ";
		if (strlen ($aux)             > 0 ) $cond_8 = "AND substr(serie,0,4) IN ($aux)";

		if($login_fabrica == 20)$tipo_data = " tbl_extrato_extra.exportado ";
		else $tipo_data = " tbl_extrato.data_geracao ";

		if ($login_fabrica == 14) $cond14 = " AND   tbl_extrato.liberado IS NOT NULL";
		/*
		$sql = "SELECT  tbl_familia.familia                 ,
						tbl_familia.descricao               ,
						fcr1.qtde              AS ocorrencia,
						fcr1.pecas                          ,
						fcr1.mao_de_obra
			FROM tbl_familia
			JOIN (
				SELECT tbl_familia.familia, COUNT(*) AS qtde, SUM(tbl_os.pecas) AS pecas , SUM (tbl_os.mao_de_obra) AS mao_de_obra
				FROM tbl_os
				JOIN (
					SELECT tbl_os_extra.os ,
						(SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os_extra.os ORDER BY data DESC LIMIT 1) AS status
					FROM tbl_os_extra
					JOIN tbl_extrato       USING (extrato)
					JOIN tbl_extrato_extra USING(extrato)
					JOIN tbl_posto         USING(posto)
					WHERE tbl_extrato.fabrica = $login_fabrica
					AND   $tipo_data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
					AND tbl_posto.pais='BR'
					$cond14
				) fcr ON tbl_os.os = fcr.os
				JOIN tbl_produto USING(produto)
				JOIN tbl_familia USING(familia)
				JOIN tbl_posto   USING(posto)
				WHERE (fcr.status NOT IN (13,15) OR fcr.status IS NULL)
				AND tbl_os.excluida IS NOT TRUE
				AND $cond_2
				AND $cond_3
				AND $cond_4
				AND $cond_5
				AND $cond_6
				AND $cond_8
				GROUP BY tbl_familia.familia
			) fcr1 ON tbl_familia.familia = fcr1.familia
			ORDER BY fcr1.pecas DESC , fcr1.qtde DESC";
    */

	if($login_fabrica == 24){

		$sql = "SELECT
				tbl_os.os,
				tbl_os.mao_de_obra,
				tbl_familia.familia,
				tbl_familia.descricao
				INTO TEMP tmp_produto_familia_$login_admin
				FROM tbl_os
				JOIN tbl_os_extra ON tbl_os.os = tbl_os_extra.os and tbl_os_extra.i_fabrica = $login_fabrica
				JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
				JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato AND tbl_extrato.fabrica = $login_fabrica
				JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = $login_fabrica
				JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia
				WHERE (tbl_os.status_os_ultimo NOT IN (13,15) OR tbl_os.status_os_ultimo IS NULL)
				AND tbl_os.excluida IS NOT TRUE
				AND tbl_os.fabrica = $login_fabrica
				AND $tipo_data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
				AND tbl_posto.pais='BR'
				$cond_2
				$cond_3
				$cond_4
				$cond_5
				$cond_6
				$cond_8
                $cond14
				$cond_matriz_filial
                ;

                ALTER TABLE  tmp_produto_familia_$login_admin add column preco double precision;

                UPDATE tmp_produto_familia_$login_admin set preco = x.preco FROM (
					SELECT 	sum(tbl_pedido_item.preco) AS preco,
					tmp_produto_familia_$login_admin.os 
					FROM tbl_os_produto 
					JOIN tmp_produto_familia_$login_admin ON tmp_produto_familia_$login_admin.os = tbl_os_produto.os
					JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto 
					JOIN tbl_pedido_item ON tbl_pedido_item.pedido = tbl_os_item.pedido 
					AND tbl_pedido_item.peca = tbl_os_item.peca
					GROUP BY tmp_produto_familia_$login_admin.os
					) AS x
				WHERE tmp_produto_familia_$login_admin.os = x.os;

				SELECT count(os) AS ocorrencia,
				sum(mao_de_obra) as mao_de_obra,
				sum(preco) AS pecas,
				familia,
				descricao
				FROM tmp_produto_familia_$login_admin
				GROUP BY familia,descricao
				ORDER BY ocorrencia DESC, pecas DESC ;
		";
	}else{
	    $sql = "
	            SELECT
	                COUNT(*) AS ocorrencia,
	                SUM(tbl_os.pecas) as pecas,
	                SUM(tbl_os.mao_de_obra) as mao_de_obra,
	                SUM(tbl_os.valores_adicionais) as valores_adicionais,
	                SUM(tbl_os.qtde_km_calculada) as qtde_km_calculada,
	                tbl_familia.familia,
	                tbl_familia.descricao
	            FROM tbl_os
	                JOIN tbl_os_extra   ON tbl_os.os = tbl_os_extra.os and tbl_os_extra.i_fabrica = $login_fabrica
	                JOIN tbl_posto      ON tbl_os.posto = tbl_posto.posto
	                JOIN tbl_extrato    ON tbl_extrato.extrato = tbl_os_extra.extrato AND tbl_extrato.fabrica = $login_fabrica
	                JOIN tbl_produto    ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = $login_fabrica
	                JOIN tbl_familia    ON tbl_familia.familia = tbl_produto.familia
	            WHERE (tbl_os.status_os_ultimo NOT IN (13,15) OR tbl_os.status_os_ultimo IS NULL)
	                AND tbl_os.excluida IS NOT TRUE
	                AND tbl_os.fabrica = $login_fabrica
	                AND $tipo_data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
	                AND tbl_posto.pais='BR'
					$cond_2
					$cond_3
					$cond_4
					$cond_5
					$cond_6
					$cond_8
	                $cond14
	            GROUP BY tbl_familia.familia,tbl_familia.descricao
	            ORDER BY ocorrencia DESC, pecas DESC ;";
    }
	$res = pg_exec($con,$sql);

		$verifica = pg_numrows($res);
		if (pg_numrows($res) > 0) {

			$total = 0;
			//echo 'rm /tmp/assist/relatorio-custo-field-call-rate-linha-de-produto-$login_fabrica-$data_xls.xls';
			$fp = fopen ("/tmp/assist/relatorio-custo-field-call-rate-linha-de-produto-$login_fabrica.html","w");
			$crlf   = "\r\n";
			$f_header = "<html>\n".
						"<head>\n".
						"	<title>RELATÓRIO DE CALLCENTER - $data_xls</title>\n".
						"	<meta name='Author' content='TELECONTROL NETWORKING LTDA'>\n".
						"</head>\n".
						"<body>\n";
			fputs ($fp,$f_header);
			if(strlen($mes) > 0 or strlen($ano) > 0){
				 list($yi, $mi, $di) = explode("-", $aux_data_inicial);
				 list($yf, $mf, $df) = explode("-", $aux_data_final);

				 $data_inicial = $di."/".$mi."/".$yi;
				 $data_final   = $df."/".$mf."/".$yf;
			}
			$resposta  .= "<b>".traduz("Resultado de pesquisa entre os dias")." $data_inicial e $data_final $mostraMsgLinha $mostraMsgEstado</b>";

			$resposta  .=  "<br><br>";
			$resposta  .=  "<FONT SIZE=\"2\">(*) ".traduz("Peças que estão inativas.")."</FONT>";
			$resposta  .=  "<table border='0' class='tabela' cellpadding='2' cellspacing='1' width='700'  align='center'>";
			$resposta  .=  "<TR class='titulo_coluna' height='25'>";
			$resposta  .=  "<TD><b>".traduz("Família")."</b></TD>";
			$resposta  .=  "<TD><b>".traduz("Ocorrência de OS")."</b></TD>";
			$resposta  .=  "<TD><b> ". $real .traduz("M.Obra")."</b></TD>";
			if (in_array($login_fabrica, array(169,170))) {
				$resposta  .=  "<TD><b>% ".traduz("Mão de Obra")."</b></TD>";
				$resposta  .=  "<TD><b> ". $real .traduz("Deslocamento")."</b></TD>";
				$resposta  .=  "<TD><b>% ".traduz("Deslocamento")."</b></TD>";
				$resposta  .=  "<TD><b> ". $real .traduz("Adicionais")."</b></TD>";
				$resposta  .=  "<TD><b>% ".traduz("Adicionais")."</b></TD>";
			}
			if (!in_array($login_fabrica, array(157,158))) {
				$resposta  .=  "<TD><b> ". $real .traduz("Pecas")."</b></TD>";
			}
			$resposta  .=  "<TD><b> ". $real .traduz("TOTAL")."</b></TD>";
			if (!in_array($login_fabrica, array(169,170))) {
				$resposta  .=  "<TD><b>% ".traduz("Mão de Obra")."</b></TD>";
			}
			$resposta  .=  "<TD><b>% ".traduz("Ocorrencia")."</b></TD>";
			$resposta  .=  "</TR>";

			for ($x = 0; $x < pg_numrows($res); $x++) {

				if (in_array($login_fabrica, array(169,170))) {
					$total_pago = $total_pago + pg_result($res,$x,pecas) + pg_result($res,$x,mao_de_obra);
					$total_pago_add = $total_pago_add + pg_result($res,$x,valores_adicionais);
					$total_pago_des = $total_pago_des + pg_result($res,$x,qtde_km_calculada);
                } else {
					$total_pago = $total_pago + pg_result($res,$x,pecas) + pg_result($res,$x,mao_de_obra);
                }
				$total_ocorrencia = $total_ocorrencia + pg_result($res,$x,ocorrencia);
			}

			for ($i=0; $i<pg_numrows($res); $i++){

				$familia     = trim(pg_result($res,$i,familia))   ;
				$descricao   = trim(pg_result($res,$i,descricao)) ;
				$ocorrencia  = trim(pg_result($res,$i,ocorrencia));
				$pecas       = trim(pg_result($res,$i,pecas))  ;
				$mao_de_obra = trim(pg_result($res,$i,mao_de_obra))  ;
				$valores_adicionais = trim(pg_result($res,$i,valores_adicionais));
				$qtde_km_calculada  = trim(pg_result($res,$i,qtde_km_calculada));

                if (in_array($login_fabrica, array(169,170))) {

					$total_os        = $pecas + $mao_de_obra;
					$total_os        = ($total_os > 0) ? $total_os : 0;

					$total_os_add    = $pecas + $valores_adicionais;
					$total_os_add    = ($total_os_add > 0) ? $total_os_add : 0;
					$total_os_des    = $pecas + $qtde_km_calculada;
					$total_os_des    = ($total_os_des > 0) ? $total_os_des : 0;

					$porcentagem_add = (($total_os_add * 100) / $total_pago_add);
					$porcentagem_add = ($porcentagem_add > 0) ? $porcentagem_add : 0;
					$porcentagem_des = (($total_os_des * 100) / $total_pago_des);
					$porcentagem_des = ($porcentagem_des > 0) ? $porcentagem_des : 0;

                } else {
					$total_os = $pecas + $mao_de_obra ;
                }

				if ($total_pago > 0) $porcentagem = (($total_os * 100) / $total_pago);

					if ($total_ocorrencia > 0) $porcentagem_ocorrencia = (($ocorrencia * 100) / $total_ocorrencia);

				if($cor=="#F1F4FA")$cor = '#F7F5F0';
				else               $cor = '#F1F4FA';

				// Todo produto que for inativo estará com um (*) na frente para indicar se está Inativo ou Ativo.
				if($ativo == 'f'){$ativo = "<B>*</B>"; }else{$ativo= '';}

				$resposta  .=  "<TR bgcolor='$cor'>";
                    if($login_fabrica == 24){
                        $resposta  .=  "<td align='left'><a href='relatorio_field_call_rate_familia_produto_custo_referencia_unica.php?familia=$familia&data_inicial=$aux_data_inicial&data_final=$aux_data_final&estado={$estado}&matriz_filial=$matriz_filial' target='_blank' >$descricao</a></td>";
                        //$resposta  .=  "<td align='left'><a href='relatorio_field_call_rate_familia_pecas_custo.php?familia=$familia&data_inicial=$aux_data_inicial&data_final=$aux_data_final' target='_blank'>$descricao</a></TD>";
                    }else{
                        $resposta  .=  "<td align='left'><a href='relatorio_field_call_rate_familia_pecas_custo.php?familia=$familia&data_inicial=$aux_data_inicial&data_final=$aux_data_final' target='_blank'>$descricao</a></TD>";
                    }
                    $resposta  .=  "<TD >$ocorrencia</TD>";
                    $resposta  .=  "<TD align='right'>". number_format($mao_de_obra,2,",",".") ." </TD>";
                    if (in_array($login_fabrica, array(169,170))) {
	                    $resposta  .=  "<TD align='right'>". number_format($porcentagem,2,",",".") ." %</TD>";
                    }

                    if (in_array($login_fabrica, array(169,170))) {
                    	$resposta  .=  "<TD align='right'>". number_format($qtde_km_calculada,2,",",".") ." </TD>";
                    	$resposta  .=  "<TD align='right'>". number_format($porcentagem_des,2,",",".") ."  %</TD>";
                    	$resposta  .=  "<TD align='right'>". number_format($valores_adicionais,2,",",".") ." </TD>";
                    	$resposta  .=  "<TD align='right'>". number_format($porcentagem_add,2,",",".") ."  %</TD>";
					}
                    if (!in_array($login_fabrica, array(157,158))) {
                    	$resposta  .=  "<TD align='right'>". number_format($pecas,2,",",".") ." </TD>";
                    }
                    if (in_array($login_fabrica, array(169,170))) {
	                    $resposta  .=  "<TD align='right'>". number_format(($total_os + $total_os_add + $total_os_des),2,",",".") ." </TD>";
	                } else {

	                    $resposta  .=  "<TD align='right'>". number_format($total_os,2,",",".") ." </TD>";
	                }
                    if (!in_array($login_fabrica, array(169,170))) {
	                    $resposta  .=  "<TD align='right'>". number_format($porcentagem,2,",",".") ." %</TD>";
                    }

                    $resposta  .=  "<TD align='right'>". number_format($porcentagem_ocorrencia,2,",",".") ." %</TD>";
				$resposta  .=  "</TR>";
				if (in_array($login_fabrica, array(169,170))) {
					$total =  $total + $total_os + $total_os_add + $total_os_des;
				} else {
					$total = $total_os + $total;
				}
			}
  			$colspan = "2";
			if (in_array($login_fabrica, array(169,170))) {
  				$colspan = "4";
			}
			$resposta .=  "<tr class='Conteudo' bgcolor='#d9e2ef'>
                                <td><b>TOTAL OCORRENCIA<b></td>
                                <td colspan='1'><font size='2' color='009900'><b>$total_ocorrencia</b></td>
                                <td colspan='$colspan'><b>VALOR CUSTO TOTAL</b></td>
                                <td colspan='$colspan'><font size='2' color='009900'><b> ". $real . number_format($total,2,",",".") ." </b></td>";
                                if (!in_array($login_fabrica, array(157,158))) {
                                	$resposta .= "<td>&nbsp;</td>
			</tr>";
			}
			$resposta .= " </TABLE>";

			$resposta .=  "<br>";
			$resposta .=  "<hr width='600'>";
			$resposta .=  "<br>";

			// monta URL
			$data_inicial = trim($_POST["data_inicial_01"]);
			$data_final   = trim($_POST["data_final_01"]);
			$linha        = trim($_POST["linha"]);
			$estado       = trim($_POST["estado"]);
			$criterio     = trim($_POST["criterio"]);
			/*
			$resposta .= "<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
			$resposta .= "<tr>";
			$resposta .= "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='relatorio_field_call_rate_produto-xls.php?data_inicial=$data_inicial&data_final=$data_final&linha=$linha&estado=$estado&criterio=$criterio' target='_blank'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
			$resposta .=  "</tr>";
			$resposta .=  "</table>";*/



		}
		$listar = "";

	}

}


include "cabecalho.php";

?>
<style>
.Titulo {
	text-align: center;
	font-family: Arial;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
.Conteudo {
	font-family: Arial;
	font-size: 9px;
	font-weight: normal;
}
.ConteudoBranco {
	font-family: Arial;
	font-size: 9px;
	color:#FFFFFF;
	font-weight: normal;
}
.Mes{
	font-size: 9px;
}
.Caixa{
	BORDER-RIGHT: #6699CC 1px solid;
	BORDER-TOP: #6699CC 1px solid;
	FONT: 8pt Arial ;
	BORDER-LEFT: #6699CC 1px solid;
	BORDER-BOTTOM: #6699CC 1px solid;
	BACKGROUND-COLOR: #FFFFFF;
}
.Exibe{
	font-family: Arial, Helvetica, sans-serif;
	font-size: 8 px;
	font-weight: none;
	color: #000000;
	text-align: center;
}
.Erro{
	BORDER-RIGHT: #990000 1px solid;
	BORDER-TOP: #990000 1px solid;
	FONT: 10pt Arial ;
	COLOR: #ffffff;
	BORDER-LEFT: #990000 1px solid;
	BORDER-BOTTOM: #990000 1px solid;
	BACKGROUND-COLOR: #FF0000;
}
.Carregando{
	TEXT-ALIGN: center;
	BORDER-RIGHT: #aaa 1px solid;
	BORDER-TOP: #aaa 1px solid;
	FONT: 10pt Arial ;
	COLOR: #000000;
	BORDER-LEFT: #aaa 1px solid;
	BORDER-BOTTOM: #aaa 1px solid;
	BACKGROUND-COLOR: #FFFFFF;
	margin-left:20px;
	margin-right:20px;
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

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
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
</style>


<!--
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
-->
<?
include "javascript_calendario_new.php";
include '../js/js_css.php';
?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datepick({startdate:'01/01/2000'});
		$('#data_final').datepick({startDate:'01/01/2000'});
		$("#data_inicial").mask("99/99/9999");
		$("#data_final").mask("99/99/9999");
	});
</script>

<script language='javascript' src='../ajax.js'></script>
<script language='javascript'>

function retornaPesquisa (http,componente,componente_erro,componente_carregando) {
	var com = document.getElementById(componente);
	var com2 = document.getElementById(componente_erro);
	var com3 = document.getElementById(componente_carregando);
	if (http.readyState == 1) {

		Page.getPageCenterX() ;
		com3.style.top = (Page.top + Page.height/2)-100;
		com3.style.left = Page.width/2-75;
		com3.style.position = "absolute";

		com3.innerHTML   = "&nbsp;&nbsp;Carregando...&nbsp;&nbsp;<br><img src='../imagens/carregar_os.gif' >";
		com3.style.visibility = "visible";
	}
	if (http.readyState == 4) {
		if (http.status == 200) {
			results = http.responseText.split("|");
			if (typeof (results[0]) != 'undefined') {
				if (results[0] == 'ok') {
					com.innerHTML   = " "+results[1];
					com2.innerHTML  = " ";
					com2.style.visibility = "hidden";
					com3.innerHTML = "<br>&nbsp;&nbsp;Dados carregadas com sucesso!&nbsp;&nbsp;<br>&nbsp;&nbsp;";
					setTimeout('esconde_carregar()',3000);
				}
				if (results[0] == 'no') {
					com2.innerHTML   = " "+results[1];
					com.innerHTML   = " ";
					com2.style.visibility = "visible";
					com3.style.visibility = "hidden";
				}

			}else{
				alert ('Fechamento nao processado');
			}
		}
	}
}
function Exibir (componente,componente_erro,componente_carregando) {

	var1 = document.frm_relatorio.data_inicial.value;
	var2 = document.frm_relatorio.data_final.value;
	var4 = document.frm_relatorio.estado.value;

<?if($login_fabrica == 20){?>
	var7 = document.frm_relatorio.tipo_atendimento.value;
	var8 = document.frm_relatorio.familia.value;
	var9 = document.frm_relatorio.origem.value;
	var10= document.frm_relatorio.serie_inicial.value;
	var11= document.frm_relatorio.serie_final.value;
<?}?>

/*parametros = 'data_inicial_01='+var1+'&data_final_01='+var2+'&linha='+var3+'&estado='+var4;*/
	parametros = '';
	parametros = 'data_inicial_01='+var1+'&data_final_01='+var2+'&estado='+var4+'&ajax=sim';
<?if($login_fabrica ==20){?>
	parametros = parametros + '&tipo_atendimento='+var7+'&familia='+var8+'&origem='+var9+'&serie_inicial='+var10+'&serie_final='+var10;
<?}?>
	url = "<?=$PHP_SELF?>?ajax=sim&"+parametros;

	http.open("GET", url , true);
	http.onreadystatechange = function () { retornaPesquisa (http,componente,componente_erro,componente_carregando) ; } ;
	http.send(null);
}

var Page = new Object();
Page.width;
Page.height;
Page.top;

Page.getPageCenterX = function (){

	var fWidth;
	var fHeight;
	//For old IE browsers
	if(document.all) {
		fWidth = document.body.clientWidth;
		fHeight = document.body.clientHeight;
	}
	//For DOM1 browsers
	else if(document.getElementById &&!document.all){
			fWidth = innerWidth;
			fHeight = innerHeight;
		}
		else if(document.getElementById) {
				fWidth = innerWidth;
				fHeight = innerHeight;
			}
			//For Opera
			else if (is.op) {
					fWidth = innerWidth;
					fHeight = innerHeight;
				}
				//For old Netscape
				else if (document.layers) {
						fWidth = window.innerWidth;
						fHeight = window.innerHeight;
					}
	Page.width = fWidth;
	Page.height = fHeight;
	Page.top = window.document.body.scrollTop;
}


function AbrePeca(produto,data_inicial,data_final,linha,estado){
	janela = window.open("relatorio_field_call_rate_pecas_custo.php?produto=" + produto + "&data_inicial=" + data_inicial + "&data_final=" + data_final + "&linha=" + linha + "&estado=" + estado,"produto",'resizable=1,scrollbars=yes,width=750,height=550,top=0,left=0');
	janela.focus();
}
</script>

<? include "javascript_pesquisas.php" ?>


<FORM name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">

<div id='erro' style='position: absolute; top: 150px; left: 80px;visibility:hidden;opacity:.85;' class='Erro'></div>
<div id='carregando' style='position: absolute;visibility:hidden;opacity:.90;' class='Carregando' width='150'></div>
<table width='700' class='formulario' border='0' cellpadding='5' cellspacing='1' align='center'>
	<? if(strlen($msg) > 0){ ?>
		<tr class="msg_erro">
			<td> <? echo $msg; ?>
		</tr>
	<? } ?>
	<tr>
		<td class='titulo_tabela'><?=traduz('Parâmetros de Pesquisa')?></td>
	</tr>

	<tr>
		<td valign='bottom' align='center'>

			<table width='100%' border='0' cellspacing='2' cellpadding='2' >
				<?php
				if ($login_fabrica != 50) {
				?>
				<tr>
					<td width="120">&nbsp;</td>
					<td align='left' nowrap><font size='2'><?=traduz('Data Inicial')?><br />
						<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='frm' value="<?php echo  $_POST["data_inicial"]; ?>" >
					</td>
					<td align='left' nowrap><font size='2'><?=traduz('Data Final')?><br />
						<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='frm' value="<?php echo  $_POST["data_final"]; ?>" >
					</td>
					<td width="120">&nbsp;</td>
				</tr>

				<?php
				}

				if($login_fabrica == 51 || $login_fabrica == 50){
				?>
				<tr>
					<td width="120">&nbsp;</td>
					<td align='left'><font size='2'>Mês</font><br />
						<select name="mes" size="1" class="frm">
						<option value=''></option>
						<?
						for ($i = 1 ; $i <= count($meses) ; $i++) {
							echo "<option value='$i'";
							if ($mes == $i) echo " selected";
							echo ">" . $meses[$i] . "</option>";
						}
						?>
						</select>
					</td>
					<td align='left'><font size='2'>Ano</font><br />
						<select name="ano" size="1" class="frm">
						<option value=''></option>
						<?
						for ($i = date("Y") ; $i >= 2003; $i--) {
							echo "<option value='$i'";
							if ($ano == $i) echo " selected";
							echo ">$i</option>";
						}
						?>
						</select>
					</td>
					<td width="120">&nbsp;</td>
				</tr>
				<?php
				}
				?>

				<tr>
					<td>&nbsp;</td>
					<td align='left' colspan='2'><font size='2'>Estados<br />
					<select name="estado" size="1" class='frm'>
						<option value=""   <? if (strlen($estado) == 0)    echo " selected "; ?>>TODOS OS ESTADOS</option>
						<option value="AC" <? if ($estado == "AC") echo " selected "; ?>>AC - Acre</option>
						<option value="AL" <? if ($estado == "AL") echo " selected "; ?>>AL - Alagoas</option>
						<option value="AM" <? if ($estado == "AM") echo " selected "; ?>>AM - Amazonas</option>
						<option value="AP" <? if ($estado == "AP") echo " selected "; ?>>AP - Amapá</option>
						<option value="BA" <? if ($estado == "BA") echo " selected "; ?>>BA - Bahia</option>
						<option value="CE" <? if ($estado == "CE") echo " selected "; ?>>CE - Ceará</option>
						<option value="DF" <? if ($estado == "DF") echo " selected "; ?>>DF - Distrito Federal</option>
						<option value="ES" <? if ($estado == "ES") echo " selected "; ?>>ES - Espírito Santo</option>
						<option value="GO" <? if ($estado == "GO") echo " selected "; ?>>GO - Goiás</option>
						<option value="MA" <? if ($estado == "MA") echo " selected "; ?>>MA - Maranhão</option>
						<option value="MG" <? if ($estado == "MG") echo " selected "; ?>>MG - Minas Gerais</option>
						<option value="MS" <? if ($estado == "MS") echo " selected "; ?>>MS - Mato Grosso do Sul</option>
						<option value="MT" <? if ($estado == "MT") echo " selected "; ?>>MT - Mato Grosso</option>
						<option value="PA" <? if ($estado == "PA") echo " selected "; ?>>PA - Pará</option>
						<option value="PB" <? if ($estado == "PB") echo " selected "; ?>>PB - Paraíba</option>
						<option value="PE" <? if ($estado == "PE") echo " selected "; ?>>PE - Pernambuco</option>
						<option value="PI" <? if ($estado == "PI") echo " selected "; ?>>PI - Piauí</option>
						<option value="PR" <? if ($estado == "PR") echo " selected "; ?>>PR - Paraná</option>
						<option value="RJ" <? if ($estado == "RJ") echo " selected "; ?>>RJ - Rio de Janeiro</option>
						<option value="RN" <? if ($estado == "RN") echo " selected "; ?>>RN - Rio Grande do Norte</option>
						<option value="RO" <? if ($estado == "RO") echo " selected "; ?>>RO - Rondônia</option>
						<option value="RR" <? if ($estado == "RR") echo " selected "; ?>>RR - Roraima</option>
						<option value="RS" <? if ($estado == "RS") echo " selected "; ?>>RS - Rio Grande do Sul</option>
						<option value="SC" <? if ($estado == "SC") echo " selected "; ?>>SC - Santa Catarina</option>
						<option value="SE" <? if ($estado == "SE") echo " selected "; ?>>SE - Sergipe</option>
						<option value="SP" <? if ($estado == "SP") echo " selected "; ?>>SP - São Paulo</option>
						<option value="TO" <? if ($estado == "TO") echo " selected "; ?>>TO - Tocantins</option>
					</select>

					</td>
					<td >&nbsp;</td>
				</tr>
				<?php if($login_fabrica == 24){ ?>
				<tr>
					<td width="10">&nbsp;</td>
					<td nowrap>
						<font size='2'>Matriz - 02</font> 
						<input type="radio" name="matriz_filial" value="02" <?php if($matriz_filial == 02 OR $matriz_filial == ''){ echo " checked "; } ?>>
					</td>
					<td>
						<font size='2'>Filial - 04</font> 
						<input type="radio" name="matriz_filial" value="04" <?php if($matriz_filial == 04){ echo " checked "; } ?>>
					</td>
				</tr>
				<?php } ?>
<?if($login_fabrica==20){ ?>
				<tr>
					<td width="10">&nbsp;</td>
					<td align='right' nowrap><font size='2'>Tipo Atendimento</td>
					<td align='left' colspan='3'>
					<select name="tipo_atendimento" size="1" class="frm">
						<option <? if (strlen ($tipo_atendimento) == 0) echo " selected " ?> ></option>
						<?
						$sql = "SELECT * FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica ORDER BY tipo_atendimento";
						$res = pg_exec ($con,$sql) ;

						for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
							echo "<option ";
							if ($tipo_atendimento == pg_result ($res,$i,tipo_atendimento) ) echo " selected ";
							echo " value='" . pg_result ($res,$i,tipo_atendimento) . "'" ;
							echo " > ";
							echo pg_result ($res,$i,codigo) . " - " . pg_result ($res,$i,descricao) ;
							echo "</option>\n";
						}
						?>
					</select>
					</td>
					<td width="10">&nbsp;</td>
				</tr>


				<tr>
					<td width="10">&nbsp;</td>
					<td align='right' nowrap>Família</td>
					<td align='left' colspan='3'>
					<select name="familia" size="1" class="frm">
						<option <? if (strlen (familia) == 0) echo " selected " ?> ></option>
						<?
						$sql = "SELECT * FROM tbl_familia WHERE fabrica = $login_fabrica ORDER BY familia";
						$res = pg_exec ($con,$sql) ;

						for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
							echo "<option ";
							if ($tipo_atendimento == pg_result ($res,$i,familia) ) echo " selected ";
							echo " value='" . pg_result ($res,$i,familia) . "'" ;
							echo " > ";
							echo pg_result ($res,$i,descricao) ;
							echo "</option>\n";
						}
						?>
					</select>
					</td>
					<td width="10">&nbsp;</td>
				</tr>

				<tr>
					<td width="10">&nbsp;</td>
					<td align='right' nowrap><font size='2'>Origem</td>
					<td align='left' colspan='3'>
					<select name="origem" class="frm">
						<option value="">ESCOLHA</option>
						<option value="Nac" <? if ($origem == "Nac") echo " SELECTED "; ?>>Nacional</option>
						<option value="Imp" <? if ($origem == "Imp") echo " SELECTED "; ?>>Importado</option>
					</select>
					</td>
					<td width="10">&nbsp;</td>
				</tr>
				<tr>
					<td width="10">&nbsp;</td>
					<td align='right' nowrap><font size='2'>Serie Inicial</td>
					<td align='left'><input type="text" name="serie_inicial" size="3" class='frm' value="<? echo $serie_inicial ?>" maxlength='3'>
					</td>
					<td align='right' nowrap><font size='2'>Serie Final</td>
					<td align='left'><input type="text" name="serie_final" size="3" class='frm' value="<? echo $serie_inicial ?>" maxlength='3'>
					</td>
					<td width="10">&nbsp;</td>
				</tr>
<?}?>

			</table><br>
			<input type='submit' name='btn_acao' style="cursor:pointer " value='Consultar'>
		</td>
	</tr>
</table>
</FORM>


<p>


<?
	if (strlen ($btn_acao) > 0) {
		if(strlen($erro) == 0 && $verifica > 0){
			echo $resposta;
			fputs($fp,$resposta);
				echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /www/assist/www/admin/xls/relatorio-custo-field-call-rate-linha-de-produto-$login_fabrica-$data_xls.xls /tmp/assist/relatorio-custo-field-call-rate-linha-de-produto-$login_fabrica.html`;
					echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
					echo"<tr>";
					echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>
					<BR>RELATÓRIO CUSTO - FIELD CALL-RATE : LINHA DE PRODUTO <BR>Clique aqui para fazer o </font>
						<a href='xls/relatorio-custo-field-call-rate-linha-de-produto-$login_fabrica-$data_xls.xls'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
					echo "</tr>";
					echo "</table>";
		}
		else{
			echo "<b>".traduz("Nenhum resultado encontrado entre")." $data_inicial e $data_final $mostraMsgLinha $mostraMsgEstado</b>";
		}
	}

	include "rodape.php"
?>
