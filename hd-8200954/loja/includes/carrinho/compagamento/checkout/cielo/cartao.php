<?php if ($configLojaPagamento["meio"]["cielo"]["cartao"]) {?>
<div class="row-fluid">
    <div class="span12 meios-pagamentos" data-integrador="cielo" data-nome="CREDIT_CARD" data-valor="cartao"> 
        <div class="meio_pagamento meio_pagamento_cartao">
            <span class="icone-pagamento"> <i class="fa fa-credit-card"></i></span> 
            <div class="txt-pagamento"><?php echo traduz("cartão.de.crédito");?></div> 
        </div>
    </div>
</div>
<div class="meio_cartao" id="bx__cartao" style="display: none;">
    <p><?php echo traduz("selecione.uma.bandeira");?></p>
    <div class="row-fluid" id="formas_cartao">
        
    </div>
    <hr class="ajuste-hr">
    <div class="row-fluid">
        <div class="span6">
            <label for=""><?php echo traduz("número.do.cartão");?>:</label>
            <input type="text" class="obrigatorio2" style="width:100%" id="cartao" value="4073020000000002" name="cartao">
        </div>
        <div class="span2">
            <div id="brand_cartao"></div>
        </div>
        <div class="span4">
            <label for=""><?php echo traduz("código.de.segurança");?>: <i class="fa fa-question-circle"  data-toggle="tooltip" data-placement="left" title="São os três últimos dígitos no verso do cartão."></i></label>
            <input type="text" value="123" class="obrigatorio2"  style="width:100%" name="cvv" id="cvv"> 
        </div>
    </div>
    <div class="row-fluid">
        <div class="span4">
            <label for=""><?php echo traduz("data.de.validade");?>:</label>
            <div class="row-fluid">
                <div class="span5" style="padding-right: 0px;">
                    <select class="obrigatorio2" style="width:100%" name="validadeMes" id="validadeMes" >
                        <option value=""> MM </option>
                        <option value="01"> 01 </option>
                        <option value="02"> 02 </option>
                        <option value="03"> 03 </option>
                        <option value="04"> 04 </option>
                        <option value="05" selected> 05 </option>
                        <option value="06"> 06 </option>
                        <option value="07"> 07 </option>
                        <option value="08"> 08 </option>
                        <option value="09"> 09 </option>
                        <option value="10"> 10 </option>
                        <option value="11"> 11 </option>
                        <option value="12"> 12 </option>
                    </select>  
                </div>
                <div class="span1" style="padding-right: 0px;padding-left: 0px;text-align: center;">/ </div>
                <div class="span6" style="padding-right: 0px;padding-left: 0px;">

                    <select class="obrigatorio2" style="width:100%" name="validadeAno" id="validadeAno" >
                        <option value=""> YYYY </option>
                        <?php 
                            for ($i=0; $i <= 15 ; $i++) { 
                                $selected = ($i == 4) ? "selected" : "";
                                echo '<option '.$selected.' value="'.($i+$anoAtual).'">'.($i+$anoAtual).'</option>';
                            }
                        ?>
                    </select>
                </div>
            </div>
        </div>
        <div class="span8">
            <label for=""><?php echo traduz("nome.razão.do.titular.do.cartão");?>: <i class="fa fa-question-circle"  data-toggle="tooltip" data-placement="right" title="Como está gravado no cartão."></i> </label>
            <input value="joao de teste" type="text" style="width:100%" class="obrigatorio2" name="nome_titular_cartao">
        </div>
    </div>
    <div class="row-fluid">
        <div class="span5">
            <label for=""> <?php echo traduz("parcela.em");?>:</label>
            <select style="width:100%" class="" name="qtde_parcelas" id="qtde_parcelas" >
                <option value=""> - <?php echo traduz("selecione");?> - </option>
            </select>
        </div>
    </div>
</div>
<?php }?>
