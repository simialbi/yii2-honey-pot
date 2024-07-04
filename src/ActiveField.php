<?php

namespace sandritsch91\yii2\honeypot;

use Yii;
use yii\bootstrap\ActiveField as b3ActiveField;
use yii\bootstrap\Html as b3Html;
use yii\bootstrap4\ActiveField as b4ActiveField;
use yii\bootstrap4\Html as b4Html;
use yii\bootstrap5\ActiveField as b5ActiveField;
use yii\bootstrap5\Html as b5Html;
use yii\helpers\Html;
use yii\widgets\ActiveField as yiiActiveField;


$bsVersion = Yii::$app->params['bsVersion'] ?? 4;

if ($bsVersion === 3) {
    /**
     * Class ActiveField
     */
    class ActiveField extends b3ActiveField
    {
        use ActiveFieldTrait;

        public function init(): void
        {
            parent::init();
            $this->htmlClass = new b3Html();
        }
    }
} elseif ($bsVersion === 4) {
    /**
     * Class ActiveField
     */
    class ActiveField extends b4ActiveField
    {
        use ActiveFieldTrait;

        public function init(): void
        {
            parent::init();
            $this->htmlClass = new b4Html();
        }
    }
} elseif ($bsVersion === 5) {
    /**
     * Class ActiveField
     */
    class ActiveField extends b5ActiveField
    {
        use ActiveFieldTrait;

        public function init(): void
        {
            parent::init();
            $this->htmlClass = new b5Html();
        }
    }
} else {
    /**
     * Class ActiveField
     */
    class ActiveField extends yiiActiveField
    {
        use ActiveFieldTrait;

        public function init(): void
        {
            parent::init();
            $this->htmlClass = new Html();
        }
    }
}
