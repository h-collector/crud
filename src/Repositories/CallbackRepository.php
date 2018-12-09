<?php

namespace HC\Crud\Repositories;

use Illuminate\Support\Collection;

/**
 *
 */
class CallbackRepository extends BaseRepository
{
    /**
     * @var callable
     */
    protected $callback;

    /**
     * @var string
     */
    protected $primaryKey;

    /**
     * @var array
     */
    protected $where = [];

    /**
     * @var array
     */
    protected $order = [];

    /**
     * @param callable $callback
     * @param string   $primaryKey
     */
    public function __construct(callable $callback, string $primaryKey = 'id')
    {
        $this->callback   = $callback;
        $this->primaryKey = $primaryKey;
    }

    /**
     * {@inheritdoc}
     */
    public function all($columns = ['*']): Collection
    {
        return $this->parseResult(
            $this->resolve($columns)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function first($columns = ['*'])
    {
        return $this->parseResult(
            $this->resolve($columns)->first()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function yield($columns = ['*']): \Generator
    {
        foreach ($this->resolve($columns) as $record) {
            yield $this->parseResult($record);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function paginate($limit = null, $columns = ['*'])
    {
        $page  = (int) app('request')->input('page', 1);
        $items = $this->resolve($columns);
        $total = $items->count();
        $slice = $items->forPage($page, $limit);
        $from  = ($page - 1) * $limit;

        return [
            'data'         => $this->parseResult($slice)->toArray(),
            'current_page' => $page,
            'last_page'    => ceil($limit ? $total / $limit : $page),
            'per_page'     => $limit ?: $total,
            'from'         => $from + 1,
            'to'           => $from + $slice->count(),
            'total'        => $total,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function find($id, $columns = ['*'])
    {
        return $this->whereKey($id)->first($columns);
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $attributes)
    {
        throw new \LogicException('Not implemented'); // TODO
    }

    /**
     * {@inheritdoc}
     */
    public function update(array $attributes, $id)
    {
        throw new \LogicException('Not implemented'); // TODO
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id)
    {
        throw new \LogicException('Not implemented'); // TODO
    }

    /**
     * {@inheritdoc}
     */
    public function orderBy($column, $direction = 'asc')
    {
        $this->order = [
            'key'  => $column,
            'desc' => 'asc' !== $direction,
        ];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        $this->where[] = [
            'key'      => $column,
            'operator' => $operator,
            'value'    => $value,
        ];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function whereKey($id)
    {
        $this->where[] = [
            'key'      => $this->primaryKey,
            'operator' => '=',
            'value'    => $id,
        ];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function applyFilters(\Illuminate\Http\Request $request)
    {
        $data = $request->except([$this->primaryKey, 'page', 'size']);

        foreach ($data as $key => $value) {
            $this->where($key, '=', $value);
        }

        return $this;
    }

    /**
     * @param array $columns
     *
     * @return Collection
     */
    public function resolve($columns = ['*'])
    {
        // must return arrayable or array of arrays
        $results = Collection::make(
            app()->call($this->callback)
        );

        foreach ($this->where as $where) {
            $results = $results->where($where['key'], $where['operator'], $where['value']);
        }

        if ($this->order) {
            $results = $results->sortBy($this->order['key'], SORT_NATURAL, $this->order['desc']);
        }

        if (! in_array('*', $columns)) {
            $results->transform(function ($record) use ($columns) {
                return Collection::make($record)->only($columns)->all();
            });
        }

        $this->order  = [];
        $this->where  = [];

        return $results;
    }
}
