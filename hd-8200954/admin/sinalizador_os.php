<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

include "funcoes.php";

include "autentica_admin.php";

$btn_acao     = $_POST["btn_acao"];



if ($btn_acao=="gravar"){
	$debito = $_POST["debito"];
	$solucao = $_POST["solucao"];
	$acao = $_POST["acao"];
	$disponivel = $_POST["disponivel"];
	
	
	if ($acao == null ){
		$msg = "Necessário especificar a Ação.<br>";
	}
	if ($debito==null and strlen($msg)==0){
		$msg = "Necessário especificar o Débito.<br>";
	}

	if ($solucao==null and strlen($msg)==0){
		$msg = "Necessário especificar a Solução.<br>";
	}

	
	
	if ($disponivel == true){
		$disponivel = 't';
	}else{
		$disponivel = 'f';
	}
	

	if(strlen($msg)==0){
		$query = "INSERT INTO tbl_sinalizador_os (debito,solucao,acao,disponivel) VALUES ('$debito','$solucao','$acao','$disponivel')";
		$res = pg_exec($con, $query);

	$debito = null;
	$solucao = null;
	$acao = null;
	$disponivel = null;

	$aviso="Gravado com Sucesso";
		
	}

}
if ($btn_acao=="alterar"){
	$sql = "SELECT sinalizador FROM tbl_sinalizador_os";
	$res = pg_exec ($con,$sql) ;
	if (@pg_numrows($res) > 0) {
		for ($i=0; $i<pg_numrows ($res); $i++ ){

			$sinalizador = trim(pg_result($res,$i,sinalizador));
			
			$debito = $_POST["debito_$sinalizador"];	
			$solucao = $_POST["solucao_$sinalizador"];
			$acao = $_POST["acao_$sinalizador"];
			$disponivel = $_POST["disponivel_$sinalizador"];

			if ($solucao == null){
				$solucao='&nbsp;';
			}

			if ($acao == null){
				$acao='&nbsp;';
			}
			
			if ($disponivel == true){
				$disponivel = 't';
			}else{
				$disponivel = 'f';
			}

			$sql2 = "UPDATE tbl_sinalizador_os SET 
				debito = '$debito',
				acao = '$acao',
				solucao = '$solucao',
				disponivel = '$disponivel'
				WHERE sinalizador = $sinalizador";
	
			$res2 = pg_exec($con,$sql2);
			$aviso="Gravado com Sucesso";

		}
		
	}

}


$layout_menu = "financeiro";
$title = "CADASTRO DO SINALIZADOR DE OS";
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
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}


.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.espaco{
	padding: 0 0 0 150px;
}
</style>


<script language="JavaScript">

function _trim (s)
{
   //   /            open search
   //     ^            beginning of string
   //     \s           find White Space, space, TAB and Carriage Returns
   //     +            one or more
   //   |            logical OR
   //     \s           find White Space, space, TAB and Carriage Returns
   //     $            at end of string
   //   /            close search
   //   g            global search

   return s.replace(/^\s+|\s+$/g, "");
}

</script>

<?

if(strlen($aviso)>0){
echo "<div style='width:700px;' class='sucesso'>".$aviso."</div>";
}
?>
<form name="frm_gravar" method="post" action="<?echo $PHP_SELF?>">

<table width="700" align="center" border="0" cellspacing="0" cellpadding="2" class='formulario'>
	<?php if(strlen($msg) > 0){ ?>
		<tr class='msg_erro'><td colspan='2'><?php echo $msg;?> </td></tr>
	<?php } ?>
	<tr class='titulo_tabela'>
		<td colspan='2'>Cadastrar Sinalizador de OS</td>
	</tr>
	<tr>
		<td colspan='2'>&nbsp;</td>
	</tr>
	<tr  align='left'>
		<td class='espaco' width='60'>Ação *</td>
		<td><input type="text" name="acao" id="acao" style='width:300px;' value="<?=$acao?>" class="frm" maxlength='30'></td>
	</tr>
	<tr  align='left'>
		<td class='espaco'>Débito *</td>
		<td><select name='debito' id='debito' class='frm'  style='width:80px;'><option value=''></option><option value='S' <? if($debito=="S") echo "selected"; ?>>S</option><option value='N' <? if($debito=="N") echo "selected"; ?>>N</option></select></td>
	</tr>
	<tr  align='left'>
		<td class='espaco'>Solução *</td>
		<td><input type="text" name="solucao" id="solucao" style='width:300px;' class="frm" maxlength='100' value="<?=$solucao?>"></td>
	</tr>
	<tr  align='left'>
		<td class='espaco'>Disponível</td>
		<td><INPUT TYPE="checkbox" NAME="disponivel" class="frm"
		<?
			if(strlen($btn_acao)>0){
				if($disponivel == 't')
					echo "checked";
				
			}
			else{
				echo "checked"; 
			}
		?>>
		</td>
	</tr>
	<tr>
		<td colspan='2' style='padding:20px 0 20px 0;' align='center'>
			<input type="hidden" name="btn_acao" value="">
			<input type='button' value='Gravar' onclick="javascript: document.frm_gravar.btn_acao.value='gravar'; document.frm_gravar.submit()">
		</td>
	</tr>
</table>

</form>
<br><br><form name="frm_alterar" method="post" action="<?echo $PHP_SELF?>">
<table  align='center' border='0' cellspacing='1' cellpadding='3' class='tabela'>
<tr class='titulo_coluna'><td>Disponível</td><td>Ação</td><td>Débito</td><td>Solução</td></tr>
<?
$sql = "SELECT sinalizador,acao,debito,solucao,disponivel FROM tbl_sinalizador_os order by sinalizador";
	$res = pg_exec ($con,$sql) ;
	if (@pg_numrows($res) > 0) {
		for ($i=0; $i<pg_numrows ($res); $i++ ){

			$cor = "#F7F5F0"; 
			$btn = 'amarelo';
			if ($i % 2 == 0) 
			{
				$cor = '#F1F4FA';
				$btn = 'azul';
			}

			$sinalizador = trim(pg_result($res,$i,sinalizador));
			$acao = trim(pg_result($res,$i,acao));
			$debito = trim(pg_result($res,$i,debito));
			$solucao = trim(pg_result($res,$i,solucao));
			$disponivel = trim(pg_result($res,$i,disponivel));
			
			if ($sinalizador==4){
				$disabled='disabled';
			}else{
				$disabled='';
			}
			echo "<tr style='background-color: $cor;'><td><INPUT TYPE='checkbox' NAME='disponivel_$sinalizador' class='frm'";
			if ($disponivel=='t'){
				echo " checked";
			}
			echo " $disabled></td><td><input type='text' name='acao_$sinalizador' id='acao_$sinalizador' style='width:200px;' value='$acao' class='frm' maxlength='30' $disabled></td><td><select name='debito_$sinalizador' id='debito_$sinalizador' class='frm'  style='width:80px;' $disabled><option value='$debito' SELECTED>$debito</option><option value='S'>S</option><option value='N'>N</option></select></td><td><input type='text' name='solucao_$sinalizador' id='solucao_$sinalizador' style='width:300px;' class='frm' value='$solucao' maxlength='100' $disabled></td></tr>";

		}
	}

?>
<input type="hidden" name="btn_acao" value="">
<table width="400" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr >
		<td colspan='2' align='center'><br><input type='button' value='Alterar' onclick="javascript: document.frm_alterar.btn_acao.value='alterar'; document.frm_alterar.submit()"></td>
	</tr>
</table>
</form>

<? include "rodape.php" ?>
