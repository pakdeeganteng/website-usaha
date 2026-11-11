<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\OrderStatus;
use App\Services\OrderWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::enum(OrderStatus::class)],
        ]);

        $orders = Order::query()
            ->withCount('items')
            ->when(
                $validated['status'] ?? null,
                fn ($query, string $status) => $query->where('status', $status),
            )
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order): View
    {
        $order->load(['items', 'statusHistories.changedBy']);

        return view('admin.orders.show', compact('order'));
    }

    public function updateStatus(
        Request $request,
        Order $order,
        OrderWorkflowService $orderWorkflow,
    ): RedirectResponse {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(OrderStatus::class)],
            'reason' => [
                'nullable',
                'string',
                'max:1000',
                Rule::requiredIf($request->input('status') === OrderStatus::Rejected->value),
            ],
        ]);

        $administrator = $request->user();

        abort_unless($administrator instanceof User, 403);

        $changed = $orderWorkflow->changeStatus(
            $order,
            $administrator,
            OrderStatus::from($validated['status']),
            $validated['reason'] ?? null,
        );

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('status', $changed
                ? 'Status pesanan berhasil diperbarui.'
                : 'Status pesanan tidak berubah.');
    }
}
