# Glossário pt-AO — AJUSTA

Referência para quem traduz ou escreve textos da interface em Português de Angola
(`lang/pt_AO.json`, `lang/pt_AO/*.php`). O ficheiro é mantido à mão, fora do Crowdin;
`tests/Unit/TranslationParityTest.php` garante que nenhuma chave inglesa fica por traduzir.

## Norma

- Grafia anterior ao Acordo Ortográfico de 1990, como na assinatura da marca:
  **factura, facturação, acção, direcção, actual, seleccionar, óptimo, activo, colecção**.
- Tratamento formal e neutro (terceira pessoa: "Seleccione", "Introduza", "Pode…").
- Frases curtas. Sem pontos de exclamação nas mensagens de estado.
- Maiúscula só na primeira palavra de títulos e botões ("Adicionar cliente", não "Adicionar Cliente").

## Termos

| Inglês | pt-AO | Nota |
|---|---|---|
| Invoice | Factura | |
| Estimate | Proforma | No PDF: "Factura Proforma" |
| Recurring invoice | Factura recorrente | |
| Credit note | Nota de crédito | Emitir / creditar |
| Payment | Pagamento | |
| Payment receipt | Recibo | |
| Payment mode | Meio de pagamento | |
| Expense | Despesa | |
| Receipt (of an expense) | Comprovativo | |
| Customer | Cliente | |
| Item | Artigo | |
| Tax | Imposto | |
| VAT | IVA | Output tax = IVA liquidado; input tax = IVA dedutível |
| Tax ID | NIF | |
| Amount due | Valor em dívida | |
| Due date | Data de vencimento | |
| Expiry date | Validade / Válida até | |
| Overdue | Vencida | |
| Draft | Rascunho | |
| Statement | Extracto | |
| Dashboard | Painel | |
| Settings | Definições | |
| Preferences | Preferências | |
| User | Utilizador | |
| Member | Membro | |
| Role | Função | |
| Password | Palavra-passe | |
| Email | E-mail | |
| Phone | Telefone | Telemóvel quando for especificamente móvel |
| Address | Morada | |
| State (address) | Província | |
| Zip code | Código postal | |
| Screen | Ecrã | |
| Download | Descarregar | |
| Upload | Carregar | |
| Delete | Eliminar | |
| Save | Guardar | |
| Log in / Log out | Iniciar sessão / Terminar sessão | |
| Backup | Cópia de segurança | |
| Currency | Moeda | Kwanza (AOA), símbolo "Kz" |
| Clone | Duplicar | |
| Template | Modelo | |

## Formatos

- Data: `dd/mm/aaaa` (predefinição das novas empresas).
- Moeda: `1.234,56 Kz` — separador de milhares `.`, decimal `,`, símbolo à direita.
- Fuso horário: `Africa/Luanda`.
