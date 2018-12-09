<?php

namespace HC\Crud\Concerns;

use Closure;
use InvalidArgumentException;

/**
 *
 */
trait HasComputedAttribute
{
    /**
     * @var mixed
     */
    protected $computed;

    /**
     *  Set or get computed property.
     *
     * @param string|array|Closure $computed Closure/callable (get's record as argument) or model attribute
     *
     * @throws InvalidArgumentException
     *
     * @return self|Closure computed Closure if param is null, chainable
     */
    public function computed($computed = null)
    {
        if (null === $computed) {
            return $this->computed;
        }

        if (is_array($computed) && is_callable($computed)) {
            $computed = Closure::fromCallable($computed);
        }

        if ($computed instanceof Closure) {
            $this->computed = $computed;

            return $this;
        }

        if (! is_string($computed)) {
            throw new InvalidArgumentException('Computed property should be \Closure or string');
        }

        $this->computed = function ($model) use ($computed) {
            return is_array($model) ? $model[$computed] : $model->{$computed};
        };

        return $this;
    }
}
