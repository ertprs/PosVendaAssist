<?php
 
    include 'dbconfig.php';
    include 'includes/dbconnect-inc.php';
    $admin_privilegios="call_center";
    include 'autentica_admin.php';
    include '../class/AuditorLog.php';

    include 'funcoes.php';

    if(isset($_POST['gerar_excel'])){
        $tabela         = $_POST['tabela'];        
        $nome_tabela    = $_POST["nome_tabela"];

        $nome_arquivo   = "pecas_".$nome_tabela."_".date("YmdHms").".csv";
        $arq_valor      = fopen("xls/".$nome_arquivo, "a");
        if($login_fabrica == 140){
            $head = "Código Tabela de Preço;Código da Peça;Descrição da Peça;Preço da Peça;\r\n";
        }else{
            $head = "Referência;Descrição;Preço; \r\n";
        }

        fwrite($arq_valor, $head);

        $sql_excel .= " SELECT 
                tbl_tabela_item.tabela_item ,
                tbl_tabela.sigla_tabela,
                tbl_peca.referencia ,
                tbl_peca.descricao ,
                tbl_tabela_item.preco
                FROM tbl_tabela_item
                JOIN tbl_peca ON tbl_peca.peca = tbl_tabela_item.peca AND tbl_peca.fabrica = $login_fabrica
                JOIN tbl_tabela on tbl_tabela_item.tabela = tbl_tabela.tabela
            ORDER BY tbl_tabela.sigla_tabela DESC";
        
        $res = pg_query($con, $sql_excel);
        for($i = 0; $i<pg_num_rows($res); $i++){
            $peca_referencia    = pg_fetch_result($res, $i, referencia);
            $peca_descricao     = pg_fetch_result($res, $i, descricao);
            $valor              = pg_fetch_result($res, $i, preco);

            $peca_referencia    = str_replace(";", " ", $peca_referencia);
            $peca_referencia    = str_replace(array("'", '"'), "", $peca_referencia);
            $peca_referencia    = addslashes($peca_referencia);

            $peca_descricao     = str_replace(";", " ", $peca_descricao);
            $peca_descricao     = str_replace(array("'", '"'), "", $peca_descricao);
            $peca_descricao     = addslashes($peca_descricao);
            $codigo_tabela = pg_fetch_result($res, $i, sigla_tabela);

            if($login_fabrica == 140){
                $body .= "\"$codigo_tabela\";\"$peca_referencia\";\"$peca_descricao\";$valor; \r\n";
            }else{
                $body .= "\"$codigo_tabela\";\"$peca_referencia\";\"$peca_descricao\";$valor; \r\n";
            }
        }

        fwrite($arq_valor, $body);
        fclose($arq_valor);

        if (file_exists("xls/{$nome_arquivo}")) {
            echo "xls/{$nome_arquivo}";
        }
        exit;
    }

    if($_GET['cod_msg'] == 1){
        $sql = "SELECT email FROM tbl_admin WHERE admin = {$login_admin}";
        $res = pg_query($con, $sql);

        if(pg_num_rows($res) == 1){
            $email = pg_fetch_result($res, 0, 'email');
            if(!empty($email))
                $msg_erro = traduz("Foi enviado um email para '{$email}' referente ao envio de arquivo.");
            else
                $msg_erro = traduz("Erro cadastro admin: existe LOG no arquivo enviado, porém não foi encontrado email no cadastro do admin. Atualize seu cadastro!");
        }
    }
    $n_peca = $_GET['n_peca'];

    $tabela     = intval(@$_REQUEST['tabela']);
    $btn_acao   = @$_REQUEST['btn_acao'];
    $auditorLog = new AuditorLog();
    //verifica se o BTN_ACAO está vazio, caso esteja é considerado um edição!
    if(empty($btn_acao)){
        $sql = "SELECT
                    tabela, 
                    sigla_tabela,
                    descricao, 
                    ativa,
                    ordem,
                    zerar_ipi,
                    tabela_garantia,
                    TO_CHAR(data_vigencia::DATE,'DD/MM/YYYY') AS data_vigencia,
                    TO_CHAR(termino_vigencia::DATE,'DD/MM/YYYY') AS termino_vigencia
                FROM 
                    tbl_tabela 
                WHERE
                    fabrica = $login_fabrica
                    AND tabela = {$tabela}
                ORDER BY ordem ASC";
        $res = pg_exec($con,$sql);

        if(pg_num_rows($res) > 0)
             extract(pg_fetch_array($res));
    }else{
        //valida campos		
        extract($_POST);
        $ordem = intval($ordem);
		$sigla_tabela = trim($sigla_tabela);
        $msg_erro = null;

        $upload_tabela = $_FILES['upload_tabela']['name'];
		
        if(empty($upload_tabela) OR !empty($sigla_tabela) OR !empty($descricao) OR !empty($tabela_garantia) OR !empty($ativa)){
            if(empty($sigla_tabela))
                $msg_erro[] = traduz("Informe um código para tabela");

            if(empty($descricao))
                $msg_erro[] = traduz("Informe um descrição para tabela");

            if(empty($tabela_garantia))
                $msg_erro[] = traduz("Informe se a tabela é de garantia");

            if(empty($ativa))
                $msg_erro[] = traduz("Informe um status");
        }
        
        if(count($msg_erro) == 0 AND !empty($sigla_tabela)){
			$zerar_ipi = ($zerar_ipi == "") ? 'f' : $zerar_ipi;
            //se estiver vazio insere

            if(empty($tabela)){
                $cond_1 = "  sigla_tabela = '$sigla_tabela' ";
            }else{
                $cond_1 = "  tabela = '$tabela' ";
            }
            $campo_data_vigencia    = "";
            $campo_termino_vigencia = "";
            $up_campo_data_vigencia    = "";
            $up_campo_termino_vigencia = "";
            $valor_data_vigencia = "";
            $valor_termino_vigencia = "";

            if ($login_fabrica == 195 && strlen($data_vigencia)) {
                list($diadv,$mesdv,$anodv) = explode("/", $data_vigencia);
                $up_campo_data_vigencia    = ",data_vigencia='$anodv-$mesdv-$diadv 23:59:59'";
                $campo_data_vigencia    = ",data_vigencia";
                $valor_data_vigencia    = ",'$anodv-$mesdv-$diadv 23:59:59'";
            }



            if ($login_fabrica == 195 && strlen($termino_vigencia)) {
                list($diatv,$mestv,$anotv) = explode("/", $termino_vigencia);
                $up_campo_termino_vigencia = ",termino_vigencia='$anotv-$mestv-$diatv 23:59:59'";
                $campo_termino_vigencia = ",termino_vigencia";
                $valor_termino_vigencia = ",'$anotv-$mestv-$diatv 23:59:59'";
            }



            $sql_auditor = "SELECT tabela, sigla_tabela, descricao, ativa, tabela_garantia {$campo_data_vigencia} {$campo_termino_vigencia} FROM tbl_tabela WHERE $cond_1 AND fabrica = $login_fabrica";
            $auditorLog->retornaDadosSelect($sql_auditor);

            if(empty($tabela)){
                //verifica se o código já está cadastrado
                $sql = "SELECT sigla_tabela FROM tbl_tabela WHERE sigla_tabela = '{$sigla_tabela}' AND fabrica = $login_fabrica;";
                $res = pg_exec($con,$sql);

                if(pg_num_rows($res) > 0){
                    $msg_erro[] = traduz("Código já cadastrado!"); 
                }else{

                   
					
                    $sql = "
                        INSERT INTO tbl_tabela(
                            sigla_tabela,
                            descricao, 
                            ativa,
                            ordem,
                            tabela_garantia,
                            zerar_ipi,
                            fabrica
                            {$campo_data_vigencia}
                            {$campo_termino_vigencia}
                        )VALUES(
                            '{$sigla_tabela}',
                            '{$descricao}', 
                            '{$ativa}',
                            '{$ordem}',
                            '{$tabela_garantia}',
                            '{$zerar_ipi}',
                            '{$login_fabrica}'
                            {$valor_data_vigencia}
                            {$valor_termino_vigencia}

                        );";
                    pg_query($con,$sql);
                    $auditorLog->retornaDadosSelect()->enviarLog('insert', "tbl_tabela", $login_fabrica."*"."$login_fabrica");
                    if(pg_last_error($con))
                        $msg_erro[] = traduz("Erro ao cadastrar tabela.")."<erro>".pg_last_error($con)."</erro>";
                    elseif(empty($upload_tabela)){
                        echo "<script type='text/javascript'>window.location = '{$PHP_SELF}';</script>";
                        exit;
                    }
                }
            }else{
                $sql = "
                    UPDATE tbl_tabela SET
                        sigla_tabela = '{$sigla_tabela}',
                        descricao = '{$descricao}',
                        ativa = '{$ativa}',
                        ordem = '{$ordem}',
                        tabela_garantia = '{$tabela_garantia}',
                        zerar_ipi = '{$zerar_ipi}'
                        {$up_campo_data_vigencia}
                        {$up_campo_termino_vigencia} 
                    WHERE tabela = {$tabela};";
                pg_query($con,$sql);
                $auditorLog->retornaDadosSelect()->enviarLog('insert', "tbl_tabela", $login_fabrica."*"."$login_fabrica");
               if(pg_last_error($con))
                    $msg_erro[] = traduz("Erro ao atualizar tabela").".<erro>".pg_last_error($con)."</erro>";
               elseif(empty($upload_tabela)){
                    echo "<script type='text/javascript'>window.location = '{$PHP_SELF}';</script>";
                    exit;
               }
            }
            //echo nl2br($sql);            
        }
        if(empty($msg_erro) AND !empty($upload_tabela)){
            $_UP['pasta']       = '/tmp/';
            $_UP['tamanho']     = 1024 * 1024 * 2; // 2Mb
            $_UP['extensoes']   = array('TXT','txt');
            $_UP['renomeia']    = true;

			$extensao = strtolower(end(explode('.', $_FILES['upload_tabela']['name'])));
            if (array_search($extensao, $_UP['extensoes']) === false) {
                $msg_erro[] = traduz("Por favor, envie arquivos com as seguintes extensões").": ".implode(',',$_UP['extensoes']);
            }else if ($_UP['tamanho'] < $_FILES['upload_tabela']['size']) {
                $msg_erro[] = traduz("O arquivo enviado é muito grande, envie arquivos de até {$_UP['tamanho']}Mb.");
            }else {
                if ($_UP['renomeia'] == true) {
                    $arquivo_final = time().'.'.$extensao;
                } else {
                    $arquivo_final = $_FILES['upload_tabela']['name'];
                }

                if (move_uploaded_file($_FILES['upload_tabela']['tmp_name'], $_UP['pasta'] . $arquivo_final)) {
                    unset($_POST);
                    $n_peca = false;
                    $nome_arquivo_log = "upload_log_tabela_preco_".date("YmdHms").".csv";
                    $arq_log = fopen($_UP['pasta'].$nome_arquivo_log, "a");
                    $ponteiro = fopen ($_UP['pasta'] . $arquivo_final, "r");

                    $linha = 0;
                    while (!feof ($ponteiro)) {
                        $linha += 1;
                        $x_linha = sprintf("%06d", $linha); 
                        $dados = fgets($ponteiro, 4096);
                        $data = explode(";",$dados);

                        //somente para facilitar e evitar acidente de programação
                                                
                        if($login_fabrica == 158){
                            $x_referencia_peca  = trim($data[0]);
                            $x_tabela           = trim($data[1]);                            
                            $x_acao           = strtoupper(trim($data[3]));  
                        }else{
                            $x_tabela           = trim($data[0]); 
                            $x_referencia_peca  = trim($data[1]);    
                        }
                        $x_preco            = trim($data[2]);                        
						$x_preco = str_replace(",",".",$x_preco);

                        //valida o arquivo linha a linha
                        if(count($data) <> 3 and $login_fabrica != 158){
                            $msg_erro_upload[] = traduz("ERRO: Linha {$x_linha}, formato de layout inválido!");
                        }else{

                            if($login_fabrica == 158){
                                if(count($data) <> 4){
                                    $msg_erro_upload[] = traduz("ERRO: Linha {$x_linha}, formato de layout inválido!");
                                }
                            }                            
                            //valida se a tabela existe
                            $sql = "SELECT sigla_tabela, tabela FROM  tbl_tabela WHERE fabrica = {$login_fabrica} AND sigla_tabela = '{$x_tabela}';";
                            $res = pg_query($con,$sql);

                            if(pg_num_rows($res) > 0){
                                $tabela = pg_fetch_result($res,0,"tabela");

                                //verifica se a referencia é valida para a fabrica
                                $sql = "SELECT peca FROM tbl_peca WHERE fabrica = {$login_fabrica} AND referencia = '{$x_referencia_peca}'";
                                $res = pg_query($con,$sql);

                                if(pg_num_rows($res) > 0){
                                    $peca = pg_fetch_result($res,0,"peca");
                                    $sql_auditor = "SELECT 
                                                    tbl_tabela_item.tabela_item , tabela,
                                                    tbl_tabela_item.peca,
                                                    tbl_tabela_item.preco
                                                    FROM tbl_tabela_item
                                                    WHERE 
                                                     tbl_tabela_item.tabela = $tabela 
                                                     and tbl_tabela_item.peca = $peca";
                                    $auditorLog->retornaDadosSelect($sql_auditor);
                                    //validar o preco
                                    if(!preg_match('\d+\.\d{1,2}$',$x_preco)){

                                        //verifica se peca já tem cadastro para determinada tabela
                                        $sql = "SELECT tabela_item FROM tbl_tabela_item WHERE peca = {$peca} AND tabela = {$tabela}";
                                        $res = pg_query($con,$sql);

                                        if(pg_num_rows($res) > 0){
                                            $tabela_item = pg_fetch_result($res,0,"tabela_item");
                                        }else{
                                            $tabela_item = "";
                                        }
                                        
                                        if($x_acao == 'APAGAR'){
                                            $sql = "DELETE FROM tbl_tabela_item WHERE tabela_item = {$tabela_item};";
                                            if(!pg_query($con,$sql)){
                                                $msg_erro_upload[] = traduz("ERRO: Linha {$x_linha}, erro ao apagar os dados!");
                                            }else{
                                                $msg_erro_upload[] = traduz("ALERTA: Linha {$x_linha}, dados apagados!");
                                            }
                                        }else{
                                            if(!empty($tabela_item)){
                                                $sql = "UPDATE tbl_tabela_item SET preco = '$x_preco' WHERE tabela_item = {$tabela_item};";
                                                if(!pg_query($con,$sql))
                                                    $msg_erro_upload[] = traduz("ERRO: Linha {$x_linha}, erro ao atualizar os dados!");
                                                else
                                                    $msg_erro_upload[] = traduz("ALERTA: Linha {$x_linha}, dados atualizado!");
                                            }else{
                                               $sql = "INSERT INTO tbl_tabela_item (tabela, peca, preco) VALUES ({$tabela}, {$peca}, '{$x_preco}');";
                                                if(!pg_query($con,$sql))
                                                    $msg_erro_upload[] = traduz("ERRO: Linha {$x_linha}, erro ao gravar os dados!");
                                            }
                                        }
                                        $auditorLog->retornaDadosSelect()->enviarLog('insert', "tbl_tabela_item", $login_fabrica."*"."$login_fabrica");
                                    }else{
                                        $msg_erro_upload[] = traduz("ERRO: Linha {$x_linha}, preço '{$x_preco}' inválido!");
                                    }
                                }else{
                                    $msg_erro_upload[] = traduz("ERRO: Linha {$x_linha}, código da peça '{$x_referencia_peca}' inválido!");
                                    $n_peca = true;
                                    fwrite($arq_log, traduz("ERRO: Linha {$x_linha}, código da peça '{$x_referencia_peca}' inválido! \r\n"));
                                }
                            }else{
                                $msg_erro_upload[] = traduz("ERRO: Linha {$x_linha}, código da tabela '{$x_tabela}' inválido!");
                            }
                        }
                    }
                    fclose($arq_log);
                    fclose ($ponteiro);                    

                    unlink($_UP['pasta'] . $arquivo_final);

                    if(count($msg_erro_upload) > 0){
                        $sql = "SELECT nome_completo, email, login FROM tbl_admin WHERE admin = {$login_admin}";
                        $res = pg_query($con, $sql);

                        if(pg_num_rows($res) == 1){
                            $email          = pg_fetch_result($res, 0, 'email');
                            $login          = pg_fetch_result($res, 0, 'login');
                            $nome_completo  = pg_fetch_result($res, 0, 'nome_completo');
                            $assunto    = "Log [Tabela de Peças] - ".Date('d/m/Y H:i:s'); 
                            
                            $body_top = "MIME-Version: 1.0\r\n";
						    $body_top .= "Content-type: text/html; charset=iso-8859-1\r\n";
						    $body_top .= "From: helpdesk@telecontrol.com.br\r\n";

                            $corpo = "--------------------------- LOGs IMPORTAÇÃO ---------------------------";
                            $corpo .= "<br>Arquivo: ".$_FILES['upload_tabela']['name'];
                            $corpo .= "<br>Usuário: {$login} - {$nome_completo}";
                            $corpo .= "<br>Data: ".Date('d/m/Y H:i:s');
                            $corpo .= "<br><br>";

                            $corpo .= implode("<br>",$msg_erro_upload); 

                            $corpo.="<br><br><hr ><br>Telecontrol";
                            $corpo.="<br>www.telecontrol.com.br";

                            mail($email, utf8_encode($assunto), utf8_encode($corpo), $body_top);
                        }
                    }

                } else {
					$msg_erro[] = traduz("Não foi possível enviar o arquivo, tente novamente");
                }
            }

			if(count($msg_erro) == 0) {
				if(count($msg_erro_upload) == 0){
					echo "<script type='text/javascript'>window.location = '{$PHP_SELF}';</script>";
					exit;
				}else{
					echo "<script type='text/javascript'>window.location = '{$PHP_SELF}?cod_msg=1&n_peca=$n_peca';</script>";
					exit;
				}
			}
            
        }
    }


$layout_menu = "cadastro";
$title = traduz("MANUTENÇÃO TABELA DE PREÇOS");
include 'cabecalho_new.php';


$plugins = array(
    "shadowbox",
    "datepicker",
    "mask",
    "shadowbox",
    "dataTable",
);

include("plugin_loader.php");
?>

<script type="text/javascript">
    $(document).ready(function(){
        Shadowbox.init();
        $('#data_vigencia').datepicker();
        $('#termino_vigencia').datepicker();
        $('#data_vigencia').mask('00/00/0000');
        $('#termino_vigencia').mask('00/00/0000');


        $(".ver_pecas").click(function(){
            var tabela = $(this).attr('rel');

            if(tabela.length > 0){
                Shadowbox.open({
                    content :   "tabela_preco_item.php?tabela="+tabela,
                    player  :   "iframe",
                    title   :   '<?=traduz("Gerenciamento de Peças")?>',
                    width   :   800,
                    height  :   500
                });
            }
        });

        $(".gerar_excel").click(function () {
            if (ajaxAction()) {
                var json = $.parseJSON($(this).children(".jsonPOST").val());

                $.ajax({
                    url: "<?=$_SERVER['PHP_SELF']?>",
                    type: "POST",
                    data: json,
                    beforeSend: function () {
                        loading("show");
                    },
                    complete: function (data) {
                        window.open(data.responseText, "_blank");

                        loading("hide");
                    }
                });
            }
        });

    });

</script>
<style>
    .gerar_excel{
        cursor:pointer;
    }
</style>


    <?php
        if(!empty($msg_erro)){
            if(is_array($msg_erro))
                $msg_erro = implode("<br />",$msg_erro);

            echo "
                <div class='alert alert-error'>
                    <h4>{$msg_erro}</h4>
                </div>";
        }
    ?>

    <div class="row-fluid>">
        <div class="alert">
        <?php if($login_fabrica == 158){ ?>
            <?=traduz("Layout do anexo com delimitador 'ponto e vírgula':Referência da peça; Código da tabela; Preço")?><br />
            <strong><?=traduz('Exemplo')?>: <?php echo date('mYs')?>;VEN;500.<?php echo date('i').";"?></strong>
            
        <?}else{ ?>
            <?=traduz("Layout do anexo com delimitador 'ponto e vírgula': Código da tabela; Referência da peça; Preço")?><br />
            <strong><?=traduz('Exemplo')?>: VENDA;<?php echo date('mYs')?>;500.<?php echo date('i')?></strong>
            <? if($login_fabrica == 52){ ?>
                <br><strong><?=traduz("Não separar o milhar por 'ponto', enviar da seguinte forma: 1550.25")?></strong>
            <? } ?>
        </div>
        <?php } ?>
    </div>

    <?php if($login_fabrica == 158 and $n_peca == true){ ?>
        <div class="row-fluid>">
            <div class="alert alert-error">
                <p><?=traduz('Log de peças não cadastradas no sistema')?></p>
                    <a href="/tmp/<?=$nome_arquivo_log?>"><?=traduz('Download')?></a>
            </div>
        </div>
    <?php } ?>



    <form name='frm_tabela' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center'  enctype='multipart/form-data' class='form-search form-inline tc_formulario' >
        <input type='hidden' name='tabela' value='<?php echo $tabela; ?>' />
        <div class="titulo_tabela"><?=traduz('Cadastro da tabela de preço')?></div><br />

        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                <div class='control-group'>
                    <label class='control-label' for='sigla_tabela'><?=traduz('Código')?></label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <?php if (in_array($login_fabrica, array(175))) { 
                                if (strlen($sigla_tabela)) {
                                    $read_only = 'readonly=readonly'; 
                                }
                            } ?>
                            <input type="text" id="sigla_tabela" name="sigla_tabela" class='span12' value="<? echo $sigla_tabela ?>" <?=$read_only;?> />
                        </div>
                    </div>
                </div>
            </div>
            <div class='span4'>
                <div class='control-group'>
                    <label class='control-label' for='produto_descricao'><?=traduz('Descrição')?></label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <input type="text" id="descricao" name="descricao" class='span12' value="<? echo $descricao ?>" >
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>

        <div class="row-fluid">
            <div class="span2"></div>
            <div class='span4'>
                <div class='control-group'>
                    <label class='control-label' for='ativa'><?=traduz('Status')?></label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <select class="span12" name="ativa" id="ativa">
                                <option value=""></option>
                                <option value='t' <?php if($ativa == 't') echo " selected "; ?> ><?=traduz('Ativo')?></option>
                                <option value='f' <?php if($ativa == 'f') echo " selected "; ?>><?=traduz('Inativo')?></option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="span4">
                 <div class='control-group'>
                    
                    <?php if($login_fabrica == 87){ ?>
                  
                    <label class='control-label' for='zerar_ipi'><?=traduz('Zerar IPI')?></label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <input type='hidden' name='tabela_garantia' value='f' />
                            <select class="span12" name="zerar_ipi" id="zerar_ipi">
                                <option value='t' <?php if($zerar_ipi == 't') echo " selected "; ?>><?=traduz('Sim')?></option>
                                <option value='f' <?php if($zerar_ipi == 'f' || empty($zerar_ipi)) echo " selected "; ?>><?=traduz('Não')?></option>
                            </select>
                        </div>
                    </div>
                  
                    <?php }else{ ?>
                    
                    <label class='control-label' for='tabela_garantia'><?=traduz('Garantia')?></label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <select class="span12" name="tabela_garantia" id="tabela_garantia">
                                <option value='' ></option>
                                <option value='t' <?php if($tabela_garantia == 't') echo " selected "; ?> ><?=traduz('Sim')?></option>
                                <option value='f' <?php if($tabela_garantia == 'f') echo " selected "; ?> ><?=traduz('Não')?></option>
                            </select>
                        </div>
                    </div>
                
                    <?php } ?>
                
                </div>
            </div>
        </div>

        <?php if($login_fabrica == 195){ ?>
        <div class="row-fluid">
            <div class="span2"></div>
            <div class="span2"> 
                <label for="data_vigencia"> <?php echo traduz("Data Inicio");?></label>
                <input type="text" name="data_vigencia" id="data_vigencia" maxlength="10" class="input-block-level" autocomplete="off" onclick="if(this.value == 'dd/mm/aaaa') this.value = ''" value="<?= (!empty($data_vigencia) ) ? $data_vigencia : '';?>" />
            </div>
            <div class="span2">
                <label for="termino_vigencia"> <?php echo traduz("Data Fim");?></label>
                <input type="text" name="termino_vigencia" id="termino_vigencia" maxlength="10" class="input-block-level" autocomplete="off" onclick="if(this.value == 'dd/mm/aaaa') this.value = ''" value="<?= (!empty($termino_vigencia)) ? $termino_vigencia : '';?>"/>
            </div>

        </div>
        <?php } ?>


        <div class="row-fluid">
            <div class="span2"></div>
            <div class="span6">
                <div class='control-group'>
                    <label class='control-label' for='upload_tabela'><?=traduz('Upload de dados(somente arquivo com extensão txt)')?></label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <input type="file" id="upload_tabela" name="upload_tabela" class='span12'>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <p><br/>
                    
            <input type="submit" class="btn" name="btn_acao" value='<?=traduz("Gravar")?>' />
            <input type="reset" class="btn" name="reset" value='<?=traduz("Limpar")?>' />
        </p><br/>
        <div class="row-fluid">
            <div class="span2"></div>
            <div class="span8">
                 <br><center><a rel='shadowbox' href='relatorio_log_alteracao_new.php?parametro=tbl_tabela&id=<?php echo "$login_fabrica*$login_fabrica"; ?>' name="btnAuditorLog"><?=traduz('Visualizar Log Auditor - Tabelas')?></a></center>
                 <br>
            </div>
            <div class="span2"></div>
        </div>
        




    </form>

<?php
    
    //Lista todas as tabelas existente para manutenção
    $sql = "SELECT
                tabela, 
                sigla_tabela,
                descricao, 
                ativa,
                ordem,
                zerar_ipi,
                tabela_garantia,
                TO_CHAR(data_vigencia::DATE,'DD/MM/YYYY') AS data_vigencia,
                TO_CHAR(termino_vigencia::DATE,'DD/MM/YYYY') AS termino_vigencia
            FROM 
                tbl_tabela 
            WHERE
                fabrica = $login_fabrica 
            ORDER BY ordem ASC";
	$res = pg_exec($con,$sql);
    $registros = pg_num_rows($res);
	
    if($registros > 0){
        ?>
            <div style="margin-left: 44%;margin-right: 44%;margin-bottom: 2%;">
                <span class="gerar_excel txt">
                <input type="hidden" class="jsonPOST" value='{"gerar_excel":true}' />
                <span><img width='20' height='20' src='imagens/excel.png' /></span>
                <?=traduz('Gerar Excel')?></span>
            </div>
        <?php  
        echo "<table class='table table-striped table-bordered table-hover table-fixed'>";
            echo "<thead>";
            if ($login_fabrica == 195) {
                $colspan = 6;
            } else {
                $colspan = 4;
            }
            echo "<tr class='titulo_tabela'>";
                echo "<th colspan='$colspan'>".traduz('Relação das Tabelas Cadastradas')."".traduz('')."</th>";
            echo "</tr>";

            echo "<tr class='titulo_coluna'>";
                echo "<th>".traduz('Código')."</th>";
                echo "<th>".traduz('Descrição')."</th>";
			if($login_fabrica == 87){
                echo "<th>".traduz('Zerar IPI')."</th>";
			}
             if ($login_fabrica == 195) {
                echo "<th>".traduz('Data Início')."</th>";
                echo "<th>".traduz('Data Fim')."</th>";
             }
                echo "<th>".traduz('Status')."</th>";
                echo "<th>".traduz('Ações')."</th>";
            echo "</tr>";
            echo "</thead>";

          
        for($i = 0; $i < $registros; $i++){
            extract(pg_fetch_array($res));

            $tabela_garantia = $tabela_garantia == 't' ? "Sim" : "Não";
            //$ativa = $ativa == 't' ? "Ativo" : "Inativo";

            if($ativa=='t') $ativa = "<img title='".traduz('Ativo')."' src='imagens/status_verde.png'>";
            else            $ativa = "<img title='".traduz('Inativo')."' src='imagens/status_vermelho.png'>";

            $zerar_ipi = $zerar_ipi == 't' ? traduz("Sim") : traduz("Não");
            $ordem = intval($ordem);

            echo "<tbody>";
            echo "<tr>";
                echo "<td class='tal'>{$sigla_tabela}</td>";
                echo "<td class'tal'>{$descricao}</td>";
				if($login_fabrica == 87){
					echo "<td>{$zerar_ipi}</td>";
				}
                if ($login_fabrica == 195) {
                echo "<td class='tac'>{$data_vigencia}</td>";
                echo "<td class='tac'>{$termino_vigencia}</td>";
                }
                echo "<td class='tac'>{$ativa}</td>";
                echo "<td class='tac'>";

                    echo "<input type='button' class='btn btn-small'  name='alterar' value='".traduz("Alterar")."' onclick='window.location = \"{$PHP_SELF}?tabela={$tabela}\"' />&nbsp;&nbsp;";
                    echo "<input type='button' class='btn btn-small btn-success ver_pecas' name='pecas' value='".traduz("Ver Peças")."'  rel='{$tabela}'/>&nbsp;&nbsp;";
                    echo "<a rel='shadowbox' href='relatorio_log_alteracao_new.php?parametro=tbl_tabela_item&id=$login_fabrica*$tabela'><button class='btn btn-small btn-primary'>".traduz("Log Peças")."</button></a>";




                   if($login_fabrica == 158){ ?>
                            <span class="gerar_excel txt">
                            <input type="hidden" class="jsonPOST" value='{"gerar_excel":true,"tabela":"<?=$tabela?>", "nome_tabela": "<?=$descricao?>"}' />
                            <span><img width='20' height='20' src='imagens/excel.png' /></span>
                            <?=traduz('Gerar Excel')?></span>
                        
                    <?php }
                echo "</td>";
            echo "</tr>";
            echo "</tbody>";
        }
        echo "</table>";
    }
?>

<?php
    echo "<br /><br />";
    include "rodape.php"; 
?>
