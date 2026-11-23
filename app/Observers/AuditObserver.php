<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\ProjectSite;
use App\Services\AuditLogService;
use App\Services\Contracts\AuditLogServiceInterface;
use Illuminate\Database\Eloquent\Model;

class AuditObserver
{
    /**
     * Map model classes to application modules.
     *
     * @var array<string, string>
     */
    protected array $moduleMap = [
        'SiteCash' => 'site_cash',
        'DailyAllocation' => 'daily_wages',
        'DailyAllocationItem' => 'daily_wages',
        'DailyAllocationExpense' => 'daily_wages',
        'WeeklyWage' => 'daily_wages',
        'WeeklyWageExpense' => 'daily_wages',
        'PurchaseOrder' => 'purchases',
        'PurchaseOrderItem' => 'purchases',
        'Ledger' => 'ledger',
        'ProjectSite' => 'sites',
        'Client' => 'sites',
        'WarehouseItem' => 'warehouse',
        'WarehouseMovement' => 'warehouse',
        'User' => 'users',
        'Role' => 'users',
        'RoleMenu' => 'users',
        'WorkerCategory' => 'masters',
        'Unit' => 'masters',
        'Material' => 'masters',
        'Supplier' => 'masters',
        'SupplierMaterial' => 'masters',
        'Bank' => 'masters',
        'Branch' => 'masters',
        'Property' => 'masters',
        'PropertyType' => 'masters',
        'TransactionType' => 'masters',
    ];

    /**
     * Handle the Model "created" event.
     */
    public function created(Model $model): void
    {
        if ($model instanceof AuditLog || AuditLogService::$isAuditing) {
            return;
        }

        $baseName = class_basename($model);
        $module = $this->moduleMap[$baseName] ?? 'general';
        $targetId = $this->resolveTargetId($model);
        $siteName = $this->resolveSiteName($model);
        $amount = $this->resolveAmount($model);

        $action = 'CREATE';
        if ($baseName === 'SiteCash') {
            $action = 'CASH_TXN';
        }

        $filteredAttributes = $this->sanitizeAttributes($model->getAttributes());

        $titles = $this->buildCreateDescription($model, $baseName, $targetId, $amount);

        app(AuditLogServiceInterface::class)->log([
            'module' => $module,
            'action' => $action,
            'severity' => 'info',
            'target_type' => get_class($model),
            'target_id' => $targetId,
            'title' => $titles['en'],
            'title_ta' => $titles['ta'],
            'site_name' => $siteName,
            'amount' => $amount,
            'old_values' => null,
            'new_values' => $filteredAttributes,
            'details' => [
                'model' => $baseName,
                'id' => $model->getKey(),
            ],
        ]);
    }

    /**
     * Handle the Model "updated" event.
     */
    public function updated(Model $model): void
    {
        if ($model instanceof AuditLog || AuditLogService::$isAuditing) {
            return;
        }

        $changes = $model->getChanges();
        unset($changes['updated_at'], $changes['remember_token']);

        if (empty($changes)) {
            return;
        }

        $baseName = class_basename($model);
        $module = $this->moduleMap[$baseName] ?? 'general';
        $targetId = $this->resolveTargetId($model);
        $siteName = $this->resolveSiteName($model);
        $amount = $this->resolveAmount($model);

        $oldValues = [];
        $newValues = [];

        foreach ($changes as $key => $newVal) {
            $oldVal = $model->getOriginal($key);
            if ($key === 'password') {
                $oldValues[$key] = '[HIDDEN]';
                $newValues[$key] = '[UPDATED]';
            } else {
                $oldValues[$key] = $oldVal;
                $newValues[$key] = $newVal;
            }
        }

        $action = 'UPDATE';
        $severity = 'info';

        if (isset($changes['status'])) {
            $statusStr = strtolower((string) $changes['status']);
            if (in_array($statusStr, ['approved', 'completed', 'active', 'reconciled'], true)) {
                $action = 'APPROVE';
            } elseif (in_array($statusStr, ['rejected', 'cancelled', 'inactive'], true)) {
                $action = 'REJECT';
                $severity = 'warning';
            }
        }

        // Elevate severity if modifying critical financial, wage rate or access permissions
        if (isset($changes['daily_rate']) || isset($changes['wage']) || isset($changes['role']) || isset($changes['role_id']) || isset($changes['amount'])) {
            $severity = 'critical';
        } elseif ($action === 'UPDATE') {
            $severity = 'warning';
        }

        $titles = $this->buildUpdateDescription($model, $baseName, $targetId, $changes);

        app(AuditLogServiceInterface::class)->log([
            'module' => $module,
            'action' => $action,
            'severity' => $severity,
            'target_type' => get_class($model),
            'target_id' => $targetId,
            'title' => $titles['en'],
            'title_ta' => $titles['ta'],
            'site_name' => $siteName,
            'amount' => $amount,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'details' => [
                'model' => $baseName,
                'changed_fields' => array_keys($changes),
            ],
        ]);
    }

    /**
     * Handle the Model "deleted" event.
     */
    public function deleted(Model $model): void
    {
        if ($model instanceof AuditLog || AuditLogService::$isAuditing) {
            return;
        }

        $baseName = class_basename($model);
        $module = $this->moduleMap[$baseName] ?? 'general';
        $targetId = $this->resolveTargetId($model);
        $siteName = $this->resolveSiteName($model);
        $amount = $this->resolveAmount($model);

        $oldValues = $this->sanitizeAttributes($model->getOriginal());

        $titles = $this->buildDeleteDescription($model, $baseName, $targetId);

        app(AuditLogServiceInterface::class)->log([
            'module' => $module,
            'action' => 'DELETE',
            'severity' => 'critical',
            'target_type' => get_class($model),
            'target_id' => $targetId,
            'title' => $titles['en'],
            'title_ta' => $titles['ta'],
            'site_name' => $siteName,
            'amount' => $amount,
            'old_values' => $oldValues,
            'new_values' => null,
            'details' => [
                'model' => $baseName,
                'deleted_id' => $model->getKey(),
            ],
        ]);
    }

    /**
     * Resolve target ID / code for display.
     */
    protected function resolveTargetId(Model $model): string
    {
        if (! empty($model->voucher_no)) {
            return '#VOUCHER-'.$model->voucher_no;
        }
        if (! empty($model->bill_no)) {
            return '#BILL-'.$model->bill_no;
        }
        if (! empty($model->purchase_order_no)) {
            return '#PO-'.$model->purchase_order_no;
        }
        if (! empty($model->po_number)) {
            return '#PO-'.$model->po_number;
        }
        if (! empty($model->category_code)) {
            return '#CAT-'.$model->category_code;
        }
        if (! empty($model->code)) {
            return '#CODE-'.$model->code;
        }
        if (! empty($model->email)) {
            return '#USER-'.str_pad((string) $model->getKey(), 3, '0', STR_PAD_LEFT);
        }

        $prefix = strtoupper(substr(class_basename($model), 0, 4));

        return "#{$prefix}-".str_pad((string) $model->getKey(), 3, '0', STR_PAD_LEFT);
    }

    /**
     * Resolve site name location.
     */
    protected function resolveSiteName(Model $model): string
    {
        if (! empty($model->site_name)) {
            return (string) $model->site_name;
        }

        if ($model instanceof ProjectSite && ! empty($model->name)) {
            return (string) $model->name;
        }

        if (! empty($model->project_site_id)) {
            $site = ProjectSite::find($model->project_site_id);
            if ($site && ! empty($site->name)) {
                return (string) $site->name;
            }
            if ($site && ! empty($site->site_name)) {
                return (string) $site->site_name;
            }
        }

        if (! empty($model->site_id)) {
            $site = ProjectSite::find($model->site_id);
            if ($site && ! empty($site->name)) {
                return (string) $site->name;
            }
            if ($site && ! empty($site->site_name)) {
                return (string) $site->site_name;
            }
        }

        $base = class_basename($model);
        if (str_contains($base, 'Warehouse')) {
            return 'Central Warehouse';
        }

        return 'Head Office';
    }

    /**
     * Resolve financial transaction amount.
     */
    protected function resolveAmount(Model $model): ?float
    {
        if (isset($model->amount)) {
            return (float) $model->amount;
        }
        if (isset($model->in_amount) && (float) $model->in_amount > 0) {
            return (float) $model->in_amount;
        }
        if (isset($model->out_amount) && (float) $model->out_amount > 0) {
            return (float) $model->out_amount;
        }
        if (isset($model->total_amount)) {
            return (float) $model->total_amount;
        }
        if (isset($model->total_wage)) {
            return (float) $model->total_wage;
        }

        return null;
    }

    /**
     * Sanitize attributes array.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function sanitizeAttributes(array $attributes): array
    {
        unset($attributes['password'], $attributes['remember_token'], $attributes['created_at'], $attributes['updated_at']);

        return $attributes;
    }

    /**
     * Build friendly creation description.
     *
     * @return array{en: string, ta: string}
     */
    protected function buildCreateDescription(Model $model, string $base, string $targetId, ?float $amount): array
    {
        $amountStr = $amount !== null ? ' (₹'.number_format($amount, 2).')' : '';

        return match ($base) {
            'SiteCash' => [
                'en' => "Recorded site cash transaction {$targetId}{$amountStr}",
                'ta' => "தள ரொக்க பரிவர்த்தனை {$targetId} பதிவு செய்யப்பட்டது{$amountStr}",
            ],
            'DailyAllocation' => [
                'en' => "Created daily worker allocation sheet {$targetId}",
                'ta' => "தினசரி தொழிலாளர் வேலை ஒதுக்கீடு தாள் {$targetId} உருவாக்கப்பட்டது",
            ],
            'WeeklyWage' => [
                'en' => "Generated weekly wage settlement {$targetId}{$amountStr}",
                'ta' => "வாராந்திர கூலி வழங்கல் பட்டியல் {$targetId} உருவாக்கப்பட்டது{$amountStr}",
            ],
            'PurchaseOrder' => [
                'en' => "Created purchase order {$targetId}{$amountStr}",
                'ta' => "புதிய கொள்முதல் ஆணை {$targetId} உருவாக்கப்பட்டது{$amountStr}",
            ],
            'Ledger' => [
                'en' => "Posted ledger journal entry {$targetId}{$amountStr}",
                'ta' => "பேரேட்டில் புதிய கணக்கு பதிவு {$targetId} செய்யப்பட்டது{$amountStr}",
            ],
            'ProjectSite' => [
                'en' => 'Created new project site: '.($model->name ?? $model->site_name ?? $targetId),
                'ta' => 'புதிய கட்டுமான தளம் சேர்க்கப்பட்டது',
            ],
            'User' => [
                'en' => 'Created new system user account: '.($model->name ?? $targetId),
                'ta' => 'புதிய பயனர் கணக்கு உருவாக்கப்பட்டது',
            ],
            'Material' => [
                'en' => 'Created material catalog item: '.($model->material_name ?? $targetId),
                'ta' => 'புதிய மூலப்பொருள் அட்டவணையில் சேர்க்கப்பட்டது',
            ],
            'WorkerCategory' => [
                'en' => 'Added worker category master: '.($model->category_name ?? $targetId),
                'ta' => 'புதிய தொழிலாளர் வகை முதன்மை பதிவில் சேர்க்கப்பட்டது',
            ],
            default => [
                'en' => "Created new {$base} record {$targetId}{$amountStr}",
                'ta' => "புதிய {$base} பதிவு {$targetId} உருவாக்கப்பட்டது",
            ],
        };
    }

    /**
     * Build friendly update description.
     *
     * @param  array<string, mixed>  $changes
     * @return array{en: string, ta: string}
     */
    protected function buildUpdateDescription(Model $model, string $base, string $targetId, array $changes): array
    {
        $fieldsList = implode(', ', array_keys($changes));

        return [
            'en' => "Updated {$base} ({$targetId}) [fields: {$fieldsList}]",
            'ta' => "{$base} ({$targetId}) விவரங்கள் மாற்றியமைக்கப்பட்டது [மாற்றங்கள்: {$fieldsList}]",
        ];
    }

    /**
     * Build friendly delete description.
     *
     * @return array{en: string, ta: string}
     */
    protected function buildDeleteDescription(Model $model, string $base, string $targetId): array
    {
        return [
            'en' => "Permanently deleted {$base} record: {$targetId}",
            'ta' => "{$base} பதிவு ({$targetId}) நிரந்தரமாக நீக்கப்பட்டது",
        ];
    }
}
