<?php

namespace sandritsch91\yii2\honeypot;

use ArrayObject;
use Exception;
use yii\base\InvalidCallException;
use yii\base\UnknownPropertyException;

/**
 * Class Model
 *
 * @property-read bool $hasSpam
 * @property array hashAttributes
 * @property array hashValues
 * @property array honeyPotAttributes
 * @property array honeyPotValues
 * @method ArrayObject antiSpamValidators(ArrayObject $validators)
 */
class Model extends \yii\base\Model
{
    /**
     * Returns the value of a HashInput attribute, HoneyPotInput attribute, or an object property
     * @param string $name the name
     * @return mixed the value of a HashInput attribute, HoneyPotInput attribute, or an object property
     * @throws UnknownPropertyException if the property is not defined
     * @throws Exception
     * @see __set()
     */
    public function __get($name)
    {
        $behavior = $this->findAsBehavior();

        if (!empty($behavior)) {
            if (in_array($name, array_keys($behavior->hashValues))) {
                return $behavior->hashValues[$name];
            } elseif (in_array($name, array_keys($behavior->honeyPotValues))) {
                return $behavior->honeyPotValues[$name];
            }
        }

        return parent::__get($name);
    }

    /**
     * Sets the value of a HashInput attribute, HoneyPotInput attribute, or an object property
     * @param string $name the name
     * @throws UnknownPropertyException if the property is not defined
     * @throws InvalidCallException if the property is write-only
     * @throws Exception
     * @see __get()
     */
    public function __set($name, $value)
    {
        $behavior = $this->findAsBehavior();

        if (isset($behavior)) {
            if (in_array($name, array_keys($behavior->hashValues))) {
                $behavior->hashValues[$name] = $value;
            } elseif (in_array($name, array_keys($behavior->honeyPotValues))) {
                $behavior->honeyPotValues[$name] = $value;
            }
        } else {
            parent::__set($name, $value);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getAttributeLabel($attribute): string
    {
        $labels = $this->attributeLabels();

        if ($labels[$attribute] ?? false) {
            // If the attribute is in the attributeLabels array, return the label
            return $labels[$attribute];
        } elseif (in_array($attribute, $this->honeyPotAttributes ?? [])) {
            // If the attribute is a HoneyPot attribute, return the right label from the attributeLabels array
            $realAttribute = array_search($attribute, $this->honeyPotAttributes);
            return $labels[$realAttribute] ?? $this->generateAttributeLabel($realAttribute);
        } else {
            // Otherwise, return the default label
            return $this->generateAttributeLabel($attribute);
        }
    }

    /**
     * {@inheritDoc}
     * @throws Exception
     */
    public function createValidators(): ArrayObject
    {
        $validators = parent::createValidators();
        if (empty($this->findAsBehavior())) {
            return $validators;
        }
        return $this->antiSpamValidators($validators);
    }

    /**
     * @throws Exception
     */
    protected function findAsBehavior(): ?AntiSpamBehavior
    {
        foreach ($this->getBehaviors() as $behavior) {
            if ($behavior instanceof AntiSpamBehavior) {
                return $behavior;
            }
        }
        return null;
    }
}
