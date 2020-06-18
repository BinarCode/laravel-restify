<?php

namespace Binaryk\LaravelRestify\Commands;

use Binaryk\LaravelRestify\Documentator\PostmanCollectionWriter;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class DocumentationCommand extends Command
{
    protected $name = 'restify:doc';

    protected $description = 'Create API documentation.';

    public function handle()
    {
        $this->info('-----------Generating documentation----------------------');

        $groupedRoutes = collect([
            [
                'name' => 'routeName',
            ],
        ]);

        $this->writePostmanCollection($groupedRoutes);

        $this->info('-----------Documentation generated----------------------');
    }

    protected function writePostmanCollection(Collection $parsedRoutes): void
    {
        $this->info('Generating Postman collection');

        $collection = $this->generatePostmanCollection($parsedRoutes);
        if ($this->isStatic) {
            $collectionPath = "{$this->outputPath}/collection.json";
            file_put_contents($collectionPath, $collection);
        } else {
            $storageInstance = Storage::disk($this->config->get('storage'));
            $storageInstance->put('apidoc/collection.json', $collection, 'public');
            if ($this->config->get('storage') == 'local') {
                $collectionPath = 'storage/app/apidoc/collection.json';
            } else {
                $collectionPath = $storageInstance->url('collection.json');
            }
        }

        $this->output->info("Wrote Postman collection to: {$collectionPath}");
    }

    /**
     * Generate Postman collection JSON file.
     *
     * @param Collection $routes
     *
     * @return string
     */
    public function generatePostmanCollection(Collection $routes)
    {
        /** @var PostmanCollectionWriter $writer */
        $writer = app()->makeWith(
            PostmanCollectionWriter::class,
            ['routeGroups' => $routes, 'baseUrl' => 'restify-api']
        );

        return $writer->getCollection();
    }
}
