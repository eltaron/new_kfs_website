<?php

namespace Modules\Content\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Content\Models\DirectoryEntry;

class DirectoryController extends Controller
{
    public function index()
    {
        // Fetch all entries, group them by category, then sort groups
        $groupedEntries = DirectoryEntry::orderBy('order')->get()->groupBy('category');
        return view('directory.index', ['groupedEntries' => $groupedEntries]);
    }
}
