<?php
namespace Power\Models;
use PowerModelBase as PMB;
/**
 * 基础模型
 * @author vanni.fan
 */
class Logs extends PMB{
    public function initialize(){
        parent::initialize();
        $this->hasOne(
            'user_id',
            Users::class,
            'user_id',
            ['alias' => 'User']
        );
    }
}