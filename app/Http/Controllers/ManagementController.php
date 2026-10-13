<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Http\Requests\CouponRequest;
use App\Http\Requests\ProductRequest;
use App\Http\Requests\UserRequest;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\User;
use App\Services\ManagementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ManagementController extends Controller
{
    public const MODULES = ['users' => User::class, 'categories' => Category::class, 'products' => Product::class, 'coupons' => Coupon::class];

    public const FIELDS = ['users' => ['name', 'email', 'password', 'role', 'active'], 'categories' => ['name', 'slug', 'description'], 'products' => ['name', 'description', 'price', 'stock', 'category_id', 'vendor_id', 'status'], 'coupons' => ['code', 'type', 'value', 'minimum_cents', 'usage_limit', 'active', 'starts_at', 'expires_at']];

    public function __construct(private ManagementService $service) {}

    private function model(string $entity): string
    {
        abort_unless(isset(self::MODULES[$entity]), 404);

        return self::MODULES[$entity];
    }

    public function index(Request $request, string $entity): mixed
    {
        $class = $this->model($entity);
        Gate::authorize('viewAny', $class);
        $query = $class::query();
        if ($entity === 'categories') {
            $query->withCount('products')->with(['products' => fn ($products) => $products->latest()->orderByDesc('id')->limit(3)]);
        }
        if ($entity === 'products' && $request->user()->isVendor()) {
            $query->where('vendor_id', $request->user()->id);
        }
        $records = $query->latest()->orderByDesc('id')->paginate(15);

        return view($entity === 'categories' ? 'management.categories' : 'management.index', compact('entity', 'records'));
    }

    public function show(string $entity, int $id): mixed
    {
        $class = $this->model($entity);
        $record = $class::findOrFail($id);
        Gate::authorize('view', $record);
        if ($entity === 'categories') {
            $products = $record->products()->latest()->orderByDesc('id')->paginate(12);

            return view('management.category', compact('record', 'products'));
        }

        return view('management.show', compact('entity', 'record'));
    }

    public function form(Request $request, string $entity, ?int $id = null): mixed
    {
        $class = $this->model($entity);
        $record = $id ? $class::findOrFail($id) : new $class;
        Gate::authorize($id ? 'update' : 'create', $id ? $record : $class);
        $fields = self::FIELDS[$entity];
        if ($entity === 'products' && $request->user()->isVendor()) {
            $fields = array_values(array_diff($fields, ['vendor_id']));
        }
        $options = ['category_id' => Category::orderBy('name')->pluck('name', 'id'), 'vendor_id' => User::where('role', 'vendor')->where('active', true)->pluck('name', 'id'), 'role' => ['admin' => 'Admin', 'vendor' => 'Vendor', 'customer' => 'Customer'], 'status' => ['active' => 'Active', 'draft' => 'Draft'], 'type' => ['percent' => 'Percentage', 'fixed' => 'Fixed amount (cents)'], 'active' => [1 => 'Yes', 0 => 'No']];

        return view('management.form', compact('entity', 'record', 'fields', 'options'));
    }

    public function save(string $entity, ?int $id = null): mixed
    {
        $class = $this->model($entity);
        $record = $id ? $class::findOrFail($id) : new $class;
        Gate::authorize($id ? 'update' : 'create', $id ? $record : $class);
        $requestClass = match ($entity) {
            'users' => UserRequest::class,'categories' => CategoryRequest::class,'products' => ProductRequest::class,'coupons' => CouponRequest::class
        };
        $request = app($requestClass);
        $this->service->save($record, $request->validated(), $request->user());

        return redirect()->route('records.index', $entity)->with('success', 'Record saved.');
    }

    public function destroy(string $entity, int $id): mixed
    {
        $class = $this->model($entity);
        $record = $class::findOrFail($id);
        Gate::authorize('delete', $record);
        $this->service->delete($record, auth()->user());

        return redirect()->route('records.index', $entity)->with('success', 'Record deleted.');
    }
}
