<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

@session_start("importa_lbm");
if(!isset($_SESSION["importa_lbm"])){
	$_SESSION['importa_lbm'] = Array();
}

//print_r($_SESSION['importa_lbm']);

$btn_importar = trim($_POST['btn_importar']);
if($btn_importar == 'Importar'){
    $diretorio = "xls/";

    if(!empty($_FILES["arquivo"]["name"])){
        extract($_FILES["arquivo"]);
       
        if($type == 'application/vnd.ms-excel'){
            $ext = end(explode(".",$name));
            $arquivo_completo = $diretorio."lbm_excel-upload-{$login_admin}.{$ext}";
             
            if (@move_uploaded_file($tmp_name, $arquivo_completo)) {
               
                require_once 'xls_reader.php';
                $data = new Spreadsheet_Excel_Reader();
                $data->setOutputEncoding('CP1251');
                $data->read($arquivo_completo);
                
                $total_len = strlen($data->sheets[0]['numRows']);
                if($data->sheets[0]['numCols'] == 3) {
                    $sql = "SELECT tbl_produto.produto, tbl_produto.referencia INTO TEMP tmp_lbm_produto_{$login_admin} FROM tbl_produto JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha WHERE tbl_linha.fabrica = {$login_fabrica};";
                    $res = pg_query ($con,$sql);

                    $sql = "SELECT tbl_peca.peca, tbl_peca.referencia INTO TEMP tmp_lbm_peca_{$login_admin} FROM tbl_peca WHERE tbl_peca.fabrica = {$login_fabrica};";
                    $res = pg_query ($con,$sql);
                    
                    $t_insert = 0;
                    $t_update = 0;
                    for ($i = 1; $i <= $data->sheets[0]['numRows']; $i++) { //$data->sheets[0]['numCols'];
                        $linha = str_pad( $i, $total_len, '0', STR_PAD_LEFT );
                        $dados = $data->sheets[0]['cells'][$i];

                        $produto_referencia = $dados[1];
                        $peca_referencia    = $dados[2];
                        $quantidade         = intval($dados[3]);

                        //verifica se o produto existe
                        if(!empty($produto_referencia)){
                            $sql = "SELECT produto FROM tmp_lbm_produto_{$login_admin} WHERE referencia = '{$produto_referencia}';";
                            $res = pg_query ($con,$sql);
                            if (pg_num_rows ($res) == 1) {
                                $produto = pg_fetch_result($res, 0, 'produto');
                            }else{
                                $erro[] = "Linha {$linha} - Referência do Produto '{$produto_referencia}' não cadastrada!";
                            }

                        }else{
                            $erro[] = "Linha {$linha} - Referência do produto não encontrada!";
                        }

                        //verifica se o peça existe
                        if(!empty($peca_referencia)){
                            $sql = "SELECT peca FROM tmp_lbm_peca_{$login_admin} WHERE referencia = '{$peca_referencia}';";
                            $res = pg_query ($con,$sql);
                            if (pg_num_rows ($res) == 1) {
                                $peca = pg_fetch_result($res, 0, 'peca');
                            }else{
                                $erro[] = "Linha {$linha} - Referência da peça '{$peca_referencia}' não cadastrada!";
                            }
                        }else{
                            $erro[] = "Linha {$linha} - Referência da peça não encontrada!";
                        }

                        //verifica a quantidade
                        if(intval($produto) > 0 AND intval($peca) > 0){
                            $quantidade = intval($quantidade);

                            $sql = "SELECT lista_basica FROM tbl_lista_basica WHERE produto = $produto AND peca = $peca AND fabrica = $login_fabrica;"; 
                            $res = pg_query ($con,$sql);
                            if (pg_num_rows ($res) == 1) {
                                $lista_basica = pg_fetch_result($res, 0, 'lista_basica'); 
                            }

                            if(!empty($lista_basica)){
                                $sql = "UPDATE tbl_lista_basica SET qtde = $quantidade WHERE fabrica = {$login_fabrica} AND lista_basica = {$lista_basica}";
                                $t_update += 1;
                            }else{
                                if(!empty($peca) AND !empty($produto)){
                                    $sql = "INSERT INTO tbl_lista_basica (fabrica, peca, produto, qtde) VALUES ({$login_fabrica}, {$peca}, {$produto}, {$quantidade})";
                                    $t_insert += 1; 
                                }
                            }
                            $res = pg_query ($con,$sql);
                        }

                        $produto        = null;
                        $peca           = null;
                        $lista_basica   = null;
                    }


                }else{
                    $msg_erro = "Por favor, verificar o conteúdo de Excel, está faltando algumas colunas!";
                }

                if(strlen($msg_erro) == 0){
                    $sql = "SELECT nome_completo,  email, ultimo_ip FROM tbl_admin WHERE admin = {$login_admin}";
                    $res = pg_query($con, $sql);
                    
                    $email_admin    = pg_fetch_result($res,0,'email');
                    $nome_completo  = pg_fetch_result($res,0,'nome_completo');
                    $ultimo_ip      = pg_fetch_result($res,0,'ultimo_ip');

                    $headers  = 'From: helpdesk@telecontrol.com.br' . "\r\n" ;
                    $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
                    $headers .= 'Reply-To: helpdesk@telecontrol.com.br' . "\r\n" ;
                    $headers .= "X-Priority: 1 (Higuest)\r\n"; 
                    $headers .= "X-MSMail-Priority: High\r\n"; 
                    $headers .= 'MIME-Version: 1.0' . "\r\n"; 
                    $headers .= 'X-Mailer: PHP/' . phpversion();  

                    $to      = $email_admin;
                    $assunto = "Log - Importação de Lista Básica Upload Excel";
                    
                    $msg     = $assunto."<br />";
                    $msg     .= "Programa: Cadastramento de Lista Básica Upload Excel<br />";
                    $msg     .= "Arquivo Excel: {$name}<br />";
                    $msg     .= "Responsável: {$ultimo_ip} - {$nome_completo}<br /><br /><hr />################################# Resumo Importação #################################<br />";
                    $msg     .= "Total Insert: {$t_insert}<br />";
                    $msg     .= "Total Update: {$t_update}<br />";
                    $msg     .= "Total Erro: ".count($erro)."<br /><hr /><br /><br />";

                    if(count($erro) > 0)
                        $msg     .= implode("<br />",$erro);

                    $msg     .= "<br /><br />Att.<br />Telecontrol - Gestão Pós-Venda 100% WEB<br />www.telecontrol.com.br";
                 
                    //ini_set ( "SMTP", "smtp.telecontrol.com.br" );
                    if(mail($to, utf8_encode($assunto), utf8_encode($msg), $headers))
                    $status_email = "enviado";

                    $_SESSION['importa_lbm'] = Array(
                                                "insert"    =>  $t_insert,
                                                "update"    =>  $t_update,
                                                "erro"      =>  COUNT($erro),
                                                "status"    =>  $status_email,
                                                "email"    =>  $to  
                                            );
                    unlink($arquivo_completo);
                    header("Location: $PHP_SELF");
                    exit;
                }
            }else{
                $msg_erro = "Falha ao salvar arquivo no diretório!";
            }
        }else{
            $msg_erro = "Arquivo '{$name}' é inválido!";
        }   
    }else{
        $msg_erro = 'Nenhum arquivo foi selecionado!';
    }
}

$layout_menu = "cadastro";
$title = "CADASTRAMENTO DE LISTA BÁSICA UPLOAD";
include 'cabecalho.php';

$envio = $_SESSION['importa_lbm'];
if($envio['status'] == 'enviado'){
    $msg_erro = "Foi enviado uma email para {$envio['email']}, contendo os logs de importação!";
}
?>

<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>

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
 .titulo_coluna a{
	color:#FFFFFF;
 }

 .titulo_coluna a:hover{
	color:#000000;
 }


.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center !important;
    padding: 5px;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
}

.formulario td{
    text-align: left;
}

.subtitulo{
	background-color: #7092BE;
	color:#FFFFFF;
	font:14px Arial;
}

table.tabela tr td{
font-family: verdana;
font-size: 11px;
border-collapse: collapse;
border:1px solid #596d9b;
}
</style>

<body>

<form name="frm_lbm" id="frm_lbm" method="post" action="<? echo $PHP_SELF ?>" enctype='multipart/form-data'>
	<table width='700' align='center' border='0' cellspacing='0' cellpadding='4' class="formulario">
        <?php
            if(!empty($msg_erro)){
                echo "<tr>";
                    echo "<td class='msg_erro' colspan='4'>{$msg_erro}</td>";
                echo "</tr>";
            }
        ?>
		<tr class="titulo_tabela">
            <th colspan="4">Cadastrar Lista Básica com arquivo Excel</th>
        </tr>
        <tr>
            <td width='150px'>&nbsp;</td>
            <td width='200px'>&nbsp;</td>
            <td width='200px'>&nbsp;</td>
            <td width='150px'>&nbsp;</td>
        </tr>
        <tr>
            <td colspan='4' style='padding: 20px; '>
                O Layout de arquivo deve ser igual o exemplo abaixo. Não precisa de cabeçalho<br />
                Ex.: <b>Referência do Produto - Referência da Peça - Quantidade </b><br />
                Ex.: <b>NomeArquivo.xls</b>
            </td>
        </tr>
        <tr>
            <td width='150px'>&nbsp;</td>
            <td colspan='3'>
                Arquivo Excel<br />
                <input type='hidden' value='<?=$produto?>' name='produto_excel' />
                <input type='hidden' name='btn_lista' value='listar' />
                <input type='file' name='arquivo' size="50" class="frm" />
            </td>
        </tr>
        <tr>
            <td colspan='4' style='padding: 20px; text-align: center'>
                <input type='hidden' name='btn_acao' value='0' />
                <input type='submit' id='btn_importar' name='btn_importar' value='Importar'  />
            </td>
        </tr>
	</table>
</form>
<br /><br /><br />
<?php
    @session_destroy();   
	include "rodape.php";
?>
</body>
</html>
