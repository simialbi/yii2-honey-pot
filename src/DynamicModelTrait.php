<?php

namespace sandritsch91\yii2\honeypot;

use ArrayObject;
use yii\base\DynamicModel;
use yii\validators\Validator;

/**
 * Trait DynamicModelTrait
 *
 * @method ArrayObject handleValidator(ArrayObject $validators, Validator $validator)
 */
trait DynamicModelTrait
{
    use ModelTrait;

    public function addRule(array|string $attributes, string|Validator|callable $validator, array $options = []): static
    {
        /** @var $this DynamicModel */
        $validators = $this->getValidators();

        if ($validator instanceof Validator) {
            $validator->attributes = (array)$attributes;
        } else {
            $validator = Validator::createValidator($validator, $this, (array)$attributes, $options);
        }

        $this->handleValidator($validators, $validator);
        $this->defineAttributesByValidator($validator);

        return $this;
    }
}
