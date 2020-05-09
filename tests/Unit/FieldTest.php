<?php

namespace Binaryk\LaravelRestify\Tests\Unit;

use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\RepositoryStoreRequest;
use Binaryk\LaravelRestify\Http\Requests\RepositoryUpdateRequest;
use Binaryk\LaravelRestify\Tests\Fixtures\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Route;

class FieldTest extends IntegrationTest
{
    public function test_fields_can_have_custom_index_callback()
    {
        $field = Field::make('name')->indexCallback(function ($value) {
            return strtoupper($value);
        });

        $field->resolveForIndex((object) ['name' => 'Binaryk'], 'name');
        $this->assertEquals('BINARYK', $field->value);

        $field->resolveForShow((object) ['name' => 'Binaryk'], 'name');
        $this->assertEquals('Binaryk', $field->value);
    }

    public function test_fields_can_have_custom_show_callback()
    {
        $field = Field::make('name')->showCallback(function ($value) {
            return strtoupper($value);
        });

        $field->resolveForShow((object) ['name' => 'Binaryk'], 'name');
        $this->assertEquals('BINARYK', $field->value);

        $field->resolveForIndex((object) ['name' => 'Binaryk'], 'name');
        $this->assertEquals('Binaryk', $field->value);
    }

    public function test_fields_can_have_custom_resolver_callback_even_if_field_is_missing()
    {
        $field = Field::make('Name')->showCallback(function ($value, $model, $attribute) {
            return strtoupper('default');
        });

        $field->resolveForShow((object) ['name' => 'Binaryk'], 'email');

        $this->assertEquals('DEFAULT', $field->value);
    }

    public function test_computed_fields_resolve()
    {
        $field = Field::make(function () {
            return 'Computed';
        });

        $field->resolveForIndex((object) []);

        $this->assertEquals('Computed', $field->value);
    }

    public function test_fields_may_have_callback_resolver()
    {
        $field = Field::make('title', function () {
            return 'Resolved Title';
        });

        $field->resolveForIndex((object) []);

        $this->assertEquals('Resolved Title', $field->value);
    }

    public function test_fields_has_default_value()
    {
        $field = Field::make('title')->default('Title');

        $field->resolveForIndex((object) []);

        $this->assertEquals('Title', data_get($field->jsonSerialize(), 'value'));
    }

    public function test_field_can_have_custom_store_callback()
    {
        $request = new RepositoryStoreRequest([], []);

        $model = new class extends Model {
            protected $fillable = ['title'];
        };

        /** * @var Field $field */
        $field = Field::new('title')->storeCallback(function ($request, $model) {
            $model->title = 'from store callback';
        });

        $field->fillAttribute($request, $model);

        $this->assertEquals('from store callback', $model->title);
    }

    public function test_field_can_have_custom_udpate_callback()
    {
        $request = new RepositoryUpdateRequest([], []);

        $model = new class extends Model {
            protected $fillable = ['title'];
        };

        /** * @var Field $field */
        $field = Field::new('title')->updateCallback(function ($request, $model) {
            $model->title = 'from update callback';
        });

        $field->fillAttribute($request, $model);

        $this->assertEquals('from update callback', $model->title);
    }

    public function test_field_fill_callback_has_high_priority()
    {
        $request = new RepositoryStoreRequest([], []);

        $model = new class extends Model {
            protected $fillable = ['title'];
        };

        /** * @var Field $field */
        $field = Field::new('title')
            ->fillCallback(function ($request, $model) {
                $model->title = 'from fill callback';
            })
            ->storeCallback(function ($request, $model) {
                $model->title = 'from store callback';
            })
            ->updateCallback(function ($request, $model) {
                $model->title = 'from update callback';
            });

        $field->fillAttribute($request, $model);

        $this->assertEquals('from fill callback', $model->title);
    }

    public function test_field_fill_from_request()
    {
        $request = new RepositoryStoreRequest([], []);

        $request->setRouteResolver(function () use ($request) {
            return tap(new Route('POST', '/{repository}', function () {
            }), function (Route $route) use ($request) {
                $route->bind($request);
                $route->setParameter('repository', PostRepository::uriKey());
            });
        });

        $request->merge([
            'title' => 'title from request',
        ]);

        $model = new class extends Model {
            protected $fillable = ['title'];
        };

        /** * @var Field $field */
        $field = Field::new('title');

        $field->fillAttribute($request, $model);

        $this->assertEquals('title from request', $model->title);
    }

    public function test_field_after_store_called()
    {
        $request = new RepositoryStoreRequest([], []);

        $request->setRouteResolver(function () use ($request) {
            return tap(new Route('POST', '/{repository}', function () {
            }), function (Route $route) use ($request) {
                $route->bind($request);
                $route->setParameter('repository', PostRepository::uriKey());
            });
        });

        $request->merge([
            'title' => 'After store title',
        ]);

        $model = new class extends Model {
            protected $table = 'posts';
            protected $fillable = ['title'];
        };

        /** * @var Field $field */
        $field = Field::new('title')->afterStore(function($value, $model) {
            $this->assertEquals('After store title', $value);
            $this->assertInstanceOf(Model::class, $model);
        });

        $field->fillAttribute($request, $model);

        $model->save();

        $field->invokeAfter($request, $model);
    }

    public function test_field_after_update_called()
    {
        $model = new class extends Model {
            protected $table = 'posts';
            protected $fillable = ['title'];
        };

        $model->title = 'Before update title';
        $model->save();

        $request = new RepositoryUpdateRequest([], []);

        $request->setRouteResolver(function () use ($request, $model) {
            return tap(new Route('PUT', "/{repository}/{$model->id}", function () {
            }), function (Route $route) use ($request) {
                $route->bind($request);
                $route->setParameter('repository', PostRepository::uriKey());
            });
        });

        $request->merge([
            'title' => 'After update title',
        ]);

        /** * @var Field $field */
        $field = Field::new('title')->afterUpdate(function($valueAfterUpdate, $valueBeforeUpdate, $model) {
            $this->assertEquals('After update title', $valueAfterUpdate);
            $this->assertEquals('Before update title', $valueBeforeUpdate);
            $this->assertInstanceOf(Model::class, $model);
        });

        $field->fillAttribute($request, $model);

        $model->save();

        $field->invokeAfter($request, $model);
    }

}
