<div class="inventory-heading">
    <div>
        <a class="management-back-button btn btn-sm btn-outline-secondary" href="{{ route('records.index', 'products') }}">← Back to products</a>
        <p class="inventory-eyebrow mt-4">Inventory / Product #{{ $record->id }}</p>
        <h1>{{ $record->name }}</h1>
        <p class="text-muted mb-0">Review product information and available inventory.</p>
    </div>
    <a class="btn btn-primary" href="{{ route('records.edit', ['products', $record->id]) }}">Edit product</a>
</div>
<div class="inventory-detail-grid">
    <section class="inventory-photo-panel" aria-label="Product image">
        @if($record->image_path)
            <img src="{{ app(\App\Services\ImageService::class)->url($record->image_path) }}" alt="{{ $record->name }}">
        @else
            <div class="inventory-photo-empty">No product image yet</div>
        @endif
        <span class="inventory-status">{{ ucfirst($record->status) }}</span>
    </section>
    <div class="inventory-information">
        <div class="inventory-metrics">
            <div><span>Selling price</span><strong>₱{{ number_format($record->price, 2) }}</strong></div>
            <div><span>Total stock</span><strong>{{ $record->stock }} <small>units</small></strong></div>
        </div>
        <section class="inventory-panel">
            <h2>Product information</h2>
            <dl class="inventory-facts">
                <div><dt>Category</dt><dd>{{ $record->category?->name ?? 'Uncategorized' }}</dd></div>
                <div><dt>Seller</dt><dd>{{ $record->vendor?->name ?? 'Unassigned' }}</dd></div>
                <div><dt>Status</dt><dd>{{ ucfirst($record->status) }}</dd></div>
            </dl>
            <div class="inventory-description"><h3>Description</h3><p>{{ $record->description ?: 'No description added yet.' }}</p></div>
        </section>
        <section class="inventory-panel">
            <div class="d-flex justify-content-between align-items-center gap-2 mb-3"><h2 class="mb-0">Sizes & colors</h2><span class="text-muted small">Active inventory</span></div>
            @if($record->variants->where('active', true)->isNotEmpty())
                <div class="table-responsive"><table class="table align-middle mb-0 inventory-variant-table"><thead><tr><th>Size</th><th>Color</th><th class="text-end">Available</th></tr></thead><tbody>
                @foreach($record->variants->where('active', true) as $variant)
                    <tr><td class="fw-semibold">{{ $variant->size }}</td><td>{{ $variant->color }}</td><td class="text-end">{{ $variant->stock }} <span class="text-muted small">units</span></td></tr>
                @endforeach
                </tbody></table></div>
            @else
                <p class="text-muted mb-0">Standard product · no size or color selection required.</p>
            @endif
        </section>
    </div>
</div>
