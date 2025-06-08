<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log; // Import Log facade
use App\Models\UserClientPreference;

class PreferenceController extends Controller
{
    public function index()
    {
        return view('settings.preferences');
    }

    public function saveColumnVisibility(Request $request)
    {
        // Log the incoming request data
        Log::info('saveColumnVisibility request data: ', $request->all());

        // Validate incoming request data
        $validator = Validator::make($request->all(), [
            'table' => 'required|string|max:255',
            'visible_columns' => 'required|array' // assuming visible_columns is an array
        ]);

        if ($validator->fails()) {
            // Log::error('Validation failed: ', $validator->errors()->toArray());
            return response()->json(['error' => true, 'message' => $validator->errors()->first()], 422);
        }

        try {
            // Get the authenticated user's ID
            $userId = getAuthenticatedUser(true);
            Log::info('Authenticated user ID: ' . $userId);

            // Get the table type and visible columns from the request
            $table = $request->input('table');
            $visibleColumns = json_encode($request->input('visible_columns'));
            Log::info('Table: ' . $table);
            Log::info('Visible Columns: ' . $visibleColumns);

            // Update or insert the column visibility preferences
            UserClientPreference::updateOrInsert(
                ['user_id' => $userId, 'table_name' => $table],
                ['visible_columns' => $visibleColumns]
            );

            Log::info('Column visibility preferences saved successfully.');
            return response()->json(['error' => false, 'message' => 'Visibilidade das colunas salva com sucesso.']);

        } catch (\Exception $e) {
            Log::error('Error saving column visibility: ' . $e->getMessage());
            return response()->json(['error' => true, 'message' => 'Ocorreu um erro ao salvar a visibilidade das colunas.'], 500);
        }
    }

    public function getPreferences(Request $request)
    {
        $userId = getAuthenticatedUser(true);
        $tableName = $request->input('table_name');

        // Fetch preferences from database
        $preferences = UserClientPreference::where('user_id', $userId)
            ->where('table_name', $tableName)
            ->first();

        if ($preferences) {
            return response()->json(['fields' => json_decode($preferences->visible_columns)]);
        }

        return response()->json(['fields' => []]);
    }

    public function saveNotificationPreferences(Request $request)
    {
        try {
            // Get the authenticated user's ID
            $userId = getAuthenticatedUser(true);
            $enabledNotifications = $request->has('enabled_notifications') ? json_encode($request->input('enabled_notifications')) : null;

            UserClientPreference::updateOrInsert(
                ['user_id' => $userId, 'table_name' => 'notification_preference'],
                ['enabled_notifications' => $enabledNotifications]
            );

            return response()->json(['error' => false, 'message' => 'Preferências de notificação salvas com sucesso.']);
        } catch (\Exception $e) {
            return response()->json(['error' => true, 'message' => 'Ocorreu um erro ao salvar as preferências de notificação: ' . $e->getMessage()], 500);
        }
    }
}