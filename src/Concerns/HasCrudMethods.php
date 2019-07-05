<?php

namespace HC\Crud\Concerns;

use HC\Crud\Entity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

/**
 * [trait description].
 */
trait HasCrudMethods
{
    /**
     * [crudIndex description].
     *
     * @param Entity  $entity
     * @param Request $request
     *
     * @return LengthAwarePaginator|Collection
     *
     * @todo maybe return fields+columns+appends only
     */
    public function crudIndex(Entity $entity, Request $request)
    {
        return $entity->loading($request)
            ->getRepository()
            ->paginate((int) $request->input('size'));
    }

    /**
     * [crudStore description].
     *
     * @param Entity  $entity
     * @param Request $request
     *
     * @return mixed
     */
    public function crudStore(Entity $entity, Request $request)
    {
        $attrs = $entity->validateRequest($request);

        try {
            return $entity->getRepository()->create($attrs);
        } catch (\Exception $e) {
            throw new HttpResponseException(new Response(
                'Record not created: ' . $e->getMessage(), 500
            ));
        }
    }

    /**
     * [crudUpdate description].
     *
     * @param Entity  $entity
     * @param Request $request
     * @param int     $id
     *
     * @return mixed
     */
    public function crudUpdate(Entity $entity, Request $request, $id)
    {
        $attrs = $entity->validateRequest($request, $id);

        try {
            return $entity->getRepository()->update($attrs, $id);
        } catch (\Exception $e) {
            throw new HttpResponseException(new Response(
                "Record {$id} not updated: " . $e->getMessage(), 500
            ));
        }
    }

    /**
     * [crudDelete description].
     *
     * @param Entity     $entity
     * @param Request    $request
     * @param int|string $id
     *
     * @return mixed
     */
    public function crudDestroy(Entity $entity, Request $request, $id)
    {
        try {
            return $entity->getRepository()->delete($id);
        } catch (\Exception $e) {
            throw new HttpResponseException(new Response(
                "Record {$id} not deleted: " . $e->getMessage(), 500
            ));
        }
    }

    /**
     * [crudDelete description].
     *
     * @param Entity  $entity
     * @param Request $request
     * @param int     $id
     *
     * @return mixed
     */
    public function crudShow(Entity $entity, Request $request, $id)
    {
        return $entity->loading($request, $id)
            ->getRepository()
            ->find($id);
    }
}
