<?php

namespace HC\Crud\Buttons;

use HC\Crud\JavascriptFunc;
use Illuminate\Support\Fluent;

/**
 * @method ActionButton params(array $params)     action button request params.
 * @method ActionButton refresh(bool $refresh)    refresh table after response (true by default)
 * @method ActionButton external(bool $external)  open action uri in new window (false by default)
 * @method ActionButton uri(string $uri)          custom uri to action
 * @method ActionButton method(ing $method)       custom http method to action
 * @method ActionButton confirm(string $confirm)  action custom confirm message
 */
class ActionButton extends TableButton
{
    /**
     * @var string
     */
    protected $action;

    /**
     * @param string              $action   custom action
     * @param string              $text
     * @param string              $uri      custom uri or by default to {resource}/{id}/{action}
     * @param string              $method   http method
     * @param JavascriptFunc|null $show     bool Function(row) {}
     * @param JavascriptFunc|null $disabled vool Function(selected) {}
     */
    public function __construct(
        string $action,
        string $text = '',
        string $uri  = '',
        string $method = 'POST',
        JavascriptFunc $show    = null,
        JavascriptFunc $disabled =null
    ) {
        $this->action     = $action;
        $this->text       = $text ?: ucfirst($action);
        $this->show       = $show;
        $this->disabled   = $disabled;
        $this->attributes = new Fluent;

        $this->attributes['uri']      = $uri;
        $this->attributes['method']   = $method;
        $this->attributes['refresh']  = true;
        $this->attributes['external'] = false;
        $this->attributes['confirm']  = '';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $base = [
            'action'  => $this->action,
            'loading' => false,
        ];

        return $base + parent::toArray();
    }
}
