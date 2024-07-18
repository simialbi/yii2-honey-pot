<?php

namespace sandritsch91\yii2\honeypot;

use ArrayObject;
use yii\base\Behavior;
use yii\base\Model;
use yii\validators\RequiredValidator;
use yii\validators\Validator;

/**
 * AntiSpamBehavior provides methods that simplify using and validating the AntiSpamInput widget
 */
class AntiSpamBehavior extends Behavior
{
    /**
     * @var bool Whether the model has spam
     */
    public bool $hasSpam = false;

    /**
     * @var array|string The name(s) of attribute(s) using the {HashInput} widget. This can be a comma string in the case of a
     * single attribute that use the default hash attribute name, an array where an element may be a string for an
     * attribute that uses the default hash attribute name, or a name=>value pair where `name` is the attribute name and
     * `value` is the corresponding hash attribute name
     */
    public string|array $hashAttributes = [];

    /**
     * @var array Value(s) of the {HashInput} widget fields as name=>value pairs where `name` is the hash attribute name
     * and `value` its value
     * @internal
     */
    public array $hashValues = [];

    /**
     * @var array|string The name(s) of attribute(s) using the {HoneyPotInput} widget. This can be a string in the case
     * of a single attribute that use the default hash attribute name, an array where an element may be a string for an
     * attribute that uses the default honeypot attribute name, or a name=>value pair where `name` is the attribute name
     * and `value` is the corresponding honeypot attribute name
     */
    public string|array $honeyPotAttributes = [];

    /**
     * @var array Value(s) of the {HoneyPotInput} widget fields as name=>value pairs where `name` is the honeyPot
     * attribute name and `value` its value
     * @internal
     */
    public array $honeyPotValues = [];


    /**
     * @inheritdoc
     */
    public function init(): void
    {
        $this->normaliseAttributes('hash');
        $this->normaliseAttributes('honeyPot');
    }


    /**
     * @inheritdoc
     */
    public function events(): array
    {
        return [
            Model::EVENT_AFTER_VALIDATE => function () {
                // Set the real attributes with their HoneyPot attribute values
                foreach ($this->honeyPotAttributes as $attribute => $honeyPotAttribute) {
                    $this->owner->$attribute = $this->owner->$honeyPotAttribute;
                }
            }
        ];
    }

    /**
     * Edit validators for honeypot and hash attributes
     *
     * - Edit existing validators with honeypot attributes
     * - Add safe, validateHash and validateHoneyPot validators for the honeypot and hash attributes
     *
     * @param ArrayObject $validators
     * @return ArrayObject
     */
    public function antiSpamValidators(\ArrayObject $validators): ArrayObject
    {
        $newValidators = new ArrayObject();
        foreach ($validators as $validator) {
            $newValidators = $this->handleValidator($newValidators, $validator);
        }

        // add new validation rules for honeyPot and hash attributes
        $newValidators[] = Validator::createValidator('safe', $this, array_values($this->hashAttributes));
        $newValidators[] = Validator::createValidator('ValidateHash', $this, array_keys($this->hashAttributes));
        $newValidators[] = Validator::createValidator('ValidateHoneyPot', $this, array_keys($this->honeyPotAttributes));

        return $newValidators;
    }

    /**
     * Edit and create new validator, if it contains honeyPot attributes
     * @param ArrayObject $newValidators
     * @param Validator $validator
     * @return ArrayObject
     * @internal
     */
    public function handleValidator(ArrayObject $newValidators, Validator $validator): ArrayObject
    {
        foreach ($this->honeyPotAttributes as $attribute => $honeyPotAttribute) {
            $i = array_search($attribute, $validator->attributes);
            if ($i !== false) {
                // honeyPot attribute found in validator attributes
                // replace it with the honeyPot attribute
                // add a new validator for the real attribute, except if the original validator is a RequiredValidator

                $newValidator = clone $validator;
                $validator->attributes[$i] = $honeyPotAttribute;

                // add new validator, except if the original validator is a RequiredValidator
                if (!$validator instanceof RequiredValidator) {
                    $newValidator->attributes = [$attribute];
                    $newValidator->enableClientValidation = false;
                    $newValidators->append($newValidator);
                }
            }
        }
        $newValidators->append($validator);
        return $newValidators;
    }

    /**
     * Validates a Hash {AntiSpamInput} widget attribute and sets the hasSpam property if the attribute is invalid
     * @param string $attribute the name of the attribute to be validated
     * @see hasSpam
     */
    public function validateHash(string $attribute): void
    {
        $hashAttribute = $this->hashAttributes[$attribute];
        $value = preg_replace('#\s#', '', $this->owner->$attribute);
        if (md5($value) !== $this->owner->$hashAttribute) {
            $this->hasSpam = true;
        }
    }

    /**
     * Validates a Honey Pot {AntiSpamInput} widget attribute and sets the hasSpam property if the attribute is invalid
     * @param string $attribute the name of the attribute to be validated
     * @see hasSpam
     */
    public function validateHoneyPot(string $attribute): void
    {
        if (strlen($this->owner->$attribute) > 0) {
            $this->hasSpam = true;
        }
    }

    private function normaliseAttributes(string $for): void
    {
        $_attributes = [];
        $attributes = $for . 'Attributes';
        $values = $for . 'Values';

        if (is_string($this->$attributes)) {
            $this->$attributes = [$this->$attributes];
        }

        foreach ($this->$attributes as $k => $v) {
            if (is_int($k)) {
                $attribute = md5($v);
                $_attributes[$v] = $attribute;
                $this->$values[$attribute] = null;
            } else {
                $_attributes[$k] = $v;
                $this->$values[$v] = null;
            }
        }
        $this->$attributes = $_attributes;
    }
}
