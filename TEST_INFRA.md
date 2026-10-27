# Flockwise BizTrack Multi-Tenancy SaaS - Test Infrastructure (`TEST_INFRA.md`)

## 1. Overview & Architecture
This test infrastructure provides a comprehensive, deterministic, 4-tier E2E Opaque-Box test suite covering all 18 features (F01–F18) of the Flockwise BizTrack Multi-Tenancy SaaS transformation.

The suite executes in strict isolation using SQLite in-memory (`:memory:`) database migration rollouts, running all 90 existing migrations in ~2.7 seconds per cycle, with total suite execution under 42 seconds for 200 tests.

### 4-Tier Test Pyramid Structure
| Tier | Scope | Total Tests | Target File Location | Status |
|---|---|---|---|---|
| **Tier 1: Feature Coverage** | Baseline requirements for features F01 through F18 (>=5 tests per feature) | 90 | `tests/Tier1/F01_*.php` .. `tests/Tier1/F18_*.php` | **100% Passed (Exit Code 0)** |
| **Tier 2: Boundary & Corner Cases** | Edge cases, empty/null states, negative inputs, fractional paise, extreme rates, large volumes (>=5 tests per feature) | 90 | `tests/Tier2/F01_*.php` .. `tests/Tier2/F18_*.php` | **100% Passed (Exit Code 0)** |
| **Tier 3: Cross-Feature Interactions** | Pairwise and multi-way integrations (Day-load splits + FIFO payments + daily ledger cascading + role switching) | 10 | `tests/Tier3/CrossFeatureCombinationsTest.php` | **100% Passed (Exit Code 0)** |
| **Tier 4: Real-World Multi-Tenant Workloads** | Multi-tenant concurrent operations (Tenant #1 MSR Broilers & Tenant #2 Al-Noor Farm operating simultaneously on same domain with identical invoice numbers, zero IDOR leakage, isolated storage) | 10 | `tests/Tier4/RealWorldMultiTenantWorkloadTest.php` | **100% Passed (Exit Code 0)** |
| **Total Suite** | **Comprehensive E2E Opaque-Box Coverage** | **200** | `tests/` | **100% Passed (Exit Code 0)** |

---

## 2. Test Execution & CLI Runner

### Standard Test Execution Commands
The test suite can be run via PHPUnit or the dedicated deterministic CLI test runner:

```bash
# 1. Run all 4 tiers (200 tests):
php tests/run-all-tests.php

# 2. Run specific tiers:
php tests/run-all-tests.php --tier=1    # Tier 1 Feature Coverage (90 tests)
php tests/run-all-tests.php --tier=2    # Tier 2 Boundary Cases (90 tests)
php tests/run-all-tests.php --tier=3    # Tier 3 Cross-Feature Interactions (10 tests)
php tests/run-all-tests.php --tier=4    # Tier 4 Real-World Multi-Tenancy (10 tests)

# 3. Run with specific filter:
php tests/run-all-tests.php --filter=F16_DayLoadPoultryFormulas
php tests/run-all-tests.php --filter=FifoDealerPayment

# 4. Standard PHPUnit CLI:
vendor/bin/phpunit --testsuite="Tier1,Tier2,Tier3,Tier4"
```

### Deterministic Exit Codes
- **`0`**: All tests passed (or cleanly progressively skipped pending future milestone migrations).
- **`1` / `2`**: Failure or unhandled error detected.

---

## 3. Base Test Harness (`tests/E2ETestCase.php`)

All tests extend `Tests\E2ETestCase`, which inherits from `Tests\TestCase` and Laravel's `Illuminate\Foundation\Testing\TestCase`.

### Progressive Testability Helpers
To ensure that tests compile, run deterministically, and never fail prematurely during milestone-by-milestone development, `E2ETestCase` provides progressive guards:
- `requiresTable(string $tableName)`: Skips test if table does not yet exist in the schema.
- `requiresColumn(string $table, string $column)`: Skips test if column has not yet been added to table.
- `requiresClass(string $className)`: Skips test if service or model class has not yet been created.

*Note: Once migrations are applied in future milestones, these guards automatically become active assertions.*

### Domain Assertion Oracles
`E2ETestCase` includes domain oracles for poultry ERP business logic:
- `assertPoultryFormulas(...)`: Validates `bird_weight = box_weight - empty_weight`, `dealer_income = bird_weight * customer_rate`, `vendor_cost = effective_farm_weight * billing_rate`, and `gross_margin = dealer_income - vendor_cost`.
- `assertBoxConservation(int $remaining, int $split, int $original)`: Asserts exact box conservation invariant (`remaining + split == original`).
- `assertZeroIdor(string $modelClass, int $foreignRecordId, int $tenantId)`: Verifies that querying a foreign tenant's record throws a 404 or returns null under tenant scoping.

### Factory / Fixture Helpers
- `createTenant(array $attributes)`
- `createDealer(array $attributes)`
- `createVendor(array $attributes)`
- `createUser(array $attributes)`
- `createDayLoadBatch(array $attributes)`

---

## 4. Feature Coverage Mapping (F01 – F18)

| Code | Feature Description | Tier 1 File | Tier 2 File |
|---|---|---|---|
| **F01** | Production Hardening & Critical Vulnerability Remediation | `tests/Tier1/F01_SecurityRemediationTest.php` | `tests/Tier2/F01_SecurityRemediationBoundaryTest.php` |
| **F02** | Initial Migration Baseline Consolidation | `tests/Tier1/F02_MigrationBaselineTest.php` | `tests/Tier2/F02_MigrationBaselineTest.php` |
| **F03** | Core Multi-Tenancy Schema Migration (`tenants`, `resellers`) | `tests/Tier1/F03_CoreMultiTenancyTablesTest.php` | `tests/Tier2/F03_CoreMultiTenancyTablesBoundaryTest.php` |
| **F04** | MSR Broilers Baseline Data Migration & Seeding | `tests/Tier1/F04_MsrBroilersBaselineSeedTest.php` | `tests/Tier2/F04_MsrBroilersBaselineSeedBoundaryTest.php` |
| **F05** | Spatie Teams RBAC Schema & Configuration | `tests/Tier1/F05_SpatieTeamsRbacTest.php` | `tests/Tier2/F05_SpatieTeamsRbacBoundaryTest.php` |
| **F06** | Business Tables Tenant Partitioning | `tests/Tier1/F06_BusinessTablesPartitioningTest.php` | `tests/Tier2/F06_BusinessTablesPartitioningBoundaryTest.php` |
| **F07** | Composite Unique Constraints Hardening | `tests/Tier1/F07_CompositeUniqueConstraintsTest.php` | `tests/Tier2/F07_CompositeUniqueConstraintsBoundaryTest.php` |
| **F08** | Reversible Migration Rollback Safety | `tests/Tier1/F08_ReversibleMigrationRollbackTest.php` | `tests/Tier2/F08_ReversibleMigrationRollbackBoundaryTest.php` |
| **F09** | HTTP Request Tenant Identification & Context Resolution | `tests/Tier1/F09_RequestTenantContextTest.php` | `tests/Tier2/F09_RequestTenantContextBoundaryTest.php` |
| **F10** | Eloquent Tenant Global Scope & Trait Implementation | `tests/Tier1/F10_EloquentTenantGlobalScopeTest.php` | `tests/Tier2/F10_EloquentTenantGlobalScopeBoundaryTest.php` |
| **F11** | Gate & Policy Authorization Hardening | `tests/Tier1/F11_GateAuthorizationHardeningTest.php` | `tests/Tier2/F11_GateAuthorizationHardeningBoundaryTest.php` |
| **F12** | Model Lifecycle Events & Notification Multi-Tenant Scoping | `tests/Tier1/F12_ModelLifecycleNotificationScopingTest.php` | `tests/Tier2/F12_ModelLifecycleNotificationScopingBoundaryTest.php` |
| **F13** | Storage & Cache Partitioning by Tenant Context | `tests/Tier1/F13_StorageCachePartitioningTest.php` | `tests/Tier2/F13_StorageCachePartitioningBoundaryTest.php` |
| **F14** | Tenant-Scoped Sequential Invoice Numbering | `tests/Tier1/F14_TenantScopedInvoiceNumberingTest.php` | `tests/Tier2/F14_TenantScopedInvoiceNumberingBoundaryTest.php` |
| **F15** | Daily Cash/Bank Ledger Roll-Forward & Recalculation Cascading | `tests/Tier1/F15_CashBankLedgerCascadingTest.php` | `tests/Tier2/F15_CashBankLedgerCascadingBoundaryTest.php` |
| **F16** | Day-Load Billing & Poultry Formula Mathematical Invariants | `tests/Tier1/F16_DayLoadPoultryFormulasTest.php` | `tests/Tier2/F16_DayLoadPoultryFormulasBoundaryTest.php` |
| **F17** | Box Conservation & Day-Load Split Invariant Enforcement | `tests/Tier1/F17_BoxConservationSplitIntegrityTest.php` | `tests/Tier2/F17_BoxConservationSplitIntegrityBoundaryTest.php` |
| **F18** | 4-Tier FIFO Dealer Payment Settlement Waterfall | `tests/Tier1/F18_FifoDealerPaymentSettlementTest.php` | `tests/Tier2/F18_FifoDealerPaymentSettlementBoundaryTest.php` |

---

## 5. Escalated Implementation Defect Notices
During opaque-box test construction and verification against existing code, the following implementation defects were identified and escalated to peer implementation agents:

1. **`App\Models\Role` Missing `$fillable` for `team_id`**:
   - `App\Models\Role` defines `$fillable = ['name', 'guard_name', 'description', 'is_system', 'created_by']`.
   - Because `team_id` is missing from `$fillable`, `Role::create(['name' => '...', 'team_id' => 1])` silently drops `team_id`, setting it to `NULL`.
   - **Recommended Fix for M1 Worker**: Add `'team_id'` to `$fillable` in `app/Models/Role.php`.

2. **Negative Boxes in `DayLoadBillingService::splitEntry()`**:
   - `DayLoadBillingService::splitEntry` validates `remaining_boxes + split_boxes === original->no_of_boxes`, but does not reject negative box numbers (e.g., $55 + (-5) = 50$).
   - **Recommended Fix for M4 Worker**: Add `if ($splitBoxes <= 0 || $remainingBoxes <= 0) { throw new BoxConservationException(...); }`.
