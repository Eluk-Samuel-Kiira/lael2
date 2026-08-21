<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class TenantObserver
{
    /**
     * Handle the model "creating" event.
     */
    public function creating(Model $model): void
    {
        $this->setTenantId($model);
    }

    /**
     * Handle the model "updating" event.
     */
    public function updating(Model $model): void
    {
        $this->setTenantId($model);
    }

    /**
     * Handle the model "saving" event.
     */
    public function saving(Model $model): void
    {
        $this->setTenantId($model);
    }

    /**
     * Set tenant_id on the model if it has the column and it's null
     */
    protected function setTenantId(Model $model): void
    {
        // Check if model has tenant_id column
        if (!$this->hasTenantColumn($model)) {
            return;
        }

        // Only set if tenant_id is null or empty
        if (!empty($model->tenant_id)) {
            return;
        }

        $tenantId = $this->getTenantId();
        
        if ($tenantId) {
            $model->tenant_id = $tenantId;
        }
    }

    /**
     * Check if model has tenant_id column
     */
    protected function hasTenantColumn(Model $model): bool
    {
        try {
            return Schema::hasColumn($model->getTable(), 'tenant_id');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get tenant ID from context
     */
    protected function getTenantId(): ?int
    {
        // From authenticated user
        if (Auth::check() && Auth::user()->tenant_id) {
            return Auth::user()->tenant_id;
        }

        // From session
        if (session()->has('tenant_id')) {
            return session('tenant_id');
        }

        // From request
        if (request()->has('tenant_id') && request()->input('tenant_id')) {
            return (int) request()->input('tenant_id');
        }

        return null;
    }
}