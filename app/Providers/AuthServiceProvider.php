<?php

namespace App\Providers;

use App\Models\PatchNote;
use App\Policies\PatchNotePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        PatchNote::class => PatchNotePolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
