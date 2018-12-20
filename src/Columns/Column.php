<?php

namespace HC\Crud\Columns;

use HC\Crud\Concerns\HasComputedAttribute;
use Illuminate\Support\Fluent;
use InvalidArgumentException;

/**
 * @property string $prop
 * @property string $label
 *
 * @method $this component(string $component)      custom component name
 * @method $this params(array $params)             custom component parameters
 * @method $this type(string $type)                type of column: index/selection/expand/bool/img/link/html
 * @method $this label(string $val)                property of column
 * @method $this columnKey(string $val)            property of column
 * @method $this width(string $val)                property of column
 * @method $this minWidth(string $val)             property of column
 * @method $this fixed(string|bool $val)           property of column: true/left/right
 * @method $this renderHeader(JavascriptFunc $val) property of column  Function (h, { column, $index }
 * @method $this sortable(string|bool $val)        property of column: true/false/custom
 * @method $this sortMethod(JavascriptFunc $val)   property of column: Function(a, b)
 * @method $this sortBy(JavascriptFunc $val)       property of column: Function(row, index /String/Array
 * @method $this sortOrders(array $val)            property of column: ['ascending', 'descending', null]
 * @method $this resizable(bool $val)              property of column
 * @method $this formatter(JavascriptFunc $val)    property of column: not usable
 * @method $this showOverflowTooltip(bool $val)    property of column
 * @method $this align(string $val)                property of column: left/center/right
 * @method $this headerAlign(string $val)          property of column: left/center/right
 * @method $this className(string $val)            property of column
 * @method $this labelClassName(string $val)       property of column
 * @method $this selectable(JavascriptFunc $val)   property of column: Function(row, index
 * @method $this reserveSelection(bool $val)       property of column: selection
 * @method $this filters(array $val)               property of column: Array[{ text, value }]
 * @method $this filterPlacement(string: $val)     property of column: same as Tooltip's placement
 * @method $this filterMultiple(bool $val)         property of column
 * @method $this filterMethod(JavascriptFunc $val) property of column: Function(value, row, column
 * @method $this filteredValue(array $val)         property of column
 *
 * @see http://element.eleme.io/#/en-US/component/table#table-column-attributes
 */
class Column extends Fluent
{
    use HasComputedAttribute;

    /**
     * @var string
     */
    const TYPE_SELECTION = 'selection';

    /**
     * @var string
     */
    const TYPE_INDEX = 'index';

    /**
     * @var string
     */
    const TYPE_EXPAND = 'expand';

    /**
     * @var string
     */
    const TYPE_BOOLEAN = 'boolean';

    /**
     * @var string
     */
    const TYPE_IMG = 'img';

    /**
     * @var string
     */
    const TYPE_LINK= 'link';

    /**
     * @var string
     */
    const TYPE_HTML= 'html';

    /**
     * Create a new fluent container instance.
     *
     * @param array|object $attributes
     *
     * @return void
     */
    public function __construct($attributes = [])
    {
        parent::__construct(array_filter($attributes, function ($value) {
            return ! (null === $value || [] === $value || '' === $value);
        }));

        if (false === $this->validateProp()) {
            throw new InvalidArgumentException('Prop is required');
        }
    }

    /**
     * @param array $attrs
     *
     * @return $this
     */
    public function appendAttributes(array $attrs)
    {
        $this->attributes += $attrs;

        return $this;
    }

    /**
     * @return bool
     */
    public function validateProp(): bool
    {
        if ($this->prop || '0' === $this->prop || $this->component) {
            return true;
        }

        if (in_array($this->type, [
            static::TYPE_SELECTION,
            static::TYPE_INDEX,
        ])) {
            return true;
        }

        return false;
    }
}
