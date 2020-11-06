<?php if ($configLojaPagamento["meio"]["cielo"]["boleto"]) {?>
<div class="row-fluid">
    <div class="span12 meios-pagamentos" data-integrador="cielo" data-nome="BOLETO" data-valor="boleto"> 
        <div class="meio_pagamento meio_pagamento_boleto">
            <span class="icone-pagamento"> <i class="fa fa-barcode"></i></span> 
            <div class="txt-pagamento"><?php echo traduz("boleto");?></div> 
        </div>
    </div>
</div>
<div class="meio_boleto" style="display: none;">
    <p class="alert alert-info"><?php echo traduz("após.clicar.em.finalizar.compra.aparecerá.um.link.para.impressão.do.boleto");?>.</p>
</div>
<?php }?>