<?php if ($configLojaPagamento["meio"]["maxipago"]["boleto"]) {?>
<div class="row-fluid">
    <div class="span12 meios-pagamentos" data-integrador="maxipago" data-nome="BOLETO" data-valor="boleto"> 
        <div class="meio_pagamento meio_pagamento_boleto" style="padding:20px;background: #f5f5f5" >
            <span class="icone-pagamento"> <i class="fa fa-barcode"></i></span> 
            <div class="txt-pagamento"><?php echo traduz("boleto");?></div> 
        </div>
    </div>
</div>
<div class="meio_boleto" style="display: none;">
    <p class="alert alert-info"><?php echo traduz("ap�s.clicar.em.finalizar.compra.aparecer�.um.link.para.impress�o.do.boleto");?>.</p>
</div>
<?php }?>
