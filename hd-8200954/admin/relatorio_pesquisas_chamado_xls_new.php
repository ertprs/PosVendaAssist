<?php

$msg_erro = array();

$style = "
  .titulo_tabela{
     background-color:#596d9b; font: bold 14px \"Arial\"; color:#FFFFFF; text-align:center;
  }

  .titulo_coluna{
     background-color:#596d9b !important; font: bold 11px \"Arial\"; color:#FFFFFF; text-align:center;
  }

  table.tabela{
    width:700px;
    margin:auto;
    background-color: #F7F5F0;
  }

  table.tabela tr td{
     font-family: verdana;
     font-size: 11px;
     border-collapse: collapse;
     border:1px solid #596d9b;
  }
";


//VERIFICA SE O USUARIO ESPECIFICOU O TIPO DA PESQUISA
//ESTA VARIAVEL "$tipo_pesquisa_a_gerar" vai receber ou
//	o tipo de pergunta que o usuario selecionou, ou vai
//	receber os tipos de pergunta que estãcadastrados no sistema

    if($login_fabrica == 129 and strlen(trim($nome_pesquisa))> 0 ){
        $rinnai_pesquisa = " AND tbl_pesquisa.pesquisa = $nome_pesquisa ";
    }

    if (in_array($login_fabrica, [1])) {
        $pesquisa_categoria = $pesquisa;
    }

if ($tipo_pesquisa){
	$tipo_pesquisa_a_gerar[] = $tipo_pesquisa;
}else{
	if($login_fabrica == 52){
    $sql = "SELECT  tbl_pesquisa.pesquisa
    FROM    tbl_pesquisa
    WHERE   tbl_pesquisa.fabrica=52
    AND     tbl_pesquisa.ativo
    AND     tbl_pesquisa.categoria = 'callcenter'
    AND     tbl_pesquisa.pesquisa = $pesquisa_categoria
    ORDER BY      pesquisa";
  }else if(in_array($login_fabrica,array(1,85,94,129,145,151,152,160,161)) or $replica_einhell){
    $sql = "SELECT  tbl_pesquisa.pesquisa
    FROM    tbl_pesquisa
    WHERE   tbl_pesquisa.fabrica = $login_fabrica
    AND     tbl_pesquisa.categoria = '$pesquisa_categoria'
    $rinnai_pesquisa
    ORDER BY      pesquisa";
    if (!in_array($login_fabrica, [151])) {
        $sql .= " LIMIT 1 ";
    }
  }
  $resPesquisa = pg_query($con,$sql);

    for ($w=0; $w < pg_num_rows($resPesquisa); $w++) {
        $tipo_pesquisa_a_gerar[] = pg_fetch_result($resPesquisa, $w, 'pesquisa');
    }
}

$path_link    = 'xls/';
$path         = 'xls/';
$path_tmp     = '/tmp/';
$arquivo_link = array();


for ($z=0; $z < count($tipo_pesquisa_a_gerar); $z++) {
	$arquivo_nome     = "relatorio_pesquisas_pergunta_$login_fabrica_"."_".$tipo_pesquisa_a_gerar[$z].".html";
	$arquivo_nome_xls = "relatorio_pesquisas_pergunta_$login_fabrica"."_".$tipo_pesquisa_a_gerar[$z].".xls";

	$arquivo_completo     = $path.$arquivo_nome;
	$arquivo_completo_xls = $path.$arquivo_nome_xls;

	echo `rm $arquivo_completo `;
	echo `rm $arquivo_completo_xls `;

	$fp = fopen($arquivo_completo,"w");

	fputs($fp,"<html>");
	fputs($fp,"<body>");

	//DEFINE CABECALHO DO XLS
	$xlsTableBegin = "<table border='1'>";
	fputs($fp,$xlsTableBegin);
    if (!in_array($pesquisa_categoria,array('posto','posto_sms',"ordem_de_servico","ordem_de_servico_email",'externo_outros', "recadastramento"))) {
        $xlsHeader = "
        <tr bgcolor=\"#CFA533\" >
            <th nowrap>Atendimento</th>
            <th nowrap>Data Resposta</th>
            <th nowrap>Atendente</th>
            <th nowrap>Pesquisa</th>";

    } else if (in_array($pesquisa_categoria, ["posto_sms"])) {
        $xlsHeader = "
        <tr bgcolor=\"#CFA533\" >
            <th nowrap>Data Resposta</th>
            <th nowrap>Nome do Técnico</th>
            <th nowrap>Pesquisa</th>";
    } else if (!in_array($pesquisa_categoria,array("ordem_de_servico","ordem_de_servico_email",'externo_outros', ))) {
        $xlsHeader = "
        <tr bgcolor=\"#CFA533\" >
        <th nowrap>CNPJ</th>
        <th nowrap>Data Resposta</th>
        ";
        
        if ($login_fabrica == 151) {
            $xlsHeader .= "<th nowrap>Código</th>";
        }

        $xlsHeader .= "
        <th nowrap>Posto</th>
        <th nowrap>Pesquisa</th>";
    } else if (in_array($pesquisa_categoria,array("ordem_de_servico","ordem_de_servico_email"))) {
        $xlsHeader = "
        <tr bgcolor=\"#CFA533\" >
            <th nowrap>OS</th>
            <th nowrap>CNPJ</th>
            <th nowrap>Posto</th>
            <th nowrap>Data Resposta</th>
            <th nowrap>Pesquisa</th>";
    } else if (in_array($pesquisa_categoria,array('externo_outros'))) {
        $xlsHeader = "
        <tr bgcolor=\"#CFA533\" >
            <th nowrap>Cliente</th>
            <th nowrap>Data Compra</th>
            <th nowrap>Data Resposta</th>
            <th nowrap>Pesquisa</th>
        ";
    }

    $cal_campo = (in_array($login_fabrica, [85,151])) ? "" : " AND tbl_pesquisa.categoria  = 'callcenter' ";

        //NO CABECALHO VÃO AS PERGUNTAS QUE ESTAO CADASTRADAS DE ACORDO COM O TIPO QUE VEM NO ARRAY
    $todasPerguntas = array();
	if($login_fabrica == 151 and $pesquisa_categoria == 'posto') {
 		$sql = "SELECT  DISTINCT
                        tbl_pergunta.descricao,
                        tbl_pergunta.pergunta
                FROM    tbl_pesquisa
                JOIN    tbl_resposta            ON tbl_resposta.pesquisa = tbl_pesquisa.pesquisa
                JOIN    tbl_pergunta            ON tbl_resposta.pergunta        = tbl_pergunta.pergunta
                WHERE  tbl_resposta.data_input BETWEEN '$aux_data_inicial' AND '$aux_data_final'
                $cal_campo
				AND     tbl_pesquisa.pesquisa   = ".$tipo_pesquisa_a_gerar[$z]."    ";
	}else{
       $sql = "SELECT  DISTINCT
                        tbl_pergunta.descricao,
                        tbl_pergunta.pergunta
                FROM    tbl_hd_chamado
                JOIN    tbl_hd_chamado_extra    ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
                JOIN    tbl_resposta            ON tbl_resposta.hd_chamado      = tbl_hd_chamado.hd_chamado
                JOIN    tbl_pergunta            ON tbl_resposta.pergunta        = tbl_pergunta.pergunta
                JOIN    tbl_pesquisa            ON tbl_resposta.pesquisa = tbl_pesquisa.pesquisa
                WHERE   tbl_hd_chamado.status   = 'Resolvido'
                AND     tbl_hd_chamado.fabrica  = $login_fabrica
                AND     tbl_resposta.data_input BETWEEN '$aux_data_inicial' AND '$aux_data_final'
                $cal_campo
				AND     tbl_pesquisa.pesquisa   = ".$tipo_pesquisa_a_gerar[$z]."    ";
	}
    $resPerguntasTipo = pg_query($con,$sql);

    $posicao_pergunta = [];

    for ($i=0; $i < pg_num_rows($resPerguntasTipo); $i++) {

        $descPergunta       = pg_fetch_result($resPerguntasTipo, $i, 'descricao');
        $ordemPergunta      = pg_fetch_result($resPerguntasTipo, $i, 'ordem');
        $todasPerguntas[]   = pg_fetch_result($resPerguntasTipo, $i, 'pergunta');
        $xlsHeader .= "<th nowrap>$descPergunta </th>";
        $posicao_pergunta[] = pg_fetch_result($resPerguntasTipo, $i, 'pergunta');

    }

    if(in_array($login_fabrica,array(129))){
        $xlsHeader .= "<th>Nota Final</th>";
    }
    if (in_array($login_fabrica, [161])) {
        $xlsHeader .= "<th>Média</th>";
    }
    $xlsHeader .= "
    </tr>
    ";

    fputs($fp,$xlsHeader);


    if(!in_array($login_fabrica,array(1,85,94,129,138,145,151,152,160,161)) and !$replica_einhell){
        $sql = "SELECT  DISTINCT
                        tbl_hd_chamado.hd_chamado,
                        TO_CHAR(tbl_resposta.data_input, 'DD/MM/YYYY') AS data,
                        tbl_pesquisa.descricao,
                        tbl_admin.nome_completo
                FROM    tbl_hd_chamado
                JOIN    tbl_hd_chamado_extra    ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
                JOIN    tbl_resposta            ON tbl_resposta.hd_chamado      = tbl_hd_chamado.hd_chamado
                JOIN    tbl_admin               ON tbl_admin.admin              = tbl_resposta.admin
                JOIN    tbl_pergunta            ON tbl_resposta.pergunta        = tbl_pergunta.pergunta
                JOIN    tbl_pesquisa            ON tbl_resposta.pesquisa = tbl_pesquisa.pesquisa
                WHERE   tbl_hd_chamado.status   = 'Resolvido'
                AND     tbl_hd_chamado.fabrica  = $login_fabrica
                AND     tbl_resposta.data_input BETWEEN '$aux_data_inicial' AND '$aux_data_final'
                $cal_campo
                AND     tbl_pesquisa.pesquisa   = ".$tipo_pesquisa_a_gerar[$z]."
        $conditionPosto

        ";
    } else {
        if (!in_array($pesquisa_categoria,array('posto',"posto_sms","ordem_de_servico","ordem_de_servico_email",'externo_outros', "recadastramento"))) {
            if ($pesquisa_categoria == "externo") {
                $joinAdmin = "JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.atendente";
            } else {
                $joinAdmin = "JOIN    tbl_admin               ON tbl_admin.admin              = tbl_resposta.admin";
            }

            $sql = "SELECT  DISTINCT
            tbl_hd_chamado.hd_chamado,
            TO_CHAR(tbl_resposta.data_input, 'DD/MM/YYYY') AS data,
            pesquisa.descricao,
            pesquisa.pesquisa,
            tbl_admin.nome_completo
            FROM    tbl_hd_chamado
            JOIN    tbl_hd_chamado_extra    ON tbl_hd_chamado.hd_chamado    = tbl_hd_chamado_extra.hd_chamado
            JOIN    tbl_resposta            ON tbl_resposta.hd_chamado      = tbl_hd_chamado.hd_chamado
            $joinAdmin
            JOIN    tbl_pergunta            ON tbl_resposta.pergunta        = tbl_pergunta.pergunta
            JOIN    tbl_pesquisa_pergunta   ON tbl_pergunta.pergunta        = tbl_pesquisa_pergunta.pergunta
            JOIN    (
                SELECT  tbl_pesquisa.pesquisa,
                        tbl_pesquisa.descricao,
                        tbl_pesquisa.ativo
                FROM    tbl_pesquisa
                WHERE   tbl_pesquisa.ativo IS TRUE
                AND     tbl_pesquisa.fabrica    = $login_fabrica
                $conditionPesquisa
                ORDER BY      tbl_pesquisa.pesquisa DESC
                LIMIT   1
                ) pesquisa              ON  pesquisa.pesquisa           = tbl_pesquisa_pergunta.pesquisa
                                        AND pesquisa.pesquisa           = tbl_resposta.pesquisa
                                        AND pesquisa.ativo              IS TRUE
            WHERE   tbl_hd_chamado.status   = 'Resolvido'
            AND     tbl_hd_chamado.fabrica  = $login_fabrica
            AND     tbl_resposta.pesquisa   IS NOT NULL
            $conditionChamado
            AND     tbl_resposta.data_input BETWEEN '$aux_data_inicial' AND '$aux_data_final'

            ";
        } else if (in_array($pesquisa_categoria, ["posto_sms"])) {
            $sql = "SELECT  DISTINCT
                    TO_CHAR(tbl_resposta.data_input, 'DD/MM/YYYY') AS data   ,
                    tbl_resposta.data_input,
                    pesquisa.descricao                                                  ,
                    pesquisa.pesquisa
            FROM tbl_resposta
                JOIN tbl_pergunta ON tbl_resposta.pergunta = tbl_pergunta.pergunta
                JOIN tbl_pesquisa_pergunta ON tbl_pergunta.pergunta = tbl_pesquisa_pergunta.pergunta
                JOIN (
                    SELECT  tbl_pesquisa.pesquisa,
                            tbl_pesquisa.descricao
                        FROM tbl_pesquisa
                        WHERE tbl_pesquisa.ativo IS TRUE
                            AND tbl_pesquisa.fabrica = $login_fabrica
                            $conditionPesquisa
                        ORDER BY      tbl_pesquisa.pesquisa DESC
                        LIMIT   1
                ) pesquisa ON  pesquisa.pesquisa = tbl_pesquisa_pergunta.pesquisa AND pesquisa.pesquisa = tbl_resposta.pesquisa
          WHERE   tbl_resposta.pesquisa   IS NOT NULL
          AND     tbl_resposta.data_input BETWEEN '$aux_data_inicial' AND '$aux_data_final'";

        } else if (!in_array($pesquisa_categoria,array("ordem_de_servico","ordem_de_servico_email",'externo_outros'))) {
            if ($login_fabrica != 151) {
                $aux_limit = " LIMIT 1 ";
            }

            $sql = "SELECT  DISTINCT
                TO_CHAR(tbl_resposta.data_input, 'DD/MM/YYYY')   AS data         ,
                pesquisa.descricao                                                          ,
                pesquisa.pesquisa                                                           ,
                tbl_posto.nome                                              AS posto_nome   ,
                tbl_posto.posto                                                             ,
                tbl_posto.cnpj
                FROM    tbl_posto
                JOIN    tbl_posto_fabrica       ON  tbl_posto_fabrica.posto     = tbl_posto.posto
                                                AND tbl_posto_fabrica.fabrica   = $login_fabrica
           LEFT JOIN    tbl_posto_linha         ON  tbl_posto_linha.posto       = tbl_posto.posto
                JOIN    tbl_resposta            ON  tbl_resposta.posto          = tbl_posto.posto
                JOIN    tbl_pergunta            ON  tbl_resposta.pergunta       = tbl_pergunta.pergunta
                JOIN    tbl_pesquisa_pergunta   ON  tbl_pergunta.pergunta       = tbl_pesquisa_pergunta.pergunta
                JOIN    (
                    SELECT  tbl_pesquisa.pesquisa,
                            tbl_pesquisa.descricao,
                            tbl_pesquisa.ativo
                    FROM    tbl_pesquisa
                    WHERE   tbl_pesquisa.fabrica    = $login_fabrica
                    $rinnai_pesquisa
                    $conditionPesquisa
                    ORDER BY      tbl_pesquisa.pesquisa DESC
                    {$aux_limit}
                ) pesquisa                      ON  pesquisa.pesquisa           = tbl_pesquisa_pergunta.pesquisa
                                                AND pesquisa.pesquisa           = tbl_resposta.pesquisa
                WHERE   tbl_posto_fabrica.credenciamento    IN ('CREDENCIADO','EM DESCREDENCIAMENTO')
                AND     tbl_resposta.pesquisa               IS NOT NULL
                AND     tbl_resposta.data_input             BETWEEN '$aux_data_inicial' AND '$aux_data_final'
                $conditionPosto
                $conditionLinha
                $conditionLocal
            ";
            
            if (in_array($login_fabrica, [151])) {
                $sql .= " AND pesquisa.pesquisa = $tipo_pesquisa_a_gerar[$z] ";
            }

        } else if (in_array($pesquisa_categoria,array("ordem_de_servico","ordem_de_servico_email"))) {
            $sql = "SELECT  DISTINCT
                tbl_os.os,
                tbl_os.sua_os,
                TO_CHAR(tbl_resposta.data_input, 'DD/MM/YYYY') AS data,
                tbl_posto.nome AS posto_nome,
                tbl_posto.cnpj,
                tbl_posto.posto,
                pesquisa.descricao,
                pesquisa.pesquisa
                FROM    tbl_resposta
                JOIN    tbl_os                  ON  tbl_os.os                   = tbl_resposta.os
                                                AND tbl_os.fabrica              = {$login_fabrica}
                JOIN    tbl_posto_fabrica       ON  tbl_posto_fabrica.posto     = tbl_os.posto
                                                AND tbl_posto_fabrica.fabrica   = {$login_fabrica}
                JOIN    tbl_posto               ON  tbl_posto.posto             = tbl_posto_fabrica.posto
                JOIN    tbl_pergunta            ON  tbl_resposta.pergunta       = tbl_pergunta.pergunta
                JOIN    tbl_pesquisa_pergunta   ON  tbl_pergunta.pergunta       = tbl_pesquisa_pergunta.pergunta
                JOIN    (
                    SELECT  tbl_pesquisa.pesquisa,
                    tbl_pesquisa.descricao
                    FROM    tbl_pesquisa
                    WHERE   tbl_pesquisa.ativo IS TRUE
                    AND     tbl_pesquisa.fabrica    = $login_fabrica
                    $conditionPesquisa
                    ORDER BY      tbl_pesquisa.pesquisa DESC
                    LIMIT   1
                    ) pesquisa              ON  pesquisa.pesquisa           = tbl_pesquisa_pergunta.pesquisa
                                            AND pesquisa.pesquisa           = tbl_resposta.pesquisa
            WHERE   tbl_posto_fabrica.credenciamento    IN ('CREDENCIADO','EM DESCREDENCIAMENTO')
            AND     tbl_resposta.pesquisa               IS NOT NULL
            AND     tbl_resposta.data_input             BETWEEN '$aux_data_inicial' AND '$aux_data_final'
            $conditionPosto
            $conditionLinha
            $conditionLocal
            ";
        } else if (in_array($pesquisa_categoria,array("externo_outros"))) {
            $sql = "
                SELECT  DISTINCT
                        TO_CHAR(tbl_resposta.data_input, 'DD/MM/YYYY') AS data   ,
                        pesquisa.descricao                                                  ,
                        pesquisa.pesquisa                                                   ,
                        TO_CHAR(tbl_venda.data_nf, 'DD/MM/YYYY') AS data_compra   ,
                        tbl_venda.venda                                                     ,
                        tbl_cliente.nome AS cliente
                FROM    tbl_resposta
                JOIN    tbl_venda               USING(venda)
                JOIN    tbl_cliente             USING(cliente)
                JOIN    tbl_pesquisa_pergunta   ON tbl_pesquisa_pergunta.pesquisa = tbl_resposta.pesquisa
                JOIN    (
                            SELECT  tbl_pesquisa.pesquisa,
                                    tbl_pesquisa.descricao
                            FROM    tbl_pesquisa
                            WHERE   tbl_pesquisa.ativo IS TRUE
                            AND     tbl_pesquisa.fabrica    = $login_fabrica
                            $conditionPesquisa
                      ORDER BY      tbl_pesquisa.pesquisa DESC
                            LIMIT   1
                        ) pesquisa              ON  pesquisa.pesquisa           = tbl_pesquisa_pergunta.pesquisa
                                                AND pesquisa.pesquisa           = tbl_resposta.pesquisa
                WHERE   os IS NULL
                AND     hd_chamado IS NULL
            ";
        }
    }

    $resChamados = pg_query($con,$sql);

    for ($i=0; $i < pg_num_rows($resChamados); $i++) {

        $hd_chamado         = pg_fetch_result($resChamados, $i, 'hd_chamado');
        $dataChamado        = pg_fetch_result($resChamados, $i, 'data');
        $data_compra        = pg_fetch_result($resChamados, $i, 'data_compra');
        $descTipoPergunta   = pg_fetch_result($resChamados, $i, 'descricao');
        $atendenteChamado   = pg_fetch_result($resChamados, $i, 'nome_completo');
        $posto              = pg_fetch_result($resChamados, $i, 'posto');
        $posto_nome         = pg_fetch_result($resChamados, $i, 'posto_nome');
        $posto_cnpj         = pg_fetch_result($resChamados, $i, 'cnpj');
        $os                 = pg_fetch_result($resChamados, $i, "os");
        $venda              = pg_fetch_result($resChamados, $i, "venda");
        $sua_os             = pg_fetch_result($resChamados, $i, "sua_os");
        $cliente            = pg_fetch_result($resChamados, $i, "cliente");

        if (in_array($login_fabrica, [1])) {
            $key_pesquisa = pg_fetch_result($resChamados, $i, pesquisa);
            $dataChamadoInput = pg_fetch_result($resChamados, $i, 'data_input');

            $sql_tec = "SELECT  tbl_tecnico.tecnico,
                                tbl_tecnico.nome
                            FROM    tbl_resposta
                                JOIN tbl_tecnico using(tecnico)
                            WHERE   pesquisa    = {$key_pesquisa}
                                AND     os isnull
                                AND     hd_chamado isnull
                                AND     tbl_resposta.data_input BETWEEN (timestamp'{$dataChamadoInput}' - INTERVAL '5 second' ) AND (timestamp'{$dataChamadoInput}' + INTERVAL '5 second')
                                AND tecnico is not null
                            ORDER BY pergunta LIMIT 1;";
            $res_tec = pg_query($con,$sql_tec);

            if (pg_num_rows($res_tec)) {
                $tecnico_nome = pg_fetch_result($res_tec, 0, 1);
            } else {
                $tecnico_nome = 'NON';
            }
        }

        $cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

        $nota_final = 0;

        $xlsContents = "<tr bgcolor='$cor'>";

        if (!in_array($pesquisa_categoria,array('posto','posto_sms',"ordem_de_servico","ordem_de_servico_email","externo_outros", "recadastramento"))) {
//         echo $atendenteChamado;
            $xlsContents .= "<td>$hd_chamado</td>";
            $xlsContents .= "<td>$dataChamado</td>";
            $xlsContents .= "<td>$atendenteChamado</td>";
            $xlsContents .= "<td>$descTipoPergunta</td>";
        } elseif (in_array($pesquisa_categoria, ["posto_sms"])) {
            $xlsContents .= "<td>$dataChamado</td>";
            $xlsContents .= "<td>$tecnico_nome</td>";
            $xlsContents .= "<td>$descTipoPergunta</td>";
        } else if (!in_array($pesquisa_categoria,array("ordem_de_servico","ordem_de_servico_email","externo_outros"))){
//         echo 2;
            $xlsContents .= "<td>".str_pad($posto_cnpj,15,"'",STR_PAD_LEFT)."</td>";
            $xlsContents .= "<td>$dataChamado</td>";

            if ($login_fabrica == 151) {
                $aux_sql = "SELECT codigo_posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND posto = $posto";
                $aux_res = pg_query($con, $aux_sql);
                $aux_cod = pg_fetch_result($aux_res, 0, 'codigo_posto');

                $xlsContents .= "<td>$aux_cod</td>";
            }

            $xlsContents .= "<td>$posto_nome</td>";
            $xlsContents .= "<td>$descTipoPergunta</td>";
        } else if (in_array($pesquisa_categoria,array("ordem_de_servico","ordem_de_servico_email"))) {
//         echo 3;
            $xlsContents .= "<td>$sua_os</td>";
            $xlsContents .= "<td>".str_pad($posto_cnpj,15,"'",STR_PAD_LEFT)."</td>";
            $xlsContents .= "<td>$posto_nome</td>";
            $xlsContents .= "<td>$dataChamado</td>";
            $xlsContents .= "<td>$descTipoPergunta</td>";
        } else if (in_array($pesquisa_categoria,array("externo_outros"))) {
            $xlsContents .= "<td>$cliente</td>";
            $xlsContents .= "<td>$data_compra</td>";
            $xlsContents .= "<td>$dataChamado</td>";
            $xlsContents .= "<td>$descTipoPergunta</td>";
        }
// exit;
        //PERGUNTAS E RESPOSTAS DO CHAMADOS
        if (!in_array($pesquisa_categoria,array('posto','posto_sms',"ordem_de_servico","ordem_de_servico_email","externo_outros","recadastramento"))) {
            $cond = " AND tbl_resposta.hd_chamado = $hd_chamado\n";
            $chavePergResp = $hd_chamado;
        } elseif (in_array($pesquisa_categoria, ["posto_sms"])) {
            $cond = " AND     tbl_resposta.data_input BETWEEN (timestamp'{$dataChamadoInput}' - INTERVAL '5 second' ) AND (timestamp'{$dataChamadoInput}' + INTERVAL '5 second') AND tbl_resposta.pesquisa = {$key_pesquisa} \n";
            $chavePergResp = $dataChamado;

        } else if (!in_array($pesquisa_categoria,array("ordem_de_servico","ordem_de_servico_email","externo_outros"))){
            $cond = " AND tbl_resposta.posto = $posto\n";
            $chavePergResp = $posto;
        } else if (in_array($pesquisa_categoria,array("ordem_de_servico","ordem_de_servico_email"))) {
            $cond = " AND tbl_resposta.os = $os\n";
            $chavePergResp = $os;
        } else if (in_array($pesquisa_categoria,array("externo_outros"))) {
            $cond = " AND tbl_resposta.venda = $venda\n";
            $chavePergResp = $venda;
        }

        $sqlPerguntaResposta = "SELECT  tbl_resposta.txt_resposta   ,
        tbl_tipo_resposta.tipo_descricao,
        tbl_resposta.pergunta
        FROM    tbl_resposta
        JOIN    tbl_pesquisa            ON  tbl_pesquisa.pesquisa           = tbl_resposta.pesquisa
        JOIN    tbl_pergunta            ON  tbl_pergunta.pergunta           = tbl_resposta.pergunta
        JOIN    tbl_tipo_resposta       ON  tbl_tipo_resposta.tipo_resposta = tbl_pergunta.tipo_resposta
        WHERE   tbl_pesquisa.fabrica    = $login_fabrica
        AND     tbl_resposta.pesquisa   = ".$tipo_pesquisa_a_gerar[$z]."
        $cond
        ORDER BY      tbl_resposta.resposta;
        ";

        $resPergunta = pg_query($con,$sqlPerguntaResposta);

        $arrPergResp = "";
        $arrSoPergs = "";
        for ($x=0; $x < pg_num_rows($resPergunta); $x++) {

            $resposta   = pg_fetch_result($resPergunta, $x, 'txt_resposta');
            $descricao  = pg_fetch_result($resPergunta, $x, 'tipo_descricao');
            $pergunta   = pg_fetch_result($resPergunta, $x, 'pergunta');

            $arrPergResp[$chavePergResp][] = array("pergunta"=> $pergunta,"resultado" => array("desc" => $descricao,"resposta" => $resposta));
            $arrSoPergs[] = $pergunta;

        }

        $arrSoPergs = array_unique($arrSoPergs);
        $ant = "";
        $arrSoPergs = (count($arrSoPergs) < count($todasPerguntas)) ? $todasPerguntas : $arrSoPergs;
        $todas = $todasPerguntas;
        $todasArr = [];
        $arrTodas = [];
		/*asort($todas);
		asort($arrSoPergs);*/
        
        foreach ($todasPerguntas as $key => $value) {
            foreach ($posicao_pergunta as $p => $v) {
                if ($value == $v) {
                    $todasArr[$p] = $value;
                }
            }
        }

        foreach ($arrSoPergs as $key => $value) {
            foreach ($posicao_pergunta as $p => $v) {
                if ($value == $v) {
                    $arrTodas[$p] = $v;
                }
            }
        }

        if (count($todasArr) > 0) {
            $todas = $todasArr;
            ksort($todas);
        }

        if (count($arrTodas) > 0) {
            $arrSoPergs = $arrTodas;
            ksort($arrSoPergs);
        }
        
        foreach($todas as $pT){
            foreach($arrSoPergs as $pR){
                if ($pT == $pR) {
                    $sqlRespostas = "
                        SELECT tbl_resposta.txt_resposta, tbl_tipo_resposta.peso AS peso_resposta, tbl_resposta.tipo_resposta_item
                        FROM   tbl_resposta
                        JOIN   tbl_pesquisa            ON  tbl_pesquisa.pesquisa           = tbl_resposta.pesquisa
                        JOIN   tbl_pergunta            ON  tbl_pergunta.pergunta           = tbl_resposta.pergunta
                        JOIN   tbl_tipo_resposta       ON  tbl_tipo_resposta.tipo_resposta = tbl_pergunta.tipo_resposta
                        WHERE  tbl_pesquisa.fabrica    = $login_fabrica
                        AND    tbl_resposta.pesquisa   = ".$tipo_pesquisa_a_gerar[$z]."
                        AND    tbl_resposta.pergunta = $pR
                        $cond
                    ";
                    $resRespostas = pg_query($con,$sqlRespostas);
                    if (pg_num_rows($resRespostas) == 0 && $login_fabrica != 151) {
                        $xlsContents .= "<td></td>";
                    } else {
                        $verifica_repeticao = array();
                        $contaResp = pg_num_rows($resRespostas);
                        for($r = 0; $r < $contaResp; $r++){
                            $nao_inserir = 0;
                            if (isset($verifica_repeticao) && in_array($login_fabrica,array(145))) {
                                for ($posicao2=0; $posicao2 < count($verifica_repeticao); $posicao2++) {
                                    if($verifica_repeticao[$posicao2] == pg_fetch_result($resRespostas,$r,'txt_resposta')){
                                        $nao_inserir = 1;
                                        break;
                                    }
                                }
                            }
                            if ($nao_inserir == 0) {
                                if ($login_fabrica == 151) {
                                    $auxiliar = $tipo_pesquisa_a_gerar[$z]. "|" .$pR. "|" .$posto;
                                    if (!in_array($auxiliar, $array_ver_duplicados)) {
                                        $array_ver_duplicados[] = $auxiliar;

                                        $rr .= pg_fetch_result($resRespostas,$r,'txt_resposta');
                                    }
                                } else {
                                    $rr .= pg_fetch_result($resRespostas,$r,'txt_resposta');
                                }
                            }
                            $verifica_repeticao[] = pg_fetch_result($resRespostas,$r,'txt_resposta');
                            $peso_resposta = pg_fetch_result($resRespostas,$r,'peso_resposta');
                            $tipo_resposta_item = pg_fetch_result($resRespostas,$r,'tipo_resposta_item');
                            $nota_final += $peso_resposta;

                            if(!empty($tipo_resposta_item) AND in_array($login_fabrica,array(129))){
                                $sqlItem = "SELECT peso FROM tbl_tipo_resposta_item WHERE tipo_resposta_item = {$tipo_resposta_item}";
                                $resItem = pg_query($con,$sqlItem);
                                $peso_item = pg_fetch_result($resItem,0,'peso');
                                $nota_final += $peso_item;
                            }

                            if(($r + 1)!= $contaResp && !in_array($login_fabrica,array(145,151))){
                                $rr .= ", ";
                            }
                        }
                        if (strlen($rr)> 0 ){
                            $xlsContents .= "<td>$rr</td>";
                            $countMedia[] = $rr;
                        }else{
                            if ($login_fabrica != 151) $xlsContents .= "<td>&nbsp;</td>";
                        }

                    }

                    array_shift($arrSoPergs);
                    $rr = "";
					if($login_fabrica <> 52 ) {
						continue 2;
					}
                } else {
					if($login_fabrica <> 52 ) {
						$xlsContents .= "<td>&nbsp;</td>";
                    if ($pT < $pR) {
                        continue 2;
					}
					}
                }
            }
        }

        if(in_array($login_fabrica,array(129))){
            $xlsContents .= "<td>$nota_final</td>";
        }
        if (in_array($login_fabrica, [161])) {
            $countMediaTotal[] = (array_sum($countMedia)/count($countMedia));
            $xlsContents .= "<td>".number_format( (array_sum($countMedia)/count($countMedia)) , 2, ",", ".")."</td>";
            unset($countMedia);
        }
        $xlsContents .= "</tr>";
        fputs($fp,$xlsContents);
    }

    if (in_array($login_fabrica, [161])) {
        $colspan = ($$pesquisa_categoria == "externo_outros") ? 4 + pg_num_rows($resChamados) : 9;
        $xlsTotal .= "<tr>";
        $xlsTotal .= "<td colspan='$colspan' align='center'>Média da Satisfação nesse período Consultado: ".number_format( (array_sum($countMediaTotal)/count($countMediaTotal)) , 2, ",", ".")."</td>";
        $xlsTotal .= "</tr>";
        fputs($fp,$xlsTotal);
        unset($countMedia);
    }

    $xlsTableEnd = "</table>";
    fputs($fp,$xlsTableEnd);

    fputs($fp,"</body>");
    fputs($fp,"</body>");
    fputs($fp,"</html>");

    fclose($fp);

	if(pg_num_rows($resPerguntasTipo) > 0) {
		$arquivo_nome = rename($arquivo_completo, $arquivo_completo_xls);
		$arquivo_link[$tipo_pesquisa_a_gerar[$z]] = $path_link.$arquivo_nome_xls;
	}
}


foreach ($arquivo_link as $key => $value) {

    $sql = "SELECT tbl_pesquisa.pesquisa,tbl_pesquisa.descricao from tbl_pesquisa where pesquisa = $key";
    $res = pg_query($con,$sql);

    $result = pg_fetch_all($res);
    foreach ($result as $field) {
        if ($field['pesquisa'] == $key){
?>
            <a href="<?=$value?>" target="_blank" >
                <img src="imagens/excel.gif" alt="Download Excel">
            <?php
            echo "Download Arquivo XLS: ".$field['descricao'];
            ?>
        </a>
        <br>
<?php
        }else{
            continue;
        }
    }
}
