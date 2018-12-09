<?php

namespace HC\Crud\Buttons;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Fluent;
use Illuminate\Support\Traits\ForwardsCalls;
use JsonSerializable;
use HC\Crud\JavascriptFunc;

/**
 * @method TableButton type(string $type)   button type:  primary / success / warning / danger / info / text
 * @method TableButton plain(bool $plain)   determine whether it's a plain button
 * @method TableButton round(bool $round)   determine whether it's a round button
 * @method TableButton circle(bool $circle) determine whether it's a circle button
 * @method TableButton icon(string $icon)   icon class name.
 */
class TableButton implements Arrayable, JsonSerializable
{
    use ForwardsCalls;

    /**
     * @var string
     */
    protected $text;

    /**
     * @var JavascriptFunc
     */
    protected $atClick;

    /**
     * @var JavascriptFunc
     */
    protected $show;

    /**
     * @var JavascriptFunc
     */
    protected $disabled;

    /**
     * @var Fluent
     */
    protected $attributes;

    /**
     * @param string              $text
     * @param JavascriptFunc      $atClick  void Function(row, ctx) {}
     * @param JavascriptFunc|null $show     bool Function(row) {}
     * @param JavascriptFunc|null $disabled vool Function(selected) {}
     */
    public function __construct(
        string $text,
        JavascriptFunc $atClick,
        JavascriptFunc $show    = null,
        JavascriptFunc $disabled =null
    ) {
        $this->text       = $text;
        $this->atClick    = $atClick;
        $this->show       = $show;
        $this->disabled   = $disabled;
        $this->attributes = new Fluent;
    }

    /**
     * @param string         $text
     * @param JavascriptFunc $atClick void Function(row, ctx) {}
     *
     * @return static
     */
    public static function make($text, JavascriptFunc $atClick)
    {
        return new static($text, $atClick);
    }

    /**
     * @param JavascriptFunc $atClick void Function(row) {}
     *
     * @return $this
     */
    public function setAtClick(JavascriptFunc $atClick)
    {
        $this->atClick = $atClick;

        return $this;
    }

    /**
     * @param JavascriptFunc|null $show bool Function(row) {}
     *
     * @return $this
     */
    public function setShow(JavascriptFunc $show = null)
    {
        $this->show = $show;

        return $this;
    }

    /**
     * @param JavascriptFunc|null $disabled vool Function(selected) {}
     *
     * @return $this
     */
    public function setDisabled(JavascriptFunc $disabled = null)
    {
        $this->disabled = $disabled;

        return $this;
    }

    /**
     * Valid attributes
     * type   :string - button type:  primary / success / warning / danger / info / text
     * plain  :bool   - determine whether it's a plain button
     * round  :bool   - determine whether it's a round button
     * circle :bool   - determine whether it's a circle button
     * icon   :string - icon class name.
     *
     * @param array $attrs
     *
     * @return Fluent
     */
    public function attrs(array $attrs = [])
    {
        if ($attrs) {
            foreach ($attrs as $key => $value) {
                $this->attributes[$key] = $value;
            }
        }

        return $this->attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $attrs = [
            'text'     => $this->text,
            'atClick'  => $this->atClick,
            'show'     => $this->show,
            'disabled' => $this->disabled,
        ];

        $extra = $this->attributes->toArray();

        return array_filter($attrs + $extra, function ($value) {
            return ! ('' === $value || null === $value/* || [] === $value*/);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function __call($method, $params)
    {
        $this->forwardCallTo($this->attributes, $method, $params);

        return $this;
    }
}
