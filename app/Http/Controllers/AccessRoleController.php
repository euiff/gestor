<?php
// app/Http/Controllers/AccessRoleController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Permission;

class AccessRoleController extends Controller
{
  public function index()
  {
    $permissions = Permission::all();
    return view('app-access-roles', compact('permissions'));
  }
}
