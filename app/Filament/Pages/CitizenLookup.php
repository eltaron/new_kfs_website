<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\SyndicateCertificate;

class CitizenLookup extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';
    protected static ?string $navigationLabel = 'البحث عن قرار بالرقم القومي';
    protected static ?string $title = 'البحث عن قرار الإشراف';
    protected static ?string $slug = 'citizen-lookup';
    protected static string $view = 'filament.pages.citizen-lookup';

    public ?string $nationalId = '';
    public ?array $result = null;
    public ?string $searchedId = null;

    public static function canAccess(): bool
    {
        $u = auth()->user();
        return $u && $u->hasAnyRole(['super_admin', 'Admin', 'نقابة المهندسين', 'مجلس المدينة']);
    }

    public function mount(): void
    {
        $this->result = null;
    }

    public function search(): void
    {
        $this->searchedId = $this->nationalId;

        if (strlen($this->nationalId) !== 14) {
            $this->result = ['found' => false, 'error' => 'يجب إدخال 14 رقم'];
            return;
        }

        $certificate = SyndicateCertificate::where('owner_national_id', $this->nationalId)->first();

        if ($certificate) {
            $this->result = [
                'found' => true,
                'type' => $certificate->type_label,
                'execution_sub_type' => $certificate->sub_type_label,
                'engineer_name' => $certificate->engineer_name,
                'engineer_syndicate_number' => $certificate->engineer_syndicate_number,
                'engineer_department' => $certificate->engineer_department,
                'engineer_registration_number' => $certificate->engineer_registration_number,
                'owner_name' => $certificate->owner_name,
                'owner_address' => $certificate->owner_address,
                'license_number' => $certificate->license_number,
                'license_date' => $certificate->license_date?->format('Y-m-d'),
                'license_authority' => $certificate->license_authority,
                'project_address' => $certificate->project_address,
                'city' => $certificate->city,
                'governorate' => $certificate->governorate,
                'licensed_works_description' => $certificate->licensed_works_description,
                'works_value_numbers' => number_format($certificate->works_value_numbers, 2),
                'works_value_words' => $certificate->works_value_words,
                'certificate_file' => $certificate->certificate_file,
            ];
        } else {
            $this->result = ['found' => false];
        }
    }
}
