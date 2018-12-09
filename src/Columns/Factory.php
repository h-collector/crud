<?php

namespace HC\Crud\Columns;

use Illuminate\Support\Str;

/**
 * @method Column boolean($prop, $label = '', $attrs = [])
 * @method Column img($prop, $label = '', $attrs = [])
 * @method Column link($prop, $label = '', $attrs = [])
 * @method Column html($prop, $label = '', $attrs = [])
 * @method Column expand($prop, $label = '', $attrs = [])
 */
class Factory
{
    /**
     * @var Collection|Column[]
     */
    protected $columns;

    /**
     * @param Collection $collection
     */
    public function __construct(Collection $collection)
    {
        $this->columns = $collection;
    }

    /**
     * @return Collection
     */
    public function getColumns(): Collection
    {
        return $this->columns;
    }

    /**
     * @param array $columns
     *
     * @return $this
     */
    public function setColumns(array $columns)
    {
        foreach ($this->columns as $key => $value) {
            $this->columns->offsetUnset($key);
        }

        foreach ($columns as $col) {
            $this->columns->push(new Column($col));
        }

        return $this;
    }

    /**
     * Append col to collection.
     *
     * @param Column $col
     *
     * @return Column
     */
    public function addCol(Column $col)
    {
        $this->columns->push($col);

        return $col;
    }

    /**
     * @param string $prop  required beside selection, index
     * @param string $label
     * @param string $type  one of: index, selection, expand, bool, img, link, html
     * @param array  $attrs other attributes
     *
     * @return Column
     */
    public function col(string $prop = '', string $label = '', string $type = '', array $attrs = [])
    {
        return $this->addCol(new Column(['prop'  => $prop, 'label' => $label, 'type'  => $type]))
            ->appendAttributes($attrs);
    }

    /**
     * @param array $attrs other attributes
     *
     * @return Column
     */
    public function selection($attrs = [])
    {
        return $this->addCol(new Column(['type' => Column::TYPE_SELECTION]))->appendAttributes($attrs);
    }

    /**
     * @param array $attrs other attributes
     *
     * @return Column
     */
    public function index($attrs = [])
    {
        return $this->addCol(new Column(['type' => Column::TYPE_INDEX]))->appendAttributes($attrs);
    }

    /**
     * @param string $method
     * @param array  $params
     *
     * @return Column column
     */
    public function __call($method, $params)
    {
        switch ($method) {
            case Column::TYPE_BOOLEAN:
            case Column::TYPE_IMG:
            case Column::TYPE_LINK:
            case Column::TYPE_HTML:
            case Column::TYPE_EXPAND:
                return $this->addCol(new Column([
                    'type'       => $method,
                    'prop'       => $params[0] ?? '',
                    'label'      => $params[1] ?? '',
                ]))->appendAttributes($params[2] ?? []);

            default:
                return $this->addCol(new Column([
                    'component'  => Str::snake($method, '-'),
                    'prop'       => $params[0] ?? '',
                    'label'      => $params[1] ?? '',
                ]))->appendAttributes($params[2] ?? []);
        }
    }
}
