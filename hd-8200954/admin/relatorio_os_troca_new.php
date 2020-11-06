<?php
	/**
	 *	@description Relatorio Pesquisa de Satisfação - HD 674943 e 720502
	 *  @author Brayan L. Rastelli
	 **/
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include "autentica_admin.php";
	$layout_menu = "gerencia";
	$title = "RELATÓRIO DE OS DE TROCA";
	include "cabecalho.php";
?>

<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all">

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
	table.tabela tr td{
		font-family: verdana;
		font-size: 11px;
		border-collapse: collapse;
		border:1px solid #596d9b;
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

<script type="text/javascript" src="js/jquery-1.4.2.js"></script>
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>
<script type="text/javascript" src="js/datePicker.v1.js"></script>
<script src="../plugins/shadowbox/shadowbox.js" type="text/javascript"></script>

<script type="text/javascript">
	$().ready(function(){

		Shadowbox.init();

		$( "#data_inicial" ).maskedinput("99/99/9999");
		$( "#data_inicial" ).datePicker({startDate : "01/01/2000"});
		$( "#data_final" ).maskedinput("99/99/9999");
		$( "#data_final" ).datePicker({startDate : "01/01/2000"});

	});

	function pesquisaPosto(campo,tipo){
		var campo = campo.value;

		if (jQuery.trim(campo).length > 2){
			Shadowbox.open({
				content:	"posto_pesquisa_2_nv.php?"+tipo+"="+campo+"&tipo="+tipo,
				player:	"iframe",
				title:		"Pesquisa Posto",
				width:	800,
				height:	500
			});
		}else
			alert("Informe toda ou parte da informação para realizar a pesquisa!");
	}

	function pesquisaProduto(produto,tipo){

        if (jQuery.trim(produto.value).length > 2){
            Shadowbox.open({
                content:    "produto_pesquisa_nv.php?"+tipo+"="+produto.value,
                player: "iframe",
                title:      "Produto",
                width:  800,
                height: 500
            });
        }else{
            alert("Informe toda ou parte da informação para realizar a pesquisa!");
            produto.focus();
        }
    }

    function retorna_produto(produto,referencia,descricao, numero_serie, posicao){
        gravaDados("produto_referencia_"+posicao,referencia);
        gravaDados("produto_descricao_"+posicao,descricao);
        gravaDados("produto_serie_"+posicao,numero_serie);
    }

    function retorna_dados_produto(referencia,descricao,produto,linha,nome_comercial,voltagem,referencia_fabrica,garantia,ativo,valor_troca,troca_garantia,troca_faturada){
        gravaDados('produto_referencia',referencia);
        gravaDados('produto_descricao',descricao);
    }

	function retorna_posto(codigo_posto,posto,nome,cnpj,cidade,estado,credenciamento){
		gravaDados('posto_codigo',codigo_posto);
		gravaDados('posto_nome',nome);
	}

	function gravaDados(name, valor){
		try{
			$("input[name="+name+"]").val(valor);
		} catch(err){
			return false;
		}
	}

</script>

<?php include "javascript_calendario.php";?>

<?php

	if ( isset($_POST['gerar']) ) { // requisicao de relatorio

		$cond = array();

		if($_POST["data_inicial"]) $data_inicial = trim ($_POST["data_inicial"]);
		if($_POST["data_final"]) $data_final = trim($_POST["data_final"]);

		$os 	= (int) trim( $_POST['os'] );
		$os 	= ($os == 0) ? null : $os;

		if( ( empty($data_inicial) OR empty($data_final) ) && empty($os) )
			$msg_erro = "Data Inválida";

		if(strlen($msg_erro)==0 && !empty($data_inicial) && !empty($data_final)) {

			list($di, $mi, $yi) = explode("/", $data_inicial);
			list($df, $mf, $yf) = explode("/", $data_final);

			if(!checkdate($mi,$di,$yi) || !checkdate($mf,$df,$yf))
				$msg_erro = "Data Inválida";

			$aux_data_inicial = "$yi-$mi-$di";
			$aux_data_final = "$yf-$mf-$df";

			if(strtotime($aux_data_final) < strtotime($aux_data_inicial))
				$msg_erro = "Data Inválida.";

			if(strlen($msg_erro)==0)
				if (strtotime("$aux_data_inicial + 1 month" ) < strtotime($aux_data_final))
					$msg_erro = 'O intervalo entre as datas não pode ser maior que um mês.';

			if(empty($msg_erro)) {

				$cond[] = "AND tbl_os.data_abertura BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'";

			}

		}

		if (!empty($os)) {

			$sql = "SELECT os
					FROM tbl_os
					WHERE fabrica = $login_fabrica
					AND os = $os";

			$res = pg_query($con,$sql);

			if (pg_num_rows($res) == 0) {

				$msg_erro = "OS $os não Encontrada";

			}
			else {

				$cond[] = "AND tbl_os.os = $os";

			}

		}

		$codigo_posto 	= trim ($_POST['posto_codigo']);
		$nome_posto	=	trim ($_POST['posto_nome']);

		if ( ( !empty ($codigo_posto) || !empty($nome_posto) ) && empty($msg_erro) ) { // HD 720502

			$cond_posto = '';
			$cond_posto =  (!empty($codigo_posto) ) ? " AND tbl_posto_fabrica.codigo_posto =  '$codigo_posto' " : '';
			$cond_posto =  (!empty($nome_posto) && empty($codigo_posto) ) ? " AND tbl_posto.nome LIKE  '$nome_posto' " : $cond_posto;

			$sql = "SELECT posto
					FROM tbl_posto
					JOIN tbl_posto_fabrica USING(posto)
					WHERE fabrica = $login_fabrica
					$cond_posto";

			$res = pg_query($con,$sql);

			$total = pg_num_rows($res);

			if ( $total > 0 )  {

				$posto = pg_result($res,0,0);
				$cond[] = 'AND os2.posto =  ' . $posto;

			}
			else {

				$msg_erro = "Posto não Encontrado";

			}

		}

		$referencia = trim ($_POST['produto_referencia']);

		if ( !empty($referencia) && empty($msg_erro) ) {

		    $sql = "SELECT produto
		            FROM tbl_produto
		            JOIN tbl_linha USING(linha)
		            WHERE referencia = '$referencia'";

		    $res = pg_query($con,$sql);

		    if (pg_num_rows($res)) {
		        $produto 	= pg_result($res,0,0);
		        $cond[] 	= "AND tbl_hd_chamado_extra.produto = $produto";
		    }
		    else
		        $msg_erro = 'Produto '.$referencia.' não Encontrado';

		}

	}

?>

<div class="formulario" style="width:700px; margin:auto;">
	<div id="msg"></div>
	<div class="titulo_tabela">Parâmetros de Pesquisa</div>
	<form action="<?=$_SERVER['PHP_SELF'];?>" method="POST" name="frm_os">
		<table cellspacing="1" align="center" class="form">
			<tr>
				<td>
					<label for="os">OS</label><br />
					<input type="text" name="os" value="<?=$os?>" class="frm" id="os" size="15" />
				</td>
				<td>&nbsp;</td>
			</tr>
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
						Código do Posto<br />
						<input class="frm" type="text" id="posto_codigo" name="posto_codigo" size="15" value="<? echo $posto_codigo ?>">
						<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: pesquisaPosto (document.frm_os.posto_codigo, 'codigo');">
					</td>
					<td>
						Nome do Posto<br />
						<input class="frm" id="posto_nome" type="text" name="posto_nome" size="30" value="<? echo $posto_nome ?>">
						<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: pesquisaPosto (document.frm_os.posto_nome, 'nome');">
					</td>
			</tr>
			             <tr>
                 <td>
                     Referência Produto<br>
                     <input type="text" name="produto_referencia" id="produto_referencia" value="<?php echo $produto_referencia;?>" size="15" m
 axlength="20" class='frm' />
 					 <img src='../imagens/lupa.png' border='0' align='absmiddle' onclick="javascript: pesquisaProduto(document.frm_os.produto_referencia,'referencia');" alt='Clique aqui para pesquisar pela referência do produto' style='cursor:pointer;'>
                 </td>

                 <td>
                     Descrição Produto&nbsp;<br>
                     <input type="text" name="produto_descricao" id="produto_descricao" value="<?php echo $produto_descricao;?>" size="30"
  maxlength="50" class='frm'>&nbsp;<img src='../imagens/lupa.png' border='0' align='absmiddle' onclick="javascript: pesquisaProduto(document.frm_os.produto_descricao,'descricao')" alt='Clique aqui para pesquisar pela descrição do produto' style='cursor:pointer;'>
                 </td>
             </tr>
			<tr>
				<td colspan="2" style="padding-top:15px;padding-right:80px;" align="center">
					<input type="submit" name="gerar" value="Consultar" />
				</td>
			</tr>
		</table>
	</form>
</div>

<?php

	$link_xls = "xls/relatorio_pesquisa_satisfacao_$login_fabrica_" . date("d-m-y") . '.xls';

	if (file_exists($link_xls))
		exec("rm -f $link_xls");

	if ( is_dir("xls/") && is_writable("xls/") )
		$file = fopen($link_xls, 'a+');
	else
		$msg_erro = 'Sem Permissão de escrita / Pasta XLS não existe';

	if ( isset($_POST['gerar']) && empty($msg_erro) ) {

		$parametros = implode(" ", $cond);

		$sql = "SELECT 	tbl_os.os, tbl_os.sua_os ,
			tbl_produto.referencia || ' - ' || tbl_produto.descricao AS produto_descricao,
			tbl_defeito_constatado.descricao AS constatado_descricao, os2.sua_os as sua_os_origem, tbl_os_campo_extra.os_troca_origem
			FROM  	tbl_os
			JOIN tbl_os_campo_extra ON tbl_os_campo_extra.fabrica = tbl_os.fabrica AND tbl_os_campo_extra.os = tbl_os.os
			JOIN tbl_os os2 ON tbl_os_campo_extra.os_troca_origem = os2.os
			JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
			JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
			JOIN tbl_defeito_constatado ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado
			WHERE 	tbl_os.fabrica = $login_fabrica
			$parametros";
		$res = pg_query($con,$sql);

		if(pg_num_rows($res)) {

			ob_start();

			for($i = 0; $i < pg_num_rows($res); $i++) {

				$os 		= pg_result($res, $i, 'os');
				$sua_os 		= pg_result($res, $i, 'sua_os');
				$os_origem 	= pg_result($res,$i,'os_troca_origem');
				$sua_os_origem 	= pg_result($res,$i,'sua_os_origem');

				$sql = "SELECT tbl_posto.nome
						FROM tbl_os
						JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
						WHERE tbl_os.fabrica = $login_fabrica
						AND tbl_os.os = $os_origem";

				$res_posto = pg_query($con,$sql);

				$posto_orig = pg_result($res_posto,0,0);

				if ( $i == 0 ){

					echo '<br />
						<table class="tabela" cellspacing="1" align="center" style="min-width:700px;">
							<tr class="titulo_coluna">
								<th>Posto de Origem</th>
								<th>OS de Origem</th>
								<th>OS Posto Interno</th>
								<th>Produto</th>
								<th>Peças</th>
								<th>Defeito Constatado</th>
							</tr>';

				}

				$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

				$sql_pecas = "SELECT tbl_peca.referencia || ' - ' || tbl_peca.descricao AS peca_descricao
							  FROM   tbl_os
							  JOIN   tbl_os_produto USING(os)
							  JOIN   tbl_os_item USING(os_produto)
							  JOIN   tbl_peca USING(peca)
							  WHERE tbl_os.fabrica = $login_fabrica
							  AND tbl_os.os = $os";

				$res_pecas = pg_query($con,$sql_pecas);

				$pecas = array();

				for ($j = 0; $j < pg_num_rows($res_pecas); $j++) {

					$pecas[] = trim ( pg_result($res_pecas,$j,0) );

				}

				echo '<tr bgcolor="'.$cor.'">
						<td>'.$posto_orig.'</td>
						<td><a href="os_press.php?os='.$os_origem.'" target="_blank">'.$sua_os_origem.'</a></td>
						<td><a href="os_press.php?os='.$os.'" target="_blank">'.$sua_os.'</a></td>
						<td>'.pg_result($res,$i,'produto_descricao').'</td>
						<td>'.(implode(', ',$pecas)).'</td>
						<td>'.pg_result($res,$i,'constatado_descricao').'</td>
					  </tr>';

			}

			echo '</table>';

			$dados_relatorio = ob_get_contents();

			fwrite($file,$dados_relatorio);

		}

	} // fim request com sucesso

	else if ( isset($_POST['gerar']) ) { // Erro de validacao

		echo '<div id="erro" class="msg_erro" style="display:none;">'.$msg_erro.'</div>';

	}

	if ( isset ($file) && !empty($dados_relatorio) ) {

		echo "<button class='download' onclick=\"window.open('$link_xls') \">Download XLS</button>";
		fclose($file);

	}

	else if(empty($msg_erro) && isset($_POST['gerar']) ) {

		echo "Não foram encontrados resultados para essa pesquisa";

	}

?>

<script type="text/javascript">

	<?php if ( !empty($msg_erro) ){ ?>

			$("#erro").appendTo("#msg").fadeIn("slow");

	<?php } ?>

</script>

<?php include 'rodape.php'; ?>
