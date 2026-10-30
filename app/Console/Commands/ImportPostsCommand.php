<?php

namespace App\Console\Commands;

use Modules\Content\Models\Category;
use Modules\Content\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // For logging errors
use Illuminate\Support\Str;

class ImportPostsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:posts {filepath?}'; // We can optionally pass the file path

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import posts from a CSV file into the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Define the default file path
        $defaultPath = storage_path('app/imports/posts.csv');
        $filePath = $this->argument('filepath') ?? $defaultPath;

        // Check if the file exists
        if (!file_exists($filePath)) {
            $this->error("Import file not found at path: {$filePath}");
            return 1; // Return an error code
        }

        $this->info("Starting post import from: {$filePath}");

        // Use a progress bar for better user experience
        $file = new \SplFileObject($filePath, 'r');
        $file->seek(PHP_INT_MAX);
        $totalRows = $file->key();
        $file->rewind();
        $progressBar = $this->output->createProgressBar($totalRows - 1); // Exclude header row

        // Get the default category for news, create it if it doesn't exist
        $newsCategory = Category::firstOrCreate(
            ['name' => 'أخبار المحافظة']
        );

        $header = $file->fgetcsv(); // Read the header row
        $rowCount = 0;

        DB::beginTransaction(); // Start a database transaction

        try {
            while (!$file->eof() && ($row = $file->fgetcsv()) !== false) {
                // Combine header with row to create an associative array
                if (count($header) !== count($row)) continue; // Skip malformed rows
                $data = array_combine($header, $row);

                if (empty(array_filter($data))) continue; // Skip empty rows

                // Create the post using the data
                // We use updateOrCreate to avoid duplicates if you run the command multiple times
                Post::updateOrCreate(
                    ['slug' => $data['slug'] ?? Str::slug($data['title'])],
                    [
                        'title'         => $data['title'],
                        'content'       => $data['content'] ?? '',
                        // Assuming your images field has the path to the main thumbnail
                        'thumbnail'     => $data['images'] ?? $data['featured_image'] ?? 'placeholders/default.jpg',
                        'category_id'   => $newsCategory->id,
                        'is_published'  =>  true,
                        'is_featured'   => filter_var($data['is_featured'] ?? 0, FILTER_VALIDATE_BOOLEAN),
                        'published_at'  => !empty($data['published_at']) ? \Carbon\Carbon::parse($data['published_at']) : now(),
                        'created_at'    => !empty($data['created_at']) ? \Carbon\Carbon::parse($data['created_at']) : now(),
                        'updated_at'    => !empty($data['updated_at']) ? \Carbon\Carbon::parse($data['updated_at']) : now(),
                    ]
                );

                $progressBar->advance();
                $rowCount++;
            }

            DB::commit(); // If everything is successful, commit the changes to the database

            $progressBar->finish();
            $this->info("\nSuccessfully imported {$rowCount} posts.");
            return 0; // Return a success code

        } catch (\Exception $e) {
            DB::rollBack(); // If an error occurs, roll back all changes

            Log::error('Post Import Error: ' . $e->getMessage());
            $this->error("\nAn error occurred during import. Check the log file for details. No data was imported.");
            return 1;
        }
    }
}
