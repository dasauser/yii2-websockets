<?php

namespace app\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * @property int $id
 * @property string $token
 * @property int $user_id
 * @property string $user_agent
 * @property string $opened_at
 * @property string $closed_at
 * @property bool $active
 */
class Connection extends ActiveRecord
{
    const SCENARIO_OPEN = 'open';
    const SCENARIO_CLOSE = 'close';

    public static function tableName()
    {
        return '{{%connections}}';
    }

    public function scenarios()
    {
        return [
            static::SCENARIO_DEFAULT => ['token', 'user_id', 'user_agent', 'active', 'opened_at'],
            static::SCENARIO_OPEN => ['token', 'user_id', 'user_agent', 'active', 'opened_at'],
            static::SCENARIO_CLOSE => ['token', 'user_id', 'user_agent', 'active', 'closed_at'],
        ];
    }

    public function rules()
    {
        return [
            [['user_id'], 'integer', 'min' => 1],
            [['token', 'user_agent'], 'string', 'max' => 255],
            [['opened_at', 'closed_at'], 'datetime'],
            [['active'], 'boolean'],
        ];
    }

    public function beforeSave($insert)
    {
        if ($this->getScenario() === static::SCENARIO_OPEN) {
            $this->opened_at = new Expression('NOW()');
            $this->active = true;
        }
        if ($this->getScenario() === static::SCENARIO_CLOSE) {
            $this->closed_at = new Expression('NOW()');
            $this->active = false;
        }
        return parent::beforeSave($insert);
    }
}