# AntiSpam Extension for Yii 2

This extension provides form anti-spam features for the [Yii 2.0 framework](http://www.yiiframework.com).

For license information check the [LICENSE](LICENSE) file.

Documentation is at [docs/index.md](docs/index.md).

## Copyright

This extension was inspired by [BeastBytes/yii2-anti-spam](https://github.com/beastbytes/yii2-anti-spam)

BeastBytes' extension relies on modifications to the view files to work. If a lot of existing forms are to be modified,
this can be a lot of work. This extension aims to provide the same functionality without the need to modify the view
files.

To achieve this, this extension uses a custom ActiveField and a custom Model class that extends the default
ActiveField / Model class. The custom ActiveField class adds the necessary hidden fields to the form and the custom
Model class handles the attributes / validation

If extending the default ActiveField / Model class is not an option, BeastBytes' original extension should be used.

## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
composer require --prefer-dist sandritsch91/yii2-honey-pot
```

or add

```json
"sandritsch91/yii2-honey-pot": "^1.0"
```

to the `require` section of your composer.json.
