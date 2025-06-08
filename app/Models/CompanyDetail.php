<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class CompanyDetail extends Model
{
    use HasFactory;

    protected $table = 'company_details';

    protected $fillable = [
        'user_id',
        'company_name',
        'company_whatsapp',
        'access_token',
        'company_logo',
        'company_logo_dark',
        'company_logo_light',
        'pix_manual',
        'referral_balance',
        'api_session',
        'public_key', 
        'site_id',
        'evolution_api_url',
        'evolution_api_key',
        'api_version',
        'not_gateway',
        'notification_url',
        'favicon',
        'qpanel_api_url',
        'qpanel_api_key' 
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function setQpanelApiUrlAttribute($value)
    {
        $this->attributes['qpanel_api_url'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getQpanelApiUrlAttribute($value)
    {
        try {
            return $value ? Crypt::decryptString($value) : null;
        } catch (\Exception $e) {
            return $value;
        }
    }

    public function setQpanelApiKeyAttribute($value)
    {
        if ($value !== null && $value !== '') {
            $this->attributes['qpanel_api_key'] = Crypt::encryptString($value);
        }
    }

    public function getQpanelApiKeyAttribute($value)
    {
        try {
            return $value ? Crypt::decryptString($value) : null;
        } catch (\Exception $e) {
            return $value; 
        }
    }

}