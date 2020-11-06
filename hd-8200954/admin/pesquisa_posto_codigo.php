<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

if (strlen($_GET['posto']) > 0) {
	$codposto = strtoupper($_GET['posto']);
	
	if ($_GET['tipo']=='codigo') { //Pesquisa pelo código
		$condicao = "tbl_posto_fabrica.codigo_posto ~* '$codposto'";
		$legenda  = 'código do posto';
	} else {
		$condicao = "tbl_posto.nome ~* '$codposto'";
		$legenda  = 'nome do posto';
	}
	$pesquisa_por = "Pesquisando pelo <b>$legenda</b>: <i>$codposto</i>";

	$sql = "SELECT  tbl_posto_fabrica.posto       ,
					tbl_posto_fabrica.codigo_posto,
					tbl_posto.nome                ,
					tbl_posto.cnpj                ,
					tbl_posto_fabrica.contato_cidade      AS cidade         ,
					tbl_posto_fabrica.contato_estado      AS estado         ,
					CASE WHEN credenciamento = 'CREDENCIADO'    THEN 'Sim'
					     WHEN credenciamento = 'DESCREDENCIADO' THEN 'Não'
						 ELSE SUBSTR(credenciamento, 1, 7)||'.'
					END  AS credenciado,
					tbl_posto.pais
			FROM    tbl_posto
			JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
			WHERE   tbl_posto_fabrica.fabrica = $login_fabrica
			AND     $condicao
			ORDER BY tbl_posto.nome;";
	$res = pg_exec ($con,$sql);
	
	$tot_resultados = (is_resource($res)) ? pg_num_rows($res) : 0; //Se não há erro, pega o total de registros

	if ($tot_resultados == 0) { ?>
<div style="float:left;width:97%;height:40px;background: url('css/table/imagem/ui-bg_highlight-soft_75_cccccc_1x100.png');"></div>
<div style="float:right;width:3%;background: url('css/table/imagem/ui-bg_highlight-soft_75_cccccc_1x100.png');height:40px;">
    <a href='#' onclick='closeMessage_1("");' width='50px' height='30px' alt='Fechar' 
    title='Fechar'>
    <img src='css/modal/excluir.png' border='0'/></a>
</div>
    
<div style="float:left;color:#596d9b;width:100%;height:40px;background: url('css/table/imagem/ui-bg_highlight-soft_75_cccccc_1x100.png');">
    <center><h3>Pesquisa de Posto Autorizado</h3></center>
</div>
<div class='demo_jui' style='width:100%;height:100%;' onmouseover='setTimeout("closeMessage_1()",2500);'>
		<h1><?echo ucfirst($legenda) . " '$codposto'";?> não encontrado</h1>
</div>
<?
		exit;
	} elseif ($tot_resultados == 1) {
		extract(pg_fetch_assoc($res, 0));
		echo "<div class='demo_jui' style='width:100%;height:100%;' onmouseover='retorno(\"$codigo_posto|$nome\");'></div>\n";
	} else {
?>
<div style="float:left;width:97%;height:40px;background: url('css/table/imagem/ui-bg_highlight-soft_75_cccccc_1x100.png');"></div>
<div style="float:right;width:3%;background: url('css/table/imagem/ui-bg_highlight-soft_75_cccccc_1x100.png');height:40px;">
    <a href='#' onclick='closeMessage_1("");' width='50px' height='30px' alt='Fechar' title='Fechar'>
    	<img src='css/modal/excluir.png' border='0'/>
	</a>
</div>
    
<div style="float:left;color:#596d9b;width:100%;height:40px;background: url('css/table/imagem/ui-bg_highlight-soft_75_cccccc_1x100.png');">
    <center><h3>Pesquisa de Posto Autorizado</h3></center>
</div>

<div class='demo_jui'>
    <table cellpadding='0' cellspacing='0' border='0' class='display' id='example'>
        <thead>
            <tr style='text-align:center;background-color:#596d9b;font: bold 14px Arial;color:#FFFFFF;'>
                <th width='100'>Código</th>
                <th width='256'>Razão Social</th>
                <th width='150'>Cidade</th>
                <th width='060'>Estado</th>
                <th width='064' title='Informa se o posto está Credenciado ou Não'>Cred.</th>
            </tr>
        </thead>
            
        <tbody>
<?
		$info_postos = pg_fetch_all($res);
		foreach($info_postos as $linha_posto) {
			extract($linha_posto);
			$cnpj = ($pais == 'BR') ? preg_replace('/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/', '$1.$2.$3/$4-$5', $cnpj) : $cnpj;
			switch ($credenciado) {
				case 'Sim':		$estilo_cred = " style='color:green'"; break;
				case 'Não':		$estilo_cred = " style='color:red'"; break;
				case 'EM CRED.':$estilo_cred = " style='color:navy'"; break;
				case 'EM DESC.':$estilo_cred = " style='color:darkred'"; break;
				default: $estilo_cred = '';
			}
?>
			<tr class='grade' style='font-weight:bold'>
				<td align='right'><a href='#' onclick="retorno('<?echo "$codigo_posto|$nome";?>');"><?=$codigo_posto?></a></td>
				<td align='left'><a href='#' onclick="retorno('<?echo "$codigo_posto|$nome";?>');"><?=$nome?></a></td>
				<td><?=$cidade?></td>
				<td align='center'><?=$estado?></td>
				<td align='center'<?=$estilo_cred?>><?=$credenciado?></td>
			</tr>
<?		}?>
        </tbody>
    </table>
</div>
<?  } //FIM Loop tabela postos
}?>
