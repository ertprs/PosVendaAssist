<?
include_once "../dbconfig.php";
include_once "../includes/dbconnect-inc.php";

# -------------------------------------
// dados padronizados para abetura de pedido
$fabrica       = 10;
$posto         = 6359;
$tipo_pedido   = 77;
$status_pedido = 1;
# -------------------------------------

# CLASSE QUE LOCALIZA E RETORNA O CEP
class CalcFrete{
    var $servico,$ceporigem,$cepdestino,$peso;

    function calcular($ceporigem,$cepdestino,$peso,$servico = '40010'){

        $peso = $peso / 1000;

        $this->servico = $servico;
        $this->ceporigem = $ceporigem;
        $this->cepdestino = $cepdestino;
        $this->peso = $peso;
        if(!($this->peso == "0" || $this->peso > "30")){
            $correioFile = "http://www.correios.com.br/encomendas/precos/calculo.cfm?servico=" . $this->servico . "&CepOrigem=".$this->ceporigem."&CepDestino=".$this->cepdestino."&Peso=".$this->peso;
            $resultado = join("",file($correioFile));
            $procura = strpos($resultado,'Tarifa=')+strlen('Tarifa=');
            $resultado = trim(substr($resultado,$procura));
            $fim = strpos($resultado,"&erro=");
            return trim(substr($resultado,0,$fim));
        }else{
            return false;
        }
    }
}

if (strlen($cookie_login['cook_pedido']) > 0) $cook_pedido = $cookie_login['cook_pedido'];

if (strlen($_POST["btn_acao"]) > 0) $btn_acao = trim($_POST["btn_acao"]);


// chama CLASSE de calculo de frete
$ofrete = new CalcFrete();

// Valores para calcular o CEP
$cepOrigem = '17513230';                        // CEP DE ORIGEM DA EMPRESA
$cepDestino = $_POST['cep'];                    // CEP DESTINO

if ($btn_acao == 'consultacep'){
    $res  = pg_exec ($con,"SELECT sum(tbl_peca.peso * tbl_pedido_item.qtde) AS total_peso FROM tbl_pedido_item join tbl_peca using(peca) WHERE tbl_pedido_item.pedido = $cook_pedido");
    $peso         = pg_result ($res,0,total_peso);
    //$peso         = $peso/1000;

    $ofrete = new CalcFrete();
    $valor_cep = $ofrete->calcular($cepOrigem, $cepDestino, $peso);
    setcookie ("cep", $_POST['cep']);
    setcookie ("valor_cep", $valor_cep);
}
if (strlen($cookie_login['cep']) > 0) $cep = $cookie_login['cep'];

if ($btn_acao == 'continuar'){
    $res = pg_exec ($con,"SELECT sum(tbl_pedido_item.preco * tbl_pedido_item.qtde) AS total_compra, sum(tbl_peca.peso * tbl_pedido_item.qtde) AS total_peso FROM tbl_pedido_item join tbl_peca using(peca) WHERE tbl_pedido_item.pedido = $cook_pedido");
    $total_compra = pg_result ($res,0,total_compra);
    $peso         = pg_result ($res,0,total_peso);
    //$peso         = $peso/1000;

    $valor_cep  = $ofrete->calcular($cepOrigem, $cepDestino, $peso);

    if (strlen($cep) > 0 AND strlen($valor_cep) > 0){
        header("Location: pedido_finaliza.php");
        exit;
    }else{
        $msg_erro = "Favor informar o cep destino";
    }

    if (strlen($cep) == 0) $msg_erro = "Favor informar o cep destino";

}


// EXCLUIR ITEM
$pedido_item = $_POST['item'];
$peca        = $_POST['peca'];

if (strlen ($pedido_item) > 0 and $btn_acao == 'excluir') {
    $sql = "DELETE FROM tbl_pedido_item
            WHERE  tbl_pedido_item.pedido_item = $pedido_item
            AND    tbl_pedido_item.peca        = $peca
            AND    tbl_pedido.pedido           = $cook_pedido";
    $res = @pg_exec ($con,$sql);
    $msg_erro = pg_errormessage ($con);
}

if ($btn_acao == 'atualizar'){
    $sql = "SELECT sum(tbl_peca.peso * tbl_pedido_item.qtde) AS total_peso FROM tbl_pedido_item join tbl_peca using(peca) WHERE tbl_pedido_item.pedido = $cook_pedido";
    $res = pg_exec ($con,$sql);
    $peso = pg_result ($res,0,total_peso);
//    $peso = $peso/1000;

    $valor_cep = $ofrete->calcular($cepOrigem, $cepDestino, $peso);

//echo $valor_cep ."=". $ofrete."->calcular(".$cepOrigem .",". $cepDestino.",". $peso;

    $qtde_itens = $_POST['qtde_itens'];

    if (strlen($qtde_itens) > 0){
        for ($i = 0; $i < $qtde_itens; $i++){
            $qtde        = $_POST['qtde_'.$i];
            $pedido_item = $_POST['pedido_item_'.$i];

            if ($pedido_item > 0 and $qtde > 0){
                $sql = "UPDATE tbl_pedido_item SET
                               qtde            = $qtde
                        WHERE  pedido_item     = $pedido_item
                        AND    pedido          = $cook_pedido";
                $res = pg_exec ($con,$sql);
                $msg_erro = pg_errormessage($con);
            }
        }
    }
}else{
    #------------------------- insere pecas na pedido ----------------
    $peca    = trim($_POST['peca']);
    $qtde    = trim($_POST['qtde']);

    if (strlen($peca) > 0 and $qtde > 0 ){
        $sql = "SELECT  tbl_peca.peca        ,
                        tbl_peca.referencia  ,
                        tbl_tabela_item.preco,
                        tbl_peca.peso        
                FROM    tbl_peca
                JOIN    tbl_tabela_item ON tbl_tabela_item.peca = tbl_peca.peca
                WHERE   tbl_peca.fabrica = $fabrica
                AND     tbl_peca.peca    = $peca";
        $res = pg_exec ($con,$sql);
//echo "SQL seleciona dados da peca: $sql <br>";

        $peca       = pg_result($res, 0, peca);
        $referencia = pg_result($res, 0, referencia);
        $preco      = pg_result($res, 0, preco);
        $peso       = pg_result($res, 0, peso);

        $res = pg_exec ($con,"BEGIN TRANSACTION");

        if (strlen($cook_pedido) == 0){

            $sql = "INSERT INTO tbl_pedido(fabrica, posto, tipo_pedido, data, status_pedido) VALUES ($fabrica, $posto, $tipo_pedido, current_timestamp, $status_pedido)";
//echo "SQL insert pedido: $sql <br>";
            $res = pg_exec ($con,$sql);
            $msg_erro = pg_errormessage($con);

            if (strlen ($msg_erro) == 0) {
                $res    = pg_exec ($con,"SELECT CURRVAL ('seq_pedido')");
                $cook_pedido  = pg_result ($res,0,0);
                setcookie ("cook_pedido",$cook_pedido);
//echo "Cookie: $cook_pedido <br>";
            }
        }

        $sql = "SELECT *
                FROM   tbl_pedido_item
                WHERE  peca   = $peca
                AND    pedido = $cook_pedido";
        $res = pg_exec($con,$sql);
//echo "SQL verifica se item está no pedido: $sql <br>";

        if (pg_numrows($res) == 0) {
            $sql = "INSERT INTO tbl_pedido_item(
                        pedido  ,
                        peca    ,
                        qtde    ,
                        preco
                    ) VALUES (
                        $cook_pedido ,
                        $peca        ,
                        $qtde        ,
                        '$preco'
                    );";
        }else{
            $pedido_item = pg_result($res,0,0);
            $sql = "UPDATE tbl_pedido_item SET
                           qtde        = qtde + $qtde
                    WHERE  pedido_item = $pedido_item
                    AND    pedido      = $cook_pedido";
        }
        $res = pg_exec ($con,$sql);
//echo "SQL grava item: $sql <br>";

        $msg_erro = pg_errormessage($con);

        if (strlen ($msg_erro) == 0) 
            $res = pg_exec ($con,"COMMIT TRANSACTION");
        else
            $res = pg_exec ($con,"ROLLBACK TRANSACTION");
    }
}

include"cabecalho.php";

// se deu erro
if (strlen($msg_erro) > 0){
	echo "<tr>\n";
	echo "<td bgcolor='#FF0066'>\n";
	echo "<center><font size='3' color='#ffffff'><b>\n";
	echo $msg_erro;
	echo "</b></font></center>\n";
	echo "</td>\n";
	echo "</tr>\n";
}

?>
		<tr>
			<td>
				<br><br>
				<form name='frm' method='post' action='<? echo $PHP_SELF ?>'>
				<input type="hidden" name="peso" value="<?echo $peso;?>">
				<input type="hidden" name="cep" value="<?echo $cep;?>">
				<input type="hidden" name="btn_acao" value="">
				<input type="hidden" name="peca" value="">
				<input type="hidden" name="item" value="">

				<table border="0" cellspacing="2" cellpadding="3" width='80%' align='center'>
<?
//echo "Cookie: < $cook_pedido ><br><br>";
if (strlen($cook_pedido) > 0 OR strlen($cookie_login['cook_pedido']) > 0){
	$sql = "SELECT  tbl_pedido_item.pedido_item,
					tbl_peca.peca              ,
					tbl_peca.referencia        ,
					tbl_peca.descricao         ,
					tbl_pedido_item.qtde       ,
					tbl_tabela_item.preco      
			FROM	tbl_pedido_item 
			JOIN	tbl_peca        ON tbl_peca.peca        = tbl_pedido_item.peca
			JOIN	tbl_tabela_item ON tbl_tabela_item.peca = tbl_peca.peca 
			WHERE	tbl_peca.fabrica       = $fabrica
			and		tbl_pedido_item.pedido = $cook_pedido";
	$res = pg_exec($con,$sql);
	$linhas = pg_numrows($res);

	if ($linhas > 0){
		echo "<tr bgcolor='#FFCC99'>\n";
		echo "<td><B>PRODUTO</B></td>\n";
		echo "<td width='50' align='center'><B>QTDE</B></td>\n";
		echo "<td width='70' align='center'><B>R$ UNIT</B></td>\n";
		echo "<td width='70' align='center'><B>R$ TOTAL</B></td>\n";
		echo "<td width='20' align='center'><B>EXC</B></td>\n";
		echo "</tr>\n";
		
		for ($i=0; $i<$linhas; $i++){
			$pedido_item = pg_result($res,$i,pedido_item);
			$peca        = pg_result($res,$i,peca);
			$referencia  = pg_result($res,$i,referencia);
			$descricao   = pg_result($res,$i,descricao);
			$qtde        = pg_result($res,$i,qtde);
			$preco       = pg_result($res,$i,preco);
			
			$Xpreco      = number_format($preco,2,',','.');
			
			$total       = $preco * $qtde;
			$Xtotal      = number_format($total,2,',','.');
			
			$total_pedido += $total;
			
			echo "<tr bgcolor='#fdfdfd'>\n";
			echo "<td align='left'>&middot; $referencia - $descricao<input type='hidden' name='pedido_item_$i' value='$pedido_item'></td>\n";
			echo "<td align='center'><input type='text' name='qtde_$i' value='$qtde' size='1' maxlength='2'></td>\n";
			echo "<td align='right'>R$ $Xpreco</td>\n";
			echo "<td align='right'>R$ $Xtotal</td>\n";
			echo "<td align='center'><input type='button' name='excluir_$i' value=' X ' onClick=\"excluir_item($peca,$pedido_item)\"></td>\n";
			echo "</tr>\n";
		}
		// CEP
		echo "<tr align=\"center\">\n";
		echo "<td colspan=\"2\"><font face=\"Tahoma\" color=\"#000000\"><small>&nbsp; Digite o CEP do endereço de entrega: </small></font></td>\n";
		echo "<td><INPUT TYPE=\"text\" NAME=\"cep\" size='10' maxlength='10' value = '$cep'><INPUT TYPE=\"button\" value=' OK ' onclick=\"javascript: document.forms[0].btn_acao.value='consultacep'; document.forms[0].submit() ;\"></td>\n";
		$Xvalor_cep = number_format($valor_cep,2,',','.');
		echo "<td align=\"right\">R$ ".$Xvalor_cep."</td>\n";
		echo "<td></td>\n";
		echo "</tr>\n";

		// TOTALIZACAO
		$total_pedido += $valor_cep;
		$Xtotal_pedido = number_format($total_pedido,2,',','.');
		echo "<tr bgcolor='#ffcc99'>\n";
		echo "<td colspan='3' align='right'><B>TOTAL DO PEDIDO</B></td>\n";
		echo "<td align='right'><B>R$ $Xtotal_pedido</B></td>\n";
		echo "<td>&nbsp;</td>\n";
		echo "</tr>\n";

		// BOTÕES
		echo "<tr>\n";
		echo "<td height=\"39\" align=\"center\"><font face=\"Verdana\">Se você alterou a quantidade de algum produto, clique ao lado em &quot;<b>Atualizar</b>&quot;:</font></td>\n";
		echo "<td align=\"center\"><input type='button' name='atualizar' value=' Atualizar ' onclick=\"javascript: document.forms[0].btn_acao.value='atualizar'; document.forms[0].submit();\"></td>\n";
		echo "<td align=\"center\"><input type='button' name='voltar'    value='<< Voltar' onclick=\"javascript:window.location='index.php'\"></td>\n";
		echo "<td align=\"center\"><input type='button' name='continuar' value='Fechar >>' onclick=\"javascript: document.forms[0].btn_acao.value='continuar'; document.forms[0].submit();\"></td>\n";
		echo "</tr>\n";

		echo "<input type=\"hidden\" name=\"qtde_itens\" value=\"$linhas\">";

	}else{
		echo "<tr>\n";
		echo "<td align='center'>SEU PEDIDO AINDA ESTÁ SEM PRODUTOS!!!</td>\n";
		echo "</tr>\n";
	}
}else{
	echo "<tr>\n";
	echo "<td align='center'>SEU PEDIDO AINDA ESTÁ SEM PRODUTOS!!!</td>\n";
	echo "</tr>\n";
}

?>
				</table>
				</form>
			</td>
		</tr>

<?
include"rodape.php";
?>

<script language="javascript">
<!--
function fecha_compra(total,sedex,pedido) {
    if (sedex=="0") {
        window.alert("Por favor, informe a região da entrega.");
        return;
    }
    location = 'pedido_finaliza.php';
}

function excluir_item(peca, item) {
    if (confirm('Deseja realmente retirar esse produto do seu pedido?') == true){
        document.forms[0].btn_acao.value = 'excluir';
        document.forms[0].peca.value = peca;
        document.forms[0].item.value = item;
        document.forms[0].submit ();
    }
}

function limpa_string(S){
// Deixa so' os digitos no numero
var Digitos = "0123456789";
var temp = "";
var digito = "";
    for (var i=0; i<S.length; i++){
      digito = S.charAt(i);
      if (Digitos.indexOf(digito)>=0){temp=temp+digito}
    }
    return temp
}
//-->
</script>
