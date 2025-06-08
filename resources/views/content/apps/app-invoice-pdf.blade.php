<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $payment->id }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .invoice-header, .invoice-footer {
            text-align: center;
            margin-bottom: 20px;
        }
        .invoice-details {
            width: 100%;
            margin-bottom: 20px;
        }
        .invoice-details th, .invoice-details td {
            padding: 10px;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="invoice-header">
        <h1>{{ $empresa->company_name }}</h1>
        <p>WhatsApp da Empresa: {{ $empresa->company_whatsapp }}</p>
    </div>

    <div class="invoice-details">
        <table>
            <tr>
                <th>ID do Pagamento</th>
                <td>{{ $payment->id }}</td>
            </tr>
            <tr>
                <th>Data de Emissão</th>
                <td>{{ $payment->created_at ? $payment->created_at->format('d M, Y') : 'N/A' }}</td>
            </tr>
            <tr>
                <th>Data de Vencimento</th>
                <td>{{ $payment->due_date ? $payment->due_date->format('d M, Y') : 'N/A' }}</td>
            </tr>
            <tr>
                <th>Cliente</th>
                <td>{{ $cliente->nome }}</td>
            </tr>
            <tr>
                <th>WhatsApp do Cliente</th>
                <td>{{ $cliente->whatsapp }}</td>
            </tr>
            <tr>
                <th>Plano</th>
                <td>{{ $plano->nome }}</td>
            </tr>
            <tr>
                <th>Preço</th>
                <td>{{ $plano->preco }}</td>
            </tr>
            <tr>
                <th>Duração</th>
                <td>{{ $plano->duracao }} dias</td>
            </tr>
            <tr>
                <th>Valor</th>
                <td>{{ $payment->valor }}</td>
            </tr>
            <tr>
                <th>Status</th>
                <td>
                    @if($payment->status == 'paid')
                        Pago
                    @elseif($payment->status == 'pending')
                        Pendente
                    @elseif($payment->status == 'fail')
                        Falhou
                    @endif
                </td>
            </tr>
            <tr>
                <th>Nota</th>
                <td>{{ $payment->note }}</td>
            </tr>
        </table>
    </div>

    <div class="invoice-footer">
        <p>Obrigado por fazer negócios conosco!</p>
    </div>
</body>
</html>
