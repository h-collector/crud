<?php

namespace HC\Crud\Fields;

use Illuminate\Support\Str;

/**
 * @method Field make($type, $id, $label = '', $default = null, $el = [], $attrs = [], $when = [])
 * @method Field input($id, $label = '', $default = null, $el = [], $attrs = [], $when = [])
 * @method Field autocomplete($id, $label = '', $default = null, $el = [], $attrs = []. $when = [])                  has remote for remote method
 * @method Field inputNumber($id, $label = '', $default = null, $el = [], $attrs = []. $when = [])
 * @method Field radio($id, $label = '', $default = null, $el = [], $attrs = []. $when = [])
 * @method Field checkbox($id, $label = '', $default = null, $el = [], $attrs = []. $when = [])
 * @method Field select($id, $label = '', $options = [], $default = null, $el = [], $attrs = []. $when = [])         has remote for remote method
 * @method Field radioGroup($id, $label = '', $options = [], $default = null, $el = [], $attrs = []. $when = [])
 * @method Field radioButton($id, $label = '', $options = [], $default = null, $el = [], $attrs = []. $when = [])
 * @method Field checkboxGroup($id, $label = '', $options = [], $default = null, $el = [], $attrs = []. $when = [])
 * @method Field checkboxButton($id, $label = '', $options = [], $default = null, $el = [], $attrs = []. $when = [])
 * @method Field cascader($id, $label = '', $default = null, $el = [], $attrs = []. $when = [])
 * @method Field switch($id, $label = '', $default = null, $el = [], $attrs = []. $when = [])
 * @method Field slider($id, $label = '', $default = null, $el = [], $attrs = []. $when = [])
 * @method Field timePicker($id, $label = '', $default = null, $el = [], $attrs = []. $when = [])
 * @method Field datePicker($id, $label = '', $default = null, $el = [], $attrs = []. $when = [])
 * @method Field colorPicker($id, $label = '', $default = null, $el = [], $attrs = []. $when = [])
 * @method Field rate($id, $label = '', $default = null, $el = [], $attrs = []. $when = [])
 * @method Field transfer($id, $label = '', $default = null, $el = [], $attrs = []. $when = [])
 */
class Factory
{
    /**
     * @var Collection|Field[]
     */
    protected $fields;

    /**
     * @param Collection $collection
     */
    public function __construct(Collection $collection)
    {
        $this->fields = $collection;
    }

    /**
     * @return Collection
     */
    public function getFields(): Collection
    {
        return $this->fields;
    }

    /**
     * @param array $fields
     *
     * @return $this
     */
    public function setFields(array $fields)
    {
        foreach ($this->fields as $key => $value) {
            $this->fields->offsetUnset($key);
        }

        foreach ($fields as $field) {
            $this->fields->push(new Field($field));
        }

        return $this;
    }

    /**
     * Append field to collection.
     *
     * @param Field $field
     *
     * @return Field
     */
    public function addField(Field $field)
    {
        $this->fields->push($field);

        return $field;
    }

    /**
     * @param string   $id
     * @param callable $fields gets Factory as argument
     *
     * @return Collection
     */
    public function group($id, callable $fields)
    {
        $collection = new Collection;

        $fields(new static($collection));

        if ($id) {
            $this->addField(new Field([
                '$id'    => $id,
                '$type'  => 'group',
                '$items' => $collection,
            ]));
        } else {
            $collection->each([$this, 'addField']);
        }

        return $collection;
    }

    /**
     * @param string $method
     * @param array  $params
     *
     * @return Field element
     */
    public function __call($method, $params)
    {
        $type = Str::snake($method, '-');

        switch ($method) {
            case 'make':

                return $this->addField(Field::make(...$params));

            case 'select':
            case 'radioGroup':
            case 'radioButton':
            case 'checkboxGroup':
            case 'checkboxButton':

                return $this->addField(new Field([
                    '$type'       => $type,
                    '$id'         => $params[0],
                    'label'       => $params[1] ?? '',
                    '$options'    => $params[2] ?? [],
                    '$default'    => $params[3] ?? null,
                    '$el'         => $params[4] ?? [],
                    '$attrs'      => $params[5] ?? [],
                    '$enableWhen' => $params[6] ?? [],
                ]));

            case 'input':
            case 'inputNumber':
            case 'autocomplete':
            case 'radio':
            case 'checkbox':
            case 'cascader':
            case 'switch':
            case 'slider':
            case 'time-picker':
            case 'date-picker':
            case 'color-picker':
            case 'rate':
            case 'transfer':
            default:

                return $this->addField(new Field([
                    '$type'       => $type,
                    '$id'         => $params[0],
                    'label'       => $params[1] ?? '',
                    '$default'    => $params[2] ?? null,
                    '$el'         => $params[3] ?? [],
                    '$attrs'      => $params[4] ?? [],
                    '$enableWhen' => $params[5] ?? [],
                ]));
        }
    }
}
