@extends('layouts/layoutMaster')
@section('title', 'Sessions')

@section('content')
<h4 class="py-3 mb-2">
  <span class="text-muted fw-light">Admin /</span> Sessões Ativas
</h4>

<table class="table table-striped">
    <thead>
        <tr>
            <th>User ID</th>
            <th>IP Address</th>
            <th>Location</th>
            <th>Last Activity</th>
        </tr>
    </thead>
    <tbody>
        @foreach($sessions as $session)
        <tr>
             <td>{{ $session->user_name }}</td>
            <td>{{ $session->ip_address }}</td>
            <td>{{ $session->location }}</td>
            <td>{{ \Carbon\Carbon::createFromTimestamp($session->last_activity)->format('d/m/Y H:i:s') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection
