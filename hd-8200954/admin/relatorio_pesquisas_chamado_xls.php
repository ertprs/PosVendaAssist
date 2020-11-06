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
	
	$sql = "SELECT tbl_tipo_pergunta.tipo_pergunta from tbl_tipo_pergunta join tbl_tipo_relacao using(tipo_relacao) where tbl_tipo_pergunta.fabrica=52 and tbl_tipo_pergunta.ativo and tbl_tipo_relacao.sigla_relacao = 'C' order by tipo_pergunta";
	
	$resTipo = pg_query($con,$sql);

	for ($w=0; $w < pg_num_rows($resTipo); $w++) { 
		$tipo_pesquisa_a_gerar[] = pg_fetch_result($resTipo, $w, tipo_pergunta);
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
	$xlsHeader = "
		<tr bgcolor=\"#CFA533\" >
			<th nowrap>Atendimento</th>
			<th nowrap>Data Resposta</th>
			<th nowrap>Atendente</th>
			<th nowrap>Tipo Pesquisa</th>";

	//NO CABECALHO TAMBÉ VAI AS PERGUNTAS QUE ESTAO CADASTRADAS DE ACORDO COM O TIPO QUE VEM NO ARRAY
	$sql = "SELECT tbl_pergunta.descricao,tbl_tipo_pergunta.descricao as tipo_pergunta_descricao
			FROM   tbl_pergunta 
			JOIN   tbl_tipo_pergunta using (tipo_pergunta) 
			WHERE  tbl_pergunta.tipo_pergunta = ".$tipo_pesquisa_a_gerar[$z]." 
			AND    tbl_pergunta.ativo is true 
			AND    tbl_pergunta.fabrica = $login_fabrica 
			ORDER BY pergunta 
			";

	$resPerguntasTipo = pg_query($con,$sql);

	for ($i=0; $i < pg_num_rows($resPerguntasTipo); $i++) {
		
		$descPergunta = pg_fetch_result($resPerguntasTipo, $i, 'descricao');
		$TipoPerguntaDescricao = pg_fetch_result($resPerguntasTipo, $i, 'tipo_pergunta_descricao');
		if ($TipoPerguntaDescricao == 'Auditoria em Campo'){

			if (in_array($i, array(8,9,10,11))){
				$num = ($num + 0.1);
			}else{
				if ($i >= 12){
					if ($num <> 9){
						$num = 9;
					}else{
						$num++;
					}
				}else{
					$num = ($i+1);
				}
			}

		}else{
			$num = $i+1;
		}
		$xlsHeader .= "<th nowrap>$num - $descPergunta </th>";

	}

	$xlsHeader .= "
		</tr>
	";

	fputs($fp,$xlsHeader);	

	$sql = "SELECT  distinct tbl_hd_chamado.hd_chamado,
					TO_CHAR(tbl_resposta.data_input, 'DD/MM/YYYY HH24:MI:SS') AS data,
					tbl_tipo_pergunta.descricao,
					tbl_admin.nome_completo
			FROM tbl_hd_chamado 
			JOIN tbl_hd_chamado_extra on(tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado)
			JOIN tbl_admin    on(tbl_hd_chamado.atendente = tbl_admin.admin) 
			JOIN tbl_resposta on(tbl_hd_chamado.hd_chamado = tbl_resposta.hd_chamado) 
			JOIN tbl_pergunta on(tbl_resposta.pergunta = tbl_pergunta.pergunta) 
			JOIN tbl_tipo_pergunta on(tbl_pergunta.tipo_pergunta = tbl_tipo_pergunta.tipo_pergunta) 
			WHERE tbl_hd_chamado.status = 'Resolvido' 
			AND tbl_hd_chamado.fabrica = $login_fabrica 
			AND tbl_resposta.data_input between '$aux_data_inicial' and '$aux_data_final' 
			AND tbl_tipo_pergunta.tipo_pergunta = ".$tipo_pesquisa_a_gerar[$z]."
			$conditionPosto  
			;
	";

	$resChamados = pg_query($con,$sql);

	for ($i=0; $i < pg_num_rows($resChamados); $i++) { 
		
		$hd_chamado       = pg_fetch_result($resChamados, $i, 'hd_chamado');
		$dataChamado      = pg_fetch_result($resChamados, $i, 'data');
		$descTipoPergunta = pg_fetch_result($resChamados, $i, 'descricao');
		$atendenteChamado = pg_fetch_result($resChamados, $i, 'nome_completo');
		$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
		$xlsContents = "<tr bgcolor='$cor'>";

			$xlsContents .= "<td>$hd_chamado</td>";
			$xlsContents .= "<td>$dataChamado</td>";
			$xlsContents .= "<td>$atendenteChamado</td>";
			$xlsContents .= "<td>$descTipoPergunta</td>";

			//PERGUNTAS E RESPOSTAS DO CHAMADOS
			$sqlPerguntaResposta = "SELECT  tbl_resposta.txt_resposta
									FROM tbl_tipo_pergunta 
									JOIN tbl_pergunta on (tbl_tipo_pergunta.tipo_pergunta = tbl_pergunta.tipo_pergunta)
									JOIN tbl_resposta on (tbl_pergunta.pergunta = tbl_resposta.pergunta)
									WHERE tbl_tipo_pergunta.fabrica = $login_fabrica
									AND tbl_pergunta.fabrica = $login_fabrica 
									AND tbl_resposta.hd_chamado = ".$hd_chamado." 
									ORDER BY tbl_pergunta.pergunta
			";
			$resPergunta = pg_query($con,$sqlPerguntaResposta);

			for ($x=0; $x < pg_num_rows($resPergunta); $x++) { 

				$resposta = pg_fetch_result($resPergunta, $x, 'txt_resposta');

				$xlsContents .= "<td>$resposta</td>";

			}

		$xlsContents .= "</tr>";

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

}

foreach ($arquivo_link as $key => $value) {
	
	$sql = "SELECT tbl_tipo_pergunta.tipo_pergunta,tbl_tipo_pergunta.descricao from tbl_tipo_pergunta where tipo_pergunta = $key";
	$res = pg_query($con,$sql);

	$result = pg_fetch_all($res);
	foreach ($result as $field) {
		if ($field['tipo_pergunta'] == $key){
			?>
			<a href="<?=$value?>" target="_blank" >
				<img src="imagens/excel.gif" alt="Download Excel">
			<?php  
				echo "Download Arquivo XLS do tipo: ".$field['descricao'];
			?>		
			</a>
			<br>
			<?

		}else{
			
			continue;
		
		}

	}


}
