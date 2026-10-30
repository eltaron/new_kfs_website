<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('center_guides', function (Blueprint $table) {
            $table->id();

            // Page 1 - Contact
            $table->string('city');
            $table->string('main_activity');
            $table->text('other_activities')->nullable();
            $table->string('head');
            $table->string('contact_person');
            $table->string('phone');

            // Page 2 - Investment
            $table->integer('projects_count')->nullable();
            $table->text('investment_size')->nullable();
            $table->integer('permanent_workers')->nullable();
            $table->integer('seasonal_workers')->nullable();
            $table->text('quality_level')->nullable();
            $table->text('opportunity_name')->nullable();
            $table->text('utility_status')->nullable();
            $table->text('utility_type')->nullable();
            $table->text('utility_percentage')->nullable();
            $table->text('activity_type')->nullable();
            $table->text('opportunity_location')->nullable();
            $table->text('opportunity_desc')->nullable();
            $table->text('coord_x')->nullable();
            $table->text('coord_y')->nullable();
            $table->text('occupant')->nullable();
            $table->text('opportunity_status')->nullable();
            $table->integer('assumed_workers')->nullable();

            // Page 3 - Population
            $table->integer('total_population')->nullable();
            $table->integer('males_count')->nullable();
            $table->integer('females_count')->nullable();
            $table->text('growth_rate')->nullable();
            $table->integer('youth_count')->nullable();
            $table->integer('elderly_count')->nullable();
            $table->integer('children_count')->nullable();
            $table->text('illiteracy_rate')->nullable();
            $table->text('university_rate')->nullable();
            $table->integer('disabled_count')->nullable();
            $table->text('internal_migration')->nullable();
            $table->text('external_migration')->nullable();
            $table->text('migration_reasons')->nullable();

            // Page 4 - Water
            $table->integer('water_stations')->nullable();
            $table->text('water_capacity')->nullable();
            $table->text('water_coverage')->nullable();
            $table->text('water_problems')->nullable();
            $table->text('water_service_name')->nullable();

            // Sewage
            $table->text('sewage_coverage')->nullable();
            $table->integer('sewage_plants')->nullable();
            $table->text('sewage_deprived')->nullable();
            $table->text('sewage_future_needs')->nullable();

            // Electricity
            $table->integer('electricity_transformers')->nullable();
            $table->text('electricity_coverage')->nullable();
            $table->text('electricity_outages')->nullable();
            $table->text('electricity_needs')->nullable();

            // Gas
            $table->text('gas_coverage')->nullable();
            $table->integer('gas_served_areas')->nullable();
            $table->text('gas_target_areas')->nullable();

            // Lighting
            $table->integer('light_poles')->nullable();
            $table->integer('light_lamps')->nullable();
            $table->text('light_coverage')->nullable();
            $table->text('light_unserved')->nullable();
            $table->text('light_needs')->nullable();
            $table->text('light_other')->nullable();

            // Page 5 - Roads
            $table->decimal('roads_total_km', 10, 2)->nullable();
            $table->text('roads_regional')->nullable();
            $table->text('roads_main')->nullable();
            $table->text('roads_internal')->nullable();
            $table->text('roads_need_upgrade')->nullable();
            $table->text('main_parking')->nullable();
            $table->text('public_transport_lines')->nullable();
            $table->text('traffic_problems')->nullable();

            // Page 6 - Education
            $table->integer('primary_schools')->nullable();
            $table->integer('prep_schools')->nullable();
            $table->integer('secondary_schools')->nullable();
            $table->integer('technical_schools')->nullable();
            $table->integer('total_students')->nullable();
            $table->integer('male_students')->nullable();
            $table->integer('female_students')->nullable();
            $table->integer('classrooms_count')->nullable();
            $table->integer('class_density')->nullable();
            $table->integer('govt_universities')->nullable();
            $table->integer('ahli_universities')->nullable();
            $table->integer('private_universities')->nullable();
            $table->text('institutes')->nullable();
            $table->text('colleges')->nullable();
            $table->integer('university_density')->nullable();
            $table->text('education_problems')->nullable();
            $table->text('education_needs')->nullable();

            // Page 7 - Health
            $table->integer('hospitals_count')->nullable();
            $table->integer('health_units')->nullable();
            $table->integer('family_medicine_centers')->nullable();
            $table->integer('ambulances_count')->nullable();
            $table->integer('doctors_count')->nullable();
            $table->text('doctor_citizen_ratio')->nullable();
            $table->integer('nurses_count')->nullable();
            $table->text('nurse_citizen_ratio')->nullable();
            $table->text('specialties_available')->nullable();
            $table->text('specialties_lacking')->nullable();
            $table->text('health_challenges')->nullable();
            $table->text('health_needs')->nullable();

            // Page 8 - Youth & Sports
            $table->integer('youth_centers')->nullable();
            $table->integer('sports_clubs')->nullable();
            $table->text('legal_stadiums')->nullable();
            $table->text('youth_needs')->nullable();

            // Page 9 - State Property
            $table->integer('land_plots_count')->nullable();
            $table->text('land_total_area')->nullable();
            $table->text('land_legalization_status')->nullable();
            $table->text('state_buildings')->nullable();
            $table->text('state_warehouses')->nullable();
            $table->text('state_markets')->nullable();
            $table->text('state_garages')->nullable();
            $table->text('support_land_area')->nullable();
            $table->text('current_land_use')->nullable();
            $table->text('state_property_challenges')->nullable();
            $table->text('state_property_needs')->nullable();

            // Page 10 - Public Shops
            $table->integer('licensed_shops')->nullable();
            $table->integer('unlicensed_shops')->nullable();
            $table->text('popular_activities')->nullable();
            $table->integer('commercial_shops_licensed')->nullable();
            $table->integer('commercial_shops_unlicensed')->nullable();
            $table->integer('industrial_workshops_licensed')->nullable();
            $table->integer('industrial_workshops_unlicensed')->nullable();
            $table->integer('service_shops_licensed')->nullable();
            $table->integer('service_shops_unlicensed')->nullable();
            $table->integer('entertainment_shops_licensed')->nullable();
            $table->integer('entertainment_shops_unlicensed')->nullable();
            $table->integer('tourism_shops_licensed')->nullable();
            $table->integer('tourism_shops_unlicensed')->nullable();

            // Page 11 - Economic Activities
            $table->text('agricultural_activity')->nullable();
            $table->text('industrial_activity')->nullable();
            $table->text('trade_activity')->nullable();
            $table->integer('fish_farms_count')->nullable();
            $table->text('fish_farms_area')->nullable();
            $table->text('fishing_ports')->nullable();
            $table->text('fish_market')->nullable();

            // Page 12 - Vital Projects
            $table->text('vital_project_name')->nullable();
            $table->text('vital_project_sector')->nullable();
            $table->text('vital_project_cost')->nullable();
            $table->text('vital_project_progress')->nullable();
            $table->text('vital_project_executor')->nullable();
            $table->date('vital_project_end_date')->nullable();
            $table->text('vital_project_impact')->nullable();

            // Page 13 - Tourism
            $table->text('tourism_assets')->nullable();
            $table->text('beaches')->nullable();
            $table->text('lakes')->nullable();
            $table->text('heritage_sites')->nullable();
            $table->text('nature_reserves')->nullable();
            $table->text('entertainment_venues')->nullable();
            $table->text('parks')->nullable();
            $table->text('promenades')->nullable();
            $table->text('cultural_centers')->nullable();
            $table->text('annual_events')->nullable();

            // Page 14 - Parks & Beautification
            $table->integer('parks_count')->nullable();
            $table->text('parks_total_area')->nullable();
            $table->text('parks_maintenance_status')->nullable();
            $table->text('parks_current_density')->nullable();
            $table->text('parks_expected_density')->nullable();
            $table->text('parks_challenges')->nullable();
            $table->text('parks_needs')->nullable();

            // Page 15 - Government / Financial / Security
            $table->text('service_complexes')->nullable();
            $table->text('technology_centers')->nullable();
            $table->text('post_offices')->nullable();
            $table->text('banks')->nullable();
            $table->integer('atms_count')->nullable();
            $table->integer('police_centers')->nullable();
            $table->integer('police_departments')->nullable();
            $table->integer('rescue_points')->nullable();
            $table->text('civil_protection')->nullable();

            // Page 16 - Environment
            $table->text('cleanliness_system')->nullable();
            $table->text('daily_waste_amount')->nullable();
            $table->text('recycling_sites')->nullable();
            $table->text('environmental_problems')->nullable();

            // Page 17 - SWOT
            $table->text('swot_strengths')->nullable();
            $table->text('swot_weaknesses')->nullable();
            $table->text('swot_opportunities')->nullable();
            $table->text('swot_threats')->nullable();
            $table->text('swot_development_priorities')->nullable();

            // Page 18 - Development Priorities
            $table->text('development_priorities')->nullable();

            // Page 19 - Attachments (stored as JSON paths)
            $table->json('attachments')->nullable();

            // Status
            $table->enum('status', ['pending', 'reviewed', 'approved', 'rejected'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('center_guides');
    }
};
