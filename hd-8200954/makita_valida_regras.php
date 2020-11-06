<?
include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'helpdesk/mlg_funciones.php';

$gera_extrato = trim($_GET["gera_extrato"]);

if ($gera_extrato == 'extrato'){
	$login_fabrica = trim($_GET["login_fabrica"]);
	$login_posto   = trim($_GET["login_posto"]);
}else{
	include_once 'autentica_usuario.php';
}


$msg_erro = "";
$btn_acao = $_REQUEST['btn_acao'];
$linha_form = $_REQUEST['linha_form'];

if (strlen ($btn_acao) > 0 OR strlen ($linha_form) > 0) {
	$cod			= trim($_POST ['cod']);
	$vendedor		= trim($_POST ['vendedor']);
	$cnpj			= trim($_POST ['cnpj']);
	$condpg			= trim($_POST ['condpg']);

	if (strlen ($cod)      == 0) $cod      = trim($_GET ['cod']);
	if (strlen ($vendedor) == 0) $vendedor = trim($_GET ['vendedor']);
	if (strlen ($cnpj)     == 0) $cnpj     = trim($_GET ['cnpj']);
	if (strlen ($condpg)   == 0) $condpg   = trim($_GET ['condpg']);

	$linha_form = $_GET['linha_form'];

	if (strlen ($cnpj) == 0) {
		$posto = trim($_GET ['posto']);
		$sql = "SELECT cnpj
				FROM tbl_posto
				WHERE posto = $posto
				";
		$res_makita = pg_query ($con,$sql);
		$cnpj = pg_fetch_result ($res_makita,0,0);
	}

	if (strlen ($condpg) == 0) {
		$condicao = trim($_GET ['condicao']);
		$sql = "SELECT codigo_condicao
				FROM tbl_condicao
				WHERE condicao = $condicao;";
		$res_makita = pg_query ($con,$sql);
		$condpg = pg_fetch_result ($res_makita,0,0);
	}

	if (strlen ($cod) == 0) {
		$produto_referencia = trim($_GET ['produto_referencia']);

		//somente tela tabela_precos_makita
		////verifica se a peca existe
		if($btn_acao == 'acao_tabela_preco'){
            $sql_verifica_peca = "
                SELECT tbl_peca.referencia
                     , CASE origem
                           WHEN 'NAC' THEN 0
                           WHEN 'IMP' THEN 1
                           ELSE 2
                       END AS CST
                     , FL.peca IS NOT NULL AS fora_linha, tbl_peca.parametros_adicionais, tbl_peca.classificacao_fiscal
                  FROM tbl_peca
             LEFT JOIN tbl_peca_fora_linha FL USING(peca, fabrica)
                 WHERE tbl_peca.referencia = '$produto_referencia'
					AND tbl_peca.ativo
                   AND fabrica    = $login_fabrica";
			$res_verifica_peca = pg_query ($con,$sql_verifica_peca);

			if (pg_num_rows($res_verifica_peca) == 0) {
				exit("referencia_invalida");
			}
            list ($produto_referencia, $CST, $fora_linha, $parametros_adicionais,$classificacao_fiscal) = pg_fetch_row($res_verifica_peca, 0);
	    $parametros_adicionais = json_decode($parametros_adicionais,true);
            $disponibilidade = ($parametros_adicionais['status']) ? $parametros_adicionais['status']  : 'Disponível';
		}

		$sql = "
              SELECT tbl_depara.para
                   , tbl_peca.referencia
                   , tbl_peca.descricao
                   , CASE origem
                         WHEN 'NAC' THEN 0
                         WHEN 'IMP' THEN 1
                         ELSE 2
                     END AS CST,
		tbl_peca.parametros_adicionais
                FROM tbl_depara
                JOIN tbl_peca
                  ON tbl_peca.referencia = tbl_depara.de
                 AND tbl_peca.fabrica    = tbl_depara.fabrica
               WHERE tbl_depara.de       = '$produto_referencia'
                 AND tbl_depara.fabrica  = $login_fabrica";
			$res1 = pg_query ($con,$sql);

			if (pg_num_rows($res1) > 0) {
				$xpeca_para          = pg_fetch_result ($res1,0,para);
				$xreferencia_peca_de = pg_fetch_result ($res1,0,referencia);
				$xdescricao_peca_de  = pg_fetch_result ($res1,0,descricao);
				$parametros_adicionais  = pg_fetch_result ($res1,0,parametros_adicionais);			
				$parametros_adicionais = json_decode($parametros_adicionais,true);
				$disponibilidade = ($parametros_adicionais['status']) ? $parametros_adicionais['status']  : '';
				$cor = "#00B95C";
				$cod = $xpeca_para;
				$mudou = 'SIM';
			}else {
				$cod = $produto_referencia;
			}

		if($mudou != "SIM"){
			$sql = "SELECT * FROM tbl_peca_fora_linha WHERE referencia = '$produto_referencia' AND fabrica = $login_fabrica";
			$resFora = pg_query($con,$sql);
			if(pg_num_rows($resFora) > 0){
				$fora = "SIM";
			}
		}
	}

	$cod_devolve = $cod;
	echo $cod ;

	$sql = "SELECT * FROM makita_sb1_produto WHERE b1_cod = '$cod';";

	$res_makita    = pg_query ($con,$sql);
	$descricao     = pg_fetch_result ($res_makita,0,'b1_desc');
	$preco_1       = pg_fetch_result ($res_makita,0,'b1_prv1');
	$procedencia   = pg_fetch_result ($res_makita,0,'b1_proced');
	$ipi           = pg_fetch_result ($res_makita,0,'b1_ipi');
	$produto_grupo = pg_fetch_result ($res_makita,0,'b1_grupo');
	$produto_tipo  = pg_fetch_result ($res_makita,0,'b1_tipo');

    $classifFiscal = pg_fetch_result($res_makita, 0, 'b1_clasfis');

	$produto_prom1 = pg_fetch_result ($res_makita,0,'b1_prom1');
	$produto_prom2 = pg_fetch_result ($res_makita,0,'b1_prom2');
	$produto_prom3 = pg_fetch_result ($res_makita,0,'b1_prom3');
	$produto_prom4 = pg_fetch_result ($res_makita,0,'b1_prom4');
	$produto_prom5 = pg_fetch_result ($res_makita,0,'b1_prom5');

	if (strlen ($produto_prom1) == 0) $produto_prom1 = 0 ;
	$produto_prom1 = str_replace (',','.',$produto_prom1);
	if (strlen ($produto_prom2) == 0) $produto_prom2 = 0 ;
	$produto_prom2 = str_replace (',','.',$produto_prom2);
	if (strlen ($produto_prom3) == 0) $produto_prom3 = 0 ;
	$produto_prom3 = str_replace (',','.',$produto_prom3);
	if (strlen ($produto_prom4) == 0) $produto_prom4 = 0 ;
	$produto_prom4 = str_replace (',','.',$produto_prom4);
	if (strlen ($produto_prom5) == 0) $produto_prom5 = 0 ;
	$produto_prom5 = str_replace (',','.',$produto_prom5);

	if (strlen ($preco_1) == 0) $preco_1 = 0 ;
	$preco_1 = str_replace (',','.',$preco_1);

	$ipi = str_replace (',','.',$ipi);

	if (strlen ($linha_form) == 0) {
		echo " - ";
		echo $descricao;
		echo " - (b1_prv1) R$ ";
		echo number_format ($preco_1,2,",",".");
		echo " - (b1_ipi) ";
		echo number_format ($ipi,2,",",".");
		echo "%";
	}

	#--------- Determina Condição de Pagamento -----------
	$sql = "SELECT * FROM tbl_condicao
			WHERE codigo_condicao = '$condpg'
			AND   fabrica = 42
			";
	$res_makita = pg_query ($con,$sql);
	if (pg_num_rows ($res_makita) > 0) {
		$condpg_descricao = pg_fetch_result ($res_makita,0,descricao);
		$condpg_descfin   = pg_fetch_result ($res_makita,0,desconto_financeiro);
		$condpg_acresci   = pg_fetch_result ($res_makita,0,acrescimo_financeiro);
		$condpg_codigo    = pg_fetch_result ($res_makita,0,codigo_condicao);
		echo "<br>";
		echo "Condição = $condpg - $condpg_descricao - DescFin $condpg_descfin - Acresci - $condpg_acresci";
		echo "<br>";
	}else{
		echo "<br>";
		echo "condicao $condpag não cadastrada";
		echo "<br>";
	}

	#-------- Le dados do Cliente ---------
	$sql = "SELECT * FROM makita_sa1_cliente WHERE a1_cgc = '$cnpj';";
	$res_makita = pg_query ($con,$sql);
	if (pg_num_rows ($res_makita) > 0) {
#		$codtab = pg_fetch_result ($res_makita,0,codtab);
		$nome           = pg_fetch_result ($res_makita,0,a1_nome);
		$cliente_tipo   = pg_fetch_result ($res_makita,0,a1_tipo);
		$cliente_estado = pg_fetch_result ($res_makita,0,a1_est);

		$cliente_desmaq  = pg_fetch_result ($res_makita,0,a1_desmaq);
		$cliente_despec  = pg_fetch_result ($res_makita,0,a1_despec);
		$cliente_desace  = pg_fetch_result ($res_makita,0,a1_desace);

		$cliente_desmaq1 = pg_fetch_result ($res_makita,0,a1_desmaq1);
		$cliente_despec1 = pg_fetch_result ($res_makita,0,a1_despec1);
		$cliente_desace1 = pg_fetch_result ($res_makita,0,a1_desace1);

		$cliente_desmaq2 = pg_fetch_result ($res_makita,0,a1_desmaq2);
		$cliente_despec2 = pg_fetch_result ($res_makita,0,a1_despec2);
		$cliente_desace2 = pg_fetch_result ($res_makita,0,a1_desace2);

		if (strlen ($cliente_desmaq1) == 0) $cliente_desmaq1 = 0 ;
		$cliente_desmaq1 = str_replace (',','.',$cliente_desmaq1);
		if (strlen ($cliente_despec1) == 0) $cliente_despec1 = 0 ;
		$cliente_despec1 = str_replace (',','.',$cliente_despec1);
		if (strlen ($cliente_desace1) == 0) $cliente_desace1 = 0 ;
		$cliente_desace1 = str_replace (',','.',$cliente_desace1);

		if (strlen ($cliente_desmaq2) == 0) $cliente_desmaq2 = 0 ;
		$cliente_desmaq2 = str_replace (',','.',$cliente_desmaq2);
		if (strlen ($cliente_despec2) == 0) $cliente_despec2 = 0 ;
		$cliente_despec2 = str_replace (',','.',$cliente_despec2);
		if (strlen ($cliente_desace2) == 0) $cliente_desace2 = 0 ;
		$cliente_desace2 = str_replace (',','.',$cliente_desace2);

		if (strlen ($cliente_desmaq) == 0) $cliente_desmaq = 0 ;
		$cliente_desmaq = str_replace (',','.',$cliente_desmaq);
		if (strlen ($cliente_despec) == 0) $cliente_despec = 0 ;
		$cliente_despec = str_replace (',','.',$cliente_despec);
		if (strlen ($cliente_desace) == 0) $cliente_desace = 0 ;
		$cliente_desace = str_replace (',','.',$cliente_desace);

		$cliente_prom1 = pg_fetch_result ($res_makita,0,'a1_prom1');
		$cliente_prom2 = pg_fetch_result ($res_makita,0,'a1_prom2');
		$cliente_prom3 = pg_fetch_result ($res_makita,0,'a1_prom3');
		$cliente_prom4 = pg_fetch_result ($res_makita,0,'a1_prom4');
		$cliente_prom5 = pg_fetch_result ($res_makita,0,'a1_prom5');

		$cliente_regiao = pg_fetch_result ($res_makita,0,'a1_regiao');

		$makita_preco = 0 ;
		if (strlen ($linha_form) == 0) {
			echo "<br>";
			echo "Nome do cliente = $nome ";
			echo "<br>";
			echo "Tabela = $tabela";
		}

		#-------- Determina tabela de precos - UC-03 -------
		# ??? como determinar se tem tabela de descontos especifica ???
		if ($cliente_tipo == 'I') {
			$tabela = 3;
			echo "<br>";
			echo "Fixando tabela $tabela";
		}

		if ($cliente_tipo == 'E') {
			if ($condpg_codigo == '62') {
				$tabela = 6;
				echo "<br>";
				echo "Fixando tabela $tabela";
			}
			if ($condpg_codigo == '63') {
				$tabela = 5;
				echo "<br>";
				echo "Fixando tabela $tabela";
			}
			if ($condpg_codigo <> '62' AND $condpg_codigo <> '63') {
				$tabela = 4;
				echo "<br>";
				echo "Fixando tabela $tabela";
			}
		}

		$sql = "SELECT tab_item.da1_prcven AS preco
				FROM makita_da1_item_tab_preco tab_item
				WHERE da1_codpro = '$cod'
				AND da1_codtab = '$tabela'
				";
		$res_makita = pg_query ($con,$sql);
		if (pg_num_rows ($res_makita) > 0) {
			$makita_preco = pg_fetch_result ($res_makita,0,preco);
			echo "<br>";
			echo "Preço da tabela $codtab = R$ ";
			echo number_format ($makita_preco,2,",",".");
		}
	}else{
		echo "<br>";
		echo "cliente $cnpj não cadastrado";
		echo "<br>";
	}

	if ($makita_preco == 0) $makita_preco = $preco_1;

	# -----------------------
	# ?? Onde achar – Se código da natureza financeira (ED_CODIGO = Outros)
	#------------ Acrescimo Financeiro -------
	echo "<br>";
	if (strlen ($condpg_descfin) > 0) {
		$descfin = str_replace (',','.',$condpg_descfin);
		$makita_preco   = $makita_preco * (1 - ($descfin / 100)) ;
		echo "Com DescFin - R$ ";
		echo number_format ($makita_preco,2,",",".");
	}

	if (strlen ($condpg_acresci) > 0) {
		$acresci = str_replace (',','.',$condpg_acresci);
		//$makita_preco   = $makita_preco * (1 + ($acresci / 100)) ;
		//Retirado regra, pois no banco de daddos gravar o valor do porcentual já calculado!
		$makita_preco   = $makita_preco * $acresci;
		echo "Com Acresci - R$ ";
		echo number_format ($makita_preco,2,",",".");
	}

	#------------ Procedencia -------
	# ??? Onde vejo a Unidade (Ponta Grossa ou PATAMs) ???
	# Resposta do Rodrigo - Unidade de venda será fixa Ponta Grossa - PR

	echo "<br>";
	echo "Procedencia = $procedencia ";
	echo "<br>";
	echo "Tipo Cliente = $cliente_tipo ";
	if (($procedencia == '2' OR $procedencia == '3') AND $cliente_tipo == 'F') {
		echo "<br>";
		echo "Aplicando IPI $ipi ";
		$makita_preco   = $makita_preco * (1 + ($ipi / 100)) ;
		echo number_format ($makita_preco,2,",",".");
	}

	if ($makita_preco <= 0) {
		echo "<br>";
		echo "******* SAINDO POR PREÇO ZERO *********";
	}

	#------------ Desconto pelo Grupo -------
	echo "<br>";
	echo "Produto Grupo = $produto_grupo ";
	$familia = substr ($produto_grupo,0,1);
	echo "<br>";
	if ($familia == '1') echo "É uma Máquina";
	if ($familia == '2') echo "É uma Peça";
	if ($familia == '3') echo "É um Acessório";
	if ($familia == '4') echo "É uma Impotação";
	if ($familia == '5') echo "É um Brinde, acessório";
	if ($familia == '6') echo "É Outros";
	echo "<br>";

	//echo "GGGGGGGGGG - ".substr ($produto_grupo,1,1)." - ".$familia." - ".$cliente_despec1." - GGGGGGGGGGGGGG";

	if (substr ($produto_grupo,1,1) == '3' ) {
		echo "<br>";
		echo "Desconto Maktec ";
		if ($familia == '1') $makita_preco = $makita_preco * (1 - ($cliente_desmaq1 / 100)) ;
		if ($familia == '2') $makita_preco = $makita_preco * (1 - ($cliente_despec1 / 100)) ;
		if ($familia == '3') $makita_preco = $makita_preco * (1 - ($cliente_desace1 / 100)) ;
		if ($familia == '6') $makita_preco = $makita_preco * (1 - ($cliente_desace1 / 100)) ;
		echo number_format ($makita_preco,2,",",".");
	}

	if (substr ($produto_grupo,1,1) == '1' ) {
		echo "<br>";
		echo "Desconto OPE ";
		if ($familia == '1') $makita_preco = $makita_preco * (1 - ($cliente_desmaq2 / 100)) ;
		if ($familia == '2') $makita_preco = $makita_preco * (1 - ($cliente_despec2 / 100)) ;
		if ($familia == '3') $makita_preco = $makita_preco * (1 - ($cliente_desace2 / 100)) ;
		if ($familia == '6') $makita_preco = $makita_preco * (1 - ($cliente_desace2 / 100)) ;
		echo number_format ($makita_preco,2,",",".");
	}

	if (substr ($produto_grupo,1,1) == '0' OR substr ($produto_grupo,1,1)  == '4') {

		echo "<br>";
		echo "Desconto PADRAO ";
		if ($familia == '1') $makita_preco = $makita_preco * (1 - ($cliente_desmaq / 100)) ;
		if ($familia == '2') $makita_preco = $makita_preco * (1 - ($cliente_despec / 100)) ;
		if ($familia == '3') $makita_preco = $makita_preco * (1 - ($cliente_desace / 100)) ;
		if ($familia == '6') $makita_preco = $makita_preco * (1 - ($cliente_desace / 100)) ;
		echo number_format ($makita_preco,2,",",".");
	}

	#------------ Promoção -------
	if ($cliente_prom1 == 'S') {
		echo "<br>";
		echo "Promoção 1 - $produto_prom1 R$ ";
		$makita_preco   = $makita_preco * (1 - ($produto_prom1 / 100)) ;
		echo number_format ($makita_preco,2,",",".");
	}

	if ($cliente_prom2 == 'S') {
		echo "<br>";
		echo "Promoção 2 - $produto_prom2 R$ ";
		$makita_preco   = $makita_preco * (1 - ($produto_prom2 / 100)) ;
		echo number_format ($makita_preco,2,",",".");
	}

	if ($cliente_prom3 == 'S') {
		echo "<br>";
		echo "Promoção 3 - $produto_prom3 R$ ";
		$makita_preco   = $makita_preco * (1 - ($produto_prom3 / 100)) ;
		echo number_format ($makita_preco,2,",",".");
	}

	if ($cliente_prom4 == 'S') {
		echo "<br>";
		echo "Promoção 4 - $produto_prom4 R$ ";
		$makita_preco   = $makita_preco * (1 - ($produto_prom4 / 100)) ;
		echo number_format ($makita_preco,2,",",".");
	}

	#------------- Consumidir Final ------------
	# ??? C5_CONSUMI está em que tabela
	#

	#--------- Desconto por Região - Saida São Bernardo -------
	# ??? Onde determinar a saida do produto ???
	$estado_venda = 'SP';  // para testes hoje, depois voltar PR
	if (1==1) {
		echo "<br>";
		echo "Venda saindo de Sao Bernardo";
		echo "<br>";
		echo "Desconto por região - $cliente_regiao";
		if ($cod <> '004201-0') {
			if ($cliente_regiao == '003') $makita_preco   = $makita_preco * (1 - (10 / 100)) ;
			if ($cliente_regiao == '002') $makita_preco   = $makita_preco * (1 - (06 / 100)) ;
		}
		echo " - R$ ";
		echo number_format ($makita_preco,2,",",".");
	}

	# ------ Saindo de PATAMs e Ponta Grossa ------------
	# ??? onde fica F4_ICM para ver se tem tributacao ICMS ???

	if (1==2) {
		# fixando unidade de saida como Ponta Grossa - PR
		$estado_venda = 'PR';

		echo "<br>";
		echo "Saindo de Ponta Grossa - PR";
		echo "<br>";
		echo "Desconto por região - $cliente_regiao";
		if ($cod <> '004201-0' AND $produto_tipo <> 'MO') {
			if ($estado_venda == 'SP') {
				if ($cliente_regiao == '003') $makita_preco = $makita_preco * (1 - (10 / 100)) ;
				if ($cliente_regiao == '002') $makita_preco = $makita_preco * (1 - (06 / 100)) ;
			}
			if ($estado_venda == 'RJ') {
				if ($cliente_estado == 'RJ') $makita_preco = $makita_preco * (1 - (06 / 100)) ;
			}
			if ($estado_venda == 'BA') {
				# ??? onde pesquiso C5_CONSUMI
				if ($clietne_tipo == 'F' AND $cliente_estado <> 'BA')
					$makita_preco   = $makita_preco * (1 - (06 / 100)) ;
			}
			if ($estado_venda == 'PR') {
				if ($cliente_regiao == '003')
					$makita_preco = $makita_preco * (1 - (10 / 100)) ;
				if (($cliente_regiao == '001' OR $cliente_regiao == '002')
					AND $cliente_estado == 'SP')
					$makita_preco = $makita_preco * (1 - (06.82 / 100)) ;
				if (($cliente_regiao == '001' OR $cliente_regiao == '002')
					AND $cliente_estado <> 'SP' AND $cliente_estado <> 'PR')
					$makita_preco = $makita_preco * (1 - (06 / 100)) ;
				if (($cliente_regiao == '001' OR $cliente_regiao == '002')
					AND $cliente_estado == 'PR' AND $cliente_tipo == 'R')
					$makita_preco = $makita_preco * (1 - (06 / 100)) ;
			}
		}
		echo " - R$ ";
		echo number_format ($makita_preco,2,",",".");
	}

	echo "<br><br><hr>";
	echo "<b>Preço Final R$ ";
	echo number_format ($makita_preco,2,",",".");
	#HD 400618 - Analise - 2 - INICIO

    if ($disponibilidade)
        $disponibilidade = $disponibilidade == 'I' ? 'Indisponível' : 'Disponível';
	
	$classifFiscal = (!empty($classifFiscal)) ? : $classificacao_fiscal;
	$valor_total = ( $makita_preco +  ( ($makita_preco*$ipi)/100 ) );
	#HD 400618 - Analise - 2 - FIM
	#-------- Para uso do AJAX ----------
	$cod = ($mudou == "SIM") ? $produto_referencia : $cod;
    $info = [
        number_format ($makita_preco,2,",","."),
        $linha_form,
        $descricao,
        $mudou,
        $cod,
        $cod_devolve,
        #HD 400618 - Analise - 2 - INICIO
        $ipi, //VALOR DO IPI
        number_format ($valor_total,2,",","."), // VALOR TOTAL
        #HD 400618 - Analise - 2 - FIM
        number_format ($preco_1,2,",","."),
        $fora_linha == 't' ? 'Fora de linha' : '',
        $classifFiscal,
        $CST,
	is_date($previsao_entrega, 'CDATE', 'EUR'),
	$fora,
	$disponibilidade
    ];
    echo "<br>";
    echo "<preco>";
    echo implode('|', $info);
    echo "</preco>";
	#------------------------------------
}

