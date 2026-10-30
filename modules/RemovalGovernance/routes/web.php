<?php

// Removal governance routes (moved verbatim from routes/web.php — URIs, names and middleware unchanged).

use Illuminate\Support\Facades\Route;
use Modules\RemovalGovernance\Models\RemovalOrder;

Route::get('/gis/removal-orders/{record}', function (RemovalOrder $record) {
    $record->load(['creator', 'engineeringEngineer', 'spatialManager', 'spatialMember', 'systemsSpecialist']);
    return view('gis.removal-order-detail', compact('record'));
})->name('gis.removal.detail')->middleware(['auth', 'can:view-removal-order,record']);

Route::get('/gis/removal-print/{record}', function (RemovalOrder $record) {
    return view('gis.print-removal-decision', compact('record'));
})->name('gis.removal.print')->middleware(['auth', 'can:view-removal-order,record']);

Route::get('/gis/removal-geojson/{record}', function (RemovalOrder $record) {
    $spatial = $record->spatial_data;
    $geojsonStr = $spatial['polygon_geojson'] ?? null;
    if (!$geojsonStr) {
        abort(404, 'No spatial data available');
    }
    $geojson = json_decode($geojsonStr, true);
    if (!$geojson) {
        abort(404, 'Invalid GeoJSON data');
    }
    return response()->json($geojson, 200, [
        'Content-Type' => 'application/geo+json',
        'Content-Disposition' => 'attachment; filename="removal-' . $record->id . '.geojson"',
    ]);
})->name('gis.removal.geojson')->middleware(['auth', 'can:view-removal-order,record']);

Route::get('/gis/removal-shapefile/{record}', function (RemovalOrder $record) {
    $spatial = $record->spatial_data;
    $geojsonStr = $spatial['polygon_geojson'] ?? null;
    if (!$geojsonStr) {
        abort(404, 'No spatial data available');
    }
    $geojson = json_decode($geojsonStr, true);
    if (!$geojson) {
        abort(404, 'Invalid GeoJSON data');
    }

    // Extract coordinates from FeatureCollection, Feature, or raw geometry
    $geometry = $geojson;
    if (isset($geojson['type']) && $geojson['type'] === 'FeatureCollection' && !empty($geojson['features'][0])) {
        $geometry = $geojson['features'][0]['geometry'] ?? $geojson['features'][0];
    }
    if (isset($geometry['type']) && $geometry['type'] === 'Feature') {
        $geometry = $geometry['geometry'] ?? $geometry;
    }
    $coords = $geometry['coordinates'][0] ?? [];
    if (empty($coords)) {
        abort(404, 'No polygon coordinates');
    }

    // Build Polygon geometry
    $ring = new \Shapefile\Geometry\Linestring();
    foreach ($coords as $c) {
        $ring->addPoint(new \Shapefile\Geometry\Point($c[0], $c[1]));
    }
    $polygon = new \Shapefile\Geometry\Polygon();
    $polygon->addRing($ring);

    // Write Shapefile
    $tempDir = sys_get_temp_dir() . '/shp_' . uniqid();
    mkdir($tempDir, 0777, true);
    $writer = new \Shapefile\ShapefileWriter($tempDir . '/export', [
        \Shapefile\Shapefile::OPTION_BUFFERED_RECORDS => 1,
    ]);
    $writer->setShapeType(\Shapefile\Shapefile::SHAPE_TYPE_POLYGON);
    $writer->addCharField('record_id', 36);
    $writer->addCharField('owner', 100);
    $writer->addCharField('center', 50);
    $writer->writeRecord($polygon, [
        'record_id' => $record->id,
        'owner'     => $record->owner_name,
        'center'    => $record->center,
    ]);
    $writer->save();

    // Zip all output files
    $zipFile = tempnam(sys_get_temp_dir(), 'shp_') . '.zip';
    $zip = new \ZipArchive();
    $zip->open($zipFile, \ZipArchive::CREATE);
    foreach (glob($tempDir . '/export.*') as $f) {
        $zip->addFile($f, basename($f));
    }
    $zip->close();

    // Cleanup
    array_map('unlink', glob($tempDir . '/export.*'));
    rmdir($tempDir);

    return response()->download($zipFile, 'removal-' . $record->id . '.zip')->deleteFileAfterSend(true);
})->name('gis.removal.shapefile')->middleware(['auth', 'can:view-removal-order,record']);
