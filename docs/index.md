# AntiSpam Extension for Yii 2

This extension provides form anti-spam features for the [Yii 2.0 framework](http://www.yiiframework.com).

## Features

* Two methods of detecting a form submitted by a spam-bot: Hash and Honey Pot
* No need to declare additional attributes, attribute labels, or rules in your model
* `hasSpam` attribute added to your model (by the behavior) to determine whether a form has been submitted by a spam-bot

## Using the AntiSpam Extension

### How It Works

The AntiSpam extension provides two methods of determining whether a form has been submitted by a spam-bot:

1. Hash:
   When applied to a model attribute, a hidden input is created that receives the MD5 hash of the input value on the
   blur event for the real input. Spam-bots to do not trigger input events, so when the form is validated, if the
   content of _hash_ input does not equal the MD5 hash of the field value the form has been submitted by a spam-bot.
2. Honey Pot:
   Spam-bots look for "standard" field names, e.g. email, and/or complete all fields on a form. When applied to a model
   attribute, an additional input is created; this field receives the name of the attribute and hidden by CSS so that it
   cannot be completed by a human but can be by a spam-bot. Therefore, when the form is validated if the _honey pot_
   field contains a value the form has been submitted by a spam-bot. A separate input is created for a human to
   complete; the value in this field is copied to the actual attribute after validation so that your code only ever uses
   the real attribute.

**There are few things to note:**

* Both Hash and Honey Pot _must_ be applied to attributes that generate text inputs; they _**can not**_ be used on other
  types of controls, e.g. select, checkbox, radio, etc. Although some widgets (MaskedInput, Flatpickr) are supported. If
  you need another widget to be supported, please open an issue.
* Hash and Honey Pot are independent of each other, i.e. you do not have to use both of them, though using both on a
  form gives the best protection
* Hash and Honey Pot _**must not**_ be applied to the same attribute
* You can have more than one Hash and/or Honey Pot field in the same form

### Usage

There are a number of steps to use the extension:

#### In the Model

* Add the custom ActiveField class to your config;

```php
'container' => [
    'definitions' => [
        // choose one of the following
        \yii\widgets\ActiveField::class => sandritsch91\yii2\honeypot\ActiveField::class
        \yii\bootstrap\ActiveField::class => sandritsch91\yii2\honeypot\ActiveField::class
        \yii\bootstrap4\ActiveField::class => sandritsch91\yii2\honeypot\ActiveField::class
        \yii\bootstrap5\ActiveField::class => sandritsch91\yii2\honeypot\ActiveField::class
    ]
]
```

* Use the traits in your Model / DynamicModel / ActiveRecord

```php
use sandritsch91\yii2\honeypot\ModelTrait;

class MyForm extends \yii\base\Model
{
    use ModelTrait;
    // ...
}
```

```php
use sandritsch91\yii2\honeypot\DynamicModelTrait;

class MyDynamicModel extends \yii\base\DynamicModel
{
    use DynamicModelTrait;
    // ...
}
```

```php
use sandritsch91\yii2\honeypot\ModelTrait;

class MyForm extends \yii\base\ActiveRecord
{
    use ModelTrait;
    // ...
}
```

* Attach the AntiSpam behavior to the model; this adds attributes, methods, and an event handler to the model.

```php
public function behaviors()
{
  return [
    // other behaviors
    [
      'class' => \sandritsch91\yii2\honeypot\AntiSpamBehavior::class,
      'hashAttributes' => ['hashAttribute1', 'hashAttribute', ... 'hashAttributeN'],
      'honeyPotAttributes' => ['honeyPotAttribute1', 'honeyPotAttribute', ... 'honeyPotAttributeN']
    ]
  ];
}
```

`hashAttributes` and `honeyPotAttributes` can be given as:

* a string for a single attribute
* an array of strings for multiple attributes
* an array of key=>value pairs, where `key` is the attribute name and `value` is its anti-spam attribute name

Anti-spam attributes will be automatically named if the name is not defined; the name will be the reverse string of the
MD5 hash of the attribute name.

##### A minimal example for a simple form:

```php   
use yii\base\Model;
use sandritsch91\yii2\honeypot\AntiSpamBehavior;
use sandritsch91\yii2\honeypot\ModelTrait;

class SimpleForm extends Model
{ 
  use ModelTrait;

  public $name;
  public $email;
   
  public function behaviors()
  {
    return [
      [
        'class' => AntiSpamBehavior::class,
        'hashAttributes' => 'name',
        'honeyPotAttributes' => 'email'
      ]
    ];
  }
}
```

##### A minimal example for a DynamicModel:

```php
use sandritsch91\yii2\honeypot\AntiSpamBehavior;
use sandritsch91\yii2\honeypot\DynamicModelTrait;
use yii\base\DynamicModel;

class DynamicModel extends DynamicModel
{
  use DynamicModelTrait;

  public function behaviors()
  {
    return [
      [
        'class' => AntiSpamBehavior::class,
        'hashAttributes' => 'username',
        'honeyPotAttributes' => 'email'
      ]
    ];
  }
}
```

##### A minimal example for an ActiveRecord model:

```php
use sandritsch91\yii2\honeypot\AntiSpamBehavior;
use sandritsch91\yii2\honeypot\ModelTrait;
use yii\db\ActiveRecord;

class User extends ActiveRecord
{
  use ModelTrait;

  public function behaviors()
  {
    return [
      [
        'class' => AntiSpamBehavior::class,
        'hashAttributes' => 'username',
        'honeyPotAttributes' => 'email'
      ]
    ];
  }
}
```

#### Determine if the Form has been Submitted by a Spam Bot

Examine the model's `hasSpam` attribute (added by AntiSpamBehavior) to determining whether the form has been submitted
by a spam-bot.

##### In a Controller or View

```php
if ($model->hasSpam) {
  // form completed by spam bot
} else {
  // form is OK
}
```

##### In the Model

If your form collects data that will be saved to a database:

```php
public function beforeSave($insert)
{
  if (!parent::beforeSave($insert)) {
    return false;
  }
   
  return !$this->hasSpam; // Don't save if the form has spam
}
```

### Supported Widgets

- [x] \yii\widgets\MaskedInput
- [x] \sandritsch91\yii2\flatpickr\Flatpickr
- [x] \kartik\password\PasswordInput (Hash only)
