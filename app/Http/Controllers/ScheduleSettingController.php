<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ScheduleSetting;
use Illuminate\Support\Facades\Auth;
use App\Models\PlanoRenovacao;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\User;
class ScheduleSettingController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $current_plan_id = $user->plano_id;
        $planos_revenda = PlanoRenovacao::all();

        return view('templates.manage-templates', compact('current_plan_id', 'planos_revenda', 'user'));
    }

        public function list(Request $request)
        {
            Log::info('Acessando a listagem de templates com paginação e busca.');
        
            try {
                if (Auth::check()) {
                    $user = Auth::user();
                    $userRole = $user->role->name;
        
                    $search = $request->input('search');
                    $sort = $request->input('sort', 'id');
                    $order = $request->input('order', 'DESC');
        
                    // Retorna apenas os dados do usuário logado
                    $settings = ScheduleSetting::where('user_id', $user->id);
        
                    if ($search) {
                        $settings = $settings->where(function ($query) use ($search) {
                            $query->where('finalidade', 'like', '%' . $search . '%')
                                  ->orWhere('execution_time', 'like', '%' . $search . '%');
                        });
                    }
        
                    $totalSettings = $settings->count();
        
                    $settings = $settings->orderBy($sort, $order)
                        ->paginate($request->input('limit', 10))
                        ->through(function ($setting) {
                            $actions = '<div class="d-grid gap-3">
                                            <div class="row g-3">
                                                <div class="col-6 mb-2">
                                                    <form action="' . route('schedule-settings.destroy', $setting->id) . '" method="POST" style="display:inline;" onsubmit="return confirm(\'Tem certeza que deseja deletar esta configuração?\');">
                                                        ' . csrf_field() . '
                                                        ' . method_field('DELETE') . '
                                                        <button type="submit" class="btn btn-sm btn-danger w-100" data-bs-toggle="tooltip" data-bs-placement="top" title="Deletar">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>';
        
                            $statusBadge = $setting->status == 'enviado' 
                                ? '<span class="badge bg-label-secondary me-1" style="background-color: green;">Enviado</span>'
                                : '<span class="badge bg-label-primary me-1" style="background-color: orange;">Pendente</span>';
        
                            return [
                                'id' => $setting->id,
                                'user_id' => $setting->user_id,
                                'finalidade' => $setting->finalidade,
                                'execution_time' => $setting->execution_time,
                                'status' => $statusBadge,
                                'created_at' => $setting->created_at->format('d/m/Y H:i:s'),
                                'updated_at' => $setting->updated_at->format('d/m/Y H:i:s'),
                                'actions' => $actions,
                            ];
                        });
        
                    // Fetch user preferences for visible columns
                    $userId = $user->id;
                    $preferences = DB::table('user_client_preferences')
                        ->where('user_id', $userId)
                        ->where('table_name', 'templates')
                        ->value('visible_columns');
        
                    $visibleColumns = json_decode($preferences, true) ?: [
                        'id',
                        'user_id',
                        'finalidade',
                        'execution_time',
                        'status',
                        'created_at',
                        'updated_at',
                        'actions',
                    ];
        
                    // Filter the columns based on user preferences
                    $filteredSettings = $settings->map(function ($setting) use ($visibleColumns) {
                        return array_filter($setting, function ($key) use ($visibleColumns) {
                            return in_array($key, $visibleColumns);
                        }, ARRAY_FILTER_USE_KEY);
                    });
        
                    // Adicionar dados adicionais que eram retornados no método index
                    $planos_revenda = PlanoRenovacao::all();
                    $current_plan_id = $user->plano_id;
                    $users = User::all();
        
                    return response()->json([
                        'rows' => $filteredSettings,
                        'total' => $totalSettings,
                        'settings' => $settings,
                        'planos_revenda' => $planos_revenda,
                        'current_plan_id' => $current_plan_id,
                        'users' => $users
                    ]);
                } else {
                    // Usuário não está autenticado
                    return response()->json(['error' => 'Usuário não autenticado'], 401);
                }
            } catch (\Exception $e) {
                Log::error('Erro ao acessar a listagem de templates: ' . $e->getMessage());
                return response()->json(['error' => 'Erro ao acessar a listagem de templates'], 500);
            }
        }
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'finalidade' => 'required|string',
            'execution_time' => 'required|date_format:H:i',
        ]);

        ScheduleSetting::updateOrCreate(
            [
                'user_id' => $request->user_id,
                'command' => 'clientes:verificar-vencidos',
                'finalidade' => $request->finalidade,
            ],
            [
                'execution_time' => $request->execution_time,
                'status' => 'pendente',
            ]
        );

        return redirect()->back()->with('success', 'Configuração de agendamento salva com sucesso.');
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $setting = ScheduleSetting::findOrFail($id);
        $setting->delete();

        return redirect()->back()->with('success', 'Configuração deletada com sucesso.');
    }

     public function destroyMultiple(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:schedule_settings,id',
        ]);
    
        ScheduleSetting::whereIn('id', $request->ids)->delete();
    
        return response()->json(['error' => false, 'message' => 'Configurações deletadas com sucesso.']);
    }
}