<?php

namespace HC\Crud;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Collection;
use Illuminate\View\Factory as ViewFactory;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 *
 */
class CrudService
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @var string
     */
    protected $uri;

    /**
     * @var array
     */
    protected $menu;

    /**
     * @var array
     */
    protected $entities;

    /**
     * @var bool|string
     */
    protected $dashboard;

    /**
     * @var string
     */
    protected $defaultComponent = 'el-crud-view';

    /**
     * @param Container   $container
     * @param array       $entities
     * @param string      $uri
     * @param array       $menu
     * @param bool|string $dashboard
     */
    public function __construct(
        Container $container,
        array $entities,
        string $uri = '',
        array $menu = [],
        $dashboard = false
    ) {
        $this->container = $container;
        $this->entities  = $entities;
        $this->uri       = $uri;
        $this->menu      = $menu;
        $this->dashboard = $dashboard;
    }

    /**
     * @return Collection
     */
    public function getMenu(): Collection
    {
        return Collection::make($this->menu ?: [])
            ->map([$this, 'makeMenuItem'])
            ->reject(function ($entry) {
                return empty($entry['path']);
            })
            ->values();
    }

    /**
     * @param string|array $entry [description]
     * @param string|int   $key   [description]
     *
     * @return array
     */
    public function makeMenuItem($entry, $key): array
    {
        $item = [];

        if (is_string($key)) {
            $item['title'] = $key;
        }

        if (is_string($entry)) {
            $entry = ['entity' => $entry];
        }

        if ($entry['entity'] ?? false) {
            // if (empty($this->entities[$entry['entity']])) {
            //     throw new InvalidArgumentException('Entity not defined');
            // }
            $item['path']      = '/' . $entry['entity'];
            $item['component'] = $entry['component'] ?? $this->defaultComponent;
        }

        if ($entry['component'] ?? $item['component'] ?? false) {
            $item['props'] = (array) ($entry['props'] ?? []) + ['baseUri' => $this->uri];
        }

        if ($entry['path'] ?? false) {
            $item['path'] = $entry['path'];
        }

        if ($entry['children'] ?? false) {
            $item['children'] = Collection::make((array) $entry['children'])
                ->map([$this, 'makeMenuItem'])
                ->values();
        }

        if ($entry['redirect'] ?? false) {
            $item['redirect'] = $entry['redirect'];
        }

        if ($entry['title'] ?? false) {
            $item['title'] = $entry['title'];
        }

        if ($entry['icon'] ?? false) {
            $item['icon'] = $entry['icon'];
        }

        return $item;
    }

    /**
     * @param string $entity registered entity name
     *
     * @throws Exception error while retrieving the entry
     *
     * @return Entity|mixed
     */
    public function getEntity(string $entity): Entity
    {
        return $this->container->make(
            $this->entities[$entity] ?? (in_array($entity, $this->entities) ? $entity : null)
        );
    }

    /**
     * @return Collection
     */
    public function getAllEntities(): Collection
    {
        return Collection::make($this->entities)->map(function ($class, $entity) {
            return $this->getEntity($entity);
        });
    }

    /**
     * Make dashboard view from custom path or crud::dashboard.
     *
     * @param array $params
     *
     * @return View|null
     */
    public function getDashboardView(array $params = []): ? View
    {
        if (! ($name = $this->dashboard)) {
            return null;
        }
        /** @var ViewFactory $view */
        $view = $this->container->get('view');

        if (! is_string($name)) {
            $name = 'crud::dashboard';
        }

        return $view->make($name, $params + [
            'menu' => $this->getMenu(),
        ]);
    }

    /**
     * Make index view from crud.{resource} or crud::index.
     *
     * @param string|Entity $entity
     * @param array         $params
     *
     * @return View
     */
    public function getIndexView($entity, array $params = []): View
    {
        $entity = $this->getEntity($entity);

        /** @var ViewFactory $view */
        $view = $this->container->get('view');

        $name = 'crud.' . $entity->getResource();

        if (! $view->exists($name)) {
            $name = 'crud::index';
        }

        return $view->make($name, $params + [
            'menu'   => $this->getMenu(),
            'entity' => $entity,
        ]);
    }

    /**
     * @param string          $action
     * @param string|Entity   $entity
     * @param string|int|null $id
     *
     * @return mixed
     */
    public function executeCustomAction($entity, $action, $id = null)
    {
        $entity = $entity instanceof Entity ? $entity : $this->getEntity($entity);

        $handler = $entity->actions()[$action] ?? null;

        if (null === $handler) {
            throw new NotFoundHttpException("Custom action [{$action}] is not registered on [{$entity->getResource()}]");
        }

        if (is_string($handler) && false === strpos($handler, '@')) {
            $handler .= '@__invoke';
        }

        return $this->container->call($handler, [
            'entity' => $entity,
            'id'     => $id,
        ]);
    }
}
