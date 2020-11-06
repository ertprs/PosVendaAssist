<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_assist.php';

session_start();

$sql = "SELECT  tbl_fabrica.os_item_subconjunto
		FROM    tbl_fabrica
		WHERE   tbl_fabrica.fabrica = $login_fabrica";
$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	$os_item_subconjunto = pg_result ($res,0,os_item_subconjunto);
	if (strlen($os_item_subconjunto) == 0) $os_item_subconjunto = 't';
}


#------------ Le OS da Base de dados ------------#
$os = $_GET['os'];
if (strlen ($os) > 0) {
	$sql = "SELECT  tbl_os.sua_os                                                     ,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura     ,
					to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento   ,
					tbl_os.consumidor_nome                                            ,
					tbl_os.consumidor_cidade                                          ,
					tbl_os.consumidor_fone                                            ,
					tbl_os.consumidor_estado                                          ,
					tbl_os.revenda_nome                                               ,
					tbl_os.revenda_cnpj                                               ,
					tbl_os.nota_fiscal                                                ,
					tbl_os.obs                                                        ,
					to_char(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf           ,
					tbl_defeito_reclamado.descricao              AS defeito_reclamado ,
					tbl_os.defeito_reclamado_descricao                                ,
					tbl_os.defeito_reclamado as dr                                   ,
					tbl_os.aparencia_produto                                          ,
					tbl_os.acessorios                                                 ,
					tbl_produto.referencia                                            ,
					tbl_produto.descricao                                             ,
					tbl_produto.voltagem                                              ,
					tbl_os.serie                                                      ,
					tbl_os.codigo_fabricacao                                          ,
					tbl_os.consumidor_revenda                                         ,
					tbl_posto_fabrica.codigo_posto                                    
			FROM    tbl_os
			LEFT JOIN    tbl_defeito_reclamado USING (defeito_reclamado)
			LEFT JOIN    tbl_produto USING (produto)
			JOIN         tbl_posto USING (posto)
			JOIN         tbl_posto_fabrica  ON  tbl_posto.posto           = tbl_posto_fabrica.posto
											AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE   tbl_os.os    = $os
			AND     tbl_os.posto = $login_posto";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows ($res) == 1) {
		$sua_os                      = pg_result ($res,0,sua_os);
		$data_abertura               = pg_result ($res,0,data_abertura);
		$data_fechamento             = pg_result ($res,0,data_fechamento);
		$consumidor_nome             = pg_result ($res,0,consumidor_nome);
		$consumidor_cidade           = pg_result ($res,0,consumidor_cidade);
		$consumidor_fone             = pg_result ($res,0,consumidor_fone);
		$consumidor_estado           = pg_result ($res,0,consumidor_estado);
		$revenda_cnpj                = pg_result ($res,0,revenda_cnpj);
		if($sistema_lingua<>"ES")$revenda_cnpj                = substr($revenda_cnpj,0,2) .".". substr($revenda_cnpj,2,3) .".". substr($revenda_cnpj,5,3) ."/". substr($revenda_cnpj,8,4) ."-". substr($revenda_cnpj,12,2);
		$revenda_nome                = pg_result ($res,0,revenda_nome);
		$nota_fiscal                 = pg_result ($res,0,nota_fiscal);
		$data_nf                     = pg_result ($res,0,data_nf);
		$defeito_reclamado           = pg_result ($res,0,defeito_reclamado);
		$aparencia_produto           = pg_result ($res,0,aparencia_produto);
		$acessorios                  = pg_result ($res,0,acessorios);
		$defeito_reclamado_descricao = pg_result ($res,0,defeito_reclamado_descricao);
		$produto_referencia          = pg_result ($res,0,referencia);
		$produto_descricao           = pg_result ($res,0,descricao);
		$produto_voltagem            = pg_result ($res,0,voltagem);
		$serie                       = pg_result ($res,0,serie);
		$codigo_fabricacao           = pg_result ($res,0,codigo_fabricacao);
		$obs                         = pg_result ($res,0,obs);
		$codigo_posto                = pg_result ($res,0,codigo_posto);
		$consumidor_revenda          = pg_result ($res,0,consumidor_revenda);
		$dr                          = pg_result ($res,0,dr);

		$sql_idioma = " SELECT tbl_produto_idioma.* FROM tbl_produto_idioma
				JOIN    tbl_produto USING (produto)
				WHERE referencia     = '$produto_referencia'
				AND upper(idioma) = '$sistema_lingua'";
		$res_idioma = @pg_exec($con,$sql_idioma);
		if (@pg_numrows($res_idioma) >0) {
		$produto_descricao  = trim(@pg_result($res_idioma,0,descricao));
		}
	
		$sql_idioma = "SELECT * FROM tbl_defeito_reclamado_idioma
				WHERE defeito_reclamado = $dr
				AND upper(idioma)        = '$sistema_lingua'";
		$res_idioma = @pg_exec($con,$sql_idioma);
		if (@pg_numrows($res_idioma) >0) {
		$defeito_reclamado  = trim(@pg_result($res_idioma,0,descricao));
		}


	}

	if($login_fabrica==3 OR $login_fabrica==11){
		$sql = "SELECT tbl_os_status.status_os,
					tbl_produto.troca_obrigatoria AS produto_troca_obrigatoria,
					tbl_peca.troca_obrigatoria AS peca_troca_obrigatoria,
					tbl_peca.retorna_conserto AS peca_retorna_conserto
				FROM tbl_os_status 
				LEFT JOIN tbl_os_produto USING(os)
				LEFT JOIN tbl_produto USING(produto)
				LEFT JOIN tbl_os_item USING(os_produto)
				LEFT JOIN tbl_peca USING(peca)
				WHERE tbl_os_status.os = $os
				AND (tbl_os_status.status_os=62 OR tbl_os_status.status_os=65)";
	
		$sql = "SELECT 	tbl_produto.troca_obrigatoria AS produto_troca_obrigatoria,
					tbl_peca.troca_obrigatoria AS peca_troca_obrigatoria,
					tbl_peca.retorna_conserto AS peca_retorna_conserto,
					(SELECT status_os FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os ORDER BY data DESC LIMIT 1) AS status_os
				FROM tbl_os
				LEFT JOIN tbl_os_produto USING(os)
				LEFT JOIN tbl_produto ON tbl_produto.produto=tbl_os.produto
				LEFT JOIN tbl_os_item USING(os_produto)
				LEFT JOIN tbl_peca USING(peca)
				WHERE tbl_os.os = $os";
		
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res) >0) {
			$produto_troca_obrigatoria	= pg_result ($res,0,produto_troca_obrigatoria);
			$peca_troca_obrigatoria		= pg_result ($res,0,peca_troca_obrigatoria);
			$peca_intervencao_fabrica	= pg_result ($res,0,peca_retorna_conserto);
			$status_os					= pg_result ($res,0,status_os);
			if ($status_os=="62"){
				if ($produto_troca_obrigatoria=='t' || $peca_troca_obrigatoria=='t'){
					$temp="<b style='font-size:14px;color:red'>INTERVENÇÃO DA ASSISTÊNCIA TÉCNICA DA FÁBRICA</b><br><br>
							<b style='color:#000000;font-size:12px'>Este produto deve ser trocado.<br> A fábrica irá efetuar a troca.</b>";
				} else {
					$temp="<b style='font-size:14px;color:red'>INTERVENÇÃO DA ASSISTÊNCIA TÉCNICA DA FÁBRICA</b><br><br>
							<b style='color:#000000;font-size:12px'>Pela peça selecionada, está OS estará agora sob intervenção da Assistência Técnica da Fábrica. E não poderá mais ser alterada até sua liberação.<br>Aguarde a fábrica entrar em contato.</b>";	
				}
			}
			if ($status_os=="65"){
				$temp="<b style='font-size:14px;color:red'>INTERVENÇÃO DA ASSISTÊNCIA TÉCNICA DA FÁBRICA</b><br><br>
							<b style='color:#000000;font-size:12px'>O produto desta OS deve ser reparado pela Assistência Técnica da Fábrica.<br>Se o produto ainda não foi enviado, por favor, enviar para o reparo.  <a href='os_devolucao_fabio.php?os=$os'>CLIQUE AQUI</a></b>";
			}
			if ($status_os=="72"){
				$temp="<b style='font-size:14px;color:red'>INTERVENÇÃO DO SAP</b><br><br>
							<b style='color:#000000;font-size:12px'>Pela peça selecionada, está OS estará agora sob intervenção do SAP. <br>Aguarde a fábrica analisar a solicitação da peça.</b>";
			}
			if (strlen($temp)>0){
				$msg_intervencao = "<center>
					<div style='font-family:verdana;border:1px dashed #666666;padding:10px;width:600px;align:center' align='center'>
						$temp
					</div></center>";
			}
		}
	}

}
if($sistema_lingua=='ES')$title = "Finalización de lanzamiento de itens en la órden de servicio ";
else                     $title = "Finalização de lançamento de itens na Ordem de Serviço";

$layout_menu = 'os';
include "menu.php";

if (($login_fabrica==3 OR $login_fabrica==11) AND strlen($msg_intervencao)>0){
 	echo "<br>$msg_intervencao<br>";
}

?>

<center>
<div id="container">
<!-- ------------- Formulário ----------------- -->
<!-- ------------- INFORMAÇÕES DA ORDEM DE SERVIÇO------------------ -->
	<form name="frm_os" method="post" action="<? echo $PHP_SELF ?>">
	<input type="hidden" name="os" value="<? echo $HTTP_GET_VARS['os'] ?>">
	<div id="page">
		<h2><?
			if($sistema_lingua=='ES')echo "Informaciones sobre la orden de servicio";
			else                     echo "Informações sobre a Ordem de Serviço";
		?>
		<div id="contentcenter" style="width: 650px;">
			<div id="contentleft2" style="width: 200px; ">
				<?
				if($sistema_lingua=='ES')echo "OS FABRICANTE";
				else                     echo "OS FABRICANTE";
				?>
			</div>
			<div id="contentleft2" style="width: 200px; ">
				<?
				if($sistema_lingua=='ES')echo "FECHA DE ABERTURA";
				else                     echo "DATA DE ABERTURA";
				?>
			</div>
		</div>

		<div id="contentcenter" style="width: 650px;">
			<div id="contentleft" style="width: 200px;font:75%">
				<? if ($login_fabrica == 1) echo $codigo_posto; echo $sua_os; ?>
			</div>
			<div id="contentleft" style="width: 200px;font:75%">
				<? echo $data_abertura ?>
			</div>
		</div>
		</h2>
	</div>

</div>

<!-- ------------- INFORMAÇÕES DO CONSUMIDOR ------------------ -->


<? if ($consumidor_revenda <> 'R') { ?>
<div id="container">
<div id="page">
	<h2><?
		if($sistema_lingua=='ES')echo "Informaciones sobre el CLIENTE";
		else                     echo "Informações sobre o CONSUMIDOR";
		?>
	<div id="contentcenter" style="width: 650px;">
		<div id="contentleft2" style="width: 250px; ">
			<?
				if($sistema_lingua=='ES')echo "NOMBRE DEL CLIENTE";
				else                     echo "NOME DO CONSUMIDOR";
			?>
		</div>
		<div id="contentleft2" style="width: 150px; ">
			<?
			if($sistema_lingua=='ES')echo "CIUDAD";
			else                     echo "CIDADE";
			?>
		</div>
		<div id="contentleft2" style="width: 80px; ">
			<?
				if($sistema_lingua=='ES')echo "PROVINCIA";
				else                     echo "ESTADO";
			?>
		</div>
		<div id="contentleft2" style="width: 130px; ">
			<?
				if($sistema_lingua=='ES')echo "TELÉFONO";
				else                     echo "FONE";
			?>
		</div>
	</div>

	<div id="contentcenter" style="width: 650px;">
		<div id="contentleft" style="width: 250px;font:75%">
			<? echo $consumidor_nome ?>
		</div>
		<div id="contentleft" style="width: 150px;font:75%">
			<? echo $consumidor_cidade ?>
		</div>
		<div id="contentleft" style="width: 80px;font:75%">
			<? echo $consumidor_estado ?>
		</div>
		<div id="contentleft" style="width: 130px;font:75%">
			<? echo $consumidor_fone ?>
		</div>
	</div>
	</h2>
</div>
</div>
<? } ?>
<!-- ------------- INFORMAÇÕES DA REVENDA------------------ -->


<div id="container">
<div id="page">
	<h2><?
		if($sistema_lingua=='ES')echo "Infomaciones sobre el DISTRIBUIDOR";
		else                     echo "Informações sobre a REVENDA";
		?>
	<div id="contentcenter" style="width: 650px;">
		<div id="contentleft2" style="width: 150px; ">
			<?
				if($sistema_lingua=='ES')echo "IDENTIFICACIÓN DISTRIBUIDOR";
				else                     echo "CNPJ REVENDA";
			?>
		</div>
		<div id="contentleft2" style="width: 150px; ">
			<?
				if($sistema_lingua=='ES')echo "NOMBRE DEL DISTRIBUIDOR";
				else                     echo "NOME DA REVENDA";
			?>
		</div>
		<div id="contentleft2" style="width: 150px; ">
			<?
				if($sistema_lingua=='ES')echo "FACTURA COMERCIAL";
				else                     echo "NOTA FISCAL N.";
			?>
		</div>
		<div id="contentleft2" style="width: 130px; ">
			<?
				if($sistema_lingua=='ES')echo "FECHA DE LA FACTURA";
				else                     echo "DATA DA N.F.";
			?>
		</div>
	</div>
	<div id="contentcenter" style="width: 650px;">
		<div id="contentleft" style="width: 150px;font:75%">
			<? echo $revenda_cnpj ?>
		</div>
		<div id="contentleft" style="width: 150px;font:75%">
			<? echo $revenda_nome ?>
		</div>
		<div id="contentleft" style="width: 150px;font:75%">
			<? echo $nota_fiscal ?>
		</div>
		<div id="contentleft" style="width: 130px;font:75%">
			<? echo $data_nf ?>
		</div>
	</div>
	</h2>
</div>

</div>
<!-- ------------- INFORMAÇÕES DO PRODUTO------------------ -->


<div id="container">
<div id="page">
	<h2><?
		if($sistema_lingua=='ES')echo "Informaciones del PRODUCTO";
		else                     echo "Informações sobre o PRODUTO";
		?>
	<div id="contentcenter" style="width: 650px;">
		<div id="contentleft2" style="width: 100px; ">
			<?
				if($sistema_lingua=='ES')echo "REFERENCIA";
				else                     echo "REFERÊNCIA";
			?>
		</div>
		<div id="contentleft2" style="width: 250px; ">
			<?
				if($sistema_lingua=='ES')echo "DESCRIPCIÓN ";
				else                     echo "DESCRIÇÃO";
			?>
		</div>
		<? if ($login_fabrica == 1) { ?>
		<div id="contentleft2" style="width: 75px; ">
			VOLTAGEM
		</div>
		<div id="contentleft2" style="width: 125px; ">
			CÓD. FABRICAÇÃO
		</div>
		<? } ?>
		<div id="contentleft2" style="width: 75px; ">
			<?
				if($sistema_lingua=='ES')echo "SERIE";
				else                     echo "SÉRIE";
			?>
		</div>
	</div>
	<div id="contentcenter" style="width: 650px;">
		<div id="contentleft" style="width: 100px;font:75%">
			<? echo $produto_referencia ?>
		</div>
		<div id="contentleft" style="width: 250px;font:75%">
			<? echo $produto_descricao ?>
		</div>
		<? if ($login_fabrica == 1) { ?>
		<div id="contentleft" style="width: 75px;font:75%">
			<? echo $produto_voltagem ?>
		</div>
		<div id="contentleft" style="width: 125px;font:75%">
			<? echo $codigo_fabricacao ?>
		</div>
		<? } ?>
		<div id="contentleft" style="width: 75px;font:75%">
			<? echo $serie ?>
		</div>
	</div>
	</h2>
</div>

</div>
<!-- ------------- INFORMAÇÕES DO DEFEITO ------------------ -->


<div id="container">
<div id="page">
	<h2><?
		if($sistema_lingua=='ES')echo "Falla informada por el cliente";
		else {
			echo "Defeito Apresentado";
			if ($consumidor_revenda <> 'R') echo " pelo Cliente";
		}
		?>
	<div id="contentcenter" style="width: 650px;">
		<div id="contentleft" style="width: 650px;font:75%">
			<? echo $defeito_reclamado ?>
		</div>
	</div>
	</h2>
</div>
</div>
<div id="container">
<div id="page">
	<h2><?
		if($sistema_lingua=='ES')echo "Aparencia del producto";
		else                     echo "Aparência Geral do Produto";
		?>
	<div id="contentcenter" style="width: 650px;">
		<div id="contentleft" style="width: 650px;font:75%">
			<? echo $aparencia_produto ?>
		</div>
	</div>
	</h2>
</div>
</div>

<div id="container">
	<div id="page">
		<h2><?
		if($sistema_lingua=='ES')echo "Accesorios dejados por el cliente";
		else {
			echo "Acessórios Deixados";
			 if ($consumidor_revenda <> 'R') echo " pelo Cliente";
		}
		?>
		<div id="contentcenter" style="width: 650px;">
			<div id="contentleft" style="width: 650px;font:75%">
				<? echo $acessorios; ?>
			</div>
		</div>
		</h2>
	</div>
</div>

<div id="container">
	<div id="page">
<?
// ITENS
if (strlen ($os) > 0) {
	$sql = "SELECT  tbl_os_produto.os_produto                                     ,
					tbl_os_item.qtde                                              ,
					tbl_os_item.peca_original                                     ,
					tbl_defeito.descricao AS defeito_descricao                    ,
					tbl_servico_realizado.servico_realizado                       ,
					tbl_servico_realizado.descricao AS servico_realizado_descricao,
					tbl_peca.referencia                                           ,
					tbl_peca.descricao                                            ,
					tbl_produto.referencia          AS subproduto_referencia      ,
					tbl_produto.descricao           AS subproduto_descricao       ,
					tbl_lista_basica.posicao        
			FROM	tbl_os_produto
			JOIN	tbl_os_item      ON tbl_os_produto.os_produto = tbl_os_item.os_produto
			JOIN	tbl_peca         ON tbl_peca.peca             = tbl_os_item.peca
			JOIN	tbl_lista_basica ON  tbl_lista_basica.produto = tbl_os_produto.produto
									 AND tbl_lista_basica.peca    = tbl_peca.peca
			LEFT JOIN tbl_defeito           USING (defeito)
			LEFT JOIN tbl_servico_realizado USING (servico_realizado)
			JOIN    tbl_produto      ON tbl_os_produto.produto    = tbl_produto.produto
			WHERE   tbl_os_produto.os = $os ";

	$sql = "SELECT  tbl_os_produto.os_produto                                              ,
					tbl_os_item.qtde                                                       ,
					tbl_os_item.peca_original                                              ,
					tbl_os_item.posicao                                                    ,
					tbl_os_item.pedido                                                     ,
					tbl_defeito.descricao                   AS defeito_descricao           ,
					tbl_servico_realizado.servico_realizado                                ,
					tbl_servico_realizado.descricao         AS servico_realizado_descricao ,
					tbl_servico_realizado.troca_de_peca                                    ,
					tbl_peca.peca                                                          ,
					tbl_peca.referencia                                                    ,
					tbl_peca.descricao                                                     ,
					tbl_peca.bloqueada_garantia                                            ,
					tbl_produto.referencia                  AS subproduto_referencia       ,
					tbl_produto.descricao                   AS subproduto_descricao        ,
					tbl_pedido.pedido_blackedecker          AS pedido_blackedecker         
			FROM	tbl_os_produto
			JOIN	tbl_os_item      USING (os_produto)
			JOIN    tbl_produto      USING (produto)
			JOIN	tbl_peca         USING (peca)
			LEFT JOIN tbl_defeito           USING (defeito)
			LEFT JOIN tbl_servico_realizado USING (servico_realizado)
			LEFT JOIN tbl_pedido            ON tbl_pedido.pedido = tbl_os_item.pedido
			WHERE   tbl_os_produto.os = $os ORDER BY os_item ASC";

	$res = pg_exec ($con,$sql);
	
	echo "<table width='100%' border='0' cellspacing='0' cellspadding='0'>";
	echo "<tr bgcolor='#cccccc'>";
	if($os_item_subconjunto == 't') {
		echo "<td align='center'><b><font color='#ffffff' face='verdana' size='1'>Subconjuto</font></b></td>";
		echo "<td align='center'><b><font color='#ffffff' face='verdana' size='1'>Posição</font></b></td>";
	}
	echo "<td align='center'><b><font color='#ffffff' face='verdana' size='1'>";
	if($sistema_lingua=='ES')echo "Referencia";
	else                     echo "Referência";
	echo "</font></b></td>";
	echo "<td align='center'><b><font color='#ffffff' face='verdana' size='1'>";
	if($sistema_lingua=='ES')echo "Descripción";
	else                     echo "Descrição";
	echo "</font></b></td>";
	echo "<td align='center'><b><font color='#ffffff' face='verdana' size='1'>";
	if($sistema_lingua=='ES')echo "Cant.";
	else                     echo "Qtde";
	echo "</font></b></td>";

	if ($login_fabrica <> 20) {
		echo "<td align='center'><b><font color='#ffffff' face='verdana' size='1'>";
		if($sistema_lingua=='ES')echo "Defecto";
		else                     echo "Defeito";
		echo "</font></b></td>";
		echo "<td align='center'><b><font color='#ffffff' face='verdana' size='1'>";
		if($sistema_lingua=='ES')echo "Servicio";
		else                     echo "Serviço";
		echo "</font></b></td>";
		echo "<td align='center'><b><font color='#ffffff' face='verdana' size='1'>";
		if($sistema_lingua=='ES')echo "Pedido";
		else                     echo "Pedido";
		echo "</font></b></td>";
	}
	if (pg_numrows ($res) > 0) {
		for ($i=0; $i< pg_numrows ($res); $i++){
			$qtde                  = pg_result ($res,$i,qtde);
			$peca_original         = pg_result ($res,$i,peca_original);
			$peca                  = pg_result ($res,$i,peca);
			$referencia            = pg_result ($res,$i,referencia);
			$descricao             = pg_result ($res,$i,descricao);
			$defeito               = pg_result ($res,$i,defeito_descricao);
			$servico               = pg_result ($res,$i,servico_realizado_descricao);
			$cod_servico           = pg_result ($res,$i,servico_realizado);
			$subproduto_referencia = pg_result ($res,$i,subproduto_referencia);
			$subproduto_descricao  = pg_result ($res,$i,subproduto_descricao);
			$posicao               = pg_result ($res,$i,posicao);
			$pedido                = pg_result ($res,$i,pedido);
			$pedido_blackedecker   = pg_result ($res,$i,pedido_blackedecker);
			$bloqueada_garantia    = pg_result ($res,$i,bloqueada_garantia);
			$troca_de_peca         = pg_result ($res,$i,troca_de_peca);

			//--=== Tradução para outras linguas ============================= Raphael HD:1212
			$sql_idioma = "SELECT * FROM tbl_peca_idioma WHERE peca = $peca AND upper(idioma) = '$sistema_lingua'";

			$res_idioma = @pg_exec($con,$sql_idioma);
			if (@pg_numrows($res_idioma) >0) {
				$descricao  = trim(@pg_result($res_idioma,0,descricao));
			}
			//--=== Tradução para outras linguas ===================================================================

			$cor = ($i%2==0) ? '#f8f8f8' : '#ffffff';
			echo "<tr bgcolor='$cor'>";

			if($os_item_subconjunto == 't'){
				echo "<td><font face='arial' size='-2'> $subproduto_referencia - $subproduto_descricao </font></td>";
				echo "<td><font face='arial' size='-2'> $posicao </font></td>";
			}

			echo "<td nowrap>";
			echo "<font face='verdana' size='1'>";
			echo $referencia;
			echo "</font>";
			echo "</td>";

			echo "<td nowrap>";
			echo "<font face='verdana' size='1'>";
			echo $descricao;
			echo "</font>";
			echo "</td>";

			echo "<td nowrap align='center'>";
			echo "<font face='verdana' size='1'>";
			echo $qtde;
			echo "</font>";
			echo "</td>";

			if ($login_fabrica <> 20) {
				echo "<td nowrap align='center'>";
				echo "<font face='verdana' size='1'>";
				echo $defeito;
				echo "</font>";
				echo "</td>";

				echo "<td>";
				echo "<font face='verdana' size='1'>";
				echo $servico;
				echo "</font>";
				echo "</td>";
				
				echo "<td align='center'>";
				echo "<font face='verdana' size='1'>";
				if ($login_fabrica == 1) echo $pedido_blackedecker;
				else                     echo $pedido;
				echo "</font>";
				echo "</td>";
			}
			echo "</tr>";

			if ($bloqueada_garantia=='t' AND $login_fabrica==3 and $troca_de_peca =='t' ){
				echo "<tr>\n";
				echo "<td colspan='4'>\n";
				echo "<font face='Arial, Verdana, Times, Sans' size='1' color='#000000'><b>";
				//echo "A peça $referencia necessita de autorização da Britânia para atendimento em garantia. Para liberação desta peça, favor enviar e-mail para <a href=\"mailto:assistenciatecnica@britania.com.br\">assistenciatecnica@britania.com.br</A>, informando a OS e a justificativa.";
				//alterado por Fabio - 16/03/2007 - chamado 1392
				echo "A peça $referencia necessita de autorização da Britânia para atendimento em garantia.";
				echo "</b></font>";
				echo "</td>\n";
				echo "</tr>\n";
			}

			if (strlen($peca_original) > 0) {
				$sql = "SELECT referencia from tbl_peca where peca = $peca_original and fabrica = $login_fabrica";
				$resOriginal = pg_exec ($con,$sql);
				$referencia_original = pg_result ($resOriginal,0,referencia);
				echo "<tr bgcolor='$cor'>";
				
				echo "<td colspan='6'>";
				echo "<font face='Verdana' size='1' color='#CC0066'>";
				echo "A peça <B>$referencia_original</B> digitada pelo posto foi substituída automaticamente pela peça <B>$referencia</B>";
				echo "</font>";
				
				echo "</td>";
				echo "</tr>";
			}

			if ($cod_servico == "62" and $login_fabrica == 1 and strlen($pedido) == 0) {
				echo "<tr bgcolor='$cor'>";
				
				echo "<td colspan='6'>";
				echo "<font face='Verdana' size='2' color='0000ff'><b>";
				echo "O item acima, constará em um pedido de garantia. Toda segunda e quarta-feira o site gera o pedido e envia para a fábrica no horário padrão das 13h30. Para saber o número do pedido que o site gerou e fazer o acompanhamento, clique no menu PEDIDOS e em seguida CONSULTA DE PEDIDOS e LISTAR TODOS OS PEDIDOS.";
				echo "</b></font>";
				
				echo "</td>";
				echo "</tr>";
			}
			
		}
	}
	echo "</table>";
}
?>
	</div>
</div>

<div id="container">
	<div id="page">
		<? if ($sistema_lingua=='ES') { ?>
			<h2>Observacion
		<? } else { ?>
			<h2>Observação
		<? } ?>
		<div id="contentcenter" style="width: 650px;">
			<div id="contentleft" style="width: 650px;font:75%">
				<? echo $obs; ?>
			</div>
		</div>
		</h2>
	</div>
</div>

</form>

</table>

</div>


<!--            Valores da OS           -->
<?
if ($login_fabrica == "20") {

	$pecas              = 0;
	$mao_de_obra        = 0;
	$tabela             = 0;
	$desconto           = 0;
	$desconto_acessorio = 0;

	$sql = "SELECT mao_de_obra FROM tbl_produto_defeito_constatado WHERE produto = (SELECT produto FROM tbl_os WHERE os = $os) AND defeito_constatado = (SELECT defeito_constatado FROM tbl_os WHERE os = $os)";
	$res = pg_exec ($con,$sql);
	if (pg_numrows ($res) == 1) {
		$mao_de_obra = pg_result ($res,0,mao_de_obra);
	}

	$sql = "SELECT tabela,desconto,desconto_acessorio FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 1) {

		$tabela             = pg_result ($res,0,tabela)            ;
		$desconto           = pg_result ($res,0,desconto)          ;
		$desconto_acessorio = pg_result ($res,0,desconto_acessorio);

	}
	if (strlen ($desconto) == 0) $desconto = "0";

	if (strlen ($tabela) > 0) {

		$sql = "SELECT SUM (tbl_tabela_item.preco * tbl_os_item.qtde) AS total 
				FROM tbl_os
				JOIN tbl_os_produto USING (os)
				JOIN tbl_os_item    USING (os_produto)
				JOIN tbl_tabela_item ON tbl_os_item.peca = tbl_tabela_item.peca AND tbl_tabela_item.tabela = $tabela
				WHERE tbl_os.os = $os";
		$res = pg_exec ($con,$sql);

		if (pg_numrows ($res) == 1) {
			$pecas = pg_result ($res,0,0);
		}
	}else{
		$pecas = "0";
	}

	echo "<table cellpadding='10' cellspacing='0' border='1' align='center' style='border-collapse: collapse' bordercolor='#485989'>";
	echo "<tr style='font-size: 12px ; color:#53607F ' >";
	echo "<td align='center' bgcolor='#E1EAF1'><b>";
	if($sistema_lingua=='ES')echo "Valor de Piezas";else echo "Valor das Peças";
	echo "</b></td>";
	echo "<td align='center' bgcolor='#E1EAF1'><b>";
	if($sistema_lingua=='ES')echo "Mano de Obra";else echo "Mão-de-Obra";
	echo "</b></td>";
	echo "<td align='center' bgcolor='#E1EAF1'><b>";
	if($sistema_lingua=='ES')echo "Impuesto IVA";else echo "Imposto IVA";
	echo "</b></td>";
	echo "<td align='center' bgcolor='#E1EAF1'><b>Total</b></td>";
	echo "</tr>";

	$valor_liquido = 0;

	if ($desconto > 0 and $pecas <> 0) {

		$sql = "SELECT produto FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica";
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res) == 1) {
			$produto = pg_result ($res,0,0);
		}
		//echo 'peca'.$pecas;
		if( $produto == '20567' ){
			$desconto_acessorio = '0.2238';
			$valor_desconto = round ( (round ($pecas,2) * $desconto_acessorio ) ,2);

		}else{
			$valor_desconto = round ( (round ($pecas,2) * $desconto / 100) ,2);
		}

		$valor_liquido = $pecas - $valor_desconto ;

	}
	$acrescimo = 0;
	if($login_pais<>"BR"){
		$sql = "select pecas,mao_de_obra  from tbl_os where os=$os";
		$res = pg_exec ($con,$sql);

		if (pg_numrows ($res) == 1) {
			$valor_liquido = pg_result ($res,0,pecas);
			$mao_de_obra   = pg_result ($res,0,mao_de_obra);
		}
		$sql = "select imposto_al  from tbl_posto_fabrica where posto=$login_posto and fabrica=$login_fabrica";
		$res = pg_exec ($con,$sql);

		if (pg_numrows ($res) == 1) {
			$imposto_al   = pg_result ($res,0,imposto_al);
			$imposto_al   = $imposto_al / 100;
			$acrescimo     = ($valor_liquido + $mao_de_obra) * $imposto_al;
		}
	}

	$total = $valor_liquido + $mao_de_obra + $acrescimo;

	$total          = number_format ($total,2,",",".")         ;
	$mao_de_obra    = number_format ($mao_de_obra ,2,",",".")  ;
	$acrescimo      = number_format ($acrescimo ,2,",",".")    ;
	$valor_desconto = number_format ($valor_desconto,2,",",".");
	$valor_liquido  = number_format ($valor_liquido ,2,",",".");

	echo "<tr style='font-size: 12px ; color:#000000 '>";
	echo "<td align='right'>" ;
	echo "<font color='#333377'><b>$valor_liquido</b>" ;
	echo "</td>";
	echo "<td align='center'>$mao_de_obra</td>";
	echo "<td align='center'>+ $acrescimo</td>";
	echo "<td align='center' bgcolor='#E1EAF1'><font size='3' color='FF0000'><b>$total</b></td>";
	echo "</tr>";

	echo "</table>";

}
?>



<p>


<TABLE cellpadding='5' cellspacing='5'>
<TR>
	<TD><a href="os_cadastro.php"><img src="imagens/<?if($sistema_lingua=="ES")echo "es_";?>btn_lancanovaos.gif"></a></TD>
	<?
	if ($login_fabrica == 1) {
		echo "<TD><a href='os_cadastro.php?os=$os'><img src='imagens/btn_alterarcinza.gif'></a></TD>";
	}
	?>
	<TD><a href="os_print.php?os=<? echo $os ?>" target="blank"><img src="imagens/btn_imprimir.gif"></a></TD>
	<?

	if (strlen($_SESSION["sua_os_explodida"]) > 0) {
		echo "<TD><a href='os_revenda_explodida_blackedecker.php?sua_os=".$_SESSION["sua_os_explodida"]."'><img src='imagens/";
    echo "btn_voltar.gif'></a></TD>";
		session_destroy();
	}else{
		echo "<TD><a href='os_consulta_lite.php'><img src='imagens/";
    if($sistema_lingua=="ES")echo "es_";
    echo "btn_voltarparaconsulta.gif'></a></TD>";
	}
	?>
<?
	if ($login_fabrica == 20) {
		echo "<TD><a href='os_comprovante_servico_print.php?os=$os'><img src='imagens/";
		if($sistema_lingua=="ES")echo "es_";
		echo "btn_comprovante.gif'></a></TD>";
	}
	?>
</TR>
</TABLE>

</center>

<p>
<p>

<? include "rodape.php";?>
