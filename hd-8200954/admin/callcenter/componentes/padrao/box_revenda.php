<div class="container_bc">
    <div class="top_painel">
        <div class="row" style="line-height: 34px;">
            <div class="col-md-12 tac">
                <b><?php echo traduz("Informações da Revenda");?></b> 
            </div>
        </div>
    </div>
    <div  class="box_campos">
        <div class="box_add_init_callcenter">
             <div class="row">
                <div class="col-md-5">
                    <label><?php echo traduz("Nome da Revenda");?>:</label>
                    <div class="input-group">
                        <input type="text" name="revenda[nome]" class="form-control input-sm">
                        <span class="input-group-btn">
                            <a  href="<?php ?>" class="btn btn-sm btn-default" data-toggle="modal" data-target=".modal_projeto" title="<?php echo traduz("Pesquisar Revenda por Nome");?>"><i class="fa fa-search"></i></a>
                        </span>
                    </div>
                </div>
                <div class="col-md-2">
                    <label><?php echo traduz("CNPJ");?>:</label>
                    <div class="input-group">
                        <input type="text" name="revenda[cnpj]" class="form-control input-sm">
                        <span class="input-group-btn">
                            <a  href="<?php ?>"  data-toggle="modal" data-target=".modal_contato" title="<?php echo traduz("Pesquisar Revenda por CNPJ");?>" class="btn btn-sm btn-default"><i class="fa fa-search"></i></a>
                        </span>
                    </div>
                </div>
                <div class="col-md-3">
                    <label><?php echo traduz("E-mail");?>:</label>
                    <input type="text" name="revenda[email]" class="form-control input-sm">
                </div>
                <div class="col-md-2">
                    <label><?php echo traduz("Fone");?>:</label>
                    <div class="input-group">
                        <input type="text" name="revenda[fone]" class="form-control input-sm">
                        <span class="input-group-addon"><i class="fa fa-phone"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
       