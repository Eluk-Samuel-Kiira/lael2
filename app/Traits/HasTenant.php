<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait HasTenant
{
    protected static function bootHasTenant()
    {
        static::creating(function ($model) {
            $model->setTenantId();
        });

        static::updating(function ($model) {
            if (empty($model->tenant_id)) {
                $model->setTenantId();
            }
        });
    }

    public function setTenantId()
    {
        if (empty($this->tenant_id)) {
            $tenantId = $this->getTenantId();
            if ($tenantId) {
                $this->tenant_id = $tenantId;
            }
        }
    }

    protected function getTenantId()
    {
        if (Auth::check() && Auth::user()->tenant_id) {
            return Auth::user()->tenant_id;
        }

        if (session()->has('tenant_id')) {
            return session('tenant_id');
        }

        if (request()->has('tenant_id') && request()->input('tenant_id')) {
            return (int) request()->input('tenant_id');
        }

        return null;
    }
}