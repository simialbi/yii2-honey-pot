<?php

namespace sandritsch91\yii2\honeypot;

use ArrayObject;
use Exception;
use yii\base\DynamicModel;
use yii\base\InvalidCallException;
use yii\base\Model;
use yii\base\UnknownPropertyException;
use yii\db\ActiveRecord;

/**
 * Trait ModelTrait
 */
trait ModelTrait
{
    abstract public function attributeLabels();
    abstract public function generateAttributeLabel($name);

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
                return;
            } elseif (in_array($name, array_keys($behavior->honeyPotValues))) {
                $behavior->honeyPotValues[$name] = $value;
                return;
            }
        }

        parent::__set($name, $value);
    }

    /**
     * {@inheritDoc}
     */
    public function getAttributeLabel($attribute): string
    {
        /** @var Model|DynamicModel|ActiveRecord|AntiSpamBehavior $this */
        $labels = $this->attributeLabels();

        if ($labels[$attribute] ?? false) {
            // If the attribute is in the attributeLabels array, return the label
            return $labels[$attribute];
        } elseif (in_array($attribute, $this->honeyPotAttributes ?? [])) {
            // If the attribute is a HoneyPot attribute, return the right label from the attributeLabels array
            $realAttribute = array_search($attribute, $this->honeyPotAttributes);
            return $labels[$realAttribute] ?? $this->generateAttributeLabel($realAttribute);
        } else {
            // call parent function
            return parent::getAttributeLabel($attribute);
        }
    }

    /**
     * {@inheritDoc}
     * @throws Exception
     */
    public function createValidators(): ArrayObject
    {
        /** @var Model|DynamicModel|ActiveRecord|AntiSpamBehavior $this */
        $validators = parent::createValidators();
        if (empty($this->findAsBehavior())) {
            return $validators;
        }
        return $this->antiSpamValidators($validators);
    }

    /**
     * @throws Exception
     */
    public function findAsBehavior(): ?AntiSpamBehavior
    {
        /** @var Model|DynamicModel|ActiveRecord|AntiSpamBehavior  $this */
        foreach ($this->getBehaviors() as $behavior) {
            if ($behavior instanceof AntiSpamBehavior) {
                return $behavior;
            }
        }
        return null;
    }
}
