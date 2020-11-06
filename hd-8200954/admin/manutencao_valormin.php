<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios='cadastros';
include 'autentica_admin.php';

include 'funcoes.php';
if(!isset($semcab)){
	$title = "CADASTRO";
	$layout_menu = "cadastro";
	include 'cabecalho_new.php';
}
echo $login_master;


if(strlen($_POST["btn_acao"])>0){

	$valor_pedido_minimo           = trim($_POST["valor_pedido_minimo"]);
	$valor_pedido_minimo_capital   = trim($_POST["valor_pedido_minimo_capital"]);
	$email_loja_virtual            = trim($_POST["email_loja_virtual"]);
	$regra_loja_virtual            = trim($_POST["regra_loja_virtual"]);	
	$descricao_forma_pagamento     = trim($_POST["descricao_forma_pagamento"]);

	$regra_loja_virtual = nl2br($regra_loja_virtual);
	

	if(strlen($valor_pedido_minimo)        == 0) $msg_erro = "Por favor digite o Valor";
	if(strlen($valor_pedido_minimo_capital)== 0) $msg_erro = "Por favor digite o Valor";
	if(strlen($email_loja_virtual)== 0)          $msg_erro = "Digite o e-mail do responsável pelo cadastro de peças";
	if(strlen($msg_erro) > 0){
		$controlgrup = "control-group error";
	}else{
		$controlgrup = "control-group";
	}

	if(strlen($msg_erro)==0){
			$valor_pedido_minimo         = str_replace(",",".",$valor_pedido_minimo);
			$valor_pedido_minimo_capital = str_replace(",",".",$valor_pedido_minimo_capital);

			$sql = "UPDATE tbl_fabrica SET
					valor_pedido_minimo = $valor_pedido_minimo,
					valor_pedido_minimo_capital = $valor_pedido_minimo_capital
					WHERE fabrica = $login_fabrica";
			$res = pg_exec ($con,$sql);

				
			$msg_erro = pg_errormessage($con);

			$sql = "SELECT email_loja_virtual, regra_loja_virtual,descricao_forma_pagamento
					FROM tbl_configuracao
					WHERE fabrica=$login_fabrica";
			$res_conf = pg_exec($con,$sql);
			$resultado = pg_numrows($res_conf);

			if ($resultado == 0){
				$sql = "INSERT INTO tbl_configuracao
						(fabrica) VALUES ($login_fabrica)";
				$res_conf = pg_exec($con,$sql);
			}
			

			$sql = "UPDATE tbl_configuracao SET 
						email_loja_virtual        = '$email_loja_virtual',
						regra_loja_virtual        = '$regra_loja_virtual',
						descricao_forma_pagamento = '$descricao_forma_pagamento'
					WHERE fabrica = $login_fabrica";
			$res = pg_exec ($con,$sql);
			

	if(strlen($msg_erro) == 0){
		$msg = "Gravado com Sucesso!";
	}
}
}
include "menu.php";

?>

<script language='javascript' src='../ajax.js'></script>
<script>


function checarNumero(campo){
	var num = campo.value.replace(",",".");
	campo.value = parseFloat(num).toFixed(2);
	if (campo.value=='NaN') {
		campo.value=0;
	}
}
</script>
<?

$sql = "SELECT valor_pedido_minimo, valor_pedido_minimo_capital 
		FROM tbl_fabrica 
		WHERE fabrica = $login_fabrica";
$res = pg_exec ($con,$sql);
$valor_pedido_minimo         = trim(pg_result($res,valor_pedido_minimo));
$valor_pedido_minimo_capital = trim(pg_result($res,valor_pedido_minimo_capital));

if( strlen($valor_pedido_minimo)         == 0 ) {
	$valor_pedido_minimo = 0;
}
if( strlen($valor_pedido_minimo_capital) == 0 ){
	$valor_pedido_minimo_capital = 0;
}

$valor_pedido_minimo         = number_format($valor_pedido_minimo,2,'.','');
$valor_pedido_minimo_capital = number_format($valor_pedido_minimo_capital,2,'.','');

$sql = "SELECT email_loja_virtual,regra_loja_virtual,descricao_forma_pagamento
		FROM tbl_configuracao
		WHERE fabrica=$login_fabrica";
$res_conf = pg_exec($con,$sql);
$resultado = pg_numrows($res_conf);
if ($resultado>0){
	$email_loja_virtual        = trim(pg_result($res_conf,0,email_loja_virtual));
	$descricao_forma_pagamento = trim(pg_result($res_conf,0,descricao_forma_pagamento));
	$regra_loja_virtual        = trim(pg_result($res_conf,0,regra_loja_virtual));
	$regra_loja_virtual        = strip_tags($regra_loja_virtual);

}

 if (strlen($msg_erro) > 0) { ?>
<div class='alert alert-error'>
	<h4><? echo $msg_erro; ?></h4>
</div>
<? } ?>

<? if (strlen($msg) > 0) { ?>
<div class="alert alert-success">
	<h4><? echo $msg; ?></h4>
</div>
<? } ?>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>


<form method='POST' name='frm' action='<? echo $PHP_SELF;if(isset($semcab))echo "?semcab=yes"; ?>'align="center" class="form-search form-inline tc_formulario">

<!-- -->
<div class="titulo_tabela ">Manutenção Loja Virtual</div>
<br>
<div class="row-fluid">
	<div class="span1"></div>
	<div class="<? echo $controlgrup?>">
  		<div class="span4">Valor mínimo para cidades do interior:</div>
  		<div class="span7">
  			<h5 class='asteristico'>*</h5>
  			<input type='text' size='10' id='valor_pedido_minimo' onblur="javascript:checarNumero(this);" name='valor_pedido_minimo' class='frm' value='<? echo $valor_pedido_minimo; ?>'>
  		</div>
  </div>
</div> 
<div class="row-fluid">
	<div class="span1"></div>
	<div class="<? echo $controlgrup ?>">
		<div class="span4">Valor mínimo para as capitais:</div>
		<div class="span7">
			<h5 class='asteristico'>*</h5>
			<input type='text' size='10' name='valor_pedido_minimo_capital' id='valor_pedido_minimo_capital' onblur="javascript:checarNumero(this);" class='frm' value='<? echo $valor_pedido_minimo_capital; ?>'>
		</div>
	</div>
</div> 
<div class="row-fluid">
	<div class="span1"></div>
	<div class="<? echo $controlgrup?>">
	<div class="span4">E-mail do Responsável:<img src='imagens/help.png' title="Mais que 1 email, separe por  ',' (vírgula)" /></div>
	<div class="span7">
	<h5 class='asteristico'>*</h5>
		<input type='text' size='62' class='frm' name='email_loja_virtual' id='email_loja_virtual' style="width: 430px;" value='<? echo $email_loja_virtual; ?>'>
	</div>
	</div>
</div>
<div class="row-fluid">
	<div class="span1"></div>
	<div class="control-group">
	<div class="span4">Descrição da forma de pagamento:<img src='imagens/help.png' title='Será descrita em todos os produtos e no carrinho de compras' /></div>
	<div class="span7">
		<textarea name="descricao_forma_pagamento" rows="2" cols="60" class='frm' style="width: 430px;"><? echo $descricao_forma_pagamento; ?></textarea>
	</div>
	</div>
</div>
<br></br>
<div class="row-fluid">
	<div class="span1"></div>
	<div class="control-group">
	<div class="span4">Regra da Loja Virtual <img src='imagens/help.png' alt='Esta regra será mostrada na Loja Virtual' title='Esta regra será mostrada na Loja Virtual' />
	</div>
	<div class="span7">
		<textarea name="regra_loja_virtual" rows="2" cols="60" class='frm' style="width: 430px;" ><? echo $regra_loja_virtual; ?></textarea>
	</div>
	</div>
</div>

 <p>
  <br /> 
	<button class="btn" onclick='document.frm.btn_acao.value="gravar"; document.frm.submit()' style='cursor: hand;text-align=center'>Gravar</button>
	<input type='hidden' name='btn_acao'>
 </p>
 <br />
</form>
<?
/*
	echo "<table border='0' cellpadding='2' cellspacing='0' class='HD' align='rigth' width='400'>";

		echo "<tr bgcolor='#ddf8cc' class='Conteudo'>";
			echo "<td height='40' colspan='4' align='center'  align='center' class='Label' bgcolor='#e6eef7' nowrap><b>Valor mínimo de compra para loja virtual</b></td>";
		echo "</tr>";
		echo "<tr bgcolor='#FFFFFF' class='Label'>";
			echo "<td nowrap align='right'>Valor mínimo para cidades do interior:</td>";
			echo "<td align='right'><input type='text' size='10' id='valor_pedido_minimo' onblur=\"javascript:checarNumero(this);\" style='text-align:right' name='valor_pedido_minimo' class='Caixa' value='$valor_pedido_minimo'></td>";
		echo "</tr>";
		echo "<tr bgcolor='#FFFFFF' class='Conteudo'>";
			echo "<td align='right'>Valor mínimo para as capitais:</td>";
			echo "<td align='right'><input type='text' size='10' id='valor_pedido_minimo_capital' onblur=\"javascript:checarNumero(this);\" style='text-align:right' name='valor_pedido_minimo_capital' class='Caixa' value='$valor_pedido_minimo_capital'></td>";
		echo "</tr>";
		echo "<tr bgcolor='#FFFFFF' class='Conteudo'>";
			echo "<td colspan='2'><hr></td>";
		echo "</tr>";
		echo "<tr bgcolor='#FFFFFF' class='Conteudo'>";
			echo "<td align='left'>E-mail do Responsável: <input type='text' size='20' name='email_loja_virtual' id='email_loja_virtual' value='$email_loja_virtual'></td>";
		echo "</tr>";
		echo "<tr bgcolor='#FFFFFF' class='Conteudo'>";
				echo "<td colspan='2' align='center'><input type='submit' name='btn_acao' value='Gravar'></td>";
		echo "</tr>";

	echo "</form>";
	echo "</table>";
*/
if(!isset($semcab)){include "rodape.php";}
?>
