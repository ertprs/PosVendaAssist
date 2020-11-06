<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";

if(strlen($excluir)>0){
	$sql = "SELECT produto_rg
			FROM   tbl_produto_rg                
			WHERE  posto= $login_posto
			AND    data_digitacao::DATE = CURRENT_DATE
			AND    data_digitacao_termino IS NULL";
	$res1 = @pg_exec($con,$sql);

	if(@pg_numrows($res1)>0){

		$produto_rg = @pg_result($res1,0,0);

		$sql = "DELETE FROM tbl_produto_rg_item 
				WHERE produto_rg      = $produto_rg
				AND   produto_rg_item = $excluir   ";
		$res2 = @pg_exec($con,$sql);
	}
	header("Location:$PHP_SELF");
	exit;
	$msg = "Excluido com sucesso!";
}

if(strlen($explodir)>0) {
	$sql = "SELECT produto_rg_item
			FROM   tbl_produto_rg      RG
			JOIN   tbl_produto_rg_item RI USING(produto_rg)
			WHERE  RG.produto_rg = $explodir
			AND    produto IS NULL";
	$res = @pg_exec($con,$sql);
	$msg_erro = pg_errormessage($con);

	if(@pg_numrows($res)==0){
		$sql = "BEGIN;";
		$res = @pg_exec($con,$sql);
		$sql =	"UPDATE tbl_produto_rg SET
					data_digitacao_termino = NOW()            ,
					data_conferencia       = CURRENT_TIMESTAMP
				WHERE produto_rg            = $explodir 
				AND   posto                 = $login_posto
				AND   data_digitacao::DATE  IS NOT NULL
				AND   data_digitacao_termino IS NULL; ";
		$res = @pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);
		$sql =	"UPDATE tbl_produto_rg_item SET
					fabrica           = 45               ,
					data_conferencia  = CURRENT_TIMESTAMP
				WHERE produto_rg = $explodir 
				AND   data_conferencia IS NULL; ";
		$res = @pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);

		$sql = "SELECT fn_gera_lote($explodir,$cook_posto);";
		//echo $sql;
		$res = @pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);
		if(strlen($msg_erro)==0){
			$sql = "COMMIT;";
			$res = @pg_exec($con,$sql);
			header("Location: planilha.php");
			exit;
		}else{
			$sql = "ROLLBACK;";
			$res = @pg_exec($con,$sql);
			$msg_erro .= "Não é possível gerar a OS! Favor verificar todas as informações dos produtos!";
			echo "<FONT COLOR='RED' >$msg_erro</FONT>";
		}
	}else{
		$msg_erro .= "Não é possível gerar a OS enquanto todos os produtos não forem informados";
			echo "<FONT COLOR='RED' >$msg_erro</FONT>";
	}
}


$aba=2;
include "cabecalho.php";

?>
<script type="text/javascript" src="rg_recebimento.js"></script>
<script type="text/javascript" src="js/bibliotecaAJAX.js"></script>

<script>
function executar(campo1,campo2) {
	/* Busca pelo Nome */
	$('#'+campo1).autocomplete("pesquisa.php?tipo=produto&busca=tudo", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: false,
		highlightItem: true,
		formatItem: function (row)   {return row[4]},
		formatResult: function(row)  {return row[2];}
	});

	$('#'+campo1).result(function(event, data, formatted) {
		$('#'+campo2).val(data[0])    ;
		$('#'+campo1).focus() ;
	});

}
</script>
<div id="m_1" class="modbox" style='width:98%;' >
	<h2 class="modtitle">
		<div id="m_1_h">
			<span id="m_1_title" class="modtitle_text"><font color="#005f9d">Recebimento de Lote de Produto</font></span>
		</div>
	</h2>

	<div id="m_1_b" class="modboxin">
		<div id="ftl_1_0" class="uftl" style='text-align:justify'>

		<form name="frm_os" method="post"  onsubmit="return false">
		<table border='0' onclick='foco();'>
			<tr>
				<td valign='top' align='left'>
					<table align='center' border='0' class='TabelaRevenda'>
						<tr>
							<th align='left' height='20'>Código de Barra</th>
							<td valign='top'>
								<input type='hidden' id='controle_foto' value=''><A NAME='foto' ></a>
								<input type='text' name='codigo_barra' id='codigo_barra' value='' maxlength='13' size='15' onKeyPress="return proximo();" class='Caixa'>
							</td>
							<th align='left' valign='top' height='20'>RG</th>
							<td valign='top'>
								<input type='text' name='rg' id='rg' value='' size='30' onfocus='verifica_codigo_barra();'  onKeyPress="var tecla = event.keyCode;if ((tecla == 13)) {gravar(this.form,'sim','<?=$PHP_SELF;?>','nao');}return tecla;alert('Gravando...');" class='Caixa'>
							</td>
						</tr>
					</table>
				</td>
				<td rowspan='2'>
					<div id='div_foto' align='center' style='display:inline;float:left;'></div>
				</td>
			</tr>
			<tr>
				<td><div id='div_quadro' align='center' style='display:inline;float:left;'></div></td>
			</tr>
		</table>
		<div id='div_produto' align='center' onclick='foco();'></div>
		<div id='saida' style='display:inline;'></div>
		<script>
			MostraInfo();
		</script>
		</form>


		</div>
	</div>
</div>



<!--<script>window.location="#foto";</script>-->
<?

include "rodape.php";
?>