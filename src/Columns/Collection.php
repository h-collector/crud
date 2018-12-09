<?php

namespace HC\Crud\Columns;

use Illuminate\Support\Collection as BaseCollection;
use RuntimeException;

/**
 */
class Collection extends BaseCollection
{
    /**
     * @return array
     */
    public function getIds(): array
    {
        return $this->whereNotIn('type', ['selection', 'index'])->pluck('prop')->toArray();
    }

    /**
     * @return BaseCollection
     */
    public function getComputed() : BaseCollection
    {
        return $this->keyBy('prop')->map->computed()->filter();
    }

    /**
     * Push an item onto the end of the collection.
     *
     * @param Column $field
     *
     * @throws RuntimeException if item don't have unique prop
     *
     * @return $this
     */
    public function push($field)
    {
        // or maybe cache ids?
        foreach ($this->items as $item) {
            if (null !== $field['prop'] && $field['prop'] === $item['prop']) {
                throw new RuntimeException('Column is not unique');
            }
        }

        return parent::push($field);
    }
}
