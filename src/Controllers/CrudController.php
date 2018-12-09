<?php

namespace HC\Crud\Controllers;

use HC\Crud\Concerns\HasCrudMethods;
use HC\Crud\CrudService;
use HC\Crud\Entity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;

/**
 *
 */
class CrudController extends BaseController
{
    use HasCrudMethods;

    /**
     * @var CrudService
     */
    protected $crud;

    /**
     * @param CrudService $crud
     */
    public function __construct(CrudService $crud)
    {
        $this->crud = $crud;
    }

    /**
     * @param string|Entity $entity
     *
     * @return Entity
     */
    public function getEntity($entity)
    {
        return $entity instanceof Entity ? $entity : $this->crud->getEntity($entity);
    }

    /**
     * @param Request $request
     * @param string  $entity
     *
     * @return Response
     */
    public function schema(Request $request, $entity)
    {
        $entity = $this->getEntity($entity);

        // Jsonp with parse function
        if ($callback = $request->input('callback')) {
            return (new JsonResponse())
                ->setJson($entity->jsonWithParse())
                ->withCallback($callback);
        }

        return new JsonResponse($entity, 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param Request $request
     * @param string  $entity
     *
     * @return Response
     */
    public function executeCustomAction(Request $request, $entity)
    {
        return $this->crud->executeCustomAction(
            $entity,
            $request->route('action'),
            $request->route('id')
        );
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function dashboard(Request $request)
    {
        return $this->crud->getDashboardView() ?? new Response('Dashboard not available', 404);
    }

    /**
     * @param Request $request
     * @param string  $entity
     *
     * @return Response
     */
    public function index(Request $request, $entity)
    {
        if (! $request->expectsJson()) {
            return $this->crud->getIndexView($entity);
        }

        return $this->crudIndex($this->getEntity($entity), $request);
    }

    /**
     * @param Request $request
     * @param string  $entity
     *
     * @return Response
     */
    public function store(Request $request, $entity)
    {
        return $this->crudStore($this->getEntity($entity), $request);
    }

    /**
     * @param Request $request
     * @param string  $entity
     * @param int     $id
     *
     * @return Response
     */
    public function update(Request $request, $entity, $id)
    {
        return $this->crudUpdate($this->getEntity($entity), $request, $id);
    }

    /**
     * @param Request $request
     * @param string  $entity
     * @param int     $id
     *
     * @return Response
     */
    public function destroy(Request $request, $entity, $id)
    {
        return $this->crudDestroy($this->getEntity($entity), $request, $id);
    }

    /**
     * @param Request $request
     * @param string  $entity
     * @param int     $id
     *
     * @return Response
     */
    public function show(Request $request, $entity, $id)
    {
        return $this->crudShow($this->getEntity($entity), $request, $id);
    }
}
