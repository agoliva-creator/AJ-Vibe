<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Http\Requests\OrderStatusRequest;
use App\Http\Requests\TrackingRequest;
use App\Http\Resources\OrderResource;
use App\Mail\OrderConfirmation;
use App\Models\Order;
use App\Services\CheckoutService;
use App\Services\OrderService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;

class OrderController extends Controller
{
    public function __construct(private CheckoutService $checkout, private OrderService $orders) {}

    public function index(Request $request): mixed
    {
        $orders = Order::visibleTo($request->user())->with(['items' => fn ($query) => $request->user()->isVendor() ? $query->where('vendor_id', $request->user()->id) : $query])->latest()->orderByDesc('id')->paginate(15);

        if (! $request->is('api/*')) {
            $orders->getCollection()->load('items.product');
        }

        return $request->is('api/*') ? OrderResource::collection($orders) : view('orders.index', compact('orders'));
    }

    private function visibleItems(Request $request, Order $order): void
    {
        Gate::authorize('view', $order);
        $order->load(['items' => fn ($query) => $request->user()->isVendor() ? $query->where('vendor_id', $request->user()->id) : $query]);
    }

    public function show(Request $request, Order $order): mixed
    {
        $this->visibleItems($request, $order);

        if (! $request->is('api/*')) {
            $order->load('items.product');
        }

        return $request->is('api/*') ? new OrderResource($order) : view('orders.show', compact('order'));
    }

    public function store(CheckoutRequest $request): mixed
    {
        $order = $this->checkout->place($request->user(), $request->validated());

        return $request->is('api/*') ? (new OrderResource($order))->response()->setStatusCode(201) : redirect()->route('orders.show', $order)->with('success', 'Order placed. Confirmation email queued.');
    }

    public function update(OrderStatusRequest $request, Order $order): mixed
    {
        $order = $this->orders->fulfill($request->user(), $order, $request->validated());
        $this->visibleItems($request, $order);

        return $request->is('api/*') ? new OrderResource($order) : back()->with('success', 'Fulfillment updated.');
    }

    public function cancel(Request $request, Order $order): mixed
    {
        $order = $this->orders->cancel($request->user(), $order);

        return $request->is('api/*') ? new OrderResource($order) : back()->with('success', 'Order cancelled and stock restored.');
    }

    public function destroy(Request $request, Order $order): mixed
    {
        $this->orders->delete($request->user(), $order);

        return redirect()->route('orders.index')->with('success', 'Cancelled order removed from the active list.');
    }

    public function invoice(Request $request, Order $order): mixed
    {
        Gate::authorize('view', $order);
        abort_if($request->user()->isVendor(), 403, 'Invoices are available to the buyer and administrators.');
        $order->load('items');

        return view('orders.invoice', compact('order'));
    }

    public function tracking(TrackingRequest $request, Order $order): mixed
    {
        Gate::authorize('update', $order);
        $item = $order->items()->whereKey($request->validated('item_id'));
        if ($request->user()->isVendor()) {
            $item->where('vendor_id', $request->user()->id);
        }
        $item = $item->firstOrFail();
        abort_if($item->status === 'cancelled', 422, 'Cancelled items cannot be tracked.');
        $item->forceFill($request->safe()->only(['courier', 'tracking_number']))->save();

        return $request->is('api/*') ? response()->json(['message' => 'Tracking updated.']) : back()->with('success', 'Tracking updated.');
    }

    public function pdf(Request $request, Order $order): mixed
    {
        Gate::authorize('view', $order);
        abort_if($request->user()->isVendor(), 403);
        $order->load('items');

        return Pdf::loadView('orders.pdf', compact('order'))->download('invoice-'.$order->id.'.pdf');
    }

    public function email(Request $request, Order $order): mixed
    {
        Gate::authorize('view', $order);
        abort_unless($request->user()->isAdmin() || $order->user_id === $request->user()->id, 403);
        Mail::to($order->buyer_email)->queue((new OrderConfirmation($order))->afterCommit());

        return back()->with('success', 'Order confirmation email queued.');
    }
}
