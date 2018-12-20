<?php

namespace HC\Crud\Buttons;

use HC\Crud\JavascriptFunc;
use Illuminate\Support\Fluent;

/**
 * @method $this params(array $params)      action button request params.
 * @method $this refresh(bool $refresh)     refresh table after response (true by default)
 * @method $this external(bool $external)   open action uri in new window (false by default)
 * @method $this uri(string $uri)           custom uri to action
 * @method $this method(ing $method)        custom http method to action
 * @method $this confirm(string $confirm)   action custom confirm message
 * @method $this search(bool $search)       append search params to request (false by default)
 * @method $this selection(bool $selection) append row|selection to request (false by default)
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

        $this->attributes['uri']       = $uri;
        $this->attributes['method']    = $method;
        $this->attributes['refresh']   = true;
        $this->attributes['external']  = null; // false
        $this->attributes['confirm']   = '';
        $this->attributes['search']    = null; // false
        $this->attributes['selection'] = null; // false
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
