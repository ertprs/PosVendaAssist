<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label class="control-label"><?php echo traduz("Linha");?>:</label>
            <select name="mapa_rede[linha]" class="form-control input-sm">
                <option value=""><?php echo traduz("Selecione");?> ...</option>
                <option value="A" <?php echo (isset($status) && (trim($status) == "A")) ? "selected" : "";?>>Aprovado</option>
                <option value="P" <?php echo (isset($status) && (trim($status) == "P")) ? "selected" : "";?>>Reprovado</option>
            </select>
        </div>
    </div>
    <div class="col-md-2">
        <div class="form-group">
            <label class="control-label"><?php echo traduz("Estado");?>:</label>
            <select name="mapa_rede[estado]" class="form-control input-sm">
                <option value=""><?php echo traduz("Selecione");?> ...</option>
                <option value="A" <?php echo (isset($status) && (trim($status) == "A")) ? "selected" : "";?>>Aprovado</option>
                <option value="P" <?php echo (isset($status) && (trim($status) == "P")) ? "selected" : "";?>>Reprovado</option>
            </select>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label class="control-label"><?php echo traduz("Cidade");?>:</label>
            <div class="input-group">
                <input type="text" name="mapa_rede[cidade]" class="form-control input-sm">
                <span class="input-group-btn">
                    <button type="button" class="btn btn-sm btn-abre-mapa btn-default"  data-fabrica="<?php echo $login_fabrica;?>" title="<?php echo traduz("Abre Mapa da Rede");?>"><i class="fa  fa-map-marker"></i> <?php echo traduz("Mapa");?></a>
                </span>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-2">
        <div class="form-group">
            <label class="control-label"><?php echo traduz("Códigp Posto");?>:</label>
            <div class="input-group">
                <input type="text" name="mapa_rede[codigo_posto]" class="form-control input-sm">
                <span class="input-group-btn">
                    <a  href="<?php ?>" class="btn btn-sm btn-default" data-toggle="modal" data-target=".modal_projeto" title="Adicionar Novo Projeto"><i class="fa fa-search"></i></a>
                </span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label class="control-label"><?php echo traduz("Nome do Posto");?>:</label>
            <div class="input-group">
                <input type="text" name="mapa_rede[nome_posto]" class="form-control input-sm">
                <span class="input-group-btn">
                    <a  href="<?php ?>"  data-toggle="modal" data-target=".modal_contato" title="Adicionar Novo Contato" class="btn btn-sm btn-default"><i class="fa fa-search"></i></a>
                </span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label class="control-label"><?php echo traduz("E-mail");?>:</label>
            <input type="text" name="mapa_rede[email_posto]" class="form-control input-sm">
        </div>
    </div>
    <div class="col-md-2">
        <div class="form-group">
            <label class="control-label"><?php echo traduz("Fone");?>:</label>
            <div class="input-group">
                <input type="text" name="mapa_rede[fone_posto]" class="form-control input-sm">
                <span class="input-group-addon"><i class="fa fa-phone"></i>
                </span>
            </div>
        </div>
    </div>
</div>
