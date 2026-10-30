<?php

namespace Modules\Investment\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CenterGuide extends Model
{
    use HasFactory;

    protected $fillable = [
        'city', 'main_activity', 'other_activities', 'head', 'contact_person', 'phone',
        'projects_count', 'investment_size', 'permanent_workers', 'seasonal_workers',
        'quality_level', 'opportunity_name', 'utility_status', 'utility_type',
        'utility_percentage', 'activity_type', 'opportunity_location', 'opportunity_desc',
        'coord_x', 'coord_y', 'occupant', 'opportunity_status', 'assumed_workers',
        'total_population', 'males_count', 'females_count', 'growth_rate', 'youth_count',
        'elderly_count', 'children_count', 'illiteracy_rate', 'university_rate',
        'disabled_count', 'internal_migration', 'external_migration', 'migration_reasons',
        'water_stations', 'water_capacity', 'water_coverage', 'water_problems', 'water_service_name',
        'sewage_coverage', 'sewage_plants', 'sewage_deprived', 'sewage_future_needs',
        'electricity_transformers', 'electricity_coverage', 'electricity_outages', 'electricity_needs',
        'gas_coverage', 'gas_served_areas', 'gas_target_areas',
        'light_poles', 'light_lamps', 'light_coverage', 'light_unserved', 'light_needs', 'light_other',
        'roads_total_km', 'roads_regional', 'roads_main', 'roads_internal', 'roads_need_upgrade',
        'main_parking', 'public_transport_lines', 'traffic_problems',
        'primary_schools', 'prep_schools', 'secondary_schools', 'technical_schools',
        'total_students', 'male_students', 'female_students', 'classrooms_count', 'class_density',
        'govt_universities', 'ahli_universities', 'private_universities', 'institutes', 'colleges',
        'university_density', 'education_problems', 'education_needs',
        'hospitals_count', 'health_units', 'family_medicine_centers', 'ambulances_count',
        'doctors_count', 'doctor_citizen_ratio', 'nurses_count', 'nurse_citizen_ratio',
        'specialties_available', 'specialties_lacking', 'health_challenges', 'health_needs',
        'youth_centers', 'sports_clubs', 'legal_stadiums', 'youth_needs',
        'land_plots_count', 'land_total_area', 'land_legalization_status', 'state_buildings',
        'state_warehouses', 'state_markets', 'state_garages', 'support_land_area',
        'current_land_use', 'state_property_challenges', 'state_property_needs',
        'licensed_shops', 'unlicensed_shops', 'popular_activities',
        'commercial_shops_licensed', 'commercial_shops_unlicensed',
        'industrial_workshops_licensed', 'industrial_workshops_unlicensed',
        'service_shops_licensed', 'service_shops_unlicensed',
        'entertainment_shops_licensed', 'entertainment_shops_unlicensed',
        'tourism_shops_licensed', 'tourism_shops_unlicensed',
        'agricultural_activity', 'industrial_activity', 'trade_activity',
        'fish_farms_count', 'fish_farms_area', 'fishing_ports', 'fish_market',
        'vital_project_name', 'vital_project_sector', 'vital_project_cost',
        'vital_project_progress', 'vital_project_executor', 'vital_project_end_date', 'vital_project_impact',
        'tourism_assets', 'beaches', 'lakes', 'heritage_sites', 'nature_reserves',
        'entertainment_venues', 'parks', 'promenades', 'cultural_centers', 'annual_events',
        'parks_count', 'parks_total_area', 'parks_maintenance_status',
        'parks_current_density', 'parks_expected_density', 'parks_challenges', 'parks_needs',
        'service_complexes', 'technology_centers', 'post_offices', 'banks', 'atms_count',
        'police_centers', 'police_departments', 'rescue_points', 'civil_protection',
        'cleanliness_system', 'daily_waste_amount', 'recycling_sites', 'environmental_problems',
        'swot_strengths', 'swot_weaknesses', 'swot_opportunities', 'swot_threats', 'swot_development_priorities',
        'development_priorities',
        'attachments', 'status', 'admin_notes',
    ];

    protected $casts = [
        'attachments' => 'array',
        'vital_project_end_date' => 'date',
        'roads_total_km' => 'decimal:2',
    ];

    public function getCityNameAttribute(): string
    {
        return $this->city ?: '—';
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status) {
            'approved' => 'success',
            'reviewed' => 'info',
            'pending' => 'warning',
            'rejected' => 'danger',
            default => 'secondary',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'approved' => 'معتمد',
            'reviewed' => 'تمت المراجعة',
            'pending' => 'قيد الانتظار',
            'rejected' => 'مرفوض',
            default => 'غير معروف',
        };
    }
}
