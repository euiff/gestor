<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Revenda;
use App\Models\User;
use App\Models\PlanoRenovacao;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Log;

class RevendaController extends Controller
{
public function __construct()
{
  $this->middleware('auth');

  // verifica se o usuário logado tem role_id igual a 1
  $this->middleware(function ($request, $next) {
    $user = Auth::user();
    if ($user->role_id !== 1) {
      return redirect('/app/ecommerce/dashboard')->with('error', 'Você não tem permissão para acessar esta página.');
    }
    return $next($request);
  });
}


public function index()
{
  $user = Auth::user();
  $planos_revenda = PlanoRenovacao::all();
  $current_plan_id = $user->plano_id;
  $rendas_creditos = Revenda::all();
  return view('revenda.index', compact('user', 'planos_revenda', 'current_plan_id', 'rendas_creditos'));
}


public function list(Request $request)
{
  Log::info('Acessando a listagem de créditos com paginação e busca.');

  try {
    if (Auth::check()) {
      $user = Auth::user();
      $userRole = $user->role->name;

      $search = $request->input('search');
      $sort = $request->input('sort', 'id');
      $order = $request->input('order', 'DESC');

      // Verifica se o usuário é um administrador
      if ($userRole === 'admin') {
        $filter = $request->input('filter', 'all');

        if ($filter == 'mine') {
          // Mostrar apenas os créditos do administrador
          $creditos = Revenda::where('user_id', $user->id);
        } else {
          // Mostrar todos os créditos
          $creditos = Revenda::query();
        }
      } else {
        // Retorna apenas os dados do usuário logado se não for administrador
        $creditos = Revenda::where('user_id', $user->id);
      }

      if ($search) {
        $creditos = $creditos->where('nome', 'like', '%' . $search . '%');
      }

      $totalCreditos = $creditos->count();
      $canEdit = true; // Defina a lógica para verificar se o usuário pode editar
      $canDelete = true; // Defina a lógica para verificar se o usuário pode deletar


      $creditos = $creditos->orderBy($sort, $order)
        ->paginate($request->input('limit', 10))
        ->through(function ($credito) use ($canEdit, $canDelete) {
          $actions = '<div class="d-grid gap-3">
                                                      <div class="row g-3">
                                                          <div class="col-6 mb-2">
                                                              <button class="btn btn-sm btn-primary w-100" data-bs-toggle="modal" data-bs-target="#editCreditoModal' . $credito->id . '" data-bs-toggle="tooltip" data-bs-placement="top" title="Editar">
                                                                  <i class="fas fa-edit"></i>
                                                              </button>
                                                          </div>
                                                          <div class="col-6 mb-2">
                                                              <form action="' . route('revenda.destroy', $credito->id) . '" method="POST" style="display:inline;">
                                                                  ' . csrf_field() . '
                                                                  ' . method_field('DELETE') . '
                                                                  <button type="submit" class="btn btn-sm btn-danger w-100" data-bs-toggle="tooltip" data-bs-placement="top" title="Deletar">
                                                                      <i class="fas fa-trash-alt"></i>
                                                                  </button>
                                                              </form>
                                                          </div>
                                                      </div>
                                                  </div>';

          $modal = '<div class="modal fade" id="editCreditoModal' . $credito->id . '" tabindex="-1" aria-hidden="true">
                                                  <div class="modal-dialog modal-lg modal-simple modal-edit-credito">
                                                      <div class="modal-content p-3 p-md-5">
                                                          <div class="modal-body">
                                                              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                              <div class="text-center mb-4">
                                                                  <h3 class="mb-2">Editar Plano de Créditos</h3>
                                                                  <p class="text-muted">Atualize os detalhes do plano de créditos.</p>
                                                              </div>
                                                              <form id="editCreditoForm' . $credito->id . '" class="row g-3" action="' . route('revenda.update', $credito->id) . '" method="POST">
                                                                  ' . csrf_field() . '
                                                                  ' . method_field('PUT') . '
                                                                  <div class="col-12">
                                                                      <label class="form-label" for="editPlanoCreditos' . $credito->id . '">Nome do Plano</label>
                                                                      <input type="text" id="editPlanoCreditos' . $credito->id . '" name="nome" class="form-control" value="' . $credito->nome . '" required />
                                                                  </div>
                                                                  <div class="col-12">
                                                                      <label class="form-label" for="editPlanoCreditos' . $credito->id . '">Quantidade de Créditos</label>
                                                                      <input type="number" id="editPlanoCreditos' . $credito->id . '" name="creditos" class="form-control" value="' . $credito->creditos . '" required />
                                                                  </div>
                                                                  <div class="col-12">
                                                                      <label class="form-label" for="editPlanoPreco' . $credito->id . '">Preço por Crédito</label>
                                                                      <input type="number" step="0.01" id="editPlanoPreco' . $credito->id . '" name="preco" class="form-control" value="' . number_format((float) $credito->preco, 2, '.', '') . '" required />
                                                                  </div>
                                                                  <div class="col-12">
                                                                      <label class="form-label" for="editPlanoTotal' . $credito->id . '">Total</label>
                                                                      <input type="number" step="0.01" id="editPlanoTotal' . $credito->id . '" name="total" class="form-control" value="' . number_format((float) $credito->total, 2, '.', '') . '" required />
                                                                  </div>
                                                                  <div class="col-12 text-center">
                                                                      <button type="submit" class="btn btn-primary me-sm-3 me-1">Salvar</button>
                                                                      <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal" aria-label="Close">Cancelar</button>
                                                                  </div>
                                                              </form>
                                                          </div>
                                                      </div>
                                                  </div>
                                              </div>';

          return [
            'id' => $credito->id,
            'nome' => $credito->nome,
            'creditos' => $credito->creditos,
            'preco' => $credito->preco,
            'total' => $credito->total,
            'actions' => $actions . $modal
          ];
        });

      // Fetch user preferences for visible columns
      $userId = $user->id;
      $preferences = DB::table('user_client_preferences')
        ->where('user_id', $userId)
        ->where('table_name', 'creditos')
        ->value('visible_columns');

      $visibleColumns = json_decode($preferences, true) ?: [
        'id',
        'nome',
        'creditos',
        'preco',
        'total',
        'actions'
      ];

      // Filter the columns based on user preferences
      $filteredCreditos = $creditos->map(function ($credito) use ($visibleColumns) {
        return array_filter($credito, function ($key) use ($visibleColumns) {
          return in_array($key, $visibleColumns);
        }, ARRAY_FILTER_USE_KEY);
      });

      return response()->json([
        'rows' => $filteredCreditos,
        'total' => $totalCreditos
      ]);
    } else {
      // Usuário não está autenticado
      return response()->json(['error' => 'Usuário não autenticado'], 401);
    }
  } catch (\Exception $e) {
    Log::error('Erro ao acessar a listagem de créditos: ' . $e->getMessage());
    return response()->json(['error' => 'Erro ao acessar a listagem de créditos'], 500);
  }
}

public function store(Request $request)
{
  $creditos = $request->input('creditos');
  $preco_por_credito = $request->input('preco');
  $total = $creditos * $preco_por_credito;

  Revenda::create([
    'nome' => $request->input('nome'),
    'user_id' => auth()->id(),
    'creditos' => $creditos,
    'preco' => $preco_por_credito,
    'total' => $total,
  ]);

  return redirect()->route('revenda.index')->with('success', 'Plano de créditos criado com sucesso!');
}

public function update(Request $request, $id)
{
  $plano = Revenda::findOrFail($id);
  $plano->update([
    'nome' => $request->input('nome'),
    'creditos' => $request->input('creditos'),
    'preco' => $request->input('preco'),
    'total' => $request->input('creditos') * $request->input('preco'),
  ]);

  return redirect()->route('revenda.index')->with('success', 'Plano  atualizado com sucesso!');
}

public function destroy($id)
{
  $revenda = Revenda::findOrFail($id);
  $revenda->delete();

  return redirect()->route('revenda.index')->with('success', 'Plano deletados com sucesso!');
}

public function destroyMultiple(Request $request)
{
    $ids = $request->input('ids');

    if (empty($ids)) {
        return response()->json(['error' => true, 'message' => 'Nenhum Plano selecionado para exclusão.'], 400);
    }

    Revenda::whereIn('id', $ids)->delete();
    return response()->json(['error' => false, 'message' => 'Plano deletados com sucesso!']);
}
}