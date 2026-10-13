<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('users')->whereNotNull('deleted_at')->orderBy('id')->chunkById(100, function ($users): void {
            foreach ($users as $user) {
                DB::transaction(function () use ($user): void {
                    DB::table('users')->where('id', $user->id)->whereNotNull('deleted_at')->update([
                        'email' => 'deleted-'.$user->id.'-'.Str::uuid().'@deleted.invalid',
                        'email_verified_at' => null,
                        'remember_token' => null,
                        'active' => false,
                    ]);
                    DB::table('personal_access_tokens')->where('tokenable_type', User::class)->where('tokenable_id', $user->id)->delete();
                });
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Released emails cannot be restored safely after another account claims them.
    }
};
