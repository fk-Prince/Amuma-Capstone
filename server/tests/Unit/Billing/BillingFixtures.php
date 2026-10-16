<?php

namespace Tests\Unit\Billing;

use App\Enums\ModuleEnum;
use App\Models\Client;
use App\Models\Employee;
use App\Models\EmployeeBranch;
use App\Models\EmployeePermission;
use App\Models\Invoice;
use App\Models\InvoiceAdjustment;
use App\Models\Module;
use App\Models\Patient;
use App\Models\PatientAccess;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait BillingFixtures
{
    protected int $branchId;
    protected string $branchUuid;
    protected int $moduleId;
    protected int $admissionId;
    protected Patient $patient;
    protected User $cashier;
    protected User $guardian;
    protected Client $client;
    private int $userCount = 0;

    protected function setUpBilling(): void
    {
        Invoice::forgetPatientInvoiceIds();

        $agencyId = DB::table('agencies')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'name' => 'AMUMA Incorporation',
            'email' => 'info@amuma.com',
            'registered_by' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->branchUuid = (string) Str::uuid();

        $this->branchId = DB::table('branches')->insertGetId([
            'uuid' => $this->branchUuid,
            'agency_id' => $agencyId,
            'name' => 'AMUMA Davao City',
            'email' => 'davao@amuma.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->moduleId = Module::create(['module_name' => ModuleEnum::BillingAndInvoices->value])->module_id;

        $patientId = DB::table('patients')->insertGetId([
            'branch_id' => $this->branchId,
            'uuid' => (string) Str::uuid(),
            'patient_code' => 'PT-0001',
            'first_name' => 'Lola',
            'last_name' => 'Cruz',
            'gender' => 'female',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->patient = Patient::findOrFail($patientId);

        $this->admissionId = DB::table('patient_admissions')->insertGetId([
            'patient_id' => $patientId,
            'bed_id' => 1,
            'status' => 'admitted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->cashier = $this->staff(ModuleEnum::BillingAndInvoices->actionColumns());
        $this->guardian = $this->makeUser();

        $this->client = Client::create([
            'user_id' => $this->guardian->user_id,
            'first_name' => 'Gina',
            'last_name' => 'Cruz',
        ]);

        PatientAccess::create([
            'client_id' => $this->client->client_id,
            'patient_id' => $patientId,
            'have_access' => true,
            'relationship_type' => 'relative',
        ]);
    }

    protected function makeUser(): User
    {
        $this->userCount++;

        return User::create([
            'uuid' => (string) Str::uuid(),
            'email' => "user{$this->userCount}@amuma.com",
            'password' => 'password',
        ]);
    }

    protected function staff(array $actions, string $role = 'cashier'): User
    {
        $user = $this->makeUser();

        $employee = Employee::create([
            'user_id' => $user->user_id,
            'first_name' => ucfirst($role),
            'last_name' => 'Staff',
        ]);

        EmployeeBranch::create([
            'branch_id' => $this->branchId,
            'employee_id' => $employee->employee_id,
            'role_name' => $role,
            'status' => 'active',
        ]);

        EmployeePermission::create([
            'employee_id' => $employee->employee_id,
            'branch_id' => $this->branchId,
            'module_id' => $this->moduleId,
        ] + EmployeePermission::grantColumns($actions));

        return $user;
    }

    protected function invoice(float $amount, string $description = 'Room fee'): Invoice
    {
        $invoice = Invoice::create([
            'branch_id' => $this->branchId,
            'total_amount' => $amount,
            'status' => Invoice::STATUS_PENDING,
        ]);

        DB::table('additional_charges')->insert([
            'patient_id' => $this->patient->patient_id,
            'invoice_id' => $invoice->invoice_id,
            'type' => 'additional_charges',
            'description' => $description,
            'amount' => $amount,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Invoice::forgetPatientInvoiceIds();

        return $invoice;
    }

    protected function settle(Invoice $invoice, ?float $amount = null): Payment
    {
        $amount ??= (float) $invoice->total_amount;

        $transaction = Transaction::create([
            'branch_id' => $this->branchId,
            'patient_id' => $this->patient->patient_id,
            'amount' => $amount,
            'type' => Transaction::TYPE_PAYMENT,
            'direction' => Transaction::DIRECTION_CREDIT,
            'status' => Transaction::STATUS_COMPLETED,
            'method' => 'CASH',
        ]);

        $payment = Payment::create([
            'transaction_id' => $transaction->transaction_id,
            'prior_balance' => $amount,
            'new_balance' => 0,
            'cash_tendered' => $amount,
            'created_at' => now(),
        ]);

        $payment->allocations()->create([
            'invoice_id' => $invoice->invoice_id,
            'amount' => $amount,
            'created_at' => now(),
        ]);

        $invoice->refresh()->syncStatus();

        return $payment;
    }

    protected function creditOnAccount(float $amount): void
    {
        $invoice = $this->invoice(1000, 'Credit source');

        $this->settle($invoice);

        InvoiceAdjustment::create([
            'invoice_id' => $invoice->invoice_id,
            'type' => InvoiceAdjustment::TYPE_CORRECTION,
            'amount' => -$amount,
            'reason' => 'Rate lowered',
        ]);
    }

    protected function makeRequest(string $method, array $data, ?User $user = null): Request
    {
        $user ??= $this->cashier;

        return Request::create('/api/billing', $method, $data)->setUserResolver(fn() => $user);
    }

    protected function assertFailsWith(string $message, callable $action, ?int $code = null): void
    {
        try {
            $action();
        } catch (Exception $e) {
            $this->assertSame($message, $e->getMessage());

            if ($code !== null) {
                $this->assertSame($code, $e->getCode());
            }

            return;
        }

        $this->fail("Expected the error \"{$message}\".");
    }
}
