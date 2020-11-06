<?php

$statusAbasConfig = [
    "preenchida" => [
       "descricao" => "Preenchida",
       "cor"       => "#c2e3b6"
    ],
    "pendente" => [
        "descricao" => "Aguardando Preenchimento",
        "cor"       => "#facf96"
    ],
    "bloqueada" => [
        "descricao" => "Bloqueada",
        "cor"       => "lightgray"
    ]
];

$abas = [
    "posvenda" => [
        "descricao"            => "P�s-vendas",
        "apenas_visualiza"     => true,
        "status_preenchimento" => "preenchida",
        "ativa"                => true,
        "anexo_config" => [
            "contexto"         => "ri_posvenda",
            "apenas_visualiza" => false,
            "plugin_id"        => "box-uploader-app-posvenda"
        ],
    ],
    "time_analise" => [
        "descricao"            => "An�lise",
        "apenas_visualiza"     => true,
        "status_preenchimento" => "bloqueada",
        "ativa"                => false,
        "usa_anexo"            => false,
        "anexo_config" => []
    ],
    "acao_contencao" => [
        "descricao"            => "Conten��o",
        "apenas_visualiza"     => true,
        "status_preenchimento" => "bloqueada",
        "ativa"                => false,
        "anexo_config" => [
            "contexto"         => "ri_contencao",
            "apenas_visualiza" => false,
            "plugin_id"        => "box-uploader-app-contencao"
        ],
    ],
    "causa_analise" => [
        "descricao"            => "Causa",
        "apenas_visualiza"     => true,
        "status_preenchimento" => "bloqueada",
        "ativa"                => false,
        "anexo_config" => [
            "contexto"         => "ri_causa",
            "apenas_visualiza" => false,
            "plugin_id"        => "box-uploader-app-causa"
        ],
    ],
    "identificacao_acoes" => [
        "descricao"            => "Identifica��o",
        "apenas_visualiza"     => true,
        "status_preenchimento" => "bloqueada",
        "ativa"                => false,
        "anexo_config" => [
            "contexto"         => "ri_identificacao",
            "apenas_visualiza" => false,
            "plugin_id"        => "box-uploader-app-identificacao"
        ],
    ],
    "implementacao_acoes" => [
        "descricao"            => "Implementa��o",
        "apenas_visualiza"     => true,
        "status_preenchimento" => "bloqueada",
        "ativa"                => false,
        "anexo_config" => [
            "contexto"         => "ri_implementacao",
            "apenas_visualiza" => false,
            "plugin_id"        => "box-uploader-app-implementacao"
        ],
    ],
    "eficacia_acoes" => [
        "descricao"            => "Efic�cia",
        "apenas_visualiza"     => true,
        "status_preenchimento" => "bloqueada",
        "ativa"                => false,
        "anexo_config" => [
            "contexto"         => "ri_eficacia",
            "apenas_visualiza" => false,
            "plugin_id"        => "box-uploader-app-eficacia"
        ],
    ],
    "conclusao" => [
        "descricao"            => "Conclus�o",
        "apenas_visualiza"     => true,
        "status_preenchimento" => "bloqueada",
        "ativa"                => false,
        "anexo_config" => [
            "contexto"         => "ri_conclusao",
            "apenas_visualiza" => false,
            "plugin_id"        => "box-uploader-app-conclusao"
        ],
    ],
];