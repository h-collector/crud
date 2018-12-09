<?php

namespace HC\Crud\Fields;

use ArrayAccess;
use Closure;
use HC\Crud\Concerns\HasComputedAttribute;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Fluent;
use Illuminate\Support\Traits\ForwardsCalls;
use InvalidArgumentException;

/**
 *
 */
class Field implements Arrayable, ArrayAccess
{
    use ForwardsCalls,
        HasComputedAttribute;

    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * @param array $field array notation
     *
     * @see https://github.com/leezng/el-form-renderer/blob/dev/README.md
     */
    public function __construct(array $field = [])
    {
        $type  = $field['$type'] ?? 'input';
        $id    = $field['$id'] ?? null;
        $el    = $field['$el'] ?? null;
        $attrs = $field['$attrs'] ?? null;

        if (! (is_string($id) || is_int($id))) {
            throw new InvalidArgumentException('Field has invalid id');
        }

        $this->attributes['$id']   = $id;
        $this->attributes['$type'] = $type;

        if (isset($el) && ! $el instanceof Fluent) {
            $this->attributes['$el'] = new Fluent($el);
        }

        if (isset($attrs) && ! $attrs instanceof Fluent) {
            $this->attributes['$attrs'] = new Fluent($attrs);
        }

        $this->attributes += $field;
    }

    /**
     * @param string $type       Type, all the form types provided by element, like el-xxx
     * @param string $id         Each atomic form uses an id to store its value, be careful not to repeat
     * @param string $label      A property on the el-form-item
     * @param mixed  $default    Default value
     * @param array  $el         Used to define the properties of a specific atomic form (el-select in this case)
     * @param array  $attrs      Optional attribute, wording follows the Render function specification of Vue
     * @param array  $enableWhen Optional attribute, display field in relation to other field's value [id => value]
     * @param array  $params     Optional params like
     *                           $options for atomic form with Selection Capabilities
     *                           $items group fields
     *                           $remote url for autocomplete/select
     *                           rules A property on the el-form-item
     *
     * @see https://github.com/leezng/el-form-renderer/blob/dev/README.md
     *
     * @return static
     */
    public static function make(
        $type,
        $id,
        $label = '',
        $default = null,
        array $el = [],
        array $attrs = [],
        array $enableWhen = [],
        array $params = []
    ) {
        return new static($params + [
            '$id'         => $id,
            '$type'       => $type,
            'label'       => $label,
            '$default'    => $default,
            '$el'         => $el,
            '$attrs'      => $attrs,
            '$enableWhen' => $enableWhen,
        ]);
    }

    /**
     * @return string|int
     */
    public function getId()
    {
        return $this->attributes['$id'];
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->attributes['$type'];
    }

    /**
     * @return Fluent
     */
    public function getEl(): Fluent
    {
        return $this->attributes['$el'] ?? ($this->attributes['$el'] = new Fluent);
    }

    /**
     * @return Fluent
     */
    public function getAttrs(): Fluent
    {
        return $this->attributes['$attrs'] ?? ($this->attributes['$attrs'] = new Fluent);
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return (string) $this->attributes['label'];
    }

    /**
     * @return array
     */
    public function getRules(): array
    {
        return $this->attributes['rules'] ?? [];
    }

    /**
     * @param array $rules
     *
     * @return $this
     */
    public function setRules(array $rules)
    {
        $this->attributes['rules'] = $rules;

        return $this;
    }

    /**
     * @return bool bool
     */
    public function isRequired(): bool
    {
        foreach ($this->getRules() as $rule) {
            if ($rule['required'] ?? false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string|array $field
     * @param mixed        $value
     *
     * @return $this
     */
    public function enabledWhen($field, $value = null)
    {
        if (is_array($field)) {
            $this->attributes['$enableWhen'] = $field;

            return $this;
        }
        $this->attributes['$enableWhen'][$field] = $value;

        return $this;
    }

    /**
     * For atomic form with Selection Capabilities.
     *
     * @param array|Closure $options
     *
     * @return $this
     */
    public function setOptions($options)
    {
        if (! (is_array($options) || $options instanceof Closure)) {
            throw new InvalidArgumentException(
                'Options can be only array or array returning Closure')
            ;
        }
        $this->attributes['$options'] = $options;

        return $this;
    }

    /**
     * Set default value.
     *
     * @param mixed $value
     */
    public function setDefault($value)
    {
        $this->attributes['$default'] = $value;
    }

    /**
     * {@inheritdoc}
     *
     * @todo convert $el, $attrs to array
     */
    public function toArray(): array
    {
        $attributes = $this->attributes;

        // allow Closure attributes (like $options)
        // $attributes = array_map('value', $attributes);
        if (($opts = &$attributes['$options']) && $opts instanceof Closure) {
            $opts = $opts();
        }

        return array_filter($attributes, function ($value) {
            return ! (null === $value || $value === []);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return isset($this->attributes[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->attributes[$offset] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        return $this->attributes[$offset] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        if (in_array($offset, ['$id', '$type'])) {
            throw new InvalidArgumentException('Cant remove given ' . $offset);
        }
        unset($this->attributes[$offset]);
    }

    /**
     * Fluent element attributes.
     *
     * @param string $method
     * @param array  $params
     *
     * @return $this
     */
    public function __call($method, $params)
    {
        $this->forwardCallTo($this->getEl(), $method, $params);

        return $this;
    }
}
