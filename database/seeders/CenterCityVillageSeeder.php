<?php

namespace Database\Seeders;

use Modules\Content\Models\Center;
use Modules\Tourism\Models\CityVillage;
use Modules\Gis\Models\GisMarkaz;
use Modules\Gis\Models\GisShiakha;
use Modules\Gis\Models\GisVillage;
use Modules\Content\Models\Markaz;
use Modules\Content\Models\Shiakha;
use Modules\Content\Models\Village;
use Database\Seeders\Support\DemoData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * التقسيم الإداري للمحافظة (13 مركزاً) في كل الجداول التي تعتمد عليه:
 *  - gis_markazs → gis_shiakhas → gis_villages   (المنظومة المكانية)
 *  - markazs / shiakhas / villages                 (طبقات الخرائط)
 *  - centers → city_villages                       (دليل المراكز والقرى)
 *
 * غير هدّام: لا يحذف ولا يفرغ أي جدول (كانت النسخة السابقة تعمل truncate) — كل شيء updateOrCreate.
 */
class CenterCityVillageSeeder extends Seeder
{
    public function run(): void
    {
        $markazNo = 0;
        $shiakhaNo = 0;

        foreach (DemoData::VILLAGES as $center => $villages) {
            $markazNo++;
            $code = sprintf('KFS-%02d', $markazNo);
            $slug = DemoData::CENTER_SLUGS[$center] ?? 'center-' . $markazNo;

            // ── GIS ──
            $gm = GisMarkaz::where('name', $center)->first()
                ?? GisMarkaz::firstOrNew(['gis_code' => $code]);
            $gm->name = $center;
            $gm->gis_code ??= $code;
            $gm->save();

            // شياختان لكل مركز: البندر (المدينة) والريف
            $shiakhas = [
                ['بندر ' . $center, array_slice($villages, 0, 2)],
                ['ريف ' . $center, array_slice($villages, 2)],
            ];
            foreach ($shiakhas as $s => [$sName, $sVillages]) {
                $shiakhaNo++;
                $sCode = sprintf('%s-%02d', $code, $s + 1);
                $gs = GisShiakha::updateOrCreate(['gis_markaz_id' => $gm->id, 'name' => $sName], ['shiakha_code' => $sCode]);
                foreach ($sVillages as $v => $vName) {
                    GisVillage::updateOrCreate(['gis_shiakha_id' => $gs->id, 'name' => $vName], ['is_ezba' => $v === 3]);
                    GisVillage::updateOrCreate(['gis_shiakha_id' => $gs->id, 'name' => 'عزبة ' . $vName], ['is_ezba' => true]);
                }

                // ── طبقات الخرائط (مفاتيح غير تلقائية) ──
                Shiakha::updateOrCreate(['id' => $shiakhaNo], [
                    'shiakha_num' => $shiakhaNo,
                    'name' => $sName,
                    'shiakha_c_code' => $sCode,
                    'shiakha_g_code' => $sCode,
                    'municipality_name' => $center,
                    'municipality_code' => $code,
                    'markaz_name' => $center,
                    'markaz_code' => $code,
                    'gov_code' => '15',
                    'gov_name' => 'كفر الشيخ',
                    'global_id' => sprintf('{DEMO-SH-%04d}', $shiakhaNo),
                    'st_area' => 1000000 + $shiakhaNo * 12345.5,
                    'st_length' => 5000 + $shiakhaNo * 33.3,
                ]);
                foreach ($sVillages as $vName) {
                    Village::updateOrCreate(['name' => $vName, 'shiakha_code' => $sCode], []);
                }
            }

            Markaz::updateOrCreate(['id' => $markazNo], [
                'name' => $center,
                'g_code' => $code,
                'gov_name' => 'كفر الشيخ',
                'global_id' => sprintf('{DEMO-MK-%02d}', $markazNo),
                'st_area' => 250000000 + $markazNo * 1000000,
                'st_length' => 90000 + $markazNo * 1000,
            ]);
            DB::table('markazs')->where('id', $markazNo)->update([
                'cities_num' => 1, 'mun_num' => 1, 'shiakha_num' => 2, 'villages_num' => count($villages), 'ezab_num' => count($villages),
            ]);

            // ── دليل المراكز ──
            $c = Center::updateOrCreate(['slug' => $slug], ['name' => $center, 'is_active' => true, 'sort_order' => $markazNo]);
            CityVillage::updateOrCreate(['center_id' => $c->id, 'name' => 'مدينة ' . $center], ['type' => 'city', 'is_active' => true, 'sort_order' => 0]);
            foreach ($villages as $v => $vName) {
                CityVillage::updateOrCreate(['center_id' => $c->id, 'name' => $vName], ['type' => 'village', 'is_active' => true, 'sort_order' => $v + 1]);
            }
        }

        if (method_exists(GisMarkaz::class, 'clearCache')) {
            GisMarkaz::clearCache();
        }

        $this->command?->info("Seeded {$markazNo} centers / {$shiakhaNo} shiakhas with villages (GIS + layers + directory).");
    }
}
