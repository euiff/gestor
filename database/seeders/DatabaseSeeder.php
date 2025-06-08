<?php
// database/seeders/DatabaseSeeder.php
use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
  public function run()
  {
    // Criar permissões
    $permissions = [
      'gerenciar-usuarios',
      'gerenciar-assinaturas',
      'visualizar-relatorios',
      'criar-postagens',
      'editar-postagens',
      'excluir-postagens'
    ];
    foreach ($permissions as $permission) {
      Permission::create(['name' => $permission]);
    }

    // Criar papéis
    $adminRole = Role::create(['name' => 'admin']);
    $userRole = Role::create(['name' => 'usuario']);

    // Atribuir permissões aos papéis
    $adminRole->permissions()->attach(Permission::all());
    $userRole->permissions()->attach(Permission::whereIn('name', ['visualizar-relatorios', 'criar-postagens'])->get());

    // Criar usuário admin e atribuir papel
    $admin = User::create([
      'name' => 'admin',
      'email' => 'admin@example.com',
      'password' => Hash::make('787890'),
    ]);
    $admin->roles()->attach($adminRole);

    // Criar usuário padrão e atribuir papel
    $user = User::create([
      'name' => 'usuario',
      'email' => 'usuario@example.com',
      'password' => Hash::make('senhadousuario'),
    ]);
    $user->roles()->attach($userRole);
  }
}
