<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Dealer;
use App\Models\Vendor;
use App\Models\Route;
use App\Models\DayLoadBatch;
use App\Models\DayLoadEntry;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\DailyBill;
use App\Models\WeeklyBill;
use App\Models\CustomerPayment;
use App\Models\DealerPayment;
use App\Models\VendorPayment;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Emi;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PerfectDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Clearing existing transaction data...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('purchase_items')->truncate();
        DB::table('purchases')->truncate();
        DB::table('day_load_entries')->truncate();
        DB::table('day_load_batches')->truncate();
        DB::table('daily_bills')->truncate();
        DB::table('weekly_bills')->truncate();
        DB::table('customer_payments')->truncate();
        DB::table('dealer_payments')->truncate();
        DB::table('vendor_payments')->truncate();
        DB::table('expenses')->truncate();
        DB::table('emis')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->command->info('Seeding fresh master records...');
        $this->seedMasterData();
        $this->command->info('Seeding day-load batches with entries...');
        $this->seedDayLoads();
        $this->command->info('Seeding purchases...');
        $this->seedPurchases();
        $this->command->info('Seeding daily bills...');
        $this->seedDailyBills();
        $this->command->info('Seeding weekly bills...');
        $this->seedWeeklyBills();
        $this->command->info('Seeding payments...');
        $this->seedPayments();
        $this->command->info('Seeding expenses & EMIs...');
        $this->seedExpensesAndEmis();
        $this->command->info('Perfect data seeding complete!');
    }

    private function seedMasterData(): void
    {
        // ── Routes ────────────────────
        $routes = ['Ambattur', 'Porur', 'Chromepet', 'Tambaram', 'Avadi'];
        foreach ($routes as $name) {
            Route::firstOrCreate(['route_name' => $name]);
        }
        $routeIds = Route::pluck('id', 'route_name');

        // ── Customers ─────────────────
        $customers = [
            ['name' => 'Sri Murugan Chicken Centre', 'phone' => '9000010001', 'route_id' => $routeIds['Ambattur'], 'type' => 'Wholesale', 'address' => '13, Ambattur OT Main Rd', 'balance' => 0],
            ['name' => 'Annai Chicken Shop',          'phone' => '9000010002', 'route_id' => $routeIds['Ambattur'], 'type' => 'Retail',    'address' => '67, Red Hills Rd', 'balance' => 0],
            ['name' => 'Kaveri Meat Mart',            'phone' => '9000010003', 'route_id' => $routeIds['Porur'],    'type' => 'Wholesale', 'address' => '22, Arcot Rd', 'balance' => 0],
            ['name' => 'Vignesh Fresh Chicken',       'phone' => '9000010004', 'route_id' => $routeIds['Porur'],    'type' => 'Retail',    'address' => '5, Ram Nagar', 'balance' => 0],
            ['name' => 'Sakthi Poultry Centre',       'phone' => '9000010005', 'route_id' => $routeIds['Chromepet'],'type' => 'Wholesale', 'address' => '1, Gandhi Rd', 'balance' => 0],
            ['name' => 'New Krishna Chicken',          'phone' => '9000010006', 'route_id' => $routeIds['Chromepet'],'type' => 'Retail',    'address' => '120, GST Rd', 'balance' => 0],
            ['name' => 'Siva Chicken Stall',           'phone' => '9000010007', 'route_id' => $routeIds['Tambaram'],'type' => 'Retail',    'address' => '3, Mudichur', 'balance' => 0],
            ['name' => 'Balaji Wholesale Poultry',     'phone' => '9000010008', 'route_id' => $routeIds['Avadi'],   'type' => 'Wholesale', 'address' => '88, Avadi Main Rd', 'balance' => 0],
        ];
        foreach ($customers as $c) {
            Customer::firstOrCreate(['phone' => $c['phone']], $c);
        }

        // ── Add real vendor names ─────
        $realVendors = [
            ['firm_name' => 'Annai Feeds Pvt Ltd',     'phone' => '9876500101', 'location' => 'Ambattur', 'contact_person' => 'Senthil',  'route' => 'Ambattur'],
            ['firm_name' => 'Sri Murugan Agencies',     'phone' => '9876500102', 'location' => 'Porur',    'contact_person' => 'Murugan',  'route' => 'Porur'],
            ['firm_name' => 'Kaveri Poultry Supplies',  'phone' => '9876500103', 'location' => 'Porur',    'contact_person' => 'Karthik',  'route' => 'Porur'],
            ['firm_name' => 'Venky\'s India Ltd',       'phone' => '9876500104', 'location' => 'Tambaram','contact_person' => 'Venkatesh','route' => 'Tambaram'],
            ['firm_name' => 'Suguna Poultry Feed',      'phone' => '9876500105', 'location' => 'Avadi',    'contact_person' => 'Sugumar',  'route' => 'Avadi'],
            ['firm_name' => 'IB Group Hatcheries',      'phone' => '9876500106', 'location' => 'Chennai',  'contact_person' => 'Iqbal',    'route' => 'Ambattur'],
        ];
        foreach ($realVendors as $v) {
            Vendor::firstOrCreate(['phone' => $v['phone']], $v);
        }
    }

    private function seedDayLoads(): void
    {
        $vendors = Vendor::orderBy('id')->get();
        $dealers = Dealer::orderBy('id')->get();

        // 8 business days: Mon Jun 29 - Wed Jul 08 (today)
        // We need records in day_load_batches with billing_date matching
        // purchase dates so the union query in invoices() picks them up together.

        $dates = [];
        $start = Carbon::create(2026, 6, 29); // Monday
        for ($i = 0; $i < 10; $i++) {
            $d = $start->copy()->addDays($i);
            if ($d->isWeekend()) continue; // skip Sat/Sun
            $dates[] = $d->format('Y-m-d');
        }

        // Realistic per-batch data: (farm_total_bird_weight)
        $dayData = [
            '2026-06-29' => ['farm' => 1650.0, 'rate' => 95, 'paper_rate' => 78],
            '2026-06-30' => ['farm' => 1880.0, 'rate' => 92, 'paper_rate' => 76],
            '2026-07-01' => ['farm' => 1420.0, 'rate' => 96, 'paper_rate' => 79],
            '2026-07-02' => ['farm' => 2100.0, 'rate' => 94, 'paper_rate' => 77],
            '2026-07-03' => ['farm' => 1750.0, 'rate' => 93, 'paper_rate' => 76],
            '2026-07-06' => ['farm' => 1920.0, 'rate' => 97, 'paper_rate' => 80],
            '2026-07-07' => ['farm' => 1580.0, 'rate' => 95, 'paper_rate' => 78],
            '2026-07-08' => ['farm' => 1340.0, 'rate' => 96, 'paper_rate' => 79],
        ];

        $entryCount = 0;

        foreach ($dayData as $date => $cfg) {
            $numEntries = rand(3, 6);
            $entries = [];

            for ($e = 0; $e < $numEntries; $e++) {
                $boxes         = rand(2, 12);
                $avgBoxWeight  = round(rand(340, 420) / 10, 1);  // 34.0 - 42.0 kg
                $avgEmpty      = round(rand(105, 125) / 10, 1);  // 10.5 - 12.5 kg
                $totalBoxWt    = round($boxes * $avgBoxWeight, 1);
                $totalEmpty    = round($boxes * $avgEmpty, 1);
                $birdWeight    = round($totalBoxWt - $totalEmpty, 2);

                $entries[] = [
                    'vendor'  => $vendors[rand(0, $vendors->count() - 1)],
                    'dealer'  => $dealers[rand(0, $dealers->count() - 1)],
                    'boxes'       => $boxes,
                    'box_weight'  => $totalBoxWt,
                    'empty_weight'=> $totalEmpty,
                    'bird_weight' => $birdWeight,
                    'paper_rate'  => $cfg['paper_rate'],
                    'billing_rate'=> $cfg['rate'],
                    'customer_rate'=> $cfg['rate'] + 50 + rand(0, 15), // ~145-162
                ];
            }

            // Create batch
            $batch = DayLoadBatch::create([
                'billing_date' => $date,
                'status'       => 'Open',
            ]);

            $createdEntries = [];

            foreach ($entries as $row) {
                $created = DayLoadEntry::create([
                    'batch_id'      => $batch->id,
                    'vendor_id'     => $row['vendor']->id,
                    'dealer_id'     => $row['dealer']->id,
                    'paper_rate'    => $row['paper_rate'],
                    'billing_rate'  => $row['billing_rate'],
                    'customer_rate' => $row['customer_rate'],
                    'no_of_boxes'   => $row['boxes'],
                    'box_weight'    => $row['box_weight'],
                    'empty_weight'  => $row['empty_weight'],
                    'farm_weight'   => 0,
                    'status'        => 'Active',
                    'version'       => 1,
                ]);

                $createdEntries[] = $created;
                $entryCount++;
            }

            // Now distribute the farm total proportionally
            $totalBirdWeight = array_sum(array_map(fn($e) => $e->bird_weight, $createdEntries));

            foreach ($createdEntries as $entry) {
                $farmWeight = $totalBirdWeight > 0
                    ? round($cfg['farm'] * ($entry->bird_weight / $totalBirdWeight), 1)
                    : 0;
                $lossWeight = round($farmWeight - $entry->bird_weight, 1);
                $entry->updateQuietly([
                    'farm_weight'  => $farmWeight,
                    'loss_weight'  => $lossWeight,
                    'total_weight' => $lossWeight,
                ]);
            }

            // Update batch totals
            $totals = DB::table('day_load_entries')
                ->where('batch_id', $batch->id)
                ->selectRaw('
                    SUM(no_of_boxes) as total_boxes,
                    SUM(box_weight) as total_box_weight,
                    SUM(empty_weight) as total_empty_weight,
                    SUM(bird_weight) as total_bird_weight,
                    COALESCE(SUM(farm_weight), 0) as total_farm_weight,
                    COALESCE(SUM(loss_weight), 0) as total_loss_weight
                ')
                ->first();

            $batch->update([
                'total_boxes'        => $totals->total_boxes ?? 0,
                'total_box_weight'   => $totals->total_box_weight ?? 0,
                'total_empty_weight' => $totals->total_empty_weight ?? 0,
                'total_bird_weight'  => $totals->total_bird_weight ?? 0,
                'total_farm_weight'  => $totals->total_farm_weight ?? 0,
                'total_loss_weight'  => $totals->total_loss_weight ?? 0,
            ]);
        }

        $this->command->info("  Created " . count($dayData) . " batches with {$entryCount} entries");
    }

    private function seedPurchases(): void
    {
        $vendors = Vendor::whereIn('phone', [
            '9876500101', '9876500102', '9876500103',
            '9876500104', '9876500105', '9876500106',
        ])->get();

        if ($vendors->isEmpty()) {
            $vendors = Vendor::orderBy('id')->take(6)->get();
        }

        $feedItems = [
            ['name' => 'Broiler Starter Feed', 'qty' => 50,  'unit' => 'Bags', 'rate' => 1850],
            ['name' => 'Broiler Grower Feed',  'qty' => 40,  'unit' => 'Bags', 'rate' => 1720],
            ['name' => 'Broiler Finisher Feed','qty' => 30,  'unit' => 'Bags', 'rate' => 1680],
            ['name' => 'Broiler Chick (Day Old)','qty' => 500, 'unit' => 'Nos', 'rate' => 38],
            ['name' => 'Lasixox 10%',          'qty' => 25,  'unit' => 'Kg',  'rate' => 420],
            ['name' => 'ND Vaccine',           'qty' => 200, 'unit' => 'Nos', 'rate' => 12],
        ];

        $dates = ['2026-06-29', '2026-06-30', '2026-07-01', '2026-07-02', '2026-07-03', '2026-07-06', '2026-07-07', '2026-07-08'];

        $purchaseCount = 0;

        foreach ($dates as $date) {
            // 1-3 purchases per date
            $numPurchases = rand(1, 3);
            for ($p = 0; $p < $numPurchases; $p++) {
                $vendor    = $vendors[rand(0, $vendors->count() - 1)];
                $gstPct    = [5, 12, 0][rand(0, 2)];
                $numItems  = rand(1, 3);
                $selected  = array_rand($feedItems, min($numItems, count($feedItems)));
                if (!is_array($selected)) $selected = [$selected];

                $subTotal = 0;
                $items    = [];

                foreach ($selected as $idx) {
                    $item = $feedItems[$idx];
                    $qty  = $item['qty'] + rand(-5, 10);
                    $lineTotal = $qty * $item['rate'];
                    $subTotal += $lineTotal;
                    $items[] = [
                        'item_name'    => $item['name'],
                        'quantity'     => max(1, $qty),
                        'unit'         => $item['unit'],
                        'rate'         => $item['rate'],
                        'total_amount' => $lineTotal,
                    ];
                }

                $gstAmt = $gstPct > 0 ? round($subTotal * $gstPct / 100, 2) : 0;
                $total  = $subTotal + $gstAmt;

                $invoiceNo = 'PUR-' . str_replace('-', '', $date) . '-' . str_pad($p + 1, 2, '0', STR_PAD_LEFT);

                $purchase = Purchase::create([
                    'vendor_id'      => $vendor->id,
                    'vendor_name'    => $vendor->firm_name,
                    'invoice_no'     => $invoiceNo,
                    'date'           => $date,
                    'gst_percentage' => $gstPct,
                    'gst_amount'     => $gstAmt,
                    'total_amount'   => $total,
                    'payment_mode'   => ['Cash', 'UPI', 'NEFT', 'Cheque'][rand(0, 3)],
                ]);

                foreach ($items as $li) {
                    PurchaseItem::create([
                        'purchase_id'  => $purchase->id,
                        'item_name'    => $li['item_name'],
                        'quantity'     => $li['quantity'],
                        'unit'         => $li['unit'],
                        'rate'         => $li['rate'],
                        'tax_amount'   => $gstPct > 0 ? round($li['total_amount'] * $gstPct / 100, 2) : 0,
                        'total_amount' => $li['total_amount'],
                    ]);
                }

                $purchaseCount++;
            }
        }

        $this->command->info("  Created {$purchaseCount} purchase invoices");
    }

    private function seedDailyBills(): void
    {
        $customers = Customer::all();
        $dates     = ['2026-06-29', '2026-06-30', '2026-07-01', '2026-07-02', '2026-07-03', '2026-07-06', '2026-07-07', '2026-07-08'];

        $billCount = 0;

        foreach ($dates as $date) {
            $numBills = rand(1, 3);
            for ($b = 0; $b < $numBills; $b++) {
                $customer   = $customers[rand(0, $customers->count() - 1)];
                $amount     = rand(5000, 35000);
                $gstPct     = 5;
                $gstAmt     = round($amount * $gstPct / 100, 2);
                $netAmount  = $amount + $gstAmt;
                $statuses   = ['COD', 'Pending', 'Bank'];
                $status     = $statuses[rand(0, 2)];
                $payModes   = ['Cash', 'UPI', 'NEFT'];

                DailyBill::create([
                    'customer_id'    => $customer->id,
                    'invoice_no'     => 'DB-' . str_replace('-', '', $date) . '-' . str_pad($b + 1, 2, '0', STR_PAD_LEFT),
                    'date'           => $date,
                    'amount'         => $amount,
                    'gst_percentage' => $gstPct,
                    'gst_amount'     => $gstAmt,
                    'net_amount'     => $netAmount,
                    'payment_mode'   => $payModes[rand(0, 2)],
                    'status'         => $status,
                ]);
                $billCount++;
            }
        }

        $this->command->info("  Created {$billCount} daily bills");
    }

    private function seedWeeklyBills(): void
    {
        $dealers = Dealer::all();
        $periods = [
            ['start' => '2026-06-22', 'end' => '2026-06-28'],
            ['start' => '2026-06-29', 'end' => '2026-07-05'],
            ['start' => '2026-07-06', 'end' => '2026-07-08'],
        ];

        $billCount = 0;

        foreach ($dealers as $dealer) {
            foreach ($periods as $period) {
                if (rand(0, 1) === 0) continue; // not all dealers have bills every week

                $amount    = rand(150000, 450000);
                $gstPct    = 5;
                $gstAmt    = round($amount * $gstPct / 100, 2);
                $netAmount = $amount + $gstAmt;
                $statuses  = ['COD', 'Pending', 'Bank'];

                WeeklyBill::create([
                    'dealer_id'       => $dealer->id,
                    'invoice_no'      => 'WB-' . $dealer->id . '-' . str_replace('-', '', $period['start']),
                    'period_start'    => $period['start'],
                    'period_end'      => $period['end'],
                    'amount'          => $amount,
                    'gst_percentage'  => $gstPct,
                    'gst_amount'      => $gstAmt,
                    'net_amount'      => $netAmount,
                    'payment_mode'    => ['Cash', 'NEFT', 'Cheque'][rand(0, 2)],
                    'status'          => $statuses[rand(0, 2)],
                ]);
                $billCount++;
            }
        }

        $this->command->info("  Created {$billCount} weekly bills");
    }

    private function seedPayments(): void
    {
        $customers = Customer::all();
        $dealers   = Dealer::all();
        $vendors   = Vendor::whereIn('phone', [
            '9876500101', '9876500102', '9876500103',
            '9876500104', '9876500105', '9876500106',
        ])->get();
        if ($vendors->isEmpty()) {
            $vendors = Vendor::orderBy('id')->take(6)->get();
        }

        // Customer payments
        $dates = ['2026-07-01', '2026-07-03', '2026-07-06', '2026-07-07'];
        foreach ($dates as $date) {
            foreach ($customers->random(rand(1, 3)) as $customer) {
                CustomerPayment::create([
                    'customer_id'         => $customer->id,
                    'date'                => $date,
                    'amount'              => rand(8000, 25000),
                    'cod_amount'          => rand(5000, 15000),
                    'bank_transfer_amount' => rand(3000, 10000),
                    'payment_mode'        => ['Cash', 'UPI', 'NEFT'][rand(0, 2)],
                    'payment_type'        => ['Full', 'Part', 'Advance'][rand(0, 2)],
                    'balance_after'       => 0,
                    'notes'               => 'Payment received',
                ]);
            }
        }

        // Dealer payments
        $dealerDates = ['2026-07-02', '2026-07-04', '2026-07-07'];
        foreach ($dealerDates as $date) {
            foreach ($dealers->random(rand(1, 3)) as $dealer) {
                DealerPayment::create([
                    'dealer_id'   => $dealer->id,
                    'date'        => $date,
                    'amount'      => rand(50000, 200000),
                    'payment_mode'=> ['Cash', 'NEFT', 'UPI', 'Cheque'][rand(0, 3)],
                    'notes'       => 'Dealer payment collected',
                ]);
            }
        }

        // Vendor payments
        $vendorDates = ['2026-07-01', '2026-07-05', '2026-07-07'];
        foreach ($vendorDates as $date) {
            foreach ($vendors->random(rand(1, 2)) as $vendor) {
                VendorPayment::create([
                    'vendor_id'     => $vendor->id,
                    'date'          => $date,
                    'amount'        => rand(75000, 300000),
                    'cash_amount'   => rand(30000, 100000),
                    'bank_amount'   => rand(20000, 150000),
                    'payment_mode'  => ['Cash', 'NEFT', 'UPI'][rand(0, 2)],
                    'notes'         => 'Vendor payment made',
                ]);
            }
        }
    }

    private function seedExpensesAndEmis(): void
    {
        // Ensure expense categories exist
        $catNames = ['Rent', 'Electricity', 'Labour', 'Transport', 'Feed', 'Medicine', 'Fuel', 'Salary', 'Misc'];
        $catIds = [];
        foreach ($catNames as $name) {
            $cat = ExpenseCategory::firstOrCreate(
                ['name' => $name],
                ['color' => '#' . substr(md5($name), 0, 6)]
            );
            $catIds[$name] = $cat->id;
        }

        // Expenses across dates
        $dates = ['2026-06-29', '2026-07-01', '2026-07-03', '2026-07-06', '2026-07-07'];
        foreach ($dates as $date) {
            $numExpenses = rand(2, 5);
            for ($i = 0; $i < $numExpenses; $i++) {
                $catName = $catNames[array_rand($catNames)];
                Expense::create([
                    'date'           => $date,
                    'category'       => $catName,
                    'category_id'    => $catIds[$catName],
                    'description'    => $catName . ' expense for ' . $date,
                    'amount'         => rand(1500, 12000),
                    'payment_method' => ['Cash', 'Bank Transfer'][rand(0, 1)],
                ]);
            }
        }

        // EMIs
        $emis = [
            ['emi_type' => 'Bank', 'loan_name' => 'Poultry Shed Loan - HDFC',  'bank_name' => 'HDFC Bank', 'amount' => 12500.00, 'due_date' => '2026-07-15', 'status' => 'Upcoming'],
            ['emi_type' => 'Bank', 'loan_name' => 'Delivery Van EMI - SBI',    'bank_name' => 'SBI',       'amount' => 8400.00,  'due_date' => '2026-07-20', 'status' => 'Upcoming'],
            ['emi_type' => 'Bank', 'loan_name' => 'Generator Loan - Axis Bank', 'bank_name' => 'Axis Bank', 'amount' => 4200.00,  'due_date' => '2026-07-05', 'status' => 'Paid'],
            ['emi_type' => 'Finance Company', 'loan_name' => 'Equipment Finance - Bajaj', 'bank_name' => 'Bajaj Finserv', 'amount' => 6500.00, 'due_date' => '2026-07-25', 'status' => 'Upcoming'],
        ];
        foreach ($emis as $e) {
            Emi::firstOrCreate(
                ['loan_name' => $e['loan_name']],
                $e
            );
        }
    }
}
