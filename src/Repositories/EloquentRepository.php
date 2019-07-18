<?php

namespace HC\Crud\Repositories;

use Generator;
use HC\Eloquent\FilterableTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 *
 */
class EloquentRepository extends BaseRepository
{
    /**
     * @var string
     */
    protected $model;

    /**
     * @var Builder
     */
    protected $query;

    /**
     * @param string $model
     */
    public function __construct(string $model)
    {
        $this->model = $model;
    }

    /**
     * @return Builder
     */
    public function getQuery()
    {
        return $this->query ?? $this->resetQuery();
    }

    /**
     * @return Builder
     */
    public function resetQuery()
    {
        return $this->query = $this->createModel()->newQuery();
    }

    /**
     * {@inheritdoc}
     */
    public function all($columns = ['*']): Collection
    {
        return $this->parseResult(
            $this->getQuery()->get($columns)
        );
    }

    /**
     * Alias of All method.
     *
     * @param array $columns
     *
     * @return Collection
     */
    public function get($columns = ['*'])
    {
        return $this->all($columns);
    }

    /**
     * {@inheritdoc}
     */
    public function first($columns = ['*'])
    {
        return $this->parseResult(
            $this->getQuery()->first($columns)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function yield($columns = ['*']): Generator
    {
        $query = $this->getQuery();

        $records = $query->getEagerLoads()
            ? $query->get($columns)
            : $query->select($columns)->cursor()
        ;

        foreach ($records as $record) {
            yield $this->parseResult($record);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function paginate($limit = null, $columns = ['*'])
    {
        return $this->parseResult(
            $this->getQuery()->paginate($limit, $columns)
        );
    }

    /**
     * {@inheritdoc}
     *
     * @return Model
     */
    public function find($id, $columns = ['*'])
    {
        return $this->getQuery()->findOrFail($id, $columns);
    }

    /**
     * {@inheritdoc}
     *
     * @return Model
     */
    public function create(array $attributes = [])
    {
        $model = $this->createModel()->forceFill($attributes);

        if (false === (/*$entity->saving($model) && */$model->saveOrFail())) {
            throw new RuntimeException(
                'Record not created'
            );
        }

        return $model;
    }

    /**
     * {@inheritdoc}
     */
    public function update(array $attributes, $id)
    {
        $model = $this->find($id)->forceFill($attributes);

        if (false === (/*$entity->saving($model) && */$model->saveOrFail())) {
            throw new RuntimeException(
                "Record {$id} not updated"
            );
        }

        return $model;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id)
    {
        $ids = $this->split($id);

        $delete = function (Model $model) {
            if (false === (/*$entity->deleting($model) &&*/ $model->delete())) {
                throw new RuntimeException("Record {$model->getKey()} not deleted");
            }
        };

        $connection = $this->createModel()->getConnection();

        return $connection->transaction(function () use ($ids, $delete) {
            return $this->find($ids)->each($delete);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function orderBy($column, $direction = 'asc')
    {
        $this->getQuery()->orderBy($column, $direction);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        $this->getQuery()->where($column, $operator, $value, $boolean);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function whereKey($id)
    {
        $this->getQuery()->whereKey($id);

        return $this;
    }

    /**
     * Load relations.
     *
     * @param array|string $relations
     *
     * @return $this
     */
    public function with($relations)
    {
        $this->getQuery()->with($relations);

        return $this;
    }

    /**
     * @param array $attributes [description]
     *
     * @return Model
     */
    public function createModel($attributes = [])
    {
        return new $this->model($attributes);
    }

    /**
     * Modify loading query / apply filters
     * (by default apply filters if model uses FilterableTrait).
     *
     * @param Request $request
     *
     * @return $this
     */
    public function applyFilters(Request $request)
    {
        $traits = class_uses($this->model);

        // Apply filters by default
        if ($traits && in_array(FilterableTrait::class, $traits)) {
            $this->getQuery()->filtered(
                array_filter($request->all(), function ($val) {
                    return '' !== $val;
                })
            );
        }

        return $this;
    }
}
