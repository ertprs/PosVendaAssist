<?php

#session_name("carrinho");
#session_start();
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include_once 'class/email/mailer/class.phpmailer.php';
$mailer = new PHPMailer(); //Class para envio de email com autenticação no servidor
include_once "class/tdocs.class.php";
$tDocs = new TDocs($con, $login_fabrica);


$peca = $_POST['peca'];

if(strlen($_POST['produto_acabado'])>0) $produto_acabado = $_POST['produto_acabado'];
else                                    $produto_acabado = $_GET['produto_acabado'];

if(strlen($_POST['btn_comprar2'])>0) $btn_comprar = $_POST['btn_comprar2'];
else                                 $btn_comprar = $_POST['btn_comprar2'];

if (strlen($cook_fabrica)==0 AND strlen($cook_login_unico)>0){	
	include 'login_unico_autentica_usuario.php';
	$login_fabrica = 10;
}elseif (strlen($cook_fabrica)==0 AND strlen($cook_login_simples)>0){	
	include 'login_simples_autentica_usuario.php';
}else{	
	include 'autentica_usuario.php';
}

if($login_fabrica == 85){
	$flagJsRedireciona = false;
}

if (!function_exists('calcula_frete')) {
	function calcula_frete($cep_origem,$cep_destino,$peso,$codigo_servico = null ){
		/*
			$url = "www.correios.com.br";
			$ip = gethostbyname($url);
			$fp = fsockopen($ip, 80, $errno, $errstr, 10);

			if ($codigo_servico == null){
				$cod_servico     = "40010"; #Código SEDEX
			}else{
				$cod_servico = $codigo_servico;
			}
		*/
		if (strlen($cep_origem)>0 AND strlen($cep_destino)>0 AND strlen($peso)>0){
			/*$saida  = "GET /encomendas/precos/calculo.cfm?servico=$codigo_servico&CepOrigem=$cep_origem&CepDestino=$cep_destino&Peso=$peso &MaoPropria=N&avisoRecebimento=N&resposta=xml\r\n";
			$saida .= "Host: www.correios.com.br\r\n";
			$saida .= "Connection: Close\r\n\r\n";*/
			//fwrite($fp, $saida);

			/*$resposta = "";
			while (!feof($fp)) {
				$resposta .= fgets($fp, 128);
			}
			fclose($fp);
			#echo htmlspecialchars ($resposta);

			$posicao = strpos ($resposta,"Tarifa=");
			$tarifa  = substr ($resposta,$posicao+7);
			$posicao = strpos ($tarifa,"&");
			$tarifa  = substr ($tarifa,0,$posicao);*/

			$correios = "http://www.correios.com.br/encomendas/precos/calculo.cfm?servico=".$cod_servico."&cepOrigem=".$cep_origem."&cepDestino=".$cep_destino."&peso=".$peso."&MaoPropria=N&avisoRecebimento=N&resposta=xml";

			#echo $correios.'<BR><BR>';

			$correios_info = file($correios);


			foreach($correios_info as $info){
				$bsc = "/\<preco_postal>(.*)\<\/preco_postal>/";
				if(preg_match($bsc,$info,$tarifa)){
					$precofrete = $tarifa[1];
				}
			}
			return $precofrete;
		}else{
			return null;
		}
	}
}



/*$ajax = $_GET['ajax'];
if(strlen($ajax)>0){
	$peca = $_GET['peca'];
	if ($dh = opendir('imagens_pecas/media/')) {
		echo"<center>
			<img src='imagens_pecas/media/$peca' border='0'>
			</center>";
	}
	exit;
}*/

?>

<script language="JavaScript">
	function abrir(URL) {
		var width = 480;
		var height = 500;
		var left = 90;
		var top = 90;

		window.open(URL,'janela', 'width='+width+', height='+height+', top='+top+', left='+left+', scrollbars=yes, status=no, toolbar=no, location=no, directories=no, menubar=no, resizable=no, fullscreen=no');
	}
</script>

<?
if(strlen($condicao)>0){
	$sql    = "UPDATE tbl_pedido SET condicao = $condicao WHERE pedido = $pedido";
	$res = pg_exec($con,$sql);
	header("Location: lv_carrinho.php");
	exit;
}


# Pesquisa pelo AutoComplete AJAX
$q = trim($_GET["q"]);
if (isset($_GET["q"])){
	$busca      = $_GET["busca"];
	$tipo_busca = $_GET["tipo_busca"];
	if (strlen($q)>2){
		if ($tipo_busca=="produto"){
			$sql = "SELECT tbl_peca.peca,
							tbl_peca.referencia,
							tbl_peca.ipi            ,
							tbl_peca.descricao,
							estoque                 ,
							garantia_diferenciada   ,
							informacoes             ,
							linha_peca              ,
							multiplo_site           ,
							qtde_minima_site        ,
							qtde_max_site           ,
							qtde_disponivel_site
					FROM tbl_peca
					WHERE tbl_peca.fabrica = $login_fabrica
					AND tbl_peca.peca NOT IN (SELECT peca FROM tbl_peca_fora_linha WHERE fabrica = $login_fabrica)
					AND   tbl_peca.ativo   IS TRUE";

			if ($busca == "codigo"){
				$sql .= " AND UPPER(tbl_peca.referencia) like UPPER('%$q%') ";
			}else{
				$sql .= " AND UPPER(tbl_peca.descricao) like UPPER('%$q%') ";
			}


			$res = pg_exec($con,$sql);
			if (pg_numrows ($res) > 0) {
				for ($i=0; $i<pg_numrows ($res); $i++ ){
					$peca					= trim(pg_result($res,$i,peca));
					$referencia				= trim(pg_result($res,$i,referencia));
					$descricao				= trim(pg_result($res,$i,descricao));
					$ipi					= trim(pg_result ($res,$i,ipi));
					$estoque				= trim(pg_result ($res,$i,estoque));
					$garantia_diferenciada	= trim(pg_result ($res,$i,garantia_diferenciada));
					$informacoes			= trim(pg_result ($res,$i,informacoes));
					$linha					= trim(pg_result ($res,$i,linha_peca));
					$multiplo_site 			= trim(pg_result ($res,$i,multiplo_site));
					$qtde_minima_site		= trim(pg_result ($res,$i,qtde_minima_site));
					$qtde_max_site			= trim(pg_result ($res,$i,qtde_max_site));
					$qtde_disponivel_site	= trim(pg_result ($res,$i,qtde_disponivel_site));

					$sql3 = "SELECT preco
							FROM tbl_tabela_item
							WHERE peca  = $peca";
						if($produto_acabado=="t" AND ($login_fabrica==3 || $login_fabrica == 85)){
							# HD 110817
							$sql3 .= " AND tabela = 265;";
						}else{
							$sql3 .= "
							AND   tabela IN (
								SELECT tbl_tabela.tabela
								FROM tbl_posto_linha
								JOIN tbl_tabela       USING(tabela)
								JOIN tbl_posto        USING(posto)
								JOIN tbl_linha        USING(linha)
								WHERE tbl_posto.posto       = $login_posto
								AND   tbl_linha.fabrica     = $login_fabrica
								AND   tbl_posto_linha.linha IN (
									SELECT DISTINCT tbl_produto.linha
									FROM tbl_produto
									JOIN tbl_lista_basica USING(produto)
									JOIN tbl_peca USING(peca)
									WHERE peca = $peca
								)
							)";
						}
					$res3 = pg_exec ($con,$sql3);

					//if ((strlen($login_unico)>0 OR strlen($login_simples)>0) AND $login_fabrica==10 ){
					if (((strlen($login_unico)>0 OR strlen($login_simples)>0) AND $login_fabrica==10) OR ($login_fabrica==3 && pg_numrows ($res3) == 0) OR ($login_fabrica==85 && pg_numrows ($res3) == 0)){
						$sql2 = "SELECT preco
								FROM tbl_tabela_item
								WHERE peca  = $peca
								AND   tabela IN (
									SELECT tbl_tabela.tabela
									FROM tbl_tabela
									WHERE fabrica = $login_fabrica
									AND   ativa IS TRUE
								)";
						$res3 = pg_exec ($con,$sql2);
					}

					if (pg_numrows ($res3) > 0) {
						$preco = trim(pg_result ($res3,0,preco));
						$preco_formatado = number_format($preco,2,'.',',');
						echo "$peca|$descricao|$referencia|$ipi|$linha|$qtde_max_site|$qtde_disponivel_site|$preco_formatado|$qtde_minima_site";
						echo "\n";
					}
				}
			}
		}
	}
	exit;
}

function imprimir_pedido_item($con,$login_fabrica,$login_posto,$status,$pedido){

	if ($status=="finalizado"){
		$condicao1 = "";
	}else{
		$condicao1 = " AND   tbl_pedido.finalizado          IS NULL ";
	}

	$sql = "SELECT
				tbl_pedido.pedido              ,
				tbl_pedido_item.pedido         ,
				tbl_pedido_item.pedido_item    ,
				tbl_peca.peca                  ,
				tbl_peca.referencia            ,
				tbl_peca.descricao             ,
				tbl_peca.ipi                   ,
				tbl_peca.promocao_site         ,
				tbl_peca.qtde_disponivel_site  ,
				tbl_pedido_item.qtde           ,
				tbl_pedido_item.preco          ,
				tbl_linha.nome as linha_desc
		FROM  tbl_pedido
		JOIN  tbl_pedido_item USING (pedido)
		JOIN  tbl_peca        USING (peca)
		LEFT JOIN tbl_linha USING(linha)
		WHERE tbl_pedido.posto   = $login_posto
		AND   tbl_pedido.fabrica = $login_fabrica
		AND   tbl_pedido.pedido  = $pedido
		AND   tbl_pedido.pedido_loja_virtual IS TRUE
		AND   tbl_pedido.exportado           IS NULL
		$condicao1
		ORDER BY tbl_pedido_item.pedido_item ASC";
	$res = pg_exec ($con,$sql);
	$pedido_ant = "";
	if (pg_numrows($res) > 0) {
		for($i=0; $i< pg_numrows($res); $i++) {
			$pedido          = trim(pg_result($res,$i,pedido));
			$pedido_item     = trim(pg_result($res,$i,pedido_item));
			$peca            = trim(pg_result($res,$i,peca));
			$referencia      = trim(pg_result($res,$i,referencia));
			$peca_descricao  = trim(pg_result($res,$i,descricao));
			$qtde_carro      = trim(pg_result($res,$i,qtde));
			$peca            = trim(pg_result($res,$i,peca));
			$preco           = trim(pg_result($res,$i,preco));
			$ipi             = trim(pg_result($res,$i,ipi));
			$promocao_site   = trim(pg_result($res,$i,promocao_site));
			$qtde_disponivel = trim(pg_result($res,$i,qtde_disponivel_site));
			$linha_desc      = trim(pg_result($res,$i,linha_desc));

			$preco_2         = str_replace(",",".",$preco);

			if (strlen($ipi)>0 AND $ipi>0){
				$valor_total = $preco * $qtde_carro + ($preco*$qtde_carro *$ipi/100);
				$ipi = $ipi." %";
			}else{
				$ipi = "";
				$valor_total = $preco * $qtde_carro;
			}

			$soma       += $valor_total;
			$preco       = number_format($preco, 2, ',', '');
			$preco       = str_replace(".",",",$preco);
			$valor_total = number_format($valor_total, 2, ',', '');

			$a++;
			$cor = "#FFFFFF";
			if ($a % 2 == 0){
				$cor = '#E3EAEE';
			}

			if ($pedido_ant<>$pedido){
				if ($promocao_site=='t' OR strlen($qtde_disponivel)>0){
					$msg_pedido = "Pedido de Promoção";
				}else{
					$msg_pedido = "Pedido de Peças";
				}
			}
			$pedido_ant = $pedido;

			echo "<tr class='Conteudo'>";
			echo "<td  bgcolor='$cor'  align='center'><a href=\"javascript: if(confirm('Deseja excluir o item $referencia - $peca_descricao?')){window.location='$PHP_SELF?acao=remover&peca=$peca&pedido=$pedido&pedido_item=$pedido_item'}\"> <IMG SRC='imagens/excluir_loja.gif' alt='Remover Produto'border='0'></a></td>";
			echo "<td  bgcolor='$cor' align='l' width='45'>";


			$xpecas  = $tDocs->getDocumentsByRef($peca, "peca");
			if (!empty($xpecas->attachListInfo)) {

				$a = 1;
				foreach ($xpecas->attachListInfo as $kFoto => $vFoto) {
				    $fotoPeca = $vFoto["link"];
				    if ($a == 1){break;}
				}
				echo "<a href=\"javascript:abrir('lv_detalhe_popup.php?caminho_final=$fotoPeca&peca=$peca&caminho=$fotoPeca&descricao=$peca_descricao&referencia=$referencia');\"  title='$referencia' class='thickbox'>
				<img src='$fotoPeca' border='0' width='40' >
				<input type='hidden' name='peca_imagem' value='$fotoPeca' ></a>";
			} else {


				if(strlen($peca)>0){
					$sqlF = "SELECT  peca_item_foto      ,
									caminho             ,
									caminho_thumb       ,
									descricao           ,
									(select count(*) from tbl_peca_item_foto where peca = $peca) AS qtde_fotos
							FROM tbl_peca_item_foto
							WHERE peca = $peca
							group by peca_item_foto ,
							caminho                 ,
							caminho_thumb           ,
							descricao               ,
							ordem
							order by ordem LIMIT 1";
					#echo nl2br($sqlF);
					$resF = pg_exec ($con,$sqlF) ;
					$num_fotos = pg_num_rows($resF);
				}
				if ($num_fotos>0){
					$caminho        = trim(pg_result($resF,0,caminho));
					$caminho_thum   = trim(pg_result($resF,0,caminho_thumb));
					$foto_descricao = trim(pg_result($resF,0,descricao));
					$foto_id        = trim(pg_result($resF,0,peca_item_foto));
					$qtde_fotos     = trim(pg_result($resF,0,qtde_fotos));

					$xcaminho      = str_replace("/www/assist/www/",'',$caminho);

					//Manolo, 24/11/08: Ronaldo pediu para mostrar a foto e não apenas o thumb...
					$mlg_infoimagem = getimagesize($caminho_dir."/".$caminho);
					if ($mlg_infoimagem[0] < 150 AND $mlg_infoimagem[1]<250) {
						$mlg_medidasimg = $mlg_infoimagem[3];
					//Manolo, 24/11/08: Limitar o tamanho dependendo da orientação da imagem...
						}elseif ($mlg_infoimagem[0] >  $mlg_infoimagem[1]) {
						$mlg_medidasimg = "WIDTH=60";
						}else{
						$mlg_medidasimg = "HEIGHT=60";
					}

					echo "<a href='$PHP_SELF?ajax=true&peca=$filename&keepThis=trueTB_iframe=true&height=340&width=420' title='$referencia' class='thickbox'>
					<img src='$xcaminho' border='0' $mlg_medidasimg >
						</a>";

				}else{
					if($login_fabrica == '85'){
						if ($dh = opendir('imagens_pecas/85/pequena/')) {
							$diretorio = "imagens_pecas/85/pequena/";                
			                $ret = scandir($diretorio);
			                $fotoP = "";
			                foreach ($ret as $value) {
			                    if($value != "." && $value != ".."){
			                        
			                        $pecaImagem = strstr($value, '.',true);
			                        if($pecaImagem != false){
			                            if($peca == $pecaImagem){
			                                $fotoP = "imagens_pecas/85/media/".$value;                                    
			                            }                            
			                        }
			                    }
			                }
			                if($fotoP == ""){
			                    $fotoP = "imagens_pecas/semimagem.jpg";                        
			                }

			                ?>
										<a href="javascript:abrir('lv_detalhe_popup.php?caminho_final=imagens_pecas/85/media/<? echo $filename ?>&peca= <? echo $peca ?>&caminho=imagens_pecas/85/media/<? echo $filename ?>&descricao=<? echo $peca_descricao ?>&referencia=<? echo $referencia ?>')";  title="<?echo $referencia;?>" class="thickbox">
										<img src='<?php echo $fotoP ?>' border='0' width='40' >
										<input type='hidden' name='peca_imagem' value='<?echo $filename; ?>' ></a>
							<?
						
						}else{
							$fotoP = "imagens_pecas/semimagem.jpg";
							?>
										<a href="javascript:abrir('lv_detalhe_popup.php?caminho_final=imagens_pecas/85/media/<? echo $filename ?>&peca= <? echo $peca ?>&caminho=imagens_pecas/85/media/<? echo $filename ?>&descricao=<? echo $peca_descricao ?>&referencia=<? echo $referencia ?>')";  title="<?echo $referencia;?>" class="thickbox">
										<img src='<?php echo $fotoP ?>' border='0' width='40' >
										<input type='hidden' name='peca_imagem' value='<?echo $filename; ?>' ></a>
							<?
						}	
					}else{
						if ($dh = opendir('imagens_pecas/pequena/')) {
							
							$contador=0;
							while (false !== ($filename = readdir($dh))) {
								if($contador == 1) break;
								if (strpos($filename,$referencia) !== false){
									$contador--;
									$po = strlen($referencia);
									if(substr($filename, 0,$po)==$referencia){?>
										<a href="javascript:abrir('lv_detalhe_popup.php?caminho_final=imagens_pecas/media/<? echo $filename ?>&peca= <? echo $peca ?>&caminho=imagens_pecas/media/<? echo $filename ?>&descricao=<? echo $peca_descricao ?>&referencia=<? echo $referencia ?>')";  title="<?echo $referencia;?>" class="thickbox">
										<img src='imagens_pecas/pequena/<?echo $filename; ?>' border='0' width='40' >
										<input type='hidden' name='peca_imagem' value='<?echo $filename; ?>' ></a>
						<?			}
								}
							}
						}	
					}
					
				}
			}
			echo "</td>";
			echo "<td  bgcolor='$cor' align='left'>";
			echo "$referencia - $peca_descricao</td>";
			echo "<td  bgcolor='$cor' align='center'>$qtde_carro</td>";
			echo "<td  bgcolor='$cor' align='right'>R$ $preco</td>";
			echo "<td  bgcolor='$cor' align='center'>$ipi</td>";
			echo "<td  bgcolor='$cor' align='right'>R$ $valor_total</td>";
			echo "</tr>";
		}
	}
	return $soma;
}

# Pega o pedido ATUAL
$sql = "SELECT tbl_pedido.pedido
		FROM  tbl_pedido
		WHERE tbl_pedido.pedido_loja_virtual IS TRUE
		AND   tbl_pedido.finalizado          IS NULL
		AND   tbl_pedido.exportado           IS NULL
		AND   tbl_pedido.posto               = $login_posto
		AND   tbl_pedido.fabrica             = $login_fabrica
		ORDER BY pedido DESC
		LIMIT 1";
//if($ip=="200.246.170.155") echo nl2br($sql);
$res = pg_exec ($con,$sql);
if (pg_numrows($res) > 0 ) {
	$pedido = pg_result($res,0,pedido);

	if($login_fabrica==3 || $login_fabrica == 85){
		# Verifica se o pedido é at_shop
		$sql = "SELECT tbl_peca.at_shop
				FROM  tbl_pedido
				JOIN tbl_pedido_item using(pedido)
				JOIN tbl_peca using(peca)
				WHERE tbl_pedido.pedido = $pedido
				AND   tbl_peca.at_shop is true
				LIMIT 1;
				";
		$res = pg_exec ($con,$sql);
		if (pg_numrows($res) > 0 ) {
			$at_shop = pg_result($res,0,at_shop);
			$produto_acabado = "t"; // As peças de at_shop são produtos acabados.
		}
	}
}
$btn_acao       = $_GET['btn_acao'];
$acao           = $_GET['acao'];
$adicionar_ajax = $_GET['adicionar_ajax'];

if(strlen($acao)>0){

	if ($acao=="adicionar"){
		$pedido = "";
		$peca        = $_POST['cod_produto'];
		$referencia  = $_POST['referencia'];
		$qtde        = $_POST['qtde'];
		$valor       = $_POST['valor'];
		$descricao   = $_POST['descricao'];
		$qtde_maxi   = $_POST['qtde_maxi'];
		$qtde_disp   = $_POST['qtde_disp'];
		$qtde_min    = $_POST['qtde_min'];

		/* echo "Pedido: ".$pedido."<br />";
		echo "Peça: ".$peca."<br />";
		echo "Referencia: ".$referencia."<br />";
		echo "Qtde: ".$qtde."<br />";
		echo "Valor: ".$valor."<br />";
		echo "Descrição: ".$descricao."<br />";
		echo "Qtde Maxi: ".$qtde_maxi."<br />";
		echo "Qtde Disp: ".$qtde_disp."<br />";
		echo "Qtde Min: ".$qtde_min."<br />"; */

		$qtde_linha    =  $_POST['qtde_linha'];
		if(strlen($qtde_linha)==0){
			$qtde_linha = 1;
			$liquidacao =  "NAO";
		}else{
			$liquidacao =  "SIM";
		}

		for($w=0;$w<$qtde_linha;$w++){

			if($liquidacao == "SIM"){
				$qtde      = trim($_POST['liquida_qtde_'.$w]);
				$peca      = trim($_POST['liquida_peca_'.$w]);
				$valor     = str_replace(",",".",trim($_POST['liquida_preco_'.$w]));
				$qtde_maxi = trim($_POST['qtde_maxi_'.$w]);
				$qtde_disp = trim($_POST['qtde_disp_'.$w]);
			}			
			if(($liquidacao=="NAO" AND strlen($peca)>0) OR ($liquidacao == "SIM" and strlen($qtde)>0 AND strlen($peca)>0)){				
				$res = pg_exec ($con,"BEGIN TRANSACTION");

				$peca_promocional = 'f';

				$sql = "SELECT	tbl_peca.promocao_site,
								tbl_peca.qtde_disponivel_site,
								tbl_peca.at_shop,
								tbl_produto.linha
						FROM   tbl_peca
						LEFT JOIN tbl_produto on tbl_produto.referencia = tbl_peca.referencia
						WHERE  tbl_peca.peca    = $peca
						AND    tbl_peca.fabrica = $login_fabrica";

				$res = pg_exec ($con,$sql);

				if (pg_numrows($res)>0){
					$pesq_promo = trim(pg_result ($res,0,promocao_site));
					$pesq_qtde  = trim(pg_result ($res,0,qtde_disponivel_site));
					$at_shop    = trim(pg_result ($res,0,at_shop));
					$linha      = trim(pg_result ($res,0,linha));
					if ($pesq_promo == 't' OR strlen($pesq_qtde) > 0){
						$peca_promocional = 't';
					}
				}

				if ($peca_promocional == 't'){
					//$axu_peca_promocional = " AND ( tbl_peca.promocao_site IS TRUE OR tbl_peca.qtde_disponivel_site IS NOT NULL ) ";
				}else{//agora é independente de ser da loja virtual, promocao ou nao
				//	$axu_peca_promocional = " AND tbl_peca.promocao_site IS NOT TRUE AND tbl_peca.qtde_disponivel_site IS NULL ";
				}				
				if($at_shop == 't'){
					$tabela = 265; #hd 110817
				}else{
					$sql = "
						SELECT DISTINCT 
							tbl_produto.linha,tbl_tabela.tabela
						FROM 
							tbl_produto
							JOIN tbl_lista_basica USING(produto)
							JOIN tbl_peca USING(peca)
							JOIN tbl_posto_linha USING(linha)
							JOIN tbl_tabela USING(tabela)
							JOIN tbl_tabela_item ON tbl_tabela.tabela = tbl_tabela_item.tabela AND tbl_tabela_item.peca = $peca
						WHERE 
							tbl_posto_linha.posto = $login_posto
							AND tbl_peca.peca = $peca
							AND tbl_peca.fabrica = $login_fabrica
							AND tbl_tabela.ativa IS TRUE
						ORDER BY tbl_produto.linha ASC";						
					$res = pg_exec ($con,$sql);

					if (((strlen($login_unico)>0 OR strlen($login_simples)>0) AND $login_fabrica==10 ) OR $login_fabrica==1 OR ($login_fabrica==3 && pg_numrows ($res) == 0) OR ($login_fabrica==85 && pg_numrows ($res) == 0)){
						$sql = "SELECT ' NULL '           AS linha,
										tbl_tabela.tabela AS tabela
								FROM tbl_tabela
								JOIN tbl_tabela_item ON tbl_tabela.tabela = tbl_tabela_item.tabela AND tbl_tabela_item.peca = $peca
								WHERE tbl_tabela_item.peca = $peca
								AND   tbl_tabela.fabrica   = $login_fabrica
								AND   tbl_tabela.ativa     IS TRUE
								ORDER BY tbl_tabela.tabela DESC
								LIMIT 1 ";
					}		
					
					$res = pg_exec ($con,$sql);					

					if (pg_numrows ($res) > 0) {
						$linha    = pg_result ($res,0,linha);
						$tabela   = pg_result ($res,0,tabela);
					}else{
						if ((strlen($login_unico)>0 OR strlen($login_simples)>0) AND $login_fabrica==10 ){
							$linha  = " NULL ";
							$tabela = " NULL ";
						}else{
							$msg_erro .= "Peça sem linha para seu posto ";
						}
					}					
				}

				# Pegar o pedido - Nao usa mais, um soh pedido para tudo
				$sql = "SELECT DISTINCT tbl_pedido.pedido
						FROM  tbl_pedido
						JOIN  tbl_pedido_item USING(pedido)
						JOIN  tbl_peca        USING(peca)
						WHERE tbl_pedido.pedido_loja_virtual IS TRUE
						AND   tbl_pedido.exportado           IS NULL
						AND   tbl_pedido.finalizado          IS NULL
						AND   tbl_pedido.posto               = $login_posto
						AND   tbl_pedido.fabrica             = $login_fabrica
						 ";
						 //$axu_peca_promocional
				if ( ($login_fabrica==3 OR $login_fabrica==85) AND strlen($linha)>0){
					$sql .= " AND   tbl_pedido.linha               = $linha";
				}
				$res = pg_exec ($con,$sql);
				if (pg_numrows($res) > 0 ) {
					$pedido = pg_result($res,0,pedido);
				}else{
					$sql = "SELECT DISTINCT tbl_pedido.pedido
						FROM  tbl_pedido
						LEFT JOIN  tbl_pedido_item USING(pedido)
						LEFT JOIN  tbl_peca        USING(peca)
						WHERE tbl_pedido.pedido_loja_virtual IS TRUE
						AND   tbl_pedido.exportado           IS NULL
						AND   tbl_pedido.finalizado          IS NULL
						AND   tbl_pedido.posto               = $login_posto
						AND   tbl_pedido.fabrica             = $login_fabrica
						AND   tbl_pedido.admin               IS NOT NULL
						 ";
					if ( ($login_fabrica==3 OR $login_fabrica ==85) AND strlen($linha)>0){
						$sql .= " AND   tbl_pedido.linha               = $linha";
					}
					$res = pg_exec ($con,$sql);
					if (pg_numrows($res) > 0 ) {
						$pedido = pg_result($res,0,pedido);
					}
				}


				# ROTINA PARA INSERIR UM PEDIDO SE ESTE PEDIDO AINDA NÃO EXISTE
				if (strlen($pedido)==0){

					$Xpedido           = $_POST['pedido'];
					$Xcondicao         = $_POST['condicao'];
					$tipo_pedido       = $_POST['tipo_pedido'];
					$pedido_cliente    = $_POST['pedido_cliente'];

					if (strlen($Xcondicao) == 0) {
						$aux_condicao = "null";
					}else{
						$aux_condicao = $Xcondicao ;
					}

					if (strlen($pedido_cliente) == 0) {
						$aux_pedido_cliente = "null";
					}else{
						$aux_pedido_cliente = "'". $pedido_cliente ."'";
					}

					if (strlen($tipo_pedido) <> 0) {
						$aux_tipo_pedido = "'". $tipo_pedido ."'";
					}else{
						$sql = "SELECT	tipo_pedido
								FROM	tbl_tipo_pedido
								WHERE	descricao IN ('Faturado','Venda','ACESSORIOS')
								AND		fabrica = $login_fabrica
								ORDER BY descricao LIMIT 1";
						$res = pg_exec ($con,$sql);
						if (pg_numrows($res) > 0 ) $aux_tipo_pedido = pg_result($res,0,tipo_pedido);
						else                       $aux_tipo_pedido = " null ";
					}
					if($login_fabrica == 1) $pedido_acessorio = "TRUE";
					else                    $pedido_acessorio = "FALSE";

					if(strlen($linha) == 0){
						$linha = "null";
					}

					if(strlen($msg_erro)==0){
						$sql = "INSERT INTO tbl_pedido (
									posto          ,
									fabrica        ,
									condicao       ,
									pedido_cliente ,
									tipo_pedido    ,
									pedido_loja_virtual,
									linha          ,
									desconto       ,
									pedido_acessorio
								) VALUES (
									$login_posto        ,
									$login_fabrica      ,
									$aux_condicao       ,
									$aux_pedido_cliente ,
									$aux_tipo_pedido    ,
									TRUE                ,
									$linha              ,
									0                   ,
									$pedido_acessorio									
								)";
						
						$res = pg_exec ($con,$sql);
						
						$msg_erro = pg_errormessage($con);

						if (strlen($msg_erro) == 0){
							$res = pg_exec ($con,"SELECT CURRVAL ('seq_pedido')");
							$pedido  = pg_result ($res,0,0);
							if ($login_fabrica==10){
								setcookie ("cook_pedido_lu",$pedido);
							}
						}
					}
				}

				if (strlen($pedido)==0){
					$msg_erro .= "Não foi possível criar um pedido!";
				}

				if (strlen($qtde) == 0 OR $qtde < 1 ) {
					$msg_erro   = "Não foi digitada a quantidade para a Peça $peca_referencia.";
					$linha_erro = $i;
					break;
				}

				//verifica se a peça tem o valor da peca caso nao tenha exibe a msg
				//só verifica os precos dos campos que tenha a referencia da peça.
				if ($login_fabrica == '3' OR $login_fabrica == '85'){
					if($tipo_pedido <> '90'){

						if(strlen($valor) == 0){
							$msg_erro .= 'Existem peças sem preço.<br>';
							$linha_erro = $i;
							break;
						}
					}
				}

				if (strlen ($msg_erro) == 0) {

					$sql = "SELECT  tbl_peca.peca                     ,
									tbl_peca.referencia               ,
									tbl_peca.descricao                ,
									tbl_peca.multiplo_site            ,
									tbl_peca.qtde_disponivel_site     ,
									tbl_peca.ipi
							FROM    tbl_peca
							WHERE   tbl_peca.peca    = $peca
							AND     tbl_peca.fabrica = $login_fabrica";
					$res = pg_exec ($con,$sql);

					if (pg_numrows ($res) == 0) {
						$msg_erro  .= "Peça $peca não cadastrada";
						$linha_erro = $i;
						break;
					}else{
						$peca      = pg_result ($res,0,peca);
						$peca_ref  = pg_result ($res,0,referencia);
						$peca_desc = pg_result ($res,0,descricao);
						$peca_mult = pg_result ($res,0,multiplo_site);
						$ipi       = pg_result ($res,0,ipi);
						$qtde_disp = pg_result ($res,0,qtde_disponivel_site);
					}

					########## Validação de Quantidade #########
					$sql = "SELECT	tbl_pedido_item.pedido_item,
									tbl_pedido_item.qtde
							FROM tbl_pedido
							JOIN tbl_pedido_item USING(pedido)
							WHERE  tbl_pedido.pedido      = $pedido
							AND    tbl_pedido_item.peca   = $peca";
					$res = pg_exec ($con,$sql);
					$pedido_item = "";
					if (pg_numrows ($res) > 0) {
						$pedido_item = pg_result ($res,0,pedido_item);
						$qtde_pedido = pg_result ($res,0,qtde);
						$qtde_pedido = $qtde_pedido + $qtde;

						if (strlen($msg_erro)==0 AND strlen($qtde_maxi)>0 AND $qtde_pedido > $qtde_maxi){
							$msg_erro .= "Quantidade máxima permitida para o produto $peca_ref é de $qtde_maxi";
						}
						if (strlen($msg_erro)==0 AND strlen($qtde_disp)>0 AND $qtde_pedido > $qtde_disp){
							#$msg_erro .= "Produto $peca_ref tem $qtde_disp unidades disponíveis.<br> Não foi adicionado ao carrinho. $qtde_pedido > $qtde_disp";
						}

						if($qtde>0 AND $peca_mult>0){
							$xqtde = ($qtde % $peca_mult);
						}
						if (strlen($msg_erro)==0 AND strlen($peca_mult)>0 AND $xqtde <> 0){
							$msg_erro .= "Quantidade deve ser múltiplo de $peca_mult.";
						}

						if (strlen($msg_erro)==0 AND strlen($qtde_disp)>0 AND $qtde > $qtde_disp){
							if ($qtde_disp<0){
								$msg_erro .= "<br>Não há mais unidades disponíveis desta peça.";
							}else{								
								$msg_erro .= "<br>Este produto tem $qtde_disp unidades. Você selecionou $qtde unidades.";
							}
						}
					}else{
						if (strlen($msg_erro)==0 AND strlen($qtde_disp)>0 AND $qtde > $qtde_disp){							
							$msg_erro .= "Este produto tem $qtde_disp unidades. Você selecionou $qtde unidades.";
						}

						if($qtde>0 AND $peca_mult>0){
							$xqtde = ($qtde % $peca_mult);
						}
						if (strlen($msg_erro)==0 AND strlen($peca_mult)>0 AND $xqtde <> 0){
							$msg_erro .= "Quantidade deve ser múltiplo de $peca_mult.";
						}
					}

					if (strlen ($msg_erro) == 0) {
						if (strlen($pedido_item) == 0){
							$sql = "INSERT INTO tbl_pedido_item (
											pedido ,
											peca   ,
											qtde   ,
											preco  ,
											ipi    ,
											tabela
										) VALUES (
											$pedido,
											$peca  ,
											$qtde  ,
											$valor  ,
											$ipi,
											$tabela
										)";
						}else{
							$sql = "UPDATE tbl_pedido_item SET
										qtde = COALESCE(qtde,0) + $qtde
									WHERE pedido_item = $pedido_item
									AND   pedido      = $pedido
									AND   peca        = $peca";
						}
						$res = @pg_exec ($con,$sql);
						$msg_erro .= pg_errormessage($con);

						if (strlen($msg_erro) == 0 AND strlen($pedido_item) == 0) {
							$res         = pg_exec ($con,"SELECT CURRVAL ('seq_pedido_item')");
							$pedido_item = pg_result ($res,0,0);
							$msg_erro    = pg_errormessage($con);
						}
	/*
						if (strlen($ipi)>0 AND $ipi>0){
							$total_peca = $qtde * $valor + ($qtde * $valor * $ipi)/100;
						}else{
							$total_peca = $qtde * $valor;
						}
	*/					if (strlen($msg_erro) == 0 ){
							$sql = "UPDATE tbl_pedido
										SET   total = ROUND (
														(
														SELECT SUM(tbl_pedido_item.preco * tbl_pedido_item.qtde + tbl_pedido_item.preco * tbl_pedido_item.qtde * tbl_pedido_item.ipi / 100)
														FROM   tbl_pedido_item
														JOIN tbl_peca USING(peca)
														WHERE  tbl_pedido_item.pedido = $pedido
														)::NUMERIC , 2
													)
									WHERE tbl_pedido.fabrica  = $login_fabrica
									AND tbl_pedido.pedido     = $pedido
									AND tbl_pedido.posto      = $login_posto";
							if($login_fabrica==1){
								$sql = "UPDATE tbl_pedido
										SET   total = ROUND (
											(
											SELECT SUM(tbl_pedido_item.preco * tbl_pedido_item.qtde)
											FROM   tbl_pedido_item
											JOIN tbl_peca USING(peca)
											WHERE  tbl_pedido_item.pedido = $pedido
											)::NUMERIC , 2
										)
										WHERE tbl_pedido.fabrica  = $login_fabrica
										AND tbl_pedido.pedido     = $pedido
										AND tbl_pedido.posto      = $login_posto";
							}
							$res = pg_exec ($con,$sql);
							$msg_erro .= pg_errormessage($con);

							$sql = "UPDATE tbl_peca
									SET   qtde_disponivel_site = qtde_disponivel_site - $qtde
									WHERE peca     = $peca
									AND   fabrica  = $login_fabrica
									AND qtde_disponivel_site IS NOT NULL";
							$res = pg_exec ($con,$sql);
							$msg_erro .= pg_errormessage($con);
						}
						# Verificação para enviar email em caso de peça não tiver mais estoque
						if (strlen($msg_erro) == 0) {
							$sql = "SELECT  tbl_peca.peca,
											tbl_peca.descricao,
											tbl_peca.referencia,
											tbl_peca.qtde_disponivel_site
									FROM tbl_peca
									WHERE peca   = $peca
									AND fabrica  = $login_fabrica
									AND qtde_disponivel_site IS NOT NULL
									AND qtde_disponivel_site < 1";

							$res         = pg_exec ($con,$sql);
							if (pg_numrows ($res) > 0) {
								$peca_peca       = pg_result ($res,0,peca);
								$peca_descricao  = pg_result ($res,0,descricao);
								$peca_referencia = pg_result ($res,0,referencia);
								$qtde_disponivel = pg_result ($res,0,qtde_disponivel_site);
								$msg_erro        = pg_errormessage($con);
								$mandar_email = "sim";
							}
						}

						if (strlen($pedido_item)==0){
							$msg_erro .= "Não foi possível adicionar o produto no carrinho.";
						}

						if (strlen($msg_erro) == 0) {
							$sql = "SELECT fn_valida_pedido_item ($pedido,$peca,$login_fabrica)";
							$res = pg_exec ($con,$sql);
							$msg_erro .= pg_errormessage($con);
						}
						if (strlen ($msg_erro) > 0) {
							$res = pg_exec ($con,"ROLLBACK TRANSACTION");
							break ;
						}
					}
					if($login_fabrica == 3)
					{
						#if( strlen($valor) > 0 AND strlen($qtde) > 0){
						#	$total_valor = (($total_valor) + ( str_replace( "," , "." ,$valor) * $qtde));
						#}
					}
				}

				//HD: 59705 08/01/2009 - RETIRADO
				if($liquidacao == "SIM" and $login_fabrica ==3 and 1==2){
					//HD: 58775 07/01/2009 - ESTAVA GRAVANDO PEDIDOS DE PEÇAS DE LINHAS DIFERENTES (ATUALMENTE NAO PODE)
					$sql = "SELECT fn_pedido_finaliza($pedido,$login_fabrica)";
					$res = @pg_exec ($con,$sql);
					$msg_erro = pg_errormessage($con);
				}
				if (strlen ($msg_erro) == 0) {

					$res = pg_exec ($con,"COMMIT TRANSACTION");
					#header ("Location: loja_finalizado.php?pedido=$pedido&loc=1");
					$msg = "Produto adicionado com sucesso!";

					if ($mandar_email == "sim"){
						$sql = "SELECT email_loja_virtual
								FROM tbl_configuracao
								WHERE fabrica=$login_fabrica";
						$res_conf = pg_exec($con,$sql);
						if (pg_numrows($res_conf)>0){
							$email_loja_virtual = trim(pg_result($res_conf,0,email_loja_virtual));
						}
						if (strlen($email_loja_virtual)>0){
                        
							$nome       = "Telecontrol";
							$email       = "$email_loja_virtual";
                            //$email = "ederson@sanweb.com.br,ederson.sandre@telecontrol.com.br,cgustavo25@gmail.com";
							$mensagem  .= "MENSAGEM AUTOMÁTICA. NÃO RESPONDA A ESTE E-MAIL <br><br>Este email foi enviado pelo sistema de Loja Virtual <br><br>Peça: <b>$peca_referencia - $peca_descricao</b><br>Quantidade disponível em estoque: <b>$qtde_disponivel</b><br><br>Esta peça teve seu estoque zerado. <b>É recomendado voltar ao preço normal.</b><br><br><br>____________________________________________<br>\n";
							$mensagem  .= "Telecontrol Networking<br>\n";
							$mensagem  .= "www.telecontrol.com.br";
							$assunto   = "Loja Virtual - Produto sem estoque - $peca_referencia - $peca_descricao";
							$boundary = "XYZ-" . date("dmYis") . "-ZYX";
							$mens  = "--$boundary\n";
							$mens .= "Content-Transfer-Encoding: 8bits\n";
							$mens .= "Content-Type: text/html; charset=\"ISO-8859-1\"\n\n";
							$mens .= "$mensagem\n";
							$mens .= "--$boundary\n";
							$headers  = "MIME-Version: 1.0\n";
							$headers .= "Date: ".date("D, d M Y H:i:s O")."\n";
							$headers .= "From: \"Telecontrol\" <helpdesk@telecontrol.com.br>\r\n";
							$headers .= "Content-type: multipart/mixed; boundary=\"$boundary\"\r\n";

                            $emails = explode(',',$email);
                            foreach($emails AS $mail){
                                if(!empty($mail))
                                    $mailer->AddAddress($mail);    
                            }

                            $mailer->IsSMTP();
                            $mailer->IsHTML();                    
                            $mailer->AddReplyTo('helpdesk@telecontrol.com.br', 'Suporte Telecontrol');
                            $mailer->Subject = $assunto;
                            $mailer->Body = $mensagem;

                            if (!$mailer->Send()) {              
                                    $msg_erro = "Erro ao enviar email para {$email_para}";
                                    echo $mailer->ErrorInfo;
                            }
							//mail($email, $assunto,$mens, $headers);
						}
					}
				}else{
					$res = pg_exec ($con,"ROLLBACK TRANSACTION");
				}
			}else{
				if($liquidacao == "NAO" and strlen($peca)==0){
					$msg_erro   .= "Selecione o produto.";
				}
			}
		}
		if ($adicionar_ajax=="sim"){
			if (strlen($msg_erro)>0){
				echo "erro||$msg_erro||";
			}else{
				echo "ok||"."||";
			}
			$sql = "SELECT  DISTINCT
					tbl_pedido.pedido           ,
					tbl_pedido.linha            ,
					tbl_linha.nome as linha_desc
			FROM  tbl_pedido
			JOIN  tbl_pedido_item USING (pedido)
			JOIN  tbl_peca        USING (peca)
			LEFT JOIN tbl_linha USING(linha)
			WHERE tbl_pedido.posto   = $login_posto
			AND   tbl_pedido.fabrica = $login_fabrica
			AND   tbl_pedido.pedido_loja_virtual IS TRUE
			AND   tbl_pedido.exportado           IS NULL
			AND   tbl_pedido.finalizado          IS NULL
			ORDER BY tbl_pedido.pedido DESC";
			$res = pg_exec ($con,$sql);
			$qtde_itens = pg_numrows($res);

			if ( $qtde_itens > 0) {
				for ($i=0;$i<pg_numrows($res);$i++){
					$pedido      = trim(pg_result($res,$i,pedido));
					$linha       = trim(pg_result($res,$i,linha));
					$linha_desc  = trim(pg_result($res,$i,linha_desc));
					$soma = imprimir_pedido_item($con,$login_fabrica,$login_posto,$status,$pedido);
					$xsoma = number_format($soma, 2, ',', '');
					$xsoma = str_replace(".",",",$xsoma);
					$retorno .="<b>Sub Total: <font color='#FF0033'>R$ $xsoma</font></b><br>";
				}
			}
			echo "||$retorno";
			exit;
		}
	}

	if($acao=='limpa'){
		$res = pg_exec ($con,"BEGIN TRANSACTION");

		if (strlen($msg_erro)==0){
			# Coloquei $pedido
			$sql = "UPDATE tbl_peca
					SET qtde_disponivel_site = qtde_disponivel_site + tbl_pedido_item.qtde
					FROM tbl_pedido
					JOIN tbl_pedido_item ON tbl_pedido_item.pedido = tbl_pedido.pedido
					WHERE tbl_pedido_item.peca          = tbl_peca.peca
					AND tbl_pedido.fabrica              = $login_fabrica
					AND tbl_pedido.posto                = $login_posto
					AND tbl_pedido.pedido_loja_virtual IS TRUE
					AND tbl_pedido.exportado           IS NULL
					AND tbl_pedido.finalizado          IS NULL
					AND tbl_peca.qtde_disponivel_site  IS NOT NULL";
			$res = pg_exec ($con,$sql);
			$msg_erro .= pg_errormessage($con);
		}

		if (strlen($msg_erro)==0){

			$sql = "UPDATE tbl_pedido
					SET fabrica  = 0,
						total    =  0
					WHERE tbl_pedido.fabrica              = $login_fabrica
					AND   tbl_pedido.posto                = $login_posto
					AND   tbl_pedido.exportado           IS NULL
					AND   tbl_pedido.finalizado          IS NULL
					AND   tbl_pedido.pedido_loja_virtual IS TRUE ";
			$res = pg_exec ($con,$sql);
			$msg_erro .= pg_errormessage($con);
		}

		if (strlen ($msg_erro) == 0) {
			$res = pg_exec ($con,"COMMIT TRANSACTION");
			//$res = pg_exec ($con,"ROLLBACK TRANSACTION");
			$msg = "O carrinho foi limpo!";
		}else{
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		}
		
		header('Location: lv_completa.php');
	}

	if($acao=='remover'){

			$res = pg_exec ($con,"BEGIN TRANSACTION");

			$pedido      = $_GET['pedido'];
			$peca        = $_GET['peca'];
			$pedido_item = $_GET['pedido_item'];

			$sql = "UPDATE tbl_pedido SET finalizado = null
					WHERE  tbl_pedido.pedido_loja_virtual IS TRUE
					AND    tbl_pedido.exportado    IS NULL
					AND    tbl_pedido.finalizado   IS NOT NULL
					AND    tbl_pedido.pedido       = $pedido
					AND    tbl_pedido.posto        = $login_posto
					AND    tbl_pedido.fabrica      = $login_fabrica";
			$res = pg_exec ($con,$sql);

			$sql = "SELECT	tbl_pedido_item.pedido_item,
							tbl_pedido_item.qtde,
							tbl_pedido_item.preco,
							tbl_peca.ipi
					FROM tbl_pedido_item
					JOIN tbl_pedido USING(pedido)
					JOIN tbl_peca USING(peca)
					WHERE  tbl_pedido_item.pedido = $pedido
					AND    tbl_pedido_item.peca   = $peca
					AND    tbl_pedido.fabrica     = $login_fabrica
					AND    tbl_pedido.posto       = $login_posto
					AND    tbl_pedido.pedido_loja_virtual  IS TRUE
					AND    tbl_pedido.exportado            IS NULL
					AND    tbl_pedido.finalizado           IS NULL
					";
			$res = pg_exec ($con,$sql);
			if (pg_numrows ($res) > 0) {
				$pedido_item  = pg_result ($res,0,pedido_item);
				$qtde_remover = pg_result ($res,0,qtde);
				$preco        = pg_result ($res,0,preco);
				$ipi          = pg_result ($res,0,ipi);
			}else{
				$msg_erro .= "Produto não encontrado.";
			}

			if (strlen($msg_erro) == 0) {
				$sql = "DELETE FROM tbl_pedido_item
						WHERE pedido      = $pedido
						AND   peca        = $peca
						AND   pedido_item = $pedido_item";
				$res = pg_exec ($con,$sql);
				$msg_erro .= pg_errormessage($con);
			}

			if (strlen($msg_erro) == 0) {
				$sql = "UPDATE tbl_peca
						SET   qtde_disponivel_site = qtde_disponivel_site + $qtde_remover
						WHERE peca     = $peca
						AND   fabrica  = $login_fabrica
						AND   qtde_disponivel_site IS NOT NULL";
				$res = pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
			}

			$sql = "SELECT  count(*) as qtde_itens
					FROM  tbl_pedido
					JOIN  tbl_pedido_item USING(pedido)
					WHERE tbl_pedido.pedido = $pedido
					AND   tbl_pedido.pedido_loja_virtual IS TRUE
					AND   tbl_pedido.exportado     IS NULL
					AND   tbl_pedido.finalizado    IS NULL
					AND   tbl_pedido.posto         = $login_posto
					AND   tbl_pedido.fabrica       = $login_fabrica";
			$res = pg_exec ($con,$sql);
			$qtde_itens_pedido = pg_result($res,0,qtde_itens);
			$msg_erro .= pg_errormessage($con);

			# Seta fabrica=0 se não tiver mais produtos
			if ($qtde_itens_pedido==0){
				if (strlen($msg_erro)==0){
					$sql = "UPDATE tbl_pedido
							SET fabrica = 0
							WHERE tbl_pedido.pedido  = $pedido
							AND   tbl_pedido.posto   = $login_posto
							AND   tbl_pedido.fabrica = $login_fabrica
							AND   tbl_pedido.pedido_loja_virtual IS TRUE
							AND   tbl_pedido.exportado           IS NULL";
					$res = pg_exec ($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}
			}else{
				if (strlen($ipi)>0 AND $ipi>0){
					$total_peca = $qtde * $preco + ($qtde * $preco * $ipi)/100;
				}else{
					$total_peca = $qtde * $preco;
				}

				$sql = "UPDATE tbl_pedido
						SET total = COLLAPSE(total,0) - $total_peca
						WHERE pedido = $pedido
						AND fabrica  = $login_fabrica
						AND posto    = $login_posto";
				#$res = pg_exec ($con,$sql);
				#$msg_erro .= pg_errormessage($con);

				$sql = "UPDATE tbl_pedido
							SET   total = ROUND (
											(
											SELECT SUM(tbl_pedido_item.preco * tbl_pedido_item.qtde + tbl_pedido_item.preco * tbl_pedido_item.qtde * tbl_pedido_item.ipi / 100)
											FROM   tbl_pedido_item
											JOIN tbl_peca USING(peca)
											WHERE  tbl_pedido_item.pedido = $pedido
											)::NUMERIC , 2
										)
						WHERE tbl_pedido.pedido     = $pedido
						AND   tbl_pedido.posto      = $login_posto
						AND   tbl_pedido.fabrica    = $login_fabrica
						AND   tbl_pedido.exportado           IS NULL
						AND   tbl_pedido.pedido_loja_virtual IS TRUE
						";
				if($login_fabrica==1){
					$sql = "UPDATE tbl_pedido
							SET   total = ROUND (
								(
								SELECT SUM(tbl_pedido_item.preco * tbl_pedido_item.qtde)
								FROM   tbl_pedido_item
								JOIN tbl_peca USING(peca)
								WHERE  tbl_pedido_item.pedido = $pedido
								)::NUMERIC , 2
							)
						WHERE tbl_pedido.pedido     = $pedido
						AND   tbl_pedido.posto      = $login_posto
						AND   tbl_pedido.fabrica    = $login_fabrica
						AND   tbl_pedido.exportado           IS NULL
						AND   tbl_pedido.pedido_loja_virtual IS TRUE
						";
				}
				$res = pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
			}

			if (strlen ($msg_erro) == 0) {
				$res = pg_exec ($con,"COMMIT TRANSACTION");
				#$res = pg_exec ($con,"ROLLBACK TRANSACTION");
				$msg = "Produto removido com sucesso!";
			}else{
				$res = pg_exec ($con,"ROLLBACK TRANSACTION");
			}
	}
}

//alterado por Gustavo HD 3780 - refeito por Fabio
if ($btn_acao == "fechar_pedido"){

	if (strlen($pedido)==0){
		//$msg_erro .= "Não foi criado o pedido!";
	}

	$sql ="	SELECT SUM(total) AS total
			FROM tbl_pedido
			WHERE tbl_pedido.posto   = $login_posto
			AND   tbl_pedido.fabrica = $login_fabrica
			AND   tbl_pedido.pedido_loja_virtual IS TRUE
			AND   tbl_pedido.exportado           IS NULL
			AND   tbl_pedido.finalizado          IS NULL
			";
	$res = pg_exec ($con,$sql);
	if (pg_numrows ($res) > 0) {
		$valor_total_pedido = pg_result ($res,0,total);
	}

	$sql ="	SELECT	valor_pedido_minimo,
					valor_pedido_minimo_capital
			FROM tbl_fabrica
			WHERE fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);
	if (pg_numrows ($res) > 0) {
		$valor_pedido_minimo        = pg_result ($res,0,valor_pedido_minimo);
		$valor_pedido_minimo_capital = pg_result ($res,0,valor_pedido_minimo_capital);
	}

	$sql ="	SELECT capital_interior
			FROM tbl_posto
			WHERE posto = $login_posto;";
	$res = pg_exec ($con,$sql);
	if (pg_numrows ($res) > 0) {
		$capital_interior = pg_result ($res,0,capital_interior);
	}

	$valor_minimo = 0;

	if ($capital_interior == 'CAPITAL') {
		$valor_minimo = $valor_pedido_minimo_capital;
	}

	if ($capital_interior == 'INTERIOR'){
		$valor_minimo = $valor_pedido_minimo;
	}

	if ($valor_minimo == 0) {
		$valor_minimo = $valor_pedido_minimo;
	}

	$valor_minimo = number_format($valor_minimo,2,'.',',');



	if ($valor_total_pedido < $valor_minimo) {
		$msg_erro .= "O valor minimo para um pedido é de R$ $valor_minimo";
	}

	#hd 169977 samuel
	/*
	#HD 17012
	$pedido_via_distribuidor = "";
	$sql = "SELECT pedido_via_distribuidor
			FROM  tbl_fabrica
			WHERE fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);
	if(pg_numrows($res)>0) {
		$pedido_via_distribuidor = pg_result ($res,0,pedido_via_distribuidor);
	}
	#HD 17012
	if ($pedido_via_distribuidor == 't'){
		$sql = "SELECT distribuidor
				FROM  tbl_posto_fabrica
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica
				AND   tbl_posto_fabrica.posto   = $login_posto
				AND   tbl_posto_fabrica.distribuidor IS NOT NULL";
		$res = pg_exec ($con,$sql);
		if(pg_numrows($res)>0) {
			$distribuidor = pg_result ($res,0,distribuidor);
		}
	}
	*/

	$res = pg_exec ($con,"BEGIN TRANSACTION");


	$sql = "SELECT
				tbl_pedido.pedido,
				tbl_pedido.linha,
				sum(total) as total_pedido
		FROM  tbl_pedido
		LEFT JOIN tbl_condicao USING(condicao)
		WHERE tbl_pedido.posto   = $login_posto
		AND   tbl_pedido.fabrica = $login_fabrica
		AND   tbl_pedido.pedido_loja_virtual IS TRUE
		AND   tbl_pedido.exportado           IS NULL
		AND   tbl_pedido.finalizado          IS NULL
		GROUP BY tbl_pedido.pedido,tbl_pedido.linha
		ORDER BY tbl_pedido.pedido DESC";

	$resPedido = pg_exec ($con,$sql);
	$qtde_produto = 0;
	$total        = 0;
	$pedidos      = "";

	if (pg_numrows($resPedido) > 0) {
		for ($i=0;$i<pg_numrows($resPedido);$i++){
			$pedido       = pg_result ($resPedido,$i,pedido);
			$linha        = pg_result ($resPedido,$i,linha);
			$total_pedido = pg_result ($resPedido,$i,total_pedido);
			if ($login_fabrica ==3 OR $login_fabrica == 85) {
				if(strlen($linha) > 0){
					$sql2 = "	SELECT tbl_posto_linha.tabela
								FROM tbl_posto_linha
								WHERE tbl_posto_linha.posto   = $login_posto
								AND tbl_posto_linha.linha   = $linha
								LIMIT 1;";
					$res2 = pg_exec ($con,$sql2);
					if(pg_numrows($res2)>0) {
						$tabela = pg_result ($res2,0,tabela);
						$sql =" UPDATE tbl_pedido SET tabela = $tabela
								WHERE tbl_pedido.posto   = $login_posto
								AND   tbl_pedido.fabrica = $login_fabrica
								AND   tbl_pedido.pedido  = $pedido
								AND   tbl_pedido.pedido_loja_virtual IS TRUE
								AND   tbl_pedido.exportado           IS NULL;";
						$res = pg_exec ($con,$sql);
					}
				}
				$sql ="	UPDATE tbl_pedido
						SET condicao = 962
						WHERE  tbl_pedido.posto   = $login_posto
						AND    tbl_pedido.fabrica = $login_fabrica
						AND    tbl_pedido.pedido  = $pedido
						AND    tbl_pedido.pedido_loja_virtual IS TRUE
						AND    tbl_pedido.exportado           IS NULL;";
				$res = pg_exec ($con,$sql);

				if ( $total_pedido > 200.01 ) {

					$sql ="	UPDATE tbl_pedido
							SET condicao = 963
							WHERE  tbl_pedido.posto   = $login_posto
							AND    tbl_pedido.fabrica = $login_fabrica
							AND    tbl_pedido.pedido  = $pedido
							AND    tbl_pedido.pedido_loja_virtual IS TRUE
							AND    tbl_pedido.exportado           IS NULL;	";
					$res = pg_exec ($con,$sql);
				}

				if ( $total_pedido > 400 ) {
					$sql ="	UPDATE tbl_pedido
							SET condicao = 964
							WHERE  tbl_pedido.posto   = $login_posto
							AND    tbl_pedido.fabrica = $login_fabrica
							AND    tbl_pedido.pedido  = $pedido
							AND    tbl_pedido.pedido_loja_virtual IS TRUE
							AND    tbl_pedido.exportado           IS NULL;	";
					$res = pg_exec ($con,$sql);
				}

				$sql ="	SELECT tbl_pedido_item.peca
						FROM   tbl_pedido
						JOIN   tbl_pedido_item   USING (pedido)
						WHERE  tbl_pedido_item.preco IS NULL
						AND    tbl_pedido.posto   = $login_posto
						AND    tbl_pedido.fabrica = $login_fabrica
						AND    tbl_pedido.pedido  = $pedido
						AND    tbl_pedido.pedido_loja_virtual IS TRUE
						AND    tbl_pedido.exportado           IS NULL";
				$res = pg_exec ($con,$sql);
				if(pg_numrows($res)>0) {
					$peca_aux = pg_result ($res,0,peca);
					$msg_erro .= " Peça $peca_aux sem preço.";
				}

				#HD 169977 - samuel 

				#HD 17012
				/*
				if ($pedido_via_distribuidor == 't' AND strlen($distribuidor)>0){
					$sql = "SELECT tbl_posto_fabrica.pedido_via_distribuidor
							FROM tbl_posto_fabrica
							WHERE fabrica = $login_fabrica
							AND   posto   = $distribuidor
							AND   pedido_via_distribuidor IS TRUE";
					$res = pg_exec ($con,$sql);
					if(pg_numrows($res)>0) {
						$sql = "UPDATE tbl_pedido SET
									pedido_via_distribuidor = 't',
									distribuidor = $distribuidor
								WHERE tbl_pedido.pedido = $pedido";
						$res = pg_exec ($con,$sql);
					}
				}
				#HD 17012
				$sql = "UPDATE tbl_pedido SET
							distribuidor            = tbl_posto_linha.distribuidor ,
							pedido_via_distribuidor = 't'
						WHERE tbl_pedido.pedido = $pedido
						AND   tbl_pedido.linha  = tbl_posto_linha.linha
						AND   tbl_pedido.posto  = tbl_posto_linha.posto
						AND   exportado       IS NULL
						AND   tbl_posto_linha.distribuidor IS NOT NULL ;";
				$res = pg_exec ($con,$sql);

				#HD 17012
				$sql = "UPDATE tbl_pedido SET
							distribuidor = NULL,
							pedido_via_distribuidor = 'f'
						WHERE tbl_pedido.pedido = $pedido
						AND   tbl_pedido.linha = tbl_posto_linha.linha
						AND   tbl_pedido.posto = tbl_posto_linha.posto
						AND   exportado IS NULL
						AND  (tbl_posto_linha.distribuidor IS NULL OR tbl_posto_linha.posto = tbl_posto_linha.distribuidor) ;
						";
				$res = pg_exec ($con,$sql);

				#HD 17012
				$sql = "UPDATE tbl_pedido SET
							distribuidor = NULL ,
							pedido_via_distribuidor = 'f'
						WHERE tbl_pedido.pedido = $pedido
						AND   tbl_pedido.posto  = tbl_pedido.distribuidor ;";
				$res = pg_exec ($con,$sql);
				*/

				$sql ="SELECT total
						FROM tbl_pedido
						WHERE total IS NULL
						AND   tbl_pedido.posto   = $login_posto
						AND   tbl_pedido.fabrica = $login_fabrica
						AND   tbl_pedido.pedido  = $pedido
						AND   tbl_pedido.pedido_loja_virtual IS TRUE
						AND   tbl_pedido.exportado           IS NULL";
				$res = pg_exec ($con,$sql);
				if(pg_numrows($res)>0) {
					$sql ="UPDATE tbl_pedido SET
								finalizado = NULL
							WHERE total IS NULL
							AND   tbl_pedido.posto   = $login_posto
							AND   tbl_pedido.fabrica = $login_fabrica
							AND   tbl_pedido.pedido  = $pedido
							AND   tbl_pedido.pedido_loja_virtual IS TRUE
							AND   tbl_pedido.exportado           IS NULL";
					$res = pg_exec ($con,$sql);
					$msg_erro .= "Pedido da Loja Virtual não totalizado.";
				}else{

					$sql ="UPDATE tbl_pedido SET
								status_pedido = 1,
								finalizado = current_timestamp
							WHERE tbl_pedido.posto   = $login_posto
							AND   tbl_pedido.fabrica = $login_fabrica
							AND   tbl_pedido.pedido  = $pedido
							AND   tbl_pedido.pedido_loja_virtual IS TRUE
							AND   tbl_pedido.exportado           IS NULL";
					$res = pg_exec ($con,$sql);
				}

			}

			if($login_fabrica==10) {
				$sql_f = "SELECT sum(peso) as peso_total,
								 total,
								 case when length(cep) > 0 then cep else contato_cep end as cep
							from tbl_pedido
							JOIN tbl_pedido_item using(pedido)
							JOIN tbl_posto ON tbl_pedido.posto = tbl_posto.posto
							JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_pedido.posto and tbl_posto_fabrica.fabrica = tbl_pedido.fabrica
							JOIN tbl_peca ON tbl_peca.peca = tbl_pedido_item.peca
							WHERE tbl_pedido.posto   = $login_posto
							AND   tbl_pedido.fabrica = $login_fabrica
							AND   tbl_pedido.pedido  = $pedido
							AND   tbl_peca.frete_gratis IS NOT TRUE
							GROUP BY total,contato_cep,cep ";
				$res_f=pg_exec($con,$sql_f) ;
				$peso_total = pg_result($res_f,0,peso_total);
				$total      = pg_result($res_f,0,total);
				$cep        = pg_result($res_f,0,cep);
				$cep_origem = "17519255";
				if(strlen($cep) > 0){
					$valor_frete = calcula_frete($cep_origem,$cep,$peso_total);
					$sql_ft="UPDATE tbl_pedido set total = total + $valor_frete WHERE pedido = $pedido";
					$res_ft=pg_exec($con,$sql_ft);
				}


				$sql2 = "SELECT tabela
						FROM tbl_tabela
						WHERE fabrica = $login_fabrica
						AND ativa IS TRUE
						LIMIT 1";
				$res2 = pg_exec ($con,$sql2);
				if(pg_numrows($res2)>0) {
					$tabela = pg_result ($res2,0,tabela);
					$sql ="	UPDATE tbl_pedido
							SET tabela = $tabela
							WHERE tbl_pedido.posto   = $login_posto
							AND   tbl_pedido.fabrica = $login_fabrica
							AND   tbl_pedido.pedido  = $pedido
							AND   tbl_pedido.pedido_loja_virtual IS TRUE
							AND   tbl_pedido.exportado           IS NULL;";
					$res = pg_exec ($con,$sql);
				}

				$sql ="UPDATE tbl_pedido SET
							distribuidor            = 4311,
							pedido_via_distribuidor = 't'
						WHERE  tbl_pedido.posto   = $login_posto
						AND    tbl_pedido.fabrica = $login_fabrica
						AND   tbl_pedido.pedido  = $pedido
						AND    tbl_pedido.pedido_loja_virtual IS TRUE
						AND    tbl_pedido.exportado           IS NULL;";
				$res = pg_exec ($con,$sql);

				$sql ="SELECT total
						FROM tbl_pedido
						WHERE total IS NULL
						AND   tbl_pedido.posto   = $login_posto
						AND   tbl_pedido.fabrica = $login_fabrica
						AND   tbl_pedido.pedido  = $pedido
						AND   tbl_pedido.pedido_loja_virtual IS TRUE
						AND   tbl_pedido.exportado           IS NULL";
				$res = pg_exec ($con,$sql);
				if(pg_numrows($res)>0) {
					$sql ="UPDATE tbl_pedido SET
								finalizado = NULL
							WHERE total IS NULL
							AND   tbl_pedido.posto   = $login_posto
							AND   tbl_pedido.fabrica = $login_fabrica
							AND   tbl_pedido.pedido  = $pedido
							AND   tbl_pedido.pedido_loja_virtual IS TRUE
							AND   tbl_pedido.exportado           IS NULL";
					$res = pg_exec ($con,$sql);
					$msg_erro .= "Pedido da Loja Virtual não totalizado.";
				}else{

					$sql ="UPDATE tbl_pedido SET
								finalizado = current_timestamp
							WHERE tbl_pedido.posto   = $login_posto
							AND   tbl_pedido.fabrica = $login_fabrica
							AND   tbl_pedido.pedido  = $pedido
							AND   tbl_pedido.pedido_loja_virtual IS TRUE
							AND   tbl_pedido.exportado           IS NULL";
					$res = pg_exec ($con,$sql);

					$sql ="UPDATE tbl_pedido SET
								status_pedido = 1,
								tipo_pedido = 77
							WHERE tbl_pedido.posto   = $login_posto
							AND   tbl_pedido.fabrica = $login_fabrica
							AND   tbl_pedido.pedido  = $pedido
							AND   tbl_pedido.pedido_loja_virtual IS TRUE
							AND   tbl_pedido.exportado           IS NULL
							AND   status_pedido IS NULL";
					$res = pg_exec ($con,$sql);
				}



			}
			#HD16875
			if($login_fabrica==1 or $login_fabrica==35){
				$sql = "SELECT fn_pedido_finaliza($pedido,$login_fabrica)";
				$res = @pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
			}
		}
	}

	if (strlen ($msg_erro) == 0) {

		$res = pg_exec ($con,"COMMIT TRANSACTION");

		if($produto_acabado=="t" AND ($login_fabrica==3 OR $login_fabrica==85)){
			$produto_acabado_limpa = "&produto_acabado=t";
		}

		$msg = "Pedido finalizado com sucesso!";
		if($login_fabrica == 85){
			$flagJsRedireciona = true;
		}
		//echo "<script languague='javascript'>window.location='".$PHP_SELF."?status=finalizado&p=$pedido$produto_acabado_limpa';</script>";

		if($login_fabrica == 10) {
			$sql=" SELECT tbl_posto.nome,
						  tbl_peca.descricao,
						  tbl_pedido_item.qtde,
						  tbl_pedido.pedido
					FROM  tbl_pedido
					JOIN  tbl_pedido_item USING (pedido)
					JOIN  tbl_posto ON tbl_posto.posto = tbl_pedido.posto
					WHERE tbl_pedido.posto   = $login_posto
					AND   tbl_pedido.fabrica = $login_fabrica
					AND   tbl_pedido.pedido  = $pedido ";
			$res=pg_exec($con,$sql);

			$nome       = "Telecontrol";
			$email       = "ronaldo@telecontrol.com.br, helpdesk@telecontrol.com.br";
			$mensagem  .= "MENSAGEM AUTOMÁTICA. NÃO RESPONDA A ESTE E-MAIL <br><br>O pedido $pedido da loja virtual precisa da sua aprovação para liberar embarque: <br>Para aprovar ou consultar o pedido, clique no link abaixo:<br><br><a href='http://posvenda.telecontrol.com.br/assist/distrib/pedido_consulta.php?status=aprovacao'>http://posvenda.telecontrol.com.br/assist/distrib/pedido_consulta.php?status=aprovacao</a><br>\n";
			$mensagem  .= "Telecontrol Networking<br>\n";
			$mensagem  .= "www.telecontrol.com.br";
			$assunto   = "Loja Virtual - Pedido $pedido aguardando sua aprovação ";
			$boundary = "XYZ-" . date("dmYis") . "-ZYX";
			$mens  = "--$boundary\n";
			$mens .= "Content-Transfer-Encoding: 8bits\n";
			$mens .= "Content-Type: text/html; charset=\"ISO-8859-1\"\n\n";
			$mens .= "$mensagem\n";
			$mens .= "--$boundary\n";
			$headers  = "MIME-Version: 1.0\n";
			$headers .= "Date: ".date("D, d M Y H:i:s O")."\n";
			$headers .= "From: \"Telecontrol\" <helpdesk@telecontrol.com.br>\r\n";
			$headers .= "Content-type: multipart/mixed; boundary=\"$boundary\"\r\n";
			@mail($email, utf8_encode($assunto), utf8_encode($mens), $headers);

			$sql = "SELECT  tbl_peca.peca,
							tbl_peca.descricao,
							tbl_peca.referencia,
							tbl_peca.qtde_disponivel_site
					FROM tbl_pedido_item
					JOIN tbl_peca USING(peca)
					WHERE tbl_pedido_item.pedido = $pedido
					AND fabrica  = $login_fabrica
					AND qtde_disponivel_site IS NOT NULL
					AND qtde_minima_estoque  IS NOT NULL
					AND qtde_disponivel_site <= qtde_minima_estoque";

			$res         = pg_exec ($con,$sql);
			if (pg_numrows ($res) > 0) {
				for($x=0;$x<pg_numrows($res);$x++){
				$peca_peca       = pg_result ($res,$x,peca);
				$peca_descricao  = pg_result ($res,$x,descricao);
				$peca_referencia = pg_result ($res,$x,referencia);
				$qtde_disponivel = pg_result ($res,$x,qtde_disponivel_site);
				$msg_erro        = pg_errormessage($con);

					$nome      = "Telecontrol";
					$email     = "ronaldo@telecontrol.com.br, helpdesk@telecontrol.com.br";
					$mensagem  = "MENSAGEM AUTOMÁTICA. NÃO RESPONDA A ESTE E-MAIL <br><br>Este email foi enviado pelo sistema de Loja Virtual <br><br>Peça: <b>$peca_referencia - $peca_descricao</b><br>Quantidade disponível em estoque: <b>$qtde_disponivel</b><br><br> chegou na quantidade mínima. Por favor, verificar. <br><br><br>____________________________________________<br>\n";
					$mensagem  .= "Telecontrol Networking<br>\n";
					$mensagem  .= "www.telecontrol.com.br";
					$assunto   = "Loja Virtual - Produto chegou na quantidade mínima no estoque - $peca_referencia - $peca_descricao";
					$boundary = "XYZ-" . date("dmYis") . "-ZYX";
					$mens  = "--$boundary\n";
					$mens .= "Content-Transfer-Encoding: 8bits\n";
					$mens .= "Content-Type: text/html; charset=\"ISO-8859-1\"\n\n";
					$mens .= "$mensagem\n";
					$mens .= "--$boundary\n";
					$headers  = "MIME-Version: 1.0\n";
					$headers .= "Date: ".date("D, d M Y H:i:s O")."\n";
					$headers .= "From: \"Telecontrol\" <helpdesk@telecontrol.com.br>\r\n";
					$headers .= "Content-type: multipart/mixed; boundary=\"$boundary\"\r\n";
					@mail($email, utf8_encode($assunto), utf8_encode($mens), $headers);
				}
			}
		}
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

//########################################################################################################
$layout_menu = 'pedido';
$title = "Carrinho de Compras";
if (strlen($cook_fabrica)==0 AND (strlen($cook_login_unico)>0 OR strlen($cook_login_simples)>0)){
	include "login_unico_cabecalho.php";
}else{
	include "cabecalho.php";
}

?>
<style type="text/css">
table.base {
       border-style: solid;
       border-width: 2px;
		border-color: #6B7290;
		margin-top: 5px;
}

.lcar_title {
       font-size: 10px;
       font-weight: bold;
       color: white;
       font-weight: bold;
       background-image: url('./imagens/barra_dg_azul_tc_30.jpg');
       background-repeat: repeat-x;
       background-attachment: top, left;
}

thead#title{text-align:left;background-image: url('imagens/barra_dg_azul_tc_30.jpg');color:#FFFFFF;}

/*div.top, div.inner {
	color: #266BBF; text-align: center; font: verdana, arial, sans-serif;
}*/

div.top {
	float: left; width: 100%; padding: 2px; margin: 0em; /*background:#0082d7; */
}

.tabela_peca td{
	padding:2px;
}

div#cc {	/*	Continuar Comprando	*/
	content: " ";
	position: relative;
	top: 0px;
	left:0px;
	width: 157px;
	height: 25px;
	background-image: url('./imagens/btn_cont_compra_up.png');
	background-repeat: none;
}
div#cc :hover {
	background-image: url('./imagens/btn_cont_compra_dn.png');
	background-repeat: none;
}

</style>

<BODY TOPMARGIN=0>
<?

include 'lv_menu.php';
?>
<script type='text/javascript' src='admin/js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="admin/js/jquery.autocomplete.css" />
<script type='text/javascript' src='admin/js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='admin/js/dimensions.js'></script>

<script type="text/javascript" src="js/niftycube.js"></script>
<script type="text/javascript" src="js/niftyLayout.js"></script>

<script language="JavaScript">

<?php if($login_fabrica == 85){ ?>
window.onload = function(){
	var redireciona = <?php echo $flagJsRedireciona; ?>;

	if(redireciona == true){
		window.setTimeout(function(){
			window.location = 'lv_completa.php';
		},2000);		
	}
}
<?php } ?>
		

function adicionarProduto(form){

	peca       = form.cod_produto.value;
	referencia = form.referencia_peca.value;
	qtde       = form.qtde.value;
	valor      = form.valor.value;
	descricao  = form.descricao_peca.value;
	qtde_maxi  = form.qtde_maxi.value;
	qtde_disp  = form.qtde_disp.value;
	qtde_min    = form.qtde_min.value;

	if (peca.length==0){
		alert('Informe a peça');
		return;
	}

	if (qtde>0){
		endereco = "&cod_produto="+peca+"&referencia="+referencia+"&qtde="+qtde+"&valor="+valor+"&descricao="+descricao+"&qtde_maxi="+qtde_maxi+"&qtde_disp="+qtde_disp+"&qtde_min="+qtde_min;

		form.btn_comprar.disabled = true;

		$.ajax({
			type: "POST",
			url: "<?=$PHP_SELF?>?acao=adicionar&adicionar_ajax=sim",
			data: endereco,
			success: function(msg){
				retorno = msg.split("||");
				if (retorno[0] == "erro"){
					alert(retorno[1]);
				}else{
					form.cod_produto.value		= "";
					form.referencia_peca.value	= "";
					form.qtde.value				= "";
					form.valor.value			= "";
					form.descricao_peca.value	= "";
					form.qtde_maxi.value		= "";
					form.qtde_disp.value		= "";
					form.qtde_min.value			= "";
				}
				$('#tabela_produtos tbody').html(retorno[2]); //tabela com os itens
				$('#total_pedido').html(retorno[3]); // total pedido
				form.btn_comprar.disabled = false;
				$("#referencia_peca").focus();
			}
		});
	}else{
		alert('Informe a quantidade');
	}
}


$().ready(function() {

	function formatItem(row) {
		return row[2] + " - " + row[1];
	}

	/* DE */
	/* Busca por Produto */
	/* echo "$peca|$descricao|$referencia|$ipi|$linha|$qtde_max_site|$qtde_disponivel_site|$preco_formatado"; */
	$("#referencia_peca").autocomplete("<?echo $PHP_SELF.'?tipo_busca=produto&busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#referencia_peca").result(function(event, data, formatted) {
		$("#cod_produto").val(data[0]) ;     // peca
		$("#descricao_peca").val(data[1]) ;  // descricao
		$("#referencia_peca").val(data[2]) ; // referencia
		$("#ipi").val(data[3]) ;             // ipi
		$("#linha").val(data[4]) ;           // linha
		$("#qtde_maxi").val(data[5]) ;       // qtde_max
		$("#qtde_disp").val(data[6]) ;       // qtde disponivel
		$("#valor").val(data[7]) ;           // preço formatado
		$("#qtde_min").val(data[8]) ;        // qtde minima
		$('#qtde').focus();
	});

	/* Busca pelo Nome */
	$("#descricao_peca").autocomplete("<?echo $PHP_SELF.'?tipo_busca=produto&busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#descricao_peca").result(function(event, data, formatted) {
		$("#cod_produto").val(data[0]) ;     // peca
		$("#descricao_peca").val(data[1]) ;  // descricao
		$("#referencia_peca").val(data[2]) ; // referencia
		$("#ipi").val(data[3]) ;             // ipi
		$("#linha").val(data[4]) ;           // linha
		$("#qtde_maxi").val(data[5]) ;       // qtde_max
		$("#qtde_disp").val(data[6]) ;       // qtde disponivel
		$("#valor").val(data[7]) ;           // preço formatado
		$("#qtde_min").val(data[8]) ;        // qtde minima
		$('#qtde').focus();
	});


});

function MostraEsconde(dados) {

    if (document.getElementById)
    {
        var style2 = document.getElementById(dados);
        if (style2==false) return;
        if (style2.style.display=="block"){
            style2.style.display = "none";
            }
        else{
            style2.style.display = "block";
        }
     }
}
</script>
<script language='javascript'>
	function checarNumero(campo){
		var num = campo.value.replace(",",".");
		campo.value = parseInt(num);
		if (campo.value<0){
			campo.value = campo.value * -1;
		}
		if (campo.value=='NaN') {
			campo.value='';
		}
	}
</script>
<?

$status = trim($_GET['status']);

if ($status=="finalizado" AND strlen ($msg_erro) == 0) {
	$pedido = $_GET['p'];
}

if(strlen($msg_erro)>0){
	$error = str_replace("ERROR:","",$msg_erro);
	echo "<center><b><h4 style='color:red'>".$error."</h4></b></center>";
}
if(strlen($msg)>0){
	echo "<center><b><h4 style='color:blue'>".$msg."</h4></b></center>";
}

if($login_posto){
	$sql="SELECT * FROM tbl_posto WHERE posto = $login_posto";
	$res = pg_exec ($con, $sql);
	if(pg_numrows($res)>0){
		$posto			= trim(pg_result ($res,0,posto));
		$nome			= trim(pg_result ($res,0,nome));
	}
}
echo "<BR>";
echo "<table width='98%' border='0' align='center' cellpadding='3' cellspacing='0'>";

echo "<tr>";

if($at_shop=="t" AND ($login_fabrica==3 OR $login_fabrica==85)){
	$produto_acabado = "t"; #todo at shop são produtos acabados
}
if(strlen($produto_acabado)==0){
	echo "<td width='180' valign='top'>";
		include "lv_menu_lateral.php";
	echo "</td>";
}

echo "<td align='center' valign='top' class='Conteudo'>";
	echo "<table width='95%' border='0' align='center' cellpadding='1' cellspacing='0'>";

	echo "<tr>";
	echo "<td>";
	echo " <font color='#4A4A4A'><B>Carrinho de Compras</B></font>";
	echo "</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<td  height='1px' bgcolor='#4A4A4A'>";
	echo "</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<td colspan='6' align='right' class='Conteudo'>";
/*PEDIDOS NAO FINALIZADOS*/

if (strlen($pedido)>0 OR $status=="finalizado"){
	# Retorna todos os pedidos da Loja Virtual que não foram exportados

	if ($status=="finalizado"){
		$condicao1 = "";
		if($login_fabrica==1) $condicao1 = " AND tbl_pedido.pedido=$pedido";
	}else{
		$condicao1 = " AND   tbl_pedido.finalizado          IS NULL ";
	}

	$sql = "SELECT
				tbl_pedido.pedido,
				to_char(tbl_pedido.finalizado,'DD/MM/YYYY') AS finalizado,
				to_char(tbl_pedido.data,'DD/MM/YYYY')       AS data      ,
				tbl_pedido.total                                         ,
				CASE WHEN tbl_pedido.pedido_blackedecker > 99999 THEN
						LPAD((tbl_pedido.pedido_blackedecker - 100000)::text,5,'0')
					ELSE
						LPAD(tbl_pedido.pedido_blackedecker::text,5,'0')
					END AS pedido_blackedecker ,
				tbl_condicao.descricao                      AS condicao  ,
				(SELECT login FROM tbl_admin WHERE tbl_admin.admin = tbl_pedido.admin) AS admin_pedido
		FROM  tbl_pedido
		LEFT JOIN tbl_condicao USING(condicao)
		WHERE tbl_pedido.posto   = $login_posto
		AND   tbl_pedido.fabrica = $login_fabrica
		AND   tbl_pedido.pedido_loja_virtual IS TRUE
		AND   tbl_pedido.exportado           IS NULL
		$condicao1
		ORDER BY tbl_pedido.pedido DESC";
	$res = pg_exec ($con,$sql);
	if (pg_numrows($res) > 0) {

		$qtde_produto = 0;
		$total        = 0;
		$pedidos      = "";

		for ($i=0;$i<pg_numrows($res);$i++){
			$_pedido      = trim(pg_result($res,$i,pedido));
			$admin_pedido = trim(pg_result($res,$i,admin_pedido));
			$pedido_bed   = trim(pg_result($res,$i,pedido_blackedecker));

			$pedidos    .= $_pedido;
			if(strlen($admin_pedido)>0) $pedidos .= " (Responsável: $admin_pedido)";
			$pedidos .= ", ";
			$finalizado  = trim(pg_result($res,$i,finalizado));
			$data        = trim(pg_result($res,$i,data));
			$condicao    = trim(pg_result($res,$i,condicao));


			if($login_fabrica==1){
				$sql = "
					SELECT SUM ((tbl_pedido_item.qtde * tbl_pedido_item.preco)+((tbl_pedido_item.qtde * tbl_pedido_item.preco) * tbl_peca.ipi / 100)) AS total
					FROM tbl_pedido_item
					JOIN tbl_peca using(peca)
					WHERE tbl_pedido_item.pedido = $_pedido ";
					$res2 = pg_exec ($con,$sql);
					$total      += trim(pg_result($res2,0,total));
			}else{
				$total      += trim(pg_result($res,$i,total));
			}

			$sql = "SELECT  SUM(qtde) as qtde
					FROM  tbl_pedido_item
					WHERE pedido = $_pedido";

			$res2 = pg_exec ($con,$sql);
			$qtde_produto  += pg_result($res2,0,qtde);


		}

		$total = number_format ( $total,2,'.',',');

		$pedidos = trim($pedidos);
		$pedidos = substr ($pedidos,0,strlen ($pedidos)-1);

		if($login_fabrica==1) $pedidos = $pedido_bed;

		if (strlen($finalizado)>0){
			$status_pedido = "Finalizado";
		}else{
			$status_pedido = "Não Finalizado";
		}

		echo "<br>";
		echo "<table width='100%' cellpadding='2' cellspacing='1' align='center' STYLE='border-bottom: 1px solid #ccc;'>";

		if ($status=="finalizado"){
			echo "<tr>";
			echo "<td colspan='4' align='center' class='Titulo' bgcolor='#e6eef7' >";
			echo "<font size='3' color='blue'><b>Pedido finalizado com sucesso!</b></font><br>";
			if($login_fabrica==10){
				echo "<font size='2' color='black'>Este pedido será enviado para o Responsável.</font><br>";
			}else{
				echo "<font size='2' color='black'>Este pedido será enviado para a Fábrica.</font><br>";
			}
			if($login_fabrica==1)
				echo "<font size='1' color='black'>Este pedido poderá ser consultado na <a href='pedido_relacao_blackedecker_acessorio.php?listar=todas'>Consulta de Pedidos</a></font><br><br>";
			elseif($login_fabrica==10){
				echo "<font size='1' color='black'>Este pedido poderá ser consultado na <a href='lv_pedido.php'>Consulta de Pedidos</a></font><br><br>";
			}else{
				echo "<font size='1' color='black'>Este pedido poderá ser consultado na <a href='pedido_relacao.php?listar=todas'>Consulta de Pedidos</a></font><br><br>";
			}
			echo "</td>";
			echo "</tr>";
		}

		echo "<tr> ";
		echo "<td  align='left' class='Titutlo2' >Número do seu Pedido</td>";
		echo "<td colspan='3' align='left' class='Conteudo'>$pedidos</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td  align='left' class='Titutlo2'>Status da sua Compra</td>";
		if (strlen($finalizado)>0){
			$status_pedido = "<b style='color:blue'>$status_pedido</n>";
		}else{
			$status_pedido = "<b style='color:red'>$status_pedido</n>";
		}
		echo "<td colspan='3' align='left' class='Conteudo'>$status_pedido</td>";
		echo "</tr>";

		if (strlen($finalizado)>0){
			echo "<tr>";
			echo "<td  align='left' class='Titutlo2'></td>";
			if($login_fabrica==10){
				echo "<td colspan='3' align='left' class='Conteudo' bgcolor='#e6eef7' ><b>O pedido da Loja Virtual será enviado hoje para o Responsável.</b></td>";
			}else{
				echo "<td colspan='3' align='left' class='Conteudo' bgcolor='#e6eef7' ><b>O pedido da Loja Virtual será enviado hoje para a Fábrica.</b></td>";
			}
			echo "</tr>";
		}

		echo "<tr>";
		echo "<td align='left' class='Titutlo2' width='200'>Quantidade de ";
		if($login_fabrica == 3 OR $login_fabrica == 85 ) echo "Peças";
		else                     echo "Produtos";
		echo "</td>";
		echo "<td align='left' class='Conteudo'>$qtde_produto</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td align='left' class='Titutlo2'>Valor</td>";
		echo "<td colspan='3' align='left' class='Conteudo'>R$ $total</td>";
		echo "</tr>";

		if (strlen($condicao)>0){
			echo "<tr>";
			echo "<td align='left' class='Titutlo2'>Condição de Pagamento</td>";
			echo "<td align='left' class='Conteudo'>$condicao</td>";
			echo "</tr>";
		}

		if (strlen($finalizado)==0){
			echo "<tr>";
			if($login_fabrica==10){
				echo "<td colspan='4' align='left' class='Conteudo' style='color: red;'>O pedido da Loja Virtual não será enviado até que seja <B>FECHADO</b>. </td>";				
			}else{				
				echo "<td colspan='4' align='left' class='Conteudo'>O pedido da Loja Virtual não será enviado para a Fábrica até que seja <B>FECHADO</b>. </td>";				
			}

			$sql ="	SELECT	valor_pedido_minimo,
					valor_pedido_minimo_capital
					FROM tbl_fabrica
					WHERE fabrica = $login_fabrica";
			$res = pg_exec ($con,$sql);
			if (pg_numrows ($res) > 0) {
				$valor_pedido_minimo        = pg_result ($res,0,valor_pedido_minimo);
				$valor_pedido_minimo_capital = pg_result ($res,0,valor_pedido_minimo_capital);
			}

			$sql ="	SELECT capital_interior
					FROM tbl_posto
					WHERE posto = $login_posto;";
			$res = pg_exec ($con,$sql);
			if (pg_numrows ($res) > 0) {
				$capital_interior = pg_result ($res,0,capital_interior);
			}

			$valor_minimo = 0;

			if ($capital_interior == 'CAPITAL') {
				$valor_minimo = $valor_pedido_minimo_capital;
			}

			if ($capital_interior == 'INTERIOR'){
				$valor_minimo = $valor_pedido_minimo;
			}

			if ($valor_minimo == 0) {
				$valor_minimo = $valor_pedido_minimo;
			}

			

			if($valor_minimo > 0){
				$valor_minimo = number_format($valor_minimo,2,'.',',');
				echo "</tr>";
				echo "<tr><td colspan='4' align='left' class='Conteudo' style='color: red; font-style: bold;'><p>O valor minimo para fechar o carrinho é de R$".$valor_minimo."</p></td></tr>";	
			}
			
		}

		echo "</table>";
		echo "<br>";
	}
}

/*PEDIDOS NAO FINALIZADOS*/

echo "</td>";
echo "</tr>";

	echo "<tr>";
	echo "<td colspan='6' align='right' class='Conteudo'><BR>";

	if($produto_acabado=="t" AND ($login_fabrica==3 OR $login_fabrica == 85)){
		$xproduto_acabado = "?produto_acabado=t";
		$produto_acabado_limpa = "&produto_acabado=t";
	}

	echo "<a href='lv_completa.php$xproduto_acabado'><img src='imagens/continuar_comprando.png' border='0'></a>";
	echo "<a href=\"javascript:if (confirm('Deseja limpar o seu carrinho de compras?')) window.location='$PHP_SELF?acao=limpa$produto_acabado_limpa'\"><img src='imagens/limpar_carrinho.png' border='0'></a>";
	if ($login_fabrica==10){
		echo "<a href=\"javascript:if (confirm('Deseja finalizar este pedido?')) window.location = '$PHP_SELF?btn_acao=fechar_pedido'\" value='Fechar Pedido'><img src='imagens/fechar_pedido.png' border='0'></a>";
	}else if($login_fabrica==3 AND $produto_acabado=="t"){
		echo "<a href='acordo_compra.php?produto_acabado=t'><img src='imagens/fechar_pedido.png' border='0'></a>";
	}else{
		echo "<a href=\"javascript:if (confirm('Deseja finalizar este pedido? Este pedido será enviado para a Fábrica.')) window.location = '$PHP_SELF?btn_acao=fechar_pedido$produto_acabado_limpa'\" value='Fechar Pedido'><img src='imagens/fechar_pedido.png' border='0'></a>";
	}

	echo "<div class='lcar_title'>";		
		echo "<table width='100%' border='0' align='center' cellspacing='0' cellpadding='10' id='tabela_produtos' class='base'>";

		//cabeca
		echo "<thead id='title'>";
		#echo "<tr class='title'>"; bgcolor='#0082d7'
			echo "<th id='esquerda' width='20' width='25' align='center'>&nbsp;&nbsp;</th>";
			echo "<th height='30' align='left' colspan='2'>Peça</th>";
			echo "<th height='30' align='center'>Qtde</th>";
			echo "<th height='30' align='right'>Valor Unit.</th>";
			echo "<th height='30' align='center'>IPI</th>";
			echo "<th id='direita' height='30' align='right'>Valor Total </th>";
		#echo "</tr>";
		echo "</thead>";
		//fim cabeca


		$sql = "SELECT  DISTINCT
					tbl_pedido.pedido           ,
					tbl_pedido.linha            ,
					tbl_linha.nome as linha_desc,
					tbl_pedido.condicao         ,
					tbl_pedido.total
			FROM  tbl_pedido
			JOIN  tbl_pedido_item USING (pedido)
			JOIN  tbl_peca        USING (peca)
			LEFT JOIN tbl_linha USING(linha)
			WHERE tbl_pedido.posto   = $login_posto
			AND   tbl_pedido.fabrica = $login_fabrica
			AND   tbl_pedido.pedido_loja_virtual IS TRUE
			AND   tbl_pedido.exportado           IS NULL
			AND   tbl_pedido.finalizado          IS NULL
			ORDER BY tbl_pedido.pedido DESC";
		#echo nl2br($sql);
		$res = pg_exec ($con,$sql);
		$qtde_itens = pg_numrows($res);

		if ( $qtde_itens > 0) {
			echo "<tbody>";
			for ($i=0;$i<pg_numrows($res);$i++){
				$pedido      = trim(pg_result($res,$i,pedido));
				$linha       = trim(pg_result($res,$i,linha));
				$condicao    = trim(pg_result($res,$i,condicao));
				$linha_desc  = trim(pg_result($res,$i,linha_desc));
				$total       = trim(pg_result($res,$i,total));
				$soma = imprimir_pedido_item($con,$login_fabrica,$login_posto,$status,$pedido);

				//HD 115256
				if($login_fabrica==3 OR $login_fabrica == 85){
					$xtotal   = $xtotal + $total;
					$xxtotal  = number_format($xtotal, 2, ',', '');
					$xxxtotal = str_replace(".",",",$xxtotal);
					$retorno  = "Sub Total: <font color='#FF0033'>R$ $xxxtotal</font> <br>";
				}else{
					$xsoma = number_format($soma, 2, ',', '');
					$xsoma = str_replace(".",",",$xsoma);
					$retorno .="<b>Sub Total: <font color='#FF0033'>R$ $xsoma</font></b><br>";
				}
			}
			echo "</tbody>";
			echo "<tfoot>";


			if (strlen($login_unico)==0 AND $login_fabrica<>10  AND $login_fabrica <> 1 AND $login_fabrica<>3 AND $login_fabrica<>35 AND $login_fabrica <> 85){
				echo "<tr>";
				echo "<td colspan='7'>";
				//echo "<a  onClick='MostraEsconde(\"dados_1\");'><img src='imagens/adiciona_carrinho.png' border='0'></a><BR>";

				echo "<form action='".$PHP_SELF."?acao=adicionar' method='post' name='frmcarrinho' align='center'>";
				//echo "<div id='dados_1' style='position:relative; display:none; border: 1px solid #949494;background-color: #f4f4f4; padding:2px'>";
				echo "<input type='hidden' name='cod_produto' id='cod_produto' value='$cod_produto'>";
				echo "<input type='hidden' name='ipi'         id='ipi'         value=''>";
				echo "<input type='hidden' name='qtde_maxi'   id='qtde_maxi'   value=''>";
				echo "<input type='hidden' name='qtde_disp'   id='qtde_disp'   value=''>";
				echo "<input type='hidden' name='qtde_min'   id='qtde_min'   value=''>";
				echo "&nbsp;&nbsp;<font size='1'><b>Adicionar peça ao carrinho de compra</b></font><BR>";
				echo "&nbsp;&nbsp;<font size='1'>Referência:</font>";
				echo "<input type='text' class='frm' name='referencia_peca' id='referencia_peca' value='$referencia_para' size='10' maxlength='20' >";
				echo "&nbsp;&nbsp;<font size='1'>Descrição:</font>";
				echo "<input type='text' class='frm' name='descricao_peca' id='descricao_peca' value='$descricao_para' size='35' maxlength='50' >";
				echo "&nbsp;&nbsp;<font size='1'>Qtde:</font>";
				echo "<input type='text' size='2' maxlength='3' name='qtde' id='qtde' value=''";

					echo "onblur=\"javascript:
					checarNumero(this);
					if (this.value=='') return;
					if (this.form.qtde_disp.value!='' && this.form.qtde_maxi.value!=''){
						if (parseInt(this.value) < parseInt(this.form.qtde_min.value) || this.value=='' ) {
							alert('Quantidade abaixo da mínima permitida. A quantidade mínima para compra desta peça é de '+this.form.qtde_min.value+'!');
							this.value=this.form.qtde_min.value;
						}
						if (parseInt(this.value) > parseInt(this.form.qtde_maxi.value) || this.value=='' ) {
							alert('Quantidade acima da máxima permitida. A quantidade máxima para compra desta peça é de '+this.form.qtde_maxi.value+'!');
							this.value=this.form.qtde_maxi.value;
						}
					}
					\"";

				echo ">";
				echo "&nbsp;<font size='1'>Valor:</font>";
				echo "<input type='text' name='valor' size='5'  id='valor' value='' readonly > &nbsp;";
				echo "<input type='button' name='btn_comprar' id='btn_comprar' value='Comprar' class='botao' onClick='adicionarProduto(this.form)'>";
				echo "</div>";
				echo "</form>";

				echo "</td>";
				echo "</tr>";
			}
		}else{
				echo "<tbody>";
				echo "<tr class='Conteudo'>";
				echo "<td  bgcolor='#FFFFFF' colspan='6' align='center'><br>Carrinho vazio<br><br></td>";
				echo "</tr>";
				echo "</tbody>";

		}
		//TOTAL
		echo "<tr>";
		echo "<td  colspan='7' height='30' align='right' nowrap><font size='2'><span id='total_pedido'><B>$retorno</B></span></font>";
		echo "</tr>";
		//TOTAL

		echo "<tr>";
			echo "<td colspan='7' height='30' align='right' nowrap>";
				if(strlen($pedido)>0){
					$sqlf = "SELECT sum(peso) as peso_total,
							total,
							case when length(cep) > 0 then cep else contato_cep end as cep
						from tbl_pedido
						JOIN tbl_pedido_item using(pedido)
						JOIN tbl_posto ON tbl_pedido.posto = tbl_posto.posto
						JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_pedido.posto and tbl_posto_fabrica.fabrica = tbl_pedido.fabrica
						JOIN tbl_peca ON tbl_peca.peca = tbl_pedido_item.peca
						WHERE tbl_pedido.posto   = $login_posto
						AND   tbl_pedido.fabrica = $login_fabrica
						AND   tbl_pedido.pedido  = $pedido
						AND   tbl_peca.frete_gratis IS NOT TRUE
						GROUP BY total,contato_cep,cep ";

					#echo nl2br($sqlf);
					$resf=pg_exec($con,$sqlf) ;
					if(pg_numrows($resf)>0){
						$peso_total = pg_result($resf,0,peso_total);
						$total      = pg_result($resf,0,total);
						$cep        = pg_result($resf,0,cep);
						$cep_origem = "17519255";
					}
				}
				if(strlen($cep) > 0){
					$valor_frete = calcula_frete($cep_origem,$cep,$peso_total);
					$xvalor_frete = number_format($valor_frete, 2, ",",".");
					$retorno ="Total Frete(Sedex): <font color='#FF0033'>R$ $xvalor_frete</font> <br>";
					echo "<font size='2'><span id='total_pedido'><B>$retorno</B></span></font>";
				}
			echo "</td>";
		echo "</tr>";
		echo "<tr>";
			echo "<td  colspan='7' height='30' align='right' nowrap><font size='2'>";
				if($login_fabrica==3 OR $login_fabrica == 85){
					$xxxtotal           = str_replace(",",".",$xxxtotal);
					$total_pedido_frete = $valor_frete + $xxxtotal;
					$total_pedido_frete = number_format($total_pedido_frete, 2, ",",".");
				}else{
					$total_pedido_frete = $valor_frete + $soma;
					$total_pedido_frete = number_format($total_pedido_frete, 2, ",",".");
				}
				echo "<font size='2'><span id='total_pedido'><B>Total Pedido: <font color='#FF0033'>R$ $total_pedido_frete</font></B></span></font>";
			echo "</td>";
		echo "</tr>";

		echo "</tfoot>";

		echo "</table>";
		echo "</div>";

	echo "</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td>";
	if(strlen($descricao_forma_pagamento)>0) echo "<font color='#777777'>$descricao_forma_pagamento</font><br><br>";
	if($login_fabrica==1){
		echo "<b>Condição de Pagamento:</b>";
		echo "<select name='condicao' class='frm' onChange='window.location=\"$PHP_SELF?pedido=$pedido&condicao=\"+this.value'>";
		echo "<option value=''></option>";
		$sql = "SELECT  tbl_black_posto_condicao.id_condicao            ,
						tbl_condicao.descricao               AS condicao
				FROM tbl_black_posto_condicao
				JOIN tbl_condicao ON tbl_condicao.condicao = tbl_black_posto_condicao.id_condicao
				WHERE posto = $login_posto
				AND tbl_condicao.fabrica = $login_fabrica
				AND promocao IS NOT TRUE";

		$res = pg_exec ($con,$sql);
		if (pg_numrows($res) > 0) {
			for ($x=0; $x < pg_numrows($res); $x++) {
				echo "<option "; if ($condicao == pg_result($res,$x,id_condicao)) echo " SELECTED "; echo " value='" . pg_result($res,$x,id_condicao) . "'>" . pg_result($res,$x,condicao) . "</option>\n";
			}
		}
		else {
			echo "<option value='51'>30DD (sem financeiro)</option>";
		}
		echo "</select>";
	}
	echo "</td>";
	echo "</tr>";
	echo "</table>";
echo "</td>";
echo "</tr>";

echo "</table>";

if (strlen($cook_fabrica)==0 AND ( strlen($cook_login_unico)>0 OR strlen($cook_login_simples)>0)) {
	include "login_unico_rodape.php";
}else{
	include "rodape.php";
}

?>
