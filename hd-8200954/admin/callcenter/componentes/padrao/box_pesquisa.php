<div class="container_bc">
    <div class="top_painel_pesquisa">
        <div class="row">
            <div class="col-md-12 tac">
                <b><?php echo traduz("Pesquisa de Atendimento");?></b> 
            </div>
        </div>
    </div>
    <div class="box_pesquisa">
        <div class="row">
            <div class="col-xs-4 col-sm-2 col-md-2">
                <label><?php echo traduz("Nº Atendimento");?>:</label>
                <div class="input-group">
                    <input type="text" name="pesquisa[n_protocolo]" class="form-control input-sm">
                    <span class="input-group-btn">
                        <button type="button" data-filtro="n_protocolo" class="btn btn-sm btn-lupa-pesquisa btn-default"><i class="fa fa-search"></i></button>
                    </span>
                </div>
            </div>
            <div class="col-xs-4 col-sm-2 col-md-2">
                <label><?php echo traduz("CPF/CNPJ");?>:</label>
                <div class="input-group ">
                    <input type="text" name="pesquisa[cpf_cnpj]" class="form-control cpf_cnpj input-sm">
                    <span class="input-group-btn">
                        <button type="button" data-filtro="cpf_cnpj" class="btn btn-sm btn-lupa-pesquisa btn-default"><i class="fa fa-search"></i></button>
                    </span>
                </div>
            </div>
            <div class="col-xs-4 col-sm-6 col-md-6">
                <label><?php echo traduz("Nome");?>:</label>
                <div class="input-group">
                    <input type="text" name="pesquisa[nome]" class="form-control input-sm">
                    <span class="input-group-btn">
                        <button type="button" data-filtro="nome" class="btn btn-sm btn-lupa-pesquisa btn-default"><i class="fa fa-search"></i></button>
                    </span>
                </div>
            </div>
            <div class="col-xs-4 col-sm-2 col-md-2">
                <label><?php echo traduz("Telefone");?>:</label>
                <div class="input-group">
                    <input type="text" name="pesquisa[telefone]" class="form-control input-sm">
                    <span class="input-group-btn">
                        <button type="button" data-filtro="telefone" class="btn btn-sm btn-lupa-pesquisa btn-default"><i class="fa fa-search"></i></button>
                    </span>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-xs-4 col-sm-2 col-md-2">
                <label><?php echo traduz("Nº O.S.");?>:</label>
                <div class="input-group">
                    <input type="text" name="pesquisa[n_ordem_servico]" class="form-control input-sm">
                    <span class="input-group-btn">
                        <button type="button" data-filtro="n_ordem_servico" class="btn btn-sm btn-lupa-pesquisa btn-default"><i class="fa fa-search"></i></button>
                    </span>
                </div>
            </div>
            <div class="col-xs-4 col-sm-2 col-md-2">
                <label><?php echo traduz("Nº Série");?>:</label>
                <div class="input-group">
                    <input type="text" name="pesquisa[n_serie]" class="form-control input-sm">
                    <span class="input-group-btn">
                        <button type="button" data-filtro="n_serie" class="btn btn-sm btn-lupa-pesquisa btn-default"><i class="fa fa-search"></i></button>
                    </span>
                </div>
            </div>
            <div class="col-xs-4 col-sm-2 col-md-2">
                <label><?php echo traduz("CEP");?>:</label>
                <div class="input-group">
                    <input type="text" name="pesquisa[cep]" class="form-control input-sm">
                    <span class="input-group-btn">
                        <button type="button" data-filtro="cep" class="btn btn-sm btn-lupa-pesquisa btn-default"><i class="fa fa-search"></i></button>
                    </span>
                </div>
            </div>
            
        </div><BR>
	</div>
</div>