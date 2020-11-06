<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="gerencia";
include 'autentica_admin.php';
include 'funcoes.php';

$title       = strtoupper("Relatorio de OS com peÇas");
$layout_menu = "gerencia";


// include 'jquery-ui.html';


$sql_os = "SELECT distinct tbl_os.os,
			tbl_os.data_abertura,
			to_char(tbl_os.data_abertura,'DD/MM/YYYY') as abertura,
			tbl_os.posto,
			tbl_posto.nome as posto_nome,
			tbl_posto.cnpj as posto_cnpj,
			tbl_os.revenda,
			tbl_os.consumidor_nome, 
			tbl_produto.descricao as produto,
			tbl_os.excluida,
			to_char(tbl_os_item.digitacao_item,'DD/MM/YYYY') as digitacao_item,
			tbl_peca.descricao
			FROM tbl_os 
			JOIN tbl_os_produto USING(os)
            JOIN tbl_os_item USING(os_produto)
            JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
			JOIN tbl_produto on tbl_os.produto = tbl_produto.produto
			JOIN tbl_peca on tbl_os_item.peca = tbl_peca.peca
            JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
            JOIN tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = $login_fabrica
			LEFT JOIN tbl_os_status ON tbl_os_status.os = tbl_os.os AND tbl_os_status.fabrica_status=$login_fabrica
			WHERE tbl_os.fabrica = $login_fabrica
			AND   tbl_os.finalizada IS NULL
			AND   tbl_os.data_fechamento IS NULL
			AND   tbl_os.os_fechada IS FALSE
			AND   tbl_os_item.pedido IS NULL 
            AND   tbl_os.excluida   IS NOT TRUE
            AND   tbl_servico_realizado.gera_pedido is true
            AND   UPPER(tbl_posto_fabrica.credenciamento) NOT IN ('DESCREDENCIADO')
			AND   tbl_os.data_abertura < current_date - INTERVAL '3 days'
            AND   (SELECT status_os FROM tbl_os_status WHERE tbl_os_status.fabrica_status = $login_fabrica AND tbl_os_status.os = tbl_os.os ORDER BY tbl_os_status.data DESC LIMIT 1) NOT IN (15, 62, 70)
            ORDER by tbl_os.os,
            tbl_posto.cnpj,
            tbl_peca.descricao,
            digitacao_item ,
            tbl_peca.descricao,
            tbl_os.revenda,
            tbl_os.consumidor_nome,
            tbl_produto.descricao ,
            tbl_os.posto,
            tbl_posto.nome ,
            tbl_os.data_abertura ,
           tbl_os.excluida ";
 //echo nl2br($sql_os);exit;
$res_os = pg_query($con,$sql_os);
if (pg_num_rows($res_os)==0){
    header("Location: hd_aguarda_aprovacao.php");
    exit;
}
echo $msg_err0 = pg_last_error();
$count = pg_num_rows($res_os);
include 'cabecalho_new.php';

if ($res_os > 0) {
	echo"<table width='700'id='resultado_os_abertas' class='table table-striped table-bordered table-hover table-large'>";
    echo "<thead>
            <tr class='titulo_tabela'>";
	        echo "<th colspan='11'>Relação de OS</th>";
	    echo "</tr>";
	echo "<tr class='titulo_coluna'>";
	    echo "<th >OS</th>";
	    echo "<th >Código Posto</th>";
	    echo "<th >Nome Posto</th>";
	    echo "<th >Peça</th>";
	    echo "<th>Data lançamento</th>";
	    echo "<th>Produto</th>";
        echo "</tr>
            </thead>
            <tbody>";

	while ($fetch = pg_fetch_array($res_os)) {
		$os            		= trim($fetch['os']);
		$data_abertura      = trim($fetch['data_abertura']);
		$abertura 			= trim($fetch['abertura']);
		$posto_nome 		= trim($fetch['posto_nome']);
		$posto_cnpj       	= trim($fetch['posto_cnpj']);
		$consumidor_nome 	= trim($fetch['consumidor_nome']);
		$produto 			= trim($fetch['produto']);
		$item_abertura  	= trim($fetch['item_abertura']);
		$descricao			= trim($fetch['descricao']);
		$excluida			= trim($fetch['excluida']);

		if ($excluida=='t'){
			continue;
		}
		$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;
		$i++;

		echo "<tr class='Conteudo' bgcolor='$cor' title='$dica'>";
		    echo "<td><a href='os_press.php?os=$os' target='_blank'>".$os."</a></td>";
		echo "<th>".$posto_cnpj."</th>";
		echo "<th>".$posto_nome."</th>";
		echo "<th>".$descricao."</th>";
		echo "<th>".$abertura."</th>";
		echo "<th>".$produto."</th>";
		// echo "<td>".$abertura."</td>";
		// echo "<td>".$data_abertura."</td>";
		// echo "<td>".$consumidor_nome."</td>";
		// echo "<td>".$nome_posto."</td>";
		echo "</tr>";

	}
        $conteudo  = "</tbody>
            </table>";
	$conteudo .= "<BR><CENTER>".$count." Registros encontrados</CENTER>";


	// echo ` cp $arquivo_completo_tmp $path `;

	// echo "<script language='javascript'>";
	// echo "document.getElementById('id_download').style.display='block';";
	// echo "</script>";
	echo "<br>";
} else {
	echo "<table border='0' cellpadding='2' cellspacing='0' align='center'>";
	echo "<tr height='50'>";
	echo "<td valign='middle' align='center'><img src='imagens/atencao.gif' border='0'>
	<font size=\"2\"><b>Não foram encontrados registros com os parâmetros informados/digitados!!!</b></font>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
}

// mostrar o resultado com: Posto, OS, peça e data que lançou a peça


echo "<a href='$login_fabrica_site' target='_new'>";
echo "<IMG SRC='/assist/logos/$login_fabrica_logo' ALT='$login_fabrica_site' border='0'>";
echo "</a>";



include 'rodape.php';
include '../google_analytics.php';
