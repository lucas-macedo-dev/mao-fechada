<?php

return [
    'validation_failed' => 'Falha na validacao.',
    'unauthenticated' => 'Nao autenticado.',
    'forbidden' => 'Acesso negado.',
    'resource_not_found' => 'Recurso nao encontrado.',
    'http_error' => 'Erro HTTP.',
    'server_error' => 'Erro interno do servidor.',
    'logged_out' => 'Sessao encerrada.',
    'ownership_denied' => 'Voce nao tem permissao para acessar este recurso.',
    'category_parent_self' => 'Uma categoria nao pode ser pai dela mesma.',
    'category_parent_must_be_root' => 'Subcategorias nao podem ser categorias pai.',
    'category_type_must_match_parent' => 'O tipo da subcategoria deve ser igual ao da categoria pai.',
    'category_type_must_match_children' => 'O tipo da categoria deve ser compativel com as subcategorias existentes.',
    'transaction_type_must_match_category' => 'O tipo da transacao deve ser igual ao da categoria.',
    'category_fallback_name' => 'Sem Categoria',
    'category_delete_with_transactions' => 'Esta categoria possui transações vinculadas. Ao deletar, elas serão movidas para "Sem Categoria". Deseja continuar?',
    'report_not_ready' => 'Este relatório ainda não está pronto para download.',

    // E-mails
    'email_verify_subject'  => 'Verifique seu endereço de e-mail',
    'email_verify_heading'  => 'Confirme seu e-mail',
    'email_verify_greeting' => 'Olá, :name!',
    'email_verify_body'     => 'Obrigado por se cadastrar. Clique no botão abaixo para verificar seu endereço de e-mail. Este link expira em 60 minutos.',
    'email_verify_action'   => 'Verificar e-mail',
    'email_verify_footer'   => 'Se você não criou uma conta, pode ignorar este e-mail com segurança.',
    'email_verify_fallback' => 'Se o botão acima não funcionar, copie e cole o link abaixo no seu navegador:',

    'email_reset_subject'   => 'Redefina sua senha',
    'email_reset_heading'   => 'Redefinição de senha',
    'email_reset_greeting'  => 'Olá, :name!',
    'email_reset_body'      => 'Recebemos uma solicitação para redefinir a senha da sua conta.',
    'email_reset_action'    => 'Redefinir senha',
    'email_reset_expiry'    => 'Este link expira em :minutes minutos.',
    'email_reset_footer'    => 'Se você não solicitou a redefinição de senha, nenhuma ação é necessária.',
    'email_reset_fallback'  => 'Se o botão acima não funcionar, copie e cole o link abaixo no seu navegador:',
];
