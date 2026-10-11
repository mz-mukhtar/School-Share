<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('users')
            ->where(function (Builder $query): void {
                $query->whereNull('username')->orWhereRaw('TRIM(username) = ?', ['']);
            })
            ->select('id')
            ->chunkById(100, function (Collection $users): void {
                foreach ($users as $user) {
                    $base = 'user_'.$user->id;
                    $username = $base;
                    $suffix = 1;

                    while (DB::table('users')->where('username', $username)->exists()) {
                        $username = $base.'_'.$suffix++;
                    }

                    DB::table('users')->where('id', $user->id)->update(['username' => $username]);
                }
            });
    }

    /**
     * Keep assigned route identities on rollback; missing identities cannot be restored safely.
     */
    public function down(): void {}
};
