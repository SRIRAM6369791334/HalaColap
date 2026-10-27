<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\Dealer;
use App\Models\Vendor;
use App\Models\Route;
use App\Models\DailyBill;
use App\Models\WeeklyBill;
use App\Models\CustomerPayment;
use App\Models\DealerPayment;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\VendorPayment;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Emi;
use Carbon\Carbon;

class ProfitTestSeeder extends Seeder
{
    public function run(): void
    {
        $today = Carbon::now()->format("Y-m-d");
        $route = Route::firstOrCreate(["route_name" => "Test Route"]);

        $customer = Customer::firstOrCreate(
            ["phone" => "9000000001"],
            ["name" => "Test Customer Profit", "address" => "Chennai", "type" => "Retail", "route_id" => $route->id]
        );

        $dealer = Dealer::firstOrCreate(
            ["phone" => "9000000002"],
            ["firm_name" => "Test Dealer Profit", "contact_person" => "Ravi", "route" => "Test Route"]
        );

        $vendor = Vendor::firstOrCreate(
            ["phone" => "9000000003"],
            ["firm_name" => "Test Vendor Profit", "contact_person" => "Kumar", "location" => "Chennai"]
        );

        // ── INFLOW Rs 70,00,000 ─────────────────────────
        // DailyBill (Customer) = Rs 20,00,000  (4 x Rs 5L)
        for ($i = 0; $i < 4; $i++) {
            DailyBill::create([
                "customer_id"    => $customer->id,
                "invoice_no"     => "TESTDB" . $i . time(),
                "date"           => Carbon::now()->subDays($i)->format("Y-m-d"),
                "amount"         => 500000.00,
                "gst_percentage" => 0,
                "gst_amount"     => 0,
                "net_amount"     => 500000.00,
                "payment_mode"   => "Cash",
                "status"         => "COD",
            ]);
        }

        // WeeklyBill (Dealer) = Rs 15,00,000  (3 x Rs 5L)
        for ($i = 0; $i < 3; $i++) {
            WeeklyBill::create([
                "dealer_id"    => $dealer->id,
                "invoice_no"   => "TESTWB" . $i . time(),
                "period_start" => Carbon::now()->startOfWeek()->format("Y-m-d"),
                "period_end"   => Carbon::now()->format("Y-m-d"),
                "net_amount"   => 500000.00,
                "amount"       => 500000.00,
                "gst_percentage" => 0,
                "gst_amount"   => 0,
                "payment_mode" => "Cash",
                "status"       => "COD",
            ]);
        }

        // CustomerPayment = Rs 20,00,000  (4 x Rs 5L)
        for ($i = 0; $i < 4; $i++) {
            CustomerPayment::create([
                "customer_id"  => $customer->id,
                "amount"       => 500000.00,
                "cod_amount"   => 500000.00,
                "bank_transfer_amount" => 0,
                "date"         => Carbon::now()->subDays($i)->format("Y-m-d"),
                "payment_mode" => "Cash",
                "payment_type" => "Full",
                "balance_after"=> 0,
                "notes"        => "Test customer payment " . ($i + 1),
            ]);
        }

        // DealerPayment = Rs 15,00,000  (3 x Rs 5L) -- Dealer pays US = INFLOW
        for ($i = 0; $i < 3; $i++) {
            DealerPayment::create([
                "dealer_id"          => $dealer->id,
                "amount"             => 500000.00,
                "pending_balance_after" => 0,
                "date"               => Carbon::now()->subDays($i)->format("Y-m-d"),
                "payment_mode"       => "Cash",
                "notes"              => "Test dealer payment " . ($i + 1),
            ]);
        }

        // ── OUTFLOW Rs 60,00,000 ─────────────────────────
        // Purchase (Vendor) = Rs 40,00,000  (8 x Rs 5L)
        for ($i = 0; $i < 8; $i++) {
            $purchase = Purchase::create([
                "vendor_id"      => $vendor->id,
                "vendor_name"    => "Test Vendor Profit",
                "invoice_no"     => "TESTPUR" . $i . time(),
                "date"           => Carbon::now()->subDays($i)->format("Y-m-d"),
                "gst_percentage" => 0,
                "gst_amount"     => 0,
                "total_amount"   => 500000.00,
                "payment_mode"   => "Cash",
            ]);

            PurchaseItem::create([
                "purchase_id"  => $purchase->id,
                "item_name"    => "Broiler Chicken",
                "quantity"     => 1000,
                "unit"         => "Kg",
                "rate"         => 500,
                "tax_amount"   => 0,
                "total_amount" => 500000.00,
            ]);
        }

        // VendorPayment = Rs 10,00,000  (2 x Rs 5L)
        for ($i = 0; $i < 2; $i++) {
            VendorPayment::create([
                "vendor_id"    => $vendor->id,
                "amount"       => 500000.00,
                "cash_amount"  => 500000.00,
                "bank_amount"  => 0,
                "pending_balance_after" => 0,
                "date"         => Carbon::now()->subDays($i)->format("Y-m-d"),
                "payment_mode" => "Cash",
                "notes"        => "Test vendor payment " . ($i + 1),
            ]);
        }

        // Expense = Rs 7,00,000  (7 x Rs 1L)
        $cats = ["Rent","Electricity","Labour","Transport","Feed","Medicine","Misc"];
        foreach ($cats as $cat) {
            $catModel = ExpenseCategory::firstOrCreate(
                ["name" => $cat],
                ["color" => "#" . substr(md5($cat), 0, 6)]
            );
            Expense::create([
                "date"          => $today,
                "category"      => $cat,
                "category_id"   => $catModel->id,
                "description"   => "Test " . $cat,
                "amount"        => 100000.00,
                "payment_method"=> "Cash",
            ]);
        }

        // EMI = Rs 3,00,000  (3 x Rs 1L)
        for ($i = 0; $i < 3; $i++) {
            Emi::create([
                "emi_type"  => "Bank",
                "loan_name" => "Test EMI " . ($i + 1),
                "bank_name" => "Test Bank",
                "amount"    => 100000.00,
                "due_date"  => Carbon::now()->subDays($i)->format("Y-m-d"),
                "status"    => "Paid",
            ]);
        }

        $this->command->info("Seeding done. Expected profit = Rs 10,00,000");
    }
}
