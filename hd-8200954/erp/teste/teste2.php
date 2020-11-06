<style>


.Label{
	font-family: Verdana;
	font-size: 10px;
}

.Caixa{
	FONT: 8pt Arial ;
	BORDER-RIGHT:     #6699CC 1px solid;
	BORDER-TOP:       #6699CC 1px solid;
	BORDER-LEFT:      #6699CC 1px solid;
	BORDER-BOTTOM:    #6699CC 1px solid;
	BACKGROUND-COLOR: #FFFFFF;
}

.tabela{
	font-family: Verdana;
	font-size: 10px;
	
}

</style>
<script>

function checarNumero(campo){
	var num = campo.value.replace(",",".");
	campo.value = parseFloat(num).toFixed(2);
	if (campo.value=='NaN') {
		campo.value='';
	}
}

function gravar(){

}
</script>

<script language='javascript' src='../ajax.js'></script>
<script language='javascript' src='../ajax_cep.js'></script>
<script language='javascript' src='ajax_orcamento.js'></script>

<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';
include '../funcoes.php';

$btn_acao = trim($_GET["btn_acao"]);
if (strlen($btn_acao)==0){
	$btn_acao = trim($_POST["btn_acao"]);
}
$peca	= $_POST["peca"];
if(strlen($peca)==0) 
	$peca= $_GET["peca"];
	$referencia            = trim($_POST['referencia']);
	$descricao             = trim($_POST['descricao']);
	$qtde                  = trim($_POST['qtde']);
	$preco                 = trim($_POST['preco']);
	$requisicao            = trim($_POST['requisicao']);


?>	

<table style=' border:#485989 1px solid; background-color: #e6eef7' align='center' width='700' border='0' class='tabela'>
<form name="frm_cadastro" method="post" action="<? echo $PHP_SELF ?>" ENCTYPE="multipart/form-data">
<input  type="hidden" name="peca" value="<? echo $peca ?>">
		<tr height='20' bgcolor='#7392BF'>
			<td class='Titulo_Tabela' colspan='6'>Produto do Cliente</td>
		</tr>       
		<tr height='3'>
			<td  colspan='4'>&nbsp;</td>
		</tr>
		<tr>
			<td class='Label'>Código</td>
			<td colspan='4'><input class="Caixa" type="text" name="referencia" size="20" maxlength="50" value="<? echo $referencia ?>"></td>
		</tr>
		<tr>
			<td class='Label'>Produto</td>
			<td colspan='4'><input class="Caixa" type="text" name="descricao" size="50" maxlength="50" value="<? echo $descricao ?>"></td>
		</tr>
		<tr>
		<td class='Label'>Quantidade</td>
			<td colspan='4'><input class="Caixa" type="text" name="qtde" size="3" maxlength="5" style='text-align:right' value="<? echo $qtde ?>"></td>
		</tr>
		<tr>
			<td class='Label'>Valor de Compra</td>
			<td colspan='4'><input class="Caixa" type="text" name="preco" size="10"  style='text-align:right' maxlength="10" value="<? echo $preco ?>" onblur="javascript:checarNumero(this);"></td>
		</tr>
		<tr>
			<td class='Label' colspan='5' align='center'>
			<input class="botao" type="button" name="<? $btn_acao ?>" value='Gravar'>
			</td>
		</tr>
	</table>
<?
		if ($btn_acao == "Gravar") {
			$sql =" SELECT requisicao 
					FROM tbl_requisicao 
					WHERE orcamento = $orcamento 
					AND   empresa   =$login_empresa ";
			$res = pg_exec ($con,$sql);

			if(pg_numrows($res)==0){
			$sql = "
					INSERT INTO tbl_requisicao (
						data             ,
						hora             ,
						usuario          ,
						empresa          ,
						orcamento        ,
						status
					) VALUES (
						current_date     ,
						current_time     ,
						$login_empregado ,
						$login_empresa   ,
						$orcamento       ,
						'aberto' 
					)";
			$res = pg_exec ($con,$sql);
			$sql= " SELECT CURRVAL ('tbl_requisicao_requisicao_seq') as requisicao";
			$res= pg_exec($con, $sql);
			$requisicao=trim(pg_result($res,0,requisicao));
		}else {
			$requisicao=trim(pg_result($res,0,requisicao));
			$sql ="	UPDATE tbl_requisicao SET
							data          =current_date         ,
							hora          =current_time         ,
							usuario       =$login_empregado     ,
							status        ='aberto'             
							WHERE requisicao = $requisicao";
			$res = pg_exec ($con,$sql);
			}
			$sql = "INSERT INTO tbl_peca (
						referencia    ,
						descricao     ,
						origem        ,
						ativo         ,
						fabrica
					)VALUES (
						'$referencia'   ,
						'$descricao'    ,
						'nacional'            ,
						't'                   ,
						$login_empresa
					)";
			$res = pg_exec ($con,$sql);

			$sql= " SELECT CURRVAL ('seq_peca') as peca";
			$res= pg_exec($con, $sql);

			$id_peca = pg_result ($res,0,0);
			$peca = $id_peca;
			$xpeca =$id_peca;
			
			$sql = "INSERT INTO tbl_peca_item (
						familia                 ,
						linha                   ,
						peca                    
						)VALUES(
						767                     ,
						447                     ,
						$id_peca                
						)";
			$res= pg_exec($con, $sql);

			$sql= "INSERT INTO tbl_requisicao_item(
						requisicao   ,
						peca         ,
						quantidade   ,
						status
						) Values (
						$requisicao  ,
						$peca        ,
						$qtde       ,
						'aberto' 
						)";
			$res= pg_exec($con, $sql);
	}
?>