<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

$admin_privilegios="cadastro";
include 'autentica_admin.php';

$btn_acao = strtolower($_POST["btn_acao"]);

if (strlen($btn_acao)> 0){

	$qtde_item = trim($_POST["qtde_item"]);

	for ($i = 0; $i < $qtde_item; $i ++){
		$pais                 = trim($_POST['pais_'.$i]);
		$conversao_moeda      = trim($_POST['conversao_moeda_'.$i]);
		$conversao_dolar_euro = trim($_POST['conversao_dolar_euro_'.$i]);
		$desconto_peca        = trim($_POST['desconto_peca_'.$i]);
		$unidade_trabalho     = trim($_POST['unidade_trabalho_'.$i]);
		$conversao_moeda      = str_replace(",", ".", $conversao_moeda );
		$conversao_dolar_euro = str_replace(",", ".", $conversao_dolar_euro);
		$desconto_peca        = str_replace(",", ".", $desconto_peca  );
		$unidade_trabalho     = str_replace(",", ".", $unidade_trabalho  );
		
		$msg_erro="";

		if (strlen($pais) > 0) {
		}else{
			$msg_erro= "Erro no país" ;
		}
		if (strlen($conversao_moeda) > 0) {

		}else{
			$msg_erro= "Conversão de Moeda para o país $pais está vazio." ;
		}
		if (strlen($desconto_peca) > 0) {
			if($desconto_peca <= 1) {
			//nao faz nada
			}else{
				$msg_erro= "Valor máximo de 1 para representar 100% de desconto para o país: $pais" ;
			}
		}else{
			$msg_erro= "Desconto de peça para o país $pais está vazio. $desconto_peca" ;
		}
			
		if (strlen($msg_erro) == 0 ){

			$res = pg_exec($con,"begin;");
			$sql = "UPDATE tbl_pais
						SET conversao_dolar_euro = $conversao_dolar_euro,
							conversao_moeda      = $conversao_moeda ,
							desconto_peca        = $desconto_peca,
							unidade_trabalho     = $unidade_trabalho
					WHERE pais ='$pais' ";

			$res = pg_exec($con,$sql);

			$msg_erro = pg_errormessage($con);
			if(strlen($msg_erro) > 0){
				$res = pg_exec($con,"rollback;");
				echo "<font color = 'red'>Erro na gravação do país: $pais.<br>$msg_erro - $sql</font>";
			}else{
				$res = pg_exec($con,"commit;");

			}
		}else{
			echo "<font color = 'red'>Erro na gravação do país: $pais.<br>$msg_erro</font>";
		
		}
	}

}

$layout_menu = "cadastro";
$title = 'INFORMAÇÕES DOS PAÍSES';
include "cabecalho.php";

?>
<style>
	table.tabela tr td{
		font-family: verdana;
		font-size: 11px;
		border-collapse: collapse;
		border:1px solid #596d9b;
	}
.titulo_coluna{
	color:#FFFFFF;
	font:bold 11px "Arial";
	text-align:center;
	background:#596d9b;
}
.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}
</style>
<?php

$sql = "SELECT  pais, nome, conversao_moeda,conversao_dolar_euro,desconto_peca,unidade_trabalho
		FROM     tbl_pais
		order by nome;";
$res = pg_exec ($con,$sql);

echo "<table width='700' cellspacing='1' cellpadding='0' align='center' class='tabela'>\n";
echo "<form name='frm_pais' method='post' action='$PHP_SELF'>";

echo '<tr class="titulo_coluna">',
	 '	<td><b>País</td>',
	 '	<td><b>Nome</td>',
	 '	<td><b>Conversão de Moeda</td>',
	 '	<td width="250"><b>Desconto De Peça <br>(Ex: 0,5 é o mesmo que 50%)</td>',
	 '	<td><b>Conversão<br />Dólar-Euro</td>',
	 '	<td><b>Unidade de Trabalho</td>',
	 '</tr>';

for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {

	$pais                 = trim(pg_result($res,$i,pais));
	$nome                 = trim(pg_result($res,$i,nome));
	$conversao_moeda      = trim(pg_result($res,$i,conversao_moeda));
	$conversao_dolar_euro = trim(pg_result($res,$i,conversao_dolar_euro));
	$desconto_peca        = trim(pg_result($res,$i,desconto_peca));
	$unidade_trabalho     = trim(pg_result($res,$i,unidade_trabalho));

	$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
	echo "<tr bgcolor='$cor'>\n";
	
	echo "<td >\n";
	echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$pais
				<input type='hidden' name='pais_$i' value='$pais'>	
	</font>\n";
	echo "</td>\n";
	
	echo "<td align='left'>\n";
	echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$nome</font>\n";
	echo "</td>\n";

	echo "<td>\n";
	echo "<input type='text' class='frm' name='conversao_moeda_$i' size='10' maxlength='' value='$conversao_moeda'>\n";
	echo "</td>\n";

	echo "<td>\n";
	echo "<input type='text' class='frm' name='desconto_peca_$i' size='10' maxlength='' value='$desconto_peca'>\n";
	echo "</td>\n";

	echo "<td>\n";
	echo "<input type='text' class='frm' name='conversao_dolar_euro_$i' size='10' maxlength='' value='$conversao_dolar_euro'>\n";
	echo "</td>\n";

	echo "<td>\n";
	echo "<input type='text' class='frm' name='unidade_trabalho_$i' size='10' maxlength='' value='$unidade_trabalho'>\n";
	echo "</td>\n";

	echo "</tr>\n";
}
echo "<input type='hidden' name='qtde_item' value='$i'>";
echo "</table>\n";


?>
<table align='center'>
<input type='hidden' name='btn_acao' value=''>
<tr>
	<td colspan=9 align='center'>
		<center>
		<input type="button" onclick="javascript: if (document.frm_pais.btn_acao.value == '' ) { document.frm_pais.btn_acao.value='gravar2' ; document.frm_pais.submit() } else { alert ('Aguarde submissão') }" value="Gravar" 
		style="cursor:pointer; "/>

		</center>
	</td>
</tr>
</table>
</form>
<?php include 'rodape.php'; ?>
</body>
</html>