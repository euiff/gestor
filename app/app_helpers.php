<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\UserClientPreference;

if (!function_exists('getAuthenticatedUser')) {
    function getAuthenticatedUser($idOnly = false)
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }

        if ($idOnly) {
            return $user->id;
        }
        return $user;
    }
}

if (!function_exists('getUserPreferences')) {
    function getUserPreferences($table, $column = 'visible_columns', $userId = null)
    {
        if ($userId === null) {
            $userId = getAuthenticatedUser(true); // Get user ID without prefix
        }
        Log::info('Fetching user preferences', ['user_id' => $userId, 'table' => $table, 'column' => $column]);
        
        $result = UserClientPreference::where('user_id', $userId)
            ->where('table_name', $table)
            ->first();
        
        Log::info('User preferences result', ['result' => $result]);
        
        if (!$result) {
            return [];
        }

        switch ($column) {
            case 'visible_columns':
                if ($result->visible_columns) {
                    return json_decode($result->visible_columns, true);
                }
                return [];
            case 'enabled_notifications':
                if ($result->enabled_notifications) {
                    return json_decode($result->enabled_notifications, true);
                }
                return [];
            default:
                return null;
        }
    }
}