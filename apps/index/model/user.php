<?php
namespace app\index\model;
use RC\Model\Think as ThinkModel;
use RC\Model\Laravel as LaravelModel;
class user extends ThinkModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $pk = 'id';

    protected $primaryKey = 'id';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    public function log()
    {
        return $this->hasOne('app\index\model\log','user_id');
    }

    
}