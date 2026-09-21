<?php

namespace Database\Seeders;

use App\Domains\Accounts\Models\Company;
use App\Domains\Accounts\Models\CompanySetting;
use App\Domains\Accounts\Models\User;
use App\Domains\Catalog\Models\Item;
use App\Domains\Catalog\Models\Unit;
use App\Domains\Contacts\Models\Address;
use App\Domains\Contacts\Models\Country;
use App\Domains\Contacts\Models\Customer;
use App\Domains\Metadata\Models\CustomField;
use App\Domains\Metadata\Models\Note;
use App\Domains\Money\Models\Currency;
use App\Domains\Purchases\Models\Expense;
use App\Domains\Purchases\Models\ExpenseCategory;
use App\Domains\Receivables\Application\PaymentAllocationService;
use App\Domains\Receivables\Jobs\GeneratePaymentPdfJob;
use App\Domains\Receivables\Models\Payment;
use App\Domains\Receivables\Models\PaymentAllocation;
use App\Domains\Receivables\Models\PaymentMethod;
use App\Domains\Sales\Application\SerialNumberService;
use App\Domains\Sales\Models\Estimate;
use App\Domains\Sales\Models\EstimateItem;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Sales\Models\InvoiceItem;
use App\Domains\Sales\Models\RecurringInvoice;
use App\Domains\Taxation\Models\Tax;
use App\Domains\Taxation\Models\TaxType;
use App\Facades\Hashids;
use App\Support\Hashids\HashidConnection;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;

/**
 * AJUSTA showcase data for screenshots and demos (development only).
 *
 * Same shape as RealisticDemoSeeder, but the demo company becomes an Angolan
 * business: "Kilamba Serviços, Lda", Portuguese (Angola), Luanda time,
 * Kwanza prices, IVA 14% and Angolan customers. Run after DemoSeeder:
 *
 *     php artisan db:seed --class=AngolaShowcaseSeeder --force
 *
 * It rewrites the demo company's records only; other companies are untouched.
 * NIFs and contacts are fictitious.
 */
class AngolaShowcaseSeeder extends Seeder
{
    private User $user;

    private int $companyId;

    /** @var array<int, Customer> */
    private array $customers = [];

    /** @var array<int, Item> */
    private array $items = [];

    /** @var array<int, ExpenseCategory> */
    private array $expenseCategories = [];

    private int $paymentMethodId;

    private int $unitId;

    private int $currencyId;

    private ?int $countryId = null;

    private int $invoiceSequence = 1;

    private int $estimateSequence = 1;

    private int $paymentSequence = 1;

    /** @var array<int, TaxType> */
    private array $taxTypes = [];

    /** @var array<int, string> */
    private array $invoiceNotes = [];

    public function run(): void
    {
        $this->ensureReferenceData();
        $this->resolveDemoContext();
        $this->cleanupExistingDemoData();

        $this->seedCompanyLogo();
        $this->seedTaxTypes();
        $this->seedNotes();
        $this->seedCustomFields();
        $this->seedCustomers();
        $this->seedCatalogItems();
        $this->seedExpenseCategories();
        $this->seedInvoicesWithPayments();
        $this->seedEstimates();
        $this->seedRecurringInvoice();
        $this->seedExpenses();

        $this->info(sprintf(
            'AngolaShowcaseSeeder done: %d customers, %d items, %d invoices (%d overdue, %d paid, %d partially_paid), %d payments, %d estimates, %d expenses, %d tax types, %d notes, %d recurring.',
            Customer::where('company_id', $this->companyId)->count(),
            Item::where('company_id', $this->companyId)->count(),
            Invoice::where('company_id', $this->companyId)->count(),
            Invoice::where('company_id', $this->companyId)->where('overdue', true)->count(),
            Invoice::where('company_id', $this->companyId)->where('paid_status', Invoice::STATUS_PAID)->count(),
            Invoice::where('company_id', $this->companyId)->where('paid_status', Invoice::STATUS_PARTIALLY_PAID)->count(),
            Payment::where('company_id', $this->companyId)->count(),
            Estimate::where('company_id', $this->companyId)->count(),
            Expense::where('company_id', $this->companyId)->count(),
            TaxType::where('company_id', $this->companyId)->count(),
            Note::where('company_id', $this->companyId)->count(),
            RecurringInvoice::where('company_id', $this->companyId)->count(),
        ));
    }

    /**
     * Find the demo user + company. If missing, run the base DemoSeeder first.
     */
    /**
     * Wrap command output so the seeder can also run programmatically
     * (e.g. from a test) without a Command instance attached.
     */
    private function info(string $message): void
    {
        if ($this->command !== null) {
            $this->command->info($message);
        }
    }

    /**
     * Verify the base reference tables (currencies, countries) have data.
     *
     * Both are seeded by `php artisan db:seed` / the installer via DatabaseSeeder
     * ahead of DemoSeeder. If they're empty, every downstream insert that
     * references them (customers.currency_id, addresses.country_id, items.currency_id…)
     * will fail with an opaque SQLite "FOREIGN KEY constraint failed" — so we bail
     * early with an actionable error.
     */
    private function ensureReferenceData(): void
    {
        if (Currency::count() === 0) {
            throw new RuntimeException(
                'Currencies table is empty. Run `php artisan db:seed --class=CurrenciesTableSeeder --force` first.'
            );
        }

        if (Country::count() === 0) {
            throw new RuntimeException(
                'Countries table is empty. Run `php artisan db:seed --class=CountriesTableSeeder --force` first.'
            );
        }

        // Resolve the Kwanza (or fall back to whatever is at id=1 / first).
        $this->currencyId = Currency::where('code', 'AOA')->first()?->id
            ?? Currency::find(1)?->id
            ?? Currency::first()->id;

        // Resolve country for demo addresses — prefer US if present, else first.
        $this->countryId = Country::where('code', 'AO')->first()?->id
            ?? Country::find(1)?->id
            ?? Country::first()?->id;
    }

    private function resolveDemoContext(): void
    {
        $user = User::where('email', 'demo@invoiceshelf.com')->first();

        if ($user === null) {
            $this->info('Demo user missing; running DemoSeeder first…');
            Artisan::call('db:seed', ['--class' => 'DemoSeeder', '--force' => true]);
            $user = User::where('email', 'demo@invoiceshelf.com')->firstOrFail();
        }

        $this->user = $user;
        $this->companyId = $user->companies()->firstOrFail()->id;

        // CompanyService::setupDefaults() seeds default payment methods + units;
        // use whichever rows exist for this company. Fall back to creating one if
        // setupDefaults wasn't called (edge case — the standard DemoSeeder invokes it).
        $paymentMethod = PaymentMethod::where('company_id', $this->companyId)->first()
            ?? PaymentMethod::create(['name' => 'Bank Transfer', 'company_id' => $this->companyId]);
        $this->paymentMethodId = $paymentMethod->id;

        $unit = Unit::where('company_id', $this->companyId)->first()
            ?? Unit::create(['name' => 'pc', 'company_id' => $this->companyId]);
        $this->unitId = $unit->id;

        // Make the demo company Angolan: Kwanza, pt_AO, Luanda time, FT/PF/RC series.
        CompanySetting::setSettings([
            'currency' => (string) $this->currencyId,
            'language' => 'pt_AO',
            'time_zone' => 'Africa/Luanda',
            'carbon_date_format' => 'd/m/Y',
            'moment_date_format' => 'DD/MM/YYYY',
            'invoice_number_format' => '{{SERIES:FT}}{{DELIMITER:-}}{{SEQUENCE:6}}',
            'estimate_number_format' => '{{SERIES:PF}}{{DELIMITER:-}}{{SEQUENCE:6}}',
            'payment_number_format' => '{{SERIES:RC}}{{DELIMITER:-}}{{SEQUENCE:6}}',
        ], $this->companyId);

        foreach (['Cash', 'Check', 'Credit Card', 'Bank Transfer'] as $name) {
            PaymentMethod::where('company_id', $this->companyId)
                ->where('name', $name)
                ->update(['name' => __($name, [], 'pt_AO')]);
        }

        $company = Company::findOrFail($this->companyId);
        $company->update(['name' => 'Kilamba Serviços, Lda', 'tax_id' => '5417000000']);
    }

    /**
     * Wipe any previously seeded demo data so the seeder is idempotent — you
     * can re-run it after code changes without duplicating records.
     *
     * Deletes in child-first order to respect foreign keys. We deliberately
     * do NOT touch company_settings, the demo user, or the demo company.
     */
    private function cleanupExistingDemoData(): void
    {
        $paymentIds = Payment::where('company_id', $this->companyId)->pluck('id');
        PaymentAllocation::whereIn('payment_id', $paymentIds)->delete();
        Payment::whereIn('id', $paymentIds)->delete();
        InvoiceItem::where('company_id', $this->companyId)->delete();
        Invoice::where('company_id', $this->companyId)->delete();
        EstimateItem::where('company_id', $this->companyId)->delete();
        Estimate::where('company_id', $this->companyId)->delete();
        Expense::where('company_id', $this->companyId)->delete();
        ExpenseCategory::where('company_id', $this->companyId)->delete();

        // Taxes and recurring invoices still point at their customer. Remove
        // them before deleting customers so a second seeder run is idempotent
        // on databases that enforce foreign keys.
        Tax::where('company_id', $this->companyId)->delete();
        RecurringInvoice::where('company_id', $this->companyId)->delete();

        // Customers: delete along with their addresses (addresses keyed by customer_id)
        $customerIds = Customer::where('company_id', $this->companyId)->pluck('id');
        Address::whereIn('customer_id', $customerIds)->delete();
        Customer::whereIn('id', $customerIds)->delete();

        Item::where('company_id', $this->companyId)->delete();

        // The reusable definitions and standalone rows do not cascade from
        // their documents.
        TaxType::where('company_id', $this->companyId)->delete();
        Note::where('company_id', $this->companyId)->delete();
        CustomField::where('company_id', $this->companyId)->delete();
    }

    private function seedCustomers(): void
    {
        $customers = [
            ['Sonadist Distribuição', 'Ana Mendes', 'contas@sonadist.example', '+244 923 111 201', 'https://sonadist.example', 'Rua Rainha Ginga, 29', 'Luanda', 'Luanda', ''],
            ['Muxima Comércio, Lda', 'João Kiala', 'financeiro@muxima.example', '+244 924 552 310', 'https://muxima.example', 'Av. 4 de Fevereiro, 120', 'Luanda', 'Luanda', ''],
            ['Kwanza Logística', 'Helena Lourenço', 'facturas@kwanzalog.example', '+244 925 740 118', 'https://kwanzalog.example', 'Estrada de Catete, km 12', 'Viana', 'Luanda', ''],
            ['Benguela Pescas', 'Manuel Tchipa', 'geral@benguelapescas.example', '+244 926 381 402', 'https://benguelapescas.example', 'Rua Silva Porto, 8', 'Benguela', 'Benguela', ''],
            ['Huambo Agro, SA', 'Rosa Chilombo', 'contabilidade@huamboagro.example', '+244 927 219 663', 'https://huamboagro.example', 'Rua Norton de Matos, 55', 'Huambo', 'Huambo', ''],
            ['Talatona Clínica', 'Paulo Neto', 'admin@talatonaclinica.example', '+244 928 604 725', 'https://talatonaclinica.example', 'Via S8, Talatona', 'Luanda', 'Luanda', ''],
            ['Lobito Construções', 'Carlos Domingos', 'financas@lobitoconst.example', '+244 929 330 841', 'https://lobitoconst.example', 'Av. da Independência, 210', 'Lobito', 'Benguela', ''],
            ['Namibe Hotéis', 'Isabel Kapapelo', 'reservas@namibehoteis.example', '+244 930 912 057', 'https://namibehoteis.example', 'Marginal do Namibe, 3', 'Moçâmedes', 'Namibe', ''],
        ];

        foreach ($customers as [$companyName, $contactName, $email, $phone, $website, $street, $city, $state, $zip]) {
            $customer = Customer::create([
                'name' => $companyName,
                'company_name' => $companyName,
                'contact_name' => $contactName,
                'email' => $email,
                'phone' => $phone,
                'website' => $website,
                'enable_portal' => true,
                'currency_id' => $this->currencyId,
                'company_id' => $this->companyId,
                'creator_id' => $this->user->id,
            ]);

            // Billing + shipping addresses — created via the Customer relation so
            // customer_id is set automatically. We deliberately DO NOT set user_id
            // or company_id: customer addresses leave both null (the real app's
            // CustomerService follows the same pattern). If we did set company_id,
            // Company::address() — a bare hasOne with no scoping — would pick up
            // customer addresses and CompanyResource would try to serialize them,
            // triggering a Company → Address → User → Companies → Company circular
            // reference that crashes json_encode in BootstrapController.
            foreach ([Address::BILLING_TYPE, Address::SHIPPING_TYPE] as $type) {
                $customer->addresses()->create([
                    'name' => $companyName,
                    'address_street_1' => $street,
                    'city' => $city,
                    'state' => $state,
                    'country_id' => $this->countryId,
                    'zip' => $zip,
                    'phone' => $phone,
                    'type' => $type,
                ]);
            }

            $this->customers[] = $customer;
        }
    }

    private function seedCatalogItems(): void
    {
        // Format: [name, description, unit_price_in_cents]
        $items = [
            ['Consultoria sénior', 'Assessoria estratégica por consultor principal (por hora)', 4500000],
            ['Consultoria júnior', 'Implementação e apoio por consultor associado (por hora)', 2200000],
            ['Desenvolvimento web', 'Desenvolvimento de aplicações web (por hora)', 3200000],
            ['Design de interfaces', 'Pesquisa e desenho de interfaces (por hora)', 3500000],
            ['Redacção técnica', 'Documentação e manuais (por hora)', 2500000],
            ['Auditoria de processos', 'Levantamento completo com recomendações', 25000000],
            ['Sessão de estratégia', 'Workshop de meio dia com entregáveis', 45000000],
            ['Pacote de revisão', 'Até 20 horas de revisão técnica', 18000000],
            ['Formação presencial', 'Dia inteiro de formação nas instalações do cliente', 38000000],
            ['Integração à medida', 'Integração com sistema de terceiros, instalação única', 75000000],
            ['Licença Start', 'Licença anual, plano Start', 18000000],
            ['Licença Empresa', 'Licença anual, plano Empresa', 54000000],
        ];

        foreach ($items as [$name, $description, $priceCents]) {
            $this->items[] = Item::create([
                'name' => $name,
                'description' => $description,
                'price' => $priceCents,
                'unit_id' => $this->unitId,
                'currency_id' => $this->currencyId,
                'tax_per_item' => false,
                'company_id' => $this->companyId,
                'creator_id' => $this->user->id,
            ]);
        }
    }

    private function seedExpenseCategories(): void
    {
        $names = [
            ['Software', 'Subscrições, licenças e ferramentas'],
            ['Deslocações', 'Visitas a clientes e transportes'],
            ['Marketing', 'Publicidade, conteúdos e campanhas'],
            ['Material de escritório', 'Papelaria, mobiliário e equipamento'],
            ['Serviços', 'Internet, telefone, electricidade e água'],
            ['Prestadores externos', 'Trabalhadores independentes e subcontratados'],
        ];

        foreach ($names as [$name, $description]) {
            $this->expenseCategories[] = ExpenseCategory::create([
                'name' => $name,
                'description' => $description,
                'company_id' => $this->companyId,
            ]);
        }
    }

    /**
     * Create 35 invoices with a deliberate status + time distribution, then
     * back-fill payments for the PAID and PARTIALLY_PAID ones.
     */
    private function seedInvoicesWithPayments(): void
    {
        // Distribution plan: [count, status, paid_status, overdue, age_weeks_min, age_weeks_max, current_month]
        //
        // Split by time bucket so get_company_stats(period=this_month/last_month/this_quarter) differ.
        $plan = [
            // Current calendar month — each customer receives posted activity
            // so its default Account Activity statement is useful immediately.
            [3, Invoice::STATUS_SENT,      Invoice::STATUS_UNPAID,         false, 0, 3, true],
            [2, Invoice::STATUS_VIEWED,    Invoice::STATUS_UNPAID,         false, 0, 4, true],
            [2, Invoice::STATUS_DRAFT,     Invoice::STATUS_UNPAID,         false, 0, 2, true],
            [3, Invoice::STATUS_COMPLETED, Invoice::STATUS_PAID,           false, 0, 4, true],
            [1, Invoice::STATUS_COMPLETED, Invoice::STATUS_PARTIALLY_PAID, false, 0, 4, true],
            // Last month (4-8 weeks ago)
            [2, Invoice::STATUS_VIEWED,    Invoice::STATUS_UNPAID,         false, 4, 8, false],
            [1, Invoice::STATUS_DRAFT,     Invoice::STATUS_UNPAID,         false, 4, 8, false],
            [2, Invoice::STATUS_SENT,      Invoice::STATUS_UNPAID,         true,  5, 8, false],  // overdue
            [3, Invoice::STATUS_COMPLETED, Invoice::STATUS_PAID,           false, 4, 8, false],
            [2, Invoice::STATUS_COMPLETED, Invoice::STATUS_PARTIALLY_PAID, false, 4, 8, false],
            // 2-3 months ago
            [2, Invoice::STATUS_SENT,      Invoice::STATUS_UNPAID,         true,  10, 13, false], // overdue
            [3, Invoice::STATUS_COMPLETED, Invoice::STATUS_PAID,           false, 8, 13, false],
            [2, Invoice::STATUS_COMPLETED, Invoice::STATUS_PARTIALLY_PAID, false, 9, 13, false],
            [1, Invoice::STATUS_VIEWED,    Invoice::STATUS_UNPAID,         false, 10, 13, false],
            // 4-6 months ago (older)
            [2, Invoice::STATUS_COMPLETED, Invoice::STATUS_PAID,           false, 16, 24, false],
            [1, Invoice::STATUS_COMPLETED, Invoice::STATUS_PARTIALLY_PAID, false, 16, 22, false],
            [1, Invoice::STATUS_VIEWED,    Invoice::STATUS_UNPAID,         false, 18, 24, false],
            [1, Invoice::STATUS_SENT,      Invoice::STATUS_UNPAID,         false, 18, 24, false],
            [1, Invoice::STATUS_DRAFT,     Invoice::STATUS_UNPAID,         false, 20, 26, false],
        ];

        $activityCustomerIndex = 0;

        foreach ($plan as [$count, $status, $paidStatus, $overdue, $minWeeks, $maxWeeks, $currentMonth]) {
            for ($i = 0; $i < $count; $i++) {
                $invoiceDate = $currentMonth
                    ? Carbon::now()->startOfMonth()->addDays(random_int(0, Carbon::now()->day - 1))->startOfDay()
                    : Carbon::now()->subWeeks(random_int($minWeeks, $maxWeeks))->subDays(random_int(0, 6))->startOfDay();
                $dueDate = $overdue
                    ? Carbon::now()->subDays(random_int(3, 45))->startOfDay()
                    : $invoiceDate->copy()->addDays(30);

                $itemCount = random_int(1, 4);
                $customer = $currentMonth && $status !== Invoice::STATUS_DRAFT
                    ? $this->customers[$activityCustomerIndex++ % count($this->customers)]
                    : null;

                $this->createInvoice($invoiceDate, $dueDate, $status, $paidStatus, $itemCount, $overdue, $customer);
            }
        }
    }

    private function createInvoice(
        Carbon $invoiceDate,
        Carbon $dueDate,
        string $status,
        string $paidStatus,
        int $itemCount,
        bool $overdue,
        ?Customer $customer = null,
    ): void {
        $customer ??= $this->customers[array_rand($this->customers)];
        $selectedItems = collect($this->items)->random($itemCount)->all();

        // Compute totals from the selected line items
        $lines = [];
        $subTotal = 0;
        foreach ($selectedItems as $item) {
            $quantity = random_int(1, 8);
            $lineTotal = $item->price * $quantity;
            $subTotal += $lineTotal;
            $lines[] = [
                'item' => $item,
                'quantity' => $quantity,
                'line_total' => $lineTotal,
            ];
        }

        // Tax is computed once off the subtotal, not per line, and then has to be
        // carried through total and every base_* twin.
        $taxType = $this->taxTypeForDocument($this->invoiceSequence);
        $taxAmount = $taxType ? (int) round($subTotal * $taxType->percent / 100) : 0;

        $total = $subTotal + $taxAmount;
        // PaymentAllocationService is the source of truth for paid balances.
        // Start with the full balance, then let it reduce the amount after each
        // seeded payment is allocated.
        $dueAmount = $total;

        $invoiceNumber = 'FT-'.str_pad((string) $this->invoiceSequence, 6, '0', STR_PAD_LEFT);
        $this->invoiceSequence++;

        $invoice = Invoice::create([
            'invoice_date' => $invoiceDate->toDateString(),
            'due_date' => $dueDate->toDateString(),
            'invoice_number' => $invoiceNumber,
            'reference_number' => null,
            'template_name' => 'invoice1',
            'type' => Invoice::TYPE_INVOICE,
            'status' => $status,
            'paid_status' => $paidStatus,
            'overdue' => $overdue,
            'tax_per_item' => 'NO',
            'tax_included' => false,
            'discount_per_item' => 'NO',
            'discount_type' => 'fixed',
            'discount' => 0,
            'discount_val' => 0,
            'sub_total' => $subTotal,
            'total' => $total,
            'tax' => $taxAmount,
            'due_amount' => $dueAmount,
            'exchange_rate' => 1,
            'base_discount_val' => 0,
            'base_sub_total' => $subTotal,
            'base_total' => $total,
            'base_tax' => $taxAmount,
            'base_due_amount' => $dueAmount,
            'currency_id' => $this->currencyId,
            'customer_id' => $customer->id,
            'company_id' => $this->companyId,
            'user_id' => $this->user->id,
            'creator_id' => $this->user->id,
            'sent' => $status !== Invoice::STATUS_DRAFT,
            'viewed' => in_array($status, [Invoice::STATUS_VIEWED, Invoice::STATUS_COMPLETED], true),
            'notes' => $this->invoiceNotes === [] ? null : $this->invoiceNotes[$this->invoiceSequence % count($this->invoiceNotes)],
        ]);

        // Touch timestamps to match the invoice_date so tool queries like
        // `latest('invoice_date')` match `latest('created_at')` plausibly.
        // The PDF routes bind on unique_hash. Creating through the model rather
        // than InvoiceService skips the one place that normally assigns it, so
        // set it here or every seeded document 404s on preview and download.
        $serial = (new SerialNumberService)
            ->setModel($invoice)
            ->setCompany($invoice->company_id)
            ->setCustomer($invoice->customer_id)
            ->setSequenceScope(['type' => Invoice::TYPE_INVOICE])
            ->setNextNumbers();

        $invoice->sequence_number = $serial->nextSequenceNumber;
        $invoice->customer_sequence_number = $serial->nextCustomerSequenceNumber;
        $invoice->unique_hash = Hashids::connection(HashidConnection::Invoice->value)->encode($invoice->id);
        $invoice->created_at = $invoiceDate;
        $invoice->updated_at = $invoiceDate;
        $invoice->save();

        foreach ($lines as $line) {
            InvoiceItem::create([
                'item_id' => $line['item']->id,
                'name' => $line['item']->name,
                'description' => $line['item']->description,
                'price' => $line['item']->price,
                'quantity' => $line['quantity'],
                'total' => $line['line_total'],
                'discount_type' => 'fixed',
                'discount' => 0,
                'discount_val' => 0,
                'tax' => 0,
                'invoice_id' => $invoice->id,
                'company_id' => $this->companyId,
                'exchange_rate' => 1,
                'base_price' => $line['item']->price,
                'base_discount_val' => 0,
                'base_tax' => 0,
                'base_total' => $line['line_total'],
            ]);
        }

        if ($taxType !== null) {
            $this->applyDocumentTax($invoice, $taxType, $taxAmount, 'invoice_id');
        }

        // Back-fill payments for PAID and PARTIALLY_PAID invoices. Allocation
        // recalculation updates their stored balance and paid status.
        if ($paidStatus === Invoice::STATUS_PAID) {
            $this->createPayment($invoice, $total, $invoiceDate->copy()->addDays(random_int(3, 25)));
        } elseif ($paidStatus === Invoice::STATUS_PARTIALLY_PAID) {
            // 40% of the total, in one payment
            $partialAmount = (int) round($total * 0.4);
            $this->createPayment($invoice, $partialAmount, $invoiceDate->copy()->addDays(random_int(5, 20)));
        }
    }

    private function createPayment(Invoice $invoice, int $amount, Carbon $paymentDate): void
    {
        $paymentNumber = 'RC-'.str_pad((string) $this->paymentSequence, 6, '0', STR_PAD_LEFT);
        $this->paymentSequence++;

        // Cap payment_date at today so `list_recent_payments(days=N)` doesn't
        // return future-dated rows for tests done close to the invoice_date.
        if ($paymentDate->isFuture()) {
            $paymentDate = Carbon::now()->subDays(random_int(1, 7));
        }

        $payment = Payment::withoutEvents(fn () => Payment::create([
            'payment_number' => $paymentNumber,
            'payment_date' => $paymentDate->toDateString(),
            'amount' => $amount,
            'base_amount' => $amount,
            'exchange_rate' => 1,
            'user_id' => $this->user->id,
            'creator_id' => $this->user->id,
            'customer_id' => $invoice->customer_id,
            'payment_method_id' => $this->paymentMethodId,
            'currency_id' => $this->currencyId,
            'company_id' => $this->companyId,
            'notes' => null,
        ]));

        // See seedInvoice(): the PDF routes bind on unique_hash.
        $serial = (new SerialNumberService)
            ->setModel($payment)
            ->setCompany($payment->company_id)
            ->setCustomer($payment->customer_id)
            ->setNextNumbers();

        $payment->sequence_number = $serial->nextSequenceNumber;
        $payment->customer_sequence_number = $serial->nextCustomerSequenceNumber;
        $payment->unique_hash = Hashids::connection(HashidConnection::Payment->value)->encode($payment->id);
        $payment->created_at = $paymentDate;
        $payment->updated_at = $paymentDate;
        Payment::withoutEvents(fn () => $payment->save());

        app(PaymentAllocationService::class)->replace($payment, [[
            'invoice_id' => $invoice->id,
            'amount' => $amount,
        ]]);

        GeneratePaymentPdfJob::dispatch($payment);
    }

    private function seedEstimates(): void
    {
        // Status mix inferred from Estimate model constants — use strings directly.
        $plan = [
            [2, 'DRAFT',    0,  3],
            [3, 'SENT',     2,  6],
            [2, 'ACCEPTED', 4,  8],
            [1, 'REJECTED', 8, 12],
        ];

        foreach ($plan as [$count, $status, $minWeeks, $maxWeeks]) {
            for ($i = 0; $i < $count; $i++) {
                $weeksAgo = random_int($minWeeks, $maxWeeks);
                $estimateDate = Carbon::now()->subWeeks($weeksAgo)->subDays(random_int(0, 6))->startOfDay();
                $expiryDate = $estimateDate->copy()->addDays(30);

                $this->createEstimate($estimateDate, $expiryDate, $status, random_int(1, 3));
            }
        }
    }

    private function createEstimate(Carbon $estimateDate, Carbon $expiryDate, string $status, int $itemCount): void
    {
        $customer = $this->customers[array_rand($this->customers)];
        $selectedItems = collect($this->items)->random($itemCount)->all();

        $lines = [];
        $subTotal = 0;
        foreach ($selectedItems as $item) {
            $quantity = random_int(1, 6);
            $lineTotal = $item->price * $quantity;
            $subTotal += $lineTotal;
            $lines[] = ['item' => $item, 'quantity' => $quantity, 'line_total' => $lineTotal];
        }

        // Same arithmetic as createInvoice(): one rounding off the subtotal,
        // then carried through total and the base_* twins.
        $taxType = $this->taxTypeForDocument($this->estimateSequence);
        $taxAmount = $taxType ? (int) round($subTotal * $taxType->percent / 100) : 0;
        $total = $subTotal + $taxAmount;

        $estimateNumber = 'PF-'.str_pad((string) $this->estimateSequence, 6, '0', STR_PAD_LEFT);
        $this->estimateSequence++;

        $estimate = Estimate::create([
            'estimate_date' => $estimateDate->toDateString(),
            'expiry_date' => $expiryDate->toDateString(),
            'estimate_number' => $estimateNumber,
            // Without this the estimate has no template and its PDF route 500s.
            // seedInvoice() has always set it; the estimate side never did.
            'template_name' => 'estimate1',
            'status' => $status,
            'tax_per_item' => 'NO',
            'tax_included' => false,
            'discount_per_item' => 'NO',
            'discount_type' => 'fixed',
            'discount' => 0,
            'discount_val' => 0,
            'sub_total' => $subTotal,
            'total' => $total,
            'tax' => $taxAmount,
            'exchange_rate' => 1,
            'base_discount_val' => 0,
            'base_sub_total' => $subTotal,
            'base_total' => $total,
            'base_tax' => $taxAmount,
            'currency_id' => $this->currencyId,
            'customer_id' => $customer->id,
            'company_id' => $this->companyId,
            'user_id' => $this->user->id,
            'creator_id' => $this->user->id,
            'notes' => null,
        ]);

        if ($taxType !== null) {
            $this->applyDocumentTax($estimate, $taxType, $taxAmount, 'estimate_id');
        }

        // See seedInvoice(): the PDF routes bind on unique_hash.
        $serial = (new SerialNumberService)
            ->setModel($estimate)
            ->setCompany($estimate->company_id)
            ->setCustomer($estimate->customer_id)
            ->setNextNumbers();

        $estimate->sequence_number = $serial->nextSequenceNumber;
        $estimate->customer_sequence_number = $serial->nextCustomerSequenceNumber;
        $estimate->unique_hash = Hashids::connection(HashidConnection::Estimate->value)->encode($estimate->id);
        $estimate->created_at = $estimateDate;
        $estimate->updated_at = $estimateDate;
        $estimate->save();

        foreach ($lines as $line) {
            EstimateItem::create([
                'item_id' => $line['item']->id,
                'name' => $line['item']->name,
                'description' => $line['item']->description,
                'price' => $line['item']->price,
                'quantity' => $line['quantity'],
                'total' => $line['line_total'],
                'discount_type' => 'fixed',
                'discount' => 0,
                'discount_val' => 0,
                'tax' => 0,
                'estimate_id' => $estimate->id,
                'company_id' => $this->companyId,
                'exchange_rate' => 1,
                'base_price' => $line['item']->price,
                'base_discount_val' => 0,
                'base_tax' => 0,
                'base_total' => $line['line_total'],
            ]);
        }
    }

    /**
     * Attach the demo company's logo.
     *
     * The showcase company has no logo of its own yet, so the templates show
     * its name as text.
     */
    private function seedCompanyLogo(): void
    {
        // The Acme sample logo does not belong to this company.
        Company::find($this->companyId)?->clearMediaCollection('logo');
    }

    /**
     * Reusable tax definitions.
     *
     * type must be GENERAL: TaxTypesController::index() filters on it, so a
     * MODULE row would be invisible in the admin UI.
     */
    private function seedTaxTypes(): void
    {
        $definitions = [
            ['name' => 'IVA', 'percent' => 14, 'description' => 'Imposto sobre o Valor Acrescentado — taxa geral'],
            ['name' => 'Isento', 'percent' => 0, 'description' => 'Operações isentas de IVA'],
        ];

        foreach ($definitions as $definition) {
            $this->taxTypes[] = TaxType::create([
                'name' => $definition['name'],
                'percent' => $definition['percent'],
                'calculation_type' => 'percentage',
                'compound_tax' => false,
                'collective_tax' => false,
                'description' => $definition['description'],
                'type' => TaxType::TYPE_GENERAL,
                'company_id' => $this->companyId,
            ]);
        }
    }

    /**
     * The reusable notes library, plus the text those notes put on documents.
     *
     * These are two unrelated things in this application: a Note row is a
     * snippet an operator inserts by hand, and a document's `notes` column is a
     * plain string copied at that moment. There is no foreign key between them,
     * and is_default only controls a badge in the settings list -- nothing
     * pre-fills a new document with it. So the library is seeded for the
     * settings screen, and the same text is put on documents separately.
     */
    private function seedNotes(): void
    {
        $notes = [
            ['type' => 'Invoice', 'name' => 'Condições de pagamento', 'notes' => 'Pagamento a 15 dias por transferência bancária.', 'is_default' => true],
            ['type' => 'Invoice', 'name' => 'Agradecimento', 'notes' => 'Obrigado pela preferência.', 'is_default' => false],
            ['type' => 'Estimate', 'name' => 'Validade da proforma', 'notes' => 'Esta proforma é válida por 30 dias a contar da data de emissão.', 'is_default' => true],
            ['type' => 'Payment', 'name' => 'Confirmação de recebimento', 'notes' => 'Recebemos o seu pagamento. Obrigado.', 'is_default' => true],
        ];

        foreach ($notes as $note) {
            Note::create($note + ['company_id' => $this->companyId]);
        }

        $this->invoiceNotes = array_column(
            array_filter($notes, fn ($note) => $note['type'] === 'Invoice'),
            'notes'
        );
    }

    /**
     * Custom fields on customers.
     *
     * Customer is the only model_type with a create/edit UI end to end, so a
     * seeded field is both visible and editable. The PDF renders only
     * model_type 'Item' fields, which would add a column to the items table and
     * change a layout that was just squared up across both drivers -- left
     * alone deliberately.
     */
    private function seedCustomFields(): void
    {
        $fields = [
            ['name' => 'Gestor de conta', 'type' => 'Input', 'string_answer' => 'Teresa Bumba'],
            ['name' => 'Renovação do contrato', 'type' => 'Date', 'date_answer' => Carbon::now()->addMonths(8)->toDateString()],
        ];

        foreach ($fields as $order => $field) {
            CustomField::create($field + [
                'label' => $field['name'],
                'model_type' => 'Customer',
                'slug' => clean_slug('Customer', $field['name']),
                'is_required' => false,
                'order' => $order + 1,
                'company_id' => $this->companyId,
            ]);
        }
    }

    /**
     * One active recurring invoice, so the feature is not an empty screen.
     *
     * frequency is a five-field cron expression, not a keyword, and
     * next_invoice_at has to be derived from it -- nothing computes that at read
     * time. Line items hang off recurring_invoice_id, leaving invoice_id null
     * until an invoice is actually generated.
     */
    private function seedRecurringInvoice(): void
    {
        $customer = $this->customers[0];
        $items = collect($this->items)->random(2)->all();

        $subTotal = 0;
        foreach ($items as $item) {
            $subTotal += $item->price;
        }

        $taxType = $this->taxTypes[0];
        $tax = (int) round($subTotal * $taxType->percent / 100);
        $total = $subTotal + $tax;

        $startsAt = Carbon::now()->startOfMonth()->addMonth();
        $frequency = '0 0 1 * *'; // monthly, on the first

        $recurring = RecurringInvoice::create([
            'starts_at' => $startsAt,
            'send_automatically' => false,
            'customer_id' => $customer->id,
            'company_id' => $this->companyId,
            'creator_id' => $this->user->id,
            'status' => RecurringInvoice::ACTIVE,
            'next_invoice_at' => RecurringInvoice::getNextInvoiceDate($frequency, $startsAt),
            'frequency' => $frequency,
            'limit_by' => RecurringInvoice::NONE,
            'currency_id' => $this->currencyId,
            'exchange_rate' => 1,
            'tax_per_item' => 'NO',
            'discount_per_item' => 'NO',
            'tax_included' => false,
            'discount_type' => 'fixed',
            'discount' => 0,
            'discount_val' => 0,
            'sub_total' => $subTotal,
            'tax' => $tax,
            'total' => $total,
            'due_amount' => $total,
            'template_name' => 'invoice1',
            'notes' => $this->invoiceNotes[0] ?? null,
        ]);

        foreach ($items as $item) {
            InvoiceItem::create([
                'item_id' => $item->id,
                'name' => $item->name,
                'description' => $item->description,
                'price' => $item->price,
                'quantity' => 1,
                'total' => $item->price,
                'discount_type' => 'fixed',
                'discount' => 0,
                'discount_val' => 0,
                'tax' => 0,
                'recurring_invoice_id' => $recurring->id,
                'company_id' => $this->companyId,
                'exchange_rate' => 1,
                'base_price' => $item->price,
                'base_discount_val' => 0,
                'base_tax' => 0,
                'base_total' => $item->price,
            ]);
        }

        $this->applyDocumentTax($recurring, $taxType, $tax, 'recurring_invoice_id');
    }

    /**
     * Attach a document-level tax row.
     *
     * The service layer trusts whatever `amount` it is given rather than
     * recomputing it, so the caller owns the arithmetic and has to keep the
     * document's own tax/total columns in step. Every row must point at a real
     * TaxType: TaxResource dereferences it without a null check.
     */
    private function applyDocumentTax(object $document, TaxType $taxType, int $amount, string $foreignKey): void
    {
        Tax::create([
            'tax_type_id' => $taxType->id,
            $foreignKey => $document->id,
            'company_id' => $this->companyId,
            'name' => $taxType->name,
            'calculation_type' => 'percentage',
            'percent' => $taxType->percent,
            'amount' => $amount,
            'compound_tax' => false,
            'exchange_rate' => 1,
            'base_amount' => $amount,
            'currency_id' => $this->currencyId,
        ]);
    }

    /**
     * The tax type to apply to a document, or null for an untaxed one.
     *
     * Most documents are taxed, but not all: a demo where every row looks the
     * same shows less than one with a zero-rated example in it.
     */
    private function taxTypeForDocument(int $sequence): ?TaxType
    {
        if ($this->taxTypes === [] || $sequence % 5 === 0) {
            return null;
        }

        return $this->taxTypes[0];
    }

    private function seedExpenses(): void
    {
        // 15 expenses spread across categories and months
        // Format: [category_index, amount_cents, age_weeks_min, age_weeks_max, notes]
        $expenses = [
            [0, 1500000,  0,  2, 'Microsoft 365 — equipa'],
            [0, 4200000,  2,  4, 'Alojamento do site'],
            [0, 6800000,  6,  8, 'Licenças de software'],
            [1, 9500000,  1,  3, 'Viagem a Benguela — cliente'],
            [1, 2600000,  2,  4, 'Táxis e combustível'],
            [1, 18000000, 8, 12, 'Feira — stand e alojamento'],
            [2, 3500000,  0,  2, 'Anúncios nas redes sociais'],
            [2, 9000000,  4,  6, 'Material promocional'],
            [3, 750000,   1,  3, 'Papel e toner'],
            [3, 6500000,  6,  8, 'Cadeiras de escritório'],
            [4, 1800000,  0,  1, 'Internet do escritório'],
            [4, 900000,   0,  1, 'Telemóveis da equipa'],
            [4, 4000000,  4,  6, 'Electricidade e gerador'],
            [5, 32000000, 2,  4, 'Designer independente — site'],
            [5, 21000000, 6, 10, 'Programador externo — integração'],
        ];

        foreach ($expenses as [$catIndex, $amount, $minWeeks, $maxWeeks, $notes]) {
            $weeksAgo = random_int($minWeeks, $maxWeeks);
            $expenseDate = Carbon::now()->subWeeks($weeksAgo)->subDays(random_int(0, 6))->startOfDay();

            $expense = Expense::create([
                'expense_date' => $expenseDate->toDateString(),
                'amount' => $amount,
                'base_amount' => $amount,
                'exchange_rate' => 1,
                'notes' => $notes,
                'expense_category_id' => $this->expenseCategories[$catIndex]->id,
                'currency_id' => $this->currencyId,
                'company_id' => $this->companyId,
                'creator_id' => $this->user->id,
            ]);

            $expense->created_at = $expenseDate;
            $expense->updated_at = $expenseDate;
            $expense->save();
        }
    }
}
