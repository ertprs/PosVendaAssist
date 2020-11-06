<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';


?>

<style>

</style>
<body>

<? include 'menu.php' ; ?>

<center><h1><?echo $title;?></h1></center>

<p>
	<center>
	<form name='frm_estoque' action='<? echo $PHP_SELF ?>' method='POST'>
	<table>
		<caption>Relatorio gerado com data fixa de 01/07/2013 a 20/08/2014</caption>
		<tr>
			<td align='right'>Fábrica</td>
			<td align='left'>
			<?
			echo "<select style='width:120px;' name='fabrica' id='fabrica' class='frm'>";
				$sql = "SELECT fabrica,nome FROM tbl_fabrica WHERE fabrica IN ($telecontrol_distrib) ORDER BY nome";
				$res = pg_exec($con,$sql);
				if(pg_numrows($res)>0){
					for($x = 0; $x < pg_numrows($res);$x++) {
						$aux_fabrica = pg_fetch_result($res,$x,fabrica);
						$aux_nome    = pg_fetch_result($res,$x,nome);
						echo "<option value='$aux_fabrica'" ;if($fabrica==$aux_fabrica) echo "selected"; echo ">$aux_nome</option>";
					}
				}
			echo "</select>";
			?>
			</td>
		</tr>
		<tr>
			<td align='center' colspan='6'>Tipo <input type='radio' name='tipo' value='peca'>Peça <input type='radio' name='tipo' value='produto'>Produto Acabado</td>
		</tr>
		<tr>
			<td align='center' colspan='6'><input type='submit' name='btn_acao' id='btn_acao' value='Pesquisar'></td>
		</tr>
	</table>
	<br/>

<?php

if(!empty($btn_acao) and empty($msg_erro) ) {
$fabrica = $_POST['fabrica'];
$tipo = $_POST['tipo'];
$cond = ($tipo=='peca') ? " and produto_acabado is not true " : " and produto_acabado "; 

$sql = "SELECT distinct tbl_peca.peca,
               tbl_peca.descricao,
			   tbl_peca.referencia
	   FROM tbl_peca
		JOIN tbl_faturamento_item USING(peca)
		JOIN tbl_faturamento USING(faturamento)
		WHERE tbl_faturamento.fabrica = 10
		and tbl_peca.fabrica = $fabrica";
$res = pg_query ($con,$sql);

if (@pg_num_rows($res) > 0) {
	ob_start();
	echo "<table align='center' border='0' cellspacing='1' cellpadding='1'>";
        echo "<tr height='20' bgcolor='#999999'>";
            echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Código</b></font></td>";
            echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Refêrencia</b></font></td>";
            echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Descrição</b></font></td>";
            echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Entrada</b></font></td>";
            echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Saida</b></font></td>";
            echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Qtd Estoque</b></font></td>";
            echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Preco</b></font></td>";
            echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Localização</b></font></td>";
        echo "</tr>";

        for ($i = 0; $i < pg_num_rows($res); $i++) {
            $cor = ($i % 2 == 0) ? '#F1F4FA' : '#FFFFFF';

            $peca           = trim(pg_fetch_result($res,$i,peca));
            $referencia     = trim(pg_fetch_result($res,$i,referencia));
            $descricao      = trim(pg_fetch_result($res,$i,descricao));

			$sql = "SELECT    tbl_posto_estoque.qtde,tbl_posto_estoque_localizacao.localizacao,
							   (select sum(qtde) from tbl_faturamento_item join tbl_faturamento using(faturamento) where fabrica= 10 and status_nfe isnull and posto in (4311,376542) and tbl_faturamento_item.peca = tbl_peca.peca ) as qtde_entrada,
							   (select sum(qtde) from tbl_faturamento_item join tbl_faturamento using(faturamento) where fabrica= 10 and status_nfe='100' and tbl_faturamento_item.peca = tbl_peca.peca and posto not in (4311,376542) and emissao > current_date - interval '18 months' ) as qtde_saida,
								(select preco from tbl_faturamento_item join tbl_faturamento using(faturamento) where fabrica= 10 and posto = 4311 and status_nfe isnull and tbl_faturamento_item.peca = tbl_peca.peca order by faturamento desc limit 1) as preco
					   FROM tbl_peca
					   JOIN tbl_posto_estoque USING(peca)
					   left join tbl_posto_estoque_localizacao using(peca)
						WHERE tbl_peca.peca = $peca 
						";
			$resq = pg_query($con,$sql);
			if(pg_num_rows($resq)>0) {
					$qtde           = trim(pg_fetch_result($resq,0,qtde));
					$qtde_saida     = trim(pg_fetch_result($resq,0,qtde_saida));
					$qtde_entrada     = trim(pg_fetch_result($resq,0,qtde_entrada));
					$localizacao     = trim(pg_fetch_result($resq,0,localizacao));
					$preco          = trim(pg_fetch_result($resq,0,preco));
					$preco = number_format($preco,2,',','.');

					if($qtde_saida  >0   ) continue;
			}else{
				continue;
			}
            echo "<tr bgcolor='$cor'>";
                echo "<td>$peca</td>";
                echo "<td>$referencia</td>";
				echo "<td>$descricao</td>";
                echo "<td>$qtde_entrada</td>";
                echo "<td>$qtde_saida</td>";
                echo "<td>$qtde</td>";
                echo "<td>$preco</td>";
                echo "<td>$localizacao</td>";
            echo "</tr>";

        }

    echo "</table>";
	$link_xls = "xls/relatorio_peca.xls";
		if (file_exists($link_xls))
			exec("rm -f $link_xls");
		if ( is_writable("xls/") ) 
			$file = fopen($link_xls, 'a+');

		$dados_relatorio = ob_get_contents();

		fwrite($file,$dados_relatorio);

		fclose($file);
		if ( isset ($file) && !empty($dados_relatorio) ) {
		echo "<button class='download' onclick=\"window.open('$link_xls') \">Download XLS</button>";
	}

}
}?>

<p>

<? include "rodape.php"; ?>

</body>
</html>
