<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

$causa_defeito = trim($_GET["causa_defeito"]);
if (strlen ($causa_defeito) == 0) {
    $causa_defeito = trim($_POST["causa_defeito"]);
}

$btnacao = trim($_POST["btn_acao"]);

if ($btnacao == "deletar" and strlen($causa_defeito) > 0 ) {

    $res = pg_query($con,"BEGIN TRANSACTION");
    
    $sql = "DELETE FROM tbl_causa_defeito
                  WHERE fabrica = $login_fabrica
                    AND causa_defeito = $causa_defeito;";
    $res = pg_query($con,$sql);
    if (strlen(pg_last_error($con)) > 0) {
        $msg_erro["msg"][] = pg_last_error($con);
    }
    if (count($msg_erro) == 0) {
        ###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
        $res = pg_query($con,"COMMIT TRANSACTION");

        $msg_sucesso["msg"][] = traduz("Apagado com sucesso");
        echo "<meta http-equiv=refresh content=\"0;URL=causa_defeito_cadastro.php\">";
    } else {
        ###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
        
        $causa_defeito  = $_POST["causa_defeito"];
        $codigo         = $_POST["codigo"];
        $descricao      = $_POST["descricao"];
        
        $res = pg_query($con,"ROLLBACK TRANSACTION");
    }
}

if ($btnacao == "gravar") {
    $msg_erro = array();

    $descricao    = ucfirst(trim($_POST["descricao"]));
    $descricao    = str_replace('"', "'", $descricao);
    $codigo       = strtoupper(trim($_POST["codigo"]));
    $descricao_es = trim($_POST["descricao_es"]);

    if (in_array($login_fabrica, [169,170])) {
        $ativo    = $_POST['ativo'];
    }

    if (strlen($codigo) == 0) {
        $msg_erro["msg"][] = traduz("Código é obrigatório.");
        $msg_erro["campos"][] = "codigo";
    }
    if (strlen($descricao) == 0) {
        $msg_erro["msg"][] = traduz("Descrição é obrigatório.");
        $msg_erro["campos"][] = "descricao";
    }

    $sqlValida = "SELECT causa_defeito FROM tbl_causa_defeito WHERE codigo = '$codigo' AND fabrica = $login_fabrica;";
    $resValida = pg_query($con,$sqlValida);

    if (pg_num_rows($resValida) > 0) {
        if (strlen($causa_defeito) == 0) {
            $msg_erro["msg"][] = traduz("Código já cadastrado.");
        } else {
            $xcausa_defeito = pg_result($resValida, 0, causa_defeito);
            if (strlen($causa_defeito) == $xcausa_defeito) {
                $msg_erro["msg"][] = traduz("Código já cadastrado.");
            }
        }
    }

    if (count($msg_erro) == 0) {
        $res = pg_query($con,"BEGIN TRANSACTION");
        
        if (strlen($causa_defeito) == 0) {
            ###INSERE NOVO REGISTRO
            if (in_array($login_fabrica, [169,170])) {
                $sql = "
		    INSERT INTO tbl_causa_defeito (
                        codigo,
                        descricao,
                        fabrica,
                        ativo
                    ) VALUES (
                        '$codigo'     ,
                        '$descricao'  ,
                        $login_fabrica,
                        '$ativo'
                    );
		";
            } else {
                $sql = "
		    INSERT INTO tbl_causa_defeito (
                        codigo,
                    	descricao,
                    	fabrica
                    ) VALUES (
                    	'$codigo'     ,
                    	'$descricao'  ,
                    	$login_fabrica
                    );
		";
            }
            
            $res = pg_query($con,$sql);

            if (strlen(pg_last_error($con)) > 0) {
                $msg_erro["msg"][] = pg_last_error($con);
            }

            $res = pg_query($con,"SELECT CURRVAL ('seq_causa_defeito')");
            $x_causa_defeito  = pg_result($res,0,0);
        }else{
            ###ALTERA REGISTRO
            if (in_array($login_fabrica, [169,170])) {
                $sql = "UPDATE tbl_causa_defeito SET
                            codigo    = '$codigo'    ,
                            descricao = '$descricao' ,
                            ativo     = '$ativo'
                        WHERE  fabrica       = $login_fabrica
                        AND    causa_defeito = $causa_defeito";
            } else {
                $sql = "UPDATE tbl_causa_defeito SET
                            codigo    = '$codigo'    ,
                            descricao = '$descricao'
                        WHERE  fabrica       = $login_fabrica
                        AND    causa_defeito = $causa_defeito";
            }
            $res = pg_query($con,$sql);
            if (strlen(pg_last_error($con)) > 0) {
                $msg_erro["msg"][] = pg_last_error($con);
            }
            $x_causa_defeito = $causa_defeito;
        }

        if ($login_fabrica == 20) {

            $sql = "SELECT * FROM tbl_causa_defeito_idioma WHERE causa_defeito = $x_causa_defeito AND idioma = 'ES'";
            $res = pg_query($con,$sql);
            if (pg_num_rows($res) > 0) {
                $x_defeito_reclamado  = trim(pg_result($res,0,causa_defeito));
                $sql2 = "UPDATE tbl_causa_defeito_idioma SET descricao = '$descricao_es' 
                    WHERE causa_defeito = $x_causa_defeito 
                    AND   idioma         = 'ES' ; ";
            }else{
    
                $sql2 = "INSERT INTO tbl_causa_defeito_idioma (
                            causa_defeito   ,
                            descricao           ,
                            idioma
                        ) VALUES (
                            $x_causa_defeito   ,
                            '$descricao_es',
                            'ES'
                        );";
            }
            $res = pg_query($con,$sql2);
            if (strlen(pg_last_error($con)) > 0) {
                $msg_erro["msg"][] = pg_last_error($con);
            }
            
        }


    }
    
    if (count($msg_erro) == 0) {
        ###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
        $res = pg_query($con,"COMMIT TRANSACTION");
        $msg_sucesso["msg"][] = traduz("Gravado com sucesso");
        echo "<meta http-equiv=refresh content=\"2;URL=causa_defeito_cadastro.php\">";
    }else{
        ###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
        
        $causa_defeito   = $_POST["causa_defeito"];
        $codigo          = $_POST["codigo"];
        $descricao       = $_POST["descricao"];
	
	if (in_array($login_fabrica, [169,170])) {
           $ativo           = $_POST["ativo"]; 
        }
        
        $res = pg_query($con,"ROLLBACK TRANSACTION");
    }
}

###CARREGA REGISTRO
if (strlen($causa_defeito) > 0) {
    $sql = "SELECT  codigo,
                    descricao,
		    ativo
            FROM    tbl_causa_defeito
            WHERE   fabrica = $login_fabrica
            AND     causa_defeito = $causa_defeito;";

    $res = pg_query($con,$sql);
    
    if (pg_num_rows($res) > 0) {
        $codigo    = trim(pg_result($res,0,codigo));
        $descricao = trim(pg_result($res,0,descricao));

	if (in_array($login_fabrica, [169,170])) {
	    $ativo     = trim(pg_result($res,0,'ativo')); 
        }

        $sql2 = "SELECT descricao
                   FROM tbl_causa_defeito_idioma
                  WHERE causa_defeito = $causa_defeito
                    AND idioma = 'ES'  ";
        $res2 = pg_query($con,$sql2);

        if (pg_num_rows($res2) > 0) $descricao_es   = trim(pg_result($res2,0,descricao));

    }
}


$layout_menu = "cadastro";

if (in_array($login_fabrica, [169,170])) {
    $title = traduz("Cadastramento de Motivos de 2° Solicitação");
    $titulo_form = traduz("CADASTRO DE MOTIVOS");
} else if ($login_fabrica == 177){
    $title = traduz("Cadastramento de Defeitos Constatados Genéricos");
    $titulo_form = traduz("DEFEITOS GENÉRICOS");
}else if ($login_fabrica == 183){
    $title = traduz("Cadastramento de Códigos de Utilização");
    $titulo_form = traduz("CÓDIGOS DE UTILIZAÇÃO");
}else{
    $title = traduz("Cadastramento de Causas de Defeitos");
    $titulo_form = traduz("CAUSAS DE DEFEITOS");
}

include 'cabecalho_new.php';

$plugins = array(
    "dataTable",
);

include("plugin_loader.php");

?>

<style type='text/css'>
.txtsubtitulo {
    font-weight: normal;
    font-size: 12px;
}
</style>
<script language="javascript">
    var hora = new Date();
    var engana = hora.getTime();

    $(function() {
        $.dataTableLoad({table:"#tabela",aoColumns:[{"sType":"string"}, null]});
    });
</script>

    <?php
     if (count($msg_erro["msg"]) > 0) {?>
        <div class="alert alert-error">
            <h4><?php echo implode("<br />", $msg_erro["msg"]);?></h4>
        </div>
    <?php }?>

    <?php if (count($msg_sucesso["msg"]) > 0){?>
        <div class="alert alert-success">
            <h4><?php echo implode("<br />", $msg_sucesso["msg"]);?></h4>
        </div>
    <?php }?>

    <div class="row">
        <b class="obrigatorio pull-right">* <?=traduz('Campos obrigatórios')?> </b>
    </div>

    <form name='frm_causa_defeito' METHOD='POST' enctype="multipart/form-data" ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
        <input type="hidden" name="causa_defeito" value="<? echo $causa_defeito ?>">
        <div class='titulo_tabela '> <?=$titulo_form?></div>
        <br/>
        <div class='row-fluid'>
            <div class='span2'></div>
             <?php  
                $span_codigo    = 3;
                $span_descricao = 5;
                if ($login_fabrica == 20) {
                    $span_codigo = 2;
                    $span_descricao = 3;
                }
            ?>
            <div class='span<?php echo $span_codigo;?>'>
                <div class='control-group <?=(in_array("codigo", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'><?=traduz('Código')?></label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" class="span12" value="<?php echo $codigo;?>" name="codigo" size='10' maxlength='10' id="codigo">
                        </div>
                    </div>
                </div>
            </div>
           
            <div class='span<?php echo $span_descricao;?>'>
                <div class='control-group <?=(in_array("descricao", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'><?=traduz('Descrição')?></label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" class="span12" value="<?php echo $descricao;?>" name="descricao" size='40' maxlength='30' id="descricao">
                        </div>
                    </div>
                </div>
            </div>
            <?php 
                if ($login_fabrica == 20) {

                    echo "<div class='span".$span_descricao."'>
                            <div class='control-group'>
                                <label class='control-label'>Descrição Espanhol</label>
                                <div class='controls controls-row'>
                                    <div class='span12'>
                                        <h5 class='asteristico'>*</h5>
                                        <input type='text' class='span12' value='".$descricao_es."' name='descricao_es' size='40' maxlength='30' id='descricao_es'>
                                    </div>
                                </div>
                            </div>
                        </div>";
                }
            ?>
            <div class='span2'></div>
        </div>
	<?php
            if (in_array($login_fabrica, [169,170])) { ?>
                <div class="row-fluid">
                    <div class="span2"></div>
                    <div class='span4'>
                        <div class='control-group'>
                            <label class='control-label' for='ativo'><?=traduz('Ativo')?> / <?=traduz('Inativo')?></label>
                            <div class='controls controls-row'>
                                <div class='span10 input-append'>
                                    <label class="checkbox inline" style="margin-left: -20px;">
                                        <input type="radio" name="ativo" value='t' <?php if ($ativo == 't' || $ativo == ""){ echo "checked"; } ?> > <?php echo traduz("Ativo"); ?>
                                     </label>
                                    <label class="checkbox inline">
                                        <input type="radio" name="ativo" value='f' <?php if ($ativo == 'f'){ echo "checked"; } ?> > <?php echo traduz("Inativo"); ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
        <?php } ?>
        <p><br/>

            <button class='btn' id="btn_acao" type="button"  onclick="$('#btn_click').val('gravar');submit('frm_causa_defeito');"><?=traduz('Gravar')?></button>
            <?php if ($causa_defeito > 0) {?>
            <button class='btn btn-danger' id="btn_acao" type="button" onclick="$('#btn_click').val('deletar');submit('frm_causa_defeito');"><?=traduz('Apagar')?></button>
            <?php }?>
            <input type='hidden' id="btn_click" name='btn_acao' value='' />
        </p><br/>
    </form> 
    <br />

<?php
    $orderby = " descricao ASC;";
    if ($login_fabrica == 131) {
        $orderby = " tbl_causa_defeito.codigo::integer ASC;";
    }
    $sql =  "SELECT causa_defeito ,
                    descricao     ,
                    codigo        ,
		    ativo
                FROM tbl_causa_defeito
               WHERE fabrica = $login_fabrica
            ORDER BY $orderby";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {

	$titulo_tabela_dados = traduz('Relação de causas do defeitos');
        $sub_titulo_dados    = traduz('Para efetuar alterações, clique na descrição da causa do defeito.');
        if (in_array($login_fabrica, [169, 170])) {
            $titulo_tabela_dados = 'Relação de Motivo Para 2ª solicitação'; 
            $sub_titulo_dados    = 'Para efetuar alterações, clique na descrição ou código do motivo.';
        } else if ($login_fabrica == 177) {
	    $titulo_tabela_dados = 'Relação dos defeitos genéricos';
	    $sub_titulo_dados 	 = 'Para efetuar alterações, clique na descrição do defeito.';
	}

        echo "<table class='table table-striped table-bordered table-hover table-fixed' id='tabela'>
                <thead>
                   <tr class='titulo_tabela'>
                       <th colspan='4'>".
                        $titulo_tabela_dados
                        ."<br />
                        <i class='txtsubtitulo'>".$sub_titulo_dados."</i>
                        </th>
                   </tr>   
                   <tr class='titulo_coluna' >
                        <th align='left'>".traduz('CÓDIGO')."</th>
                        <th align='left'>".traduz('DESCRIÇÃO')."</th>
                        ";
			if (in_array($login_fabrica, [169, 170])) {
                            echo "<th align='left'>ATIVO</th>";
                        }
                        if ($login_fabrica == 20) {
                            echo "<th nowrap>ESPANHOL</th>";
                        } 

        echo "     </tr>
                </thead>
                <tbody>";
        for ($y = 0 ; $y < pg_num_rows($res) ; $y++) {
            $causa_defeito = trim(pg_result($res,$y,causa_defeito));
            $codigo        = trim(pg_result($res,$y,codigo));
            $descricao     = trim(pg_result($res,$y,descricao));

	    if (in_array($login_fabrica, [169, 170])) {
                $ativo     = trim(pg_result($res,$y,'ativo'));       
            }

            echo "<tr>";
            echo "<td nowrap><a href='$PHP_SELF?causa_defeito=$causa_defeito'>$codigo</a></td>";
            echo "<td nowrap><a href='$PHP_SELF?causa_defeito=$causa_defeito'>$descricao</a></td>";

	    if (in_array($login_fabrica, [169, 170])) {
                echo "<td class='tac'>";
                $imagem_ativo = ($ativo == "t") ? 'status_verde.png' : 'status_vermelho.png';
                echo "<img src='imagens/".$imagem_ativo."' />";
                echo "</td>";
            }

            if ($login_fabrica == 20) {
                $sqlES = "SELECT  descricao
                    FROM    tbl_causa_defeito_idioma
                    WHERE   causa_defeito = $causa_defeito
                    AND     idioma = 'ES'  ";
                $resES = pg_query($con,$sqlES);
        
                if (pg_num_rows($resES) > 0)  echo "<td align='left'>".trim(pg_result($resES,0,descricao))."</td>";
            }

            echo "</tr>";
        }
        echo "</tbody>
        </table>";
    }

echo "</div><br>";

include "rodape.php";
?>
