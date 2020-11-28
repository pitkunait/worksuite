<?php

namespace App;

use App\Traits\CustomFieldsTrait;
use Illuminate\Database\Eloquent\Model;

class ClientDetails extends BaseModel
{
    use CustomFieldsTrait;

    protected $fillable = ['company_name', 'user_id', 'address', 'postal_code','country','state','city','office','cell','website', 'note', 'skype', 'facebook', 'twitter', 'linkedin', 'gst_number', 'shipping_address'];

    protected $default = ['id', 'company_name', 'address', 'website', 'note', 'skype', 'facebook', 'twitter', 'linkedin', 'gst_number', 'name', 'email'];

    protected $table = 'client_details';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id')->withoutGlobalScopes(['active']);
    }
}
