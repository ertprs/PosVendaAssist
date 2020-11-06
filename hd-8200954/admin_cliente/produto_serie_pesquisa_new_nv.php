<?php
/*
	Esse arquivo foi criado em substituição do arquivo produto_serie_pesquisa_fricon,
	pois outras fábricas necessitam usar esse programa também.
*/
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$fale_conosco_esmaltec = trim($_REQUEST["fale_conosco_esmaltec"]);
if ($fale_conosco_esmaltec == true) {
	$login_fabrica = 30;
	$cond_ativo = " AND tbl_produto.ativo IS TRUE";
} else {
	include 'autentica_admin.php';
	$cond_ativo = "";
}

$mapa_linha = trim (strtolower ($_REQUEST['mapa_linha']));
$tipo       = trim (strtolower ($_REQUEST['tipo']));
$serie      = trim($_REQUEST["campo"]);
$pos        = trim($_REQUEST["pos"]);
if ($login_fabrica == 30 && $fale_conosco_esmaltec == true) {
	$ajuste_css = "padding-top: 10px;";
} else {
	$ajuste_css = "";
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
    <title> Pesquisa Produto... </title>
    <meta name="Author" content="">
    <meta name="Keywords" content="">
    <meta name="Description" content="">
    <meta http-equiv=pragma content=no-cache>
    <link rel="stylesheet" type="text/css" href="js/thickbox.css" media="screen">
    <script type="text/javascript" src="../js/jquery-1.4.2.js"></script>
    <script src="../plugins/jquery/jquery.tablesorter.min.js" type="text/javascript"></script>
    <script src="js/thickbox.js" type="text/javascript"></script>
    <style type="text/css">
    @import "../css/lupas/lupas.css";
    body {
        margin: 0;
        font-family: Arial, Verdana, Times, Sans;
        background: #fff;
    }
    </style>
    <script type="text/javascript">
        $(document).ready(function() {
        $("#gridRelatorio").tablesorter();
    });
    </script>
</head>
<body>
<?php if ($login_fabrica != 30 && $fale_conosco_esmaltec != true) {?>
<div class="lp_header">
	<a href='' onclick='window.parent.Shadowbox.close();' style='border: 0;'>
		<img src='css/modal/excluir.png' alt='Fechar' class='lp_btn_fechar' />
	</a>
</div>
<?php }?>
<div class='lp_nova_pesquisa' style="text-align: center;<?php echo $ajuste_css;?>">
	<form action="<?=$_SERVER["PHP_SELF"]?>" method='POST' name='nova_pesquisa'>
		<input type="hidden" name="mapa_linha" value="<?=$mapa_linha?>" />
		<input type="hidden" name="tipo" value="<?=$tipo?>" />
		<input type="hidden" name="pos" value="<?=$pos?>" />
		<label>Série: </label><input type="text" name="campo" value="<?=$serie?>" placeholder="Digite a série..." />
		<input type="submit" value="Pesquisar" />
	</form>
</div>
<?
if ($tipo == "serie") {
    if(strlen($serie) > 0) {
        $serie = strtoupper($serie); ?>
    <div class='lp_pesquisando_por'>Pesquisando por série: <?=$serie?></div>
<?php
        // HD 3779808 - Bloquear produtos com NS nesta tabela.
        if (in_array($login_fabrica, array(24,30))) {
            // autorizado usar o bigint por Ronald/Pinsard
            $sql_serie = "SELECT tbl_produto_serie.produto_serie,
                                 tbl_produto_serie.observacao
                            FROM tbl_produto_serie
                           WHERE fabrica = $login_fabrica
                             -- AND produto = {$produto}
                             AND '{$serie}'::BIGINT
                                 BETWEEN serie_inicial::BIGINT
                                     AND serie_final::BIGINT";
            $res_serie = pg_query($con, $sql_serie);

            if (pg_num_rows($res_serie) > 0) {
                $observacao = '<strong>Número de Série Bloqueado</strong>' .
                    "<br>\n" . pg_fetch_result($res_serie, 0, 'observacao');
                die("<div class='lp_msg_erro'>$observacao</div>");
            }
        }

        if ($login_fabrica == 161) {

            $campos = ", tbl_revenda.revenda, tbl_revenda.nome, tbl_revenda.cnpj ";
            $join  = " LEFT JOIN tbl_revenda ON tbl_revenda.cnpj = tbl_numero_serie.cnpj ";

        }

        $sql = "SELECT	tbl_numero_serie.serie,
                        tbl_numero_serie.produto,
                        tbl_numero_serie.ordem  ,
                        tbl_numero_serie.data_fabricacao,
                        tbl_numero_serie.data_venda,
                        tbl_numero_serie.bloqueada_garantia,
                        tbl_produto.referencia  ,
                        tbl_produto.descricao   ,
                        tbl_produto.linha       ,
                        tbl_produto.voltagem,
                        tbl_serie_controle.motivo
                        {$campos}
                FROM     tbl_numero_serie
                JOIN     tbl_produto ON tbl_produto.produto = tbl_numero_serie.produto
                LEFT JOIN tbl_serie_controle ON tbl_serie_controle.serie = tbl_numero_serie.serie
                    AND tbl_serie_controle.fabrica = $login_fabrica
                {$join}
                WHERE    tbl_numero_serie.serie = '$serie'
                AND      tbl_numero_serie.fabrica = $login_fabrica 
                {$cond_ativo} limit 30";

        if ($login_fabrica == 161) {
            $sql = "SELECT tbl_produto.descricao,
                        tbl_produto.referencia,
                        tbl_produto.voltagem,
                        tbl_produto.produto,
                        tbl_produto.linha,
                        tbl_numero_serie.serie,
                        tbl_cliente.nome AS cliente_nome,
                        tbl_cliente.cpf,
                        tbl_cliente.numero AS cliente_numero,
                        tbl_cliente.complemento AS cliente_complemento,
                        tbl_cliente.cep AS cliente_cep,
                        tbl_cliente.email AS cliente_email,
                        tbl_cliente.fone AS cliente_fone,
                        tbl_cliente.cliente as id_cliente,
                        tbl_venda.nota_fiscal,
                        TO_CHAR(data_nf, 'DD/MM/YYYY') AS data_nf
                        $campos
                    FROM tbl_numero_serie
					left JOIN tbl_venda ON tbl_numero_serie.serie = tbl_venda.serie
					left join tbl_cliente ON tbl_venda.cliente = tbl_cliente.cliente
                    JOIN tbl_produto On tbl_numero_serie.produto = tbl_produto.produto
                    $join
                    WHERE tbl_numero_serie.fabrica = $login_fabrica
                    AND tbl_numero_serie.serie = '$serie'";
        }

        $res = pg_query($con,$sql);

        if (!pg_num_rows($res)) {
            ?>
            <div class='lp_msg_erro'>Produto com a série '<?=$serie?>' não encontrado</div>
            <?
        }

        // Descrição - Referência - Mapa_Linha - Série
        if(pg_num_rows($res) >= 1) {
?>
<table style='width:100%; border: 0;' cellspacing='1' class='lp_tabela' id='gridRelatorio'>
	<thead>
		<tr>
            <?php if ($login_fabrica == 161): ?>
            <th>Série</th>
            <th>Nome</th>
            <th>CPF</th>
            <th>NF</th>
            <th>DATA NF</th>
            <?php else: ?>
			<th>Série</th>
			<th>Referência</th>
			<th>Descrição</th>
			<th>Linha</th>
			<th>Voltagem</th>
			<?php endif ?>
		</tr>
	</thead>
	<tbody>
<?php
		$mostraDefeitos = "";

		for($i = 0; $i < pg_num_rows($res); $i++) {
			$cor        = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
			$serie      = pg_fetch_result($res, $i, 'serie');
			$produto    = pg_fetch_result($res, $i, 'produto');
			$ordem      = pg_fetch_result($res, $i, 'ordem');
			$referencia = pg_fetch_result($res, $i, 'referencia');
			$descricao  = pg_fetch_result($res, $i, 'descricao');
			$linha      = pg_fetch_result($res, $i, 'linha');
			$voltagem   = pg_fetch_result($res, $i, 'voltagem');
			if($login_fabrica == 52){
				$bloqueada_garantia = pg_fetch_result($res, $i, 'bloqueada_garantia');
				$motivo             = pg_fetch_result($res, $i, 'motivo');
			}
			if($login_fabrica == 161){
				$revenda = pg_fetch_result($res, $i, "revenda");
				$revenda_nome = pg_fetch_result($res, $i, "nome");
				$cnpj    = pg_fetch_result($res, $i, "cnpj");
			}

			$onclick = (trim($descricao)  != '' ? "'$descricao'"    : "''")   .
					   (trim($referencia) != '' ? ", '$referencia'" : ", ''") .
					   (trim($serie)      != '' ? ", '$serie'"      : ", ''") .
					   (trim($voltagem)   != '' ? ", '$voltagem'"   : ", ''") .
					   (trim($produto)    != '' ? ", $produto"      : ", ''") .
					   (trim($ordem)      != '' ? ", '$ordem'"      : ", ''") .
					   (($mapa_linha == 't')    ? ", $linha"        : ", ''") .
					   ((strlen($pos) > 0)      ? ", '$pos'"        : ", ''");

			if (in_array($login_fabrica, array(30,74,158,165))) {
				$data_fabricacao = strtotime(pg_fetch_result($res, $i, 'data_fabricacao'));
				$data_fabricacao = date("d/m/Y", $data_fabricacao);
				$onclick        .= (trim($data_fabricacao) != '' ? ", '$data_fabricacao'" : ", ''");

				if (in_array($login_fabrica, array(30, 74))) {
					// autorizado usar o bigint por Ronald/Pinsard
					$sql_serie = "SELECT tbl_produto_serie.produto_serie,
										 tbl_produto_serie.observacao
									FROM tbl_produto_serie
								   WHERE fabrica = $login_fabrica
									 AND produto = {$produto}
									 AND '{$serie}'::BIGINT BETWEEN serie_inicial::BIGINT AND serie_final::BIGINT";
					$res_serie = pg_query($con, $sql_serie);
					if(pg_num_rows($res_serie) > 0){
						$observacao = pg_fetch_result($res_serie, 0, 'observacao');
						$serie_inicial_final = "true|".$observacao;
					}else{
						$serie_inicial_final = "false";
					}
					$onclick .= (trim($serie_inicial_final) != '' ? ", '$serie_inicial_final'" : ", ''");
				}

				if ($login_fabrica == 158) {
					$data_venda  	= strtotime(pg_fetch_result($res, $i, 'data_venda'));
					$data_venda = date("d/m/Y", $data_venda);
					$onclick .= (trim($data_venda) 	 != '' ? ", '$data_venda'" : ", ''");
				}
				$mostraDefeitos = " window.parent.mostraDefeitos('Reclamado', '".$referencia."');";
			}

			if($login_fabrica == 52 && $bloqueada_garantia != "f"){
				?>
				<tr style="background: <?=$cor?>";>
					<td><?=$serie?></td>
					<td>Número de série bloqueado</td>
					<td colspan="3"><?=$motivo?></td>
				</tr>
				<?php

				echo "<script>
					window.parent.document.getElementById('serie_$pos').value='';
					setTimeout(function(){window.parent.Shadowbox.close();},5000);
					</script>";
			}else{
				if($login_fabrica == 161){
					//$onclick .= ", '{$revenda}', '{$nome}', '{$cnpj}'";

					$id_cliente = pg_fetch_result($res, $i, 'id_cliente');
                    $nome = pg_fetch_result($res, $i, 'cliente_nome');
                    $cpf = pg_fetch_result($res, $i, 'cpf');
                    $cliente_cep = pg_fetch_result($res, $i, 'cliente_cep');
                    $cliente_numero = pg_fetch_result($res, $i, 'cliente_numero');
                    $cliente_complemento = pg_fetch_result($res, $i, 'cliente_complemento');
                    $cliente_email = pg_fetch_result($res, $i, 'cliente_email');
                    $cliente_fone = pg_fetch_result($res, $i, 'cliente_fone');
                    $nota_fiscal = pg_fetch_result($res, $i, 'nota_fiscal');
                    $data_nf = pg_fetch_result($res, $i, 'data_nf');

                    if (strlen($cliente_cep) == 8) {
                        $cliente_cep = substr($cliente_cep, 0, 5) . '-' . substr($cliente_cep, 5, 3);
                    }

                    $onclick .= ", '{$id_cliente}', '{$nome}', '{$cpf}', '{$nota_fiscal}', '{$data_nf}'";
                    $onclick .= ", '{$revenda}', '{$revenda_nome}', '{$cnpj}'";
                    $onclick .= ", '{$cliente_cep}', '{$cliente_numero}', '{$cliente_complemento}'";
                    $onclick .= ", '{$cliente_email}', '{$cliente_fone}'";

                    echo "<tr style='background: $cor' onclick=\"window.parent.retorna_serie($onclick);$mostraDefeitos window.parent.Shadowbox.close();\">";

                    echo "
                        <td style='text-align: center;'>$serie</td>
                        <td style='text-align: center;'>$nome</td>
                        <td style='text-align: center;'>$cpf</td>
                        <td style='text-align: center;'>$nota_fiscal</td>
                        <td style='text-align: center;'>$data_nf</td>
                        </tr>";
                } else {
                    echo "<tr style='background: $cor' onclick=\"window.parent.retorna_serie($onclick);$mostraDefeitos window.parent.Shadowbox.close();\">
                        <td style='text-align: center;'>$serie</td>
                        <td style='text-align: center;'>$referencia</td>
                        <td style='text-align: center;'>$descricao</td>
                        <td style='text-align: center;'>$linha</td>
                        <td style='text-align: center;'>$voltagem</td>
                        </tr>";
                }
			}
		}
	?>
	</tbody>
</table>
<?
	} else if(pg_num_rows($res) == 1){
			$serie      = pg_fetch_result($res, 0, 'serie');
			$produto    = pg_fetch_result($res, 0, 'produto');
			$ordem      = pg_fetch_result($res, 0, 'ordem');
			$referencia = pg_fetch_result($res, 0, 'referencia');
			$descricao  = pg_fetch_result($res, 0, 'descricao');
			$linha      = pg_fetch_result($res, 0, 'linha');
			$voltagem   = pg_fetch_result($res, 0, 'voltagem');

			$onclick = (trim($descricao)  != '' ? "'$descricao'"    : "''")   .
					   (trim($referencia) != '' ? ", '$referencia'" : ", ''") .
					   (trim($serie)      != '' ? ", '$serie'"      : ", ''") .
					   (trim($voltagem)   != '' ? ", '$voltagem'"   : ", ''") .
					   (trim($produto)    != '' ? ", $produto"      : ", ''") .
					   (trim($ordem)      != '' ? ", '$ordem'"      : ", ''") .
					   (($mapa_linha == 't')    ? ", $linha"        : ", ''") .
					   ((strlen($pos) > 0)      ? ", '$pos'"        : ", ''");

			if(in_array($login_fabrica, array(74,158))){
				$data_fabricacao  	= strtotime(pg_fetch_result($res, $i, 'data_fabricacao'));
				$data_fabricacao = date("d/m/Y", $data_fabricacao);
				$onclick .= (trim($data_fabricacao) 	 != '' ? ", '$data_fabricacao'" : ", ''");
				if ($login_fabrica == 158) {
					$data_venda  	= strtotime(pg_fetch_result($res, $i, 'data_venda'));
					$data_venda = date("d/m/Y", $data_venda);
					$onclick .= (trim($data_venda) 	 != '' ? ", '$data_venda'" : ", ''");
				}
			}
			?>
			<script type="text/javascript">
				window.parent.retorna_serie(<?=$onclick?>); window.parent.Shadowbox.close();
				<? if (in_array($login_fabrica, array(74,158))) { ?>
					window.parent.mostraDefeitos('Reclamado', '<?= $referencia ?>');
				<? } ?>
			</script>
			<?
	}
} else { ?>

	<div class='lp_msg_erro'>Informar toda ou parte da informação para realizar a pesquisa!</div>
<? } } ?>

</body>
</html>
