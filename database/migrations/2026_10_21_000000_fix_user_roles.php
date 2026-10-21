<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $buyerId = DB::table('roles')->where('slug', 'buyer')->value('id');
        
        // Если роли buyer еще нет, создаем её
        if (!$buyerId) {
            $buyerId = DB::table('roles')->insertGetId([
                'slug' => 'buyer',
                'name' => 'Покупатель',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Находим старую роль 'user'
        $userRoleId = DB::table('roles')->where('slug', 'user')->value('id');
        
        if ($userRoleId) {
            // Переносим всех, у кого была роль 'user', на роль 'buyer'
            DB::table('role_user')->where('role_id', $userRoleId)->update(['role_id' => $buyerId]);
            // Удаляем старую роль
            DB::table('roles')->where('id', $userRoleId)->delete();
        }

        // На всякий случай обновляем/создаем остальные роли
        DB::table('roles')->updateOrInsert(['slug' => 'seller'], ['name' => 'Продавец']);
        DB::table('roles')->updateOrInsert(['slug' => 'admin'], ['name' => 'Администратор']);
    }

    public function down(): void {}
};