<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ManagementService
{
    public function __construct(private ImageService $images) {}

    public function save(Model $record, array $data, User $actor): Model
    {
        $file = $data['image'] ?? $data['avatar'] ?? null;
        unset($data['image'],$data['avatar']);
        $variants = $data['variants'] ?? null;
        unset($data['variants']);
        $field = $record instanceof Product ? 'image_path' : 'avatar_path';
        $oldImage = $record->getAttribute($field);
        $newImage = $file instanceof UploadedFile ? $this->images->store($file, $record instanceof Product ? 'products' : 'avatars') : null;
        if ($newImage) {
            $data[$field] = $newImage;
        }
        try {
            DB::transaction(function () use ($record, $data, $actor, &$oldImage, $field, $variants): void {
                if ($record instanceof User) {
                    User::where('role', 'admin')->orderBy('id')->lockForUpdate()->get();
                    if ($record->exists) {
                        User::whereKey($record->id)->lockForUpdate()->firstOrFail();
                        $record->refresh();
                        $oldImage = $record->getAttribute($field);
                    }
                    if ($record->exists && $record->role === 'admin' && ($data['role'] !== 'admin' || ! $data['active']) && User::where('role', 'admin')->where('active', true)->count() <= 1) {
                        throw ValidationException::withMessages(['role' => 'Keep at least one active administrator.']);
                    }
                    $record->role = $data['role'];
                    $record->active = $data['active'];
                    unset($data['role'],$data['active']);
                    if (empty($data['password'])) {
                        unset($data['password']);
                    }
                }
                if ($record instanceof Product) {
                    if ($record->exists) {
                        Product::whereKey($record->id)->lockForUpdate()->firstOrFail();
                        $record->refresh();
                        Gate::forUser($actor)->authorize('update', $record);
                        $oldImage = $record->getAttribute($field);
                    }
                    if ($actor->isVendor()) {
                        $data['vendor_id'] = $actor->id;
                    }
                }
                if ($record instanceof Category && empty($data['slug'])) {
                    $data['slug'] = (Str::slug($data['name']) ?: 'category').'-'.Str::lower(Str::random(8));
                }
                $record->fill($data)->save();
                if ($record instanceof Product && $variants !== null) {
                    $seen = [];
                    foreach ($variants as $variant) {
                        $key = strtolower($variant['size'].'|'.$variant['color']);
                        if (isset($seen[$key])) {
                            throw ValidationException::withMessages(['variants' => 'Each size/color combination must be unique.']);
                        }
                        $seen[$key] = true;
                    }
                    if ($record->variants()->exists() && count($variants) === 0) {
                        throw ValidationException::withMessages(['variants' => 'Keep at least one size/color for a variant product.']);
                    }
                    $record->variants()->update(['active' => false]);
                    foreach ($variants as $variant) {
                        $record->variants()->updateOrCreate(['size' => $variant['size'], 'color' => $variant['color']], ['stock' => $variant['stock'], 'active' => true]);
                    }
                    if ($record->variants()->exists()) {
                        $record->update(['stock' => $record->variants()->sum('stock')]);
                    }
                }
                if ($record instanceof User && (! $record->active || $record->wasChanged(['role', 'password', 'email']))) {
                    $record->tokens()->delete();
                }
                if ($record instanceof Product && $variants === null && $record->variants()->exists()) {
                    $record->update(['stock' => $record->variants()->sum('stock')]);
                }
            }, 3);
        } catch (\Throwable $exception) {
            $this->images->delete($newImage);
            if ($exception instanceof QueryException && in_array((string) $exception->getCode(), ['23000', '23505'])) {
                throw ValidationException::withMessages(['record' => 'A unique value is already in use.']);
            }
            throw $exception;
        }
        if ($newImage) {
            $this->images->delete($oldImage);
        }

        return $record;
    }

    public function delete(Model $record, User $actor): void
    {
        $avatar = $record instanceof User ? $record->avatar_path : null;
        try {
            DB::transaction(function () use ($record, $actor): void {
                if ($record instanceof Product) {
                    Product::whereKey($record->id)->lockForUpdate()->firstOrFail();
                    $record->refresh();
                    Gate::forUser($actor)->authorize('delete', $record);
                }
                if ($record instanceof User) {
                    Gate::forUser($actor)->authorize('delete', $record);
                    User::where('role', 'admin')->orderBy('id')->lockForUpdate()->get();
                    $record->refresh();
                    if ($record->role === 'admin' && $record->active && User::where('role', 'admin')->where('active', true)->count() <= 1) {
                        throw ValidationException::withMessages(['user' => 'The last active admin cannot be deleted.']);
                    }
                    $record->tokens()->delete();
                    $record->notifications()->delete();
                    DB::table('orders')->where('user_id', $record->id)->update(['user_id' => null]);
                    DB::table('order_items')->where('vendor_id', $record->id)->update(['vendor_id' => null]);
                    DB::table('products')->where('vendor_id', $record->id)->update(['vendor_id' => null, 'status' => 'draft']);
                    $record->reviews()->delete();
                    DB::table('password_reset_tokens')->where('email', $record->email)->delete();
                    if (config('session.driver') === 'database') {
                        DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))->where('user_id', $record->id)->delete();
                    }
                    $record->forceDelete();

                    return;
                }
                $record->delete();
            }, 3);
        } catch (QueryException $exception) {
            if (! in_array((string) $exception->getCode(), ['23000', '23503'])) {
                throw $exception;
            }
            throw ValidationException::withMessages(['record' => 'This record is still referenced by another record.']);
        }
        if ($avatar) {
            $this->images->delete($avatar);
        }
    }
}
