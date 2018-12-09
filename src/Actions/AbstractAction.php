<?php

namespace HC\Crud\Actions;

use HC\Crud\Contracts\Repository;
use HC\Crud\Entity;
use HC\Crud\Repositories\EloquentRepository;
use Illuminate\Http\Request;

/**
 *
 */
abstract class AbstractAction
{
    /**
     * @param Request    $request
     * @param Entity     $entity
     * @param string|int $id
     *
     * @return mixed
     */
    abstract public function __invoke(Request $request, Entity $entity, $id);

    /**
     * @param Request    $request
     * @param Entity     $entity
     * @param int|string $id
     *
     * @return Repository
     */
    public function getRepository(Request $request, Entity $entity, $id)
    {
        $repo = $entity->getRepository();

        if (('action' !== $id) && ($ids = $this->split($id))) {
            $repo->whereKey($ids);
        }

        if ($with = $request->get('include')) {
            if ($repo instanceof EloquentRepository) {
                $repo->with($this->split($with));
            }
        }

        return $repo;
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
