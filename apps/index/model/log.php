<?php
namespace app\index\model;
use RC\Model\Think as ThinkModel;
use RC\Model\Laravel as LaravelModel;
class log extends ThinkModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'log';

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


    public function user()
    {
        return $this->belongsTo('app\index\model\user','id');
    }

    
}
?>