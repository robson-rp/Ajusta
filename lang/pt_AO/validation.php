<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Mensagens de validação em Português de Angola. Regras sem tradução aqui
    | caem para o inglês (APP_FALLBACK_LOCALE).
    |
    */

    'accepted' => 'O campo :attribute deve ser aceite.',
    'accepted_if' => 'O campo :attribute deve ser aceite quando :other é :value.',
    'active_url' => 'O campo :attribute não é um URL válido.',
    'after' => 'O campo :attribute deve ser uma data posterior a :date.',
    'after_or_equal' => 'O campo :attribute deve ser uma data igual ou posterior a :date.',
    'alpha' => 'O campo :attribute só pode conter letras.',
    'alpha_dash' => 'O campo :attribute só pode conter letras, números, hífenes e sublinhados.',
    'alpha_num' => 'O campo :attribute só pode conter letras e números.',
    'array' => 'O campo :attribute deve ser uma lista.',
    'before' => 'O campo :attribute deve ser uma data anterior a :date.',
    'before_or_equal' => 'O campo :attribute deve ser uma data igual ou anterior a :date.',
    'between' => [
        'numeric' => 'O campo :attribute deve estar entre :min e :max.',
        'file' => 'O campo :attribute deve ter entre :min e :max kilobytes.',
        'string' => 'O campo :attribute deve ter entre :min e :max caracteres.',
        'array' => 'O campo :attribute deve ter entre :min e :max elementos.',
    ],
    'boolean' => 'O campo :attribute deve ser verdadeiro ou falso.',
    'confirmed' => 'A confirmação do campo :attribute não coincide.',
    'current_password' => 'A palavra-passe está incorrecta.',
    'date' => 'O campo :attribute não é uma data válida.',
    'date_equals' => 'O campo :attribute deve ser uma data igual a :date.',
    'date_format' => 'O campo :attribute não corresponde ao formato :format.',
    'carbon_date_format' => 'O campo :attribute não corresponde ao formato :format.',
    'moment_date_format' => 'O campo :attribute não corresponde ao formato :format.',
    'decimal' => 'O campo :attribute deve ter :decimal casas decimais.',
    'declined' => 'O campo :attribute deve ser recusado.',
    'different' => 'Os campos :attribute e :other devem ser diferentes.',
    'digits' => 'O campo :attribute deve ter :digits dígitos.',
    'digits_between' => 'O campo :attribute deve ter entre :min e :max dígitos.',
    'dimensions' => 'O campo :attribute tem dimensões de imagem inválidas.',
    'distinct' => 'O campo :attribute tem um valor duplicado.',
    'email' => 'O campo :attribute deve ser um endereço de e-mail válido.',
    'ends_with' => 'O campo :attribute deve terminar com um dos seguintes valores: :values.',
    'enum' => 'O valor seleccionado em :attribute é inválido.',
    'exists' => 'O valor seleccionado em :attribute é inválido.',
    'file' => 'O campo :attribute deve ser um ficheiro.',
    'filled' => 'O campo :attribute é obrigatório.',
    'gt' => [
        'numeric' => 'O campo :attribute deve ser superior a :value.',
        'file' => 'O campo :attribute deve ter mais de :value kilobytes.',
        'string' => 'O campo :attribute deve ter mais de :value caracteres.',
        'array' => 'O campo :attribute deve ter mais de :value elementos.',
    ],
    'gte' => [
        'numeric' => 'O campo :attribute deve ser igual ou superior a :value.',
        'file' => 'O campo :attribute deve ter :value kilobytes ou mais.',
        'string' => 'O campo :attribute deve ter :value caracteres ou mais.',
        'array' => 'O campo :attribute deve ter :value elementos ou mais.',
    ],
    'image' => 'O campo :attribute deve ser uma imagem.',
    'in' => 'O valor seleccionado em :attribute é inválido.',
    'in_array' => 'O campo :attribute não existe em :other.',
    'integer' => 'O campo :attribute deve ser um número inteiro.',
    'ip' => 'O campo :attribute deve ser um endereço IP válido.',
    'json' => 'O campo :attribute deve ser um texto JSON válido.',
    'lt' => [
        'numeric' => 'O campo :attribute deve ser inferior a :value.',
        'file' => 'O campo :attribute deve ter menos de :value kilobytes.',
        'string' => 'O campo :attribute deve ter menos de :value caracteres.',
        'array' => 'O campo :attribute deve ter menos de :value elementos.',
    ],
    'lte' => [
        'numeric' => 'O campo :attribute deve ser igual ou inferior a :value.',
        'file' => 'O campo :attribute deve ter :value kilobytes ou menos.',
        'string' => 'O campo :attribute deve ter :value caracteres ou menos.',
        'array' => 'O campo :attribute não pode ter mais de :value elementos.',
    ],
    'max' => [
        'numeric' => 'O campo :attribute não pode ser superior a :max.',
        'file' => 'O campo :attribute não pode ter mais de :max kilobytes.',
        'string' => 'O campo :attribute não pode ter mais de :max caracteres.',
        'array' => 'O campo :attribute não pode ter mais de :max elementos.',
    ],
    'max_digits' => 'O campo :attribute não pode ter mais de :max dígitos.',
    'mimes' => 'O campo :attribute deve ser um ficheiro do tipo: :values.',
    'mimetypes' => 'O campo :attribute deve ser um ficheiro do tipo: :values.',
    'min' => [
        'numeric' => 'O campo :attribute deve ser pelo menos :min.',
        'file' => 'O campo :attribute deve ter pelo menos :min kilobytes.',
        'string' => 'O campo :attribute deve ter pelo menos :min caracteres.',
        'array' => 'O campo :attribute deve ter pelo menos :min elementos.',
    ],
    'min_digits' => 'O campo :attribute deve ter pelo menos :min dígitos.',
    'multiple_of' => 'O campo :attribute deve ser múltiplo de :value.',
    'not_in' => 'O valor seleccionado em :attribute é inválido.',
    'not_regex' => 'O formato do campo :attribute é inválido.',
    'numeric' => 'O campo :attribute deve ser um número.',
    'password' => [
        'letters' => 'O campo :attribute deve conter pelo menos uma letra.',
        'mixed' => 'O campo :attribute deve conter pelo menos uma letra maiúscula e uma minúscula.',
        'numbers' => 'O campo :attribute deve conter pelo menos um número.',
        'symbols' => 'O campo :attribute deve conter pelo menos um símbolo.',
        'uncompromised' => 'O valor de :attribute apareceu numa fuga de dados. Escolha outro.',
    ],
    'present' => 'O campo :attribute deve estar presente.',
    'prohibited' => 'O campo :attribute não é permitido.',
    'regex' => 'O formato do campo :attribute é inválido.',
    'required' => 'O campo :attribute é obrigatório.',
    'required_if' => 'O campo :attribute é obrigatório quando :other é :value.',
    'required_unless' => 'O campo :attribute é obrigatório, excepto quando :other está em :values.',
    'required_with' => 'O campo :attribute é obrigatório quando :values está presente.',
    'required_with_all' => 'O campo :attribute é obrigatório quando :values estão presentes.',
    'required_without' => 'O campo :attribute é obrigatório quando :values não está presente.',
    'required_without_all' => 'O campo :attribute é obrigatório quando nenhum de :values está presente.',
    'same' => 'Os campos :attribute e :other devem coincidir.',
    'size' => [
        'numeric' => 'O campo :attribute deve ser :size.',
        'file' => 'O campo :attribute deve ter :size kilobytes.',
        'string' => 'O campo :attribute deve ter :size caracteres.',
        'array' => 'O campo :attribute deve conter :size elementos.',
    ],
    'starts_with' => 'O campo :attribute deve começar com um dos seguintes valores: :values.',
    'string' => 'O campo :attribute deve ser texto.',
    'timezone' => 'O campo :attribute deve ser um fuso horário válido.',
    'unique' => 'O valor de :attribute já está a ser utilizado.',
    'uploaded' => 'Não foi possível carregar o campo :attribute.',
    'url' => 'O formato do campo :attribute é inválido.',
    'uuid' => 'O campo :attribute deve ser um UUID válido.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | Nomes legíveis para os campos mais comuns, para que as mensagens digam
    | "O campo nome é obrigatório" em vez de "O campo name é obrigatório".
    |
    */

    'attributes' => [
        'name' => 'nome',
        'email' => 'e-mail',
        'password' => 'palavra-passe',
        'password_confirmation' => 'confirmação da palavra-passe',
        'phone' => 'telefone',
        'website' => 'website',
        'address_street_1' => 'morada',
        'address_street_2' => 'morada (linha 2)',
        'city' => 'cidade',
        'state' => 'província',
        'zip' => 'código postal',
        'country_id' => 'país',
        'currency_id' => 'moeda',
        'customer_id' => 'cliente',
        'company' => 'empresa',
        'company_name' => 'nome da empresa',
        'tax_id' => 'NIF',
        'vat_id' => 'número de IVA',
        'prefix' => 'prefixo',
        'display_name' => 'nome a apresentar',
        'contact_name' => 'nome do contacto',
        'invoice_date' => 'data da factura',
        'due_date' => 'data de vencimento',
        'invoice_number' => 'número da factura',
        'estimate_date' => 'data da proforma',
        'expiry_date' => 'validade',
        'estimate_number' => 'número da proforma',
        'payment_date' => 'data do pagamento',
        'payment_number' => 'número do pagamento',
        'payment_method_id' => 'meio de pagamento',
        'expense_date' => 'data da despesa',
        'expense_category_id' => 'categoria da despesa',
        'amount' => 'valor',
        'price' => 'preço',
        'quantity' => 'quantidade',
        'discount' => 'desconto',
        'description' => 'descrição',
        'notes' => 'notas',
        'items' => 'artigos',
        'unit_id' => 'unidade',
        'percent' => 'percentagem',
        'exchange_rate' => 'taxa de câmbio',
        'language' => 'idioma',
        'time_zone' => 'fuso horário',
        'date_format' => 'formato de data',
        'fiscal_year' => 'ano fiscal',
        'subject' => 'assunto',
        'body' => 'mensagem',
        'from' => 'remetente',
        'to' => 'destinatário',
        'role' => 'função',
    ],

];
