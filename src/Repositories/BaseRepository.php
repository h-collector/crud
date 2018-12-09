<?php

namespace HC\Crud\Repositories;

use Closure;
use HC\Crud\Contracts\Repository;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 *
 */
abstract class BaseRepository implements Repository
{
    /**
     * @var callable
     */
    protected $presenter;

    /**
     * {@inheritdoc}
     */
    public function setPresenter($presenter)
    {
        if (! is_callable($presenter)) {
            throw new InvalidArgumentException('Presenter must by callable');
        }
        if (! $presenter instanceof Closure) {
            $presenter = Closure::fromCallable($presenter);
        }

        $this->presenter = $presenter;
    }

    /**
     * @param mixed $record
     *
     * @return mixed
     */
    public function callPresenter($record)
    {
        return ($this->presenter)($record);
        // return $this->presenter ? ($this->presenter)($record) : $record;
    }

    /**
     * @param Collection|AbstractPaginator|mixed $result
     *
     * @return mixed
     */
    public function parseResult($result)
    {
        if (empty($this->presenter)) {
            return $result;
        }

        if ($result instanceof AbstractPaginator) {
            $collection = $result->getCollection();
            $collection->transform(function ($record) {
                return $this->callPresenter($record);
            });

            return $result;
        }

        if ($result instanceof Collection) {
            $result->transform(function ($record) {
                return $this->callPresenter($record);
            });

            return $result;
        }

        return $this->callPresenter($result);
    }

    /**
     * @param mixed  $value
     * @param string $delimeter regex
     * @param int    $limit
     *
     * @return string[]|array[]
     */
    public function split($value, $delimeter = '/,/', $limit = -1): array
    {
        if (is_array($value)) {
            return $value;
        }

        return preg_split($delimeter, (string) $value, $limit, PREG_SPLIT_NO_EMPTY) ?: [];
    }
}
