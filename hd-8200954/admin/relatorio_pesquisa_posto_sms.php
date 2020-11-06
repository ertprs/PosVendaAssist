<?php 

	$sqlPesquisa = "SELECT pesquisa, descricao as nome_pesquisa from tbl_pesquisa WHERE categoria = 'posto_sms' and fabrica = $login_fabrica and ativo = true ORDER BY pesquisa desc limit 1";
	$resPesquisa = pg_query($sqlPesquisa);
	if(pg_num_rows($resPesquisa)>0){
		$pesquisaId = pg_fetch_result($resPesquisa, 0, pesquisa);
		$nome_pesquisa = pg_fetch_result($resPesquisa, 0, nome_pesquisa);
	}else{
		echo "Pesquisa não encontrada";
		exit;
	}

    $path_link    = 'xls/';
    $path         = 'xls/';
    $path_tmp     = '/tmp/';
    $arquivo_link = array();

	$arquivo_nome     = "relatorio_pesquisas_pergunta_$login_fabrica"."_posto_sms.html";
	$arquivo_nome_xls = "relatorio_pesquisas_pergunta_$login_fabrica"."_posto_sms.xls";

	$arquivo_completo     = $path.$arquivo_nome;
	$arquivo_completo_xls = $path.$arquivo_nome_xls;

	echo `rm $arquivo_completo `;
	echo `rm $arquivo_completo_xls `;

	$fp = fopen($arquivo_completo,"w");

	fputs($fp,"<html>");
	fputs($fp,"<body>");

	fputs($fp,"<table border='1'>");

	$cabecalho .= "<tr>";
    	$cabecalho .= "<td><b>Data Inicial</b></td>";
    	$cabecalho .= "<td><b>Data Final</b></td>";
    	
    	$cabecalho .= "<td><b>Tema</b></td>";
    	$cabecalho .= "<td><b>Local treinamento</b></td>";
    	$cabecalho .= "<td><b>Telefone</b></td>";
    	$cabecalho .= "<td><b>Código do Posto</b></td>";
    	$cabecalho .= "<td><b>Nome do Posto</b></td>";
    	$cabecalho .= "<td><b>Nome do Técnico</b></td>";
    	$cabecalho .= "<td><b>Status</b></td>";
    	$cabecalho .= "<td><b>Pesquisa</b></td>";
        $cabecalho .= "<td><b>Data Resposta</b></td>";
    
    $sql = "SELECT  tbl_pergunta.descricao,
                    tbl_pesquisa_pergunta.ordem,
                    tbl_pergunta.pergunta
            FROM    tbl_pergunta
            JOIN    tbl_pesquisa_pergunta using (pergunta)
            WHERE   tbl_pesquisa_pergunta.pesquisa  = $pesquisaId
            AND     tbl_pergunta.ativo              IS TRUE
            AND     tbl_pergunta.fabrica            = $login_fabrica
            AND     tbl_pergunta.tipo_resposta      IS NOT NULL
      ORDER BY      tbl_pesquisa_pergunta.ordem ";
    $resPerguntasTipo = pg_query($con,$sql);

    for ($i=0; $i < pg_num_rows($resPerguntasTipo); $i++) {

        $descPergunta       = pg_fetch_result($resPerguntasTipo, $i, 'descricao');
        $ordemPergunta      = pg_fetch_result($resPerguntasTipo, $i, 'ordem');
        $todasPerguntas[]   = pg_fetch_result($resPerguntasTipo, $i, 'pergunta');
        $cabecalho .= "<th nowrap>$ordemPergunta - $descPergunta </th>";
    }

    $cabecalho .= "</tr>";

	fputs($fp,$cabecalho);

    if ($login_fabrica == 1) {
        $join_posto = "JOIN tbl_treinamento_posto on tbl_treinamento_posto.treinamento = tbl_treinamento.treinamento and tbl_treinamento_posto.ativo = 'true'";
    } else {
        $join_posto = "JOIN tbl_treinamento_posto on tbl_treinamento_posto.treinamento = tbl_treinamento.treinamento";
    }

	$sqlTreinamento = "SELECT 
                    tbl_treinamento.treinamento, 
                    tbl_treinamento.data_inicio, 
                    tbl_treinamento.data_fim, 
                    tbl_treinamento.titulo, 
                    tbl_treinamento.local, 
                    tbl_treinamento_posto.posto, 
                    tbl_treinamento_posto.tecnico, 
                    tbl_treinamento.data_finalizado,
                    tbl_posto.nome as nome_posto, 
                    tbl_posto_fabrica.codigo_posto,
                    tbl_tecnico.nome as nome_tecnico, 
                    tbl_tecnico.celular as telefone_tecnico 
                    FROM tbl_treinamento 
                    $join_posto
                    JOIN tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_treinamento_posto.posto and tbl_posto_fabrica.fabrica = $login_fabrica 
                    JOIN tbl_posto on tbl_posto.posto = tbl_posto_fabrica.posto

                    JOIN tbl_tecnico on tbl_tecnico.tecnico = tbl_treinamento_posto.tecnico

                    WHERE tbl_treinamento.data_finalizado  BETWEEN '$aux_data_inicial' AND '$aux_data_final'
                    and tbl_treinamento.fabrica = $login_fabrica ";
        $resTreinamento = pg_query($con, $sqlTreinamento);
        //echo nl2br($sqlTreinamento); 

        for($t=0; $t<pg_num_rows($resTreinamento); $t++){
            $treinamento        = pg_fetch_result($resTreinamento, $t, treinamento);
            $data_inicio        = substr(mostra_data(pg_fetch_result($resTreinamento, $t, data_inicio)),0,10);
            $data_fim           = substr(mostra_data(pg_fetch_result($resTreinamento, $t, data_fim)),0,10);
            $titulo             = utf8_decode(pg_fetch_result($resTreinamento, $t, titulo));
            $local              = pg_fetch_result($resTreinamento, $t, local);
            $posto              = pg_fetch_result($resTreinamento, $t, posto);
            $tecnico            = pg_fetch_result($resTreinamento, $t, tecnico);
            $data_finalizado    = pg_fetch_result($resTreinamento, $t, data_finalizado);
            $nome               = pg_fetch_result($resTreinamento, $t, nome);
            $nome_posto         = pg_fetch_result($resTreinamento, $t, nome_posto);
            $codigo_posto         = pg_fetch_result($resTreinamento, $t, codigo_posto);
            $nome_tecnico         = pg_fetch_result($resTreinamento, $t, nome_tecnico);
            $telefone_tecnico         = pg_fetch_result($resTreinamento, $t, telefone_tecnico);

            $linhasResposta .= "<tr>";

            $linhasResposta .= "<td>$data_inicio</td>";
            $linhasResposta .= "<td>$data_fim</td>";
            $linhasResposta .= "<td>$titulo</td>";
            $linhasResposta .= "<td>$local</td>";
            $linhasResposta .= "<td>$telefone_tecnico</td>";
            $linhasResposta .= "<td>$codigo_posto</td>";
            $linhasResposta .= "<td>$nome_posto</td>";
            $linhasResposta .= "<td>$nome_tecnico</td>";


            $sqlResposta = "SELECT  tbl_resposta.data_input, tbl_resposta.txt_resposta   ,
                            tbl_tipo_resposta.tipo_descricao,
                            tbl_resposta.pergunta
                            FROM    tbl_resposta
                            JOIN    tbl_pesquisa            ON  tbl_pesquisa.pesquisa           = tbl_resposta.pesquisa
                            JOIN    tbl_pergunta            ON  tbl_pergunta.pergunta           = tbl_resposta.pergunta
                            JOIN    tbl_tipo_resposta       ON  tbl_tipo_resposta.tipo_resposta = tbl_pergunta.tipo_resposta
                            JOIN    tbl_pesquisa_pergunta   ON  tbl_pergunta.pergunta           = tbl_pesquisa_pergunta.pergunta
                            AND tbl_pesquisa.pesquisa           = tbl_pesquisa_pergunta.pesquisa
                            WHERE   tbl_pesquisa.fabrica    = $login_fabrica
                            AND     tbl_pergunta.ativo      IS TRUE
                            AND     tbl_resposta.pesquisa   = ".$pesquisaId."
                            AND tbl_resposta.tecnico = $tecnico
                            AND campos_adicionais->>'treinamento' = '$treinamento'
                            ORDER BY      tbl_pesquisa_pergunta.ordem";
            $resResposta = pg_query($con, $sqlResposta);

            if(pg_num_rows($resResposta)==0){

                $linhasResposta .= "<td>Pendente</td>";
                $linhasResposta .= "<td>$nome_pesquisa</td>";
                $linhasResposta .= "<td>$data_resposta</td>";

            }else{               
                $linhasResposta .= "<td>Finalizada</td>";
                $linhasResposta .= "<td>$nome_pesquisa</td>";

                for($r=0; $r<pg_num_rows($resResposta); $r++){
                    $txt_resposta       = pg_fetch_result($resResposta, $r, txt_resposta);
                    $tipo_descricao     = pg_fetch_result($resResposta, $r, tipo_descricao);
                    $pergunta           = pg_fetch_result($resResposta, $r, pergunta);
                    $data_resposta         = substr(mostra_data(pg_fetch_result($resResposta, $r, data_input)),0,10);

                    if($r == 0){
                        $linhasResposta .= "<td>$data_resposta</td>";
                    }
                    $linhasResposta .= "<td>$txt_resposta</td>";
                }
            }
            
            $linhasResposta .= "</tr>";
        }

	fputs($fp,$linhasResposta);

    $xlsTableEnd = "</table>";
    fputs($fp,$xlsTableEnd);

    fputs($fp,"</body>");
    fputs($fp,"</body>");
    fputs($fp,"</html>");

    fclose($fp);

    $arquivo_nome = rename($arquivo_completo, $arquivo_completo_xls);
    $value = $path_link.$arquivo_nome_xls;

?>

<a href="<?=$value?>" target="_blank" >
        <img src="imagens/excel.gif" alt="Download Excel">
    <?php
    echo "Download Arquivo XLS do tipo: $nome_pesquisa";
    ?>
</a>
<br>