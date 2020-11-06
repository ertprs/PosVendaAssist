<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'funcoes.php';

$pedido = trim($_GET['pedido']);

if (strlen ($pedido) > 0) {

	if ($login_fabrica == 175){
        $valoresAdicionais = "
            JSON_FIELD('valor_frete',tbl_pedido.valores_adicionais)    AS pedidoValorFrete,
            JSON_FIELD('valor_despesa',tbl_pedido.valores_adicionais)  AS pedidoValorDespesa,
            JSON_FIELD('valor_seguro',tbl_pedido.valores_adicionais)   AS pedidoValorSeguro,
            JSON_FIELD('valor_desconto',tbl_pedido.valores_adicionais) AS pedidoValorDesconto,
        ";
    }

    if ($login_fabrica == 24 && qual_tipo_posto($login_posto) == 696){
        $valoresAdicionais = "
            tbl_pedido.valores_adicionais,
        ";
    }
    if ($login_fabrica == 183){
    	$valoresAdicionais = "tbl_pedido.valores_adicionais,";
    }
	$sql = "SELECT seu_pedido,
			       TO_CHAR(tbl_pedido.data,'DD/MM/YYYY') AS data,
			       CASE
                               WHEN $login_fabrica = 157 THEN
                                   COALESCE(tbl_posto_fabrica.desconto, 0)
                               ELSE
                                   COALESCE(tbl_pedido.desconto, 0)
	                       END AS desconto,
			       tbl_posto.nome AS posto,
			       tbl_posto.cnpj,
			       $valoresAdicionais
			       tbl_posto_fabrica.contato_fone_comercial AS fone,
			       tbl_tabela.sigla_tabela,
			       tbl_condicao.descricao AS condicao,
				   tbl_pedido.valor_frete,
				   tbl_pedido.total AS pedido_total,
				   CASE
				       WHEN tbl_pedido.fabrica IN(88,120,201,131,156)
				       THEN tbl_pedido.tipo_frete
				       ELSE tbl_condicao.frete
				   END                       AS frete,
			       tbl_tipo_pedido.descricao AS tipo_pedido
			  FROM tbl_pedido
			  JOIN tbl_posto         ON tbl_posto.posto = tbl_pedido.posto
			  JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
			  LEFT JOIN tbl_condicao      ON tbl_condicao.condicao = tbl_pedido.condicao
									AND tbl_condicao.fabrica = {$login_fabrica}
			  LEFT JOIN tbl_tabela        ON tbl_tabela.tabela = tbl_pedido.tabela AND tbl_tabela.fabrica = {$login_fabrica}
			  JOIN tbl_tipo_pedido   ON tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido
								    AND tbl_tipo_pedido.fabrica = {$login_fabrica}
			 WHERE tbl_pedido.pedido  = {$pedido}
			   AND tbl_pedido.fabrica = {$login_fabrica}";

	$res = pg_query($con,$sql);

	if(pg_num_rows($res) > 0){

		$dados_pedido = pg_fetch_all($res);

		$num_pedido = ($dados_pedido[0]['seu_pedido']) ? : $pedido;
		$valor_frete = $dados_pedido[0]['valor_frete'];
		$valores_adicionais = trim(pg_fetch_result($res, 0, 'valores_adicionais'));

		if ($login_fabrica == 183){
            if (!empty($valores_adicionais)){
                $valores_adicionais = json_decode($valores_adicionais, true);
                extract($valores_adicionais);

                if (strlen(trim($id_posto_pedido)) > 0){
                    $sql_posto_info = "
                        SELECT 
                            tbl_posto.nome,
                            tbl_posto_fabrica.codigo_posto,
                            tbl_posto.cnpj
                        FROM tbl_posto 
                        JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
                        WHERE tbl_posto.posto = {$id_posto_pedido} ";
                    $res_posto_info = pg_query($con, $sql_posto_info);

                    if (pg_num_rows($res_posto_info) > 0){
                        $codigo_posto_info = pg_fetch_result($res_posto_info, 0, "codigo_posto");
                        $nome_posto_info = pg_fetch_result($res_posto_info, 0, "nome");
                        $cnpj_posto_info = pg_fetch_result($res_posto_info, 0, "cnpj");
                    }
                }
            }
        }

		if ($login_fabrica == 175){
            $pedidoValorFrete     = pg_fetch_result($res, 0, 'pedidoValorFrete');
            $pedidoValorDespesa   = pg_fetch_result($res, 0, 'pedidoValorDespesa');
            $pedidoValorSeguro    = pg_fetch_result($res, 0, 'pedidoValorSeguro');
            $pedidoValorDesconto  = pg_fetch_result($res, 0, 'pedidoValorDesconto');
        	
        	$pedido_total 		  = pg_fetch_result($res, 0, 'pedido_total');
        }



		$ordenar = '';
		if (in_array($login_fabrica, array(87))) {
			$ordenar = 'ORDER BY tbl_peca.descricao';
		}
		$sql = "SELECT 	tbl_peca.referencia,
						tbl_peca.descricao,
						tbl_pedido_item.valores_adicionais::text AS valores_item,
						CASE
                            WHEN $login_fabrica = 87 AND tbl_pedido_item.preco_base IS NOT NULL THEN
                                tbl_pedido_item.preco_base
                            ELSE
                                tbl_pedido_item.preco
                        END as preco,
						tbl_pedido_item.qtde - qtde_cancelada as qtde,
						tbl_peca.ipi,
						tbl_pedido_item.preco_base AS preco_base_item,
						tbl_pedido_item.pedido_item
					FROM tbl_pedido_item
					JOIN tbl_peca ON tbl_peca.peca = tbl_pedido_item.peca
					AND tbl_peca.fabrica = {$login_fabrica}
					WHERE tbl_pedido_item.pedido = {$pedido}
					{$ordenar}";
		$resItens = pg_query($con,$sql);

		if(pg_num_rows($resItens) > 0){
			$dados_itens = pg_fetch_all($resItens);
		}

	}

?>

	<style type="text/css">

		body {
			margin: 0px,0px,0px,0px;
			text-align: center;
		}

		.titulo {
			font-family: normal Verdana, Geneva, Arial, Helvetica, sans-serif;
			font-size: 12px;
			font-weight: bold;
			text-align: left;
			color: #000000;
			background: #ffffff;
			border-bottom: solid 1px #000000;
			border-right: solid 1px #000000;
			border-left: solid 1px #000000;
			padding: 1px 1ex 1px 4px;
		}

		.conteudo {
			font-family: bold Verdana, Geneva, Arial, Helvetica, sans-serif;
			font-size: 12px;
			text-align: left;
			background: #ffffff;
			border-right: solid 1px #000000;
			border-left: solid 1px #000000;
			border-bottom: solid 1px #000000;
			padding: 1px 1ex 1px 3px;
		}

		.borda {
			border: solid 1px #000000;
		}

		.menu_top {
			text-align: center;
			font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
			font-size: 10px;
			font-weight: bold;
			border: 1px solid #000000;
			color:#000000;
			padding: 1px,1px,1px,1px;
		}

		.table_line {
			text-align: left;
			font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
			font-size: 10px;
			font-weight: normal;
			border: 1px solid #000000;
			padding: 1px,1px,1px,1px;
		}

		.table_line1 {
			text-align: left;
			font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
			font-size: 09px;
			font-weight: normal;
			border: 1px solid #000000;
		}
	</style>
	<table width="700px" align="center" border="1" cellpadding="1" cellspacing="0">
		<tr>
			<td colspan="5" class="titulo" style="font-size:15px; text-align:center;">DADOS DO PEDIDO</td>
		<tr>
		<tr>
			<td class="titulo">Pedido</td>
			<td class="titulo">Data</td>
			<td class="titulo">Tabela</td>
			<td class="titulo">Condição Pagamento</td>
			<td class="titulo">Tipo Pedido</td>
		</tr>

		<tr>
			<td class="conteudo"><?=$num_pedido?></td>
			<td class="conteudo"><?=$dados_pedido[0]["data"]?></td>
			<td class="conteudo"><?=$dados_pedido[0]["sigla_tabela"]?></td>
			<td class="conteudo"><?=$dados_pedido[0]["condicao"]?></td>
			<td class="conteudo"><?=$dados_pedido[0]["tipo_pedido"]?></td>
		</tr>

		<tr>
			<td class="titulo">CNPJ</td>
			<td class="titulo" colspan="3">Posto</td>
			<td class="titulo">Fone</td>
		</tr>

		<tr>
			<td class="conteudo"><?=$dados_pedido[0]["cnpj"]?></td>
			<td class="conteudo" colspan="3"><?=$dados_pedido[0]["posto"]?></td>
			<td class="conteudo"><?=$dados_pedido[0]["fone"]?></td>
		</tr>

		<?php if($login_fabrica == 183 AND in_array($login_tipo_posto_codigo, array("Rev", "Rep")) AND strlen(trim($nome_posto_info)) > 0){ ?>
                <tr>
                    <td class='titulo'><?=traduz('CNPJ.do.cliente', $con)?></td>
                    <td class='titulo'><?=traduz('codigo.do.cliente', $con)?></td>
                    <td class='titulo' colspan="3"><?=traduz('nome.do.cliente', $con)?></td>
                </tr>
                <tr>
                    <td class='conteudo'><?=$cnpj_posto_info?></td>
                    <td class='conteudo'><?=$codigo_posto_info?></td>
                    <td class='conteudo' colspan="3"><?=$nome_posto_info?></td>
                </tr>
        <?php } ?>

		<?php if ($login_fabrica == 24 && qual_tipo_posto($login_posto) == 696){ 
				$valores_adicionais = json_decode($dados_pedido[0]['valores_adicionais'], true);
                $registro_funcionario = utf8_decode($valores_adicionais['registro_funcionario']);
                $departamento_funcionario = utf8_decode($valores_adicionais['departamento_funcionario']);
                $nome_funcionario = utf8_decode($valores_adicionais['nome_funcionario']);
		?>
				<tr>
					<td class="titulo">Registro</td>
					<td class="titulo">Departamento</td>
					<td class="titulo" colspan="3">Nome Funcionário</td>
				</tr>			

				<tr>
					<td class="conteudo"><?=$registro_funcionario?></td>
					<td class="conteudo"><?=$departamento_funcionario?></td>
					<td class="conteudo" colspan="3"><?=$nome_funcionario?></td>
				</tr>

		<?php } ?>
	</table>
	<table style="padding-top:5px;" width="700px" align="center" border="" cellpadding="1" cellspacing="0">
		<tr>
			<td class="titulo">Referência</td>
			<td class="titulo">Descrição</td>
			<td class="titulo">Qtde</td>
			<td class="titulo">Preço</td>
			<?php if ($login_fabrica == 175){ ?>
				<td class="titulo"><?=traduz('Aliq. IPI')?></td>
	            <td class="titulo"><?=traduz('Base. IPI')?></td>
	            <td class="titulo"><?=traduz('IPI')?></td>
	            <td class="titulo"><?=traduz('Aliq. ICMS')?></td>
	            <td class="titulo"><?=traduz('Base ICMS')?></td>
	            <td class="titulo"><?=traduz('ICMS')?></td>
	            <td class="titulo"><?=traduz('Total impostos')?></td>
	            <td class="titulo"><?=traduz('Total anterior')?></td>
			<?php } ?>
			<?php
			if (in_array($login_fabrica, array(42,157))) {
			?>
				<td class="titulo">IPI</td>
			<?php
			}
			?>
			<td class="titulo">Total Item </td>
			<?php
			if (in_array($login_fabrica, array(40))) {
			?>
			<td class="titulo">Total Item + IPI</td>
			<?php
			}
			?>
		</tr>
<?php
	if($login_fabrica != 138){
		$total_pedido = (float)$valor_frete;
	}

	foreach ($dados_itens as $key => $value) {
		if(($login_fabrica == 42 OR $login_fabrica > 138) and $login_fabrica <> 179) {
            $ipi            = $value['ipi'];
            $preco          = $value['preco'];
            $qtde           = $value['qtde'];
            $total_item     = $value['preco'] * $value['qtde'];
			if(!$replica_einhell and !in_array($login_fabrica, [42,169,170])) {
				$total_item_ipi = ($preco + ($preco * $ipi)/100) *($qtde);
			}else{
				$total_item_ipi = $preco*$qtde;
			}
            $total_pedido   += $total_item_ipi;

            if ($login_fabrica == 175){
            	$preco_base_item = $value['preco_base_item'];
                $valoresItens = $value['valores_item'];
                        
                $valoresItens = str_replace('"{', '{', $valoresItens);
                $valoresItens = str_replace('}"', '}', $valoresItens);
                $valoresItens = str_replace('\\', '', $valoresItens);
                $valoresItens = json_decode($valoresItens, true);
                
                if (!empty($valoresItens['aliq_ipi'])){
                    $aliq_ipi_item = $valoresItens['aliq_ipi'];
                }else{
                    $aliq_ipi_item = 0;
                }
                if (!empty($valoresItens['base_ipi'])){
                    $base_ipi_item = $valoresItens['base_ipi'];
                }else{
                    $base_ipi_item = 0;
                }
                if (!empty($valoresItens['ipi'])){
                    $ipi_item = $valoresItens['ipi'];
                }else{
                    $ipi_item = 0;
                }
                if (!empty($valoresItens['aliq_icms'])){
                    $aliq_icms_item = $valoresItens['aliq_icms'];
                }else{
                    $aliq_icms_item = 0;
                }
                if (!empty($valoresItens['base_icms'])){
                    $base_icms_item = $valoresItens['base_icms'];
                }else{
                    $base_icms_item = 0;
                }
                if (!empty($valoresItens['icms'])){
                    $icms_item = $valoresItens['icms'];
                }else{
                    $icms_item = 0;
                }
                if (!empty($valoresItens['total_impostos'])){
                    $total_impostositem = $valoresItens['total_impostos'];
                }else{
                    $total_impostositem = 0;
                }

                $total_icms_item += $icms_item;
                $total_ipi_item += $ipi_item;
                $total_geral_impostos_item += $total_impostositem;
            }

        }elseif(in_array($login_fabrica, array(40))){
            $ipi            = $value['ipi'];
            $preco          = $value['preco'];
            $qtde           = $value['qtde'];
            $total_item     = $value['preco'] * $value['qtde'];
            $total_item_ipi = ($preco + ($preco * $ipi)/100) *($qtde);
            $total_com_ipi  += $total_item_ipi;
            $total_pedido   += $total_item;
		}else{

			if (in_array($login_fabrica, array(87))) {
                $sqlTotItem = "SELECT total_item FROM tbl_pedido_item_jacto WHERE pedido_item = {$value['pedido_item']} AND total_item IS NOT NULL";
                $qryTotItem = pg_query($con, $sqlTotItem);
                if (pg_num_rows($qryTotItem) == 1) {
                    $total_item = pg_fetch_result($qryTotItem, 0, 'total_item');
                }else{
					$total_item = $value['preco'] * $value['qtde'];
                }

				$total_item = $total_item;
				$total_pedido += $total_item;
			}else{
				$total_item = $value['preco'] * $value['qtde'];
				$total_pedido += $total_item;
			}
		}
		
		echo "<tr>
				<td class='conteudo'>{$value['referencia']}</td>
				<td class='conteudo'>{$value['descricao']}</td>
				<td class='conteudo' style='text-align:right;padding-right:2ex'>{$value['qtde']}</td>
				<td class='conteudo' style='text-align:right'>".number_format($value['preco'],2,",",".")."</td>";
		if ($login_fabrica == 175){		
		?>
			<td align='right'><?=number_format($aliq_ipi_item,2,",",".")?></td>
	        <td align='right'><?=number_format($base_ipi_item,2,",",".")?></td>
	        <td align='right'><?=number_format($ipi_item,2,",",".")?></td>
	        <td align='right'><?=number_format($aliq_icms_item,2,",",".")?></td>
	        <td align='right'><?=number_format($base_icms_item,2,",",".")?></td>
	        <td align='right'><?=number_format($icms_item,2,",",".")?></td>
	        <td align='right'><?=number_format($total_impostositem,2,",",".")?></td>
	        <td align='right'><?=number_format(($preco_base_item * $qtde), 2, ',', '.')?></td>
	    <?php
		}
		if (in_array($login_fabrica, array(42,157))) {
			echo "<td class='conteudo' style='text-align: right;' >".$ipi."</td>";
		}
		echo "<td class='conteudo' style='text-align:right'>" ; 
		echo (in_array($login_fabrica,array(42,157,168))) ? number_format($total_item_ipi,2,",",".") : number_format($total_item,2,",",".");
		echo "<td>";
		if (in_array($login_fabrica, array(40))) {
			echo "<td class='conteudo' style='text-align:right'>".number_format($total_item_ipi,2,",",".")."<td>";
		}
		echo "</tr>";

	}
?>
<?php if ($valor_frete): 
	if($login_fabrica != 138){ ?>
		<tr>
			<td class="titulo" colspan="4" style="text-align:right;">Valor do Frete</td>
			<td class="conteudo" style="text-align:right"><?=number_format($valor_frete,2,",",".")?></td>
		</tr>
<?php }
	endif; 

	$colspan = (in_array($login_fabrica, array(42,157))) ? 5 : 4;
?>		
		<?php if ($login_fabrica == 175){ 
			$colspan = 12;
		?>
			<tr class='titulo'>
                <td colspan="12"><?=traduz('VALOR DO FRETE')?></td>
                <td><?=(!empty($pedidoValorFrete)) ? number_format($pedidoValorFrete,2,",",".") : "0,00"?></td>
            </tr>
            
            <tr class='titulo'>
                <td colspan="12"><?=traduz('VALOR DA DESPESA')?></td>
                <td><?= (!empty($pedidoValorDespesa)) ? number_format($pedidoValorDespesa,2,",",".") : "0,00"?></td>
            </tr>
            
            <tr class='titulo'>
                <td colspan="12"><?=traduz('VALOR DO SEGURO')?></td>
                <td><?= (!empty($pedidoValorSeguro)) ? number_format($pedidoValorSeguro,2,",",".") : "0,00" ?></td>
            </tr>
            
            <tr class='titulo'>
                <td colspan="12"><?=traduz('VALOR DO ICMS RETIDO')?></td>
                <td><?= (!empty($total_icms_item)) ? number_format($total_icms_item,2,",",".") : "0,00" ?></td>
            </tr>

            <tr class='titulo'>
                <td colspan="12"><?=traduz('VALOR TOTAL DO IPI')?></td>
                <td><?= (!empty($total_ipi_item)) ? number_format($total_ipi_item,2,",",".") : "0,00" ?></td>
            </tr>

            <tr class='titulo'>
                <td colspan="12"><?=traduz('VALOR TOTAL DOS IMPOSTOS')?></td>
                <td><?= (!empty($total_geral_impostos_item)) ? number_format($total_geral_impostos_item,2,",",".") : "0,00" ?></td>
            </tr>

            <tr class='titulo'>
                <td colspan="12"><?=traduz('TOTAL DESCONTO')?></td>
                <td><?= (!empty($pedidoValorDesconto)) ? number_format($pedidoValorDesconto,2,",",".") : "0,00" ?></td>
            </tr>
		<?php } ?>
		<tr>
			<td class="titulo" colspan="<?=$colspan?>" style="text-align:right;">Total</td>
			<?php if ($login_fabrica == 175){ ?>
				<td class="conteudo" style="text-align:right;font-weight:bold"><?=number_format($pedido_total,2,",",".")?></td>
			<?php }else{ ?>
				<td class="conteudo" style="text-align:right;font-weight:bold"><?=number_format($total_pedido,2,",",".")?></td>
			<?php } ?>
			
		<?php
		if (in_array($login_fabrica, array(40))) {
		?>
		<td class="conteudo" style="text-align:right;font-weight:bold"><?=number_format($total_com_ipi,2,",",".")?></td>
		<?php
		}
		?>
		</tr>
<?php
    if($login_fabrica > 138 AND $login_fabrica != 175){
        $desconto = $dados_pedido[0]["desconto"];
        $valor_desconto = ($total_pedido * $desconto) / 100;
        $total_desconto = $total_pedido - $valor_desconto;
?>
        <tr>
            <td class="titulo" colspan="<?=$colspan?>" style="text-align:right">Desconto de <?=number_format($desconto,2,",",".")?>%</td>
            <td class="conteudo" style="text-align:right"><?=number_format($valor_desconto,2,",",".")?></td>
        </tr>
        <tr>
            <td class="titulo" colspan="<?=$colspan?>" style="text-align:right">Total com desconto</td>
            <td class="conteudo" style="text-align:right"><?=number_format($total_desconto,2,",",".")?></td>
        </tr>
<?php
    }
?>
	</table>
	<br /><br />
	<center>
		__________________________________________ <br />
		<span style="font-size:10px; font-weight:bold;">Assinatura do Responsável</span>
	</center>

<?php if ($login_fabrica == 24 && qual_tipo_posto($login_posto) == 696) { ?>
		<br /><br />
		<center>
			__________________________________________ <br />
			<span style="font-size:10px; font-weight:bold;">Assinatura do Funcionário</span>
		</center>
		<br />
<?php } ?>
	<script>	

		<? if($login_fabrica == 24  && qual_tipo_posto($login_posto) == 696) : ?>

			var bodyHTML = document.body.innerHTML;
			var copies = 2;

			for(i = 0; i < copies; i++){
				var copiedNode = document.createElement("div");
				copiedNode.innerHTML = bodyHTML;
				document.body.appendChild(copiedNode);
			}

		<? endif; ?>

		window.print();

	</script>
<?php
}

