<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios = "auditoria";
include "autentica_admin.php";
include "funcoes.php";

#Programa desenvolvido para Britânia solicitado no chamado 42726 - 08/10/2008

$sql = "SELECT posto_fabrica
		FROM tbl_fabrica
		WHERE fabrica = $login_fabrica ";
$res2 = pg_query($con,$sql);
$posto_da_fabrica = pg_fetch_result($res2,0,0);

function verificaBloqueioOs($con,$login_fabrica,$os,$acao)
{

    $sqlItens = "
        SELECT  DISTINCT
                tbl_os_item.parametros_adicionais::JSONB->'bloqueio' AS bloqueio
        FROM    tbl_os_item
        JOIN    tbl_os_produto USING(os_produto)
        WHERE   tbl_os_produto.os = $os
        AND     tbl_os_item.peca_obrigatoria IS TRUE
    ";
//     echo $sqlItens;
    $resItens = pg_query($con,$sqlItens);

    if (pg_num_rows($resItens) > 1) {
        while ($itens = pg_fetch_object($resItens)) {
            if ($itens->bloqueio == 'true') {
                $sqlMudaOs = "
                    UPDATE  tbl_os_extra
                    SET     recolhimento = FALSE
                    WHERE   os = $os
                ";
                break;
            }
        }

    } else {
        $bloqueio = pg_fetch_result($resItens,0,bloqueio);
        if ($bloqueio === true) {
            $sqlMudaOs = "
                UPDATE  tbl_os_extra
                SET     recolhimento = FALSE
                WHERE   os = $os
            ";
        } else {
            $sqlMudaOs = "
                UPDATE  tbl_os_extra
                SET     recolhimento = TRUE
                WHERE   os = $os
            ";
        }
    }
    $resMudaOs = pg_query($con,$sqlMudaOs);

    return true;
}

if (filter_input(INPUT_POST,'ajax',FILTER_VALIDATE_BOOLEAN)) {
    $tipo = filter_input(INPUT_POST,'tipo');

    if ($tipo == "bloqueio_os") {
        $osBloqueio         = filter_input(INPUT_POST,'os_bloqueio');
        $os_item            = filter_input(INPUT_POST,'os_item');
        $numero_postagem    = filter_input(INPUT_POST,'numero_postagem');

        $sqlValor = "
            SELECT  tbl_os_item.os_item,
                    tbl_os_item.parametros_adicionais
            FROM    tbl_os_item
            JOIN    tbl_os_produto USING(os_produto)
            WHERE   tbl_os_produto.os   = $osBloqueio
            AND     tbl_os_item.os_item = $os_item
            AND     tbl_os_item.peca_obrigatoria IS TRUE
        ";
        $resValor = pg_query($con,$sqlValor);

        pg_query($con,"BEGIN TRANSACTION");
        while ($valor = pg_fetch_object($resValor)) {
            $valorBloqueio = json_decode($valor->parametros_adicionais,TRUE);


            if ($valorBloqueio['bloqueio'] == TRUE) {
                $valorBloqueio['bloqueio'] = FALSE;
                $acao = "desbloqueio";
            } else {
                $valorBloqueio['bloqueio'] = TRUE;
                $acao = "bloqueio";
            }

            $valorBloqueio['admin']             = $login_admin;
            $valorBloqueio['data_bloqueio']     = date('Y-m-d H:i:s');
            $valorBloqueio['numero_postagem']   = $numero_postagem;
            $sqlRetornaValor = "
                UPDATE  tbl_os_item
                SET     parametros_adicionais = E'".json_encode($valorBloqueio)."'
                WHERE   os_item = ".$valor->os_item
            ;
            $resRetornaValor = pg_query($con,$sqlRetornaValor);

            if (pg_last_error($con)) {
                pg_query($con,"ROLLBACK TRANSACTION");
                echo "erro";
                exit;
            }
        }

        verificaBloqueioOs($con,$login_fabrica,$osBloqueio,$acao);

        if (pg_last_error($con)) {
            pg_query($con,"ROLLBACK TRANSACTION");
            echo "erro";
            exit;
        }

        pg_query($con,"COMMIT TRANSACTION");
        echo json_encode(array("ok"=> TRUE,"acao"=>$acao));
        exit;
    }

    if ($tipo == "bloqueio_multiplo") {
        $os_item            = filter_input(INPUT_POST,"os_item",FILTER_UNSAFE_RAW,FILTER_REQUIRE_ARRAY);
        $posto              = filter_input(INPUT_POST,"posto");
        $numero_postagem    = filter_input(INPUT_POST,"numero_postagem");
        $acao               = filter_input(INPUT_POST,"acao");

        pg_query($con,"BEGIN TRANSACTION");

        foreach ($os_item as $item) {
            $sql = "
                SELECT  tbl_os_item.parametros_adicionais,
                        tbl_os.os
                FROM    tbl_os_item
                JOIN    tbl_os_produto  USING(os_produto)
                JOIN    tbl_os          USING(os)
                WHERE   tbl_os_item.os_item = $item
            ";

            $res = pg_query($con,$sql);

            $parametros = pg_fetch_result($res,0,parametros_adicionais);
            $osBloqueio = pg_fetch_result($res,0,os);

            $adicionais = json_decode($parametros,TRUE);

            if ($acao == "ocultar") {
                $adicionais["bloqueio"] = FALSE;
            } else {
                $adicionais["bloqueio"] = TRUE;
            }
            $adicionais["admin"] = $login_admin;
            $adicionais["data_bloqueio"] = date('Y-m-d H:i:s');

            $sqlDevolve = "
                UPDATE  tbl_os_item
                SET     parametros_adicionais = E'".json_encode($adicionais)."'
                WHERE   os_item = $item
            ";
            $resDevolve = pg_query($con,$sqlDevolve);

            verificaBloqueioOs($con,$login_fabrica,$osBloqueio,$acao);

            if (pg_last_error($con)) {
                echo "erro: ".pg_last_error($con);
                pg_query($con,"ROLLBACK TRANSACTION");
                exit;
            }

            $osRetorno[] = $osBloqueio;
        }
        pg_query($con,"COMMIT TRANSACTION");
        echo json_encode(array("ok"=> TRUE,"acao" => $acao,"osRetorno" => $osRetorno));
        exit;
    }
}


#Postos abaixo NÃO COBRAR PEÇAS
/*RETIRADO CONFORME SOLICITADO PELO TULIO (VISITA A BRITANIA) 01/06/2009 -  CONVERSA NO CHAT COM IGOR*/
//$postos_permitidos_novo_processo = array(0 => '0',1 => '6976', 2 => '20397', 3 => '4044', 4 => '1267', 5 => '6458', 6 => '710', 7 => '5037', 8 => '1752', 9 => '4311', 10 => '1537',11 => '6359');
$postos_permitidos_novo_processo = array(0 => '9999');

$btn_acao = strtoupper($_REQUEST["btn_acao"]);

if ((strlen($btn_acao) > 0 && $btn_acao == "PESQUISAR") OR $_POST["gerar_excel"]== "true"  ) {

	$data_inicial = trim($_REQUEST["data_inicial"]);
	$data_final = trim($_REQUEST["data_final"]);
	$codigo_posto = trim($_REQUEST["codigo_posto"]);

    if(in_array($login_fabrica,array(94,151)) && filter_input(INPUT_POST,'peca_referencia')){
        $peca = filter_input(INPUT_POST,'peca',FILTER_VALIDATE_INT);
    } else {
        $peca = "";
    }

	if (strlen($codigo_posto)>0){
		$sql = "SELECT  tbl_posto_fabrica.codigo_posto AS cod, tbl_posto.nome as nome, tbl_posto.posto as posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica USING(posto)
				WHERE tbl_posto_fabrica.fabrica=$login_fabrica
				AND tbl_posto_fabrica.codigo_posto = '$codigo_posto';";
		$res = pg_query($con,$sql);
		if (pg_num_rows($res)>0){
			$posto_codigo = pg_fetch_result($res,0,cod);
			$posto_nome   = pg_fetch_result($res,0,nome);
			$posto        = pg_fetch_result($res,0,posto);

			if(in_array($login_fabrica,array(50,151))){
				$sql_posto = " AND tbl_os.posto = $posto";
			} else {
				$sql_posto = " AND tbl_extrato_lgr.posto = $posto";
			}
		}else{
			$sql_posto = " AND 1=2 ";
		}
	}

	if(strlen($data_inicial) > 0 && $data_inicial != "dd/mm/aaaa"){
		$xdata_inicial =  fnc_formata_data_pg(trim($data_inicial));
		$xdata_inicial = str_replace("'","",$xdata_inicial);
	}else{
		$msg_erro["msg"][]    = traduz("Data Inicial Inválida");
		$msg_erro["campos"][] = "data_inicial";
	}

	if(strlen($data_final) > 0 && $data_final != "dd/mm/aaaa"){
		$xdata_final =  fnc_formata_data_pg(trim($data_final));
		$xdata_final = str_replace("'","",$xdata_final);
	}else{
		$msg_erro["msg"][]    = traduz("Data Final Inválida");
		$msg_erro["campos"][] = "data_final";
	}

	if(!count($msg_erro["msg"])){
		$dat = explode ("/", $data_inicial );//tira a barra
		$d = $dat[0];
		$m = $dat[1];
		$y = $dat[2];
		if(!checkdate($m,$d,$y)){
			$msg_erro["msg"][]    = traduz("Data Inicial Inválida");
			$msg_erro["campos"][] = "data_inicial";
		}
	}
	if(!count($msg_erro["msg"])){
		$dat = explode ("/", $data_final);//tira a barra
		$d = $dat[0];
		$m = $dat[1];
		$y = $dat[2];
		if(!checkdate($m,$d,$y)){
			$msg_erro["msg"][]    = traduz("Data Final Inválida");
			$msg_erro["campos"][] = "data_final";
		}
	}

	if($xdata_inicial > $xdata_final) {
		$msg_erro["msg"][]    = traduz("Data Inicial maior que final");
		$msg_erro["campos"][] = "data_inicial";
	}

	if (count($msg_erro['msg']) == 0){
		$sql_data = " AND tbl_extrato.data_geracao BETWEEN '$xdata_inicial 00:00:01' AND '$xdata_final 23:59:59'";
	}

	if(in_array($login_fabrica, array(3, 94))) {
		$cond_1 = " AND (tbl_peca.devolucao_obrigatoria IS TRUE OR tbl_peca.produto_acabado IS TRUE) ";
	}
}

if(isset($_POST["gerar_excel"]) && $_POST["gerar_excel"]== "true"){

	$data_inicial = trim($_REQUEST["data_inicial"]);
	$data_final = trim($_REQUEST["data_final"]);
	$codigo_posto = trim($_REQUEST["codigo_posto"]);

	$xdata_inicial =  fnc_formata_data_pg(trim($data_inicial));
	$xdata_inicial = str_replace("'","",$xdata_inicial);

	$xdata_final =  fnc_formata_data_pg(trim($data_final));
	$xdata_final = str_replace("'","",$xdata_final);

	if ($login_fabrica == 114) {
		$cond_1 = "	AND tbl_extrato.data_geracao > '2014-05-01' ";
	}

	$postos_permitidos_novo_processo = implode(",", $postos_permitidos_novo_processo);

	if($login_fabrica == 94 and $peca > 0){
		$where_pecas .= " AND tbl_extrato_lgr.peca = $peca ";
	}

	$data = date("d-m-Y-H:i");
	$fileName = "relatorio_lgr-{$data}.csv";
	$file = fopen("/tmp/{$fileName}", "w");

	if ($login_fabrica == 91) {

		$thead .=  utf8_encode("Código Posto").";"."Nome Posto;". "Extrato;" . "Total Extratos Pendentes;"."\r\n";

		$sql =  "select min(tbl_extrato.extrato) as extrato, tbl_extrato.posto, tbl_posto_fabrica.codigo_posto, tbl_posto.nome,
				(select min(fi.extrato_devolucao) from  tbl_faturamento
					join tbl_faturamento_item fi using(faturamento)
					where distribuidor = tbl_extrato.posto
					and fabrica = 91
					and conferencia isnull
				) as extrato_sem_conferencia
			from tbl_extrato
			join tbl_extrato_lgr  using(extrato)
			JOIN tbl_peca ON tbl_extrato_lgr.peca = tbl_peca.peca
			JOIN tbl_faturamento_item ON tbl_faturamento_item.peca = tbl_extrato_lgr.peca and tbl_faturamento_item.extrato_devolucao = tbl_extrato_lgr.extrato
			JOIN tbl_os_item USING(pedido, pedido_item)
			JOIN tbl_posto on tbl_extrato.posto = tbl_posto.posto
			join tbl_posto_fabrica  on tbl_extrato.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			where tbl_extrato.fabrica = $login_fabrica
			and (tbl_extrato_lgr.qtde_nf is null or tbl_extrato_lgr.qtde_nf = 0)
			AND (tbl_os_item.peca_obrigatoria or tbl_peca.devolucao_obrigatoria)
			AND tbl_extrato.extrato not in (
				select distinct fi.extrato_devolucao
				from tbl_faturamento
				join tbl_faturamento_item fi using(faturamento)
				where distribuidor = tbl_extrato.posto
				and fabrica = $login_fabrica
			)
            $sql_posto
            $sql_data
            group by tbl_extrato.posto, tbl_posto_fabrica.codigo_posto, tbl_posto.nome;";

    } else if ($login_fabrica == 50) {

        $thead .=  utf8_encode("Código Posto").";"."Nome Posto;". "OS;"."KM;".utf8_encode("Mão de Obra").";".  "Data Fechamento OS;". utf8_encode("Quant. Peças").";"."\r\n";


			$sql = "SELECT tbl_os.os,
		    		b.qtde_inspecionada,
					tbl_posto_fabrica.codigo_posto, tbl_posto.nome,
					(select count(tbl_os_item.qtde) from tbl_os_item
			inner join tbl_os_produto on tbl_os_produto.os_produto = tbl_os_item.os_produto
			where tbl_os_produto.os = tbl_os.os and tbl_os_item.fabrica_i = $login_fabrica and tbl_os_item.peca_obrigatoria = 't') as qtde_pecas,
			tbl_os.data_fechamento, tbl_os.qtde_km_calculada, tbl_os.mao_de_obra
		    FROM tbl_os
		    INNER JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
		    INNER JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto
		    INNER JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
            INNER JOIN tbl_faturamento_item ON (tbl_faturamento_item.pedido = tbl_os_item.pedido OR tbl_faturamento_item.os_item = tbl_os_item.os_item) and tbl_faturamento_item.peca = tbl_os_item.peca
		    INNER JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento AND tbl_faturamento.fabrica = $login_fabrica
		    left JOIN tbl_faturamento_item b ON b.os = tbl_os.os and tbl_os.fabrica = $login_fabrica and tbl_os_item.peca = b.peca
		    left JOIN tbl_faturamento a ON a.faturamento = b.faturamento AND a.fabrica = $login_fabrica and a.distribuidor = tbl_os.posto
		    INNER JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
		    INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto and tbl_posto_fabrica.fabrica = $login_fabrica
		    WHERE tbl_os_item.peca_obrigatoria =  't'
		    AND tbl_os.finalizada is not null

		    AND tbl_os.fabrica = $login_fabrica
		    AND tbl_os.finalizada between '$xdata_inicial 00:00:00' and  '$xdata_final 23:59:59'
		    AND tbl_os_extra.extrato is null
		    and (tbl_os_item.qtde > b.qtde_inspecionada or b.qtde_inspecionada is null)
		    $sql_posto
		    AND b.faturamento_item is null
		    group by tbl_os.os, b.qtde_inspecionada, tbl_posto_fabrica.codigo_posto, tbl_posto.nome, tbl_os_item.qtde, tbl_os.data_fechamento, tbl_os.qtde_km_calculada, tbl_os.mao_de_obra
		    ORDER by tbl_posto.nome, tbl_os.os";

    } else {
        if (in_array($login_fabrica, array(153))) {
            $thead .=
                        utf8_encode(traduz("Código Posto;"))
                        . utf8_encode(traduz("Nome Posto;"))
                        . utf8_encode(traduz("Extrato;"))
                        . utf8_encode(traduz("Data do Extrato;"))
                        . utf8_encode(traduz("Código Peça;"))
                        . utf8_encode(traduz("Descrição Peça;"))
                        . utf8_encode(traduz("Qtde Peça;"))
                        . utf8_encode(traduz("Preço Peça;"))
                        . utf8_encode(traduz("Total Peça;"))
                        /*. utf8_encode("Nota da Peça;")
                        . utf8_encode("Total da Nota;")*/
                        ."\r\n"
            ;
        } else {
            $thead .=  utf8_encode("Código Posto").";"."Nome Posto;". "Extrato;".  utf8_encode(traduz("Data do Extrato;")).";"."\r\n";
        }
        $sql = "SELECT tbl_posto_fabrica.codigo_posto,
                    tbl_posto.nome,
                    tbl_extrato.extrato,
                    TO_CHAR(tbl_extrato.data_geracao,'DD/MM/YYYY') AS data_geracao,
                    X.qtde_nf
            FROM tbl_extrato
            JOIN tbl_posto USING(posto)
            JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
            JOIN (	SELECT  DISTINCT tbl_extrato_lgr.posto, tbl_extrato_lgr.extrato, (SELECT COUNT(el.*) FROM tbl_extrato_lgr el WHERE tbl_extrato_lgr.posto = el.posto AND tbl_extrato_lgr.extrato = el.extrato AND (tbl_extrato_lgr.qtde_nf IS NULL OR tbl_extrato_lgr.qtde_nf = 0)) AS qtde_nf
                FROM tbl_extrato_lgr
                JOIN tbl_extrato USING(extrato)
                JOIN tbl_peca ON tbl_peca.peca = tbl_extrato_lgr.peca
                JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_extrato.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                LEFT JOIN tbl_extrato_devolucao ON tbl_extrato_devolucao.extrato     = tbl_extrato.extrato
                LEFT JOIN tbl_faturamento       ON tbl_faturamento.extrato_devolucao = tbl_extrato.extrato AND tbl_faturamento.distribuidor = tbl_extrato_lgr.posto
                WHERE tbl_extrato.fabrica = $login_fabrica
                $where_pecas
                AND tbl_faturamento.posto                   IS NULL
                AND tbl_extrato.liberado notnull
                AND tbl_extrato_devolucao.extrato_devolucao IS NULL
                AND (
                    (tbl_extrato.extrato > 240000
                    AND tbl_extrato.posto IN ($postos_permitidos_novo_processo)
                    AND tbl_peca.produto_acabado IS TRUE
                    ) OR (
                    tbl_extrato.extrato > 240000
                    AND tbl_extrato.posto NOT IN ($postos_permitidos_novo_processo)
                    ) OR (
                    tbl_extrato.extrato < 240000
                    )
                )
                $sql_posto
                $sql_data
                $cond_1
                AND (tbl_extrato_lgr.qtde_nf IS NULL OR tbl_extrato_lgr.qtde_nf = 0)
            ) X ON X.extrato  = tbl_extrato.extrato
            ORDER BY tbl_posto.nome,tbl_extrato.data_geracao";
		}

		fwrite($file, $thead);
		//echo nl2br($sql);
		$res = pg_query($con, $sql);
		$posto_ant = "";
		$posto_extrato_unico = false;
		$qtde_resultado = pg_num_rows($res);
		$qtde_nf_ant = 0;
		$qtde_extrato_geral = 1;
        $qtde_geral = 0;

        $total_nf = 0;
		for ($i = 0; $i < $qtde_resultado; $i++) {
			$codigo_posto	= trim(pg_fetch_result($res,$i,codigo_posto));
			$nome			= trim(pg_fetch_result($res,$i,nome));

			if($login_fabrica == 50){
				$qtde_km_calculada 		= trim(pg_fetch_result($res,$i,qtde_km_calculada));
				if(strlen(trim($qtde_km_calculada))==0){
					$qtde_km_calculada = "0";
				}
				$mao_de_obra	= trim(pg_fetch_result($res,$i,mao_de_obra));
				$mao_de_obra 	= number_format($mao_de_obra, 2, ',', ' ');
				$qtde_km_calculada 	= number_format($qtde_km_calculada, 2, ',', ' ');
            }
            $extrato		= trim(pg_fetch_result($res,$i,extrato));
            $posto          = trim(pg_fetch_result($res,$i,posto));
            $os          = trim(pg_fetch_result($res,$i,os));
            $qtde_pecas =  trim(pg_fetch_result($res,$i,qtde_pecas));
            $data_fechamento = mostra_data(trim(pg_fetch_result($res,$i,data_fechamento)));
			if($login_fabrica != 91){
			    $data_geracao	= trim(pg_fetch_result($res,$i,data_geracao));
			    $qtde_nf		= trim(pg_fetch_result($res,$i,qtde_nf));
			}else{
			    $extrato_sem_conferencia		= trim(pg_fetch_result($res,$i,'extrato_sem_conferencia'));
			}

            if($login_fabrica == 91){

                $cond_ext = ($extrato_sem_conferencia < $extrato and !empty($extrato_sem_conferencia)) ? " and tbl_extrato.extrato > $extrato_sem_conferencia " : " AND tbl_extrato.extrato >= $extrato ";
                $sql = "select tbl_extrato.extrato, tbl_extrato.data_geracao from tbl_extrato
                    JOIN tbl_posto ON tbl_extrato.posto = tbl_posto.posto
                    JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                    WHERE tbl_extrato.fabrica = $login_fabrica
					AND tbl_extrato.posto = $posto
					$cond_ext
                    GROUP BY tbl_extrato.extrato,tbl_extrato.posto, tbl_posto.nome,tbl_extrato.data_geracao
                    ORDER BY tbl_posto.nome, tbl_extrato.data_geracao;";

                $resultFor = pg_query($con,$sql);
                $extratos = "";
                $qtde_extrato_geral = 0;
                $qtdAux = pg_num_rows($resultFor);
                for($j=0;$j<$qtdAux;$j++){
                    $extrato = trim(pg_fetch_result($resultFor,$j,extrato));

                   if($j+1!=$qtdAux){
                       $virgula = ",";
                   }else{
                       $virgula = "";
                   }
                    if($j==0){
                    	$extratos .= $extrato.$virgula;
                    }else{
                    	$extratos .= $extrato.$virgula;
                    }
                    $qtde_extrato_geral += 1;
                    $qtde_geral += 1;
                }

            	$tbody .= "$codigo_posto;". utf8_encode("$nome").";". "$extratos;". "$qtde_extrato_geral\r\n";

            }elseif($login_fabrica == 50){
            	$tbody .= "$codigo_posto;". utf8_encode("$nome").";". "$os;"."$real . $qtde_km_calculada;"."$real . $mao_de_obra;". "$data_fechamento;". "$qtde_pecas \r\n";
            }else{
            	if(in_array($login_fabrica, array(153))){

            		$sqlNF = "
								SELECT DISTINCT
								   tbl_faturamento.nota_fiscal
								FROM
								   tbl_extrato_lgr
								JOIN tbl_faturamento_item ON tbl_faturamento_item.extrato_devolucao = tbl_extrato_lgr.extrato AND tbl_faturamento_item.peca = tbl_extrato_lgr.peca
								JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
								WHERE
								   tbl_extrato_lgr.extrato = $extrato;
							";

					$resNF = pg_query($con, $sqlNF);
					$nota_peca = "";

					if(pg_num_rows($resNF) > 0){
						for($a = 0; $a < pg_num_rows($resNF); $a++)
						{
							$nota_peca .= trim(pg_result($resNF, $a, nota_fiscal));

							if(($a + 1) < pg_num_rows($resNF))
							{
								$nota_peca .= ", ";
							}
						}

					}else{
						$nota_peca = "";
					}

            		$sqlPeca = "
							SELECT DISTINCT
							   tbl_peca.referencia,
							   tbl_peca.descricao,
							   tbl_extrato_lgr.qtde,
							   tbl_faturamento_item.preco,
							   (tbl_extrato_lgr.qtde * tbl_faturamento_item.preco) AS total,
							   SUM((tbl_extrato_lgr.qtde * tbl_faturamento_item.preco)) AS total_nf
							FROM
							   tbl_extrato_lgr
							JOIN tbl_faturamento_item ON tbl_faturamento_item.extrato_devolucao = tbl_extrato_lgr.extrato AND tbl_faturamento_item.peca = tbl_extrato_lgr.peca
							JOIN tbl_peca ON tbl_peca.peca = tbl_extrato_lgr.peca AND tbl_peca.fabrica = $login_fabrica
							WHERE
							   tbl_extrato_lgr.extrato = $extrato
							GROUP BY
							   tbl_peca.referencia,
							   tbl_peca.descricao,
							   tbl_extrato_lgr.qtde,
							   tbl_faturamento_item.preco
							";
							//echo $sqlPeca; exit;
					$resPeca = pg_query($con, $sqlPeca);
					$total_nf = 0;

					for($y = 0; $y < pg_num_rows($resPeca); $y++){
						$auxiliar_total = trim(pg_result($resPeca, $y, total));
						$total_nf += $auxiliar_total;
					}

					if(pg_num_rows($res) > 0){
						for($x = 0; $x < pg_num_rows($resPeca); $x++){
							$referencia = trim(pg_result($resPeca, $x, referencia));
							$descricao  = trim(pg_result($resPeca, $x, descricao));
							$qtde       = trim(pg_result($resPeca, $x, qtde));
							$preco      = trim(pg_result($resPeca, $x, preco));
							$total      = trim(pg_result($resPeca, $x, total));

							$preco      = number_format($preco, 2, '.', '');
							$total      = number_format($total, 2, '.', '');
							$total_nf   = number_format($total_nf, 2, '.', '');

		            		$tbody .=
		            					"$codigo_posto;"
		            					. utf8_encode("$nome")           .";"
		            					. "$extrato;"
		            					. "$data_geracao;"
										. "$referencia;"
										. utf8_encode("$descricao")      . ";"
										. "$qtde;"
										. utf8_encode("$preco")          . ";"
										. utf8_encode("$total")          . ";"
										/*. utf8_encode("$nota_peca")      . ";"
										. utf8_encode("$total_nf")       . ";"*/
										. "\r\n"
		            		;
		            	}
		            	$tbody .=
		            		utf8_encode("Referentes as NF's $nota_peca") . ";"
		            		. ";"
		            		. ";"
		            		. ";"
		            		. ";"
		            		. ";"
		            		. ";"
		            		. utf8_encode("Total da Nota:; $total_nf") . ";"
		            		. "\r\n"
		            	;
	            	}
            	}else {
            		$tbody .= "$codigo_posto;". utf8_encode("$nome").";". "$extrato;". "$data_geracao;". "\r\n";
            	}
            }
		}
		fwrite($file, $tbody);

	if (file_exists("/tmp/{$fileName}")) {
		system("mv /tmp/{$fileName} xls/{$fileName}");

		echo "xls/{$fileName}";
	}
	exit;
}

$layout_menu = "auditoria";
$title = ($login_fabrica == 91) ? traduz("RELATÓRIO DE EXTRATOS PENDENTES") : traduz("RELATÓRIO DE NÃO PREENCHIMENTO DO LGR");

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

<script type="text/javascript" charset="utf-8">
	$(function() {
		$.datepickerLoad(Array("data_final", "data_inicial"));
		$.autocompleteLoad(Array("posto,peca"));
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this), ["pesquisa_produto_acabado"]);
		});

        $("td[id^=lgr_]").click(function(){
            var dados = $(this).attr("id").split("_");
            var posto = dados[1];

            if ($(".tableOs#tableLgr_"+posto).css("display") == 'none') {
                $(".tableOs#tableLgr_"+posto).css("display","table-row");
            } else {
                $(".tableOs#tableLgr_"+posto).css("display","none");
            }
        });

        $("input[id^=todos_]").click(function(){
            var aux = $(this).attr("id").split("_");
            var posto = aux[1];

            if ($(this).is(":checked")) {
                $("input[rel="+posto+"]").prop("checked",true);
            } else {
                $("input[rel="+posto+"]").prop("checked",false);
            }
        });

        $("button[id*='_todos_']").click(function(e){
            e.preventDefault();
            var dados           = $(this).attr("id").split("_");
            var posto           = dados[2];
            var acao            = dados[0];
            var numero_postagem = $("#postagem_todos_"+posto).val();

            var os_item_marcados = [];

            if (numero_postagem == "") {
               alert('<?=traduz("Favor, incluir o número de Postagem")?>');
            } else {
                $("input[rel="+posto+"]:checked").each(function(k,v){
                    var aux2 = $(this).attr("name").split("_");
                    os_item_marcados.push(aux2[1]);
                });

                $.ajax({
                    url:"relatorio_lgr.php",
                    type:"POST",
                    dataType:"JSON",
                    data:{
                        ajax:true,
                        tipo:"bloqueio_multiplo",
                        os_item:os_item_marcados,
                        posto:posto,
                        numero_postagem:numero_postagem,
                        acao:acao
                    }
                })
                .done(function(data){
                    if (data.ok) {
                        $.each(data.osRetorno, function(key,value) {
    //                         console.log(value);
                            if (data.acao == "ocultar") {
                                $("button[id=devolver_"+value+"_"+os_item_marcados[key]+"]").removeClass("btn-danger").addClass("btn-success").text(traduz("Mostrar"));
                            } else {
                                $("button[id=devolver_"+value+"_"+os_item_marcados[key]+"]").removeClass("btn-success").addClass("btn-danger").text(traduz("Ocultar"));

                            }
                        });
                    }
                })
                .fail(function(){
                    alert('<?=traduz("Não foi possível modificar as peças marcadas")?>');
                   
                });
            }
        });

        $("button[id^=devolver_]").click(function(e){
            e.preventDefault();

            var dados = $(this).attr("id").split("_");
            var os = dados[1];
            var os_item = dados[2];
            var numero_postagem = $("#postagem_"+os_item).val();

            if (numero_postagem == "") {
                alert('<?=traduz("Favor, incluir o número de Postagem")?>');
            } else {
                $.ajax({
                    url:"relatorio_lgr.php",
                    type:"POST",
                    dataType:"JSON",
                    data:{
                        ajax:true,
                        tipo:"bloqueio_os",
                        os_bloqueio:os,
                        os_item:os_item,
                        numero_postagem:numero_postagem
                    }
                })
                .done(function(data){
                    if (data.ok) {
                        if (data.acao == "bloqueio") {
                            $("#devolver_"+os+"_"+os_item).removeClass("btn-danger").addClass("btn-success").text(traduz("Mostrar"));
                        } else {
                            $("#devolver_"+os+"_"+os_item).removeClass("btn-success").addClass("btn-danger").text(traduz("Ocultar"));
                        }
                    }
                })
                .fail(function(){
                    alert('<?=traduz("Não foi possível modificar o item da OS")?>');
                    
                });
            }
        });

	});

	function retorna_posto(retorno){
		$("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
	}

	function retorna_peca(retorno) {
		$("#peca").val(retorno.peca);
        $("#peca_referencia").val(retorno.referencia);
		$("#peca_descricao").val(retorno.descricao);
    }

    function VisalizaPecas(data_inicial,data_final,posto){
	    janela = window.open("visualizar_pecas_extratos.php?data_inicial=" +data_inicial+ "&data_final=" +data_final+ "&posto=" +posto);
		janela.focus();
	}

	function buscarPecas(extrato) {
		if (document.getElementById('div_sinal_' + extrato).innerHTML == '+') {
			$.ajax({
				url: "relatorio_lgr_ajax.php",
				type: "GET",
				data: {extrato : extrato},
				beforeSend: function(){
					$("#div_detalhe_"+extrato).html("<img src='a_imagens/ajax-loader.gif'>");
				},
				complete: function(data){
					$("#mostra_"+extrato).removeAttr("hidden");
					var dados = data.responseText;
					dados = dados.split("|");
					$("#div_detalhe_"+extrato).html(dados[1]);
					$("#div_sinal_"+extrato).html("-");
				}
			});
		} else {
			$("#mostra_"+extrato).attr("hidden", "hidden");
			document.getElementById('div_detalhe_' + extrato).innerHTML = "";
			document.getElementById('div_sinal_' + extrato).innerHTML = '+';
		}
	}

    function ver_nota(extrato, posto) {
        window.open(
          'extrato_posto_devolucao_lgr_novo_lgr.php?extrato='+extrato+'&posto='+posto,
          '_blank' // <- This is what makes it open in a new window.
        )
    }

</script>
<style type="text/css">
.verOs {
    cursor:pointer;
    color:#3199fa;
}
.tableOs {
    display:none;
}
</style>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
	<div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
	</div>
<?php
}
?>

<div class="row">
	<b class="obrigatorio pull-right">  * <?=traduz("Campos obrigatórios")?></b>
</div>

<form id="frm_relatorio" name="frm_relatorio" method="post" action="<?= $PHP_SELF?>" class="form-search form-inline tc_formulario">
	<div class='titulo_tabela '><?=traduz("Parâmetros de Pesquisa")?></div>
	<br />
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("data_inicial", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='data_inicial'><?=traduz("Data Inicial")?></label>
				<div class='controls controls-row'>
					<div class='span4'>
						<h5 class='asteristico'>*</h5>
						<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("data_final", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='data_final'><?=traduz("Data Final")?></label>
				<div class='controls controls-row'>
					<div class='span4'>
						<h5 class='asteristico'>*</h5>
						<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("codigo_posto", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='codigo_posto'><?=traduz("Código Posto")?></label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<?= $codigo_posto ?>" >
						<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("descricao_posto", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='descricao_posto'><?=traduz("Nome Posto")?></label>
				<div class='controls controls-row'>
					<div class='span12 input-append'>
						<input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<?= $descricao_posto ?>" >&nbsp;
						<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
<?php
    if(in_array($login_fabrica ,array(94,151))) {
    $produto_acabado = ($login_fabrica == 94) ? "pesquisa_produto_acabado='true'" : "";
?>
	<div class="row-fluid">

		<div class="span2"></div>

		<div class='span4'>
				<div class='control-group <?=(in_array("peca", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='peca_referencia'><?=traduz("Ref. Peças")?></label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" id="peca_referencia" name="peca_referencia" class='span12' maxlength="20" value="<? echo $peca_referencia ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search'></i></span>
							<input type="hidden" name="lupa_config" tipo="peca" parametro="referencia" <?=$produto_acabado?> />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("peca", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='peca_descricao'><?=traduz("Descrição Peça")?></label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<input type="text" id="peca_descricao" name="peca_descricao" class='span12' value="<? echo $peca_descricao ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="peca" parametro="descricao" <?=$produto_acabado?> />
						</div>
					</div>
				</div>
			</div>

		<div class="span2"></div>

	</div>
<?php
    }
?>
	<p>
		<br/>
		<button class='btn' type="button" onclick="if ($('#btn_acao').val() == 'PESQUISAR') { alert('Aguarde submissão'); } else { $('#btn_acao').val('PESQUISAR'); $('#frm_relatorio').submit(); }"><?=traduz("Pesquisar")?></button>
		<input type="hidden" name="peca" id="peca" value="<?=$peca?>">
		<input type='hidden' id="btn_acao" name='btn_acao' />
	</p>
	<br/>
</form>
<?
if (strlen($btn_acao) > 0 && count($msg_erro['msg']) == 0) {
	if ($login_fabrica == 114) {
		$cond_1 = " AND tbl_extrato.data_geracao > '2014-05-01' ";
	}

	$postos_permitidos_novo_processo = implode(",", $postos_permitidos_novo_processo);

    if($login_fabrica == 94 && $peca > 0){
        $where_pecas .= " AND tbl_extrato_lgr.peca = $peca ";
    } else if ($login_fabrica == 151 && $peca > 0) {
        $where_pecas .= " AND tbl_os_item.peca = $peca ";
    }

    if ($login_fabrica == 91) {

		$sql =  "select min(tbl_extrato.extrato) as extrato, tbl_extrato.posto, tbl_posto_fabrica.codigo_posto, tbl_posto.nome,
				(select min(fi.extrato_devolucao) from  tbl_faturamento
					join tbl_faturamento_item fi using(faturamento)
					where distribuidor = tbl_extrato.posto
					and fabrica = 91
					and conferencia isnull
				) as extrato_sem_conferencia
			from tbl_extrato
			join tbl_extrato_lgr  using(extrato)
			JOIN tbl_peca ON tbl_extrato_lgr.peca = tbl_peca.peca
			JOIN tbl_faturamento_item ON tbl_faturamento_item.peca = tbl_extrato_lgr.peca and tbl_faturamento_item.extrato_devolucao = tbl_extrato_lgr.extrato
			JOIN tbl_os_item USING(pedido, pedido_item)
			JOIN tbl_posto on tbl_extrato.posto = tbl_posto.posto
			join tbl_posto_fabrica  on tbl_extrato.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			where tbl_extrato.fabrica = $login_fabrica
			and (tbl_extrato_lgr.qtde_nf is null or tbl_extrato_lgr.qtde_nf = 0)
			AND (tbl_os_item.peca_obrigatoria or tbl_peca.devolucao_obrigatoria)
			AND tbl_extrato.extrato not in (
				select distinct fi.extrato_devolucao
				from tbl_faturamento
				join tbl_faturamento_item fi using(faturamento)
				where distribuidor = tbl_extrato.posto
				and fabrica = $login_fabrica
			)
            $sql_posto
            $sql_data
            group by tbl_extrato.posto, tbl_posto_fabrica.codigo_posto, tbl_posto.nome;";

    } else if (in_array($login_fabrica,array(50))) {

        $sql = "
            SELECT  tbl_os.os,
                    COALESCE(b.qtde_inspecionada,0) AS qtde_inspecionada,
                    tbl_posto_fabrica.codigo_posto, tbl_posto.nome,
                    (
                        SELECT  sum(tbl_os_item.qtde)
                        FROM    tbl_os_item
                        JOIN    tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                        WHERE   tbl_os_produto.os = tbl_os.os
                        AND     tbl_os_item.fabrica_i = $login_fabrica
                        AND     tbl_os_item.peca_obrigatoria = 't'
                    ) AS qtde_pecas,
                    tbl_os.data_fechamento,
                    tbl_os.qtde_km_calculada,
                    tbl_os.mao_de_obra
            FROM    tbl_os
            JOIN    tbl_os_produto          ON  tbl_os.os                   = tbl_os_produto.os
            JOIN    tbl_os_item             ON  tbl_os_produto.os_produto   = tbl_os_item.os_produto
            JOIN    tbl_os_extra            ON  tbl_os_extra.os             = tbl_os.os
            JOIN    tbl_faturamento_item    ON  (
                                                    tbl_faturamento_item.pedido     = tbl_os_item.pedido
                                                OR  tbl_faturamento_item.os_item    = tbl_os_item.os_item
                                                )
                                            AND tbl_faturamento_item.peca   = tbl_os_item.peca
            JOIN    tbl_faturamento         ON  tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
                                            AND tbl_faturamento.fabrica     = $login_fabrica
       LEFT JOIN    tbl_faturamento_item b  ON  b.os                        = tbl_os.os
                                            AND tbl_os.fabrica              = $login_fabrica
                                            AND tbl_os_item.peca            = b.peca
       LEFT JOIN    tbl_faturamento a       ON  a.faturamento               = b.faturamento
                                            AND a.fabrica                   = $login_fabrica
                                            AND a.distribuidor              = tbl_os.posto
            JOIN    tbl_posto               ON  tbl_posto.posto             = tbl_os.posto
            JOIN    tbl_posto_fabrica       ON  tbl_posto_fabrica.posto     = tbl_posto.posto
                                            AND tbl_posto_fabrica.fabrica   = $login_fabrica
            WHERE   tbl_os_item.peca_obrigatoria =  't'
            AND     tbl_os.finalizada IS NOT NULL
            AND     tbl_os.finalizada between '$xdata_inicial 00:00:00' and  '$xdata_final 23:59:59'
            AND     b.faturamento_item IS NULL
            AND     tbl_os.fabrica = $login_fabrica
            AND     tbl_os_extra.extrato is null
            AND     (
                        tbl_os_item.qtde > COALESCE(b.qtde_inspecionada,0)
                    OR  b.qtde_inspecionada IS NULL
                    )
            $sql_posto

      GROUP BY      tbl_os.os,
                    b.qtde_inspecionada,
                    tbl_posto_fabrica.codigo_posto,
                    tbl_posto.nome,
                    tbl_os_item.qtde,
                    tbl_os.data_fechamento,
                    tbl_os.qtde_km_calculada,
                    tbl_os.mao_de_obra
      ORDER BY      tbl_posto.nome,
                    tbl_os.os";
    } else if ($login_fabrica == 151) {
        $sql = "
            SELECT  tbl_posto_fabrica.codigo_posto,
                    tbl_posto.nome,
                    tbl_posto.posto,
                    (
                        SELECT  COUNT(tbl_os_item.qtde)
                        FROM    tbl_os_item
                        JOIN    tbl_os_produto          USING(os_produto)
                        JOIN    tbl_os                  USING(os)
                        JOIN    tbl_os_extra            USING(os)
                   LEFT JOIN    tbl_faturamento_item    ON  tbl_faturamento_item.peca       = tbl_os_item.peca
                                                        AND tbl_faturamento_item.os         = tbl_os.os
                                                        AND tbl_faturamento_item.os_item    IS NULL
                        WHERE   tbl_os.posto                            = tbl_posto.posto
                        AND     tbl_os_item.fabrica_i                   = $login_fabrica
                        AND     tbl_os_item.peca_obrigatoria            = 't'
                        AND     tbl_os_extra.extrato                    IS NULL
                        AND     tbl_faturamento_item.faturamento_item   IS NULL
                        AND     JSON_FIELD('bloqueio',tbl_os_item.parametros_adicionais) IS NOT NULL
                        $where_pecas
                    ) AS qtde_pecas
            FROM    tbl_os
            JOIN    tbl_os_produto          ON  tbl_os.os                   = tbl_os_produto.os
            JOIN    tbl_os_item             ON  tbl_os_produto.os_produto   = tbl_os_item.os_produto
            JOIN    tbl_os_extra            ON  tbl_os_extra.os             = tbl_os.os
                                            AND tbl_os_extra.extrato        IS NULL
            JOIN    tbl_faturamento_item    ON  (
                                                tbl_faturamento_item.pedido     = tbl_os_item.pedido
                                            OR  tbl_faturamento_item.os_item    = tbl_os_item.os_item
                                            )
                                            AND tbl_faturamento_item.peca   = tbl_os_item.peca
            JOIN    tbl_faturamento         ON  tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
                                            AND tbl_faturamento.fabrica     = $login_fabrica
       LEFT JOIN    tbl_faturamento_item b  ON  b.os                        = tbl_os.os
                                            AND tbl_os.fabrica              = $login_fabrica
                                            AND tbl_os_item.peca            = b.peca
       LEFT JOIN    tbl_faturamento a       ON  a.faturamento               = b.faturamento
                                            AND a.fabrica                   = $login_fabrica
                                            AND a.distribuidor              = tbl_os.posto
            JOIN    tbl_posto               ON  tbl_posto.posto             = tbl_os.posto
            JOIN    tbl_posto_fabrica       ON  tbl_posto_fabrica.posto     = tbl_posto.posto
                                            AND tbl_posto_fabrica.fabrica   = $login_fabrica
            WHERE   tbl_os_item.peca_obrigatoria                                    IS TRUE
            AND     (
                        SELECT  COUNT(tbl_os_item.qtde)
                        FROM    tbl_os_item
                        JOIN    tbl_os_produto          USING(os_produto)
                        JOIN    tbl_os                  USING(os)
                        JOIN    tbl_os_extra            USING(os)
                   LEFT JOIN    tbl_faturamento_item    ON  tbl_faturamento_item.peca       = tbl_os_item.peca
                                                        AND tbl_faturamento_item.os         = tbl_os.os
                                                        AND tbl_faturamento_item.os_item    IS NULL
                        WHERE   tbl_os.posto                            = tbl_posto.posto
                        AND     tbl_os_item.fabrica_i                   = $login_fabrica
                        AND     tbl_os_item.peca_obrigatoria            = 't'
                        AND     tbl_os_extra.extrato                    IS NULL
                        AND     tbl_faturamento_item.faturamento_item   IS NULL
                        AND     JSON_FIELD('bloqueio',tbl_os_item.parametros_adicionais) IS NOT NULL
                        $where_pecas
                    ) > 0
            AND     tbl_os.fabrica                                                  = $login_fabrica
            AND     tbl_faturamento.emissao BETWEEN '$xdata_inicial' AND '$xdata_final'
            AND     (
                        tbl_os_item.qtde > COALESCE(b.qtde_inspecionada,0)
                    OR  b.qtde_inspecionada IS NULL
                    )
            AND     tbl_os.os NOT IN (
                        SELECT  tbl_faturamento_item.os
                        FROM    tbl_faturamento_item
                        WHERE   tbl_faturamento_item.os_item IS NULL
                        AND     tbl_faturamento_item.os = tbl_os.os
                    )
            AND     tbl_os.data_fechamento IS NULL
            $where_pecas
            $sql_posto
      GROUP BY      tbl_posto_fabrica.codigo_posto,
                    tbl_posto.posto,
                    tbl_posto.nome
      ORDER BY      tbl_posto.nome
        ";
    } else {
		$cond_liberado =  ($login_fabrica == 3) ? " " : " AND tbl_extrato.liberado notnull ";
        $sql = "SELECT tbl_posto_fabrica.codigo_posto,
                    tbl_posto.nome,
                    tbl_posto.posto,
					tbl_extrato.extrato,
					TO_CHAR(tbl_extrato.data_geracao,'DD/MM/YYYY') AS data_geracao,
					X.qtde_nf
			FROM tbl_extrato
			JOIN tbl_posto USING(posto)
			JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN (	SELECT  DISTINCT tbl_extrato_lgr.posto, tbl_extrato_lgr.extrato, (SELECT COUNT(el.*) FROM tbl_extrato_lgr el WHERE tbl_extrato_lgr.posto = el.posto AND tbl_extrato_lgr.extrato = el.extrato AND (tbl_extrato_lgr.qtde_nf IS NULL OR tbl_extrato_lgr.qtde_nf = 0)) AS qtde_nf
				FROM tbl_extrato_lgr
				JOIN tbl_extrato USING(extrato)
				JOIN tbl_peca ON tbl_peca.peca = tbl_extrato_lgr.peca
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_extrato.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				LEFT JOIN tbl_extrato_devolucao ON tbl_extrato_devolucao.extrato     = tbl_extrato.extrato
				LEFT JOIN tbl_faturamento       ON tbl_faturamento.extrato_devolucao = tbl_extrato.extrato AND tbl_faturamento.distribuidor = tbl_extrato_lgr.posto
				WHERE tbl_extrato.fabrica = $login_fabrica
				$where_pecas
				AND tbl_faturamento.posto                   IS NULL
				$cond_liberado
				AND tbl_extrato_devolucao.extrato_devolucao IS NULL
				AND (
					(tbl_extrato.extrato > 240000
					AND tbl_extrato.posto IN ($postos_permitidos_novo_processo)
					AND tbl_peca.produto_acabado IS TRUE
					) OR (
					tbl_extrato.extrato > 240000
					AND tbl_extrato.posto NOT IN ($postos_permitidos_novo_processo)
					) OR (
					tbl_extrato.extrato < 240000
					)
				)
				$sql_posto
				$sql_data
				$cond_1
				AND (tbl_extrato_lgr.qtde_nf IS NULL OR tbl_extrato_lgr.qtde_nf = 0)
			) X ON X.extrato  = tbl_extrato.extrato
            ORDER BY tbl_posto.nome,tbl_posto.posto,tbl_extrato.data_geracao";
	}
// 	exit(nl2br($sql));
	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) { ?>
		<? if (in_array($login_fabrica,array(151,153))) {
				$table_houver = "";
			} else {
				$table_houver = "table-hover";
			}
?>
		<table id="relatorio_lgr" class='table table-striped table-bordered <?=$table_houver;?>  table-fixed'>
			<thead>
				<tr class='titulo_tabela'>
				<?php $colspan = (in_array($login_fabrica,array(50,151))) ? "7" : "5"; ?>
					<th colspan="<?=$colspan?>"><?=(in_array($login_fabrica,array(50,151))) ? traduz("Controle de OS Pendentes (Aguardando Preenchimento de NF)"): traduz("Controle de Extrato Pendente").";"?></th>
				</tr>
				<tr class='titulo_coluna'>
					<th><?=traduz("Código Posto")?></th>
					<th><?=traduz("Nome Posto")?></th>
					<?php
					if ($login_fabrica != 151) {
					?>
					<th><?echo ($login_fabrica != 50 ) ? traduz("Extrato"): "OS"?></th>

    				   <?php if($login_fabrica == 50){
    					     echo "<th>".traduz("KM")."</th>
    						 <th>".traduz("Mão de Obra")."</th>";
    				   }

					?>

					<? if (!in_array($login_fabrica, array(91))) { ?>
					<th><?echo ($login_fabrica != 50 )? traduz("Data do Extrato"): traduz("Data Fechamento OS")?></th>
					<? } if (in_array($login_fabrica, array(91))) { ?>
						<th><?=traduz("Total Extratos Pendentes")?></th>
						<th><?=traduz("Visualização Geral das Peças")?></th>
						<!-- <th>Total Peças Pendentes</th> -->
					<?
                        }

                        if ($login_fabrica == 175) {
                            echo "<th> AÇÕES </th>";
                        }
					}
					?>
					<?php if(in_array($login_fabrica,array(50,151))){
?>
                        <th><?=traduz("Quant. Peças")?></th>
<?php
					}
					?>
				</tr>
			</thead>
			<tbody>
<?

				$posto_ant = "";
				$posto_extrato_unico = false;
				$qtde_resultado = pg_num_rows($res);
				$qtde_nf_ant = 0;
				$qtde_extrato_geral = 1;
                $qtde_geral = 0;

                for ($i = 0; $i < $qtde_resultado; $i++) {
                    $codigo_posto       = trim(pg_fetch_result($res,$i,codigo_posto));
                    $nome               = trim(pg_fetch_result($res,$i,nome));
                    $extrato            = trim(pg_fetch_result($res,$i,extrato));
                    $posto              = trim(pg_fetch_result($res,$i,posto));
                    $os                 = trim(pg_fetch_result($res,$i,os));
                    $qtde_pecas         = trim(pg_fetch_result($res,$i,qtde_pecas));
                    $data_fechamento    = trim(pg_fetch_result($res,$i,data_fechamento));

                    if ($login_fabrica == 151 && $qtde_pecas == 0) {
                        continue;
                    }

                    if ($login_fabrica == 50) {
						$qtde_km_calculada	= trim(pg_fetch_result($res,$i,qtde_km_calculada));

						if (strlen(trim($qtde_km_calculada))==0) {
							$qtde_km_calculada = "0";
						}

						$mao_de_obra	= trim(pg_fetch_result($res,$i,mao_de_obra));
						$mao_de_obra 	= number_format($mao_de_obra, 2, ',', ' ');
						$qtde_km_calculada = number_format($qtde_km_calculada, 2, ',', ' ');

                    }
                    if ($login_fabrica != 91) {
                        $data_geracao	= trim(pg_fetch_result($res,$i,data_geracao));
                        $qtde_nf		= trim(pg_fetch_result($res,$i,qtde_nf));
					} else {
                        $extrato_sem_conferencia		= trim(pg_fetch_result($res,$i,'extrato_sem_conferencia'));
					}


					if (in_array($login_fabrica, array(91))) {

                        $cond_ext = ($extrato_sem_conferencia < $extrato and !empty($extrato_sem_conferencia)) ? " and tbl_extrato.extrato > $extrato_sem_conferencia " : " AND tbl_extrato.extrato >= $extrato ";
                        $sql = "select tbl_extrato.extrato, tbl_extrato.data_geracao from tbl_extrato
                            JOIN tbl_posto ON tbl_extrato.posto = tbl_posto.posto
                            JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                            WHERE tbl_extrato.fabrica = $login_fabrica
							AND tbl_extrato.posto = $posto
							$cond_ext
                            GROUP BY tbl_extrato.extrato,tbl_extrato.posto, tbl_posto.nome,tbl_extrato.data_geracao
                            ORDER BY tbl_posto.nome, tbl_extrato.data_geracao;";

                        $resultFor = pg_query($con,$sql);
                        $extratos = "";
                        $qtde_extrato_geral = 0;
                        $qtdAux = pg_num_rows($resultFor);
                        for($j=0;$j<$qtdAux;$j++){
                            $extrato = trim(pg_fetch_result($resultFor,$j,extrato));

                           if($j+1!=$qtdAux){
                               $virgula = ",";
                           }else{
                               $virgula = "";
                           }
                            if($j==0){
                            	$extratos .= "<b><a href='extrato_consulta_os.php?extrato=$extrato'>".$extrato."</a></b>$virgula ";
                            }else{
                            	$extratos .= "<a href='extrato_consulta_os.php?extrato=$extrato'>".$extrato."</a>$virgula  ";
                            }
                            $qtde_extrato_geral += 1;
                            $qtde_geral += 1;
                        }

                       ?>
                        <tr class='conteudo'>
                            <td class="tac"><?=$codigo_posto?></td>
                            <td class="tal"><?=$nome?></td>
                            <td class="tac"><?=$extratos?></td>
                            <td class="tac"><?=$qtde_extrato_geral?></td>
                            <td>
                                <input type='button' class='btn' value='Visualizar' onclick="javascript: VisalizaPecas('<?=$xdata_inicial?>','<?=$xdata_final?>','<?=$posto?>')"/>
                           </td>
                       </tr>
<?php
					} else if (in_array($login_fabrica,array(50,151))) {
                        if ($login_fabrica == 151) {
                            $class = "verOs";
                            $id = "id='lgr_$posto'";
                        }
?>
						<tr class='conteudo'>
							<td class="tac"><?= $codigo_posto ?></td>
							<td class="tal <?=$class?>" <?=$id?>><?=$nome?></td>
<?php
							if ($login_fabrica != 151) {
?>
							<td class="tac"><a target="_blank" href='os_press.php?os=<?=$os?>'><?= $os?></a></td>
							<td class="tal" nowrap> <?= $real . $qtde_km_calculada?></td>
							<td class="tal" nowrap> <?= $real . $mao_de_obra?></td>
							<td style='text-align: center;'><?echo mostra_data($data_fechamento) ?></td>
<?php
							}
?>
                            <td style='text-align: center;'><?=$qtde_pecas?></td>
						</tr>
<?php
                        if ($login_fabrica == 151) {
?>
                        <tr class="tableOs" id="tableLgr_<?=$posto?>">
                            <td colspan="100%">
                                <table class='table table-striped table-bordered table-fixed'>
                                    <thead>
                                        <tr class='titulo_coluna'>
<?php
                            if (!empty($where_pecas)) {
?>
                                            <th><?=traduz("SELECIONAR TODOS")?> <br /> <input type="checkbox" id="todos_<?=$posto?>" name="todos" value='t' /></th>
<?php
                            }
?>
                                            <th><?=traduz("OS")?></th>
                                            <th><?=traduz("PEDIDO")?></th>
                                            <th><?=traduz("PEÇAS")?></th>
                                            <th><?=traduz("M.O.")?></th>
                                            <th><?=traduz("DATA FECHAMENTO")?></th>
                                            <th><?=traduz("QTDE. PENDENTE")?></th>
                                            <th><?=traduz("NÚMERO POSTAGEM")?></th>
                                            <th><?=traduz("AÇÕES")?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
<?php
                            $sqlPecasLgr = "
                                SELECT  tbl_os.sua_os,
                                        tbl_os.os,
                                        tbl_os.mao_de_obra,
                                        TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY')    AS data_fechamento,
                                        tbl_peca.referencia,
                                        tbl_peca.descricao,
                                        CASE WHEN JSON_FIELD('bloqueio',tbl_os_item.parametros_adicionais)::BOOL IS TRUE
                                            THEN 'Devolver'
                                            ELSE 'Não Devolver'
                                        END                                             AS devolver,
                                        JSON_FIELD('numero_postagem',tbl_os_item.parametros_adicionais) AS numero_postagem,
                                        SUM(tbl_os_item.qtde)                           AS qtde,
                                        tbl_os_item.pedido,
                                        tbl_os_item.os_item
                                FROM    tbl_os_item
                                JOIN    tbl_os_produto      USING(os_produto)
                                JOIN    tbl_os              USING(os)
                                JOIN    tbl_os_extra        USING(os)
                                JOIN    tbl_peca            USING(peca)
                           LEFT JOIN    tbl_faturamento_item    ON  tbl_faturamento_item.peca       = tbl_peca.peca
                                                                AND tbl_faturamento_item.os         = tbl_os.os
                                                                AND tbl_faturamento_item.os_item    IS NULL
                                WHERE   tbl_os.fabrica                          = $login_fabrica
                                AND     tbl_os.posto                            = $posto
                                AND     tbl_os_extra.extrato                    IS NULL
                                AND     tbl_os_extra.extrato                    IS NULL
                                AND     tbl_os_item.peca_obrigatoria            IS TRUE
                                AND     tbl_faturamento_item.faturamento_item   IS NULL
                                AND     JSON_FIELD('bloqueio',tbl_os_item.parametros_adicionais) IS NOT NULL
                                $where_pecas
                          GROUP BY      tbl_os.sua_os,
                                        tbl_os.os,
                                        tbl_os_item.parametros_adicionais,
                                        tbl_os.mao_de_obra,
                                        tbl_os.data_fechamento,
                                        tbl_peca.referencia,
                                        tbl_peca.descricao,
                                        tbl_os_item.qtde,
                                        tbl_os_item.pedido,
                                        tbl_os_item.os_item

                            ";
//                             echo nl2br($sqlPecasLgr);
                            $resPecasLgr = pg_query($con,$sqlPecasLgr);

                            while ($pecas = pg_fetch_object($resPecasLgr)) {
?>
                                        <tr>
<?php
                                if (!empty($where_pecas)) {
?>
                                            <td class="tac"><input type="checkbox" name="acao_<?=$pecas->os_item?>" rel="<?=$posto?>" value='t' /></td>
<?php
                                }
?>
                                            <td class="tac"><a href="os_press.php?os=<?=$pecas->os?>" target="_blank"><?=$pecas->sua_os?></a></td>
                                            <td class="tac"><?=$pecas->pedido?></td>
                                            <td ><?=$pecas->referencia." - ".$pecas->descricao?></td>
                                            <td class="tar"><?= $real . number_format($pecas->mao_de_obra,2,',','.')?></td>
                                            <td class="tac"><?=$pecas->data_fechamento?></td>
                                            <td class="tac"><?=$pecas->qtde?></td>
                                            <td class="tac"><input type="text" name="postagem_<?=$pecas->os_item?>" id="postagem_<?=$pecas->os_item?>" value="<?=$pecas->numero_postagem?>" /></td>
                                            <td>
<?php
                                if ($pecas->devolver == "Devolver") {
?>
                                                <button class="btn btn-success" id="devolver_<?=$pecas->os?>_<?=$pecas->os_item?>"><?=traduz("Mostrar")?></button>
<?php
                                } else {
?>
                                                <button class="btn btn-danger" id="devolver_<?=$pecas->os?>_<?=$pecas->os_item?>"><?=traduz("Ocultar")?></button>
<?php
                                }
?>
                                            </td>
                                        </tr>
<?php
                            }
?>
                                    </tbody>
<?php
                             if (!empty($where_pecas)) {
?>
                                    <tfoot>
                                        <tr>
                                            <td colspan="100%" >
                                                <?=traduz("Número Postagem Selecionados:")?>
                                                <input type="text" name="postagem_todos_<?=$posto?>" id="postagem_todos_<?=$posto?>" value="" />
                                                <button class="btn btn-danger" id="ocultar_todos_<?=$posto?>"><?=traduz("Ocultar Todos")?></button>
                                                <button class="btn btn-success" id="mostrar_todos_<?=$posto?>"><?=traduz("Mostrar Todos")?></button>
                                            </td>
                                        </tr>
                                    </tfoot>
<?php
                            }
?>
                                </table>
                            </td>
                        </tr>
<?php
                        }
					} else {
?>
						<tr class='conteudo'>
							<td class="tac"><?= ($codigo_posto != $posto_ant) ? $codigo_posto : ""; ?></td>
							<td class="tal"><?= ($codigo_posto != $posto_ant) ? $nome : ""; ?></td>
							<? if($login_fabrica == 94){ ?>
								<td class="tac">
									<a href="relatorio_pecas_extrato_lgr.php?extrato=<?php echo $extrato?>" rel="shadowbox[]"><?= $extrato; ?></a>
								</td>
							<? } else if($login_fabrica == 153){ ?>
								<td onmouseover='this.style.cursor="pointer"'; onclick="buscarPecas(<?=$extrato;?>)">
									<? echo "$extrato<div id=div_sinal_$extrato>+</div>"; ?>
								</td>
							<? } else { ?>
								<td class="tac">
									<?=$extrato;?>
								</td>
							<? } ?>
							<td nowrap class="tac"><?= $data_geracao; ?></td>
                            <?php if ($login_fabrica == 175) { ?>
                                <td class="tac"><button class="btn btn-info" onClick="ver_nota(<?=$extrato?>,<?=$posto?>);"> Ver Espelho da Nota</button></td>
                            <?php } ?>
						</tr>
						<tr>
							<? echo "<td hidden colspan='4' id='mostra_$extrato'><div id='div_detalhe_$extrato'></div></td>"; ?>
						</tr>
					<? }

					if(!in_array($login_fabrica, array(3))){
						$posto_ant = $codigo_posto;
					}

                }

                if($login_fabrica == 91){
                    $qtde_resultado = $qtde_geral;
                }
?>
			</tbody>
<?php
        if ($login_fabrica != 151) {
?>
			<tfoot>
				<tr>
					<td colspan="<?=$colspan?>" class="tac"><b>Total de <?php echo ($login_fabrica == 50)? "Postos" : "Extratos" ?>: <?= $qtde_resultado; ?></b></td>
				</tr>

			</tfoot>
<?php
        }
?>
		</table>
		<br>
		<?php
        if ($login_fabrica != 151) {
		 	$jsonPOST = excelPostToJson($_POST); ?>
			<div id='gerar_excel' class="btn_excel">
				<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
				<span><img src='imagens/excel.png' /></span>
				<span class="txt"><?=traduz("Gerar Arquivo Excel")?></span>
			</div>


<?php
        }
	} else {
?>
		<br />
		<br />
		<div class="alert"><?=traduz("Nenhum resultado encontrado")?></div>
		<br />
		<br />
<?php
	}
}

include "rodape.php";
?>
