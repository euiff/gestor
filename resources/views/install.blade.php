<!-- filepath: /resources/views/install.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação do Servidor</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #333;
        }
        form {
            margin-top: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
        }
        input, select, button {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            background-color: #28a745;
            color: #fff;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background-color: #218838;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        .alert-info {
            color: #0c5460;
            background-color: #d1ecf1;
            border-color: #bee5eb;
        }
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Selecione o Tipo de Servidor para Instalação</h1>
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if (session('info'))
            <div class="alert alert-info">
                {{ session('info') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        <form action="{{ route('install.server') }}" method="POST">
            @csrf
            <label for="server_type">Tipo de Servidor:</label>
            <select name="server_type" id="server_type" required>
                <option value="xtreamui">XTREAM UI</option>
                <option value="streamcreed">STREAMCREED</option>
                <option value="nxt">NXT</option>
            </select>

            <label for="url">URL da API:</label>
            <input type="text" name="url" id="url" required>

            <label for="port">Porta:</label>
            <input type="text" name="port" id="port" required>

            <button type="submit">Instalar</button>
        </form>

        <form action="{{ route('test.connection') }}" method="POST">
            @csrf
            <h2>Testar Conexão com a API</h2>
            <label for="test_url">URL da API:</label>
            <input type="text" name="test_url" id="test_url" required>

            <label for="test_port">Porta:</label>
            <input type="text" name="test_port" id="test_port" required>

            <label for="test_token">Token da API:</label>
            <input type="text" name="test_token" id="test_token" required>

            <button type="submit">Testar Conexão</button>
        </form>
    </div>
</body>
</html>