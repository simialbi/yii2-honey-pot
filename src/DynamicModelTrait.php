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

    /**
     * {@inheritDoc}
     */
    public function addRule($attributes, $validator, $options = []): static
    {
        /** @var $this DynamicModel */
        $validators = $this->getValidators();

        if ($validator instanceof Validator) {
            $validator->attributes = (array)$attributes;
        } else {
            $validator = Validator::createValidator($validator, $this, (array)$attributes, $options);
        }

        $this->handleValidator($validators, $validator);

        /**
         * @see DynamicModel::defineAttributesByValidator()
         */
        // $this->defineAttributesByValidator($validator);
        foreach ($validator->getAttributeNames() as $attribute) {
            if (!$this->hasAttribute($attribute)) {
                $this->defineAttribute($attribute);
            }
        }

        return $this;
    }
}
