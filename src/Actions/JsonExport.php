<?php

namespace HC\Crud\Actions;

use HC\Crud\Entity;
use Illuminate\Http\Request;

/**
 *
 */
class JsonExport extends AbstractAction
{
    /**
     * {@inheritdoc}
     */
    public function __invoke(Request $request, Entity $entity, $id)
    {
        return $this->getRepository($request, $entity, $id)->all();
    }
}
