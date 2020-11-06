<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'funcoes.php';

if(!empty($_POST)){
	
	foreach($_POST['estoque_atual'] as $peca => $estoques){

		if(strlen($estoques[0]) > 0){
			$estoque_pecas	= $estoques[0];
		}else{
			$estoque_pecas = 'null';
		}
		
		if(strlen($estoques[1]) > 0){
			$estoque_devolucao = $estoques[1];
		}else{
			$estoque_devolucao = 'null';
		}
	
		if($estoque_pecas != 'null' || $estoque_devolucao != 'null'){
		
			$sql = "SELECT qtde,estoque_devolucao FROM tbl_estoque_posto WHERE peca=$peca AND posto=$login_posto AND fabrica=$login_fabrica";
			$res = pg_query ($con,$sql);

			if(pg_num_rows($res)>0){
			
				$qtde_banco = pg_fetch_result($res,$i,'qtde');
				$devolucao_banco = pg_fetch_result($res,$i,'estoque_devolucao');
				
				if(strlen($qtde_banco) > 0){
					$estoque_pecas	= $qtde_banco;
				}
		
				if(strlen($devolucao_banco) > 0){
					$estoque_devolucao = $devolucao_banco;
				}
			
				$upt = "UPDATE tbl_estoque_posto 
						SET qtde = $estoque_pecas, 
							estoque_devolucao = $estoque_devolucao
						WHERE peca=$peca 
						AND posto=$login_posto 
						AND fabrica=$login_fabrica";

				pg_query ($con,$upt);
			
			}else{

				$ins = "INSERT INTO tbl_estoque_posto 
						(fabrica,posto,peca,qtde,estoque_devolucao)
						VALUES
						($login_fabrica,$login_posto,$peca,$estoque_pecas,$estoque_devolucao)";

				pg_query ($con,$ins);

			}
			
			if($estoque_pecas > 0){
				$ins = "INSERT INTO tbl_estoque_posto_movimento (
							fabrica,
							posto,
							peca,
							data,
							qtde_entrada,
							obs
						)
						VALUES(
							$login_fabrica,
							$login_posto,
							$peca,
							CURRENT_DATE,
							$estoque_pecas,
							'Estoque inicial lan&ccedil;ado pelo Posto'
						)";
				pg_query ($con,$ins);
			}

		}
	
	}
}

$layout_menu = "cadastro";
$titulo = "ESTOQUE DE PEÇAS DO POSTO";
$title = "ESTOQUE DE PEÇAS DO POSTO";
include 'cabecalho.php';
include "javascript_pesquisas.php"; 

?>
<script language="javascript" src="js/assist.js"></script>
<script language='javascript' src='ajax.js'></script>
<script type="text/javascript" src="js/jquery.js"></script>
<script type='text/javascript' src='js/jquery.alphanumeric.js'></script>
<script type='text/javascript'>
$(function(){
	$(".numeros").numeric();
});
</script>


<style>
table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
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
.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
</style>
<br>
<div class="texto_avulso" style="width:700px;">
	<p>
		Nessa tela, você deve colocar o seu estoque de peças novas em estoque e peças usadas para devolução obrigatória.
	</p>
	<p>
		Caso não tenha saldo em alguma peça, coloque o valor 0 de saldo, para que fique gravado que não tem peça em estoque<br>
		Após salvar o estoque de alguma peça, não será possível alterar o estoque da mesma, necessitando entrar em contato com a fábrica para alterar!
	</p>
</div>
<br>
<form name='frm_consulta' method='post' action='<?= $PHP_SELF ?>'>

<?php
$sql = "SELECT 	tbl_peca.referencia,
				tbl_peca.peca,
				tbl_peca.descricao,
				tbl_estoque_posto.qtde,
				tbl_estoque_posto.estoque_devolucao
		FROM tbl_peca
		LEFT JOIN tbl_estoque_posto ON (tbl_estoque_posto.peca = tbl_peca.peca AND tbl_peca.fabrica = tbl_estoque_posto.fabrica AND tbl_estoque_posto.posto = $login_posto)
		WHERE tbl_peca.fabrica = $login_fabrica  
		AND tbl_peca.ativo IS TRUE
		AND tbl_peca.devolucao_obrigatoria IS TRUE
		ORDER BY tbl_peca.descricao";
		
$res = pg_query ($con,$sql);

if(pg_num_rows($res)>0){?>

	<br>
	<table class='tabela' width="700px" cellspacing="1" cellpadding="3" align='center'>
	<thead>
		<tr class='titulo_tabela'>
			<td>Código da Peça</td>
			<td>Descrição</td>
			<td width="100">Peças Novas</td>
			<td width="120">Peças Devolução</td>
		</tr>
	</thead>
	<tbody>

	<?
	for($i=0;pg_num_rows($res)>$i;$i++){

		$peca				= pg_fetch_result($res,$i,'peca');
		$peca_referencia 	= pg_fetch_result($res,$i,'referencia');
		$peca_descricao  	= pg_fetch_result($res,$i,'descricao');
		$qtde				= pg_fetch_result($res,$i,'qtde');
		$estoque_devolucao 	= pg_fetch_result($res,$i,'estoque_devolucao');
		
		$cor = ($i % 2 ==0) ? "#F7F5F0" : "#F1F4FA";
		?>
		<tr>
			<td align='left'><?php echo $peca_referencia;?></td>
			<td><?php echo $peca_descricao;?></td>
			
			<td align='center'>
				<?if($qtde > -1){?>
					<?echo $qtde;?>
				<?}else{?>
					<input type='text' size="3" maxlength="3" class="numeros" name='estoque_atual[<?=$peca;?>][0]' value='' />
				<?}?>
			</td>
			
			<td align='center'>
				<?if($estoque_devolucao > -1){?>
					<?echo $estoque_devolucao;?>
				<?}else{?>
					<input type='text' size="3" maxlength="3" class="numeros" name='estoque_atual[<?=$peca;?>][1]' value='' />
				<?}?>
			</td>
		</tr>
	<? 
	}
} ?>
</tbody>
</table>
<div align="center">
	<input type="submit" value="Salvar" />
</div>

</form>

<?php
include "rodape.php";
?>
