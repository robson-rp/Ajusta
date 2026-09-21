<?php

return [

    'attributes' => [
        'company_name' => 'nome da empresa',
    ],

    'errors' => [
        'subscription_inactive' => 'A sua conta está só de leitura até a subscrição ser paga.',
        'plan_feature' => 'O seu plano actual não inclui esta funcionalidade.',
        'owner_only' => 'Só o titular da conta pode gerir a subscrição.',
    ],

    'mail' => [
        'open_billing' => 'Abrir subscrição',

        'welcome' => [
            'subject' => 'Bem-vindo ao AJUSTA',
            'line1' => 'A sua empresa :company está pronta para facturar.',
            'line2' => 'Está em período de teste gratuito do plano :plan até :date.',
            'button' => 'Abrir o AJUSTA',
        ],

        'trial_ending' => [
            'subject' => 'O seu período de teste está a terminar',
            'line1' => 'O período de teste gratuito termina a :date.',
            'line2' => 'Escolha um plano e pague por Multicaixa Express ou por referência para continuar a facturar.',
        ],

        'renewal_due' => [
            'subject' => 'Está na altura de renovar a subscrição',
            'line1' => 'O plano :plan está pago até :date.',
            'line2' => 'Renove agora para manter a conta activa sem interrupções.',
        ],

        'suspended' => [
            'subject' => 'A sua conta AJUSTA está só de leitura',
            'line1' => 'Não recebemos o pagamento da subscrição.',
            'line2' => 'Continua a ver os seus documentos. Pague a subscrição para voltar a emitir.',
        ],

        'payment_received' => [
            'subject' => 'Pagamento recebido',
            'line1' => 'Recebemos :amount referente a :months mês(es) do plano :plan.',
            'line2' => 'A subscrição está activa até :date.',
            'line3' => 'Transacção: :id',
        ],

        'reference' => [
            'subject' => 'Dados para pagar a subscrição AJUSTA',
            'line1' => 'Pague o plano :plan (:months mês(es)) em qualquer ATM ou na app do seu banco, em "Pagamentos por referência":',
            'entity' => 'Entidade: :value',
            'reference' => 'Referência: :value',
            'amount' => 'Montante: :value',
            'valid_until' => 'Válida até: :date',
        ],
    ],

];
