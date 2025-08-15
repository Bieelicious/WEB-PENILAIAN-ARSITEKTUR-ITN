<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Support\Facades\App;
use Spatie\Permission\PermissionRegistrar;

class ShieldSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $rolesWithPermissions = '[{"name":"lecturer","guard_name":"web","permissions":["view_assessment","view_any_assessment","create_assessment","update_assessment","restore_assessment","restore_any_assessment","replicate_assessment","reorder_assessment","delete_assessment","delete_any_assessment","force_delete_assessment","force_delete_any_assessment"]},{"name":"super_admin","guard_name":"web","permissions":["view_assessment","view_any_assessment","create_assessment","update_assessment","restore_assessment","restore_any_assessment","replicate_assessment","reorder_assessment","delete_assessment","delete_any_assessment","force_delete_assessment","force_delete_any_assessment","view_role","view_any_role","create_role","update_role","delete_role","delete_any_role","view_student","view_any_student","create_student","update_student","restore_student","restore_any_student","replicate_student","reorder_student","delete_student","delete_any_student","force_delete_student","force_delete_any_student","view_user","view_any_user","create_user","update_user","restore_user","restore_any_user","replicate_user","reorder_user","delete_user","delete_any_user","force_delete_user","force_delete_any_user","view_group","view_any_group","create_group","update_group","restore_group","restore_any_group","replicate_group","reorder_group","delete_group","delete_any_group","force_delete_group","force_delete_any_group","page_MyProfilePage","page_ManageWebsite","widget_StudentOverview"]}, {"name":"lecturer","guard_name":"web","permissions":["view_assessment","view_any_assessment","create_assessment","update_assessment","restore_assessment","restore_any_assessment","replicate_assessment","reorder_assessment","delete_assessment","delete_any_assessment","force_delete_assessment","force_delete_any_assessment"]}]';
        $directPermissions = '[]';

        static::makeRolesWithPermissions($rolesWithPermissions);
        static::makeDirectPermissions($directPermissions);

        $this->command->info('Shield Seeding Completed.');
    }

    protected static function makeRolesWithPermissions(string $rolesWithPermissions): void
    {
        if (!blank($rolePlusPermissions = json_decode($rolesWithPermissions, true))) {
            /** @var Model $roleModel */
            $roleModel = Utils::getRoleModel();
            /** @var Model $permissionModel */
            $permissionModel = Utils::getPermissionModel();

            if (App::environment('demo')) {
                $rolePlusPermissions[] = [
                    'name' => 'demo',
                    'guard_name' => 'web',
                    'permissions' => ["view_assessment", "view_any_assessment", "create_assessment", "update_assessment", "restore_assessment", "restore_any_assessment", "replicate_assessment", "reorder_assessment", "delete_assessment", "delete_any_assessment", "force_delete_assessment", "force_delete_any_assessment", "create_role", "update_role", "delete_role", "delete_any_role", "view_student", "view_any_student", "create_student", "update_student", "restore_student", "restore_any_student", "replicate_student", "reorder_student", "delete_student", "delete_any_student", "force_delete_student", "force_delete_any_student", "view_user", "view_any_user", "create_user"],
                ];
            }

            foreach ($rolePlusPermissions as $rolePlusPermission) {
                $role = $roleModel::firstOrCreate([
                    'name' => $rolePlusPermission['name'],
                    'guard_name' => $rolePlusPermission['guard_name'],
                ]);

                if (!blank($rolePlusPermission['permissions'])) {
                    $permissionModels = collect($rolePlusPermission['permissions'])
                        ->map(fn($permission) => $permissionModel::firstOrCreate([
                            'name' => $permission,
                            'guard_name' => $rolePlusPermission['guard_name'],
                        ]))
                        ->all();

                    $role->syncPermissions($permissionModels);
                }
            }
        }
    }

    public static function makeDirectPermissions(string $directPermissions): void
    {
        if (!blank($permissions = json_decode($directPermissions, true))) {
            /** @var Model $permissionModel */
            $permissionModel = Utils::getPermissionModel();

            foreach ($permissions as $permission) {
                if ($permissionModel::whereName($permission)->doesntExist()) {
                    $permissionModel::create([
                        'name' => $permission['name'],
                        'guard_name' => $permission['guard_name'],
                    ]);
                }
            }
        }
    }
}
