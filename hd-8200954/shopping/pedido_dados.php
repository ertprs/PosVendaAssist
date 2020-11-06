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

if (strlen($cookie_login['cook_pedido']) > 0) $cook_pedido = $cookie_login['cook_pedido'];
if (strlen($cookie_login['cep']) > 0)         $cep         = $cookie_login['cep'];
if (strlen($cookie_login['valor_cep']) > 0)   $valor_cep   = $cookie_login['valor_cep'];

if (strlen($_POST["btn_acao"]) > 0) $btn_acao = trim($_POST["btn_acao"]);

if ($btn_acao == 'gravar'){

    $cnpj             = trim($_POST ['cnpj']);
    $nome             = trim($_POST ['nome']);
    $nome_fantasia    = trim($_POST ['nome_fantasia']);
    $ie               = trim($_POST ['ie']);
    $endereco         = trim($_POST ['endereco']);
    $numero           = trim($_POST ['numero']);
    $complemento      = trim($_POST ['complemento']);
    $bairro           = trim($_POST ['bairro']);
    $cep              = trim($_POST ['cep']);
    $cidade           = trim($_POST ['cidade']);
    $estado           = trim($_POST ['estado']);
    $contato          = trim($_POST ['contato']);
    $email            = trim($_POST ['email']);
    $fone             = trim($_POST ['fone']);
    $fax              = trim($_POST ['fax']);

    $capital_interior = '';
    $tipo_posto       = 52;

    if (strlen($cnpj) > 0) $xcnpj = "'".$cnpj."'";

    if (strlen($ie) > 0) $xie = "'".$ie."'";
    else                 $xie = 'null';

    if (strlen($nome) > 0) $xnome = "'".$nome."'";
    else                   $xnome = 'null';

    if (strlen($nome_fantasia) > 0) $xnome_fantasia = "'".$nome_fantasia."'";
    else                            $xnome_fantasia = 'null';

    if (strlen($endereco) > 0) $xendereco = "'".$endereco."'";
    else                       $xendereco = 'null';

    if (strlen($numero) > 0) $xnumero = "'".$numero."'";
    else                     $xnumero = 'null';

    if (strlen($complemento) > 0) $xcomplemento = "'".$complemento."'";
    else                          $xcomplemento = 'null';

    if (strlen($bairro) > 0) $xbairro = "'".$bairro."'";
    else                     $xbairro = 'null';

    if (strlen($cep) > 0){
        $xcep = str_replace (".","",$cep);
        $xcep = str_replace ("-","",$xcep);
        $xcep = str_replace (" ","",$xcep);
        $xcep = "'".substr($xcep,0,8)."'";
    }else{
        $xcep = 'null';
    }

    if (strlen($cidade) > 0) $xcidade = "'".$cidade."'";
    else                     $xcidade = 'null';

    if (strlen($estado) > 0) $xestado = "'".$estado."'";
    else                     $xestado = 'null';

    if (strlen($contato) > 0) $xcontato = "'".$contato."'";
    else                      $xcontato = 'null';

    if (strlen($email) > 0) $xemail = "'".$email."'";
    else                    $xemail = 'null';

    if (strlen($fone) > 0) $xfone = "'".$fone."'";
    else                   $xfone = 'null';

    if (strlen($fax) > 0) $xfax = "'".$fax."'";
    else                  $xfax = 'null';

    $sql = "INSERT INTO tbl_posto (
				nome            ,
				cnpj            ,
				ie              ,
				endereco        ,
				numero          ,
				complemento     ,
				bairro          ,
				cep             ,
				cidade          ,
				estado          ,
				contato         ,
				email           ,
				fone            ,
				fax             ,
				nome_fantasia   
			) VALUES (
				$xnome                   ,
				$xcnpj                   ,
				$xie                     ,
				$xendereco               ,
				$xnumero                 ,
				$xcomplemento            ,
				$xbairro                 ,
				$xcep                    ,
				$xcidade                 ,
				$xestado                 ,
				$xcontato                ,
				$xemail                  ,
				$xfone                   ,
				$xfax                    ,
				$xnome_fantasia          
			)";
    $res = pg_exec ($con,$sql);
    if (pg_errormessage ($con) > 0) $msg_erro = pg_errormessage ($con);

    if (strlen($msg_erro) == 0){
        $sql = "SELECT CURRVAL ('seq_posto')";
        $res = pg_exec ($con,$sql);
        $posto = pg_result ($res,0,0);
        $msg_erro = pg_errormessage ($con);
    }




if (strlen($msg_erro) == 0){
	$codigo_posto            = trim ($_POST['codigo']);
	$senha                   = "'*'";
	$tipo_posto              = trim ($_POST['tipo_posto']);
	$obs                     = trim ($_POST['obs']);
	$transportadora          = trim ($_POST['transportadora']);
	$cobranca_endereco       = trim ($_POST['cobranca_endereco']);
	$cobranca_numero         = trim ($_POST['cobranca_numero']);
	$cobranca_complemento    = trim ($_POST['cobranca_complemento']);
	$cobranca_bairro         = trim ($_POST['cobranca_bairro']);
	$cobranca_cep            = trim ($_POST['cobranca_cep']);
	$cobranca_cidade         = trim ($_POST['cobranca_cidade']);
	$cobranca_estado         = trim ($_POST['cobranca_estado']);
	$desconto                = trim ($_POST['desconto']);
	$pedido_em_garantia      = trim($_POST ['pedido_em_garantia']);
	$coleta_peca            = trim($_POST ['coleta_peca']);
	$reembolso_peca_estoque  = trim($_POST ['reembolso_peca_estoque']);
	$pedido_faturado         = trim($_POST ['pedido_faturado']);
	$digita_os               = trim($_POST ['digita_os']);
	$prestacao_servico       = trim($_POST ['prestacao_servico']);
	$banco                   = trim($_POST ['banco']);
	$agencia                 = trim($_POST ['agencia']);
	$conta                   = trim($_POST ['conta']);
	$favorecido_conta        = trim($_POST ['favorecido_conta']);
	$cpf_conta               = trim($_POST ['cpf_conta']);
	$tipo_conta              = trim($_POST ['tipo_conta']);
	$obs_conta               = trim($_POST ['obs_conta']);
	$pedido_via_distribuidor = trim($_POST ['pedido_via_distribuidor']);
}



/////////////

$sql = "INSERT INTO tbl_posto_fabrica (
			posto                  ,
			fabrica                ,
			codigo_posto           ,
			senha                  ,
			desconto               ,
			tipo_posto             ,
			obs                    ,
			transportadora         ,
			cobranca_endereco      ,
			cobranca_numero        ,
			cobranca_complemento   ,
			cobranca_bairro        ,
			cobranca_cep           ,
			cobranca_cidade        ,
			cobranca_estado        ,
			pedido_em_garantia     ,
			reembolso_peca_estoque ,
			coleta_peca           ,
			pedido_faturado        ,
			digita_os              ,
			prestacao_servico      ,
			banco                  ,
			agencia                ,
			conta                  ,
			nomebanco              ,
			favorecido_conta       ,
			cpf_conta              ,
			tipo_conta             ,
			obs_conta              ,
			pedido_via_distribuidor,
			item_aparencia         ,
			data_alteracao         ,
			admin                  
		) VALUES (
			$posto                   ,
			$login_fabrica           ,
			$xcodigo                 ,
			$xsenha                  ,
			$xdesconto               ,
			$xtipo_posto             ,
			$xobs                    ,
			$xtransportadora         ,
			$xcobranca_endereco      ,
			$xcobranca_numero        ,
			$xcobranca_complemento   ,
			$xcobranca_bairro        ,
			$xcobranca_cep           ,
			$xcobranca_cidade        ,
			$xcobranca_estado        ,
			$xpedido_em_garantia     ,
			$xreembolso_peca_estoque ,
			$xcoleta_peca           ,
			$xpedido_faturado        ,
			$xdigita_os              ,
			$xprestacao_servico      ,
			$xbanco                  ,
			$xagencia                ,
			$xconta                  ,
			$xnomebanco              ,
			$xfavorecido_conta       ,
			$xcpf_conta              ,
			$xtipo_conta             ,
			$xobs_conta              ,
			$xpedido_via_distribuidor,
			'$item_aparencia'        ,
			current_timestamp        ,
			$login_admin             
		)";
}
$res = pg_exec ($con,$sql);
if (strlen (pg_errormessage ($con)) > 0) $msg_erro = pg_errormessage($con);




/////////////










/////////////////////////////


	if (strlen($_POST["cnpj"]) > 10){
		$cnpj  = trim($_POST['cnpj']);
		$xcnpj = str_replace (".","",$cnpj);
		$xcnpj = str_replace ("-","",$xcnpj);
		$xcnpj = str_replace ("/","",$xcnpj);
		$xcnpj = str_replace (" ","",$xcnpj);
		if (strlen($xcnpj) == 11 OR strlen($xcnpj) == 14){
			$sql = "SELECT tbl_posto_fabrica.posto 
					FROM   tbl_posto_fabrica 
					JOIN   tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto 
					WHERE  tbl_posto_fabrica.fabrica = $fabrica 
					AND    tbl_posto.cnpj            = '$xcnpj'";
			$res = pg_exec ($con,$sql);

			if (pg_numrows($res) == 0){
				header("Location: pedido_dados.php");
				exit;
			}else{
				$posto = pg_result($res,0,0);
				
				$sql   = "SELECT sum(tbl_pedido_item.preco * tbl_pedido_item.qtde) AS total_compra, sum(tbl_peca.peso * tbl_pedido_item.qtde) AS total_peso FROM tbl_pedido_item join tbl_peca using(peca) WHERE tbl_pedido_item.pedido = $cook_pedido";
				$res   = pg_exec ($con,$sql);
				
				$total_pecas = pg_result ($res,0,total_compra);
				$total = $valor_cep + $total_pecas;
				
				$sql = "UPDATE tbl_pedido SET posto = $posto, total = $total WHERE fabrica = $fabrica and pedido = ".$cook_pedido;
				$res = pg_exec ($con,$sql);
				
				header("Location: pedido_confirmacao.php");
				exit;
			}
		}else{
			$msg_erro = "Digite o CNPJ/CPF corretamente.<br>CNPJ formato&nbsp;: 00.000.000/0000-00<br>ou CPF formato&nbsp;: 000.000.000-00&nbsp;&nbsp;";
		}
	}else{
		$msg_erro = "Digite o CNPJ/CPF corretamente.<br>CNPJ formato&nbsp;: 00.000.000/0000-00<br>ou CPF formato&nbsp;: 000.000.000-00&nbsp;&nbsp;";
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
				<input type="hidden" name="btn_acao" value="">
				<table border="0" cellspacing="2" cellpadding="3" width='80%' align='center'>
<?
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
			AND		tbl_pedido_item.pedido = $cook_pedido";
	$res = pg_exec($con,$sql);
	$linhas = pg_numrows($res);

	echo "<tr bgcolor='#FFCC99'>\n";
	echo "<td colspan='2'><B>DIGITE OS DADOS (para confirmação de cadastro)</B></td>\n";
	echo "</tr>\n";
	
	echo "<tr bgcolor='#fdfdfd'>\n";
	echo "<td align='left'><B>CNPJ/CPF</B></td>\n";
	echo "<td align='left'><input type=\"text\" name=\"cnpj\" value='$cnpj' maxlength='19'></td>\n";
	echo "</tr>\n";
	echo "<tr bgcolor='#fdfdfd'>\n";
	echo "<td colspan='2' align=\"center\"><input type='button' name='gravar' value='Confirmar >>' onclick=\"javascript: document.forms[0].btn_acao.value='gravar'; document.forms[0].submit();\"></td>\n";
	echo "</tr>\n";
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
