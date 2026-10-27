<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Dealer;
use App\Models\Vendor;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Emi;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\DailyBill;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        // ── Vendors ─────────────────────────────────────────────────────────────
        $vendors = [
            ['firm_name' => 'Annai Feeds Pvt Ltd',    'phone' => '9876543210', 'location' => 'Coimbatore', 'contact_person' => 'Rajan',   'route' => 'North'],
            ['firm_name' => 'Sri Murugan Agencies',    'phone' => '9876543211', 'location' => 'Salem',      'contact_person' => 'Selvam',   'route' => 'East'],
            ['firm_name' => 'Kaveri Poultry Supplies', 'phone' => '9876543212', 'location' => 'Erode',      'contact_person' => 'Priya',    'route' => 'West'],
        ];

        foreach ($vendors as $v) {
            Vendor::firstOrCreate(['firm_name' => $v['firm_name']], $v);
        }

        // ── Customers ────────────────────────────────────────────────────────────
        $customers = [
            ['name' => 'Ravi Kumar',    'phone' => '9000000001', 'address' => 'Chennai',    'route' => 'North', 'type' => 'Wholesale', 'balance' => 5000],
            ['name' => 'Meena Stores',  'phone' => '9000000002', 'address' => 'Madurai',    'route' => 'South', 'type' => 'Retail',    'balance' => 1500],
            ['name' => 'Karthik Deals', 'phone' => '9000000003', 'address' => 'Trichy',     'route' => 'East',  'type' => 'Wholesale', 'balance' => 3200],
            ['name' => 'Lakshmi Mart',  'phone' => '9000000004', 'address' => 'Coimbatore', 'route' => 'West',  'type' => 'Retail',    'balance' => 800],
        ];

        $customerIds = [];
        foreach ($customers as $c) {
            $customer = Customer::firstOrCreate(['phone' => $c['phone']], $c);
            $customerIds[] = $customer->id;
        }

        // ── Purchases ────────────────────────────────────────────────────────────
        $purchaseData = [
            ['vendor_name' => 'Annai Feeds Pvt Ltd',    'items' => [['name' => 'Starter Feed', 'qty' => 100, 'unit' => 'Bags', 'rate' => 1800]],               'gst_pct' => 5,  'mode' => 'NEFT'],
            ['vendor_name' => 'Sri Murugan Agencies',    'items' => [['name' => 'Grower Feed',  'qty' => 50,  'unit' => 'Bags', 'rate' => 1650]],               'gst_pct' => 5,  'mode' => 'UPI'],
            ['vendor_name' => 'Kaveri Poultry Supplies', 'items' => [['name' => 'Vaccines',     'qty' => 200, 'unit' => 'Vials','rate' => 45]],                 'gst_pct' => 12, 'mode' => 'Cash'],
            ['vendor_name' => 'Annai Feeds Pvt Ltd',    'items' => [['name' => 'Finisher Feed','qty' => 80,  'unit' => 'Bags', 'rate' => 1700]],               'gst_pct' => 5,  'mode' => 'Cheque'],
            ['vendor_name' => 'Sri Murugan Agencies',    'items' => [['name' => 'Poultry Vitamins','qty' => 100,'unit' => 'Btls', 'rate' => 120]],              'gst_pct' => 12, 'mode' => 'NEFT'],
        ];

        foreach ($purchaseData as $p) {
            $subTotal = collect($p['items'])->sum(fn($i) => $i['qty'] * $i['rate']);
            $gstAmt  = round($subTotal * $p['gst_pct'] / 100, 2);
            $total   = $subTotal + $gstAmt;

            $vendor = Vendor::where('firm_name', $p['vendor_name'])->first();

            $purchase = Purchase::create([
                'vendor_id'      => $vendor?->id,
                'vendor_name'    => $p['vendor_name'],
                'invoice_no'     => 'SAMP-INV-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT),
                'date'           => now()->subDays(rand(1, 30))->toDateString(),
                'gst_percentage'  => $p['gst_pct'],
                'gst_amount'     => $gstAmt,
                'total_amount'   => $total,
                'payment_mode'   => $p['mode'],
            ]);

            foreach ($p['items'] as $li) {
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'item_name'   => $li['name'],
                    'quantity'    => $li['qty'],
                    'unit'        => $li['unit'],
                    'rate'        => $li['rate'],
                    'tax_amount'  => round($li['qty'] * $li['rate'] * $p['gst_pct'] / 100, 2),
                    'total_amount'=> $li['qty'] * $li['rate'],
                ]);
            }
        }

        // ── Expenses ─────────────────────────────────────────────────────────────
        $catNames = ['Fuel', 'Salary', 'Transport', 'Utility', 'Misc'];
        $catIds = [];
        foreach ($catNames as $name) {
            $cat = ExpenseCategory::firstOrCreate(['name' => $name], ['color' => '#' . substr(md5($name), 0, 6)]);
            $catIds[$name] = $cat->id;
        }
        for ($i = 0; $i < 10; $i++) {
            $catName = $catNames[array_rand($catNames)];
            Expense::create([
                'date'         => now()->subDays(rand(1, 30)),
                'category'     => $catName,
                'category_id'  => $catIds[$catName],
                'description'  => 'Operational expense #' . ($i + 1),
                'amount'       => rand(500, 5000),
                'payment_method' => ['Cash', 'Bank Transfer'][rand(0, 1)],
            ]);
        }

        // ── EMIs ─────────────────────────────────────────────────────────────────
        $emiItems = [
            ['loan_name' => 'Poultry Shed Loan - HDFC', 'bank_name' => 'HDFC Bank', 'amount' => 12500.00, 'due_date' => now()->addDays(5),  'status' => 'Upcoming', 'type' => 'Bank'],
            ['loan_name' => 'Delivery Van EMI - SBI',   'bank_name' => 'SBI',       'amount' => 8400.00,  'due_date' => now()->addDays(12), 'status' => 'Upcoming', 'type' => 'Bank'],
            ['loan_name' => 'Generator Loan - Axis',    'bank_name' => 'Axis Bank', 'amount' => 4200.00,  'due_date' => now()->subDays(5),  'status' => 'Paid',     'type' => 'Bank'],
        ];

        foreach ($emiItems as $e) {
            Emi::firstOrCreate(['loan_name' => $e['loan_name']], [
                'emi_type'  => $e['type'],
                'loan_name' => $e['loan_name'],
                'bank_name' => $e['bank_name'],
                'amount'    => $e['amount'],
                'due_date'  => $e['due_date'],
                'status'    => $e['status'],
            ]);
        }

        // ── Daily Bills ──────────────────────────────────────────────────────────
        if (!empty($customerIds)) {
            for ($i = 0; $i < 8; $i++) {
                $amount    = rand(5000, 25000);
                $gstPct    = 5;
                $gstAmt    = round($amount * $gstPct / 100, 2);
                $netAmount = $amount + $gstAmt;
                $status    = ['COD', 'Pending', 'Bank'][rand(0, 2)];

                DailyBill::create([
                    'customer_id' => $customerIds[array_rand($customerIds)],
                    'invoice_no'  => 'DB-SAMP-' . time() . '-' . $i,
                    'date'        => now()->subDays(rand(1, 15))->toDateString(),
                    'amount'      => $amount,
                    'gst_percentage' => $gstPct,
                    'gst_amount'  => $gstAmt,
                    'net_amount'  => $netAmount,
                    'payment_mode'=> ['Cash', 'UPI', 'NEFT'][rand(0, 2)],
                    'status'      => $status,
                ]);
            }
        }

        $this->command->info('✅ Sample data seeded: vendors, customers, purchases, expenses, EMIs, daily bills.');
    }
}
