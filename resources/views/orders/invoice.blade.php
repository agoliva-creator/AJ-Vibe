@extends('layout')
@section('title', 'Invoice #'.$order->id.' · AJ Vibe')
@section('content')
<div class="no-print invoice-toolbar"><a class="order-back-link mb-0" href="{{ route('orders.show',$order) }}">← Back to order</a><div class="d-flex gap-2"><a class="btn btn-outline-secondary" href="{{ route('orders.pdf',$order) }}">Download PDF</a><button class="btn btn-primary" type="button" onclick="window.print()">Print invoice</button></div></div><div class="invoice-sheet">
@include('orders.invoice-content')
</div>
@endsection
