<?php
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	$admin_privilegios="gerencia,call_center";
	include 'autentica_admin.php';
	
	$data_inicial = $_GET['data_inicial'];
	$data_final   = $_GET['data_final'];
	$codigo_posto = $_GET['codigo_posto'];
	$posto_nome   = $_GET['posto_nome'];
	$estado       = $_GET['estado'];
	$motivo       = $_GET['motivo_aux'];
	$status       = $_GET['status'];
	$produto      = $_GET['produto'];

	/* HD 961085 - Lenoxx, adicionar combo de Linha e Família */
	if( $login_fabrica == 11 ){
		$linha        = $_GET['linha'];
		$familia      = $_GET['familia'];
	}
	/* fim - HD 961085 - Lenoxx, adicionar combo de Linha e Família */

	$cond         = " AND tbl_hd_chamado.data BETWEEN '$data_inicial 00:00:00' and '$data_final 23:59:59'";

	if(!empty($codigo_posto)){
		$cond .= " AND tbl_hd_chamado_extra.posto = $posto ";
	}

	if(!empty($estado)){
		$cond .= " AND tbl_cidade.estado = '$estado' ";
		$join .= " JOIN tbl_cidade ON tbl_hd_chamado_extra.cidade = tbl_cidade.cidade";
	}

	if(!empty($motivo)){
		$cond .= " AND tbl_hd_chamado_extra.hd_motivo_ligacao = $motivo ";
	}

	if(!empty($produto)){
		$cond .= " AND tbl_hd_chamado_extra.produto = $produto ";
	}

	if(!empty($status)){
		$cond .= " AND tbl_hd_chamado.status = '$status' ";
	}

	/* HD 961085 - Lenoxx, adicionado mais 2 filtros, linha e família */
	if( $login_fabrica == 11 )
	{
		if( !empty($linha) or !empty($familia)  ){
			$join  .= "  ";
		}

		if( !empty($linha) ){
			$field .= " tbl_linha.nome AS linha, ";
			$cond  .= " AND tbl_produto.linha = '$linha' ";
			$join  .= "  ";

			$coluna_titulo = "";
		}

		if( !empty($familia) ){
			$field .= " tbl_familia.descricao AS familia, ";
			$cond  .= " AND tbl_familia.familia = '$familia' ";
			$join  .= "  ";

			$coluna_titulo .= "";
		}
	}
	else
	{
		$coluna_titulo = '';
	}
	/* fim - HD 961085 - Lenoxx, adicionado mais 2 filtros, linha e família */    
?>

<style type="text/css">
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

.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
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

.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.col_left{
	padding-left: 100px;
}
</style>

<?php 
	
	$sql = "SELECT $field 
				tbl_hd_chamado.hd_chamado,
					tbl_hd_motivo_ligacao.descricao AS motivo_ligacao,
					(SELECT TO_CHAR(data::DATE,'DD/MM/YYYY') 
						FROM tbl_hd_chamado_item 
						WHERE tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado 
						ORDER BY data DESC LIMIT 1
					) AS ultima_interacao,
					tbl_admin.login 
				FROM tbl_hd_chamado
				JOIN tbl_hd_chamado_extra ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
				JOIN tbl_hd_motivo_ligacao ON tbl_hd_chamado_extra.hd_motivo_ligacao = tbl_hd_motivo_ligacao.hd_motivo_ligacao AND tbl_hd_motivo_ligacao.fabrica = $login_fabrica
				JOIN tbl_admin ON tbl_hd_chamado.atendente = tbl_admin.admin 
				LEFT JOIN tbl_posto_fabrica ON tbl_hd_chamado_extra.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				$join 
				
				LEFT JOIN tbl_produto ON tbl_hd_chamado_extra.produto = tbl_produto.produto
				LEFT JOIN tbl_linha   ON tbl_linha.linha = tbl_produto.linha AND tbl_linha.fabrica = $login_fabrica 
				LEFT JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia AND tbl_familia.fabrica = $login_fabrica 

				WHERE tbl_hd_chamado.fabrica = $login_fabrica 
				$cond 
				ORDER BY tbl_hd_chamado.hd_chamado DESC;";
	$res = pg_Query($con,$sql);
	#echo nl2br($sql);
	$total = pg_num_rows($res);

	if( $total > 0 ){

		$conteudo = "<table width='700' align='center' class='tabela' border='1'>
						<caption class='titulo_tabela'>Relatório de Motivos Call Center Detalhes</caption>
						<tr class='titulo_coluna' bgcolor='#596d9b'>
							<th><font color='#FFFFFF'>Nº Chamado</font></th>
							<th><font color='#FFFFFF'>Linha</font></th>
							<th><font color='#FFFFFF'>Família</font></th>
							<th><font color='#FFFFFF'>Motivo</font></th>
							<th><font color='#FFFFFF'>Última Interação</font></th>
							<th><font color='#FFFFFF'>Atendente</font></th>
						</tr>";

		for( $i = 0; $i < $total; $i++ ){

			$hd_chamado       = pg_result($res, $i, 'hd_chamado');
			$motivo_ligacao   = pg_result($res, $i, 'motivo_ligacao');
			$ultima_interacao = pg_result($res, $i, 'ultima_interacao');
			$login            = pg_result($res, $i, 'login');

			/* HD 961085 - Lenoxx, adicionado mais 2 filtros, linha e família */
			if( $login_fabrica == 11 )
			{
				$linha   = @pg_result($res, $i, 'linha');
				$familia = @pg_result($res, $i, 'familia');

				if( !empty($linha) and !empty($familia) )
			    {
			    	$coluna_valor  = "<td align='center'>$linha</td>
			    	                  <td align='center'>$familia</td>";
			    }
			    elseif( empty($linha) and empty($familia) )
				{
					$coluna_valor  = "<td align='center'>&nbsp;</td>
			    	                  <td align='center'>&nbsp;</td>";
				}
				elseif( !empty($linha) and empty($familia) )
			    {
			    	$coluna_valor  = "<td align='center'>$linha</td>
			    	                  <td align='center'>&nbsp;</td>";
			    }
			    elseif( empty($linha) and !empty($familia) )
				{
					$coluna_valor  = "<td align='center'>&nbsp;</td>
					                  <td align='center'>$familia</td>";
				}
			}
			/* fim - HD 961085 - Lenoxx, adicionado mais 2 filtros, linha e família */

			$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

			$conteudo .= "<tr bgcolor='$cor'>
							<td align='center'>
							    <a href='callcenter_interativo_new.php?callcenter=$hd_chamado' target='_blank'>
							    	$hd_chamado
							    </a>
							</td>
								$coluna_valor
							<td align='left'>$motivo_ligacao</td>
							<td align='center'>$ultima_interacao</td>
							<td align='center'>$login</td>
						  </tr>";
		}

		$conteudo .= "</table><br>";

		echo $conteudo;
		
		$data         = date('Y-m-d');
		$nome_arquivo = "xls/relatorio-motivo-callcenter-detalhe-$data-$login_fabrica.xls";
		$fp           = fopen("$nome_arquivo", "w");
		fwrite($fp,$conteudo);
		fclose($fp);
		
		echo "<center><input type='button' value='Download Excel' onclick=\"window.location.href='$nome_arquivo';\"></center>";
	} else {
		echo "<center>Nenhum resultado encontrado</center>";
	}


?>
