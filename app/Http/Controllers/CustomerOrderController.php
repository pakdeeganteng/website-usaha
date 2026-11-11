<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use App\Services\OrderWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerOrderController extends Controller
{
    public function index(Request $request): View
    {
        $customer = $request->user();

        abort_unless($customer instanceof User, 403);

        $orders = $customer->orders()
            ->with(['items', 'statusHistories.changedBy'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(10);

        return view('orders.index', compact('orders'));
    }

    public function store(Request $request, Product $product, OrderWorkflowService $orderWorkflow): RedirectResponse
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_whatsapp' => ['required', 'string', 'max:30'],
            'quantity' => ['required', 'integer', 'min:1'],
            'pickup_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'pickup_time' => ['required', 'date_format:H:i'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $customer = $request->user();

        abort_unless($customer instanceof User, 403);

        $order = $orderWorkflow->placeOrder($customer, $product, $validated);

        return redirect()
            ->route('orders.index')
            ->with('status', 'Pesanan '.$order->transaction_code.' berhasil dikirim dan menunggu konfirmasi.');
    }
}
