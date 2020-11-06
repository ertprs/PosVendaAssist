<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";
include_once '../class/tdocs.class.php';

	$ressarcimento		= $_GET['ressarcimento'];
	$os					= $_GET['os_atendimento'];
	$atendimento		= $_GET['atendimento'];
	$nome				= $_GET['nome'];
	$status				= $_GET['status'];
	$data_inicial		= $_GET['data_inicial'];
	$data_final			= $_GET['data_final'];
	$data_tipo			= $_GET['data'];

	$tDocs = new TDocs($con, $login_fabrica);
	if(!empty($os) OR !empty($atendiemnto)){
		$cond = (!empty($os)) ? " AND os = $os " : " AND atendiemnto = $atendimento ";
	}else{
		if(!empty($nome)){
			$nome = strtoupper($nome);
			$cond = " AND upper(tbl_ressarcimento.nome) LIKE '$nome%' ";
		}

		if(!empty($data_inicial) AND !empty($data_final)){
			list($d, $m, $y) = explode("/", $data_inicial);
			if(!checkdate($m,$d,$y)){ 
				$msg_erro = "Data Inválida";
			}

			list($df, $mf, $yf) = explode("/", $data_final);
			if(!checkdate($mf,$df,$yf)){ 
				$msg_erro = "Data Inválida";
			}
			
			if(empty($msg_erro)){
				$nova_data_inicial = "$y-$m-$d";
				$nova_data_final = "$yf-$mf-$df";

				if(strtotime($nova_data_final) < strtotime($nova_data_inicial)){
					$msg_erro = "Data inicial não pode ser maior do que data final";
				}
				switch($data_tipo){
					case 'abertura': $cond .= " AND tbl_ressarcimento.data_input BETWEEN '$nova_data_inicial 00:00:00' and '$nova_data_final 23:59:59' ";break;
					case 'aprovado': $cond .= " AND aprovado BETWEEN '$nova_data_inicial 00:00:00' and '$nova_data_final 23:59:59' ";break;
					case 'finalizado': $cond .= " AND finalizado BETWEEN '$nova_data_inicial 00:00:00' and '$nova_data_final 23:59:59' ";break;
				}
			}
			
			
		}

		if(!empty($status)){
			switch($status){
				case 'pendente': $cond .= " AND aprovado IS NULL AND finalizado IS NULL "; break;
				case 'aprovado': $cond .= " AND aprovado IS NOT NULL AND finalizado IS NULL "; break;
				case 'finalizado': $cond .= " AND finalizado IS NOT NULL "; break;
			}
		}
	}

	
	$sql = "SELECT tbl_ressarcimento.ressarcimento,
					os,
					tbl_ressarcimento.hd_chamado,
					tbl_ressarcimento.nome,
					cpf,
					tbl_ressarcimento.tipo_conta,
					tbl_banco.nome AS banco,
					tbl_banco.codigo AS codigo_banco,
					agencia,
					conta,
					valor_original,
					valor_alterado,
					CASE WHEN valor_alterado > 0 THEN
						valor_alterado
					ELSE
						valor_original
					END AS valor,
					urgencia,
					CASE
						WHEN tbl_ressarcimento.admin_altera is not null THEN
							tbl_ressarcimento.admin_altera
						ELSE
							tbl_ressarcimento.admin
					END AS admin,
					CASE
						WHEN liberado is null THEN
							'Aguardando Liberação'
						WHEN aprovado is null THEN
							'Pendente'
						WHEN finalizado is not null AND lote_fechado is null THEN
							'Finalizado'
						WHEN lote_fechado is not null THEN
							'Lote Fechado'
						ELSE
							'Aprovado'
					END AS status,
					TO_CHAR(tbl_ressarcimento.data_input,'DD/MM/YYYY') AS abertura,
					TO_CHAR(aprovado,'DD/MM/YYYY') AS aprovado,
					TO_CHAR(finalizado,'DD/MM/YYYY') AS finalizado,
					anexo,
					tbl_admin.login,
					TO_CHAR(lote_fechado,'DD/MM/YYYY') AS lote_fechado,
					tbl_produto.referencia,
					tbl_produto.descricao
				FROM tbl_ressarcimento
				JOIN tbl_banco ON tbl_ressarcimento.banco = tbl_banco.banco
				LEFT JOIN tbl_admin ON tbl_ressarcimento.admin_altera = tbl_admin.admin
				LEFT JOIN tbl_os USING(os)
				LEFT JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
				WHERE tbl_ressarcimento.fabrica = $login_fabrica
				$cond
				ORDER BY tbl_ressarcimento.data_input DESC";
	$res = pg_query($con,$sql);
//echo nl2br($sql);
	if(pg_num_rows($res) > 0){
	
		$resultado = "
		<table align='center' border='1'>
			<tr>
				<th bgcolor='#FFFF00'>OS</th>
				<th bgcolor='#FFFF00'>Produto</th>
				<th bgcolor='#FFFF00'>Atendimento</th>
				<th bgcolor='#FFFF00'>Nome</th>
				<th bgcolor='#FFFF00'>CPF</th>
				<th bgcolor='#FFFF00'>Data de Abertura</th>
				<th bgcolor='#FFFF00'>Data de Aprova&ccedil;&atilde;o</th>
				<th bgcolor='#FFFF00'>Data Finaliz&ccedil;&atilde;ao</th>
				<th bgcolor='#FFFF00'>Tipo da Conta</th>
				<th bgcolor='#FFFF00'>Banco</th>
				<th bgcolor='#FFFF00'>Ag&ecirc;ncia</th>
				<th bgcolor='#FFFF00'>Conta</th>
				<th bgcolor='#FFFF00'>Urg&ecirc;ncia</th>
				<th bgcolor='#FFFF00'>Status</th>
				<th bgcolor='#FFFF00'>Valor</th>
				<th bgcolor='#FFFF00'>Observa&ccedil;&atilde;o</th>
				<th bgcolor='#FFFF00'>NF</th>
				<th bgcolor='#FFFF00'>Dep&oacute;sito</th>
			</tr> ";
	
		for($i = 0; $i < pg_numrows($res); $i++){
			$ressarcimento	= pg_result($res,$i,'ressarcimento');
			$os				= pg_result($res,$i,'os');
			$hd_chamado		= pg_result($res,$i,'hd_chamado');
			$nome			= pg_result($res,$i,'nome');
			$cpf			= pg_result($res,$i,'cpf');
			$banco			= pg_result($res,$i,'banco');
			$codigo_banco	= pg_result($res,$i,'codigo_banco');
			$agencia		= pg_result($res,$i,'agencia');
			$conta			= pg_result($res,$i,'conta');
			$valor_original = pg_result($res,$i,'valor_original');
			$valor_alterado = pg_result($res,$i,'valor_alterado');
			$valor			= pg_result($res,$i,'valor');
			$urgencia		= pg_result($res,$i,'urgencia');
			$admin			= pg_result($res,$i,'admin');
			$admin_altera	= pg_result($res,$i,'admin_altera');
			$status			= pg_result($res,$i,'status');
			$abertura		= pg_result($res,$i,'abertura');
			$aprovado		= pg_result($res,$i,'aprovado');
			$finalizado		= pg_result($res,$i,'finalizado');
			$anexo			= pg_result($res,$i,'anexo');
			$observacao		= pg_result($res,$i,'observacao');
			$tipo_conta		= pg_fetch_result($res, $i, 'tipo_conta');
			$referencia  	= pg_result($res,$i,'referencia');
			$descricao  	= pg_result($res,$i,'descricao');

			if($status == "Pendente"){
				$cor = "#CDC9C9";
				$total_pendente += $valor;
			}else if($status == "Aprovado"){
				$cor = "#8FBC8F";
				$total_aprovado += $valor;
			}else{
				$cor = "#EEC591";
				$total_finalizado += $valor;
			}

            $link_deposito = "";

            $anexo_nf = $tDocs->getdocumentsByRef($ressarcimento, "ressarcimento")->url;
            $link_nf = "<a href='$anexo_nf' target='_blank'>Nota Fiscal</a>";

            $anexo = $tDocs->getdocumentsByRef($ressarcimento, "comprovante")->url;
            if (isset($anexo)) {
                $link_deposito = "<a href='$anexo' target='_blank'>Comprovante</a>";
            } else {
                $link_deposito = "";
            }

            $resultado .= "
			<tr>
				<td>$os</td>
				<td>$referencia - $descricao</td>
				<td>$hd_chamado</td>
				<td align='left'>$nome</td>
				<td>$cpf</td>
				<td>$abertura</td>
				<td>$aprovado</td>
				<td>$finalizado</td>
				<td>$tipo_conta</td>
				<td>$codigo_banco - $banco</td>
				<td>$agencia</td>
				<td>$conta</td>
				<td>$urgencia</td>
				<td bgcolor='$cor'>".strtoupper($status)."</td>
				<td bgcolor='$cor'>".number_format($valor,2,',','.')."</td>
                <td>$observacao</td>
                <td>$link_nf</td>
                <td>$link_deposito</td>
			</tr> ";
		}
		$resultado .= "<tr>
							<td colspan='12' align='right' bgcolor='#8FBC8F'>TOTAL APROVADO</td>
							<td bgcolor='#8FBC8F'>".number_format($total_aprovado,2,',','.')."</td>
						</tr>
						<tr>
							<td colspan='12' align='right' bgcolor='#EEC591'>TOTAL FINALIZADO</td>
							<td bgcolor='#EEC591'>".number_format($total_finalizado,2,',','.')."</td>
						</tr>
						<tr>
							<td colspan='12' align='right' bgcolor='#CDC9C9'>TOTAL PENDENTE</td>
							<td bgcolor='#CDC9C9'>".number_format($total_pendente,2,',','.')."</td>
						</tr>";
		$resultado .= "</table>";
				
		$data = date('Y-m-d');
		$fp = fopen("xls/relatorio-ressarcimento-$login_fabrica-$data.xls","w");
        if (!is_resource($fp)) {
            die ("erro ao abrir arquivo");
        }
		fwrite($fp,$resultado);
		fclose($fp);
		
        
		echo "xls/relatorio-ressarcimento-$login_fabrica-$data.xls";
	}

?>
