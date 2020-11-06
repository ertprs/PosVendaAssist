<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

/* Variaveis para Faturar */
$Faturar  = $_POST['Faturar'];
$embarque = $_POST['embarque'];


if (strlen ($Faturar) == 0) {
	$Faturar  = $_GET['Faturar'];
}
if (strlen ($embarque) == 0) {
	$embarque = $_GET['embarque'];
}



/* Variaveis do AJAX*/
$embarque_array = $_GET['embarque_array'];

if (strlen ($embarque_array) > 0) {
	if ($embarque_array{strlen ($embarque_array)-1}==','){
		$embarque_array = substr ($embarque_array,0,strlen ($embarque_array)-1);
	}
	$embarques      = explode (",",$embarque_array);
	$qtde_embarques = count ($embarques) ;
}

/* Variaveis para Importar */
$Importar            = $_POST['Importar'];
if (strlen($Importar)==0) {
	$Importar  = $_GET['Importar'];
}
$embarques_importar  = $_POST['embarques_importar'];
if (strlen($embarques_importar)==0) {
	$embarques_importar  = $_GET['embarques_importar'];
}


/* Importar Notas Avulsas */
$nota_fiscal = $_POST['nota_fiscal'];
if (strlen($nota_fiscal)==0){
	$nota_fiscal    = $_GET['nota_fiscal'];
}

$copia_nota_fiscal = $nota_fiscal;

if (strlen ($nota_fiscal) > 0) {
	$qtde_embarques = 0;
	$Importar  = "1";
}


$msg_erro = "";

if ($Faturar=='1' AND strlen($embarque)>0) {


	$sql = "SELECT * FROM tbl_embarque WHERE embarque = $embarque AND faturar IS NULL";
	$res = pg_exec ($con,$sql);
	if (pg_numrows ($res) == 1) {

		$resX = pg_exec ($con,"BEGIN TRANSACTION");
		$posto = pg_result ($res,0,posto);

		$tipos_pedidos[0] = "76, 77, 2, 116, 131, 153";   // tipo_pedido de venda
		$tipos_pedidos[1] = "3, 115, 132, 154";           // tipo_pedido garantia


		#------------ Totaliza Embarque ----------------- #
		$sql = "SELECT SUM (tbl_embarque_item.qtde * tbl_tabela_item.preco * (1 + (tbl_peca.ipi / 100))) 
				FROM tbl_embarque_item 
				JOIN tbl_peca ON tbl_embarque_item.peca = tbl_peca.peca 
				JOIN tbl_pedido_item ON tbl_embarque_item.pedido_item = tbl_pedido_item.pedido_item 
				JOIN tbl_pedido ON tbl_pedido_item.pedido = tbl_pedido.pedido 
				JOIN tbl_tabela_item ON tbl_tabela_item.tabela = tbl_pedido.tabela AND tbl_tabela_item.peca = tbl_embarque_item.peca 
				WHERE tbl_embarque_item.embarque = $embarque 
				AND   tbl_pedido.tipo_pedido IN (" . $tipos_pedidos[0] . ") ";
		$res = pg_exec ($con,$sql);
		$total_faturado = pg_result ($res,0,0);
		$msg_erro .= pg_errormessage($con);

		$sql = "SELECT SUM (tbl_embarque_item.qtde * tbl_tabela_item.preco * (1 + (tbl_peca.ipi / 100))) 
				FROM tbl_embarque_item 
				JOIN tbl_peca ON tbl_embarque_item.peca = tbl_peca.peca 
				JOIN tbl_pedido_item ON tbl_embarque_item.pedido_item = tbl_pedido_item.pedido_item 
				JOIN tbl_pedido ON tbl_pedido_item.pedido = tbl_pedido.pedido 
				JOIN tbl_tabela_item ON tbl_tabela_item.tabela = tbl_pedido.tabela AND tbl_tabela_item.peca = tbl_embarque_item.peca 
				WHERE tbl_embarque_item.embarque = $embarque 
				AND   tbl_pedido.tipo_pedido IN (" . $tipos_pedidos[1] . ") ";
		$res = pg_exec ($con,$sql);
		$total_garantia = pg_result ($res,0,0);
		$msg_erro .= pg_errormessage($con);

		$total_embarque = $total_faturado + $total_garantia;

/*
		$sql = "SELECT DISTINCT tbl_pedido.tipo_pedido 
				FROM tbl_embarque 
				JOIN tbl_embarque_item USING (embarque) 
				JOIN tbl_pedido_item USING (pedido_item) 
				JOIN tbl_pedido USING (pedido) 
				WHERE tbl_embarque.embarque = $embarque 
				AND tbl_embarque.posto = $posto 
				AND tbl_embarque.distribuidor = $login_posto";
		$resTipo = pg_exec ($con,$sql);
		$msg_erro .= pg_errormessage($con);
		if (pg_numrows ($resTipo)>0){
			$tipo_pedido = pg_result ($resTipo,0,tipo_pedido);
		}



		$sql = "SELECT	DISTINCT 
						tbl_pedido.fabrica,
						tbl_pedido.tipo_pedido , 
						UPPER(trim(tbl_tipo_pedido.descricao)) AS tipo_pedido_descricao
				FROM tbl_embarque 
				JOIN tbl_embarque_item USING (embarque) 
				JOIN tbl_pedido_item   USING (pedido_item) 
				JOIN tbl_pedido        USING (pedido) 
				JOIN tbl_tipo_pedido   USING (tipo_pedido)
				WHERE tbl_embarque.embarque     = $embarque 
				AND   tbl_embarque.posto        = $posto 
				AND   tbl_embarque.distribuidor = $login_posto";
		$resTipo = pg_exec ($con,$sql);
		$msg_erro .= pg_errormessage($con);
*/

		if ($login_posto == 4311 AND $tipo_pedido==3) {
			$total_garantia = $total_garantia / 3 ;
		}

		
		for ($t = 0 ; $t < 2 ; $t++) {

			$tipo_pedido = $tipos_pedidos[$t];
			if ($t == 0) {
				$tipo_pedido_descricao = "VENDA";
				$tipo_pedido_nf = "76";
			}else{
				$tipo_pedido_descricao = "GARANTIA";
				$tipo_pedido_nf = "158";
			}

			#----------- Busca valor do frete -------------#
			$sql = "SELECT * FROM tbl_embarque WHERE embarque = $embarque AND faturar IS NULL";
			$resEmb = pg_exec ($con,$sql);
			$transportadora = pg_result ($resEmb,0,transportadora);
			$qtde_volume    = pg_result ($resEmb,0,qtde_volume);
			$valor_frete    = pg_result ($resEmb,0,total_frete);
			# Não cobrar mais frete do 
			# II ROSS - 1031
			# Adriane Teixeira - 580
			# Gaslar - 1008
			# Nakayone - 19571
			# Refrigeracao Moraes - 1373

			if ($posto == 1031 OR $posto == 580 OR $posto == 1008 OR $posto == 1385 OR $posto == 19571 OR $posto == 1373 ){
				$valor_frete = 0;
			}


			#----------- ICMS de outros estados -------------#
			$sql = "SELECT estado FROM tbl_posto WHERE posto = $posto";
			$res = pg_exec ($con,$sql);
			$estado = pg_result ($res,0,0);

			/* FATURADO */
			if ($tipo_pedido_descricao == 'VENDA') {

				/* CFOP e ICMS PADRAO para TODOS os estados MENOS SP,MG,PR,RJ,RS,SC */
				$cfop      = "6102" ;
				$natureza  = "Venda Mercantil";
				$aliq_icms = 7 ;
				$aliq_ipi  = 0;
				$aliq_reducao = 0;

				$condicao = 33 ; // Condicao de pagamento unica - Tulio 12/02/2010

				if ( $estado == "SP" ) {
					$aliq_icms = 18 ;
					$cfop      = "5102" ;
				}

				if ($estado == "MG" OR $estado == "PR" OR $estado == "RJ" OR $estado == "RS" OR $estado == "SC") {
					$aliq_icms = 12 ;
					$cfop      = "6102" ;
				}

			}

			/* GARANTIA */
			if ($tipo_pedido_descricao == 'GARANTIA') {

				/* CFOP e ICMS PADRAO para TODOS os estados MENOS SP,MG,PR,RJ,RS,SC */
				$cfop = "6949";
				$natureza = "Remessa em Garantia";
				$aliq_icms = 7;
				$aliq_ipi  = 0;
				$aliq_reducao = 0;

				$condicao = 1400;       // Condicao de pagamento unica - Tulio 12/02/2010

				if ( $estado == "SP" ) {
					$aliq_icms = 18 ;
					$cfop      = "5949" ;
				}

				if ($estado == "MG" OR $estado == "PR" OR $estado == "RJ" OR $estado == "RS" OR $estado == "SC") {
					$aliq_icms = 12 ;
					$cfop      = "6949" ;
				}
			}


			#------------ Totaliza Nota ----------------- #
			$sql = "SELECT SUM (tbl_embarque_item.qtde * tbl_tabela_item.preco * (1 + (tbl_peca.ipi / 100)) )
					FROM tbl_embarque_item 
					JOIN tbl_peca ON tbl_embarque_item.peca = tbl_peca.peca 
					JOIN tbl_pedido_item ON tbl_embarque_item.pedido_item = tbl_pedido_item.pedido_item 
					JOIN tbl_pedido ON tbl_pedido_item.pedido = tbl_pedido.pedido 
					JOIN tbl_tabela_item ON tbl_tabela_item.tabela = tbl_pedido.tabela AND tbl_tabela_item.peca = tbl_embarque_item.peca 
					WHERE tbl_embarque_item.embarque = $embarque 
					AND   tbl_pedido.tipo_pedido IN (" . $tipo_pedido . ")";

			$res = pg_exec ($con,$sql);
			$msg_erro .= pg_errormessage($con);
			if (pg_numrows ($res) == 0) continue;
			$total_nota = pg_result ($res,0,0);
			if ($total_nota == 0) continue;

			if ($login_posto == 4311 AND $tipo_pedido_descricao == 'GARANTIA') {
				$total_nota = $total_nota / 3 ;
			}
			$total_nota = number_format ($total_nota,2,".","");

			$tabela = 30;   // tabela fixa para Telecontrol - Tulio 12/02/2010

			$base_icms   = $total_nota ;
			$valor_icms  = $total_nota * $aliq_icms / 100 ;
			$valor_icms  = number_format ($valor_icms,2,".","");
			$base_ipi    = 0;
			$valor_ipi   = 0;

			if ($tipo_pedido_descricao == 'GARANTIA') {
				$valor_frete = 0 ;
			}else{
				$valor_frete = $valor_frete / $total_embarque * $total_nota;

/*
echo "<br>";
echo "Frete -> " . $valor_frete ;
echo "<br>";
echo "Embarque -> " . $total_embarque ;
echo "<br>";
echo "NF - > " . $total_nota ;
exit;
*/

				$valor_frete = number_format ($valor_frete,2,".","");
				$total_nota  += $valor_frete ;
			}

			$total_nota = number_format ($total_nota,2,".","");

			$sql = "SELECT MAX (nota_fiscal::integer) AS nota_fiscal FROM tbl_faturamento WHERE distribuidor = $login_posto ";
			$res = pg_exec ($con,$sql);
			$msg_erro .= pg_errormessage($con);
			$nota_fiscal = pg_result ($res,0,0);

			if (strlen ($nota_fiscal) == 0) {
				$nota_fiscal = "000000";
			}

			$nota_fiscal = $nota_fiscal + 1 ;
			$nota_fiscal = "000000" . $nota_fiscal;
			$nota_fiscal = substr ($nota_fiscal,strlen ($nota_fiscal)-6);

			$msg_erro = "";

			$sql = "INSERT INTO tbl_faturamento (
					fabrica, 
					distribuidor, 
					qtde_volume, 
					valor_frete, 
					tipo_frete,
					tipo_pedido,
					embarque,
					natureza,
					cfop,
					condicao,
					transportadora,
					posto,
					emissao,
					saida,
					total_nota,
					base_icms,
					valor_icms,
					base_ipi,
					valor_ipi,
					nota_fiscal,
					serie,
					transp,
					tabela
					) VALUES (
					10,
					$login_posto,
					$qtde_volume,
					$valor_frete,
					'1',
					$tipo_pedido_nf,
					$embarque,
					'$natureza',
					'$cfop',
					$condicao,
					$transportadora,
					$posto,
					current_date,
					current_date,
					$total_nota,
					$base_icms,
					$valor_icms,
					$base_ipi,
					$valor_ipi,
					'$nota_fiscal',
					'2',
					(SELECT fantasia FROM tbl_transportadora WHERE transportadora = $transportadora),
					$tabela
					)";
			$res = pg_exec ($con,$sql);
			if (strlen (pg_errormessage ($con)) > 0) {
				$msg_erro .= pg_errormessage ($con);
				echo "erro|$msg_erro";
				exit;
			}

			$res = pg_exec ($con,"SELECT CURRVAL ('seq_faturamento')");
			$faturamento = pg_result ($res,0,0);

			$sql = "SELECT tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao, tbl_peca.ipi, CASE WHEN tbl_tabela_item.preco IS NOT NULL THEN tbl_tabela_item.preco ELSE tbl_pedido_item.preco END AS preco, tbl_embarque_item.qtde, tbl_pedido.pedido, tbl_embarque_item.pedido_item, tbl_embarque_item.os_item, tbl_os.os , tbl_pedido_item.preco AS preco_pedido
					FROM   tbl_peca
					JOIN   tbl_embarque_item    USING (peca)
					JOIN   tbl_embarque         USING (embarque)
					JOIN   tbl_pedido_item      USING (pedido_item)
					JOIN   tbl_pedido           ON tbl_pedido_item.pedido = tbl_pedido.pedido
					LEFT JOIN tbl_posto_linha   ON tbl_pedido.posto = tbl_posto_linha.posto AND tbl_pedido.linha = tbl_posto_linha.linha
					LEFT JOIN tbl_tabela_item   ON tbl_posto_linha.tabela = tbl_tabela_item.tabela AND tbl_tabela_item.peca = tbl_embarque_item.peca
					LEFT JOIN tbl_os_item       ON tbl_embarque_item.os_item = tbl_os_item.os_item
					LEFT JOIN tbl_os_produto    ON tbl_os_item.os_produto = tbl_os_produto.os_produto
					LEFT JOIN tbl_os            ON tbl_os_produto.os = tbl_os.os
					WHERE  tbl_pedido.tipo_pedido    IN ($tipo_pedido)
					AND    tbl_embarque.embarque     = $embarque
					AND    tbl_embarque.distribuidor = $login_posto
					AND    tbl_embarque.posto        = $posto
					ORDER BY tbl_peca.referencia";
			$resPeca = pg_exec ($con,$sql);
			$msg_erro .= pg_errormessage ($con);

			for ($p = 0 ; $p < pg_numrows ($resPeca) ; $p++) {

				$peca        = pg_result ($resPeca,$p,peca);
				$preco       = pg_result ($resPeca,$p,preco);
				$ipi         = pg_result ($resPeca,$p,ipi);
				$qtde        = pg_result ($resPeca,$p,qtde);
				$pedido_item = pg_result ($resPeca,$p,pedido_item);
				$pedido      = pg_result ($resPeca,$p,pedido);
				$os_item     = pg_result ($resPeca,$p,os_item);
				$os          = pg_result ($resPeca,$p,os);
				
				$preco = $preco * (1 + ($ipi / 100));

#				if ($login_posto == 4311 AND $tipo_pedido_descricao == 'GARANTIA') {
#					$preco = $preco / 3 ;
#				}

				$preco = number_format ($preco,2,".","");

				if (strlen ($os_item) == 0) $os_item = "null";
				if (strlen ($os) == 0)      $os      = "null";

				$sql = "INSERT INTO tbl_faturamento_item (
						faturamento,
						peca,
						qtde,
						aliq_icms,
						aliq_ipi,
						aliq_reducao,
						preco,
						pedido,
						os,
						os_item,
						situacao_tributaria
						) VALUES (
						$faturamento,
						$peca,
						$qtde,
						$aliq_icms,
						$aliq_ipi,
						$aliq_reducao,
						$preco,
						$pedido,
						$os,
						$os_item,
						'00')";
				$res = pg_exec ($con,$sql);
				if (strlen (pg_errormessage ($con)) > 0) {
					$msg_erro .= pg_errormessage ($con);
					echo "erro|$msg_erro";
					exit;
				}
			}

			$sql = "SELECT SUM (qtde * preco) FROM tbl_faturamento_item WHERE faturamento = $faturamento";
			$res = pg_exec ($con,$sql);
			$base_icms = pg_result ($res,0,0);
			$valor_icms = $base_icms * $aliq_icms / 100;
			$valor_icms = number_format ($valor_icms,2,".","");
			$base_ipi   = 0;
			$valor_ipi  = 0;
			$total_nota = number_format ($base_icms + $valor_frete,2,".","");

			if ($tipo_pedido_descricao == 'GARANTIA') {
				$base_icms   = 0;
				$valor_icms  = 0;
				$base_ipi    = 0;
				$valor_ipi   = 0;
			}

			$sql = "UPDATE tbl_faturamento SET total_nota = $total_nota , base_icms = $base_icms, valor_icms = $valor_icms, base_ipi = $base_ipi, valor_ipi = $valor_ipi WHERE faturamento = $faturamento";
			$res = pg_exec ($con,$sql);
			$msg_erro .= pg_errormessage ($con);

			$qtde_volume = 0 ;
		}

		$res = pg_exec ($con,"SELECT fn_fatura_embarque ($embarque)");
		$msg_erro .= pg_errormessage ($con);

		if (strlen ($msg_erro) == 0) {
			$res = pg_exec ($con,"COMMIT TRANSACTION");
		}else{
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		}
	}else{
		$msg_erro .= "O embarque $embarque já está faturado ou não foi encontrado."; 
	}
	if (strlen ($msg_erro) == 0) {
		echo "ok|Embarque faturado com sucesso";
	}else{
		echo "erro|Ocorreu um erro ao faturar: $msg_erro ";
	}
	exit;
} 


if ($Importar=='1' AND (strlen($embarques_importar)>0 OR strlen ($copia_nota_fiscal) > 0)){

	if (strlen ($copia_nota_fiscal) > 0) {
		$sql = "SELECT * FROM tbl_faturamento WHERE nota_fiscal  = '$copia_nota_fiscal' AND distribuidor = $login_posto AND fabrica IN (10, 3,25,45,51, 81)";
		#$sql = "SELECT * FROM tbl_faturamento WHERE nota_fiscal  BETWEEN '004678' AND '004691' AND distribuidor = $login_posto AND fabrica = $login_fabrica AND faturamento > 491279" ;
		#$sql = "SELECT * FROM tbl_faturamento WHERE nota_fiscal > '$copia_nota_fiscal' AND distribuidor = $login_posto AND fabrica = $login_fabrica";
	}else{
		$sql = "SELECT * FROM tbl_faturamento WHERE embarque IN ($embarques_importar) AND distribuidor = $login_posto ORDER BY nota_fiscal " ;
	}

	$resNF = pg_exec ($con,$sql);

	$arquivo_nf = "";

	for ($nf = 0 ; $nf < pg_numrows ($resNF) ; $nf++) {

		$faturamento = pg_result ($resNF,$nf,faturamento);
		$posto       = pg_result ($resNF,$nf,posto);
		$embarque    = pg_result ($resNF,$nf,embarque);
	
		// Nao adianta tentar pegar endereco do Posto_fabrica para efeitos da distribuicao
		// temos que manter tbl_posto atualizada para Salton e Gama
		// tulio 12/02/2010
		$sql = "SELECT  tbl_posto.posto, 
						tbl_posto.nome, 
						tbl_posto.cnpj, 
						tbl_posto.ie, 
						tbl_posto.endereco, 
						tbl_posto.numero, 
						tbl_posto.bairro, 
						tbl_posto.complemento, 
						tbl_posto.cep, 
						tbl_posto.cidade, 
						tbl_posto.estado, 
						tbl_posto.fone 
				FROM tbl_posto WHERE tbl_posto.posto = $posto";
		$resPosto = pg_exec ($con,$sql);

		#------------------ Gera arquivo texto -----------------#

		$tipo_pedido = pg_result ($resNF,$nf,tipo_pedido);

		$arquivo_nf .= "\n\n";
		$arquivo_nf .= "*** HEADER ***";
		$arquivo_nf .= "\nNota Fiscal # " . pg_result ($resNF,$nf,nota_fiscal) ;
		$arquivo_nf .= "\n";

		$nome = trim (pg_result ($resPosto,0,nome));
		$nome = "($embarque) " . $nome ;

		$endereco = trim (pg_result ($resPosto,0,endereco));
		$numero   = trim (pg_result ($resPosto,0,numero));
		if (strlen ($numero) > 0) $endereco .= " , n. " . $numero;

		$endereco = substr (sprintf ("%-50s",trim ($endereco)),0,50);

		$arquivo_nf .= substr (sprintf ("%06d" ,trim (pg_result ($resPosto,0,posto))),0,6);
		$arquivo_nf .= substr (sprintf ("%-40s",$nome),0,40);
		$arquivo_nf .= substr (sprintf ("%-14s",trim (pg_result ($resPosto,0,cnpj))),0,14);
		$arquivo_nf .= substr (sprintf ("%-20s",trim (pg_result ($resPosto,0,ie))),0,20);
		$arquivo_nf .= substr (sprintf ("%-50s",$endereco),0,50);
		$arquivo_nf .= "          " ;  # numero
		$arquivo_nf .= substr (sprintf ("%-20s",trim (pg_result ($resPosto,0,complemento))),0,20);
		$arquivo_nf .= substr (sprintf ("%-30s",trim (pg_result ($resPosto,0,bairro))),0,30);
		$arquivo_nf .= substr (sprintf ("%-30s",trim (pg_result ($resPosto,0,cidade))),0,30);
		$arquivo_nf .= substr (sprintf ("%-02s",trim (pg_result ($resPosto,0,estado))),0,2);
		$arquivo_nf .= substr (sprintf ("%-08s",trim (pg_result ($resPosto,0,cep))),0,8);
		$arquivo_nf .= substr (sprintf ("%-15s",trim (pg_result ($resPosto,0,fone))),0,15);

		
		$emissao = pg_result ($resNF,$nf,emissao);
		$emissao = substr ($emissao,8,2) . "/" . substr ($emissao,5,2) . "/" . substr ($emissao,0,4) ;
		$arquivo_nf .= $emissao ;

		$arquivo_nf .= substr (sprintf ("%-10s",trim (pg_result ($resNF,$nf,cfop))),0,10);
		$arquivo_nf .= substr (sprintf ("%-25s",trim (pg_result ($resNF,$nf,natureza))),0,25);
		
		$arquivo_nf .= "\n*** DETALHE *** \n" ;
		
		$faturamento = pg_result ($resNF,$nf,faturamento);

		$sql = "SELECT tbl_peca.referencia, tbl_peca.descricao, tbl_faturamento_item.aliq_ipi, tbl_faturamento_item.aliq_icms, tbl_faturamento_item.preco, SUM (tbl_faturamento_item.qtde) AS qtde
				FROM   tbl_peca
				JOIN   tbl_faturamento_item USING (peca)
				WHERE  tbl_faturamento_item.faturamento = $faturamento
				GROUP BY tbl_peca.referencia, tbl_peca.descricao, tbl_faturamento_item.aliq_ipi, tbl_faturamento_item.aliq_icms, tbl_faturamento_item.preco
				ORDER BY tbl_peca.referencia";
		$resPeca = pg_exec ($con,$sql);

		for ($p = 0 ; $p < pg_numrows ($resPeca) ; $p++) {
			$arquivo_nf .= substr (sprintf ("%-10s" ,trim (pg_result ($resPeca,$p,referencia))),0,10);
			$arquivo_nf .= substr (sprintf ("%-40s",trim (pg_result ($resPeca,$p,descricao))),0,40);
			$arquivo_nf .= substr (sprintf ("%06d" ,trim (pg_result ($resPeca,$p,qtde))),0,6);

			$preco = pg_result ($resPeca,$p,preco) ;
			if (pg_result ($resNF,$nf,tipo_pedido) <> "101" and pg_result ($resNF,$nf,tipo_pedido) <> "105") {
				$preco = $preco * ( 1 + (pg_result ($resPeca,$p,aliq_ipi) /100) );
			}
			$preco = number_format ($preco,2,".","");

			$arquivo_nf .= substr (sprintf ("%012.2f" ,trim ($preco)),0,12);

			$arquivo_nf .= substr (sprintf ("%02d" ,pg_result ($resPeca,$p,aliq_icms)),0,2);
			$arquivo_nf .= substr (sprintf ("%02d" ,pg_result ($resPeca,$p,aliq_ipi)),0,2);
			$arquivo_nf .= "PC";

			$arquivo_nf .= "\n";
		}

		#------ 8 linhas de mensagens com 90 caracteres #
		$arquivo_nf .= "\n*** MENSAGEM *** \n";
		$arquivo_nf .= "                                                                                          ";
		$arquivo_nf .= "\n";
		$arquivo_nf .= "                                                                                          ";
		$arquivo_nf .= "\n";
		$arquivo_nf .= "                                                                                          ";

	// campos novos
		$arquivo_nf .= "\n";
		$arquivo_nf .= "                                                                                          ";
		$arquivo_nf .= "\n";
		$arquivo_nf .= "                                                                                          ";
		$arquivo_nf .= "\n";
		$arquivo_nf .= "                                                                                          ";
		$arquivo_nf .= "\n";
		$arquivo_nf .= "                                                                                          ";
		$arquivo_nf .= "\n";
		$arquivo_nf .= "                                                                                          ";

		$arquivo_nf .= "\n";

		$arquivo_nf .= "\n*** TRAILLER *** \n";

		#- Desconto -#
		$arquivo_nf .= "000000000.00";

		#- Despesas Acessorias -#
		$arquivo_nf .= "000000000.00";

		#- Cond. PG -#
		$condicao_pg = "           ";
		$tipo_pedido = pg_result ($resNF,$nf,tipo_pedido);
		if ($tipo_pedido == "76")     $condicao_pg = "30 dias    ";
		if ($tipo_pedido == "158")    $condicao_pg = "00         ";
		if (pg_result ($resNF,$nf,garantia_antecipada) == "t") $condicao_pg = "00         "; # não gera financeiro

		$arquivo_nf .= $condicao_pg ;



		#- Transportadora -#
	#	echo "SEDEX                         "; #Nome
	#	echo "00111222000101 ";                #CNPJ
	#	echo "111222333444        ";           #I.E.
	#	echo "RUA XPTO, 123                 "; #Endereco
	#	echo "MARILIA                       "; #Cidade
	#	echo "SP";                             #Estado

		$transportadora = pg_result ($resNF,$nf,transportadora);

		$sql = "SELECT * from tbl_transportadora WHERE transportadora = $transportadora";
		$resTransp = pg_exec ($con,$sql);

		$arquivo_nf .= substr (sprintf ("%-30s",trim (pg_result ($resTransp,0,nome))),0,30);
		$arquivo_nf .= substr (sprintf ("%-15s",trim (pg_result ($resTransp,0,cnpj))),0,15);
		$arquivo_nf .= "                    ";           #I.E.
		$arquivo_nf .= substr (sprintf ("%-30s",trim (pg_result ($resTransp,0,endereco))),0,30);
		$arquivo_nf .= substr (sprintf ("%-30s",trim (pg_result ($resTransp,0,cidade))),0,30);
		$arquivo_nf .= substr (sprintf ("%-2s", trim (pg_result ($resTransp,0,estado))),0,2);


		#- Tipo do Frete -#
		$arquivo_nf .= "CIF";

		#- Valor do Frete  (Deve ser somado ao total da Nota) -#
		$arquivo_nf .= substr (sprintf ("%012.2f" ,trim (pg_result ($resNF,$nf,valor_frete))),0,12);

		#- Qtde Volumes -#
		$arquivo_nf .= substr (sprintf ("%04d" ,trim (pg_result ($resNF,$nf,qtde_volume))),0,4);

		#- Peso em Kg -#
		$arquivo_nf .= "0000.0";

		#- Especie -#
		$arquivo_nf .= "CX";

		#- Marca -#
		$arquivo_nf .= substr (sprintf ("%-20s","TELECONTROL"),0,20);

		$arquivo_nf .= "S";

		$arquivo_nf .= "\n\n\n";


	}

	if (strlen ($arquivo_nf) > 0) {
		#header("Content-type: text/plain");
		#header("Content-Disposition: attachment ; filename=nota_fiscal.telecontrol");
		#header("Content-Length: " . strlen ($arquivo_nf) . " bytes");
		#header("Content-Description: Geracao de NF");

/*		header('Pragma: public');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Content-Description: File Transfer');
		header('Content-Type: application/force-download');
		header('Cache-Control: private', false );
		header('Content-Disposition: attachment; filename=nota_fiscal.telecontrol');
		header( "Content-Transfer-Encoding: binary" );
		header("Content-Length: " . strlen ($arquivo_nf) . " bytes"); 
		readfile($url) OR die(); */

		$arquivo  = fopen ("nota_fiscal.telecontrol", "w+");
		fwrite($arquivo, "$arquivo_nf");
		fclose ($arquivo);
		if (strlen($copia_nota_fiscal)>0){
			echo "Nota fiscal: ".$copia_nota_fiscal." -> ";
		}
		echo "<a href='embarque_nota_fiscal_download.php?arquivo=nota_fiscal.telecontrol' style='font-size:16px'>Clique aqui para importar</a>";
	}else{
		echo "Não foi gerado o arquivo. Verifique.";
	}
	exit;
}
?>

<html>
<head>
<title>Faturamento de Embarques - Gerar Nota Fiscal</title>
<script type="text/javascript" src="js/jquery-latest.pack.js"></script>

<style>
.cabeca{
	background-color:'#FF9933';
	color:#ffffff;
	font-weight:bold;
}

.row1{
	font-size: 12px;
	background-color:#FFE2C6;
}

.row2{
	font-size: 12px;
	background-color:#FFFFFF;
}

tr.linha td {
	border-bottom: 1px solid #EDEDE9; 
	border-top: none; 
	border-right: none; 
	border-left: none; 
}
</style>

<script type="text/javascript">

var semafaro_faturar  = 0;
var semafaro_importar = 0;
var importado = 0;

function iniciarFaturamento(){
	alert('ATENÇÃO:\n\n1) Não click no botão VOLTAR no Navegador\n2) Aguarde faturar todas os embarques\n3) O sistema vai gerar automaticamente o arquivo e você terá que clicar no link indicado\n4) Qualquer problema contate o Suporte Telecontrol.\n\nAperte OK para iniciar o faturamento.');
	$("input[@name=btn_faturar_0]").click();
}

function faturar(botao,embarque,atual,qtde_embarques){

	if(semafaro_faturar==0){
		botao.disabled = true;
		semafaro_faturar = 1;
		botao.value='FATURANDO....AGUARDE';
		$.ajax({
			type: "POST",
			url: "<? echo $PHP_SELF ?>",
			data: "Faturar=1&embarque="+embarque,
			success: function(msg){
				semafaro_faturar = 0;
				var mensagem = msg.split("|");
				if (mensagem[0] == 'erro'){
					botao.disabled = false;
					alert(mensagem[1]);
				}
				if (mensagem[0] == 'ok' || mensagem[0]=='erro'){
					botao.disabled = true;
					botao.value=mensagem[1];

					proximo_botao = atual +1;

					if (proximo_botao >= qtde_embarques){
						importado = 1;
						importarNotas(document.frm_importar);
						return;
					}else{
						$("input[@name=btn_faturar_"+proximo_botao+"]").click();
					}
				}
			}
		});
	}else{
		alert('Aguarde faturar...');
	}
}

function importarNotas(form){
	if (importado > 0){
		if(semafaro_importar == 0){
			alert('Agora o sistema criará o arquivo de importação.\n\nAperte OK para continuar.');
			$("input[@name=btn_importar]").attr({disabled: true});
			$("input[@name=btn_importar]").attr({value: 'Gerando arquivo, aguarde...'});
			semafaro_importar = 1;
			$.ajax({
				type: "POST",
				url: "<? echo $PHP_SELF ?>",
				data: "Importar=1&embarques_importar="+form.embarques_importar.value,
				dataType: "html",
				success: function(msg){
					semafaro_importar = 0;
					//$("input[@name=btn_importar]").hide();
					$("input[@name=btn_importar]").fadeOut("slow");
					//$("#resutado_importacao").slideDown("slow");
					$("#resutado_importacao").html(msg);
					$("#resutado_importacao").fadeIn("slow");
				}
			});
		}else{
			alert('Aguarde importar...');
		}
	}else{
		alert('Aguarde faturar todas os embarques.');
	}
}
</script>

</head>
<body>

<? include 'menu.php' ?>

<center><h1>Faturar Embarque</h1></center>

<p>


<?

if($qtde_embarques>0){
	echo "<input type='button' value='Iniciar' onClick='javascript:iniciarFaturamento();'>";
	echo "<br>";
	echo '<form name="frm_faturar" method="post"  action="'.$PHP_SELF.'">';
	echo "<table width='300' align='center' cellpadding='5'>";
	echo "<tr class='cabeca'>";
	echo "	<td align='center'>Embarque</td>";
	echo "	<td align='center'>Ação</td>";
	echo "</tr>";

	# Verifica os embarque já faturados em caso de erro: 

	if(1==2){
		$qtde_embarques_aux = $qtde_embarques;

		for ($i=0;$i<$qtde_embarques_aux;$i++) {
			$sql = "SELECT faturamento 
					FROM tbl_faturamento 
					WHERE fabrica IN (3,25,51, 81)
					AND embarque=".$embarques[$i];
			$res = pg_exec ($con,$sql);
			if (pg_numrows($res)==1){
				$qtde_embarques--;
				array_splice($embarques, $i, 1);
			}
		}
	}

	for ($i=0;$i<$qtde_embarques;$i++) {
		
		$embarque_X = $embarques[$i];

		$fundo = $i%2==0?'row1':'row2';

		echo "<tr class='$fundo'>\n";
		echo "	<td align='center' nowrap>";
		echo "		<input type='text' name='embarque_$i' value='$embarque_X' size='14' maxlength='10' readOnly='readonly'>";
		echo "	</td>\n";
		echo "	<td align='center' nowrap>";
		echo "		<input type='button' value='Faturar Este Embarque' name='btn_faturar_$i' onClick='faturar(this,$embarque_X,$i,$qtde_embarques)'>";
		echo "	</td>\n";
		echo "</tr>\n";
	}

	echo "</table>";
	echo "</form>";

	echo "<br>";

	echo '<form name="frm_importar" method="post" action="'.$PHP_SELF.'">';
	#echo "<table width='400' align='center'>";
	#echo "<tr class='cabeca'>";
	#echo "	<td align='center'>Importar Embarques para Programa do Sono</td>";
	#echo "</tr>";
	#echo "<tr class='cabeca'>";
	#echo "	<td align='center'>";
	#echo "		<textarea name='notas_fiscais' rows='20' cols='10'></textarea>";
	echo "Após faturar todos os Embarques, o sistema gerará um Arquivo automaticamente <br>";
	echo "		<input type='hidden' name='embarques_importar' value='$embarque_array'>";
	#echo "		<br>";
	echo "<br><span id='resutado_importacao'></span><br>";
	echo "		<input type='button' name='btn_importar' value='Importar Embarques para Programa do Sono' onClick='importarNotas(this.form)'";
	#echo "	</td>";
	#echo "</tr>";
	echo "</form>";
	echo "<br>";
	echo "<br>";
	echo "<br>";

}else{
	echo "Nenhum embarque.";
}
exit;

if (1==2){#######
	$embarque       = $_POST['embarque'];
	$posto          = $_POST['posto'];
	$transportadora = $_POST['transportadora'];
	$qtde_volume    = $_POST['qtde_volume'];
	$valor_frete    = $_POST['valor_frete'];

	$qtde_volume = str_replace (",","",$qtde_volume);
	$qtde_volume = str_replace (".","",$qtde_volume);

	$valor_frete = str_replace (",",".",$valor_frete);
	if (substr_count ($valor_frete,".") <> 1 AND strlen ($nota_fiscal) == 0) {
		echo "<h1>Valor do Frete errado ($valor_frete)</h1>";
		exit;
	}
	$res = @pg_exec ($con,"SELECT fn_fecha_embarque ($posto, $embarque, $qtde_volume, $mbarque_total_frete, $embarque_transportadora)");

	$qtde_embarques = 1 ;
	$embarques[0] = $embarque;
}######


$copia_nota_fiscal = $nota_fiscal;
if (strlen ($nota_fiscal) > 0) {
	$qtde_embarques = 0;
}

$embarque_total_frete    = $valor_frete    ;
$embarque_qtde_volume    = $qtde_volume    ;
$embarque_transportadora = $transportadora ;


?>


<p>

<? include "rodape.php"; ?>

</body>
</html>
