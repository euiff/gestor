<?php

namespace App\Http\Controllers\apps;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;

class AccessRoles extends Controller
{
  public function index()
  {

    $roles = Role::with('users')->get();
    return view('content.apps.app-access-roles', compact('roles'));
  }
}