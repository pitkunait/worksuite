<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class StorageSetting extends BaseModel
{
    protected $table = 'file_storage_settings';

    protected $fillable = ['filesystem','auth_keys','status'];
}
