<?php
	/*
	 *	HD 400456
	 *  Brayan L. Rastelli
	*/
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include "autentica_admin.php";
	$layout_menu = "gerencia";
	$title = "RELAT&Oacute;RIO GERAL DE OS";
	include "cabecalho.php";
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
	.msg_erro{
		background-color:#FF0000;
		font: bold 14px "Arial";
		color:#FFFFFF;
		text-align:center;
	}
	.formulario{
		background-color:#D9E2EF;
		font:11px Arial;
		text-align:left;
	}
	button.download { margin-top : 15px; }
	table.form tr td{
		padding:10px 30px 0 0;
	}
	.texto_avulso{
	    font: 14px Arial; color: rgb(89, 109, 155);
	    background-color: #d9e2ef;
	    text-align: center;
	    width:700px;
	    margin: 10px auto;
	    border-collapse: collapse;
	    border:1px solid #596d9b;
	}
	div.formulario table.form{
		padding:10px 0 10px 60px;
		text-align:left;
	}

	div.formulario form p{ margin:0; padding:0; }
</style>
<script type="text/javascript">
	function fnc_pesquisa_produto (campo, campo2, tipo) {
		if (tipo == "referencia" ) {
			var xcampo = campo;
		}

		if (tipo == "descricao" ) {
			var xcampo = campo2;
		}


		if (xcampo.value != "") {
			var url = "";
			url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
			janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
			janela.referencia	= campo;
			janela.descricao	= campo2;
			janela.focus();

		}
		else{
			alert("Preencha toda ou parte da informação para realizar a pesquisa!");
		}
	}
	function fnc_pesquisa_posto2 (campo, campo2, tipo) {
      if (tipo == "codigo" ) {
          var xcampo = campo;
      }

      if (tipo == "nome" ) {
          var xcampo = campo2;
      }

      if (xcampo.value != "") {
          var url = "";
          url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
          janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=     600, height=400, top=18, left=0");
          janela.codigo  = campo;
          janela.nome    = campo2;
          janela.focus();
      }
      else{
          alert("Informe toda ou parte da informação para realizar a pesquisa");
      }
	}
</script>

<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>
<script type="text/javascript" src="js/datePicker.v1.js"></script>

<script type="text/javascript">
	$().ready(function(){
		$( "#data_inicial" ).mask("99/99/9999");
		$( "#data_inicial" ).datepick({startDate : "01/01/2000"});
		$( "#data_final" ).mask("99/99/9999");
		$( "#data_final" ).datepick({startDate : "01/01/2000"});
	});

</script>

<?php include "../js/js_css.php";?>

<?php
	if ( isset($_POST['gerar']) ) { // requisicao de relatorio

		if($_POST["data_inicial"]) $data_inicial = trim ($_POST["data_inicial"]);
		if($_POST["data_final"]) $data_final = trim($_POST["data_final"]);

		if( empty($data_inicial) OR empty($data_final) )
			$msg_erro = "Data Inv&aacute;lida";

		if(strlen($msg_erro)==0) {
			list($di, $mi, $yi) = explode("/", $data_inicial);
			list($df, $mf, $yf) = explode("/", $data_final);
			if(!checkdate($mi,$di,$yi) || !checkdate($mf,$df,$yf))
				$msg_erro = "Data Inv&aacute;lida";

		}
		if(strlen($msg_erro)==0) {
			$aux_data_inicial = "$yi-$mi-$di";
			$aux_data_final = "$yf-$mf-$df";

			if(strtotime($aux_data_final) < strtotime($aux_data_inicial))
				$msg_erro = "Data Inv&aacute;lida.";

			if(strlen($msg_erro)==0){

				if (strtotime($aux_data_inicial. '+ 1 year' ) < strtotime($aux_data_final)){
					$msg_erro = 'O intervalo entre as datas n&atilde;o pode ser maior que um ano.';
				}

				if(empty($msg_erro)) {
					$cond_data = " AND data_abertura BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' ";
				}
			}
		}

		if ( strlen($msg_erro)==0 && !empty($_POST['codigo_posto']) ) {

			$sql = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND codigo_posto='".addslashes($_POST['codigo_posto'])."'";
			$res = pg_query($con,$sql);
			if( pg_num_rows($res) )
				$cond_posto = " AND posto = " . pg_result($res,0,0);
			else
				$msg_erro = "Posto n&atilde;o encontrado";

		}

		if ( !empty( $_POST['familia'] ) ) {

			$cond_familia = " AND tbl_produto.familia = " . $_POST['familia'];

		}

		if ( !empty( $_POST['linha'] ) ) {

			$cond_linha = " AND tbl_produto.linha = " . $_POST['linha'];

		}

		if ( !empty( $_POST['estado'] ) ) {

			$cond_estado = " AND consumidor_estado = '" . $_POST['estado'] . "' ";

		}

		if ( strlen($msg_erro)==0 && !empty($_POST['codigo_referencia']) ) {

			$sql = "SELECT produto FROM tbl_produto JOIN tbl_linha USING(linha) WHERE fabrica = $login_fabrica AND referencia = '" . addslashes($_POST['codigo_referencia']) . "' $cond_familia $cond_linha";
			$res = pg_query($con,$sql);
			if( pg_num_rows($res) )
				$cond_produto = " AND produto = " . pg_result($res,0,0);
			else
				$msg_erro = "Produto n&atilde;o encontrado";

		}

		if( empty($msg_erro) ) {

			$sql = "SELECT
					tbl_os.os,
					tbl_posto.nome as nome_posto,
					data_abertura,
					tbl_os.data_fechamento,
					tbl_produto.referencia,
					tbl_produto.descricao as descricao_produto,
					tbl_os.serie,
					tbl_os.sua_os,
					tbl_os.consumidor_revenda,
					tbl_defeito_constatado.descricao as defeito_constatado,
					consumidor_nome,
					consumidor_fone,
					consumidor_email,
					consumidor_estado,
					consumidor_cidade,
					tbl_os.mao_de_obra,
					tbl_os.data_nf,
					tbl_os_extra.data_fabricacao,
					tbl_os.revenda_nome,
					tbl_os.revenda_cnpj,
					tbl_os.nota_fiscal
				FROM tbl_os
				JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto AND tbl_produto.fabrica_i=$login_fabrica
				LEFT JOIN tbl_defeito_constatado USING (defeito_constatado)
				JOIN tbl_posto USING(posto)
				JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os AND tbl_os_extra.i_fabrica=$login_fabrica
				WHERE	tbl_os.fabrica = $login_fabrica
					$cond_data
					$cond_posto
					$cond_estado
					$cond_familia
					$cond_produto
					$cond_linha";
			#echo nl2br($sql); die;
			$res = pg_query($con,$sql);

			if(pg_num_rows($res)) {

				$link_csv = "xls/relatorio_geral_os_$login_fabrica_" . date("d-m-y") . '.csv';
				if (file_exists($link_csv))
					exec("rm -f $link_csv");
				$file = fopen($link_csv, 'a+');
				if($login_fabrica == 91){
					$head = array('OS','Tipo','Nome consumidor','Telefone consumidor','Cidade do consumidor','UF do consumidor','E-mail do consumidor','Contador','Posto','Data de Abertura','Data Finalizacao','Data de Fabricacao','Referencia','Produto','Mao-de-Obra','Serie','Defeito Constatado');
				}
				else{
					$head = array('OS','Posto','Data de Abertura','Referencia','Produto','Mao-de-Obra','Serie','Defeito Constatado','Cidade','Estado','Data NF');
				}
			}

			if ($login_fabrica == "91") {
				// $sql_itens = 'SELECT tbl_peca.referencia, tbl_peca.descricao, tbl_os_item.qtde, tbl_os_item.pedido,
				// 					CASE WHEN tbl_os_extra.extrato IS NOT NULL THEN tbl_tabela_item.preco ELSE 0 END as valor_item
				// 				FROM tbl_os JOIN tbl_os_produto USING(os)
				// 				JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto
				// 				JOIN tbl_os_extra ON tbl_os.os = tbl_os_extra.os JOIN tbl_os_item USING(os_produto)
				// 				JOIN tbl_peca USING(peca)
				// 				JOIN tbl_posto_linha ON tbl_posto_linha.linha = tbl_produto.linha AND tbl_posto_linha.posto = tbl_os.posto
				// 				JOIN tbl_tabela_item ON tbl_peca.peca = tbl_tabela_item.peca
				// 				JOIN tbl_tabela ON tbl_tabela.tabela = tbl_tabela_item.tabela and tbl_tabela.fabrica = tbl_os.fabrica and tbl_posto_linha.tabela = tbl_tabela.tabela
				// 				WHERE tbl_os.os = $1 ';


				$sql_itens = 'SELECT tbl_peca.referencia, tbl_peca.descricao, tbl_os_item.qtde, tbl_os_item.pedido,
								tbl_os_extra.extrato, tbl_peca.peca , tbl_defeito.descricao as defeito, tbl_pedido_item.qtde_faturada
								FROM tbl_os JOIN tbl_os_produto USING(os)
								JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto
								JOIN tbl_os_extra ON tbl_os.os = tbl_os_extra.os
								JOIN tbl_os_item USING(os_produto)
								JOIN tbl_peca USING(peca)
								JOIN tbl_defeito USING(defeito)
								LEFT JOIN tbl_pedido_item ON tbl_os_item.pedido_item = tbl_pedido_item.pedido_item
								WHERE tbl_os.os = $1';

				$sql_valor_item = "
								SELECT tbl_tabela_item.preco FROM tbl_os
								JOIN tbl_os_produto USING(os)
								JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto
								JOIN tbl_os_item USING(os_produto)
								JOIN tbl_peca USING(peca)
								JOIN tbl_posto_linha ON tbl_posto_linha.linha = tbl_produto.linha AND tbl_posto_linha.posto = tbl_os.posto
								JOIN tbl_tabela_item ON tbl_peca.peca = tbl_tabela_item.peca
								JOIN tbl_tabela ON tbl_tabela.tabela = tbl_tabela_item.tabela and tbl_tabela.fabrica = tbl_os.fabrica and tbl_posto_linha.tabela = tbl_tabela.tabela
								WHERE tbl_os.os = $1 AND tbl_peca.peca = $2
							";
				$resultX = pg_prepare($con, "sql_valor_item", $sql_valor_item);

			} else {
				$sql_itens = 'SELECT
							tbl_peca.referencia, tbl_peca.descricao, tbl_os_item.qtde, tbl_os_item.pedido,
							CASE WHEN tbl_os_extra.extrato IS NOT NULL THEN tbl_tabela_item.preco ELSE 0 END as valor_item
						  FROM
							tbl_os
							JOIN tbl_os_produto USING(os)
							JOIN tbl_os_extra	ON tbl_os.os = tbl_os_extra.os
							JOIN tbl_os_item 	USING(os_produto)
							JOIN tbl_peca 		USING(peca)
							JOIN tbl_tabela_item ON tbl_peca.peca = tbl_tabela_item.peca
							JOIN tbl_tabela ON tbl_tabela.tabela = tbl_tabela_item.tabela and tbl_tabela.fabrica = tbl_os.fabrica and ativa and tabela_garantia
						  WHERE
							tbl_os.os = $1';
			}
			$result = pg_prepare($con, "sql_itens", $sql_itens);
			for( $i = 0; $i < pg_num_rows($res); $i++ ) {

				$contador = 1 ;
				$os = pg_result($res,$i,'os');
				$sua_os = pg_result($res,$i,'sua_os');
                $cons_rev = pg_result($res,$i,'consumidor_revenda');
				if($login_fabrica == 91){
					//print_r(pg_fetch_all($res)); die;
					$dados = array(
								$sua_os,
								($cons_rev == 'C') ? "CONSUMIDOR" : "REVENDA",
								pg_result($res,$i,'consumidor_nome'),
								pg_result($res,$i,'consumidor_fone'),
								pg_result($res,$i,'consumidor_cidade'),
								pg_result($res,$i,'consumidor_estado'),
								pg_result($res,$i,'consumidor_email'),
								$contador,
								pg_result($res,$i,'nome_posto'),
								pg_result($res,$i,'data_abertura'),
								(!empty(pg_result($res,$i,'data_fechamento'))) ? pg_result($res,$i,'data_fechamento'): '' ,
								pg_result($res,$i,'data_fabricacao'),
								pg_result($res,$i,'referencia'),
								pg_result($res,$i,'descricao_produto'),
								pg_result($res,$i,'mao_de_obra'),
								pg_result($res,$i,'serie'),
								pg_result($res,$i,'defeito_constatado')
					);
					$dados3 = array(
								pg_result($res,$i,'revenda_nome'),
								pg_result($res,$i,'revenda_cnpj'),
								pg_result($res,$i,'nota_fiscal'),
								pg_result($res,$i,'data_nf')
								);

				}
				else{
					$dados = array(
								$sua_os,
								pg_result($res,$i,'nome_posto'),
								pg_result($res,$i,'data_abertura'),
								pg_result($res,$i,'referencia'),
								pg_result($res,$i,'descricao_produto'),
								pg_result($res,$i,'mao_de_obra'),
								pg_result($res,$i,'serie'),
								pg_result($res,$i,'defeito_constatado'),
								pg_result($res,$i,'consumidor_cidade'),
								pg_result($res,$i,'consumidor_estado'),
								pg_result($res,$i,'data_nf')
					);
				}



//				die($sql_itens);

				$res_itens = pg_execute($con,'sql_itens',array($os));

				if($login_fabrica == 91){
					$head2 = array('Referência Peça','Peça','Peça Defeito','Qtde','Valor Item','Pedido','Nome da Revenda','CNPJ da Revenda','Nota Fiscal','Data da Nota Fiscal');
				}else{
					$head2 = array('Referência Peça','Peça','Peça Defeito','Qtde','Valor Item','Pedido');
				}
				foreach($head2 as $linha)
					array_push($head,$linha);


/*				echo "<pre>";
				print_r($head);
				echo "</pre>";
	*/

				if( $i == 0)
					fwrite($file, implode(';',$head) . "\n" );

				if(!pg_num_rows($res_itens))
					fwrite($file, implode(';',$dados) . "\n" );
				$contador = 1 ;	
				for($j = 0; $j < pg_num_rows($res_itens); $j++) {

					if ($login_fabrica == 91) {
						$extrato = pg_fetch_result($res_itens, $j, 'extrato');
						$peca = pg_fetch_result($res_itens, $j, 'peca');
						$defeito = pg_fetch_result($res_itens, $j, 'defeito');
						$qtde_faturada = pg_fetch_result($res_itens, $j, 'qtde_faturada');
						$contador = ($j == 0 ) ? 1 : 0;

						if (!empty($extrato)) {

							$qry_valor_item = pg_execute($con,'sql_valor_item',array($os,$peca));


							if (pg_num_rows($qry_valor_item) > 0) {
								$valor_item = number_format(pg_fetch_result($qry_valor_item, 0, 'preco'), 2, ',', '.');
							} else {
								$valor_item = 0;
							}
						} else {
							$valor_item = '0';
						}

					} else {
						$valor_item = number_format( pg_result($res_itens,$j,'valor_item'), 2,',','.');
					}

					$dados2 = array(
						 pg_result($res_itens,$j,'referencia'),
						 pg_result($res_itens,$j,'descricao'),
						 pg_result($res_itens,$j,'defeito'),
						 pg_result($res_itens,$j,'qtde'),
						 $valor_item,
						 pg_result($res_itens,$j,'pedido')
					);
					if($login_fabrica == 91){
						$dados[7] = $contador;

						foreach($dados3 as $linha)
							array_push($dados2,$linha);
					}

					foreach($dados2 as $linha)
						array_push($dados,$linha);
//					echo "<pre>"; print_r($dados); echo "</pre>";
					fwrite( $file, implode(';',$dados) . "\n" );

					for ($z=0;$z< count($dados2);$z++) {
						array_pop($dados);
					}


				}

			}
		}
		else { // Erro de validacao

			echo '<div id="erro" class="msg_erro" style="display:none;">'.$msg_erro.'</div>';

		}

	} // fim request
?>
<div class="texto_avulso">Esse relat&oacute;rio considera a data de abertura da OS.</div>
<div class="formulario" style="width:700px; margin:auto;">
	<div id="msg"></div>
	<div class="titulo_tabela">Par&acirc;metros de Pesquisa</div>
	<form action="<?=$_SERVER['PHP_SELF'];?>" method="POST" name="frm_os">
		<table cellspacing="1" align="center" class="form">
			<tr>
				<td>
					<label for="data_inicial">Data Inicial</label><br />
					<input type="text" name="data_inicial" id="data_inicial" class="frm" size="15" value="<?=isset($_POST['data_inicial'])?$_POST['data_inicial'] : '' ?>" />
				</td>
				<td>
					<label for="data_final">Data Final</label><br />
					<input type="text" name="data_final" id="data_final" class="frm" size="15" value="<?=isset($_POST['data_final'])?$_POST['data_final'] : ''?>"/>
				</td>
			</tr>
			<tr>
				<td>
					<label for="estado">Estado</label><br />
					<select name="estado" id="estado" style="width:130px; font-size:9px" class="frm">
						<option value="" <?php if (strlen($estado) == 0) echo " selected ";?> >TODOS OS ESTADOS</option>
						<option value="AC" <?php if ($estado == "AC") echo " selected "; ?>>AC - Acre</option>
						<option value="AL" <?php if ($estado == "AL") echo " selected "; ?>>AL - Alagoas</option>
						<option value="AM" <?php if ($estado == "AM") echo " selected "; ?>>AM - Amazonas</option>
						<option value="AP" <?php if ($estado == "AP") echo " selected "; ?>>AP - Amap&aacute;</option>
						<option value="BA" <?php if ($estado == "BA") echo " selected "; ?>>BA - Bahia</option>
						<option value="CE" <?php if ($estado == "CE") echo " selected "; ?>>CE - Cear&aacute;</option>
						<option value="DF" <?php if ($estado == "DF") echo " selected "; ?>>DF - Distrito Federal</option>
						<option value="ES" <?php if ($estado == "ES") echo " selected "; ?>>ES - Esp&iacute;rito Santo</option>
						<option value="GO" <?php if ($estado == "GO") echo " selected "; ?>>GO - Goi&aacute;s</option>
						<option value="MA" <?php if ($estado == "MA") echo " selected "; ?>>MA - Maranh&atilde;o</option>
						<option value="MG" <?php if ($estado == "MG") echo " selected "; ?>>MG - Minas Gerais</option>
						<option value="MS" <?php if ($estado == "MS") echo " selected "; ?>>MS - Mato Grosso do Sul</option>
						<option value="MT" <?php if ($estado == "MT") echo " selected "; ?>>MT - Mato Grosso</option>
						<option value="PA" <?php if ($estado == "PA") echo " selected "; ?>>PA - Par&aacute;</option>
						<option value="PB" <?php if ($estado == "PB") echo " selected "; ?>>PB - Para&iacute;ba</option>
						<option value="PE" <?php if ($estado == "PE") echo " selected "; ?>>PE - Pernambuco</option>
						<option value="PI" <?php if ($estado == "PI") echo " selected "; ?>>PI - Piau&iacute;</option>
						<option value="PR" <?php if ($estado == "PR") echo " selected "; ?>>PR - Paran&aacute;</option>
						<option value="RJ" <?php if ($estado == "RJ") echo " selected "; ?>>RJ - Rio de Janeiro</option>
						<option value="RN" <?php if ($estado == "RN") echo " selected "; ?>>RN - Rio Grande do Norte</option>
						<option value="RO" <?php if ($estado == "RO") echo " selected "; ?>>RO - Rond&ocirc;nia</option>
						<option value="RR" <?php if ($estado == "RR") echo " selected "; ?>>RR - Roraima</option>
						<option value="RS" <?php if ($estado == "RS") echo " selected "; ?>>RS - Rio Grande do Sul</option>
						<option value="SC" <?php if ($estado == "SC") echo " selected "; ?>>SC - Santa Catarina</option>
						<option value="SE" <?php if ($estado == "SE") echo " selected "; ?>>SE - Sergipe</option>
						<option value="SP" <?php if ($estado == "SP") echo " selected "; ?>>SP - S&atilde;o Paulo</option>
						<option value="TO" <?php if ($estado == "TO") echo " selected "; ?>>TO - Tocantins</option>
					</select>
				</td>
				<td>
					Linha<br />
					<select name="linha" style="width: 130px" class="frm" title="Escolha a Linha do Produto">
						<option value=""></option>
						<?
							 $sql = "SELECT linha, nome
									 FROM   tbl_linha
									 WHERE  tbl_linha.fabrica = $login_fabrica
									 ORDER  BY tbl_linha.nome";
							 $res = pg_exec ($con,$sql);
							 for ($x = 0 ; $x < pg_numrows($res) ; $x++){
								 $aux_linha = trim(pg_result($res,$x,'linha'));
								 $aux_nome  = trim(pg_result($res,$x,'nome'));
								 echo "<option value='$aux_linha'";
									 if ($linha == $aux_linha){
										 echo " SELECTED ";
										 $mostraMsgLinha = "<br> da LINHA $aux_nome";
									 }
								 echo ">$aux_nome</option>\n";
							 }
						?>
					</select>

				</td>
			</tr>
			<tr>
				<td>
					<label for="codigo_referencia">Refer&ecirc;ncia Produto</label> <br />
					<input class='frm' type='text' name='codigo_referencia' size='15' maxlength='20' value='<?php echo $codigo_referencia; ?>'>&nbsp;
					<img src='imagens/lupa.png' border='0' align='absmiddle' style='cursor:pointer' onclick="javascript: fnc_pesquisa_produto (document.frm_os.codigo_referencia,document.frm_os.produto_descricao,'referencia')" />
				</td>

				<td>
					<label for="produto_descricao">Descri&ccedil;&atilde;o Produto</label><br />
					<input class='frm' type='text' name='produto_descricao' size='30' value='<?php echo $produto_descricao; ?>'>&nbsp;
					<img src='imagens/lupa.png'  style='cursor:pointer' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_os.codigo_referencia,document.frm_os.produto_descricao,'descricao')" />
				</td>
			</tr>
			<tr>
				<td>
					<label for="codigo_posto">C&oacute;d. Posto</label> <br />
					<input type="text" name="codigo_posto" id="codigo_posto" size="15" onblur="javascript: fnc_pesquisa_posto2(document.frm_os.codigo_posto_off, document.frm_os.posto_nome, 'codigo');" value="<? echo $codigo_posto ?>" class="frm">&nbsp;
					<img border="0" src="imagens/lupa.png" style="cursor:pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo cÃ³digo" onclick="javascript: fnc_pesquisa_posto2 (document.frm_os.codigo_posto, document.frm_os.posto_nome, 'codigo')">
				</td>
				<td>
					<label for="posto_nome">Nome Posto</label> <br />
					<input type="text" name="posto_nome" id="posto_nome" size="30" onblur="javascript: fnc_pesquisa_posto2 (document.frm_os.codigo_posto, document.frm_os.posto_nome, 'nome');" value="<?echo $posto_nome ?>" class="frm">&nbsp;
					<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto2 (document.frm_os.codigo_posto, document.frm_os.posto_nome, 'nome')">
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<label for="familia">Fam&iacute;lia</label><br />
					<select name="familia" class="frm">
						<option></option>
						<?
							$sql = "SELECT  familia, descricao
									FROM  tbl_familia
									WHERE fabrica = $login_fabrica
									AND   ativo
									order by tbl_familia.descricao";
							$resFamilia = pg_exec($con, $sql);
							for($i=0; $i<pg_numrows($resFamilia); $i++){
								$xfamilia    = pg_result($resFamilia,$i,'familia');
								$xdescricao = pg_result($resFamilia,$i,'descricao');
								echo "<option value='$xfamilia'";
								if($xfamilia==$familia) echo "selected";
								echo ">$xdescricao</option>";
							}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<td colspan="2" style="padding-top:15px;" align="center">
					<input type="submit" name="gerar" value="Gerar" />
				</td>
			</tr>
		</table>
	</form>
</div>
<?php
	if ( isset ($file) ) {
		echo "<button class='download' onclick=\"window.open('$link_csv') \">Download CSV</button>";
		fclose($file);
	}
	else if(empty($msg_erro) && isset($_POST['gerar']) )
		echo "Não foram encontrados resultados para essa pesquisa";
?>
<script type="text/javascript">
	<?php if ( !empty($msg_erro) ){ ?>
			$("#erro").appendTo("#msg").fadeIn("slow");
	<?php } ?>
</script>
<?php include 'rodape.php'; ?>
