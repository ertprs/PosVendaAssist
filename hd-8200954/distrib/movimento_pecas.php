<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';


$title='Movimento de Peças';

include 'autentica_usuario.php';

include "cabecalho.php";



if (strlen(trim($_POST["btn_acao"])) > 0) $btn_acao = trim($_POST["btn_acao"]);
if (strlen(trim($_GET["btn_acao"])) > 0)  $btn_acao = trim($_GET["btn_acao"]);

if (strlen(trim($_POST["referencia"])) > 0) $referencia = mb_strtoupper(trim($_POST["referencia"]));
if (strlen(trim($_GET["referencia"])) > 0)  $referencia = mb_strtoupper(trim($_GET["referencia"]));

if (strlen(trim($_POST["descricao"])) > 0) $descricao = trim($_POST["descricao"]);
if (strlen(trim($_GET["descricao"])) > 0)  $descricao = trim($_GET["descricao"]);

if($_GET['excel']=='true'){

    $di = '';
    $df = '';
    $cond_datas = '';

    if (!empty($_GET['di'])) {
        $di = $_GET['di'];
    }

    if (!empty($_GET['df'])) {
        $df = $_GET['df'];
    }

    if (!empty($di) and !empty($df)) {
        $cond_datas = " AND tbl_faturamento.emissao BETWEEN '{$di}' AND '{$df}' ";
    } else {
        if (!empty($di)) {
            $cond_datas = " AND tbl_faturamento.emissao >= '{$di}' ";
        }
        elseif (!empty($df)) {
            $cond_datas = " AND tbl_faturamento.emissao <= '{$df}' ";
        }
    }


		$sql = "select
				tbl_os.sua_os,
				tbl_faturamento.nota_fiscal,
				tbl_faturamento.cfop,
                tbl_faturamento.chave_nfe,
				TO_CHAR(tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao,
				tbl_tipo_pedido.descricao AS tipo_pedido_descricao,
				tbl_faturamento_item.qtde
			FROM tbl_faturamento
			JOIN tbl_faturamento_item using (faturamento)
			JOIN tbl_peca ON tbl_peca.peca = tbl_faturamento_item.peca
			JOIN tbl_fabrica ON tbl_peca.fabrica = tbl_fabrica.fabrica and ativo_fabrica
			JOIN tbl_tipo_pedido ON tbl_faturamento.tipo_pedido=tbl_tipo_pedido.tipo_pedido
			LEFT JOIN tbl_os_item ON tbl_faturamento_item.os_item = tbl_os_item.os_item
			LEFT JOIN tbl_os_produto USING(os_produto)
			LEFT JOIN tbl_os  ON tbl_os_produto.os = tbl_os.os
			WHERE tbl_faturamento.fabrica in ($telecontrol_distrib)
			AND tbl_faturamento.distribuidor in ($login_distrib_postos)
			AND tbl_peca.referencia = '$referencia'
			$cond_datas
			ORDER BY tbl_faturamento.emissao,tbl_faturamento.nota_fiscal ASC";
		$res = pg_exec ($con,$sql);

		if(pg_numrows ($res)>0){
            $gerar_zip = $_GET['zip'];

			$data = date("d-m-Y-Hi");
			$fileName = "relatorio_movimento-{$data}.xls";
			$arq = fopen("/tmp/{$fileName}", "w");

			if ($gerar_zip == "true") {
                $dir_orig = dirname(__FILE__) . '/../nfephp2/arquivos/producao/pdf';
                $dir_dest = '/tmp/relatorio_movimento-danfes-' . date('Ymd');
                system("mkdir -p {$dir_dest}");
            }

			$file= "<br><table align='center' border='0' cellspacing='1' cellpaddin='1'>";
			$file.= "<tr bgcolor='#663366' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'><td colspan='5'>MOVIMENTO DE SAÍDA DE PEÇAS</td></tr>";
			$file.= "<tr bgcolor='#663366' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>";
			$file.= "<td>OS</td>";
			$file.= "<td>Nota Fiscal</td>";
			$file.= "<td>Emissão</td>";
            $file.= "<td>Chave NFe</td>";
			$file.= "<td>Tipo Pedido</td>";
			$file.= "<td>Qtde</td>";
			$file.= "</tr>";

			$total_qtde = 0;
			$arr_nota_fiscal = array();

			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				$arr_nota_fiscal[] = trim(pg_fetch_result($res, $i, 'nota_fiscal'));

				$total_qtde += pg_result($res, $i, qtde);
				$chave_nfe = pg_fetch_result($res, $i, 'chave_nfe');

				$cor = "#eeeeee";
				if (($i%2) == 0) $cor = '#cccccc';

				$file.= "<tr bgcolor='$cor'>";

				$file.= "<td align='center' title='Tipo do pedido'>&nbsp;";
				$file.= pg_result ($res,$i,'sua_os');
				$file.= "</td>";


				$file.= "<td title='Número da nota fiscal'>";
				$file.= pg_result ($res,$i,nota_fiscal);
				$file.= "-";
				$file.= pg_result ($res,$i,cfop);
		//		if (strlen (trim (pg_result ($res,$i,para_referencia))) > 0) $file.= "<br>" . pg_result ($res,$i,para_referencia);
				$file.= "</td>";

				$file.= "<td title='Data emissão'>";
				$file.= pg_result ($res,$i,emissao);
		//		if (strlen (trim (pg_result ($res,$i,para_referencia))) > 0) $file.= "<br>" . pg_result ($res,$i,para_descricao);
				$file.= "</td>";

                if (!empty($chave_nfe)) {
                    $file.= "<td nowrap>Chave NFe: ";
                    $file.= $chave_nfe;
                    $file.= "</td>";

                    if ($gerar_zip == "true") {
                        system("[ ! -f {$dir_dest}/{$chave_nfe}.pdf ] && [ -f {$dir_orig}/{$chave_nfe}.pdf ] && cp {$dir_orig}/{$chave_nfe}.pdf $dir_dest");
                    }
                } else {
                    $file.= '<td>&nbsp;</td>';
                }

				$file.= "<td align='center' title='Tipo do pedido'>&nbsp;";
				$file.= pg_result ($res,$i,tipo_pedido_descricao);
				$file.= "</td>";

				$qtde_fabrica = pg_result ($res,$i,qtde);
				if ($qtde_fabrica < 0) $qtde_fabrica = 0;


				$file.= "<td align='center' title='Quantidade'>&nbsp;";
				$file.= pg_result ($res,$i,qtde);
				$file.= "</td>";

				$file.= "</tr>";
			}

			//HD 211681: Totalizar as saídas
			$file.= "
			<tr bgcolor='#663366' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>
			<td>TOTAIS</td>
			<td></td>
			<td></td>
			<td></td>
			<td>$total_qtde</td>
			</tr>";
			$file.= "</table>";
			fwrite($arq,$file);
			fclose($arq);

			$zip = $dir_dest . '.zip';
			$dir_arqs = basename($dir_dest);

			if ($gerar_zip == "true") {
                system('[ "$(ls -A ' . $dir_dest . ')" ] && cd /tmp && zip -qr ' . $zip . ' ' . $dir_arqs );
                system("[ -f $zip ] && rm $dir_dest/*.pdf && rmdir $dir_dest");
            }

            $array_retorno = array();

			if (file_exists("/tmp/{$fileName}")) {
                system("mv /tmp/{$fileName} ../xls/{$fileName}");
                $array_retorno['xls'] = '../xls/'. $fileName;
            }

            if (file_exists($zip)) {
                $array_retorno['zip'] = '../xls/' . basename($zip);
                system("mv $zip {$array_retorno['zip']}");
            }

            header('Content-type: application/json');
            die(json_encode($array_retorno));
		}

}
?>


<html>
<head>
<title>Movimento de Peças</title>
<link type="text/css" rel="stylesheet" href="css/css.css">

<?php include "javascript_calendario_new.php";?>
<script>
	$(function(){
        $("#gera_inicial").maskedinput("99/99/9999");
        $("#gera_final").maskedinput("99/99/9999");

		$('#gerar_excel').click(function(){
            var zip = '';
            var a_d_in = '';
            var a_d_fn = '';
            var datas = '';
            var d_in = $("#gera_inicial").val();
            var d_fn = $("#gera_final").val();

            if ($("#gerar_zip").is(":checked")) {
                zip = '&zip=true';
            }

            if (d_in) {
                var a_d_in = d_in.split('/');
                datas = datas + '&di=' + a_d_in[2] + '-' + a_d_in[1] + '-' + a_d_in[0];
            }

            if (d_fn) {
                var a_d_fn = d_fn.split('/');
                datas = datas + '&df=' + a_d_fn[2] + '-' + a_d_fn[1] + '-' + a_d_fn[0];
            }

			$.ajax({
				type: "GET",
				url:  "<?$PHP_SELF?>",
				data: "excel=true&referencia=<?=$referencia?>" + zip + datas,
				cache: false,
				beforeSend: function(){
					$('#msg').html("Gerando Arquivo, aguarde");
				},
				complete:function(resposta){
					results = $.parseJSON(resposta.responseText);
					$('#msg').html("");

                    if (results.xls) {
                        window.open(results.xls);
                    }

                    if (results.zip) {
                        window.open(results.zip);
                    }
				}
			})

		})
	});
</script>
</head>

<body>

<? include 'menu.php' ?>


<center><h1>Movimento de Peças</h1></center>

<?


if (strlen($msg_erro) > 0) { ?>
	<table width="600" border="0" cellspacing="0" cellpadding="2" align="center" class="Error">
		<tr>
			<td><?echo $msg_erro?></td>
		</tr>
	</table>
	<br>
	<?
}
?>


<p>

<center>
<form name='frm_estoque' action='<? echo $PHP_SELF ?>' method='post'>

Referência da Peça <input type='text' size='10' name='referencia' class='frm' onFocus="this.className='frm-on'; " onBlur="this.className='frm';" value='<?=$referencia?>'>
Descrição da Peça <input type='text' size='30' name='descricao' class='frm' onFocus="this.className='frm-on'; " onBlur="this.className='frm';" value='<?=$descricao?>'>
<br>
<input type='submit' name='btn_acao' value='Pesquisar' class='frm'>

</form>
</center>


<?

flush();

if (strlen($btn_acao)>0 and strlen($msg_erro)==0){
	if (strlen ($descricao) > 2) {
		$sql = "SELECT tbl_peca.referencia, tbl_peca.descricao, tbl_posto_estoque.qtde, fabrica.qtde_fabrica, transp.qtde_transp, para.referencia AS para_referencia, para.descricao AS para_descricao, tbl_posto_estoque_localizacao.localizacao
				FROM   tbl_peca
				LEFT JOIN tbl_posto_estoque             ON tbl_peca.peca = tbl_posto_estoque.peca AND tbl_posto_estoque.posto in ($login_distrib_postos)
				LEFT JOIN tbl_posto_estoque_localizacao ON tbl_peca.peca = tbl_posto_estoque_localizacao.peca AND tbl_posto_estoque_localizacao.posto in ($login_distrib_postos)
				LEFT JOIN tbl_depara ON tbl_peca.peca = tbl_depara.peca_de
				LEFT JOIN tbl_peca para ON tbl_depara.peca_para = para.peca
				LEFT JOIN (SELECT peca, SUM (qtde - qtde_faturada - qtde_cancelada) AS qtde_fabrica FROM tbl_pedido_item JOIN tbl_pedido USING (pedido)

				WHERE (

				(tbl_pedido.posto in ($login_distrib_postos) AND tbl_pedido.tipo_pedido in (2,153) )
				OR (tbl_pedido.distribuidor in ($login_distrib_postos) AND tbl_pedido.tipo_pedido in (3,154) )
				OR tbl_pedido.fabrica in ($telecontrol_distrib)
				)
				AND tbl_pedido.fabrica in ($telecontrol_distrib) GROUP BY tbl_pedido_item.peca) fabrica ON tbl_peca.peca = fabrica.peca
				LEFT JOIN (SELECT peca, SUM (qtde) AS qtde_transp FROM tbl_faturamento_item JOIN tbl_faturamento USING (faturamento) WHERE tbl_faturamento.posto in ($login_distrib_postos) AND tbl_faturamento.fabrica in ($telecontrol_distrib) AND tbl_faturamento.conferencia IS NULL GROUP BY tbl_faturamento_item.peca) transp ON tbl_peca.peca = transp.peca
				WHERE  (tbl_posto_estoque.posto in ($login_distrib_postos) OR tbl_posto_estoque.posto IS NULL)
				AND    (tbl_peca.descricao ILIKE '%$descricao%' OR para.descricao ILIKE '%$descricao%')
				AND    tbl_peca.fabrica in ($telecontrol_distrib)
				ORDER BY tbl_peca.descricao";
		$res = pg_exec ($con,$sql);

		if(pg_numrows ($res)>0){
			echo "<table align='center' border='0' cellspacing='1' cellpadding='1'  bordercolor='#000000' >";
			echo "<tr bgcolor='#663366' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>";
			echo "<td>Referência</td>";
			echo "<td>Descrição</td>";
			echo "<td>Estoque</td>";
			echo "<td>Fábrica</td>";
			echo "<td>Transp.</td>";
			echo "<td>Localização</td>";
			echo "</tr>";

			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

				$cor = "#eeeeee";
				if (strlen (trim (pg_result ($res,$i,para_referencia))) > 0) $cor = '#cccccc';

				echo "<tr bgcolor='$cor'>";

				echo "<td>";
				echo pg_result ($res,$i,referencia);
				if (strlen (trim (pg_result ($res,$i,para_referencia))) > 0) echo "<br>" . pg_result ($res,$i,para_referencia);
				echo "</td>";

				echo "<td>";
				echo pg_result ($res,$i,descricao);
				if (strlen (trim (pg_result ($res,$i,para_referencia))) > 0) echo "<br>" . pg_result ($res,$i,para_descricao);
				echo "</td>";

				echo "<td align='right'>&nbsp;";
				echo pg_result ($res,$i,qtde);
				echo "</td>";

				$qtde_fabrica = pg_result ($res,$i,qtde_fabrica);
				if ($qtde_fabrica < 0) $qtde_fabrica = 0;

				echo "<td align='right'>&nbsp;";
				echo $qtde_fabrica;
				echo "</td>";

				echo "<td align='right'>&nbsp;";
				echo pg_result ($res,$i,qtde_transp);
				echo "</td>";

				echo "<td align='left'>&nbsp;";
				echo pg_result ($res,$i,localizacao);
				echo "</td>";

				echo "</tr>";
			}
			echo "</table>";
			exit;
		}else echo "<center><b><span class='vermelho'>$descricao </span> - NENHUM PRODUTO COM ESSA DESCRIÇÃO FOI ENCONTRADO</center></b>";
	}

	//SE ENTRAR COM O CÓDIGO DE REFERENCIA IRA FAZER OS COMANDOS ABAIXO
	if (strlen ($referencia) > 2 ) {
		$sql = "SELECT tbl_peca.referencia, tbl_peca.descricao
				FROM tbl_peca
				WHERE tbl_peca.referencia = '$referencia'
				AND    tbl_peca.fabrica in ($telecontrol_distrib)
				ORDER BY tbl_peca.descricao";

		$res = pg_exec ($con,$sql);
		if(pg_numrows ($res)==0){
			echo "<center><b><span class='vermelho'>$referencia </span>- CÓDIGO DE PEÇA NÃO CADASTRADO</center></b><br>";
			exit;
		}else
			echo '<center><b><h3>'.pg_result ($res,0,referencia).' - '.pg_result ($res,0,descricao).'</h3></center></b>';

	//************ENTRADA DE PEÇAS*************//
		$sql = "SELECT  tbl_faturamento.nota_fiscal,tbl_faturamento.cfop,
						SUM (tbl_faturamento_item.qtde) AS qtde,
						SUM (tbl_faturamento_item.qtde_estoque) AS qtde_estoque,
						SUM (tbl_faturamento_item.qtde_quebrada) AS qtde_quebrada,
						TO_CHAR(tbl_faturamento.emissao,'DD/MM/YYYY')as emissao,
						TO_CHAR(tbl_faturamento.conferencia,'DD/MM/YYYY')as conferencia,
						substr(tbl_posto.nome,1,30) as nome
				FROM tbl_faturamento
				JOIN tbl_faturamento_item using (faturamento)
				JOIN tbl_peca ON tbl_peca.peca = tbl_faturamento_item.peca
				JOIN tbl_fabrica ON tbl_peca.fabrica = tbl_fabrica.fabrica and ativo_fabrica
				JOIN tbl_posto ON tbl_faturamento.distribuidor = tbl_posto.posto
				WHERE  tbl_faturamento.posto in ($login_distrib_postos)
				AND (
					tbl_faturamento.distribuidor IN (
						SELECT DISTINCT distribuidor FROM tbl_faturamento WHERE fabrica = 10 AND posto in ($login_distrib_postos)
						and distribuidor is not null and distribuidor not in ($login_distrib_postos))
					OR
					tbl_faturamento.fabrica in ($telecontrol_distrib)
					AND tbl_faturamento.distribuidor is null
				)
				AND tbl_peca.referencia = '$referencia'
				AND tbl_faturamento.cancelada IS NULL
				AND tbl_faturamento.fabrica <> 0
				AND (tbl_faturamento.tipo_nf = 0 or tbl_faturamento.tipo_nf IS NULL)
				GROUP BY tbl_faturamento.nota_fiscal,tbl_faturamento.cfop,tbl_faturamento.emissao, tbl_faturamento.conferencia,tbl_posto.nome
				ORDER BY tbl_faturamento.emissao,tbl_faturamento.nota_fiscal ASC";
		$res = pg_exec ($con,$sql);

		$total_qtde = 0;
		$total_qtde_estoque = 0;
		$total_qtde_quebrada = 0;

		if(pg_numrows ($res)>0){

			echo "<br><table align='center'border='0' cellspacing='1' cellpaddin='1' >";
			echo"<tr bgcolor='#663366' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'><td colspan='100%'>MOVIMENTO DE ENTRADA DE PEÇAS</td></tr>";
			echo "<tr bgcolor='#663366' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>";
			echo "<td>Distribuidor</td>";
			echo "<td>Nota Fiscal</td>";
			echo "<td>Emissão</td>";
			echo "<td>Conferencia</td>";
			echo "<td>Qtde</td>";
			echo "<td>Qtde Estoque</td>";
			echo "<td>Qtde Quebrada</td>";
			echo "</tr>";

			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

				$total_qtde += pg_result($res, $i, qtde);
				$total_qtde_estoque += pg_result($res, $i, qtde_estoque);;
				$total_qtde_quebrada += pg_result($res, $i, qtde_quebrada);;
				$cor = "#eeeeee";
				if (($i%2) == 0) $cor = '#cccccc';

				echo "<tr bgcolor='$cor'>";

				echo "<td title='Data emissão'>";
				echo pg_result ($res,$i,nome);
				echo "</td>";

				echo "<td title='Número da nota fiscal'>";
				echo pg_result ($res,$i,nota_fiscal);
				echo "-";
				echo pg_result ($res,$i,cfop);
		//		if (strlen (trim (pg_result ($res,$i,para_referencia))) > 0) echo "<br>" . pg_result ($res,$i,para_referencia);
				echo "</td>";

				echo "<td title='Data emissão'>";
				echo pg_result ($res,$i,emissao);
		//		if (strlen (trim (pg_result ($res,$i,para_referencia))) > 0) echo "<br>" . pg_result ($res,$i,para_descricao);
				echo "</td>";

				echo "<td align='right'title='Data conferência'>&nbsp;";
				echo pg_result ($res,$i,conferencia);
				echo "</td>";

				$qtde_fabrica = pg_result ($res,$i,qtde);
				if ($qtde_fabrica < 0) $qtde_fabrica = 0;

				echo "<td align='center' title='Quantidade'>&nbsp;";
				echo pg_result ($res,$i,qtde);
				echo "</td>";
				echo "<td align='center'title='Quantidade em Estoque'>&nbsp;";
				echo pg_result ($res,$i,qtde_estoque);
				echo "</td>";

				echo "<td align='center'title='Quantidade Quebrada'>&nbsp;";
				echo pg_result ($res,$i,qtde_quebrada);
				echo "</td>";

				echo "</tr>";
			}
			echo "<tr bgcolor='#663366' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>";
			echo "<td colspan='4'>TOTAIS</td>";
			echo "<td>$total_qtde</td>";
			echo "<td>$total_qtde_estoque</td>";
			echo "<td>$total_qtde_quebrada</td>";
			echo "</tr>";
			echo "</table>";
		}else{
			echo "<br><center><b> MOVIMENTO DE ENTRADA DE PEÇAS</center></b>";
			echo "<CENTER><span class='vermelho'> Não foi encontrado movimento de ENTRADA de Peças </span></CENTER>";
			}


	//**************SAIDA DE PEÇAS*************//
		echo '<p>
                <div id="gerar_excel" class="btn_excel" style="cursor: pointer">
                    <img src="http://posvenda.telecontrol.com.br/assist/admin/imagens/excel.png" height="25" />
                    Gerar Arquivo Excel
                </div>
                <div style="font-size: 10px;">
                    <input type="checkbox" id="gerar_zip" checked="checked" /> Gerar arquivo zip das DANFEs
                </div>
                <div style="margin-top: 20px;">
                    Data inicial: <input type="input" id="gera_inicial" style="width: 100px;" />
                    &nbsp;&nbsp;&nbsp;
                    Data final: <input type="input" id="gera_final" style="width: 100px;" />
                </div>
				<div id="msg" style="margin-top: 10px;"></div>
			</div></p>';

		$sql = "SELECT
						tbl_faturamento.nota_fiscal,
						tbl_faturamento.cfop,
						tbl_faturamento.chave_nfe,
						TO_CHAR(tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao,
						tbl_tipo_pedido.descricao AS tipo_pedido_descricao,
						tbl_faturamento_item.preco,
						SUM(tbl_faturamento_item.qtde) AS qtde,
						tbl_posto.nome, 
						tbl_faturamento.natureza
				FROM tbl_faturamento
				JOIN tbl_faturamento_item using (faturamento)
				JOIN tbl_peca ON tbl_peca.peca = tbl_faturamento_item.peca
				JOIN tbl_fabrica ON tbl_peca.fabrica = tbl_fabrica.fabrica and ativo_fabrica
				JOIN tbl_posto ON tbl_posto.posto = tbl_faturamento.distribuidor
				LEFT JOIN tbl_pedido ON tbl_faturamento_item.pedido = tbl_pedido.pedido
				LEFT JOIN tbl_tipo_pedido ON tbl_faturamento.tipo_pedido=tbl_tipo_pedido.tipo_pedido
				WHERE tbl_faturamento.fabrica in ($telecontrol_distrib)
				AND tbl_faturamento.distribuidor in ($login_distrib_postos)
				AND tbl_peca.referencia = '$referencia'
				AND (tbl_faturamento.status_nfe='100' or tbl_faturamento.status_nfe isnull)
				/*AND (tbl_faturamento.tipo_pedido notnull or os notnull or (tbl_faturamento.tipo_pedido isnull and os isnull and tbl_pedido.tipo_pedido notnull)) HD-2416428*/
				GROUP BY
				tbl_faturamento.nota_fiscal,
				tbl_faturamento.cfop,
				tbl_faturamento.chave_nfe,
				tbl_faturamento.emissao,
				tbl_tipo_pedido.descricao,
				tbl_faturamento_item.peca,
				tbl_faturamento_item.preco,
				tbl_posto.nome, 
				tbl_faturamento.natureza
				ORDER BY tbl_faturamento.emissao,tbl_faturamento.nota_fiscal ASC";
		$res = pg_exec ($con,$sql);


		if(pg_numrows ($res)>0){
			echo "<br><table align='center' border='0' cellspacing='1' cellpaddin='1'>";
			echo "<tr bgcolor='#663366' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'><td colspan='100%'>MOVIMENTO DE SAÍDA DE PEÇAS</td></tr>";
			echo "<tr bgcolor='#663366' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>";
            echo "<td>Distribuidor</td>";
            echo "<td>OS</td>";
			echo "<td>Nota Fiscal</td>";
			echo "<td>Emissão</td>";
			echo "<td>PDF</td>";
			echo "<td>Tipo Pedido</td>";
			echo "<td>Qtde</td>";
			echo "<td>Preço</td>";
			echo "<td>Total</td>";
			echo "</tr>";

			$total_qtde = 0;
			$arr_nota_fiscal = array();

			$sql_prepare = "SELECT tbl_os.os, tbl_os.sua_os
                            FROM tbl_faturamento
                            JOIN tbl_faturamento_item using (faturamento)
                            JOIN tbl_peca ON tbl_peca.peca = tbl_faturamento_item.peca
							JOIN tbl_fabrica ON tbl_peca.fabrica = tbl_fabrica.fabrica and ativo_fabrica
                            JOIN tbl_tipo_pedido ON tbl_faturamento.tipo_pedido=tbl_tipo_pedido.tipo_pedido
                            LEFT JOIN tbl_os_item ON tbl_faturamento_item.os_item = tbl_os_item.os_item
                            LEFT JOIN tbl_os_produto USING(os_produto)
                            LEFT JOIN tbl_os  ON tbl_os_produto.os = tbl_os.os
                            WHERE tbl_faturamento.fabrica in ($telecontrol_distrib)
                            AND tbl_faturamento.distribuidor in ($login_distrib_postos)
                            AND tbl_peca.referencia = '{$referencia}'
                            AND tbl_faturamento.nota_fiscal = $1
			    UNION
			    SELECT tbl_os.os, tbl_os.sua_os
			    FROM tbl_faturamento
			    JOIN tbl_faturamento_item USING(faturamento)
                JOIN tbl_peca ON tbl_peca.peca = tbl_faturamento_item.peca
				JOIN tbl_fabrica ON tbl_peca.fabrica = tbl_fabrica.fabrica and ativo_fabrica
			    JOIN tbl_os USING(os)
			    WHERE tbl_faturamento.fabrica in ($telecontrol_distrib)
			    AND tbl_faturamento.distribuidor in ($login_distrib_postos)
			    AND tbl_faturamento.posto ISNULL
			    AND tbl_peca.referencia='{$referencia}'
			    AND tbl_faturamento.nota_fiscal = $1   "  ;
			    $prepare = pg_prepare($con, "qryos", $sql_prepare);

			    for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				$arr_nota_fiscal[] = trim(pg_fetch_result($res, $i, 'nota_fiscal'));

				$total_qtde += pg_result($res, $i, qtde);
				$total_preco+= pg_result($res, $i, preco);
				$chave_nfe = pg_fetch_result($res, $i, 'chave_nfe');
				$nota_fiscal = pg_fetch_result($res, $i, 'nota_fiscal');
				$natureza = pg_fetch_result($res, $i, 'natureza');

				$execute = pg_execute($con, "qryos", array($nota_fiscal));
				$oss = null;
				$oss = array();

				if (pg_num_rows($execute) > 0) {
				    while ($fetch = pg_fetch_assoc($execute)) {
					$oss[] = $fetch['sua_os'];
				    }
				}

				$cor = "#eeeeee";
				if (($i%2) == 0) $cor = '#cccccc';

				echo "<tr bgcolor='$cor'>";

				echo "<td title='Data emissão'>";
				echo pg_result ($res,$i,nome);
				echo "</td>";

				echo "<td title='OS' align='center'>" , implode('<br/>', $oss) , "</td>";

				echo "<td title='Número da nota fiscal'>";
				echo pg_result ($res,$i,nota_fiscal);
				echo "-";
				echo pg_result ($res,$i,cfop);
				echo "</td>";

				echo "<td title='Data emissão'>";
				echo pg_result ($res,$i,emissao);
				echo "</td>";

				echo "<td title='Visualizar NF' align='center' style='height: 25px'>";
				if (!empty($chave_nfe) and file_exists("../nfephp2/arquivos/producao/pdf/$chave_nfe.pdf")) {
				    echo '<a target="_blank" href="http://ww2.telecontrol.com.br/assist/nfephp2/arquivos/producao/pdf/' . $chave_nfe . '.pdf"><img src="../admin/imagens/pdf_icone.gif" height="25" weight="25" /></a>';
				} else {
				    echo '&nbsp';
				}
				echo "</td>";
				$tipo_pedido = (count($oss) > 0 and !empty($oss[0]) ) ? 'Garantia' : pg_fetch_result($res,$i,'tipo_pedido_descricao');
				$tipo_pedido = ($natureza == 'DEVOLUCAO') ? 'DEVOLUÇÃO' :  $tipo_pedido; 
				echo "<td align='center' title='Tipo do pedido'>&nbsp;";
				echo $tipo_pedido;
				echo "</td>";

				$qtde_fabrica = pg_result ($res,$i,qtde);
				if ($qtde_fabrica < 0) $qtde_fabrica = 0;

				$qtde_item = pg_result ($res,$i,qtde);
				$preco     = pg_result ($res,$i,preco);
				$total_item = $qtde_item * $preco ;
				$ttl_item += $total_item;

				echo "<td align='center' title='Quantidade'>&nbsp;";
				echo pg_result ($res,$i,qtde);
				echo "</td>";

				echo "<td align='center' title='preco'>&nbsp;";
				echo pg_result ($res,$i,preco);
				echo "</td>";

				echo "<td>$total_item</td>";

				echo "</tr>";
			}

			//HD 211681: Totalizar as saídas
			echo "
			<tr bgcolor='#663366' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>
			<td colspan='6' align='center' style='padding-right: 10px;'>TOTAIS</td>
			<td>$total_qtde</td>
			<td>$total_preco</td>
			<td>$ttl_item</td>
			</tr>";
			echo "</table>";

		}else{
			echo "<br><center><b> MOVIMENTO DE SAÍDA DE PEÇAS</center></b>";
			echo "<CENTER><span class='vermelho'> Não foi encontrado Movimento de SAÍDA de Peças </span></CENTER>";

		}


	//**************ACERTO************//
		$sql = "SELECT  tbl_posto_estoque_acerto.posto_estoque_acerto,
						tbl_posto_estoque_acerto.qtde,
						tbl_posto_estoque_acerto.motivo,
						TO_CHAR(tbl_posto_estoque_acerto.data,'DD/MM/YYYY')AS data,
						tbl_peca.peca,
						tbl_posto.nome,
						tbl_login_unico.nome as login_unico_nome
				FROM tbl_posto_estoque_acerto
				JOIN tbl_posto USING (posto)
				JOIN tbl_peca ON tbl_peca.peca = tbl_posto_estoque_acerto.peca
				JOIN tbl_fabrica ON tbl_peca.fabrica = tbl_fabrica.fabrica and ativo_fabrica
				LEFT JOIN tbl_login_unico USING (login_unico) 
				WHERE tbl_peca.referencia = '$referencia'
				AND motivo NOT LIKE 'Localização DE:%'
				ORDER BY tbl_posto_estoque_acerto.data ASC";
	
		$res = pg_exec ($con,$sql);

		if(pg_numrows ($res)>0){
			echo "<br><table align='center' border='0' cellspacing='1' cellpaddin='1'>";
			echo "<tr bgcolor='#663366' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'><td colspan='100%'>MOVIMENTO DE ACERTO DE PEÇAS</td></tr>";
			echo "<tr bgcolor='#663366' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>";
			echo "<td>Distribuidor</td>";
			echo "<td>Registro</td>";
			echo "<td>Qtde</td>";
			echo "<td>Motivo</td>";
			echo "<td>Data</td>";
			echo "<td>Admin</td>";
			echo "</tr>";

			/**
			 *
			 * HD 922107 - o Ronaldo pediu para retirar os acertos que foram para atender
			 *   OS e que já estão na relação de saídas. Não existe uma relação de fato
			 *   da tbl_posto_estoque_acerto com notaf_fiscal e OS, por isto usa uma
			 *   "padronização" do campo motivo
			 */
			$pr = pg_prepare($con, "check_os", "SELECT tbl_faturamento.nota_fiscal from tbl_faturamento join tbl_faturamento_item on tbl_faturamento_item.faturamento = tbl_faturamento.faturamento join tbl_os on tbl_os.os = tbl_faturamento_item.os where tbl_os.sua_os = $1 and tbl_faturamento_item.peca = $2");

			function clean($str) {
				return str_replace(".", "", trim(strtoupper($str)));
			}

			$total_qtde = 0;

			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				$nome = pg_fetch_result($res, $i, nome);
				$login_unico_nome = pg_fetch_result($res, $i, login_unico_nome);
				$motivo = pg_fetch_result($res, $i, motivo);
				$peca = pg_fetch_result($res, $i, peca);
				$exp_motivo = explode(" ", $motivo);
				$arr_motivo = array_map("clean", $exp_motivo);
				$k = array_search("OS", $arr_motivo);

				if ($k !== false) {
					$v_os = $arr_motivo[$k + 1];
					$pexec = pg_execute($con, "check_os", array($v_os, $peca));
					if (pg_num_rows($pexec) > 0) {
						$vnf = trim(pg_fetch_result($pexec, 0, 'nota_fiscal'));
						if (in_array($vnf, $arr_nota_fiscal)) {
							continue;
						}
					}
				}

				$total_qtde += pg_result($res, $i, qtde);

				$cor = "#eeeeee";
				if (($i%2) == 0) $cor = '#cccccc';

				echo "<tr bgcolor='$cor'>";

				echo "<td align='center' title='Quantidade'>&nbsp;";
				echo $nome;
				echo "</td>";

				echo "<td align='center' title='Código da peça'>&nbsp;";
				echo pg_result ($res,$i,posto_estoque_acerto);
				echo "</td>";

				$qtde_fabrica = pg_result ($res,$i,qtde);
				if ($qtde_fabrica < 0) $qtde_fabrica = 0;

				echo "<td align='center' title='Quantidade'>&nbsp;";
				echo pg_result ($res,$i,qtde);
				echo "</td>";

				echo "<td align='left' title='Motivo'>&nbsp;";
				echo $motivo;
				echo "</td>";

				echo "<td align='center' title='Data'>&nbsp;";
				echo pg_result ($res,$i,data);
				echo "</td>";

				echo "<td align='center' title='Admin'>&nbsp;";
				echo $login_unico_nome;
				echo "</td>";

				echo "</tr>";
			}
			echo "
			<tr bgcolor='#663366' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>
			<td colspan='2'>TOTAIS</td>
			<td>$total_qtde</td>
			<td></td>
			<td></td>
			<td></td>
			</tr>";
			echo "</table>";
		}else{
			echo "<br><center><b> MOVIMENTO ACERTO</center></b>";
			echo "<span class='vermelho'><CENTER>Não foi encontrado Movimento de ACERTO de Peças </CENTER></span>";
		}
	}
}

?>


<? #include "rodape.php"; ?>

</body>
</html>
<?
include'rodape.php';
?>
