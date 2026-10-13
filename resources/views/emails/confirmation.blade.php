<h1>Order confirmation #{{ $order->id }}</h1><p>Hello {{ $order->buyer_name }},</p><p>Your order has been placed. Payment is due on delivery.</p><ul>
@foreach($order->items as $item)
<li>{{ $item->product_name }} × {{ $item->quantity }} — PHP {{ \App\Services\Money::decimal($item->unit_price_cents*$item->quantity) }}</li>
@endforeach
</ul><p>Total: PHP {{ \App\Services\Money::decimal($order->total_cents) }}</p><p><a href="{{ route('orders.show',$order) }}">Track your order</a></p>