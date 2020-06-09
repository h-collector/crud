<?php

namespace HC\Crud\Fields;

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
        return $this->pluck('$id')->toArray();
    }

    /**
     * @return BaseCollection
     */
    public function getComputed() : BaseCollection
    {
        return $this->keyBy('$id')->map->computed()->filter();
    }

    /**
     * @return array
     */
    public function getDisabled(): array
    {
        return $this->where('$el.disabled')->pluck('$id')->toArray();
    }

    /**
     * @return array
     */
    public function getReadonly(): array
    {
        return $this->where('$el.readonly')->pluck('$id')->toArray();
    }

    /**
     * @return array
     */
    public function getRequired(): array
    {
        return $this->where('rules.*.required', true)->pluck('$id')->toArray();
    }

    /**
     * Push an item onto the end of the collection.
     *
     * @param Field $field
     *
     * @throws RuntimeException if item don't have unique id
     *
     * @return $this
     */
    public function push($field)
    {
        // or maybe cache ids?
        foreach ($this->items as $item) {
            if ($field['$id'] === $item['$id']) {
                throw new RuntimeException('Field is not unique');
            }
        }

        return parent::push($field);
    }
}
