<?php

namespace App\Filament\Gis\Resources\RemovalOrdersAdminResource\Pages;

use App\Filament\Gis\Resources\RemovalOrdersAdminResource;
use App\Models\GisMarkaz;
use App\Models\RemovalOrder;
use App\Models\Role;
use App\Models\User;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListRemovalOrdersAdmin extends ListRecords
{
    protected static string $resource = RemovalOrdersAdminResource::class;

    protected static string $view = 'filament.gis.pages.removal-orders-lookup';

    public array $q = [
        'text' => '',
        'center' => '',
        'stage' => '',
        'violation_type' => '',
        'specialist' => '',
        'role' => '',
    ];

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()
            ->when(
                !empty($this->q['text']),
                function (Builder $query) {
                    $text = trim($this->q['text']);
                    return $query->where(function (Builder $sub) use ($text) {
                        $sub->where('owner_name', 'like', "%{$text}%")
                            ->orWhere('stop_order_number', 'like', "%{$text}%")
                            ->orWhere('owner_national_id', 'like', "%{$text}%")
                            ->orWhere('center', 'like', "%{$text}%");
                    });
                }
            )
            ->when($this->q['center'] !== '', fn(Builder $query) => $query->where('center', $this->q['center']))
            ->when($this->q['stage'] !== '', fn(Builder $query) => $query->where('stage', $this->q['stage']))
            ->when($this->q['violation_type'] !== '', fn(Builder $query) => $query->where('violation_type', $this->q['violation_type']))
            ->when($this->q['specialist'] !== '', fn(Builder $query) => $query->where('systems_specialist_id', $this->q['specialist']))
            ->when($this->q['role'] !== '', function (Builder $query) {
                $role = $this->q['role'];
                return $query->where(function (Builder $sub) use ($role) {
                    $sub->whereHas('systemsSpecialist.roles', fn($r) => $r->where('name', $role))
                        ->orWhereHas('engineeringEngineer.roles', fn($r) => $r->where('name', $role))
                        ->orWhereHas('spatialMember.roles', fn($r) => $r->where('name', $role))
                        ->orWhereHas('creator.roles', fn($r) => $r->where('name', $role));
                });
            });
    }

    public function getCenters(): array
    {
        return GisMarkaz::cachedOptions();
    }

    public function getStageOptions(): array
    {
        return RemovalOrder::stages();
    }

    public function getSpecialistOptions(): array
    {
        return User::whereHas('roles', fn($q) => $q->where('name', 'like', '%نظم%'))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    public function getRoleOptions(): array
    {
        return Role::orderBy('name')->pluck('name', 'name')->toArray();
    }

    public function getResultsCount(): int
    {
        return $this->getFilteredTableQuery()->count();
    }

    public function clearSearch(): void
    {
        $this->q = [
            'text' => '',
            'center' => '',
            'stage' => '',
            'violation_type' => '',
            'specialist' => '',
            'role' => '',
        ];
        $this->resetTable();
        $this->resetPage();
    }

    public function applySearch(): void
    {
        $this->resetTable();
        $this->resetPage();
    }
}