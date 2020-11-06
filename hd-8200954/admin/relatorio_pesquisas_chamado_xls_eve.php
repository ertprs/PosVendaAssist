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
if ($tipo_pesquisa){
	$tipo_pesquisa_a_gerar[] = $tipo_pesquisa;
}else{
	$sql = "SELECT  tbl_pesquisa.pesquisa
		FROM    tbl_pesquisa
		WHERE   tbl_pesquisa.fabrica = $login_fabrica
		AND     tbl_pesquisa.ativo IS TRUE
		AND     tbl_pesquisa.categoria = '$pesquisa'
		$conditionPesquisaSelecionada
		ORDER BY      pesquisa
		LIMIT   1
		";
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

	$arquivo_nome     = "relatorio_pesquisas_pergunta_$login_fabrica_".$tipo_pesquisa_a_gerar[$z].".html";
	$arquivo_nome_xls = "relatorio_pesquisas_pergunta_$login_fabrica_".$tipo_pesquisa_a_gerar[$z].".xls";

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
	if($pesquisa != 'posto'){
		$xlsHeader = "
			<tr bgcolor=\"#CFA533\" >
			<th nowrap>Atendimento</th>
			<th nowrap>Data Resposta</th>
			<th nowrap>Atendente</th>
			<th nowrap>Pesquisa</th>";
	}else{
		$xlsHeader = "
			<tr bgcolor=\"#CFA533\" >
			<th nowrap>CNPJ</th>
			<th nowrap>Data Resposta</th>
			<th nowrap>Posto</th>
			<th nowrap>Pesquisa</th>";
	}

	//NO CABECALHO VÃO AS PERGUNTAS QUE ESTAO CADASTRADAS DE ACORDO COM O TIPO QUE VEM NO ARRAY
	$sql = "SELECT  tbl_pergunta.descricao,
		tbl_pesquisa_pergunta.ordem,
		tbl_pergunta.pergunta
		FROM    tbl_pergunta
		JOIN    tbl_pesquisa_pergunta using (pergunta)
		WHERE   tbl_pesquisa_pergunta.pesquisa  = ".$tipo_pesquisa_a_gerar[$z]."
		AND     tbl_pergunta.ativo              IS TRUE
		AND     tbl_pergunta.fabrica            = $login_fabrica
		AND     tbl_pergunta.tipo_resposta      IS NOT NULL
		ORDER BY      tbl_pesquisa_pergunta.ordem
		";
	$resPerguntasTipo = pg_query($con,$sql);

	for ($i=0; $i < pg_num_rows($resPerguntasTipo); $i++) {

		$descPergunta       = pg_fetch_result($resPerguntasTipo, $i, 'descricao');
		$ordemPergunta      = pg_fetch_result($resPerguntasTipo, $i, 'ordem');
		$todasPerguntas[]   = pg_fetch_result($resPerguntasTipo, $i, 'pergunta');

		$xlsHeader .= "<th nowrap>$ordemPergunta - $descPergunta </th>";

	}

	$xlsHeader .= "
		</tr>
		";

	fputs($fp,$xlsHeader);


	if($pesquisa != 'posto'){
		$sql = "SELECT  DISTINCT
			tbl_hd_chamado.hd_chamado,
			TO_CHAR(tbl_resposta.data_input, 'DD/MM/YYYY HH24:MI:SS') AS data,
			pesquisa.descricao,
			pesquisa.pesquisa,
			tbl_admin.nome_completo
			FROM    tbl_hd_chamado
			JOIN    tbl_hd_chamado_extra    ON tbl_hd_chamado.hd_chamado    = tbl_hd_chamado_extra.hd_chamado
			JOIN    tbl_admin               ON tbl_hd_chamado.atendente     = tbl_admin.admin
			JOIN    tbl_resposta            ON tbl_hd_chamado.hd_chamado    = tbl_resposta.hd_chamado
			JOIN    tbl_pergunta            ON tbl_resposta.pergunta        = tbl_pergunta.pergunta
			JOIN    tbl_pesquisa_pergunta   ON tbl_pergunta.pergunta        = tbl_pesquisa_pergunta.pergunta
			JOIN    (
				SELECT  tbl_pesquisa.pesquisa,
				tbl_pesquisa.descricao
				FROM    tbl_pesquisa
				WHERE   tbl_pesquisa.ativo IS TRUE
				AND     tbl_pesquisa.fabrica    = $login_fabrica
				$conditionPesquisa
				$conditionPesquisaSelecionada
				ORDER BY      tbl_pesquisa.pesquisa DESC
				LIMIT   1
			) pesquisa              ON  pesquisa.pesquisa           = tbl_pesquisa_pergunta.pesquisa
			AND pesquisa.pesquisa           = tbl_resposta.pesquisa
			WHERE   tbl_hd_chamado.status   = 'Resolvido'
			AND     tbl_hd_chamado.fabrica  = $login_fabrica
			AND     tbl_resposta.pesquisa   IS NOT NULL
			$conditionChamado
			AND     tbl_resposta.data_input BETWEEN '$aux_data_inicial' AND '$aux_data_final'
			";

	}else{
		$sql = "SELECT  DISTINCT
			TO_CHAR(tbl_resposta.data_input, 'DD/MM/YYYY HH24:MI:SS')   AS data         ,
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
	}


	$resChamados = pg_query($con,$sql);

	for ($i=0; $i < pg_num_rows($resChamados); $i++) {
		//echo $i;

		$hd_chamado         = pg_fetch_result($resChamados, $i, 'hd_chamado');
		$dataChamado        = pg_fetch_result($resChamados, $i, 'data');
		$descTipoPergunta   = pg_fetch_result($resChamados, $i, 'descricao');
		$atendenteChamado   = pg_fetch_result($resChamados, $i, 'nome_completo');
		$posto              = pg_fetch_result($resChamados, $i, 'posto');
		$posto_nome         = pg_fetch_result($resChamados, $i, 'posto_nome');
		$posto_cnpj         = pg_fetch_result($resChamados, $i, 'cnpj');
		$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
		$xlsContents .= "<tr bgcolor='$cor'>";

		if($pesquisa != 'posto'){
			$xlsContents .= "<td>$hd_chamado</td>";
			$xlsContents .= "<td>$dataChamado</td>";
			$xlsContents .= "<td>$atendenteChamado</td>";
			$xlsContents .= "<td>$descTipoPergunta</td>";
		}else{
			$xlsContents .= "<td>$posto_cnpj</td>";
			$xlsContents .= "<td>$dataChamado</td>";
			$xlsContents .= "<td>$posto_nome</td>";
			$xlsContents .= "<td>$descTipoPergunta</td>";
		}

		//PERGUNTAS E RESPOSTAS DO CHAMADOS
		if($pesquisa != 'posto'){
			$cond = " AND tbl_resposta.hd_chamado = $hd_chamado\n";
		}else{
			$cond = " AND tbl_resposta.posto = $posto\n";
		}
		for ($x=0; $x < pg_num_rows($resPerguntasTipo); $x++) {
			$Pergunta       = pg_fetch_result($resPerguntasTipo, $x, 'pergunta');
			$sqlPerguntaResposta = "SELECT  tbl_resposta.txt_resposta   ,
				tbl_tipo_resposta.tipo_descricao,
				tbl_resposta.pergunta
				FROM    tbl_resposta
				JOIN    tbl_pesquisa            ON  tbl_pesquisa.pesquisa           = tbl_resposta.pesquisa
				JOIN    tbl_pergunta            ON  tbl_pergunta.pergunta           = tbl_resposta.pergunta
				JOIN    tbl_tipo_resposta       ON  tbl_tipo_resposta.tipo_resposta = tbl_pergunta.tipo_resposta
				JOIN    tbl_pesquisa_pergunta   ON  tbl_pergunta.pergunta           = tbl_pesquisa_pergunta.pergunta
				AND     tbl_pesquisa.pesquisa           = tbl_pesquisa_pergunta.pesquisa
				WHERE   tbl_pesquisa.fabrica    = $login_fabrica
				AND     tbl_pergunta.ativo      IS TRUE
				AND     tbl_resposta.pergunta  = {$Pergunta}
				AND     tbl_resposta.pesquisa   = {$tipo_pesquisa_a_gerar[$z]}
		{$cond}
		ORDER BY tbl_pesquisa_pergunta.ordem
		";
			//echo nl2br($sqlPerguntaResposta);
			$resRespostas = pg_query($con,$sqlPerguntaResposta);
			if ( pg_num_rows($resRespostas) >0 ){
				for ($r=0; $r < pg_num_rows($resPerguntasTipo); $r++) {
					$resposta_teste   = pg_fetch_result($resRespostas, $r, 'txt_resposta');
					if ((strlen($resposta_teste) > 0) AND (strlen($resposta) > 0)) {
					}else{
						$resposta .= $resposta_teste;
					}
				}
			}
			//echo $resposta."<br /><br />";
			if (strlen($resposta)== 0 ){
				$resposta = "&nbsp;";
			}
			$xlsContents .= "<td>{$resposta}</td>";
			unset($resposta);
		}
		$xlsContents .= "</tr>";
	}
	fputs($fp,$xlsContents);
}


$xlsTableEnd = "</table>";
fputs($fp,$xlsTableEnd);

fputs($fp,"</body>");
fputs($fp,"</body>");
fputs($fp,"</html>");

fclose($fp);

$arquivo_nome = rename($arquivo_completo, $arquivo_completo_xls);
$arquivo_link[$tipo_pesquisa_a_gerar[$z]] = $path_link.$arquivo_nome_xls;

$tipo_pesquisa = !empty($tipo_pesquisa) ? $tipo_pesquisa : $pesquisa_selecionada;
$sql = "SELECT tbl_pesquisa.descricao from tbl_pesquisa where pesquisa = $tipo_pesquisa";
$res = pg_query($con,$sql);

$result = pg_fetch_all($res);
?>
      <a href="<?=$arquivo_link[$tipo_pesquisa_a_gerar[$z]]?>" target="_blank" >
        <img src="imagens/excel.gif" alt="Download Excel">
       <?php
       echo "Download Arquivo XLS do tipo: " .$result[0]['descricao'];
       ?>
     </a>
     <br>
     <?
