<?php

namespace HC\Crud\Contracts;

use Generator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 *
 */
interface Repository
{
    /**
     * Retrieve all data of repository.
     *
     * @param array $columns
     *
     * @return Collection
     */
    public function all($columns = ['*']): Collection;

    /**
     * Retrieve first record from repository.
     *
     * @param array $columns
     *
     * @return mixed
     */
    public function first($columns = ['*']);

    /**
     * @param array $columns
     *
     * @return Generator
     */
    public function yield($columns = ['*']): Generator;

    /**
     * @param int   $limit
     * @param array $columns
     *
     * @return Collection|LengthAwarePaginator
     */
    public function paginate($limit = null, $columns = ['*']);

    /**
     * @param int|string $id
     * @param array      $columns
     *
     * @return mixed|Collection
     */
    public function find($id, $columns = ['*']);

    /**
     * Save a new entity in repository.
     *
     * @param array $attributes
     *
     * @return mixed
     */
    public function create(array $attributes);

    /**
     * Update a entity in repository by id.
     *
     * @param array      $attributes
     * @param int|string $id
     *
     * @return mixed
     */
    public function update(array $attributes, $id);

    /**
     * Delete a entity in repository by id.
     *
     * @param int|string $id
     *
     * @return mixed
     */
    public function delete($id);

    /**
     * Order collection by a given column.
     *
     * @param string $column
     * @param string $direction
     *
     * @return $this
     */
    public function orderBy($column, $direction = 'asc');

    /**
     * @param string|array|\Closure $column
     * @param mixed                 $operator
     * @param mixed                 $value
     * @param string                $boolean
     *
     * @return $this
     */
    public function where($column, $operator = null, $value = null, $boolean = 'and');

    /**
     * @param array|int|string $id
     *
     * @return $this
     */
    public function whereKey($id);

    /**
     * @param Request $request
     *
     * @return $this
     */
    public function applyFilters(Request $request);

    /**
     * Set Presenter.
     *
     * @param callable $presenter
     *
     * @return mixed
     */
    public function setPresenter($presenter);
}
