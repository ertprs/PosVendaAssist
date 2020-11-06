<?

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "funcoes.php";
include "autentica_admin.php";

	$gerar_excel 		=			$_POST["gerar_excel"];
	$extrato 			=			$_POST["extrato"];
	$data_inicial 		=			$_POST["data_inicial"];
	$data_final 		=			$_POST["data_final"];
	$data_baixa_inicio 	=			$_POST["data_baixa_inicio"];
	$data_baixa_fim 	=			$_POST["data_baixa_fim"];
	$posto_codigo 		=			$_POST["posto_codigo"];
	$posto_nome 		=			$_POST["posto_nome"];
	$regiao_estado 		=			$_POST["regiao_estado"];

	if($login_fabrica == 152){
		$filtro_status = $_POST["filtro_status"];

		if(!empty($filtro_status)){

		    if(isset($filtro_status)){
		        switch($filtro_status){
		          case "pendente_aprovacao":
		              $condFiltroStatus = " AND status_extrato = 'Pendente de Aprovação' ";
		          break;
		          case "aguardando_nf_posto":
		              $condFiltroStatus = " AND ( (status_extrato = 'Aguardando Nota Fiscal do Posto' 
		                                              OR
		                                             status_extrato = 'Aguardando Envio da Nota Fiscal' 
		                                            )
		                                          AND (
		                                                SELECT tdocs
		                                                FROM tbl_tdocs
		                                                WHERE referencia_id = tmp_extrato_consulta.extrato
		                                                AND contexto = 'extrato'
		                                                AND situacao = 'ativo'
		                                                LIMIT 1
		                                              ) IS NULL
		                                          )";
		          break;
		          case "aguardando_aprovacao_nf":
		              $condFiltroStatus = " AND ( (status_extrato = 'Aguardando Aprovação de Nota Fiscal' 
		                                              OR 
		                                             status_extrato != 'Nota Fiscal Aprovada' 
		                                            )
		                                            AND (
		                                                  SELECT tdocs
		                                                  FROM tbl_tdocs
		                                                  WHERE referencia_id = tmp_extrato_consulta.extrato
		                                                  AND contexto = 'extrato'
		                                                  AND situacao = 'ativo'
		                                                  LIMIT 1
		                                                ) IS NOT NULL
		                                          ) ";
		          break;
		          case "aguardando_encerramento":
		              $condFiltroStatus = " AND (status_extrato = 'Aguardando Encerramento' OR status_extrato = 'Nota Fiscal Aprovada')";
		          break;
		          case "encerramento":
		              $condFiltroStatus = " AND (status_extrato = 'Encerramento' OR baixado IS NOT NULL)";
		          break;
		        }
		    }
		}
	}

	// validação data_baixa
	if (strlen($_POST['data_baixa_inicio']) > 0){
            $data_baixa_inicio = $_POST['data_baixa_inicio'];
            $x_data_baixa_inicio     = substr ($data_baixa_inicio,6,4) . "-" . substr ($data_baixa_inicio,3,2) . "-" . substr ($data_baixa_inicio,0,2);
    }
    if (strlen($_POST['data_baixa_fim']) > 0){
        $data_baixa_fim = $_POST['data_baixa_fim'];
        $x_data_baixa_fim = substr ($data_baixa_fim,6,4) . "-" . substr ($data_baixa_fim,3,2) . "-" . substr ($data_baixa_fim,0,2);
    }
    if(strlen($x_data_baixa_inicio) == 0 && strlen($x_data_baixa_fim) == 0){
        $sqlJoin_data_baixa = "LEFT JOIN tbl_extrato_pagamento EP ON EP.extrato = EX.extrato";
    }else{
        $x_data_inicial = substr ($data_inicial,6,4) . "-" . substr ($data_inicial,3,2) . "-" . substr ($data_inicial,0,2);
        $sqlJoin_data_baixa = " JOIN tbl_extrato_pagamento EP ON EP.extrato = EX.extrato
                                    AND EP.data_pagamento BETWEEN '{$x_data_baixa_inicio} 00:00:00' AND '{$x_data_baixa_fim} 23:59:59' ";
    }

    //validaçao data geracao
	if (strlen($_POST['data_inicial']) > 0){
            $data_inicial = $_POST['data_inicial'];
            $x_data_inicial     = substr ($data_inicial,6,4) . "-" . substr ($data_inicial,3,2) . "-" . substr ($data_inicial,0,2);
    }
    if (strlen($_POST['data_final']) > 0){
        $data_final = $_POST['data_final'];
        $x_data_final = substr ($data_final,6,4) . "-" . substr ($data_final,3,2) . "-" . substr ($data_final,0,2);
    }

    if(strlen($x_data_inicial) > 0 && strlen($x_data_final) > 0){
        $add_2 = " AND      EX.data_geracao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";
    }

    if (strlen($extrato) > 0) {
    	$cond_extrato = " AND EX.extrato = $extrato";
     
    }

	if(strlen($regiao_estado) > 0) {
				$regiao_estado = str_replace(",", "','",$regiao_estado);
				if (strlen($regiao_estado) > 0) {
					$condicao = " AND PF.contato_estado IN ('$regiao_estado')";
				}

	}

    //valida posto
    $xposto_codigo = str_replace (" " , "" , $posto_codigo);
    $xposto_codigo = str_replace ("-" , "" , $xposto_codigo);
    $xposto_codigo = str_replace ("/" , "" , $xposto_codigo);
    $xposto_codigo = str_replace ("." , "" , $xposto_codigo);

    if (strlen ($posto_codigo) > 0 OR strlen ($posto_nome) > 0 ){
        $sql = "SELECT posto
                FROM tbl_posto
                JOIN tbl_posto_fabrica USING(posto)
                WHERE fabrica = $login_fabrica ";
        if (strlen ($posto_codigo) > 0 ) $sql .= " AND tbl_posto.cnpj = '$xposto_codigo' ";
        if (strlen ($posto_nome) > 0 )   $sql .= " AND tbl_posto.nome ILIKE '%$posto_nome%' ";

        $res = pg_query ($con,$sql);
        if(pg_num_rows($res) > 0){
            $posto = pg_fetch_result($res,0,0);
            $add_3 = " AND EX.posto = $posto " ;
        }
    }

	if($login_fabrica == 152){
    	$colunaStatus = " (select obs from tbl_extrato_status where extrato = EX.extrato order by data desc limit 1) as status_extrato, ";
    }

	$sql= "SELECT DISTINCT
	PO.posto ,
	PO.nome ,
	PO.cnpj ,
	PF.contato_estado as estado ,
	PF.contato_email AS email ,
	PF.credenciamento ,
	PF.codigo_posto ,
	$colunaStatus
	PF.distribuidor ,
	PF.imprime_os ,
	TP.descricao AS tipo_posto ,
	PF.nomebanco AS nome_banco,
	PF.tipo_conta,
	PF.conta,
	PF.favorecido_conta as favorecido,
	PF.obs_conta as obs_conta,
	PF.agencia,
	EX.extrato ,
	EX.bloqueado ,
	EX.fabrica,
	EX.liberado ,
	EX.estoque_menor_20 ,
	EP.autorizacao_pagto,
	TO_CHAR (EX.aprovado,'dd/mm/yyyy') AS aprovado ,
	LPAD (EX.protocolo,6,'0') AS protocolo ,
	TO_CHAR (EX.data_geracao,'dd/mm/yyyy') AS data_geracao ,
	EX.data_geracao AS xdata_geracao,
	EX.total ,
	EX.pecas ,
	EX.mao_de_obra ,
	EX.avulso AS avulso ,
	EX.recalculo_pendente ,
	EP.nf_autorizacao ,
	TO_CHAR (EX.previsao_pagamento,'dd/mm/yyyy') AS previsao_pagamento,
	TO_CHAR (EX.data_recebimento_nf,'dd/mm/yyyy') AS data_recebimento_nf,
	TO_CHAR (EP.data_pagamento,'dd/mm/yyyy') AS baixado ,
	EP.valor_liquido ,
	EE.nota_fiscal_devolucao ,
	EE.nota_fiscal_mao_de_obra ,
	to_char(EE.data_coleta,'dd/mm/yyyy') AS data_coleta ,
	to_char(EE.data_entrega_transportadora,'dd/mm/yyyy') AS data_entrega_transportadora,
	to_char(EE.emissao_mao_de_obra,'dd/mm/yyyy') AS emissao_mao_de_obra,
	tbl_admin.nome_completo, 
	EX.deslocamento AS valor_km 
	into temp tmp_excel_extrato
	FROM tbl_extrato EX JOIN tbl_posto PO on PO.posto = EX.posto
	JOIN tbl_posto_fabrica PF ON EX.posto = PF.posto AND PF.fabrica = $login_fabrica
	JOIN tbl_tipo_posto TP ON TP.tipo_posto = PF.tipo_posto AND TP.fabrica = $login_fabrica 
	JOIN tbl_fabrica FB ON EX.fabrica = FB.fabrica 
	
	{$sqlJoin_data_baixa}
	LEFT JOIN tbl_extrato_extra EE ON EX.extrato = EE.extrato
	LEFT JOIN tbl_admin ON EE.admin = tbl_admin.admin

WHERE EX.fabrica = {$login_fabrica}
	AND PF.distribuidor IS NULL
	$cond_extrato
	$condicao
	$add_2
	$add_3
ORDER BY PO.nome, EX.data_geracao ";
 
$res = pg_query($con, $sql);


$sql = "select * from tmp_excel_extrato where fabrica = $login_fabrica  $condFiltroStatus ";
$res = pg_query($con, $sql);


if(pg_numrows($res)> 0){
	$data = date("d-m-Y-H:i");

	$fileName = "relatorio_extratos-{$data}.csv";

	$file = fopen("/tmp/{$fileName}", "w");
	//head

	if($login_fabrica == 138){
		fwrite($file, "Posto; Extrato; Data Geração; Valor KM; Valor M.O.; Valor Avulso; Valor Total; Banco; Tipo de Conta; Conta; Agência; Favorecido; Observação; \n");
	}else if($login_fabrica == 50){
        fwrite($file, "Código; Nome do Posto; UF; Extrato; Data; Qtde OS; Total; Data Baixa; Liberar; Peças; Mão de Obra; Avulso; Previsão de Pagamento; Data Chegada; Valor Total; Acréscimo; Desconto; Valor Líquido; \n");
    }elseif($login_fabrica == 152){
    	fwrite($file, "Código; Nome do Posto; UF; Extrato; Data; Status;Qtde OS; Total; Requisicao; Data Baixa; \n");
    }else{
		fwrite($file, "Código; Nome do Posto; UF; Extrato; Data; Qtde OS; Total; Total Líquido; Data Baixa; \n");
	}

    $dados = "";
	for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
	    $posto                   = trim(pg_fetch_result($res,$i,posto));
	    $codigo_posto            = trim(pg_fetch_result($res,$i,codigo_posto));
	    $nome                    = trim(pg_fetch_result($res,$i,nome));
	    $extrato                 = trim(pg_fetch_result($res,$i,extrato));
	    $data_geracao            = trim(pg_fetch_result($res,$i,data_geracao));
	    if($login_fabrica == 152){
	    	$total                   = number_format(pg_fetch_result($res,$i,total),2,".","");
	    }else{
	    	$total                   = number_format(pg_fetch_result($res,$i,total),2,",",".");
	    }
	    $valor_liquido           = number_format(pg_fetch_result($res,$i,valor_liquido),2,",",".");
	    $baixado                 = trim(pg_fetch_result($res,$i,baixado));
        $posto_estado            = trim(pg_fetch_result($res,$i,estado));
        $liberado                = trim(pg_fetch_result($res,$i,liberado));
        $previsao_pagamento      = trim(pg_fetch_result($res,$i,previsao_pagamento));
        $data_recebimento_nf     = trim(pg_fetch_result($res,$i,data_recebimento_nf));
        $pecas                   = number_format(pg_fetch_result($res,$i,pecas), 2, ",", ".");

	    $valor_km				 = number_format(pg_fetch_result($res, $i, 'valor_km'),2,",",".");
	    $valor_mo				 = number_format(pg_fetch_result($res, $i, 'mao_de_obra'),2,",",".");
	    $valor_avulso		     = number_format(pg_fetch_result($res, $i, 'avulso'),2,",",".");
	    $banco		    		 = pg_fetch_result($res, $i, 'nome_banco');
	    $tipo_conta		     	 = pg_fetch_result($res, $i, 'tipo_conta');
	    $conta		     		 = pg_fetch_result($res, $i, 'conta');
	    $agencia		     	 = pg_fetch_result($res, $i, 'agencia');
	    $favorecido		     	 = pg_fetch_result($res, $i, 'favorecido');
	    $observacao		     	 = pg_fetch_result($res, $i, 'obs_conta');

	    if($login_fabrica == 152){
	    	$status_extrato = pg_fetch_result($res, $i, status_extrato);
	    	$obs_mostra = "";

	    	if (!empty($status_extrato)) {
	    		switch($status_extrato){
                case "Pendente de Aprovação":
                    $obs_mostra = $status_extrato;
                break;
                case "Aguardando Nota Fiscal do Posto":
                case "Aguardando Envio da Nota Fiscal":
                  $sql_anexo = "SELECT tdocs
                                FROM tbl_tdocs
                                WHERE referencia_id = $extrato
                                AND contexto = 'extrato'
                                AND situacao = 'ativo'
                                AND fabrica = $login_fabrica
                                LIMIT 1 ";
                  $res_anexo = pg_query($con, $sql_anexo);
                  if (pg_num_rows($res_anexo) == 0) {
                    $obs_mostra = "Aguardando Nota Fiscal do Posto";
                  } else {
                    $obs_mostra = "Aguardando Aprovação de Nota Fiscal";
                  }
                break;
                case "Aguardando Aprovação de Nota Fiscal":
                   $sql_anexo = "SELECT tdocs
                                FROM tbl_tdocs
                                WHERE referencia_id = $extrato
                                AND contexto = 'extrato'
                                AND situacao = 'ativo'
                                AND fabrica = $login_fabrica
                                LIMIT 1 ";
                  $res_anexo = pg_query($con, $sql_anexo);
                  if (pg_num_rows($res_anexo) > 0) {
                    $obs_mostra = $status_extrato;
                  } else {
                    $obs_mostra = "Aguardando Nota Fiscal do Posto";
                  } 
                break;
                case "Aguardando Encerramento":
                case "Nota Fiscal Aprovada":
                    $obs_mostra = "Aguardando Encerramento";
                break;
                case "Encerramento":
                    $obs_mostra = $status_extrato;
                break;
              }
	    	}

	    	$autorizacao_pagto = pg_fetch_result($res, $i, autorizacao_pagto);
	    }

	    //total de oss
	    $sqlTotalOs = "SELECT count(*) as qtde_os FROM tbl_os_extra WHERE extrato = $extrato";
	    $resTotalOs = pg_exec($con,$sqlTotalOs);
	    if(pg_numrows($resTotalOs)>0){
	        $qtde_os = pg_result($resTotalOs,0,qtde_os);
	    }

	    if($login_fabrica == 138){
	    	$dados .= $codigo_posto." - ".$nome.";".$extrato.";".$data_geracao.";".$valor_km.";".$valor_mo.";".$valor_avulso.";".$total.";".$banco.";".$tipo_conta.";".$conta.";".$agencia.";".$favorecido.";".$observacao."\n";
	    }else if($login_fabrica == 50){
            $liberado = (strlen($liberado) > 0) ? "SIM" : "NÃO";

            $sql_extrato_pgto = "SELECT valor_total, acrescimo, desconto, valor_liquido FROM tbl_extrato_pagamento WHERE extrato = {$extrato}";
            $res_extrato_pgto = pg_query($con, $sql_extrato_pgto);

            $valor_total = pg_fetch_result($res_extrato_pgto, 0, "valor_total");
            $acrescimo = pg_fetch_result($res_extrato_pgto, 0, "acrescimo");
            $desconto = pg_fetch_result($res_extrato_pgto, 0, "desconto");
            $valor_liquido = pg_fetch_result($res_extrato_pgto, 0, "valor_liquido");

            $dados .= $codigo_posto.";".$nome.";".$posto_estado.";".$extrato.";".$data_geracao.";".$qtde_os.";".$total.";".$baixado.";".$liberado.";".$pecas.";".$valor_mo.";".$valor_avulso.";".$previsao_pagamento.";".$data_recebimento_nf.";".$valor_total.";".$acrescimo.";".$desconto.";".$valor_liquido."\n";
        }elseif($login_fabrica == 152){
        	$dados .= $codigo_posto.";".$nome.";".$posto_estado.";".$extrato.";".$data_geracao.";". $obs_mostra.";".$qtde_os.";".$total.";".$autorizacao_pagto.";".$baixado."\n";
        }else{
	    	$dados .= $codigo_posto.";".$nome.";".$posto_estado.";".$extrato.";".$data_geracao.";".$qtde_os.";".$total.";".$valor_liquido.";".$baixado."\n";
	    }

	}

    fwrite($file,$dados);
	fclose($file);

	if (file_exists("/tmp/{$fileName}")) {
	    system("mv /tmp/{$fileName} xls/{$fileName}");

	    echo "xls/{$fileName}";
	}	
}
exit;
?>
