<?php

namespace sandritsch91\yii2\honeypot;

use yii\web\AssetBundle;

class HashAsset extends AssetBundle
{
    public $sourcePath = '@vendor/sandritsch91/yii2-honey-pot/src/assets';

    public $js = [
        'md5-min.js'
    ];

    public $publishOptions = [
        'forceCopy' => YII_DEBUG
    ];
}
