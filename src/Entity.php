<?php

namespace HC\Crud;

use HC\Crud\Columns\Collection as ColumnsCollection;
use HC\Crud\Columns\Factory as ColumnsFactory;
use HC\Crud\Contracts\Repository;
use HC\Crud\Fields\Collection as FieldsCollection;
use HC\Crud\Fields\Factory as FieldsFactory;
use HC\Crud\Repositories\EloquentRepository;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use JsonSerializable;

/**
 * [Branch description].
 */
abstract class Entity implements Arrayable, JsonSerializable
{
    /**
     *  Eloquent model class name (if using eloquent repository).
     *
     * @var string
     */
    protected $model;

    /**
     * Get computed fields from columns and/or field declaration
     * ( better to use transform if time consuming declarations ).
     *
     * @var array 'columns' and/or 'fields'
     */
    protected $withComputedOn = [];

    /**
     * @var string
     */
    protected $resource;

    /**
     * @var string
     */
    protected $apiUri;

    /**
     *  C -> create (hasNew)
     *  R -> read   (hasView)
     *  U -> update (hasEdit)
     *  D -> delete (hasDelete).
     *
     * @var array
     */
    protected $operations = [
        'C', 'R', 'U', 'D',
    ];

    /**
     * @var FieldsCollection|null
     */
    private $fields;

    /**
     * @var FieldsCollection|null
     */
    private $searchFields;

    /**
     * @var ColumnsCollection|null
     */
    private $columnsList;

    /**
     * @var Collection
     */
    private $computed;

    /**
     * @var Repository
     */
    private $repository;

    /**
     * @return FieldsCollection
     *
     * @see https://github.com/leezng/el-form-renderer/blob/dev/README.md
     */
    public function getFields()
    {
        return $this->fields ?? (
            $this->fields = tap(new FieldsCollection, function ($collection) {
                $this->form(new FieldsFactory($collection));
            })
        );
    }

    /**
     * @return FieldsCollection
     *
     * @see https://github.com/leezng/el-form-renderer/blob/dev/README.md
     */
    public function getSearchFields()
    {
        return $this->searchFields ?? (
            $this->searchFields = tap(new FieldsCollection, function ($collection) {
                $this->searchForm(new FieldsFactory($collection));
            })
        );
    }

    /**
     * @return ColumnsCollection
     */
    public function getColumns()
    {
        return $this->columnsList ?? (
            $this->columnsList = tap(new ColumnsCollection, function ($collection) {
                $this->columns(new ColumnsFactory($collection));
            })
        );
    }

    /**
     * @return Collection
     */
    public function getComputed()
    {
        return $this->computed ?? (
            $this->computed = Collection::make()
                ->when(in_array('fields', $this->withComputedOn), function (Collection $computed) {
                    return $computed->union($this->getFields()->getComputed());
                })
                ->when(in_array('columns', $this->withComputedOn), function (Collection $computed) {
                    return $computed->union($this->getColumns()->getComputed());
                })
        );
    }

    /**
     * @param mixed $record
     *
     * @return mixed|array array if computed available else input
     */
    public function withComputed($record)
    {
        $computed = $this->getComputed();

        if (! $computed->isEmpty()) {
            $call = function ($callback) use ($record) {
                return $callback($record);
            };

            return $computed->map($call)->union($record)->all();
        }

        return $record;
    }

    /**
     * @return Repository
     */
    public function getRepository(): Repository
    {
        return $this->repository ?? (
            $this->repository = $this->createRepository()
        );
    }

    /**
     * @return Repository
     */
    public function createRepository(): Repository
    {
        return new EloquentRepository($this->model);
    }

    /**
     *  Define edit/create form fields.
     *
     * @param FieldsFactory $factory
     */
    protected function form(FieldsFactory $factory)
    {
        // code...
    }

    /**
     *  Define search form fields.
     *
     * @param FieldsFactory $factory
     */
    protected function searchForm(FieldsFactory $factory)
    {
        // code...
    }

    /**
     * Define some columns.
     *
     * @param ColumnsFactory $factory
     */
    abstract public function columns(ColumnsFactory $factory);

    /**
     * @return array
     */
    public function formAttrs(): array
    {
        return [];
    }

    /**
     * @return array
     */
    public function tableAttrs(): array
    {
        return [];
    }

    /**
     * Array of TableButton's.
     *
     * @return array
     */
    public function extraButtons(): array
    {
        return [];
    }

    /**
     * Array of TableButton's.
     *
     * @return array
     */
    public function headerButtons(): array
    {
        return [];
    }

    /**
     * @return bool
     */
    public function singleSelection(): bool
    {
        return false;
    }

    /**
     * Mutipliers for pagination size to chose from
     * ( available page sizes will be computed ).
     *
     * @return array
     */
    public function paginationSizeMultipliers(): array
    {
        return [1, 2, 3, 4, 5, 10];
    }

    /**
     * Size of pagination page.
     * Return 0 to disable pagination.
     *
     * @return int
     */
    public function paginationSize(): int
    {
        $repo = $this->getRepository();

        if ($repo instanceof EloquentRepository) {
            return $repo->createModel()->getPerPage();
        }

        return 0;
    }

    /**
     * Return primary key for table.
     *
     * @return string
     */
    public function getKeyName(): string
    {
        $repo = $this->getRepository();

        if ($repo instanceof EloquentRepository) {
            return $repo->createModel()->getKeyName();
        }

        return 'id';
    }

    /**
     * Pairs of callable custom actions [action => callable]
     * Executed callable accept typed custom parameters
     * and gets matched entity and id (row id, selection ids separated by comma or null) as named parameters.
     *
     * @return array
     */
    public function actions(): array
    {
        return [];
    }

    /**
     * @param int $id
     *
     * @return array
     */
    public function rules($id = 0): array
    {
        $rules = [];

        // copy nullable fields by default
        foreach ($this->getFields()->getIds() as $id) {
            $rules[$id] = 'nullable';
        }

        // copy required rules by default
        foreach ($this->getFields()->getRequired() as $id) {
            $rules[$id] = 'required';
        }

        return $rules;
    }

    /**
     * Update & store request (route name entity.store/update).
     *
     * @param Request    $request
     * @param int|string $id
     *
     * @throws ValidationException
     *
     * @return array
     */
    public function validateRequest(Request $request, $id = 0): array
    {
        $data = $request->validate($this->rules()) ?: $request->all();

        return Arr::only($data, $this->getFields()->getIds());
    }

    /**
     * Modify loading query or return custom response
     * (by default apply filters on index query - skip single).
     *
     * @param Request         $request
     * @param string|int|null $id      for single query
     *
     * @return $this
     */
    public function loading(Request $request, $id = null)
    {
        $repo = $this->getRepository();

        $repo->setPresenter(function ($model) {
            return $this->withComputed(
                $this->transform($model)
            );
        });

        if (null !== $id) {
            return $this;
        }

        $repo->applyFilters($request);

        return $this;
    }

    /**
     * @param mixed $model
     *
     * @return mixed
     */
    public function transform($model)
    {
        return $model;
    }

    /**
     * @return string
     */
    public function getApiUri(): string
    {
        return $this->apiUri ?? ($this->apiUri = app('config')->get('crud.uri'));
    }

    /**
     * @return string
     */
    public function getResource(): string
    {
        return $this->resource ?? ($this->resource = Str::snake(Str::plural(class_basename($this)), '-'));
    }

    /**
     * @return string
     */
    public function getResourceUri(): string
    {
        return $this->getApiUri() . '/' . $this->getResource();
    }

    /**
     * Get Uri to registerd action.
     *
     * @param string     $action action name
     * @param array      $params optional query parameters
     * @param string|int $id     resource identifier
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    public function getActionUri(string $action, array $params = [], $id = 'action'): string
    {
        if (empty($this->actions()[$action])) {
            throw new \InvalidArgumentException("Action [$action] is not registered");
        }

        return $this->getResourceUri() . "/{$id}/{$action}" . ($params ? '?' . http_build_query($params) : '');
    }

    /**
     *  Computed pagination options (from size and multipliers)
     *  { hasPagination, paginationSize, paginationSizes }.
     *
     * @return array
     *
     * @see paginationSize, paginationSizeMultipliers
     * @see http://element.eleme.io/#/en-US/component/pagination
     */
    public function pagination(): array
    {
        $perPage     = $this->paginationSize();
        $multipliers = $this->paginationSizeMultipliers();

        $sizes = array_map(function ($val) use ($perPage) {
            return $val * $perPage;
        }, $multipliers);

        return [
            'hasPagination'    => $perPage > 0,
            'paginationSize'   => $perPage,
            'paginationSizes'  => $sizes,
            'paginationLayout' => 'total, sizes, prev, pager, next, jumper',
        ];
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id'              => $this->getKeyName(),
            'class'           => $this->getResource(),
            'url'             => $this->getResourceUri(),
            'columns'         => $this->getColumns(),
            'form'            => $this->getFields(),
            'searchForm'      => $this->getSearchFields(),
            'formAttrs'       => (object) $this->formAttrs(),
            'tableAttrs'      => (object) $this->tableAttrs(),
            'extraButtons'    => $this->extraButtons(),
            'headerButtons'   => $this->headerButtons(),
            'single'          => $this->singleSelection(),
            'hasNew'          => in_array('C', $this->operations),
            'hasView'         => in_array('R', $this->operations),
            'hasEdit'         => in_array('U', $this->operations),
            'hasDelete'       => in_array('D', $this->operations),
        ] + $this->pagination();
    }

    /**
     * {@inheritdoc}
     *
     * @see toArray
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Json representation with parse for JS functions.
     *
     * @param mixed $data
     *
     * @return string
     */
    public static function jsonWithParse($data): string
    {
        // TODO check options for js functions
        $json = json_encode(
            $data,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES |
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
        );

        return "JSON.parse('{$json}', function (key, val) {"
            . "  if (val && (typeof val === 'string') && val.indexOf('function') === 0) {"
            . "    return new Function('return ' + val)()"
            . '  }'
            . '  return val'
            . '})'
        ;
    }

    /**
     * @return string
     *
     * @see jsonWithParse
     */
    public function __toString()
    {
        return static::jsonWithParse($this->toArray());
    }
}
