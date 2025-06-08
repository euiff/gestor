<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cliente;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ClientesImport;
use App\Exports\ClientesExport;
use Exception;
use Illuminate\Support\Facades\Auth;
use App\Models\PlanoRenovacao;

class ClienteController extends Controller
{
    public function __construct()
    {
        // Aplicar middleware de autenticação
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user();

        // Verifica se o usuário é um administrador
        if ($user->role->name === 'admin') {
            // Lógica para exibir todos os clientes
            $clientes = Cliente::all();
            $planos = PlanoRenovacao::all();
        } else {
            // Lógica para exibir apenas os clientes do usuário autenticado
            $clientes = Cliente::where('user_id', $user->id)->get();
            $planos_revenda = PlanoRenovacao::where('user_id', $user->id)->get();
        }

        return view('clientes.index', compact('clientes'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt,xlx,xls,xlsx',
        ]);

        try {
            $user = Auth::user();

            // Verificar se o arquivo foi carregado corretamente
            if (!$request->hasFile('file')) {
                return redirect()->back()->with('error', 'Nenhum arquivo foi carregado.');
            }

            // Obter o caminho do arquivo
            $file = $request->file('file');
            $path = $file->getRealPath();

            // Verificar se o caminho do arquivo é válido
            if (!file_exists($path)) {
                return redirect()->back()->with('error', 'O arquivo não foi encontrado.');
            }

            // Contar o número de clientes no arquivo de importação
            try {
                $extension = $file->getClientOriginalExtension();

                // Passar o tipo correto para o Laravel Excel
                $readerType = $this->getReaderType($extension);
                $data = Excel::toArray([], $path, null, $readerType);
                $numClientes = count($data[0]);
            } catch (Exception $e) {
                return redirect()->back()->with('error', 'Erro ao ler o arquivo.');
            }

            // Buscar o plano do usuário
            $planoUsuario = PlanoRenovacao::find($user->plano_id);

            // Verificar se o plano tem limite e se o limite foi atingido
            if ($planoUsuario->limite > 0 && $user->limite < $numClientes) {
                return redirect()->back()->with('error', 'Você atingiu o limite máximo de clientes permitidos pelo seu plano.');
            }

            // Descontar do limite do usuário
            if ($planoUsuario->limite > 0) {
                $user->limite -= $numClientes;
                $user->save();
            }

            // Importar os clientes
            Excel::import(new ClientesImport($user->id), $file);

            return redirect()->back()->with('success', 'Clientes importados com sucesso!');
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();

            foreach ($failures as $failure) {
                // Erro de validação
            }

            return redirect()->back()->with('warning', 'Erro de validação ao importar o arquivo.');
        } catch (Exception $e) {
            return redirect()->back()->with('warning', $e->getMessage());
        }
    }

    private function getReaderType($extension)
    {
        switch (strtolower($extension)) {
            case 'csv':
                return \Maatwebsite\Excel\Excel::CSV;
            case 'xls':
                return \Maatwebsite\Excel\Excel::XLS;
            case 'xlsx':
                return \Maatwebsite\Excel\Excel::XLSX;
            case 'txt':
                return \Maatwebsite\Excel\Excel::TSV;
            default:
                throw new Exception('Tipo de arquivo não suportado: ' . $extension);
        }
    }

    public function export(Request $request)
    {
        $extension = $request->input('extension', 'xlsx'); // Padrão para XLSX se não for fornecido

        $fileName = 'clientes.' . $extension;

        switch ($extension) {
            case 'csv':
                return Excel::download(new ClientesExport, $fileName, \Maatwebsite\Excel\Excel::CSV);
            case 'txt':
                return Excel::download(new ClientesExport, $fileName, \Maatwebsite\Excel\Excel::TSV);
            case 'xls':
                return Excel::download(new ClientesExport, $fileName, \Maatwebsite\Excel\Excel::XLS);
            case 'xlsx':
                return Excel::download(new ClientesExport, $fileName, \Maatwebsite\Excel\Excel::XLSX);
            default:
                return Excel::download(new ClientesExport, $fileName, \Maatwebsite\Excel\Excel::XLSX);
        }
    }
}
