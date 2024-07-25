<?php

namespace sandritsch91\yii2\honeypot;

use Exception;
use kartik\password\PasswordInput;
use sandritsch91\yii2\flatpickr\Flatpickr;
use yii\base\InvalidArgumentException;
use yii\bootstrap\ActiveField as b3ActiveField;
use yii\bootstrap\Html as b3Html;
use yii\bootstrap4\ActiveField as b4ActiveField;
use yii\bootstrap4\Html as b4Html;
use yii\bootstrap5\ActiveField as b5ActiveField;
use yii\bootstrap5\Html as b5Html;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\widgets\ActiveField as yiiActiveField;
use yii\helpers\Inflector;
use yii\web\View;
use yii\widgets\MaskedInput;

/**
 * ActiveFieldTrait
 * @property string $antiSpamAttribute
 */
trait ActiveFieldTrait
{
    /** @var b3Html|b4Html|b5Html|Html */
    public readonly b3Html|b4Html|b5Html|Html $htmlClass;

    /**
     * {@inheritDoc}
     * @throws Exception
     */
    public function input($type, $options = []): yiiActiveField|b3ActiveField|b4ActiveField|b5ActiveField
    {
        if (method_exists($this, strtolower($type) . 'Input')) {
            return $this->{strtolower($type) . 'Input'}($options);
        }
        throw new InvalidArgumentException('Invalid input type: ' . $type);
    }

    /**
     * {@inheritDoc}
     * @throws Exception
     */
    public function textInput($options = []): yiiActiveField|b3ActiveField|b4ActiveField|b5ActiveField
    {
        return $this->renderFields($options, __METHOD__);
    }

    /**
     * {@inheritDoc}
     * @throws Exception
     */
    public function textArea($options = []): yiiActiveField|b3ActiveField|b4ActiveField|b5ActiveField
    {
        return $this->renderFields($options, __METHOD__);
    }

    /**
     * {@inheritDoc}
     * @throws Exception
     */
    public function passwordInput($options = []): yiiActiveField|b3ActiveField|b4ActiveField|b5ActiveField
    {
        return $this->renderFields($options, __METHOD__);
    }

    /**
     * {@inheritDoc}
     */
    public function widget($class, $config = []): static
    {
        $field = parent::widget($class, $config);

        $classes = [
            MaskedInput::class,
            Flatpickr::class,
            PasswordInput::class,
        ];

        if (!$this->isHash() && !$this->isHoneyPot()) {
            return $field;
        }

        if (!in_array($class, $classes)) {
            return $field;
        }


        $options = [];
        $id = Html::getInputId($this->model, $this->antiSpamAttribute);
        $method = 'input';
        $type = 'text';
        $js = '';

        if ($class === MaskedInput::class) {
            if ($this->isHoneyPot()) {
                $key = 'plugin-inputmask';
                preg_match("#($key=\")(\S*)(\")#", $field->parts['{input}'], $matches);
                $var = $matches[2] ?? null;
                if ($var) {
                    $options = ArrayHelper::merge(
                        $config['clientOptions'] ?? [],
                        ['pluginOptions' => $var]
                    );
                    $js = <<<JS
jQuery('#{$this->htmlClass::getInputId($this->model, $this->attribute)}').inputmask('remove');
jQuery('#{$id}').inputmask($var);
JS;
                }
            }
        }

        if ($class === Flatpickr::class) {
            if ($this->isHoneyPot()) {
                $var = Inflector::variablize($id);
                $js = <<<JS
var elem_{$var} = jQuery('#{$this->getInputId()}');
var options_{$var} = elem_{$var}.get(0)._flatpickr.config;
delete options_{$var}['disable'];
elem_{$var}.get(0)._flatpickr.destroy();
flatpickr('#{$id}', options_{$var});
JS;
            }
        }

        if ($class === PasswordInput::class) {
            $type = 'password';
            if ($this->isHoneyPot()) {
                \Yii::error('PasswordInput is not supported for honey pot fields');
            }
        }

        $this->renderFields($options, $method, $type);
        \Yii::$app->view->registerJs($js);

        return $field;
    }

    /**
     * Handles the rendering of the email input field
     * @throws Exception
     */
    protected function emailInput($options = []): yiiActiveField|b3ActiveField|b4ActiveField|b5ActiveField
    {
        return $this->renderFields($options, 'input', 'email');
    }

    /**
     * Handles the rendering of the number input field
     * @throws Exception
     */
    protected function numberInput($options = []): yiiActiveField|b3ActiveField|b4ActiveField|b5ActiveField
    {
        return $this->renderFields($options, 'input', 'number');
    }

    /**
     * Render the input fields for the attribute and the AntiSpam attribute
     * @param array $options
     * @param string $method the method name for the visible field
     * @param string $type the input type, if parent class has no matching method
     * @return yiiActiveField|b3ActiveField|b4ActiveField|b5ActiveField
     * @throws Exception
     */
    protected function renderFields(
        array $options,
        string $method,
        string $type = ''
    ): yiiActiveField|b3ActiveField|b4ActiveField|b5ActiveField {
        if (empty($type)) {
            $method = explode('::', $method)[1];
            $field = parent::$method($options);
        } else {
            $field = parent::$method($type, $options);
        }
        $method = 'active' . ucfirst($method);

        $this->inputOptions = ArrayHelper::merge($this->inputOptions, $options);

        if ($this->isHoneyPot()) {

            // Move the value of the attribute to the AntiSpam attribute
            if ($value = $this->model->{$this->attribute}) {
                $this->model->{$this->antiSpamAttribute} = $value;
                $this->model->{$this->attribute} = '';
            }

            if (empty($type)) {
                $parts = $this->htmlClass::$method($this->model, $this->antiSpamAttribute, $this->inputOptions);
            } else {
                $parts = $this->htmlClass::$method($type, $this->model, $this->antiSpamAttribute, $this->inputOptions);
            }

            $field->parts['{input}'] = $parts .
                $this->htmlClass::activeTextInput($this->model, $this->attribute, [
                    'tabindex' => -1,
                    'autocomplete' => 'nope',
                ]);

            // client side validation
            if ($this->form->enableClientScript) {
                $attribute = $this->attribute;
                $this->attribute = $this->antiSpamAttribute;

                $clientOptions = $this->getClientOptions();
                if (!empty($clientOptions)) {
                    $this->form->attributes[] = $clientOptions;
                }

                $this->attribute = $attribute;
            }

            $this->registerClientScript();
        }

        if ($this->isHash()) {
            if (empty($type)) {
                $parts = $this->htmlClass::$method($this->model, $this->attribute, $this->inputOptions);
            } else {
                $parts = $this->htmlClass::$method($type, $this->model, $this->attribute, $this->inputOptions);
            }
            $field->parts['{input}'] = $parts .
                $this->htmlClass::activeHiddenInput($this->model, $this->antiSpamAttribute);

            $this->registerClientScript();
        }

        return $field;
    }

    /**
     * Returns the attribute for the AntiSpam input
     * @throws Exception
     */
    protected function getAntiSpamAttribute()
    {
        /** @var AntiSpamBehavior|ModelTrait $model */
        $model = $this->model;
        return $model->honeyPotAttributes[$this->attribute] ?? $model->hashAttributes[$this->attribute];
    }

    /**
     * @return bool
     * @throws Exception
     */
    protected function isHoneyPot(): bool
    {
        /** @var AntiSpamBehavior|ModelTrait $model */
        $model = $this->model;
        return in_array($this->attribute, array_keys($model->findAsBehavior()?->honeyPotAttributes ?? []));
    }

    /**
     * @return bool
     * @throws Exception
     */
    protected function isHash(): bool
    {
        /** @var AntiSpamBehavior|ModelTrait $model */
        $model = $this->model;
        return in_array($this->attribute, array_keys($model->findAsBehavior()?->hashAttributes ?? []));
    }

    /**
     * @throws Exception
     */
    protected function registerClientScript(): void
    {
        if ($this->isHash()) {
            HashAsset::register($this->form->getView());

            $attributeId = $this->htmlClass::getInputId($this->model, $this->attribute);
            $hashAttributeId = $this->htmlClass::getInputId($this->model, $this->antiSpamAttribute);

            $js = <<<JS
document.getElementById('$attributeId').onblur=function() {
    let value = document.getElementById('$attributeId').value.replace(/\s/g, '');
    document.getElementById('$hashAttributeId').value = hex_md5(value);
};
JS;

            $this->form->getView()->registerJs($js);
        }

        if ($this->isHoneyPot()) {
            $inputId = $this->inputOptions['id'] ?? $this->htmlClass::getInputId($this->model,
                $this->antiSpamAttribute);

            $classOptions = $this->options['class'] ?? [];
            if (is_string($classOptions)) {
                $classOptions = explode(' ', $classOptions);
            }

            $class = array_merge($classOptions, [
                "field-$inputId",
                $this->form->requiredCssClass
            ]);
            $class = join(' ', $class);

            $view = $this->form->getView();
            $id = $this->htmlClass::getInputId($this->model, $this->attribute);
            $var = Inflector::variablize($id);

            $css = <<< CSS
#$id {
    border: none;
    bottom: 0;
    height: 0;
    position: absolute;
    right: 0;
    width: 0;
    z-index: -10;
}
CSS;
            $view->registerCss($css);

            $js = <<<JS
var {$var}_as = document.getElementById('$id').parentElement;
{$var}_as.className = '$class';
var label = {$var}_as.getElementsByTagName('label').item(0);
if (label) {
    label.setAttribute('for', '$inputId');
}
JS;
            $view->registerJs($js, View::POS_END);
        }
    }
}
