<link href='http://fonts.googleapis.com/css?family=Oswald:300:400' rel='stylesheet' type='text/css'>
<?php header("Content-type: text/html; charset=ISO-8859-1"); ?>
<style>

    #alinhasite{
        width: 970px;
        margin: 0 auto;
        font: 16px 'Oswald', sans-serif;
        color: #595959;
    }

    button{
        font: 16px 'Oswald', sans-serif;
    }

    input{
        padding: 5px;
        background-color: #f5f5f5;
        border: 1px solid #595959;
    }

    select{
        padding: 5px;
        background-color: #f5f5f5;
        border: 1px solid #595959;
    }

    textarea{
        padding: 5px;
        background-color: #f5f5f5;
        border: 1px solid #595959;
    }

    .txt_branco{
        font-weight: bold;
        color: #FFFFFF;
        background-color: #b7212b;
        border-radius: 7px;
        padding-left: 10px;
        width: 170px !important;
    }

    .txt_vermelho{
        color: #b7212b;
    }

 
</style>

<?php
    
    $nome               = str_replace("'","",$nome);
    $email              = str_replace("'","",$email);
    $telefone           = str_replace("'","",$telefone);
    $celular_sp         = str_replace(",","",$celular_sp);
    $cep                = str_replace("'","",$cep);    
    $endereco           = str_replace("'","",$endereco);
    $numero             = str_replace("'","",$numero);
    $complemento        = str_replace("'","",$complemento);
    $bairro             = str_replace("'","",$bairro);
    $estado             = str_replace("'","",$estado);
    $cidade             = str_replace("'","",$cidade);
    $produto            = str_replace("'","", $produto);
    $defeito_reclamado  = str_replace("'","", $defeito_reclamado);
    $mensagem           = str_replace("'","",$mensagem);
    
 ?>
<body>
<div id="alinhasite">
    <div id="conteudo_internas">
        <?php if($msg['sucess']){
            echo "<div class='bgsucess'>" . $msg['msg'] . "</div>";
            echo "<script>                     
                    setTimeout(function(){ window.location = window.location.pathname;}, 6000);
                  </script>";
        } else if($msg['error']){
            echo "<div class='bgerror'>" . $msg['msg'] . "</div>";
        }
        ?>
        
        <p style="text-align:right;" class="txt_vermelho">* Campos obrigatórios</p>
        <form action="" name="contato" method="post" onsubmit="return frmValidaFormContato();">
            <input type='hidden' name='marcaID' value='<?=$marcaID?>' />
            <table width="650" border="0" align="center" cellpadding="1" cellspacing="8">
                <?php if(count($msg_erro) > 0){?>
                <tr>
                    <td colspan='2' style='background-color:#FF0000; font: bold 16px "Arial"; color:#FFFFFF; text-align:center;'>
                        <?php echo implode('<br />', $msg_erro);?>
                    </td>
                </tr>
                <?php }?>
                <tr>
                    <td width="480" align="left"  class="txt_branco">
                        &nbsp;Nome Completo: *
                    </td>
                    <td width="295" class="style1">
                        <input name="nome_completo"  type="text" class="form_text"
                                 id="nome_completo" title="Nome Completo" size="38" placeholder='Digite seu nome' value='<?=$nome?>'>
                    </td>
                </tr>
                <tr>
                    <td align="left"  class="txt_branco">&nbsp;E-mail: <span> * </span></td>
                    <td class="style1">
                        <input name="email" type="email" class="form_text" id="email" placeholder='Seu endereço de e-mail'
                              title="Email" size="38" value='<?=$email?>'></td>
                </tr>
                <tr>
                    <td align="left"  class="txt_branco">&nbsp;Tel Fixo:</td>
                    <td class="style1">
                        <input name="telefone" type="text" class="form_text telefone" id="telefone" title="Tel Fixo" value='<?=$telefone?>' />
                    </td>
                </tr>
                <tr>
                    <td align="left"  class="txt_branco">&nbsp;Celular:</td>
                    <td class="style1">
                        <input name="celular_sp" type="text" class="form_text telefone" id="celular_sp" title="Celular" value='<?=$celular_sp?>' maxlength="15" />
                    </td>
                </tr>
                <tr>
                    <td align="left"  class="txt_branco">&nbsp;CEP: <span> * </span></td>
                    <td class="style1">
                        <input name="cep" type="text" class="form_text cep" id="cep" size="9" maxlength='9' value='<?=$cep?>' title="CEP">
                    </td>
                </tr>
                <tr>
                    <td align="left"  class="txt_branco">&nbsp;Endereço Completo: <span> * </span></td>
                    <td class="style1">
                        <input type="text" name="endereco" placeholder="Se sabe seu CEP, digite-o acima para agilizar" class="form_text" id="endereco" size="38" title="Nome Completo" value="<?php echo $endereco ?>" >
                    </td>
                </tr>
                <tr>
                    <td align="left"  class="txt_branco">&nbsp;Número: <span> * </span></td>
                    <td class="style1">
                        <input type="text" name="numero" class="form_text" id="numero" size="20" maxlength="20" value="<?php echo $numero ?>" title="Número" >
                    </td>
                </tr>
                <tr>
                    <td align="left"  class="txt_branco">&nbsp;Complemento: <span> * </span></td>
                    <td class="style1">
                        <input type="text" name="complemento" class="form_text" id="complemento" size="30" maxlength="30" value="<?php echo $complemento ?>" title="Complemento">
                    </td>
                </tr>
                <tr>
                    <td align="left"  class="txt_branco">&nbsp;Bairro: <span> * </span></td>
                    <td class="style1"><input name="bairro" type="text" class="form_text" id="bairro" size="38" value="<?php echo $bairro?>" title="Bairro"></td>
                </tr>
                <tr>
                    <td align="left" class="txt_branco">&nbsp;Estado: <span> * </span></td>
                    <td class="style1">
                        <select name='estado' id='estado' title="Estado">
                            <option value="" selected="selected"></option>
                            <?php
                            foreach ($array_estado as $k => $v) {
                                echo '<option value="'.$k.'"'.($estado == $k ? ' selected="selected"' : '').'>'.$v."</option>\n";
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td align="left"  class="txt_branco" title="Cidade">&nbsp;Cidade: <span> * </span></td>
                    <td class="style1">

                        <select name='cidade' id='cidade' title="Cidade">
                            <option value="" selected="selected"></option>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <td align="left"  class="txt_branco">&nbsp;Produto: <span> * </span></td>
                    <td class="style1">
                        <input type='hidden' name='produto' id='produto' value="<?php echo $produto?>">
                        <!-- HD 941072 - Busca autocomplete pelo Nome do Produto populando o combo de Defeitos Reclamados -->
                        <input name="produto_descricao" class="form_text" id="produto_descricao" value="<?php echo $produto_descricao; ?>" type="text" size="38" maxlength="80" title="Produto" />
                    </td>
                </tr>
                <tr>
                    <td align="left"  class="txt_branco">
                        &nbsp;Defeito Reclamado: <span> * </span>
                    </td>
                    <td align='left' colspan='5' width='630' valign='top'>

                        <div id='div_defeitos' style='display:inline; Position:relative;background-color: #e6eef7;width:100%'>
                            <select id="defeito_" name="defeito_">
                                <option value="">Digite primeiro o Produto acima</option>
                            </select>
                        </div>

                    </td>
                </tr>
                <tr>
                    <td align="left" valign="top">
                        <div class="txt_branco" style="padding: 5px; padding-left: 10px;">&nbsp;Mensagem: <span> * </span></div>
                    </td>
                    <td class="style1">
                        <textarea name="mensagem" cols="36" rows="5" class="form_text" id="mensagem" title="Mensagem"><?=$mensagem?></textarea>
                    </td>
                </tr>
                <tr>
                    <td align="left" valign="top" >&nbsp;</td>
                    <td align="center">
                        <button type="submit" style="cursor:pointer;">ENVIAR</button>
                    </td>
                </tr>
            </table>
        </form>
    </div>
</div>